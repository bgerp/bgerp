<?php
/**
 * Клас 'doc_plg_DefaultValues'
 * 
 * Плъгин слагащ на полетата от модела дефолт стойности според
 * определена стратегия
 * Слага се default стойност на всички полета, имащи атрибут 'defaultStrategy'
 * със стойност някоя от предефинираните стратегии в плъгина
 * 
 * Допустими стратегии са:
 * 1 - @TODO
 * 2 - @TODO
 * 3 - @TODO
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
    private static function getLastDocument(core_Mvc $mvc, $folderId, $fromUser = TRUE)
    {
    	$cu = core_Users::getCurrent();
    	$query = $mvc->getQuery();
    	$query->where("#folderId = {$folderId}");
    	if($fromUser){
    		$query->where("#createdBy = {$cu}");
    	}
    	$query->orderBy('createdOn', 'DESC');
    	
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
    	
    	$cId = doc_Folders::fetchCoverId($rec->folderId);
    	$Class = cls::get(doc_Folders::fetchCoverClassId($rec->folderId));
    	if(cls::haveInterface('doc_ContragentDataIntf', $Class) && cls::existsMethod($Class, $name)){
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
     * 
     * Enter description here ...
     * @param int $folderId
     * @param core_FieldSet $fld
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
     * 
     * Enter description here ...
     * @param unknown_type $mvc
     * @param unknown_type $name
     * @param unknown_type $rec
     */
    private function getContragentData($mvc, $name, $rec)
    {
    	if(!$folderId = $rec->folderId){
    		if($rec->originId){
    			$folderId = doc_Containers::fetchField($rec->originId, 'folderId');
    		} elseif($rec->threadId){
    			$folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
    		}
    	}
    	
    	if(cls::haveInterface('doc_ContragentDataIntf', $mvc)){
	    	$query = $mvc->getQuery();
	    	$query->where("#folderId = {$folderId}");
	    	$query->orderBy('createdOn', 'DESC');
	    	$lastRec = $query->fetch();
	    	if($lastRec && cls::existsMethod($mvc, 'getContragentData')){
	    		$data = $mvc::getContragentData($lastRec->id);
	    	}
    	}
    	
    	if(!$data) {
    		$contragentClass = doc_Folders::fetchCoverClassName($rec->folderId);
    		$contragentId = doc_Folders::fetchCoverId($rec->folderId);
    		$data = $contragentClass::getContragentData($contragentId);
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
    			$folderId = doc_Containers::fetchField($rec->originId, 'folderId');
    		} elseif($rec->threadId){
    			$folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
    		} else {
    			return NULL;
    		}
    	}
    	
    	// Последния документ от потребителя в същата папка
    	$value = static::getLastDocument($mvc, $folderId)->{$name};
    	
    	// Последния документ в същата папка
    	if(!$value){
    		$value = static::getLastDocument($mvc, $folderId, FALSE)->{$name};
    	}
    	
    	// Дефолт метода на мениджъра
    	if(!$value){
    		$value = static::getFromDefaultMethod($mvc, $name, $rec);
    	}
    	
    	if(!$value && isset($fld->salecondSysId)){
    		$value = static::getFromSaleCondition($folderId, $fld->salecondSysId);
    	}
    	
    	return $value;
    }
}