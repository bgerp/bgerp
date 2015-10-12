<?php



/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 * 
 * Ако е заден класов параметър 'autoList' след създаване, обекта се вкарва в тази номенклатура
 * След оттегляне, ако обекта е бил перо, то се затваря. Затворените но неизползвани пера се изтриват по разписание
 * След възстановяване ако обекта е бил перо, отваряме му перото
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_Registry extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('acc_RegisterIntf');
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if (!empty($mvc->autoList)) {
        	
            // Автоматично добавяне към номенклатурата $autoList, след създаване на обекта
            expect($autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->autoList), 'id'));
            $lists = keylist::addKey('', $autoListId);
            acc_Lists::updateItem($mvc, $rec->id, $lists);
            
            if(haveRole('ceo,acc')){
            	$list = acc_Lists::fetchField("#systemId = '{$mvc->autoList}'", 'name');
            	core_Statuses::newStatus(tr("|Обекта е добавен в номенклатура|*: {$list}"));
            }
        }
    }
    
    
    /**
     * След запис
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
    	$added = FALSE;
    	
    	// Ако е зададено да се добави в номенклатура при активиране
    	if(!empty($mvc->addToListOnActivation)){
    		if($rec->state == 'active'){
    		
    			// Ако документа става перо при активиране, добавяме го като перо, ако вече не е
    			if($mvc->canAddToListOnActivation($rec)){
    				if($mvc->forceItem($rec, $mvc->addToListOnActivation)){
    					$added = TRUE;
    				}
    			}
    		} 
    	}
    	
    	// Ако обекта не е бил добавен като ново перо
    	if(!$added){
    		
    		// Ако е активно състоянието и обекта е перо
    		if($rec->state != 'closed' && $rec->state != 'rejected'){
    			
    			// Активираме перото
    			if($itemRec = acc_Items::fetchItem($mvc, $rec->id)){
    				if($itemRec->state != 'active'){
    					if(haveRole('ceo,acc')){
    						if($itemRec->lists){
    							core_Statuses::newStatus(tr("|Активирано е перо|*: {$itemRec->title}"));
    						} else {
    							core_Statuses::newStatus(tr("|Перо|*: {$itemRec->title} е без номенклатури"));
    						}
    					}
    				}
    				
    				acc_Lists::updateItem($mvc, $rec->id, $itemRec->lists);
    			}
    		}
    	}
    	
    	// Ако обекта е затворен или оттеглен
    	// Отбелязваме перото му, че е за затваряне
    	if($rec->state == 'rejected' || $rec->state == 'closed'){
    		$mvc->closeItems[$rec->id] = $rec; 
    	}
    }
    	 
    
    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
    	// Ако има пера отбелязани за затваряне, затваряме ги. Затварянето на перата трябва
    	// да става на on_Shutdown, поради това, че някои пера може да са начало на нишка, 
    	// а ако те се затворят преди да се е оттеглила цялата нишка това води до не пълно оттегляне
    	// затова затваряме перото след като са се изпълнили всички други действия на плъгините
    	if(count($mvc->closeItems)){
    		foreach ($mvc->closeItems as $rec) {
    			if($itemRec = acc_Items::fetchItem($mvc, $rec->id)){
    				if($itemRec->state == 'active'){
	    				acc_Lists::removeItem($mvc, $rec->id);
	    				 
	    				if(haveRole('ceo,acc')){
	    					core_Statuses::newStatus(tr("|Затворено е перо|*: {$itemRec->title}"));
	    				}
    				}
    			}
    		}
    	}
    }
    
    
    
    /**
     * Метод по подразбиране дали обекта може да се добави в номенклатура при активиране
     */
    public static function on_AfterForceItem($mvc, &$res, $id, $listSysId)
    {
    	if($rec) return;
    	
    	$rec = $mvc->fetchRec($id);
    	$msg = FALSE;
    	
    	$listRec = acc_Lists::fetchBySystemId($listSysId);
    	if($listRec){
    		
    		// Ако обекта е перо, но не е в номенклатурата форсираме го
    		if($itemRec = acc_Items::fetchItem($mvc, $rec->id)){
    			if(!keylist::isIn($listRec->id, $itemRec->lists)){
    				$lists = keylist::addKey($itemRec->lists, $listRec->id);
    				$msg = TRUE;
    			} else {
    				$lists = $itemRec->lists;
    			}
    		} else {
    			$lists = keylist::addKey('', $listRec->id);
    			$msg = TRUE;
    		}
    		
    		acc_Lists::updateItem($mvc, $rec->id, $lists);
    		
    		// Ъпдейтваме информацията за перото
    		if($msg){
    			if(haveRole('ceo,acc')){
    				$title = $mvc->getTitleById($rec->id);
    				core_Statuses::newStatus("|*'{$title}' |е добавен в номенклатура|* '{$listRec->name}'");
    			}
    		}
    		
    		$res = TRUE;
    	}
    }
    
    
    /**
     * Метод по подразбиране дали обекта може да се добави в номенклатура при активиране
     */
    public static function on_AfterCanAddToListOnActivation($mvc, &$res, $rec)
    {
    	if(!$res){
    		$res = TRUE;
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        if($res != 'no_one' && $action == 'delete' && isset($rec)){
            if(acc_Items::fetchItem($mvc->getClassId(), $rec->id)){
                
                // Не може да се изтрива ако обекта вече е перо
                $res = 'no_one';
            }
        }
    }
}
