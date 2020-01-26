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
     * Масив с информация за импортираните обекти
     */
    public static $imported = array();

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('classId', 'class(interface=core_ManagerIntf)', 'caption=class');
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

        // Вече експортираните обекти и тези със специални id-та не се експортират
        if (isset($res[$mvc->className][$id]) || $id <= 0) {
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

        $fields = $mvc->selectFields("#kind == 'FLD'");
        foreach ($fields as $name => $fRec) {
            // Ако имаме фиксиран експорт - използваме го
            $fKey = $mvc->className . '::' . $name;
            if (array_key_exists($fKey, $controller->fixedExport)) {
                $rec->{$name} = $controller->fixedExport[$fKey];
            }
            $fKey = '*::' . $name;
            if (array_key_exists($fKey, $controller->fixedExport)) {
                $rec->{$name} = $controller->fixedExport[$fKey];
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
                    wp($e);
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
                        wp($e);
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
                        if ($type instanceof type_Key) {
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
    public static function importRec($class, $id, &$res, $controller)
    {
        //log_System::add('sync_Map', "$class::$id");
        core_App::setTimeLimit(300);
        core_Debug::$isLogging = false;
        ini_set('memory_limit', '1024M');

        $mvc = cls::get($class);
        $class = $mvc->className;

        // В рамките на хита не импортираме повторно два пъти обекта
        if (isset(self::$imported[$class][$id])) {
            return self::$imported[$class][$id];
        }
        
        self::$imported[$class][$id] = 0;
        
        if (!$res[$class] || !$res[$class][$id] || !is_object($res[$class][$id])) {
            
            wp($res[$class][$id], $class, $id);
            
            return 0;
        }
        
        // Очакваме за посоченото id да има запис
        $rec =  $res[$class][$id];

        if (!$rec) {
            return 0;
        }


        
        $isMapClassRec = false;
        
        // Минаваме по всички полета и
        $fields = $mvc->selectFields("#kind == 'FLD'");
        foreach ($fields as $name => $fRec) {
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
                $mapFieldsClsQuery->count(1);
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
                    wp($file, $rec);
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
                        wp($file, $rec);
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
                        $rec->{$name} = self::importRec($kMvc, $rec->{$name}, $res, $controller);
                    }
                }
            } elseif ($fRec->type instanceof type_Keylist) {
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
                            $k = self::importRec($kMvc, $key, $res, $controller);
                            if ($k) {
                                $kArrN[$k] = $k;
                            }
                        }
                        $rec->{$name} = keylist::fromArray($kArrN);
                    }
                }
            } elseif ($rec->{$name} > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('contragentId', 'cId'))) {
                foreach (array('contragentCls', 'cClass', 'contragentClassId') as $cfName) {
                    if ($cfType = $fields[$cfName]->type) {
                        if ($cfType->params['mvc'] == 'core_Classes') {
                            $kMvc = cls::get($rec->{$cfName});

                            $rec->{$name} = self::importRec($kMvc, $rec->{$name}, $res, $controller);
                            
                            break;
                        }
                    }
                }
            } elseif ($rec->{$name} > 0 && get_class($fRec->type) == 'type_Int' && in_array($name, array('saoParentId', 'saoRelative'))) {
                $rec->{$name} = self::importRec($class, $rec->{$name}, $res, $controller);
            }
        }
        
        // Преобразуваме _companyId към folderId
        if($rec->_companyId) {
            $cid = self::importRec('crm_Companies', $rec->_companyId, $res, $controller);
            $cRec = crm_Companies::fetch($cid);
            $rec->folderId = $cRec->folderId;
        }

        // Вземаме съществуващият запис
        $classId = $mvc->getClassId();
        $exId = self::fetchField("#classId = {$classId} AND #remoteId = {$id}", 'localId');
        
        if ($isMapClassRec) {
            if (!$exId) {
                $exRec = $rec;
            } else {
                $exRec = $mvc->fetch($exId, '*', false);
            }
        } else {
            if (!$exId) {
                $exRec = null;
                $fArr = null;
                //log_System::add('sync_Map', "Търсим уникалност");
                $mvc->isUnique($rec, $fArr, $exRec);
            } else {
                //log_System::add('sync_Map', "Вадим записа");
                $exRec = $mvc->fetch($exId, '*', false);
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


        $lId = $mvc->save($exRec);
        //log_System::add('sync_Map', "Записахме {$class} {$lId}");

        if (!$exId) {
            $mRec = (object) array('classId' => $mvc->getClassId(), 'remoteId' => $id, 'localId' => $lId);
            self::save($mRec);
        }

        self::$imported[$class][$id] = $lId;

        return $lId;
    }
}
