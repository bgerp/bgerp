<?php



/**
 * Плъгин добавящ към документ следните състояние: чернова, чакащо, аквитно, приключено, спряно, оттеглено и събудено
 * и управляващ преминаването им от едно в друго
 * 
 * Преминаванията от състояние в състояние са следните:
 * 
 * Чернова    (draft)    -> чакащо, активно или оттеглено
 * Чакащо     (pending)  -> активно или оттеглено
 * Активно    (active)   -> спряно, приключено или оттеглено
 * Приключено (closed)   -> събудено или оттеглено
 * Спряно     (stopped)  -> активно или оттеглено
 * Събудено   (wakeup)   -> приключено или оттеглено
 * Оттеглено  (rejected) -> възстановено до някое от горните състояния
 * 
 * При активиране (от чернова) документа става активен, ако искаме да се премине в друго състояние
 * например чакащо в мениджъра трябва да има метод $mvc->getActivatedState($rec) който да върне 'pending'
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_plg_StateManager extends core_Plugin
{
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		// Ако липсва, добавяме поле за състояние
		if (!$mvc->fields['state']) {
			$mvc->FLD('state', 'enum(draft=Чернова, pending=Чакащо,active=Активирано, rejected=Оттеглено, closed=Приключено, stopped=Спряно, wakeup=Събудено)', 'caption=Състояние, input=none');
		}
	}


	/**
	 * След подготовка на тулбара на единичен изглед.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		$rec = &$data->rec;
		 
		// Добавяне на бутон за приключване
		if($mvc->haveRightFor('close', $rec)){
			$data->toolbar->addBtn("Приключване", array($mvc, 'changeState', $rec->id, 'type' => 'close', 'ret_url' => TRUE), 'ef_icon = img/16/lightbulb_off.png,title=Приключване на документа,warning=Сигурнили сте че искате да приключите документа');
		}
		 
		// Добавяне на бутон за спиране
		if($mvc->haveRightFor('stop', $rec)){
			$data->toolbar->addBtn("Спиране", array($mvc, 'changeState', $rec->id, 'type' => 'stop', 'ret_url' => TRUE), 'ef_icon = img/16/control_pause.png,title=Спиране на документа,warning=Сигурнили сте че искате да спрете документа');
		}
		 
		// Добавяне на бутон за събуждане
		if($mvc->haveRightFor('wakeup', $rec)){
			$data->toolbar->addBtn("Събуждане", array($mvc, 'changeState', $rec->id, 'type' => 'wakeup', 'ret_url' => TRUE), 'ef_icon = img/16/lightbulb.png,title=Събуждане на документа,warning=Сигурнили сте че искате да събудите документа');
		}
		 
		// Добавяне на бутон за активиране от различно от чернова състояние
		if($mvc->haveRightFor('activateAgain', $rec)){
			$data->toolbar->addBtn("Активиране", array($mvc, 'changeState', $rec->id, 'type' => 'activateAgain', 'ret_url' => TRUE, ), 'ef_icon = img/16/control_play.png,title=Активиране на документа,warning=Сигурнили сте че искате да активирате документа');
		}
		
		// Добавяне на бутон запървоначално активиране
		if($mvc->haveRightFor('activate', $rec)){
			$data->toolbar->addBtn("Активиране", array($mvc, 'changeState', $rec->id, 'type' => 'activate', 'ret_url' => TRUE, ), 'ef_icon = img/16/lightning.png,title=Активиране на документа,warning=Сигурнили сте че искате да активирате документа');
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'close' || $action == 'stop' || $action == 'wakeup' || $action == 'activateagain' || $action == 'activate') && isset($rec)){
	
			switch($action){
				case 'close':
	
					// Само активните и събудените могат да бъдат приключени
					if($rec->state != 'active' && $rec->state != 'wakeup'){
						$requiredRoles = 'no_one';
					}
					break;
				case 'stop':
	
					// Само активните могат да бъдат спрени
					if($rec->state != 'active'){
						$requiredRoles = 'no_one';
					}
					break;
				case 'wakeup':
	
					// Само приключените могат да бъдат събудени
					if($rec->state != 'closed'){
						$requiredRoles = 'no_one';
					}
					break;
				case 'activateagain':
	
					// Дали може да бъде активирана отново, след като е било променено състоянието
					if($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'wakeup' || $rec->state == 'rejected' || $rec->state == 'draft' || $rec->state == 'pending'){
						$requiredRoles = 'no_one';
					}
					break;
				case 'activate':
					
					// Само приключените могат да бъдат събудени
					if($rec->state != 'draft'){
						$requiredRoles = 'no_one';
					}
					break;
			}
	
			if($requiredRoles != 'no_one'){
				 
				// Минимални роли за промяна на състоянието
				$requiredRoles = $mvc->getRequiredRoles('changestate', $rec);
			}
		}
	}
	
	
	/**
	 * Преди изпълнението на контролерен екшън
	 *
	 * @param core_Manager $mvc
	 * @param core_ET $res
	 * @param string $action
	 */
	public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
	{
		if(strtolower($action) == 'changestate') {
			$mvc->requireRightFor('changestate');
    	
    		expect($id = Request::get('id', 'int'));
    		expect($rec = $mvc->fetch($id));
    		expect($action = Request::get('type', 'enum(close,stop,wakeup,activateAgain,activate)'));
    	
    		// Проверяваме правата за съответното действие затваряне/активиране/спиране/събуждане
    		$mvc->requireRightFor($action, $rec);
    	
    		$rec->brState = $rec->state;
    		switch($action){
    			case 'close':
    				$rec->state = 'closed';
    				$action = 'Приключване';
    				break;
    			case 'stop':
    				$rec->state = 'stopped';
    				$action = 'Спиране';
    				break;
    			case 'wakeup':
    				$rec->state = 'wakeup';
    				$action = 'Събуждане';
    			break;
    			case 'activateAgain':
    				$rec->state = 'active';
    				$action = 'Активиране';
    			case 'activate':
    				$rec->state = ($mvc->activateNow($rec)) ? 'active' : 'pending';
    				$action = 'Активиране';
    			break;
    		}
    	
    		// Обновяваме състоянието и старото състояние
    		if($mvc->save($rec, 'brState,state')){
    			$mvc->logInfo($action, $rec->id);
    			$mvc->invoke('AfterChangeState', array(&$rec));
    		}
    		
    		// Ако сме активирали: запалваме събитие че сме активирали
    		if($action == 'activate'){
    			$mvc->invoke('AfterActivation', array(&$rec));
    		}
    		
    		// Редирект обратно към документа
    		return redirect(array($mvc, 'single', $rec->id));
		}
	}
	
	
	/**
	 * Дефолт имплементация на метода за намиране на състоянието, в 
	 * което да влиза документа при активиране
	 */
	public static function on_AfterActivateNow($mvc, &$res, $rec)
	{
		// По дефолт при активиране ще се преминава в активно състояние
		if(is_null($res)){
			$res = TRUE;
		}
	}
}