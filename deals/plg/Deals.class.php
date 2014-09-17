<?php



/**
 * Плъгин за сделките
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_Deals extends core_Plugin
{
    
    
    
    /**
     * Преди рендиране на тулбара
     */
    public static function on_BeforeRenderSingleToolbar($mvc, &$res, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($mvc->haveRightFor('closeWith', $rec)) {
    		 
    		// Ако тази сделка може да се приключи с друга сделка, и има налични сделки подменяме бутона да сочи
    		// към екшън за избиране на кои сделки да се приключат с тази
    		$data->toolbar->removeBtn('btnConto');
    		$data->toolbar->removeBtn('btnActivate');
    		$data->toolbar->addBtn('Активиране', array($mvc, 'closeWith', $rec->id), "id=btnConto{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Активиране на документа');
    	}
    }
    
    
    /**
     * Кои сделки ще могатд а се приключат с документа
     * 
     * @param int $id - ид на документа
     * @return array $options - опции
     */
    public static function on_AfterGetDealsToCloseWith($mvc, &$res, $rec)
    {
    	// Избираме всички други активни сделки от същия тип и валута, като началния документ в същата папка
    	$docs = array();
    	$dealQuery = $mvc->getQuery();
    	$dealQuery->where("#id != {$rec->id}");
    	$dealQuery->where("#folderId = {$rec->folderId}");
    	$dealQuery->where("#currencyId = '{$rec->currencyId}'");
    	$dealQuery->where("#state = 'active'");
    	
    	while($dealRec = $dealQuery->fetch()){
    		$docs[$dealRec->id] = $mvc->getRecTitle($dealRec);
    	}
    	
    	$res = $docs;
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     * 
     * @param core_Manager $mvc
     * @param core_ET $res
     * @param string $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$tpl, $action)
    {   
    	if(strtolower($action) == 'closewith'){
    		$id = Request::get('id', 'int');
    		expect($rec = $mvc->fetch($id));
    		expect($rec->state == 'draft');
    		
    		// Трябва потребителя да може да контира
    		$mvc->requireRightFor('conto', $rec);
    		
    		$options = $mvc->getDealsToCloseWith($rec);
    		count($options);
    		
    		// Подготовка на формата за избор на опция
    		$form = cls::get('core_Form');
    		$form->title = "|Активиране на|* <b>" . $mvc->getTitleById($id). "</b>" . " ?";
    		$form->info = 'Искатели с активирането на тази сделка да приключите други сделки с нея';
    		$form->FLD('closeWith', "keylist(mvc={$mvc->className})", 'caption=Приключи и,column=1');
    		$form->setSuggestions('closeWith', $options);
    		$form->input();
	    	
	    	// След като формата се изпрати
	    	if($form->isSubmitted()){
	    		$rec->contoActions = 'activate';
	    		$rec->state = 'active';
	    		$mvc->save($rec);
	    		$mvc->invoke('AfterActivation', array($rec));
	    		
	    		if(!empty($form->rec->closeWith)){
	    			$CloseDoc = cls::get($mvc->closeDealDoc);
	    			$deals = keylist::toArray($form->rec->closeWith);
	    			foreach ($deals as $dealId){
	    				
	    				// Създаване на приключващ документ-чернова
	    				$dRec = $mvc->fetch($dealId);
	    				$clId = $CloseDoc->create($mvc->className, $dRec, $id);
	    				$CloseDoc->conto($clId);
	    			}
	    		}
	    		
	    		return redirect(array($mvc, 'single', $id));
	    	}
    		
    		$form->toolbar->addSbBtn('Активиране', 'save', 'ef_icon = img/16/tick-circle-frame.png');
    		$form->toolbar->addBtn('Отказ', array($mvc, 'single', $id),  'ef_icon = img/16/close16.png');
    		 
    		// Рендиране на формата
    		$tpl = $mvc->renderWrapping($form->renderHtml());
    		
    		// ВАЖНО: спираме изпълнението на евентуални други плъгини
    		return FALSE;
    	}
    }
    
    
    /**
     * Преди да се проверят имали приключени пера в транзакцията
     * 
     * Обхождат се всички документи в треда и ако един има приключено перо, документа начало на нишка
     * не може да се оттегля/възстановява/контира
     */
    public static function on_BeforeGetClosedItemsInTransaction($mvc, &$res, $id)
    {
    	$closedItems = array();
    	$rec = $mvc->fetchRec($id);
    	$dealItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
    	
    	// Записите от журнала засягащи това перо
    	$entries = acc_Journal::getEntries(array($mvc, $rec->id));
    	
    	// Намираме оттеглените документи в треда, те нямат транзакция и няма да фигурират в $entries, за това
    	// ги добавяме ръчно, за да участват и те в проверката
    	$descendants = $mvc->getDescendants($rec->id);
    	
    	// Към тях добавяме и самия документ
    	$entries[] = (object)array('docType' => $mvc->getClassId(), 'docId' => $rec->id);
    	
    	if($descendants){
    		foreach ($descendants as $doc){
    			
    			// ако е оттеглен го добавяме в масива за проверка
    			if($doc->fetchField('state') == 'rejected'){
    				$entries[] = (object)array('docType' => $doc->getClassId(), 'docId' => $doc->that);
    			}
    		}
    	}
    	
    	// За всеки запис
    	foreach ($entries as $ent){
    		
    		// Ако има метод 'getValidatedTransaction'
    		$Doc = cls::get($ent->docType);
    		
    		// Ако транзакцията е направена от друг тред запомняме от кой документ е направена
    		$threadId = $Doc->fetchField($ent->docId, 'threadId');
    		if($threadId != $rec->threadId){
    			$mvc->usedIn[$dealItem->id][] = $Doc->getHandle($ent->docId);
    		}
    		
    		if(cls::existsMethod($Doc, 'getValidatedTransaction')){
    			
    			// Ако има валидна транзакция, проверяваме дали има затворени пера
    			$transaction = $Doc->getValidatedTransaction($ent->docId);
    			
    			if($transaction){
    				// Добавяме всички приключени пера
    				$closedItems += $transaction->getClosedItems();
    			}
    		}
    	}
    	
    	if($rec->state != 'closed'){
    		unset($closedItems[$dealItem->id]);
    	}
    	
    	// Връщаме намерените пера
    	$res = $closedItems;
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	
    	if($rec->state == 'active'){
    		
    		// Ако валутата е активна, добавя се като перо
    		$lists = keylist::addKey('', acc_Lists::fetchBySystemId('deals')->id);
    		acc_Lists::updateItem($mvc, $rec->id, $lists);
    		
    		if(haveRole('ceo,acc,debug')){
    			$msg = tr("Активирано е перо|* '") . $mvc->getTitleById($rec->id) . tr("' |в номенклатура 'Сделки'|*");
    			core_Statuses::newStatus($msg);
    		}
    	}
    }
    
    
    /**
     * След оттегляме запомняме записа, чието перо трябва да се затври на shutdown
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
    	// Ако документа се е оттеглил успешно, записваме му ид-то в модела
    	$rec = $mvc->fetchRec($id);
    	$mvc->rejectedQueue[$rec->id] = $rec->id;
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
    	// Ако има оттеглени записи, затваряме им перата
    	if(count($mvc->rejectedQueue)){
    		foreach ($mvc->rejectedQueue as $id) {
    			$lists = keylist::addKey('', acc_Lists::fetchBySystemId('deals')->id);
    			acc_Lists::removeItem($mvc, $id, $lists);
    			
    			if(haveRole('ceo,acc,debug')){
    				$title = $mvc->getTitleById($id);
    				core_Statuses::newStatus(tr("|Перото|* \"{$title}\" |е затворено/изтрито|*"));
    			}
    		}
    	}
    }
    
    
    /**
     * Какво е платежното състояние на сделката
     */
    public static function on_AfterGetPaymentState($mvc, &$res, $aggregateDealInfo, $state)
    {
    	$amountPaid      = $aggregateDealInfo->get('amountPaid');
    	$amountDelivered = $aggregateDealInfo->get('deliveryAmount');
    	
    	// Ако имаме платено и доставено
    	$diff = round($amountDelivered - $amountPaid, 4);
    
    	$conf = core_Packs::getConfig('acc');
    		
    	// Ако разликата е в между -толеранса и +толеранса то състоянието е платено
    	if(($diff >= -1 * $conf->ACC_MONEY_TOLERANCE && $diff <= $conf->ACC_MONEY_TOLERANCE) || $diff < -1 * $conf->ACC_MONEY_TOLERANCE){
    			
    		// Ако е в състояние чакаща отбелязваме я като платена, ако е била просрочена става издължена
    		$res = ($state != 'overdue') ? 'paid' : 'repaid';
    		return;
    	}
    	
    	$res = 'pending';
    }
    
    
    /**
     * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * Генерира агрегираната бизнес информация за тази сделка
     *
     * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
     * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до
     * момента.
     *
     * Списъка с въпросните документи, имащи отношение към бизнес информацията за пробдажбата е
     * сечението на следните множества:
     *
     *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
     *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
     *  * Документите, в състояние различно от `draft` и `rejected`
     *
     * @return bgerp_iface_DealResponse
     */
    public static function on_AfterGetAggregateDealInfo($mvc, &$res, $id)
    {
    	$dealRec = $mvc->fetchRec($id);
    	 
    	$dealDocuments = $mvc->getDescendants($dealRec->id);
    
    	$aggregateInfo = new bgerp_iface_DealAggregator;
    	 
    	// Извличаме dealInfo от самата сделка
    	$mvc->pushDealInfo($dealRec->id, $aggregateInfo);
    
    	foreach ($dealDocuments as $d) {
    		$dState = $d->rec('state');
    		if ($dState == 'draft' || $dState == 'rejected') {
    			// Игнорираме черновите и оттеглените документи
    			continue;
    		}
    
    		if ($d->haveInterface('bgerp_DealIntf')) {
    			$d->instance->pushDealInfo($d->that, $aggregateInfo);
    		}
    	}
    
    	$res = $aggregateInfo;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'closewith' && isset($rec)){
    		$options = $mvc->getDealsToCloseWith($rec);
    		if(!count($options) || $rec->state != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
}
