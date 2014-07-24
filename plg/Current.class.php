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
    		
    		// Трябва в модела да има такова поле
    		$field = $mvc->getField($mvc->inChargeField);
    		
    		// Трябва да е инстанция на type_UserList
    		expect($field->type instanceof type_UserList, 'Полето за отговорник трябва да е от типа type_UserList');
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
            $res = Mode::get('currentPlg_' . $mvc->className)->{$part};
            
            if($bForce && (!$res) && ($mvc->className != Request::get('Ctr'))) {
            
            	if(isset($mvc->inChargeField)){
            		
	            	// Ако потребителя има достъп само до 1 запис,
		            // той се приема за избран
		            $query = $mvc->getQuery();
		            $cu = core_Users::getCurrent();
					$query->where("#{$mvc->inChargeField} = {$cu} || #{$mvc->inChargeField} LIKE '%|{$cu}|%'");
		           
					if($query->count() == 1){
		            	$rec = $query->fetch();
		            	Mode::setPermanent('currentPlg_' . $mvc->className, $rec);
		            	return $res = $rec->id;
		            }
            	}
            	
            	$msg = tr("Моля, изберете текущ/а");
            	$msg .= " " . tr($mvc->singleTitle);
            	redirect(array($mvc,'ret_url' => TRUE), FALSE, $msg);
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
        $currentId = $mvc->getCurrent();
        
        if ($rec->id == $currentId) {
            $row->currentPlg = ht::createElement('img', array('src' => sbf('img/16/accept.png', ''), 'style' => 'margin-left:20px;', 'width' => '16px', 'height' => '16px'));
            $row->ROW_ATTR['class'] .= ' state-active';
        } elseif($mvc->haveRightFor('select', $rec)) {
            $row->currentPlg = ht::createBtn('Избор', array($mvc, 'SetCurrent', $rec->id, 'ret_url' => getRetUrl()), NULL, NULL, 'ef_icon = img/16/key.png');
            $row->ROW_ATTR['class'] .= ' state-closed';
        }
    }
	
	
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'select' && isset($rec)){
    		
    		// Ако има поле за отговорник и текущия потребител
    		// не е отговорник, той няма права да избира
    		if(!(isset($mvc->canSelectAll) && haveRole($mvc->canSelectAll)) && isset($mvc->inChargeField) && ($rec->{$mvc->inChargeField} != $userId && strpos($rec->{$mvc->inChargeField}, "|$userId|") === FALSE)){
	    		$res = 'no_one';
	    	} 
    	}
    }
}