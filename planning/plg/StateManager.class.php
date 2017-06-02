<?php



/**
 * Плъгин добавящ към документ следните състояние: чернова, чакащо, аквитно, приключено, спряно, оттеглено и събудено
 * и управляващ преминаването им от едно в друго
 * 
 * Преминаванията от състояние в състояние са следните:
 * 
 * Чернова    (draft)    -> чакащо, активно или оттеглено
 * Чакащо     (waiting)  -> активно или оттеглено
 * Активно    (active)   -> спряно, приключено или оттеглено
 * Приключено (closed)   -> събудено или оттеглено
 * Спряно     (stopped)  -> активно или събудено
 * Събудено   (wakeup)   -> приключено, спряно или оттеглено
 * Оттеглено  (rejected) -> възстановено до някое от горните състояния
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
	 * За кои действия да се изисква основание
	 */
	public $demandReasonChangeState;
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->canPending, 'no_one');
		
		// Ако липсва, добавяме поле за състояние
		if (!$mvc->fields['state']) {
			$mvc->FLD('state', 'enum(draft=Чернова, pending=Заявка,waiting=Чакащо,active=Активирано, rejected=Оттеглено, closed=Приключено, stopped=Спряно, wakeup=Събудено,template=Шаблон)', 'caption=Състояние, input=none');
		}
		
		if (!$mvc->fields['timeClosed']) {
			$mvc->FLD('timeClosed', 'datetime(format=smartTime)', 'caption=Времена->Затворено на,input=none');
		}
		
		if (!$mvc->fields['timeActivated']) {
			$mvc->FLD('timeActivated', 'datetime(format=smartTime)', 'caption=Времена->Активирано на,input=none');
		}
		
		if(isset($mvc->demandReasonChangeState)){
			$mvc->demandReasonChangeState = arr::make($mvc->demandReasonChangeState, TRUE);
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
			$attr = array('ef_icon' => "img/16/gray-close.png", 'title' => "Приключване на документа", 'warning' => "Сигурни ли сте, че искате да приключите документа", 'order' => 30);
			$attr['id'] = 'btnClose';
			
			if(isset($mvc->demandReasonChangeState) && isset($mvc->demandReasonChangeState['close'])){
				unset($attr['warning']);
			}
			
			$closeError = $mvc->getCloseBtnError($rec);
			if(!empty($closeError)){
				$attr['error'] = $closeError;
				unset($attr['warning']);
			}
			
			$data->toolbar->addBtn("Приключване", array($mvc, 'changeState', $rec->id, 'type' => 'close', 'ret_url' => TRUE), $attr);
		}
		 
		// Добавяне на бутон за спиране
		if($mvc->haveRightFor('stop', $rec)){
			$attr = array('ef_icon' => "img/16/control_pause.png", 'title' => "Спиране на документа",'warning' => "Сигурни ли сте, че искате да спрете документа", 'order' => 30, 'row' => 2);
			if(isset($mvc->demandReasonChangeState) && isset($mvc->demandReasonChangeState['stop'])){
				unset($attr['warning']);
			}
			
			$data->toolbar->addBtn("Пауза", array($mvc, 'changeState', $rec->id, 'type' => 'stop', 'ret_url' => TRUE),  $attr);
		}
		 
		// Добавяне на бутон за събуждане
		if($mvc->haveRightFor('wakeup', $rec)){
			$attr = array('ef_icon' => "img/16/lightbulb.png", 'title' => "Събуждане на документа",'warning' => "Сигурни ли сте, че искате да събудите документа", 'order' => 30, 'row' => 3);
			if(isset($mvc->demandReasonChangeState) && isset($mvc->demandReasonChangeState['wakeup'])){
				unset($attr['warning']);
			}
			
			$data->toolbar->addBtn("Събуждане", array($mvc, 'changeState', $rec->id, 'type' => 'wakeup', 'ret_url' => TRUE), $attr);
		}
		 
		// Добавяне на бутон за активиране от различно от чернова състояние
		if($mvc->haveRightFor('activateAgain', $rec)){
			$attr = array('ef_icon' => "img/16/control_play.png", 'title' => "Активиране на документа",'warning'=> "Сигурни ли сте, че искате да активирате документа|*?", 'order' => 30);
			if(isset($mvc->demandReasonChangeState) && isset($mvc->demandReasonChangeState['activateAgain'])){
				unset($attr['warning']);
			}
			
			$data->toolbar->addBtn("Пускане", array($mvc, 'changeState', $rec->id, 'type' => 'activateAgain', 'ret_url' => TRUE, ), $attr);
		}
		
		// Добавяне на бутон запървоначално активиране
		if($mvc->haveRightFor('activate', $rec)){
			$attr = array('ef_icon' => "img/16/lightning.png", 'title' => "Активиране на документа", 'warning'=> "Сигурни ли сте, че искате да активирате документа|*?", 'order' => 30, 'id' => 'btnActivate');
			if(isset($mvc->demandReasonChangeState) && isset($mvc->demandReasonChangeState['activate'])){
				unset($attr['warning']);
			}
			
			$data->toolbar->addBtn("Активиране", array($mvc, 'changeState', $rec->id, 'type' => 'activate', 'ret_url' => TRUE, ), $attr);
		}
		
		// Бутон за заявка
		if($mvc->haveRightFor('pending', $rec)){
			if($rec->state != 'pending'){
				$r = $data->toolbar->hasBtn('btnActivate') ? 2 : 1;
				$data->toolbar->addBtn('Заявка', array($mvc, 'changePending', $rec->id), "id=btnRequest,warning=Наистина ли желаете документът да стане заявка?,row={$r}", 'ef_icon = img/16/tick-circle-frame.png,title=Превръщане на документа в заявка');
			} else{
				$data->toolbar->addBtn('Чернова', array($mvc, 'changePending', $rec->id), "id=btnDraft,warning=Наистина ли желаете да върнете възможността за редакция?", 'ef_icon = img/16/arrow-undo.png,title=Връщане на възможността за редакция');
			}
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
	
					// Само активните, събудените и спрените могат да бъдат приключени
					if($rec->state != 'active' && $rec->state != 'wakeup' && $rec->state != 'stopped'){
						$requiredRoles = 'no_one';
					}
					break;
				case 'stop':
	
					// Само активните могат да бъдат спрени
					if($rec->state != 'active' && $rec->state != 'wakeup'){
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
					if($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'wakeup' || $rec->state == 'rejected' || $rec->state == 'draft' || $rec->state == 'waiting' || $rec->state == 'template' || $rec->state == 'pending'){
						$requiredRoles = 'no_one';
					}
					break;
				case 'activate':
					
					// Само приключените могат да бъдат събудени
					if(($rec->state != 'draft' && $rec->state != 'pending') && isset($rec->state)){
						$requiredRoles = 'no_one';
					}
					break;
			}
	
			if($requiredRoles != 'no_one'){
				 
				// Минимални роли за промяна на състоянието
				$requiredRoles = $mvc->getRequiredRoles('changestate', $rec);
			}
		}
		
		if($action == 'reject' && isset($rec)){
			if($rec->state == 'stopped'){
				$requiredRoles = 'no_one';
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
    		
    		if(isset($mvc->demandReasonChangeState)){
    			if(in_array($action, $mvc->demandReasonChangeState)){
    				if(!$reason = Request::get('reason', 'text')){
    					$res = self::getReasonForm($mvc, $action, $rec);
    				
    					return FALSE;
    				} else {
    					$rec->_reason = $reason;
    				}
    			}
    		}
    		
    		if($action == 'close'){
    			$closeError = $mvc->getCloseBtnError($rec);
    			expect(empty($closeError));
    		}
    		
			switch($action){
    			case 'close':
    				$rec->brState = $rec->state;
    				$rec->state = 'closed';
    				$rec->timeClosed = dt::now();
    				$logAction = 'Приключване';
    				break;
    			case 'stop':
    				$rec->brState = $rec->state;
    				$rec->state = 'stopped';
    				$logAction = 'Спиране';
    				
    				break;
    			case 'wakeup':
    				$rec->brState = $rec->state;
    				$rec->state = 'wakeup';
    				$logAction = 'Събуждане';
    			break;
    			case 'activateAgain':
    				$rec->state = $rec->brState;
    				$rec->brState = 'stopped';
    				$logAction = ($rec->state == 'wakeup') ? 'Събуждане' : 'Пускане';
    				break;
    			case 'activate':
    				$rec->brState = $rec->state;
    				$rec->state = ($mvc->activateNow($rec)) ? 'active' : 'waiting';
    				$logAction = 'Активиране';
    				$rec->timeActivated = dt::now();
    			break;
    		}
    	
    		// Ако ще активираме: запалваме събитие, че ще активираме
    		$saveFields = 'brState,state,modifiedOn,modifiedBy,timeClosed,timeActivated';
    		if($action == 'activate'){
    			$mvc->invoke('BeforeActivation', array(&$rec));
    			$saveFields = NULL;
    		}
    		
    		// Обновяваме състоянието и старото състояние
    		if($mvc->save($rec, $saveFields)){
    			$mvc->logWrite($logAction, $rec->id);
    			$mvc->invoke('AfterChangeState', array(&$rec, $rec->state));
    		}
    		
    		// Ако сме активирали: запалваме събитие, че сме активирали
    		if($action == 'activate'){
    			$mvc->invoke('AfterActivation', array(&$rec));
    		}
    		
    		// Редирект обратно към документа
    		redirect(array($mvc, 'single', $rec->id));
		}
	}
	

	/**
	 * Реакция в счетоводния журнал при оттегляне на счетоводен документ
	 */
	public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		$mvc->invoke('AfterChangeState', array(&$rec, 'rejected'));
	}
	
	
	/**
	 * След възстановяване
	 */
	public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		if($rec->state != 'rejected'){
			$mvc->invoke('AfterChangeState', array(&$rec, 'restore'));
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
	
	
	/**
	 * Подготовка на формата за добавяне на основание към смяната на състоянието
	 * 
	 * @param core_Mvc $mvc
	 * @param enum(close,stop,activateAgain,activate,wakeup) $action
	 * @param stdClass $rec
	 * @return core_Form $res
	 */
	private static function getReasonForm($mvc, $action, $rec)
	{
		$actionArr = array('close' => 'Приключване', 'stop' => 'Спиране', 'activateAgain' => 'Пускане', 'activate' => 'Активиране', 'wakeup' => 'Събуждане');
		
		$form = cls::get('core_Form');
		$form->FLD('reason', 'text(rows=2)', 'caption=Основание,mandatory');
		$actionVerbal = strtr($action, $actionArr);
		$form->title = $actionVerbal . "|* " . tr("на") . "|* " . planning_Jobs::getHyperlink($rec->id, TRUE);
		$form->input();
			
		if($form->isSubmitted()){
			$url = array($mvc, 'changeState', $rec->id, 'type' => $action, 'reason' => $form->rec->reason);
		
			redirect($url);
		}
			
		$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
			
		$res = $form->renderHtml();
		$res = $mvc->renderWrapping($res);
		
		return $res;
	}
	
	
	/**
	 * След подготовка на сингъла
	 */
	public static function on_AfterPrepareSingle($mvc, &$res, $data)
	{
		$rec = &$data->rec;
		$row = &$data->row;
		
		if($rec->state == 'stopped' || $rec->state == 'closed') {
			$tpl = new ET(tr(' от [#user#] на [#date#]'));
			$dateChanged = ($rec->state == 'closed') ? $rec->timeClosed : $rec->modifiedOn;
			$row->state .= $tpl->placeArray(array('user' => $row->modifiedBy, 'date' => dt::mysql2Verbal($dateChanged)));
		}
	}
	
	
	/**
	 * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
	 */
	protected static function on_AfterPrepareEditToolbar($mvc, $data)
	{
		if ($mvc->haveRightFor('activate', $data->form->rec)) {
			$data->form->toolbar->addSbBtn('Активиране', 'active', 'id=activate, order=9.99980', 'ef_icon = img/16/lightning.png,title=Активиране на документа');
		}
	}
	
	
	/**
	 * Ако е натиснат бутона 'Активиране" добавя състоянието 'active' в $form->rec
	 */
	public static function on_AfterInputEditForm($mvc, $form)
	{
		if($form->isSubmitted()) {
			if($form->cmd == 'active') {
				$form->rec->state = ($mvc->activateNow($form->rec)) ? 'active' : 'waiting';
				$mvc->invoke('BeforeActivation', array($form->rec));
				$form->rec->_isActivated = TRUE;
			}
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 */
	public static function on_AfterSave($mvc, &$id, $rec)
	{
		if($rec->_isActivated === TRUE) {
			unset($rec->_isActivated);
			$mvc->invoke('AfterActivation', array($rec));
			$mvc->logWrite('Активиране', $rec->id);
		}
	}
	
	
	/**
	 * След намиране на текста за грешка на бутона за 'Приключване'
	 */
	public static function on_AfterGetCloseBtnError($mvc, &$res, $rec)
	{
		$res = (!empty($res)) ? $res : NULL;
	}
}