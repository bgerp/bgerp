<?php



/**
 * Клас 'plg_Current' - Прави текущ за сесията избран запис от модела
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_Current extends core_Plugin
{
	/**
     * След дефиниране на полетата на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
    	// Ако има поле за отговорник
    	if(isset($mvc->inChargeField)){
    		
    		// Трябва да е инстанция на type_UserList
    		expect($mvc->getFieldType($mvc->inChargeField) instanceof type_UserList, 'Полето за отговорник трябва да е от типа type_UserList');
    	}
    }
    
    
    /**
     * Връща указаната част (по подразбиране - id-то) на текущия за сесията запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param string $part поле от модела-домакин
     * @param boolean $bForce Дали да редирект към мениджъра ако не е избран текущ обект
     */
    function on_AfterGetCurrent($mvc, &$res, $part = 'id', $bForce = TRUE)
    {
        if(!$res) {
        	
        	// Опитваме се да вземем от сесията текущия обект
            $res = Mode::get('currentPlg_' . $mvc->className)->{$part};
            
            // Ако в сесията го има обекта, връщаме го
            if($res) return;
            
            // Ако форсираме
            if($bForce){
            	
            	// И има поле за отговорник
            	if(isset($mvc->inChargeField)){
            	
            		// Извличаме обектите, на които е отговорник потребителя
            		$query = $mvc->getQuery();
            		$cu = core_Users::getCurrent('id', FALSE);
            		$query->where("#{$mvc->inChargeField} = {$cu} || #{$mvc->inChargeField} LIKE '%|{$cu}|%'");
            		
            		// Ако е точно един обект и все още потребителя има права да му бъде отговорник, го връщаме
            		if($query->count() == 1 && haveRole($mvc->getFieldType($mvc->inChargeField)->getRoles())){
            			$rec = $query->fetch();
            			Mode::setPermanent('currentPlg_' . $mvc->className, $rec);
            			
            			$res = $rec->id;
            			
            			return;
            		}
            	}
            	
            	// Ако няма резултат, и името на класа е различно от класа на контролера (за да не стане безкрайно редиректване)
            	if(empty($res) && ($mvc->className != Request::get('Ctr'))){
            		$msg = tr("Моля, изберете текущ/а");
            		$msg .= " " . tr($mvc->singleTitle);
            	
            		// Подканваме потребителя да избере обект от модела, като текущ
            		redirect(array($mvc, 'list', 'ret_url' => TRUE), FALSE, $msg);
            	}
            }
        }
    }
    
    
    /**
     * Автоматично избиране на обект, ако потребителя има права
     */
    public static function on_AfterSelectSilent(core_Mvc $mvc, &$res, $id)
    {
    	// Трябва да има запис отговарящ на ид-то
    	expect($rec = $mvc->fetch($id));
    	
    	// Кой е текущия потребител
    	$cu = core_Users::getCurrent();
    	$currentObject = $mvc->getCurrent('id', FALSE);
    	
    	// Ако потребителя може да избере обекта, и той вече не е избран
    	if($mvc->haveRightFor('select', $rec) && $currentObject != $id){
    		
    		// Вътрешен редирект към екшъна за избиране
    		Request::forward(array('Ctr' => $mvc->className, 'Act' => 'SetCurrent', 'id' => $id));
    		
    		// Слагане на нотификация
    		$objectName = $mvc->getTitleById($id);
    		$singleTitle = mb_strtolower($mvc->singleTitle);
    		
    		// Добавяме статус съобщението
            core_Statuses::newStatus(tr("|Успешно логване в {$singleTitle}|* \"{$objectName}\""));
    		
    		// Ако всичко е наред връща се ид-то на обекта
    		return $res = $id;
    	}
    	
    	// Ако не може да избере обект връща се FALSE
    	$res = FALSE;
    }
    
    
    /**
     * Слага id-тo на даден мениджър в сесия
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param string $action
     * @return boolean
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'setcurrent') {
           
            $id = Request::get('id', 'int');
            
            expect($rec = $mvc->fetch($id));
            
            $mvc->requireRightFor('select', $rec);
            
            Mode::setPermanent('currentPlg_' . $mvc->className, $rec);
            
            if(!Request::get('ret_url')) {
                $res = new Redirect(array($mvc));
            } else {
                $res = new Redirect(getRetUrl());
            }
            
            return FALSE;
        }
    }
    
    
    /**
     * Добавя функционално поле 'currentPlg'
     *
     * @param $mvc
     */
    function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        $data->listFields['currentPlg'] = "Текущ";
        $mvc->FNC('currentPlg', 'varchar', 'caption=Терминал,tdClass=centerCol');
    }
    
    
    /**
     * Слага съдържание на полето 'currentPlg'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Проверяваме имали текущ обект
    	$currentId = $mvc->getCurrent('id', FALSE);
        
        if ($rec->id == $currentId) {
        	
        	// Ако записа е текущия обект, маркираме го като избран
            $row->currentPlg = ht::createElement('img', array('src' => sbf('img/16/accept.png', ''), 'width' => '16px', 'height' => '16px'));
            $row->ROW_ATTR['class'] .= ' state-active';
        } elseif($mvc->haveRightFor('select', $rec)) {
        	
        	// Ако записа не е текущия обект, но може да бъде избран добавяме бутон за избор
            $row->currentPlg = ht::createBtn('Избор', array($mvc, 'SetCurrent', $rec->id, 'ret_url' => getRetUrl()), NULL, NULL, 'ef_icon = img/16/key.png, title=Предпочитание за текущ');
            $row->ROW_ATTR['class'] .= ' state-closed';
        } else {
        	
        	// Ако записа не е текущия обект и не може да бъде избран оставяме го така
        	$row->ROW_ATTR['class'] .= ' state-closed';
        }
    }
	
	
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'select' && isset($rec)){
    		
    		if($rec->state == 'rejected'){
    			
    			// Никой не може да се логва в оттеглен обект
    			$res = 'no_one';
    		} else {
    			
    			// Ако има поле за отговорник и текущия потребител, не е отговорник или е отговорник но с премахнати права, той няма права да избира
    			if(!(isset($mvc->canSelectAll) && haveRole($mvc->canSelectAll)) && isset($mvc->inChargeField)
    			&& (!keylist::isIn($userId, $rec->{$mvc->inChargeField}) || (keylist::isIn($userId, $rec->{$mvc->inChargeField}) && !haveRole($mvc->getFieldType($mvc->inChargeField)->getRoles())))){
    				 
    				$res = 'no_one';
    			}
    		}
    	}
    }
}