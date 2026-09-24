<?php


/**
 * Съответствие на обекти между две bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2020 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Съответствие на обекти между две bgERP системи
 */
class sync_Map extends core_Manager
{
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'debug';
    
    
    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'debug';


    /**
     * Кой може да разглежда съответствията?
     */
    public $canList = 'sync, admin';
    
    
    /**
     * Добавяне на плъгини
     */
    public $loadList = 'plg_Sorting, plg_RowTools2, sync_Wrapper';
    
    
    /**
     * Заглавие
     */
    public $title = "Съответствия между две bgERP системи";
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Масив с информация за импортираните обекти
     */
    public static $imported = array();


    /**
     * Брой реални грешки при запис в текущия import
     */
    public static $importErrors = 0;


    /**
     * Отделни marker-и за рекурсивен import в ход и окончателен неуспех
     */
    const IMPORT_IN_PROGRESS = '__sync_import_in_progress__';
    const IMPORT_FAILED = '__sync_import_failed__';
    const MAX_REMOTE_ID = 2147483647;

    /** Рекурсивният запис е добавен или вече присъства в payload-а */
    const EXPORT_INCLUDED = 1;

    /** Рекурсивният запис е изключен от export policy */
    const EXPORT_FILTERED = 0;

    /** Рекурсивният запис липсва и трябва да остане fail-closed marker */
    const EXPORT_MISSING = -1;

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('classId', 'class(interface=core_ManagerIntf)', 'caption=Клас');
        $this->FLD('remoteId', 'int', 'caption=Отдалечено id');
        $this->FLD('localId', 'int', 'caption=Локално id');

