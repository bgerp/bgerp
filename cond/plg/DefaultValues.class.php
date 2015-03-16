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
 * lastDocSameCuntry  - Последния активен документ в папка на клиент от същата държава				 
 * defMethod	      - Дефолт метод с име "getDefault{$name}"		 
 * clientData	      - От контрагент интерфейса					 
 * clientCondition    - От дефолт търговско условие				 
 * coverMethod	      - Метод от корицата с име "getDefault{$name}"
 *  
 * -------------------------------------------------------------
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
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
    }
    
    
	/**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);

        if (isset($plugins['doc_DocumentPlg'])) {
            return FALSE;
        } 
        
        return TRUE;
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
    	
    	if(empty($rec->id)){
    		self::getFolderId($rec);
    		
    		// Ако има зададени дефолт стратегии
    		if(isset($mvc::$defaultStrategies) && count($mvc::$defaultStrategies)){
    			
    			// За всяко поле със стратегия, му се намира стойността
    			foreach ($mvc::$defaultStrategies as $name => $strat){
    				$value = self::getDefValue($mvc, $rec, $name, $strat);
    				$form->setDefault($name, $value);
    			}
    		}
    	}
    }
    
    
    /**
     *  Намира стойност по подразбиране на дадено поле
     */
    private static function getDefValue(core_Mvc $mvc, $rec, $name, $strat)
    {
    	$strat = keylist::toArray($strat);
    	if(count($strat)){
    		
    		// За всяка от стратегиите
    		foreach ($strat as $str){
    			$methodName = "getFrom{$str}";
    			expect(cls::existsMethod('cond_plg_DefaultValues', $methodName), "Няма метод {$methodName}");
    			expect(isset($mvc->fields[$name]), "{$name} не е поле от модела");
    			
    			if($value = static::$methodName($mvc, $rec, $name)){
    				
    				// Първата стратегия върнала стойност се връща
    				return $value;
    			}
    		}
    	}
    	
    	// Ако никоя от стратегиите не намери валидна стойност
    	return NULL;
    }
    
    
    /**
     * Намира последния документ в дадена папка от същия потребител
     * @param core_Mvc $mvc - мениджъра
     * @param int $folderId - ид на папката
     * @param boolean $fromUser - дали документа да е от текущия
     * потребител или не
     * @return mixed $rec - последния запис
     */
    private static function getFromLastDocUser(core_Mvc $mvc, $rec, $name)
    {
    	return self::getFromLastDocument($mvc, $rec->folderId, $name);
    }
    
    
	/**
     * Намира последния документ в дадена папка
     * @param core_Mvc $mvc - мениджъра
     * @param int $folderId - ид на папката
     * @param boolean $fromUser - дали документа да е от текущия
     * потребител или не
     * @return mixed $rec - последния запис
     */
    private static function getFromLastDoc(core_Mvc $mvc, $rec, $name)
    {
    	return self::getFromLastDocument($mvc, $rec->folderId, $name, FALSE);
    }
    
    
    /**
     * Намира последния документ в дадена папка
     */
    private static function getFromLastDocument(core_Mvc $mvc, $folderId, $name, $fromUser = TRUE)
    {
    	if(empty($folderId)) return;
    	
    	$cu = core_Users::getCurrent();
    	$query = $mvc->getQuery();
    	$query->where("#state = 'active'");
    	
    	$query->where("#folderId = {$folderId}");
    	if($fromUser){
    		$query->where("#createdBy = {$cu}");
    	}
    	
    	$query->orderBy('createdOn', 'DESC');
    	$query->show($name);
    	$query->limit(1);
    	
    	return $query->fetch()->$name;
    }
    
    
	/**
     * Определяне ст-ст по подразбиране на полето template
     */
    public static function getFromLastDocSameCuntry(core_Mvc $mvc, $rec, $name)
    {
    	// Информацията за текущия контрагент
    	$cData = doc_Folders::getContragentData($rec->folderId);
    	
    	// Намиране на последната продажба, на контрагент от същата държава
    	$query = $mvc->getQuery();
        $query->where("#state = 'active'");
        $query->orderBy("#createdOn", "DESC");
        $query->where("#folderId != {$rec->folderId}");
        $query->show("{$name},folderId");
        
        while($oRec = $query->fetch()){
            try {
                $cData2 = doc_Folders::getContragentData($oRec->folderId);
                if($cData->countryId == $cData2->countryId){
                    
                    // Ако контрагента е от същата държава 
                    return $oRec->$name;
                }
            } catch(core_exception_Expect $e){}
        }
        
        return NULL;
    }
    
    
	/**
     * Връща стойността според дефолт метода в мениджъра
     */
    private static function getFromDefMethod(core_Mvc $mvc, $rec, $name)
    {
    	$name = "getDefault{$name}";

        if(cls::existsMethod($mvc, $name)){
    		
    		return $mvc->$name($rec);
    	}
    }
    
    
	/**
     * Връща стойност от на търговско условие
     * @param string $salecondSysId - Sys Id на условие
     */
    private static function getFromClientCondition(core_Mvc $mvc, $rec, $name)
    {	
    	$fld = $mvc->fields[$name];
    	if(isset($fld->salecondSysId)){
    		$cId = doc_Folders::fetchCoverId($rec->folderId);
    		$Class = doc_Folders::fetchCoverClassId($rec->folderId);

    		// Ако е контрагент само
    		if(!cls::haveInterface('doc_ContragentDataIntf', $Class)) return FALSE;
    		
    		return cond_Parameters::getParameter($Class, $cId, $fld->salecondSysId);
    	}
    }
    
    
	/**
     * Връща данни за контрагента
     */
    private static function getFromClientData(core_Mvc $mvc, $rec, $name)
    {
    	if(!isset($mvc->_cashedContragentData)){
	    	
    		// Ако документа няма такъв метод, се взимат контрагент данните от корицата
	    	$data = self::getCoverMethod($rec->folderId, 'getContragentData');
	    	if(empty($data)) return;
	    	
	    	$conf = core_Packs::getConfig('crm');
	    	if(!$data->country){
		    	$data->country = $conf->BGERP_OWN_COMPANY_COUNTRY;
	    	}
	    	if(!$data->countryId){
	    		$data->countryId = drdata_Countries::fetchField("#commonName = '{$conf->BGERP_OWN_COMPANY_COUNTRY}'", 'id');
	    	}
	    	
    		$mvc->_cashedContragentData = $data;
    	}
    	
    	if($dataField = $mvc->fields[$name]->contragentDataField){
    		$name = $dataField;
    	}
    	
    	$Cover = (empty($rec->folderId)) ? 'crm_Persons' : doc_Folders::fetchCoverClassName($rec->folderId);
    	if(isset($mvc->_cashedContragentData->{$name})){
    		return $mvc->_cashedContragentData->{$name};
    	}
    	
    	if($Cover == 'crm_Persons'){

    		return $mvc->_cashedContragentData->{"p".ucfirst($name)};
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
    	if(empty($folderId)) return;
    	
    	$cId = doc_Folders::fetchCoverId($folderId);
    	$Class = cls::get(doc_Folders::fetchCoverClassId($folderId));
    	
    	if(cls::existsMethod($Class, $name)){
	    	
    		return $Class::$name($cId);
	    }
    }
    
    
	/**
     * Връща ид-то на папката на река
     * @param stdClass $rec - запис от модела
     * @return int $folderId - ид на папката
     */
    private static function getFolderId(&$rec)
    {
    	if(isset($rec->folderId)) return;
    	if($rec->originId){
    		$rec->folderId = doc_Containers::fetchField($rec->originId, 'folderId');
    	} elseif($rec->threadId){
    		$rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
    	}
    }
}