<?php
/**
 * Клас 'cond_plg_DefaultValues'
 *
 * Плъгин слагащ на полетата от модела дефолт стойности според
 * определена стратегия.
 *
 * В променлива $defaultStrategies на всеки модел се определя кое поле,
 * какви стратегии ще ползва. Срещу името на полето се посочват
 * стратегиите по които ще се намери стойността. Връща се
 * първата намерена стойност.
 *
 * Възможните стратегии са:
 *
 * -------------------------------------------------------------
 *
 * lastDocUser        - Последния активен документ в папката от потребителя
 * lastDoc 		      - Последния активен документ в папката
 * lastDocSameCountry - Последния активен документ в папка на клиент от същата държава
 * defMethod	      - Дефолт метод с име "getDefault{$name}", има реализация по подразбиране
 * clientData	      - От контрагент интерфейса
 * clientCondition    - От дефолт търговско условие
 * coverMethod	      - Метод от корицата с име "getDefault{$name}"
 * customMethod	      - Дефолт метод за частен случай
 * sessionValue       - Стойност от сесията, ако полето е type_Key или type_Keylist
 *
 * -------------------------------------------------------------
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_plg_DefaultValues extends core_Plugin
{
    /**
     * След инициализирането на модела
     *
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription($mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
        setIfNot($mvc->dontReloadDefaultsOnRefresh, true);
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     *
     * @param core_Mvc $mvc
     *
     * @return bool
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            
            return false;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);
        
        if (isset($plugins['doc_DocumentPlg'])) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        self::prepareDefaultValues($mvc, $data);
    }
    
    
    /**
     * Преди показване на къстом форма за добавяне/промяна.
     */
    public static function on_AfterPrepareCustomForm($mvc, &$data)
    {
        self::prepareDefaultValues($mvc, $data);
    }
    
    
    /**
     * Добавя към формата дефолт стойностти
     */
    private static function prepareDefaultValues($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if (empty($rec->id)) {
            self::getFolderId($rec);
            
            // Ако има зададени дефолт стратегии
            if (isset($mvc::$defaultStrategies) && countR($mvc::$defaultStrategies)) {
                
                // За всяко поле със стратегия, му се намира стойността
                foreach ($mvc::$defaultStrategies as $name => $strat) {
                    $value = self::getDefValueByStrategy($mvc, $rec, $name, $strat);
                    if($form->cmd == 'refresh' && $mvc->dontReloadDefaultsOnRefresh) continue;

                    $form->setDefault($name, $value);
                }
            }
        }
    }
    
    
    /**
     * Връща дефолтната стойност според стратегията
     * 
     * @param mixed $class 
     * @param int $folderId
     * @param string $field
     * 
     * @return mixed
     */
    public static function getDefaultValue($class, $folderId, $field)
    {
        $mvc = cls::get($class);
        $strategy = $mvc::$defaultStrategies[$field];
        if(empty($strategy)){
            
            return null;
        }
        
        $rec = (object)array('folderId' => $folderId);
        $value = self::getDefValueByStrategy($mvc, $rec, $field, $strategy);
      
        return $value;
    }
    
    
    /**
     *  Намира стойност по подразбиране на дадено поле
     */
    public static function getDefValueByStrategy(core_Mvc $mvc, $rec, $name, $strat)
    {
        $strat = keylist::toArray($strat);
        if (countR($strat)) {
           
            // За всяка от стратегиите
            foreach ($strat as $str) {
                $methodName = "getFrom{$str}";
                expect(cls::existsMethod('cond_plg_DefaultValues', $methodName), "Няма метод {$methodName}");
                
                if (!isset($mvc->fields[$name])) {
                    continue;
                }
                
                if ($value = static::$methodName($mvc, $rec, $name)) {
                    // Ако има състояние и е спряно, го прескачаме
                    $type = $mvc->fields[$name]->type;
                    if ($type instanceof type_Key) {
                        $vRec = $type->getRecForVal($value);
                            if ($vRec->state == 'closed') {
                                continue;
                            }
                    }
                    
                    // Първата стратегия върнала стойност се връща
                    return $value;
                }
            }
        }
    }


    /**
     * Намира стойността на полето от последния активен/затворен документ в папката за текущия потребител,
     *
     * @param core_Mvc $mvc
     * @param $rec
     * @param $name
     *
     * @return mixed
     */
    private static function getFromLastDocUser(core_Mvc $mvc, $rec, $name)
    {
        return self::getFromLastDocument($mvc, $rec->folderId, $name);
    }


    /**
     * Намира стойността на полето от последния активен/затворен документ в папката,
     *
     * @param core_Mvc $mvc
     * @param $rec
     * @param $name
     *
     * @return mixed
     */
    private static function getFromLastDoc(core_Mvc $mvc, $rec, $name)
    {
        return self::getFromLastDocument($mvc, $rec->folderId, $name, false);
    }


    /**
     * Намира стойността на полето от последния активен/затворен документ в папката,
     * от текущия потребител или без значение потребителя
     *
     * @param core_Mvc $mvc
     * @param $folderId
     * @param $name
     * @param bool $fromUser
     *
     * @return mixed
     */
    public static function getFromLastDocument(core_Mvc $mvc, $folderId, $name, $fromUser = true)
    {
        if (empty($folderId)) return;
        
        $cu = core_Users::getCurrent();
        $query = $mvc->getQuery();
        $query->where("#state IN ('active', 'closed')");
        $query->where("#folderId = {$folderId}");
        if ($fromUser) {
            $query->where("#createdBy = '{$cu}'");
        }

        // Извикване на събитие, за да може мениджъра да модифицира заявката
        $mvc->invoke('afterGetQueryFromLastDocumentDefault', array(&$query, $folderId, $name, $fromUser));
        $indexName = str::convertToFixedKey(str::phpToMysqlName(implode('_', arr::make('folderId'))));
        $query->useIndex($indexName);
        $query->orderBy('createdOn', 'DESC');
        $query->show($name);
        $query->limit(1);

        return $query->fetch()->{$name};
    }
    
    
    /**
     * Определяне ст-ст по подразбиране на полето template
     */
    public static function getFromLastDocSameCountry(core_Mvc $mvc, $rec, $name)
    {
        // Информацията за текущия контрагент
        $cData = doc_Folders::getContragentData($rec->folderId);
        
        // Намиране на последната продажба, на контрагент от същата държава
        $query = $mvc->getQuery();
        $query->where("#state IN ('active', 'closed')");
        $query->orderBy('#createdOn', 'DESC');
        $query->where("#folderId != {$rec->folderId}");
        $query->groupBy('folderId');
        $query->show("{$name},folderId");
        
        while ($oRec = $query->fetch()) {
            try {
                $cData2 = doc_Folders::getContragentData($oRec->folderId);
                if ($cData->countryId == $cData2->countryId) {
                    
                    // Ако контрагента е от същата държава
                    return $oRec->{$name};
                }
            } catch (core_exception_Expect $e) {
            }
        }
    }
    
    
    /**
     * Връща стойността според дефолт метода в мениджъра
     */
    private static function getFromDefMethod(core_Mvc $mvc, $rec, $name)
    {
        $name = "getDefault{$name}";
        
        if (cls::existsMethod($mvc, $name)) {
            
           return $mvc->$name($rec);
        }
    }
    
    
    /**
     * Връща стойността според кустом дефолтен метод в мениджъра
     */
    private static function getFromCustomMethod(core_Mvc $mvc, $rec, $name)
    {
        $name = "getCustomDefault{$name}";
        
        if (cls::existsMethod($mvc, $name)) {
            
            return $mvc->$name($rec);
        }
    }
    
    
    /**
     * Връща стойност от сесията
     */
    private static function getFromSessionValue(core_Mvc $mvc, $rec, $name)
    {
        if($fldType = $mvc->getFieldType($name)){
            if($fldType instanceof type_Key || $fldType instanceof type_Keylist){
                if($typeMvc = $fldType->params['mvc']){
                    $TypeMvc = cls::get($typeMvc);
                    if($TypeMvc->hasPlugin('plg_Current')){

                        return $TypeMvc->getCurrent('id', false);
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща стойност от на търговско условие
     *
     * @param string $salecondSysId - Sys Id на условие
     */
    private static function getFromClientCondition(core_Mvc $mvc, $rec, $name)
    {
        $fld = $mvc->fields[$name];
        if (isset($fld->salecondSysId)) {
            $cId = doc_Folders::fetchCoverId($rec->folderId);
            $Class = doc_Folders::fetchCoverClassId($rec->folderId);
            
            // Ако е контрагент само
            if (!cls::haveInterface('doc_ContragentDataIntf', $Class)) {
                
                return false;
            }
            
            return cond_Parameters::getParameter($Class, $cId, $fld->salecondSysId);
        }
    }
    
    
    /**
     * Връща данни за контрагента
     */
    private static function getFromClientData(core_Mvc $mvc, $rec, $name)
    {
        if (!isset($mvc->_cachedContragentData)) {
            
            // Ако документа няма такъв метод, се взимат контрагент данните от корицата
            $data = self::getCoverMethod($rec->folderId, 'getContragentData');
            if (empty($data)) {
                
                return;
            }
            
            $ownCountryId = crm_Companies::fetchOurCompany()->country;
            if (!$data->country) {
                $data->country = $ownCountryId;
            }
            
            if (!$data->countryId) {
                $data->countryId = $ownCountryId;
            }
            
            $mvc->_cachedContragentData = $data;
        }
        
        if ($dataField = $mvc->fields[$name]->contragentDataField) {
            $name = $dataField;
        }
        
        $Cover = (empty($rec->folderId)) ? 'crm_Persons' : doc_Folders::fetchCoverClassName($rec->folderId);
        if ($Cover == 'crm_Persons' && ($name == 'address')) {
            $name = 'p'.ucfirst($name);
        }
        
        if (isset($mvc->_cachedContragentData->{$name})) {
            
            return $mvc->_cachedContragentData->{$name};
        }
    }
    
    
    /**
     * Връща стойност от дефолт метод от корицата на документа,
     * с име getDefault{method_name}
     */
    private static function getFromCoverMethod(core_Mvc $mvc, $rec, $name)
    {
        $name = "getDefault{$name}";
        
        return self::getCoverMethod($rec->folderId, $name);
    }
    
    
    /**
     * Извиква метод( ако съществува ) от корицата на папка
     */
    private static function getCoverMethod($folderId, $name)
    {
        if (empty($folderId)) {
            
            return;
        }
        
        $cId = doc_Folders::fetchCoverId($folderId);
        $Class = cls::get(doc_Folders::fetchCoverClassId($folderId));
        
        if (cls::existsMethod($Class, $name)) {
            
            return $Class::$name($cId);
        }
    }
    
    
    /**
     * Връща ид-то на папката на река
     *
     * @param stdClass $rec - запис от модела
     *
     * @return int $folderId - ид на папката
     */
    private static function getFolderId(&$rec)
    {
        if (isset($rec->folderId)) {
            
            return;
        }
        if ($rec->originId) {
            $rec->folderId = doc_Containers::fetchField($rec->originId, 'folderId');
        } elseif ($rec->threadId) {
            $rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
        }
    }


    /**
     * Кои полета да се ъпдейтват при промяна с тези от визитката
     */
    public static function on_AfterGetContragentCoverFieldsToUpdate($mvc, &$res, $rec)
    {
        if(!$res){
            $res = array();
            if(isset($mvc::$updateContragentdataField)){
                $res = arr::make($mvc::$updateContragentdataField, true);
            }
        }
    }


    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = array())
    {
        if ($rec->folderId) {
            if (Mode::is('isMigrate')) return;

            $updateFields = $mvc->getContragentCoverFieldsToUpdate($rec);
            if (countR($updateFields) && isset($mvc::$defaultStrategies) && countR($mvc::$defaultStrategies)) {
                $fRec = doc_Folders::fetch($rec->folderId);
                
                if ($fRec && $fRec->coverClass && $fRec->coverId) {
                    if (cls::load($fRec->coverClass, true) && ($inst = cls::get($fRec->coverClass)) && $inst->haveRightFor('edit', $fRec->coverId)) {
                        $changedRecArr = array();
                        $fContrData = $inst->fetch($fRec->coverId);
                        
                        foreach ($updateFields as $cName => $name) {
                            if (!trim($rec->{$name})) {
                                continue;
                            }
                            
                            if (!($inst->fields[$cName])) {
                                continue;
                            }
                            
                            if ((isset($rec->{$name})) && (trim($rec->{$name}) != trim($fContrData->{$cName}))) {
                                $changedRecArr[$cName] = $rec->{$name};
                            }
                        }

                        if (!(empty($changedRecArr))) {
                            Request::setProtected('AutoChangeFields');
                            $updateLink = ht::createLink(tr('обновяване'), array($inst, 'edit', $fRec->coverId, 'AutoChangeFields' => serialize($changedRecArr), 'ret_url' => array($mvc, 'single', $rec->id)));
                            
                            status_Messages::newStatus("|Контактните данни се различават от тези във визитката. Ако желаете, направете|* {$updateLink}");
                        }
                    }
                }
            }
        }
    }
}
