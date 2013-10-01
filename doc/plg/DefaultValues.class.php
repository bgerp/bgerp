<?php
/**
 * Клас 'doc_plg_DefaultValues'
 * 
 * Плъгин слагащ на полетата от модела дефолт стойности според
 * определена стратегия
 * Слага се default стойност на всички полета, имащи атрибут 'defaultStrategy'
 * със стойност някоя от предефинираните стратегии в плъгина
 * 
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_DefaultValues extends core_Plugin
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
     * Намира последния документ в дадена папка 
     * @param core_Mvc $mvc - мениджъра
     * @param int $folderId - ид на папката
     * @param boolean $fromUser - дали документа да е от текущия
     * потребител или не
     * @return mixed $rec - последния запис
     */
    private static function getLastDocumentValue(core_Mvc $mvc, $folderId, $fromUser = TRUE, $name)
    {
    	$cu = core_Users::getCurrent();
    	$query = $mvc->getQuery();
    	$query->where("#folderId = {$folderId}");
    	if($fromUser){
    		$query->where("#createdBy = {$cu}");
    	}
    	
    	$query->orderBy('createdOn', 'DESC');
    	$query->show($name);
    	return $query->fetch();
    }
    
    
    /**
     * Връща стойността според дефолт метода в мениджъра или мениджъра на
     * контрагента на папката
     * @param core_Mvc $mvc - мениджър
     * @param string $name - име на поле
     * @param stdClass $rec - запис от модела
     * @return mixed - дефолт стойноста върната от съответния метод
     */
    private static function getFromDefaultMethod(core_Mvc $mvc, $name, $rec)
    {
    	if(cls::existsMethod($mvc, $name)){
    		return $mvc->$name($rec);
    	}
    	
    	return static::getCoverMethod($rec->folderId, $name);
    }
    
    
    /**
     * Извиква метод( ако съществува ) от корицата на папка
     * @param int $folderId - ид на папка
     * @param string $name - име на метод
     */
    private static function getCoverMethod($folderId, $name)
    {
    	$cId = doc_Folders::fetchCoverId($folderId);
    	$Class = cls::get(doc_Folders::fetchCoverClassId($folderId));
    	if(cls::existsMethod($Class, $name)){
	    	return $Class::$name($cId);
	    }
	    
	    return NULL;
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	if(empty($form->rec->id)){
    		
	    	// Намират се всички полета със зададено 'defaultStrategy'
	    	$strategyFields = $mvc->selectFields("#defaultStrategy");
	    	if(count($strategyFields)){
		    	foreach($strategyFields as $name => $fld){
		    		
		    		// Намира се дефолт стойността на полето спрямо стратегията
		    		$form->rec->{$name} = static::getDefault($mvc, $name, $data->form->rec, $fld);
		    	}
	    	}
    	}
    }
    
    
    /**
     * Намира търговско условие
     * @param int $folderId - ид на папка
     * @param string $salecondSysId - Sys Id на условие
     */
    private static function getFromSaleCondition($folderId, $salecondSysId)
    {
    	$cId = doc_Folders::fetchCoverId($folderId);
    	$Class = doc_Folders::fetchCoverClassId($folderId);
    	
        return salecond_Parameters::getParameter($Class, $cId, $salecondSysId);
    }
    
    
    /**
     * Намира дефолт стойносртта на дадено поле според неговата стратегия
     * @param core_Mvc $mvc - мениджър
     * @param string $name - име на поле
     * @param stdClass $rec - запис от модела
     * @param int $strategy - стратегия
     * @return mixed $value - дефолт стойността
     */
    private static function getDefault(core_Mvc $mvc, $name, $rec, $fld)
    {
    	switch($fld->defaultStrategy){
    		case '1':
				$value = static::getStrategyOne($mvc, $name, $rec, $fld);
    			break;
    		case '2':
    			$value = static::getContragentData($mvc, $name, $rec);
    			break;
    		case '3':
    			//$value = static::getStrategyThree($mvc, $name, $rec->folderId, $cu);
    			break;
    	}
    	
    	return $value;
    }
    
    
    /**
     * Връща контрагентски данни
     */
    private static function getContragentData($mvc, $name, $rec)
    {
    	if(!$folderId = $rec->folderId){
    		if($rec->originId){
    			$folderId = doc_Containers::fetchField($rec->originId, 'folderId');
    		} elseif($rec->threadId){
    			$folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
    		}
    	}
    	
    	// Ако документа поддържа контрагент интерфейса, извличаме данните за
    	// контрагента от последния документ в тази папка
    	if(cls::haveInterface('doc_ContragentDataIntf', $mvc)){
	    	$query = $mvc->getQuery();
	    	$query->where("#folderId = {$folderId}");
	    	$query->orderBy('createdOn', 'DESC');
	    	$query->show('id');
	    	if($lastRec = $query->fetch()){
	    		$data = $mvc::getContragentData($lastRec->id);
	    	}
    	}
    	
    	if(!$data) {
    		
    		// Ако документа няма такъв метод, се взимат контрагент данните от корицата
    		$data = static::getCoverMethod($folderId, 'getContragentData');
    	}
    	
    	if($name == 'country'){
    		if(!$data->country){
	    		$conf = core_Packs::getConfig('crm');
	    		$data->country = $conf->BGERP_OWN_COMPANY_COUNTRY;
    		}
    	}
    	
	    if(isset($data->{$name})){
	    	return $data->{$name};
	    } elseif(isset($data->{"p".ucfirst($name).""})){
	    	return $data->{"p".ucfirst($name).""};
	    }
    }
    
    
    /**
     * Намира дефолт стойност по стратегия 1
     * @param core_Mvc $mvc - мениджър
     * @param string $name - име на поле
     * @param stdClass $rec - запис от модела
     * @return mixed $value - дефолт стойността
     */
    private static function getStrategyOne(core_Mvc $mvc, $name, $rec, $fld)
    {
    	if(!$folderId = $rec->folderId){
    		if($rec->originId){
    			$rec->folderId = doc_Containers::fetchField($rec->originId, 'folderId');
    		} elseif($rec->threadId){
    			$rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
    		} else {
    			return NULL;
    		}
    	}
    	
    	// Последния документ от потребителя в същата папка
    	$value = static::getLastDocumentValue($mvc, $rec->folderId, TRUE, $name)->{$name};
    	$value = NULL;
    	// Последния документ в същата папка
    	if(!$value){
    		$value = static::getLastDocumentValue($mvc, $rec->folderId, FALSE, $name)->{$name};
    	}
    	$value = NULL;
    	// Дефолт метода на мениджъра
    	if(!$value){
    		$value = static::getFromDefaultMethod($mvc, "getDefault{$name}" , $rec);
    	}
    	
    	// Търси за търговско условие 
    	if(!$value && isset($fld->salecondSysId)){
    		$value = static::getFromSaleCondition($rec->folderId, $fld->salecondSysId);
    	}
    	
    	return $value;
    }
}