        $this->setDbUnique('classId,remoteId');
    }
    
    
    /**
     * Експортира в резултата един запис
     *
     * @param string|core_Mvc $class
     * @param int|stdClass    $id
     * @param array           $res
     * @param core_Mvc        $controller
     * @param stdClass|null   $exportState Вътрешен journal за rollback на филтриран клон
     *
     * @return int Един от EXPORT_INCLUDED, EXPORT_FILTERED и EXPORT_MISSING
     */
    public static function exportRec($class, $id, &$res, $controller, $exportState = null)
    {
        $mvc = cls::get($class);

        if ($exportState === null) {
            $exportState = (object) array('added' => array());
        }

        $idInt = is_object($id) ? $id->id : $id;

        // Вече експортираните обекти и тези със специални id-та не се експортират
        if ($idInt <= 0) {

            return self::EXPORT_INCLUDED;
        }
        if (isset($res[$mvc->className]) &&
            is_array($res[$mvc->className]) &&
            array_key_exists($idInt, $res[$mvc->className])) {

            return $res[$mvc->className][$idInt] === false
                ? self::EXPORT_MISSING
                : self::EXPORT_INCLUDED;
        }

        $checkpoint = count($exportState->added);

        if(is_object($id)) {
            $rec = $res[$mvc->className][$idInt] = clone($id);
        } else {
            $rec = $res[$mvc->className][$idInt] = $mvc->fetch($idInt);
        }

        $id = $idInt;
        $exportState->added[] = array($mvc->className, $id);

        // Само реално липсващ запис оставя false marker, за да остане import-ът
        // fail-closed при непълен payload.
        if (!$rec || !is_object($rec)) {
            $res[$mvc->className][$id] = false;

            return self::EXPORT_MISSING;
        }

        // Policy отказът е нормално pruning събитие, а не повреден payload.
        if (!sync_Settings::canExportRecord($mvc->className, $rec)) {
            self::rollbackExportBranch($res, $exportState, $checkpoint);

            return self::EXPORT_FILTERED;
        }

        if ($mvc->className == 'core_Users') {
            $pRec = crm_Profiles::fetch(array("#userId = '[#1#]'", $idInt));
            if ($pRec) {
                $status = self::exportRec('crm_Profiles', $pRec, $res, $controller, $exportState);
                if ($status === self::EXPORT_FILTERED) {
                    self::rollbackExportBranch($res, $exportState, $checkpoint);

                    return self::EXPORT_FILTERED;
                }
            }
        }
        
        // Фикс, ако е параметъра е файл
        if (($mvc->className == 'cat_products_Params') && ($rec->paramId)) {
            $cParRec = cat_Params::fetch($rec->paramId);
            if ($cParRec) {
                $Driver = cat_Params::getDriver($rec->paramId);
                if ($Driver) {
                    $dType = $Driver->getType($rec->paramId);
                    if ($dType && ($dType instanceof fileman_FileType)) {
                        try {
                            $status = self::exportRec('cat_Params', $rec->paramId, $res, $controller, $exportState);
                            if ($status === self::EXPORT_FILTERED) {
                                self::rollbackExportBranch($res, $exportState, $checkpoint);

                                return self::EXPORT_FILTERED;
                            }
                            $rec->__paramValue = fileman_Download::getDownloadUrl($rec->paramValue);
                            $rec->__paramId = $rec->paramId;
                        } catch (core_exception_Expect $e) {
                            $rec->paramValue = null;
                        }
                    }
                }
            }
        }
        
        $fields = $mvc->selectFields("#kind == 'FLD'");
        foreach ($fields as $name => $fRec) {
            foreach (array($mvc->className . '::' . $name, '*::' . $name) as $fKey) {
                if (array_key_exists($fKey, $controller->fixedExport)) {
                    if (isset($controller->fixedExport[$fKey])) {
                        $funcArr = explode('::', $controller->fixedExport[$fKey]);
                        call_user_func_array(
                            array(cls::get($funcArr[0]), $funcArr[1] . 'Export'),
                            array(&$rec, $name, $fRec, &$res, $controller, $exportState)
                        );
                    } else {
                        $rec->{$name} = $controller->fixedExport[$fKey];
                    }
                }
            }
            
            if ($rec->{$name} === null) {
                unset($rec->{$name});
            }
            
            if (array_key_exists($mvc->className, $controller->mapClass)) {
                $mapClsFieldArr = $controller->mapClass[$mvc->className];
                $mapFieldRec = new stdClass();
                foreach ($mapClsFieldArr as $mapFName) {
                    $mapFieldRec->{$mapFName} = $rec->{$mapFName};
                }
                
                $res[$mvc->className][$id] = $mapFieldRec;
                
                break;
            }

            if ($fRec->type instanceof type_CustomKey) {
                continue;
            }

            if ($fRec->type instanceof fileman_FileType) {
                try {
                    $rec->{$name} = fileman_Download::getDownloadUrl($rec->{$name});
                } catch (core_exception_Expect $e) {
//                     wp($e);
                    $rec->{$name} = null;
                }
                
            } elseif ($fRec->type instanceof fileman_type_Files && !empty($rec->{$name})) {
                $kArr = keylist::toArray($rec->{$name});
                $kArrN = array();
                foreach ($kArr as $fId) {
                    $fn = fileman::idToFh($fId);
                    try {
                        $kArrN[] = fileman_Download::getDownloadUrl($fn);
                    } catch (core_exception_Expect $e) {
//                         wp($e);
                    }
                }
                $rec->{$name} = $kArrN;
            } elseif ($fRec->type instanceof type_Key || $fRec->type instanceof type_Key2) {
                $kMvc = $fRec->type->params['mvc'];
                if (is_numeric($rec->{$name})) {
                    if ($uf = $controller->globalUniqKeys[$kMvc]) {
                        $kMvc = cls::get($kMvc);
                        $rec->{$name} = $kMvc->fetchField($rec->{$name}, $uf);
                    } else {
                        $status = self::exportRec($kMvc, $rec->{$name}, $res, $controller, $exportState);
                        if ($status === self::EXPORT_FILTERED) {
                            if (empty($fRec->mandatory) && empty($fRec->notNull)) {
                                unset($rec->{$name});

                                continue;
                            }

                            self::rollbackExportBranch($res, $exportState, $checkpoint);

                            return self::EXPORT_FILTERED;
                        }
                    }
                }
            } elseif (($fRec->type instanceof type_Keylist) || is_subclass_of($fRec->type, 'type_Keylist')) {
                $kMvc = $fRec->type->params['mvc'];
                if (preg_match('/\\|[0-9\\|]+\\|/', $rec->{$name})) {
                    $kArr = keylist::toArray($rec->{$name});
                    if ($uf = $controller->globalUniqKeys[$kMvc]) {
                        $kMvc = cls::get($kMvc);
                        $kArrN = array();
                        foreach ($kArr as $key) {
                            $kArrN[] = $kMvc->fetchField($key, $uf);
                        }
                        $rec->{$name} = $kArrN;
                    } else {
                        $hasFiltered = false;
                        foreach ($kArr as $key => $keyId) {
                            $status = self::exportRec($kMvc, $keyId, $res, $controller, $exportState);
                            if ($status === self::EXPORT_FILTERED) {
                                unset($kArr[$key]);
                                $hasFiltered = true;
                            }
                        }
                        if ($hasFiltered) {
                            $rec->{$name} = keylist::fromArray($kArr);
                        }
                    }
                }
            } elseif ($rec->{$name} > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('saoParentId', 'saoRelative'))) {
                $status = self::exportRec($class, $rec->{$name}, $res, $controller, $exportState);
                if ($status === self::EXPORT_FILTERED) {
                    self::rollbackExportBranch($res, $exportState, $checkpoint);

                    return self::EXPORT_FILTERED;
                }
            }
        }

        if ($expArr = $controller->exportAlso[$mvc->className]) {
            foreach ($expArr as $clsArr) {
                foreach ($clsArr as $cls => $field) {
                    $dMvc = cls::get($cls);
                    if (strpos($field, '|')) {
                        list($cField, $oField) = explode('|', $field);
                        $cond = "#{$oField} = {$id} AND #{$cField} = " . core_Classes::getId($mvc);
                    } else {
                        $type = $dMvc->getFieldType($field);
                        expect($type->params['mvc'] == $mvc->className, $field, $type);
                        if (($type instanceof type_Key) || ($type instanceof type_Key2)) {
                            $cond = "#{$field} = {$id}";
                        } elseif ($type instanceof type_Keylist) {
                            $cond = "#{$field} LIKE '%|{$id}|%'";
                        } else {
                            bp($type, $field);
                        }
                    }
                 
                    $dQuery = $dMvc->getQuery();
                                 
                    while ($dRec = $dQuery->fetch($cond)) {
                        self::exportRec($dMvc, $dRec->id, $res, $controller, $exportState);
                    }
                }
            }
        }

        return self::EXPORT_INCLUDED;
    }


    /**
     * Премахва всички записи, добавени само през вече филтриран клон.
     */
    protected static function rollbackExportBranch(&$res, $exportState, $checkpoint)
    {
        while (count($exportState->added) > $checkpoint) {
            list($class, $id) = array_pop($exportState->added);
            unset($res[$class][$id]);
            if (empty($res[$class])) {
                unset($res[$class]);
            }
        }
    }


    /**
     * Импортира един запис и рекурсивните му зависимости
     *
     * @param string|core_Mvc $class
     * @param int             $id
     * @param array           $res
     * @param core_Mvc        $controller
     * @param bool|null       $update
     * @param string|null     $source Контекстът, от който е поискана зависимостта
     *
     * @return int ID на импортирания обект
     */
    public static function importRec($class, $id, &$res, $controller, $update = null, $source = null)
    {
        //log_System::add('sync_Map', "$class::$id");
        core_App::setTimeLimit(300);
        core_Debug::$isLogging = false;

        $update = self::shouldUpdate($update);

        $classKey = is_object($class) ? $class->className : (string) $class;
        // Системният потребител е служебно ID и не присъства в export payload-а.
        if ($classKey === 'core_Users' && ($id === -1 || $id === '-1')) {

            return core_Users::SYSTEM_USER;
        }

        $normalizedId = self::normalizeRemoteId($id);
        if ($normalizedId === false) {
            self::$importErrors++;
            $message = "Невалидно remote id в sync payload за клас {$classKey}";
            if ($source) {
                $message .= "; зависимост от {$source}";
            }
            self::logErr($message);

            return 0;
        }
        $id = $normalizedId;

        if (isset(self::$imported[$classKey]) &&
            array_key_exists($id, self::$imported[$classKey])) {
            $cached = self::$imported[$classKey][$id];
            if ($cached === self::IMPORT_FAILED) {
                // Пропагираме вече установения failure и към текущия parent,
                // за да не бъде записан със занулена dependency.
                self::$importErrors++;
                if ($source) {
                    self::logErr(self::getPayloadFailureMessage($classKey, $id, $res, $source));
                }
            }

            return is_numeric($cached) && $cached > 0 ? (int) $cached : 0;
        }

        if (!is_object($class) && !cls::load($class, true)) {
            self::$imported[$classKey][$id] = self::IMPORT_FAILED;
            self::$importErrors++;
            $message = "Неинсталиран клас в sync payload: {$classKey}";
            if ($source) {
                $message .= "; зависимост от {$source}";
            }
            self::logErr($message);

            return 0;
        }

        $mvc = cls::get($class);
        $class = $mvc->className;
        $classId = $mvc->getClassId();

        // В рамките на хита не импортираме повторно два пъти обекта
        if (isset(self::$imported[$class]) &&
            array_key_exists($id, self::$imported[$class])) {
            $cached = self::$imported[$class][$id];
            if ($cached === self::IMPORT_FAILED) {
                self::$importErrors++;
                if ($source) {
                    self::logErr(self::getPayloadFailureMessage($class, $id, $res, $source));
                }
            }

            return is_numeric($cached) && $cached > 0 ? (int) $cached : 0;
        }

        self::$imported[$class][$id] = self::IMPORT_IN_PROGRESS;
        if (!sync_Settings::renewImportLock()) {
            self::markImportFailed($class, $id);
            expect(false, 'Изгубен mutex по време на sync import');
        }

        static $i;
        if (($i++ % 1000) == 55) {
            self::logDebug("{$class}: {$id} - " . round(memory_get_usage()/(1024*1024)) . 'MB');
        }

        if (isset($res[$class]) && is_object($res[$class])) {
            $res[$class] = (array)$res[$class];
        }
        
        if (empty($res[$class]) ||
            !is_array($res[$class]) ||
            !array_key_exists($id, $res[$class]) ||
            !is_object($res[$class][$id])) {
            self::markImportFailed($class, $id);
            self::logErr(self::getPayloadFailureMessage($class, $id, $res, $source));

            return 0;
        }
        
        // Очакваме за посоченото id да има запис
        // Пазим remote IDs в payload-а за зависимостите, които се обработват
        // по-късно (включително цикъла профил -> потребител -> визитка).
        $rec = clone $res[$class][$id];

        if (!$rec) {
            return 0;
        }

        $errorsAtStart = self::$importErrors;
        
        $checkIncharge = false;
        
        if (($class == 'core_Users') &&
            !empty($res['crm_Profiles']) &&
            !empty($res['crm_Persons'])) {
            
            // В старите системи да не се дублират записите в crm_Persons
            $personId = null;
            $exUserId = self::getExistingUserId($id, $res);
            if ($exUserId) {
                $personId = crm_Profiles::fetchField("#userId = {$exUserId}", 'personId');
            }
            
            if (!$personId) {
                foreach ((array) $res['crm_Profiles'] as $pRecId => $pRec) {
                    if (!is_object($pRec)) {

                        continue;
                    }

                    if ($pRec->userId == $id) {
                        $personRec = $res['crm_Persons'][$pRec->personId] ?? null;
                        $checkIncharge = is_object($personRec)
                            ? ($personRec->inCharge ?? null)
                            : null;
                        $rec->personId = sync_Map::importRec(
                            'crm_Persons',
                            $pRec->personId,
                            $res,
                            $controller,
                            $update,
                            "{$class}::{$id}.personId"
                        );
                        
                        break;
                    }
                }
            } else {
                $rec->personId = $personId;
            }
        }
        
        $exRec = null;
        
        if (!$classId) {
            self::logDebug("Неинсталиран клас: {$class}");
            self::markImportFailed($class, $id);
            
            return 0;
        }
        
        // Ако в тази (приемащата) система има вече запис съответсващ на импортирания, то го извличаме
        $mapRec = self::fetch(
            array("#classId = [#1#] AND #remoteId = [#2#]", $classId, $id),
            'id,classId,remoteId,localId'
        );
        $mappedRec = ($mapRec && $mapRec->localId) ? $mvc->fetch($mapRec->localId) : null;
        if ($mapRec && $mapRec->localId && !$mappedRec) {
            self::logWarning(
                "Поправя се sync mapping към липсващ запис: {$class}::{$id} => {$mapRec->localId}"
            );
        }

        // Визитката на вече съществуващ потребител има идентичност чрез
        // профила, дори когато няма ЕГН или mapping от тази source система.
        if (!$mappedRec && $class == 'crm_Persons') {
            foreach ((array) ($res['crm_Profiles'] ?? array()) as $profile) {
                if (!is_object($profile) || ($profile->personId ?? null) != $id) {
                    continue;
                }
                $userId = self::getExistingUserId($profile->userId ?? null, $res);
                $personId = $userId ? crm_Profiles::fetchField("#userId = {$userId}", 'personId') : null;
                $mappedRec = $personId ? $mvc->fetch($personId) : null;
                if ($mappedRec) {
                    break;
                }
            }
        }
        if ($mappedRec) {
            if (!$update) {
                if (self::$importErrors > $errorsAtStart) {
                    self::markImportFailed($class, $id, false);

                    return 0;
                }

                return self::finalizeImportedRecord(
                    $mvc,
                    $class,
                    $id,
                    $mappedRec->id,
                    $rec,
                    $res,
                    $controller,
                    $update,
                    $checkIncharge
                );
            }

            $exRec = $mappedRec;
        }

        $isMapClassRec = false;
        
        // Минаваме по всички полета и
        $fields = $mvc->selectFields("#kind == 'FLD'");
        
        foreach ($fields as $name => $fRec) {
            
            if ($exRec && !($fRec->type instanceof type_Keylist) &&
                is_scalar($exRec->{$name} ?? null) && strlen((string) $exRec->{$name})) {
                $rec->{$name} = $exRec->{$name};
                continue;
            }

            $continue = !empty($rec->__continue);
            foreach (array($mvc->className . '::' . $name, '*::' . $name) as $fKey) {
                if (array_key_exists($fKey, $controller->fixedExport)) {
                    if (isset($controller->fixedExport[$fKey])) {
                        $funcArr = explode('::', $controller->fixedExport[$fKey]);
                        call_user_func_array(
                            array(cls::get($funcArr[0]), $funcArr[1] . 'Import'),
                            array(&$rec, $name, $fRec, &$res, $controller, $update)
                        );
                        
                        $continue = true;
                    }
                }
            }
            
            if ($continue) {
                continue;
            }
            
            if ($fRec->type instanceof type_CustomKey) {
                continue;
            }
            
            if (array_key_exists($mvc->className, $controller->mapClass)) {
                $mapClsFieldArr = $controller->mapClass[$mvc->className];
                
                $mapFieldsClsQuery = $mvc->getQuery();
                $condStr = ''; 
                foreach ($mapClsFieldArr as $mapFName) {
                    $mapFieldsClsQuery->where(array("#{$mapFName} = '[#1#]'", $rec->{$mapFName}));
                    $condStr .= $condStr ? " && " : '';
                    $condStr .= "{$mapFName} == '{$rec->{$mapFName}}'";
                }
                $mapFieldsClsQuery->limit(1);
                $rec = $mapFieldsClsQuery->fetch();
                
                $sTitle = mb_strtolower($mvc->title);
                
                expect($rec, "Няма запис в {$sTitle} ({$mvc->className}), който да отговаря на: {$condStr}");
                
                $isMapClassRec = true;
                
                break;
            }
            
            if ($fRec->type instanceof fileman_FileType && !empty($rec->{$name})) {
                //log_System::add('sync_Map', "Вземаме файла от: " . $rec->{$name});
                expect(sync_Settings::renewImportLock(), 'Изгубен mutex по време на sync import');
                $file = self::downloadRemoteFile($rec->{$name});
                if ($file === false) {
                    self::$importErrors++;
                    self::logErr("Не може да се свали файл за {$class}::{$id}::{$name}");
                } else {
                    $fh = fileman::absorbStr(
                        $file,
                        $fRec->type->params['bucket'],
                        basename($rec->{$name})
                    );
                    if (!$fh) {
                        self::$importErrors++;
                        self::logErr("Не може да се запише файл за {$class}::{$id}::{$name}");
                    } else {
                        $rec->{$name} = $fh;
                    }
                }
            } elseif ($fRec->type instanceof fileman_type_Files && is_array($rec->{$name} ?? null)) {
                $kArr = array();
                foreach ($rec->{$name} as $url) {
                    //log_System::add('sync_Map', "Вземаме файла от: " . $url);
                    expect(sync_Settings::renewImportLock(), 'Изгубен mutex по време на sync import');
                    $file = self::downloadRemoteFile($url);
                    if ($file !== false) {
                        $fh = fileman::absorbStr($file, $fRec->type->params['bucket'], basename($url));
                        $k = $fh ? fileman::fetchByFh($fh, 'id') : null;
                        if ($k) {
                            $kArr[$k] = $k;
                        } else {
                            self::$importErrors++;
                            self::logErr("Не може да се запише файл за {$class}::{$id}::{$name}");
                        }
                    } else {
                        self::$importErrors++;
                        self::logErr("Не може да се свали файл за {$class}::{$id}::{$name}");
                    }
                }
                $rec->{$name} = keylist::fromArray($kArr);
            } elseif ($fRec->type instanceof type_Key || $fRec->type instanceof type_Key2) {
                $kMvc = $fRec->type->params['mvc'];
                if ($v = $res[$class][$id]->{$name} ?? null) {
                    if ($uf = $controller->globalUniqKeys[$kMvc] ?? null) {
                        $kMvc = cls::get($kMvc);
                        $rec->{$name} = $kMvc->fetchField(array("#{$uf} = '[#1#]'", $rec->{$name}));
                    } else {
                        $rec->{$name} = self::importRec(
                            $kMvc,
                            $rec->{$name},
                            $res,
                            $controller,
                            $update,
                            "{$class}::{$id}.{$name}"
                        );
                    }
                }
            } elseif (($fRec->type instanceof type_Keylist) || is_subclass_of($fRec->type, 'type_Keylist')) {
                $kMvc = $fRec->type->params['mvc'];
                if ($kArr = $res[$class][$id]->{$name} ?? null) {
                    if (!is_array($kArr)) {
                        $kArr = $fRec->type->toArray($kArr);
                    }
                    if ($uf = $controller->globalUniqKeys[$kMvc] ?? null) {
                        $kMvc = cls::get($kMvc);
                        $kArrN = array();
                        foreach ($kArr as $key) {
                            $k = $kMvc->fetchField(array("#{$uf} = '[#1#]'", $key));
                            if ($k) {
                                $kArrN[$k] = $k;
                            }
                        }
                        $rec->{$name} = keylist::fromArray($kArrN);
                    } else {
                        $kArrN = array();
                        foreach ($kArr as $key) {
                            $k = self::importRec(
                                $kMvc,
                                $key,
                                $res,
                                $controller,
                                $update,
                                "{$class}::{$id}.{$name}"
                            );
                            if ($k) {
                                $kArrN[$k] = $k;
                            }
                        }
                        $rec->{$name} = keylist::fromArray($kArrN);
                    }
                }
            } elseif (($rec->{$name} ?? 0) > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('contragentId', 'cId', 'productId'))) {
                foreach (array('contragentCls', 'cClass', 'contragentClassId', 'classId') as $cfName) {
                    if ($cfType = $fields[$cfName]->type ?? null) {
                        if (($cfType->params['mvc'] ?? null) == 'core_Classes') {
                            $kMvc = cls::get($rec->{$cfName});

                            $rec->{$name} = self::importRec(
                                $kMvc,
                                $rec->{$name},
                                $res,
                                $controller,
                                $update,
                                "{$class}::{$id}.{$name}"
                            );
                            
                            break;
                        }
                    }
                }
            } elseif (($rec->{$name} ?? 0) > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('saoParentId', 'saoRelative'))) {
                $rec->{$name} = self::importRec(
                    $class,
                    $rec->{$name},
                    $res,
                    $controller,
                    $update,
                    "{$class}::{$id}.{$name}"
                );
            }
        }
        
        // Преобразуваме _companyId към folderId
        if($rec->_companyId ?? null) {
            if ($cId = self::importRec(
                'crm_Companies',
                $rec->_companyId,
                $res,
                $controller,
                $update,
                "{$class}::{$id}._companyId"
            )) {
                $rec->folderId = crm_Companies::forceCoverAndFolder($cId);
            }
        }
        
        // Преобразуваме _personId към folderId
        if($rec->_personId ?? null) {
            if ($pId = self::importRec(
                'crm_Persons',
                $rec->_personId,
                $res,
                $controller,
                $update,
                "{$class}::{$id}._personId"
            )) {
                $rec->folderId = crm_Persons::forceCoverAndFolder($pId);
            }
        }
        
        // Фикс, ако е параметъра е файл
        if (!empty($rec->__paramValue)) {
            if (($mvc->className == 'cat_products_Params') && !empty($rec->__paramId)) {
                $cParamId = self::importRec(
                    'cat_Params',
                    $rec->__paramId,
                    $res,
                    $controller,
                    $update,
                    "{$class}::{$id}.__paramId"
                );
                
                $cParRec = cat_Params::fetch($cParamId);
                
                if ($cParRec) {
                    $Driver = cat_Params::getDriver($cParamId);
                    
                    if ($Driver) {
                        $dType = $Driver->getType($cParamId);
                        if ($dType && ($dType instanceof fileman_FileType)) {
                            expect(sync_Settings::renewImportLock(), 'Изгубен mutex по време на sync import');
                            $file = self::downloadRemoteFile($rec->__paramValue);
                            if ($file === false) {
                                self::$importErrors++;
                                self::logErr("Не може да се свали параметър-файл за {$class}::{$id}");
                            } else {
                                $fh = fileman::absorbStr(
                                    $file,
                                    $dType->params['bucket'],
                                    basename($rec->__paramValue)
                                );
                                if (!$fh) {
                                    self::$importErrors++;
                                    self::logErr("Не може да се запише параметър-файл за {$class}::{$id}");
                                } else {
                                    $rec->paramValue = $fh;
                                }
                                unset($rec->__paramValue);
                                unset($rec->__paramId);
                            }
                        }
                    }
                }
            }
        }

        // Ако задължителна рекурсивна зависимост не е импортирана, не
        // записваме и родителя с занулена връзка. Следващ успешен sync може
        // безопасно да опита отново дори при UPDATE_EXISTING=no.
        if (self::$importErrors > $errorsAtStart) {
            self::markImportFailed($class, $id, false);

            return 0;
        }
        
        // Някои fix callbacks (напр. cms_Domains) посочват директно
        // съществуващо локално ID. При no-update не пускаме save hooks.
        if (!$update && !empty($rec->__id)) {
            $lId = $rec->__id;

            return self::finalizeImportedRecord(
                $mvc,
                $class,
                $id,
                $lId,
                $rec,
                $res,
                $controller,
                $update,
                $checkIncharge
            );
        }

        if ($isMapClassRec) {
            if (!$exRec) {
                $exRec = $rec;
            }

            if (!$update) {
                $lId = $exRec->id ?? null;

                return self::finalizeImportedRecord(
                    $mvc,
                    $class,
                    $id,
                    $lId,
                    $rec,
                    $res,
                    $controller,
                    $update,
                    $checkIncharge
                );
            }
        } else {
            if (!$exRec) {
                $exRec = null;
                $fArr = null;
                //log_System::add('sync_Map', "Търсим уникалност");
                $mvc->isUnique($rec, $fArr, $exRec);
            } 

            // При забранено обновяване natural-key аналогът също се запазва
            // непроменен, но се създава mapping към него.
            if ($exRec && !$update) {
                $lId = $exRec->id;

                return self::finalizeImportedRecord(
                    $mvc,
                    $class,
                    $id,
                    $lId,
                    $rec,
                    $res,
                    $controller,
                    $update,
                    $checkIncharge
                );
            }

            if (!$exRec) {
                $exRec = $rec;
                unset($exRec->id);
            } else {
                foreach ($fields as $name => $fRec) {
                    if ($fRec->type instanceof type_Keylist) {
                        $exRec->{$name} = keylist::merge($exRec->{$name} ?? '', $rec->{$name} ?? '');
                    }
                    
                    $value = $rec->{$name} ?? null;
                    if (empty($exRec->{$name}) && (is_array($value) || is_object($value) || strlen((string) $value))) {
                        $exRec->{$name} = $rec->{$name};
                    }
                }
            }
        }
        
        if (!empty($rec->__id)) {
            $rec->id = $rec->__id;
            unset($rec->__id);
        }

        $errorsBeforeSave = self::$importErrors;
        try {
            $lId = $mvc->save($exRec);
        } catch (core_exception_Expect $e) {
            log_System::add($mvc, "Грешка при синхронизиране на данните: " . core_Type::mixedToString($e->getMessage()), $exRec, 'err', 10);
            reportException($e);
            $lId = 0;
            self::$importErrors++;
        } catch (Exception $e) {
            log_System::add($mvc, "Грешка при синхронизиране на данните: " . core_Type::mixedToString($e->getMessage()), $exRec, 'err', 10);
            reportException($e);
            $lId = 0;
            self::$importErrors++;
        } catch (Throwable $t) {
            log_System::add($mvc, "Грешка при синхронизиране на данните: " . core_Type::mixedToString($t->getMessage()), $exRec, 'err', 10);
            reportException($t);
            $lId = 0;
            self::$importErrors++;
        }

        if (!$lId && self::$importErrors == $errorsBeforeSave) {
            self::$importErrors++;
        }

        //log_System::add('sync_Map', "Записахме {$class} {$lId}");

        if (!$lId) {
            self::markImportFailed($class, $id, false);

            return 0;
        }

        return self::finalizeImportedRecord(
            $mvc,
            $class,
            $id,
            $lId,
            $rec,
            $res,
            $controller,
            $update,
            $checkIncharge
        );
    }


    /**
     * Общ успешен край за нов, mapped и natural-key запис.
     *
     * Публикуваме локалното ID преди post-recursion единствено за да
     * прекъснем валидния user/person цикъл. При неуспех marker-ът се заменя
     * с IMPORT_FAILED и mapping не се създава/поправя.
     *
     * @param core_Mvc  $mvc
     * @param string    $class
     * @param int       $id
     * @param int       $localId
     * @param stdClass  $rec
     * @param array     $res
     * @param stdClass  $controller
     * @param bool      $update
     * @param int|false $checkIncharge
     *
     * @return int
     */
    protected static function finalizeImportedRecord(
        $mvc,
        $class,
        $id,
        $localId,
        $rec,
        &$res,
        $controller,
        $update,
        $checkIncharge
    )
    {
        if (!$localId) {
            self::markImportFailed($class, $id);

            return 0;
        }

        self::$imported[$class][$id] = $localId;

        // Поправяме отговорника на визитката и при mapped/natural-key retry.
        // Така UPDATE_EXISTING=no не оставя частично приключил цикъл.
        if ($checkIncharge && $class == 'core_Users' && !empty($rec->personId)) {
            $pRec = crm_Persons::fetch($rec->personId);
            $errorsBeforeIncharge = self::$importErrors;
            $nInCharge = self::importRec(
                'core_Users',
                $checkIncharge,
                $res,
                $controller,
                $update
            );
            if (!$pRec ||
                !$nInCharge ||
                self::$importErrors > $errorsBeforeIncharge) {
                self::markImportFailed(
                    $class,
                    $id,
                    self::$importErrors == $errorsBeforeIncharge
                );

                return 0;
            }

            if ($pRec->inCharge != $nInCharge) {
                $pRec->inCharge = $nInCharge;
                if (!crm_Persons::save($pRec, 'inCharge')) {
                    self::markImportFailed($class, $id);

                    return 0;
                }
            }
        }

        // Upsert-ът поправя legacy localId=0 и mapping към изтрит запис.
        // Изпълнява се след post-recursion, за да няма mapping при partial
        // failure на нов или natural-key намерен запис.
        if (!self::saveMapping($mvc->getClassId(), $id, $localId)) {
            self::markImportFailed($class, $id);

            return 0;
        }

        return $localId;
    }


    /**
     * Сваля файл от payload-а. При legacy product push URL-ът задължително
     * трябва да е от настроения HTTPS origin; при нормален pull import
     * запазваме досегашното поведение, защото master-ът е довереният източник.
     *
     * @param string $url
     *
     * @return string|false
     */
    protected static function downloadRemoteFile($url)
    {
        if ($trustedOrigin = Mode::get('syncTrustedFileOrigin')) {

            return sync_Helper::fetchFileFromTrustedOrigin($url, $trustedOrigin);
        }

        return @file_get_contents($url);
    }


    /**
     * Намира локален потребител преди рекурсивния импорт на неговата визитка.
     */
    protected static function getExistingUserId($remoteId, $res)
    {
        $remoteId = self::normalizeRemoteId($remoteId);
        if ($remoteId === false) {
            return null;
        }
        $mvc = cls::get('core_Users');
        $localId = self::getLocalId('core_Users', $remoteId);
        if ($localId && $mvc->fetch($localId)) {
            return $localId;
        }
        $users = (array) ($res['core_Users'] ?? array());
        if (!isset($users[$remoteId]) || !is_object($users[$remoteId])) {
            return null;
        }
        $candidate = clone $users[$remoteId];
        unset($candidate->id);
        $fields = $existing = null;
        $mvc->isUnique($candidate, $fields, $existing);

        return $existing->id ?? null;
    }


    /**
     * Подготвя конкретно съобщение за неуспешен payload или зависимост
     *
     * @param string      $class
     * @param int         $id
     * @param array       $res
     * @param string|null $source
     *
     * @return string
     */
    protected static function getPayloadFailureMessage($class, $id, $res, $source = null)
    {
        if (!is_array($res)) {
            $message = 'Невалиден sync payload';
        } elseif (!array_key_exists($class, $res)) {
            $message = 'Липсва клас в sync payload';
        } else {
            $records = $res[$class];
            if (is_object($records)) {
                $records = (array) $records;
            }

            if (!is_array($records)) {
                $message = 'Невалидна колекция в sync payload';
            } elseif (!array_key_exists($id, $records)) {
                $message = 'Липсва запис в sync payload';
            } elseif ($records[$id] === false) {
                $message = 'Записът е маркиран от export-а като липсващ или забранен';
            } elseif (!is_object($records[$id])) {
                $message = 'Невалиден запис в sync payload (' . gettype($records[$id]) . ')';
            } else {
                $message = 'Предходно неуспешен import на sync зависимост';
            }
        }

        $message .= ": {$class}::{$id}";
        if ($source) {
            $message .= "; зависимост от {$source}";
        }

        return $message;
    }


    /**
     * Маркира окончателно неуспешен запис за текущия import
     *
     * @param string $class
     * @param int    $id
     * @param bool   $countError
     */
    protected static function markImportFailed($class, $id, $countError = true)
    {
        self::$imported[$class][$id] = self::IMPORT_FAILED;
        if ($countError) {
            self::$importErrors++;
        }
    }


    /**
     * Създава или поправя mapping към успешно намерен/записан локален обект
     *
     * @param int $classId
     * @param int $remoteId
     * @param int $localId
     *
     * @return int|false
     */
    protected static function saveMapping($classId, $remoteId, $localId)
    {
        $remoteId = self::normalizeRemoteId($remoteId);
        if (!$classId || $remoteId === false || !$localId) {

            return false;
        }

        $mRec = self::fetch(
            array("#classId = [#1#] AND #remoteId = [#2#]", $classId, $remoteId),
            'id,classId,remoteId,localId'
        );
        if ($mRec) {
            if ((int) $mRec->localId !== (int) $localId) {
                $mRec->localId = $localId;
                if (!self::save($mRec, 'localId')) {

                    return false;
                }
            }

            return $mRec->id;
        }

        $mRec = (object) array(
            'classId' => $classId,
            'remoteId' => $remoteId,
            'localId' => $localId,
        );
        $mappingId = self::save($mRec, null, 'IGNORE');
        if ($mappingId) {

            return $mappingId;
        }

        // При конкурентен INSERT проверяваме дали вече има коректен запис.
        $existingLocalId = self::fetchField(
            array("#classId = [#1#] AND #remoteId = [#2#]", $classId, $remoteId),
            'localId'
        );

        return ((int) $existingLocalId === (int) $localId) ? true : false;
    }


    /**
     * Приема само канонично положително integer remote ID.
     *
     * @param mixed $remoteId
     *
     * @return int|false
     */
    protected static function normalizeRemoteId($remoteId)
    {
        if (is_int($remoteId)) {

            return $remoteId > 0 && $remoteId <= self::MAX_REMOTE_ID
                ? $remoteId
                : false;
        }

        if (!is_string($remoteId) ||
            !preg_match('/^[1-9][0-9]*\z/', $remoteId)) {

            return false;
        }

        $normalized = (int) $remoteId;

        return $normalized > 0 &&
            $normalized <= self::MAX_REMOTE_ID &&
            (string) $normalized === $remoteId
            ? $normalized
            : false;
    }


    /**
     * Определя дали съществуващите записи да се обновяват
     *
     * Изричният параметър има предимство, следван от политиката за текущия
     * import и накрая от SYNC_UPDATE_EXISTING.
     *
     * @param bool|null $update
     *
     * @return bool
     */
    public static function shouldUpdate($update = null)
    {
        if ($update !== null) {

            return (bool) $update;
        }

        $modeUpdate = Mode::get('syncUpdateExisting');
        if ($modeUpdate !== null) {

            return (bool) $modeUpdate;
        }

        return sync_Setup::get('UPDATE_EXISTING') == 'yes';
    }
    
    
    /**
     * Какво локално ид съответства на съответното $remoteId
     * 
     * @param mixed $class
     * @param int $remoteId
     * @return int
     */
    public static function getLocalId($class, $remoteId)
    {
        $remoteId = self::normalizeRemoteId($remoteId);
        if ($remoteId === false) {

            return null;
        }

        $mvc = cls::get($class);
        $classId = $mvc->getClassId();
        $localId = self::fetchField(
            array("#classId = [#1#] AND #remoteId = [#2#]", $classId, $remoteId),
            'localId'
        );
        if (!$localId || !$mvc->fetch($localId)) {

            return null;
        }

        return $localId;
    }
    
    
    /**
     * Добавя нов НЕСЪЩЕСТВУВАЩ запис
     * 
     * @param mixed $class
     * @param int $localId
     * @param int $remoteId
     * @return int
     */
    public static function add($class, $localId, $remoteId)
    {
        $classId = cls::get($class)->getClassId();
        
        $exLocalId = self::getLocalId($classId, $remoteId);
        expect(!$exLocalId);

        expect(self::saveMapping($classId, $remoteId, $localId));
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FNC('search', 'varchar', 'caption=Търсене');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->showFields = 'search';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input('search');
        
        if ($search = $data->listFilter->rec->search) {
            $search = trim($search);
            $searchArr = explode(' ', $search);
            foreach ($searchArr as $search) {
                if (!is_numeric($search) && cls::load($search, true)) {
                    $data->query->where(array("#classId = '[#1#]'", cls::get($search)->getClassId()));
                }
                $data->query->orWhere(array("#remoteId = '[#1#]'", $search));
                $data->query->orWhere(array("#localId = '[#1#]'", $search));
            }
        }
        
        $data->query->orderBy('id', 'DESC');
    }
}
