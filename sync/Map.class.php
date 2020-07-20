<?php


/**
 * Съответствие на обекти между две bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2020 - 2020 Experta OOD
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
     * Добавяне на плъгини
     */
    public $loadList = 'plg_Sorting, plg_RowTools';
    
    
    /**
     * Заглавие
     */
    public $title = "Съответсвия между две bgERP системи";
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Масив с информация за импортираните обекти
     */
    public static $imported = array();

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
     */
    public static function exportRec($class, $id, &$res, $controller)
    {
        $mvc = cls::get($class);
        
        $idInt = is_object($id) ? $id->id : $id;

        // Вече експортираните обекти и тези със специални id-та не се експортират
        if (isset($res[$mvc->className][$idInt]) || $idInt <= 0) {
            return;
        }
        
        if(is_object($id)) {
            $rec = $res[$mvc->className][$id->id] = clone($id);
            $id = $id->id;
        } else {
            $rec = $res[$mvc->className][$id] = $mvc->fetch($id);
        }
        
        // При грешни данни не експортваме нищо
        if ($rec === false) {
            return;
        }
        
        if ($class == 'core_Users') {
            $pRec = crm_Profiles::fetch(array("#userId = '[#1#]'", $idInt));
            self::exportRec('crm_Profiles', $pRec, $res, $controller);
        }
        
        if ($mvc->className == 'cat_Products') {
            static $prodGroupsArr = false;
            if ($prodGroupsArr === false) {
                if ($prodGroups = sync_Setup::get('PROD_GROUPS')) {
                    $prodGroupsArr = type_Keylist::toArray($prodGroups);
                } else {
                    $prodGroupsArr = array();
                }
            }
            
            if (!empty($prodGroupsArr)) {
                $recProdGroupsArr = type_Keylist::toArray($rec->groups);
                if (!array_intersect($prodGroupsArr, $recProdGroupsArr)) {
                    $res[$mvc->className][$id] = false;
                    
                    return null;
                }
            }
        }
        

        $fields = $mvc->selectFields("#kind == 'FLD'");
        foreach ($fields as $name => $fRec) {
            foreach (array($mvc->className . '::' . $name, '*::' . $name) as $fKey) {
                if (array_key_exists($fKey, $controller->fixedExport)) {
                    if (isset($controller->fixedExport[$fKey])) {
                        $funcArr = explode('::', $controller->fixedExport[$fKey]);
                        call_user_func_array(array(cls::get($funcArr[0]), $funcArr[1] . 'Export'), array(&$rec, $name, $fRec, &$res, $controller));
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
                        $kArrN[] = fileman_Download::getDownloadUrl($$fn);
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
                        self::exportRec($kMvc, $rec->{$name}, $res, $controller);
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
                        foreach ($kArr as $key) {
                            self::exportRec($kMvc, $key, $res, $controller);
                        }
                    }
                }
            } elseif ($rec->{$name} > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('saoParentId', 'saoRelative'))) {
                self::exportRec($class, $rec->{$name}, $res, $controller);
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
                        self::exportRec($dMvc, $dRec->id, $res, $controller);
                    }
                }
            }
        }
    }


    /**
     * Експортира в резултата един запис
     *
     * @return int id на импортирания обект
     */
    public static function importRec($class, $id, &$res, $controller, $update = true)
    {
        //log_System::add('sync_Map', "$class::$id");
        core_App::setTimeLimit(300);
        core_Debug::$isLogging = false;
        
        $mvc = cls::get($class);
        $class = $mvc->className;
        $classId = $mvc->getClassId();
        
        static $i;

        if (($i++ % 1000) == 55) {
            self::logDebug("{$class}: {$id} - " . round(memory_get_usage()/(1024*1024)) . 'MB');
        }
        
        // В рамките на хита не импортираме повторно два пъти обекта
        if (isset(self::$imported[$class][$id])) {
            return self::$imported[$class][$id];
        }
        
        self::$imported[$class][$id] = 0;
        if(is_object($res[$class])){
            $res[$class] = (array)$res[$class];
        }
        
        if (!$res[$class] || !$res[$class][$id] || !is_object($res[$class][$id])) {
            
//             wp($res[$class][$id], $class, $id);
            
            return 0;
        }
        
        // Очакваме за посоченото id да има запис
        $rec =  $res[$class][$id];

        if (!$rec) {
            return 0;
        }
        
        $checkIncharge = false;
        
        if (($class == 'core_Users') && ($res['crm_Profiles']) && $res['crm_Persons']) {
            
            // В старите системи да не се дублират записите в crm_Persons
            $personId = null;
            $exUserId = self::fetchField("#classId = {$classId} AND #remoteId = {$id}", 'localId');
            if ($exUserId) {
                $personId = crm_Profiles::fetchField("#userId = {$exUserId}", 'personId');
            }
            
            if (!$personId) {
                foreach ($res['crm_Profiles'] as $pRecId => $pRec) {
                    if ($pRec->userId == $id) {
                        $checkIncharge = $res['crm_Persons'][$pRec->personId]->inCharge;
                        $rec->personId = sync_Map::importRec('crm_Persons', $pRec->personId, $res, $controller, $update);
                        
                        break;
                    }
                }
            } else {
                $rec->personId = $personId;
            }
        }
        
        $haveRec = false;
        $exRec = null;
        
        if (!$classId) {
            self::logDebug("Неинсталиран клас: {$class}");
            
            return 0;
        }
        
        // Ако в тази (приемащата) система има вече запис съответсващ на импортирания, то го извличаме
        $exId = self::fetchField("#classId = {$classId} AND #remoteId = {$id}", 'localId');
        if ($exId) {
            if (!$update) {
                self::$imported[$class][$id] = $exId;
                
                return $exId;
            }
            
            $haveRec = true;
            $exRec = $mvc->fetch($exId);
        }

        $isMapClassRec = false;
        
        // Минаваме по всички полета и
        $fields = $mvc->selectFields("#kind == 'FLD'");
        
        foreach ($fields as $name => $fRec) {
            
            if($exRec && is_scalar($exRec->{$name}) && strlen($exRec->{$name})) {
                $rec->{$name} = $exRec->{$name};
                continue;
            }

            $continue = ($rec->__continue) ? true : false;
            foreach (array($mvc->className . '::' . $name, '*::' . $name) as $fKey) {
                if (array_key_exists($fKey, $controller->fixedExport)) {
                    if (isset($controller->fixedExport[$fKey])) {
                        $funcArr = explode('::', $controller->fixedExport[$fKey]);
                        call_user_func_array(array(cls::get($funcArr[0]), $funcArr[1] . 'Import'), array(&$rec, $name, $fRec, &$res, $controller));
                        
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
                if ($file = @file_get_contents($rec->{$name})) {
                    $rec->{$name} = fileman::absorbStr($file, $fRec->type->params['bucket'], basename($rec->{$name}));
                } else {
//                     wp($file, $rec);
                }
            } elseif ($fRec->type instanceof fileman_type_Files && is_array($rec->{$name})) {
                $kArr = array();
                foreach ($rec->{$name} as $url) {
                    //log_System::add('sync_Map', "Вземаме файла от: " . $url);
                    if ($file = @file_get_contents($url)) {
                        $fh = fileman::absorbStr($file, $fRec->type->params['bucket'], basename($url));
                        $k = fileman::fetchByFh($fh);
                        $kArr[$k] = $k;
                    } else {
//                         wp($file, $rec);
                    }
                }
                $rec->{$name} = keylist::fromArray($kArr);
            } elseif ($fRec->type instanceof type_Key || $fRec->type instanceof type_Key2) {
                $kMvc = $fRec->type->params['mvc'];
                if ($v = $res[$class][$id]->{$name}) {
                    if ($uf = $controller->globalUniqKeys[$kMvc]) {
                        $kMvc = cls::get($kMvc);
                        $rec->{$name} = $kMvc->fetchField(array("#{$uf} = '[#1#]'", $rec->{$name}));
                    } else {
                        $rec->{$name} = self::importRec($kMvc, $rec->{$name}, $res, $controller, $update);
                    }
                }
            } elseif (($fRec->type instanceof type_Keylist) || is_subclass_of($fRec->type, 'type_Keylist')) {
                $kMvc = $fRec->type->params['mvc'];
                if ($kArr = $res[$class][$id]->{$name}) {
                    if (!is_array($kArr)) {
                        $kArr = $fRec->type->toArray($kArr);
                    }
                    if ($uf = $controller->globalUniqKeys[$kMvc]) {
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
                            $k = self::importRec($kMvc, $key, $res, $controller, $update);
                            if ($k) {
                                $kArrN[$k] = $k;
                            }
                        }
                        $rec->{$name} = keylist::fromArray($kArrN);
                    }
                }
            } elseif ($rec->{$name} > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('contragentId', 'cId', 'productId'))) {
                foreach (array('contragentCls', 'cClass', 'contragentClassId', 'classId') as $cfName) {
                    if ($cfType = $fields[$cfName]->type) {
                        if ($cfType->params['mvc'] == 'core_Classes') {
                            $kMvc = cls::get($rec->{$cfName});

                            $rec->{$name} = self::importRec($kMvc, $rec->{$name}, $res, $controller, $update);
                            
                            break;
                        }
                    }
                }
            } elseif ($rec->{$name} > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('saoParentId', 'saoRelative'))) {
                $rec->{$name} = self::importRec($class, $rec->{$name}, $res, $controller, $update);
            }
        }
        
        // Преобразуваме _companyId към folderId
        if($rec->_companyId) {
            if ($cId = self::importRec('crm_Companies', $rec->_companyId, $res, $controller, $update)) {
                $rec->folderId = crm_Companies::forceCoverAndFolder($cId);
            }
        }
        
        // Преобразуваме _personId към folderId
        if($rec->_personId) {
            if ($pId = self::importRec('crm_Persons', $rec->_personId, $res, $controller, $update)) {
                $rec->folderId = crm_Persons::forceCoverAndFolder($pId);
            }
        }
        
        if ($isMapClassRec) {
            if (!$exRec) {
                $exRec = $rec;
            }
        } else {
            if (!$exRec) {
                $exRec = null;
                $fArr = null;
                //log_System::add('sync_Map', "Търсим уникалност");
                $mvc->isUnique($rec, $fArr, $exRec);
            } 
            
            if (!$exRec) {
                $exRec = $rec;
                unset($exRec->id);
            } else {
                foreach ($fields as $name => $fRec) {
                    if ($fRec->type instanceof type_Keylist) {
                        $exRec->{$name} = keylist::merge($exRec->{$name}, $rec->{$name});
                    }
                    
                    if (empty($exRec->{$name}) && (is_array($rec->{$name}) || is_object($rec->{$name}) || strlen($rec->{$name}))) {
                        $exRec->{$name} = $rec->{$name};
                    }
                }
            }
        }
        
        if ($rec->__id) {
            $rec->id = $rec->__id;
            unset($rec->__id);
        }
        
        $lId = $mvc->save($exRec);
        //log_System::add('sync_Map', "Записахме {$class} {$lId}");

        if (!$haveRec) {
            $mRec = (object) array('classId' => $mvc->getClassId(), 'remoteId' => $id, 'localId' => $lId);
            self::save($mRec);
        }

        self::$imported[$class][$id] = $lId;
        
        // Поправяме отоговорника на визитката
        if (!$haveRec) {
            if ($checkIncharge) {
                if (($class == 'core_Users') && ($rec->personId)) {
                    $pRec = crm_Persons::fetch($rec->personId);
                    
                    $nInCharge = self::importRec('core_Users', $checkIncharge, $res, $controller, $update);
                    if ($nInCharge && ($pRec->inCharge != $nInCharge)) {
                        $pRec->inCharge = $nInCharge;
                        crm_Persons::save($pRec, 'inCharge');
                    }
                }
            }
        }
        
        return $lId;
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
        $classId = cls::get($class)->getClassId();
        
        return self::fetchField("#classId = {$classId} AND #remoteId = {$remoteId}", 'localId');
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
        
        $mRec = (object) array('classId' => $classId, 'remoteId' => $remoteId, 'localId' => $localId);
        sync_Map::save($mRec);
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
