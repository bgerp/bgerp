<?php


/**
 * Клас за Отворени сделки. След запис на активна продажба/покупка
 * се създава нов запис в модела. Така лесно могат да се създават пораждащи
 * документи възоснова на тях.
 * Модела се използва в модулите 'cash', 'bank', 'store'
 * В 'cash': се създават приходни и разходни касови ордер
 * В 'bank': се създават приходни и разходни банкови документи
 * В 'store': се създават експедиционни нареждания и складови разписки
 * 
 * Посочените документи се записват в треда на съответната продажба/покупка
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_OpenDeals extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Отворени сделки';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'cash_OpenDeals';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Отворена сделка";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'valior=Вальор, docId=Документ, client=Клиент, currencyId=Валута, amountDeal, amountPaid, state=Състояние, newDoc=Създаване';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Search, plg_Sorting, plg_Rejected';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, cash, bank, store';
	
	
	/**
	 * Кой може да създава
	 */
	var $canAdd = 'no_one';
	
	
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('docClass', 'class(interface=doc_DocumentIntf,select=title)', 'caption=Документ->Клас');
        $this->FLD('docId', 'int(cellAttr=left)', 'caption=Документ->Обект');
    	$this->FLD('valior', 'date', 'caption=Дата');
    	$this->FLD('amountDeal', 'double(decimals=2)', 'caption=Сума->Поръчано');
    	$this->FLD('amountPaid', 'double(decimals=2)', 'caption=Сума->Платено');
    	$this->FLD('state', 'enum(active=Активно, closed=Приключено, rejected=Оттеглено)', 'caption=Състояние');
    	
    	$this->setDbUnique('docClass,docId');
    }
	
	
	/**
      * Добавя ключови думи за пълнотекстово търсене
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
    	// Извличане на ключовите думи от документа
     	$object = new core_ObjectReference($rec->docClass, $rec->docId);
    	$folderId = $object->fetchField('folderId');
    	
    	$keywords = $object->getHandle();
    	$keywords .= " " . doc_Folders::fetchField($folderId, 'title');
     	
    	$res = plg_Search::normalizeText($keywords);
    	$res = " " . $res;
     }
     
     
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->FNC('show', 'varchar', 'input=hidden');
    	$data->listFilter->FNC('sState', 'enum(all=Всички, active=Активни, closed=Приключени)', 'caption=Състояние,input');
    	$data->listFilter->setDefault('show', Request::get('show'));
    	$data->listFilter->showFields = 'search';
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->showFields .= ', sState';
    	}
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
	 * Преди подготовка на резултатите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		$data->query->orderBy('state', "ASC");
		$data->query->orderBy('id', "DESC");
		
		if(isset($data->listFilter->rec->sState) && $data->listFilter->rec->sState != 'all'){
			$data->query->where("#state = '{$data->listFilter->rec->sState}'");
		}
	}
	
	
	/**
	 * Преди подготовка на полетата за показване в списъчния изглед
	 */
	static function on_AfterPrepareListFields($mvc, $data)
    {
    	if(Mode::is('screenMode', 'narrow')){
    		
    		// В мобилен изглед, бутона за нови документи е първи
    		$tmp = array_pop($data->listFields);
    		$data->listFields = array('newDoc' => $tmp) + $data->listFields;
    	}
    }
    
    
    /**
	 * След подготовка на вербалните записи
	 */
	function on_AfterPrepareListRows($mvc, $res, $data)
	{
		// Показване само на сделките до които има достъп касиера
		if(!haveRole('ceo')){
			if($data->recs){
				$cu = core_Users::getCurrent();
				foreach ($data->recs as $id => $rec){
					$Class = cls::get($rec->docClass);
					$docRec = $Class->fetch($rec->docId);
					
					// Ако касиера няма достъп до треда
					if(!doc_Threads::haveRightFor('single', $docRec->threadId)){
						unset($data->rows[$id]);
					} else {
						if($docRec->caseId){
							// Ако касиера не е отговорник на посочената каса
							if(cash_Cases::fetchField($docRec->caseId, 'cashier') != $cu){
								unset($data->rows[$id]);
								continue;
							}
						}
						
						if($docRec->bankAccountId){
							// Ако касиера не е отговорник на посочената б. сметка
							$operators = bank_OwnAccounts::fetchField($docRec->caseId, 'operators');
							if(keylist::isIn($cu, $operators)){
								unset($data->rows[$id]);
								continue;
							}
						}
						
						if($docRec->shipmentStoreId){
							// Ако касиера не е отговорник на посочената каса
							if(store_Stores::fetchField($docRec->caseId, 'chiefId') != $cu){
								unset($data->rows[$id]);
								continue;
							}
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Записва/Обновява нова отворена сделка
	 * @param stdClass $rec - запис от sales_Sales или purchase_Requests
	 * @param mixed $docClass - инстанция или име на класа
	 */
    public static function saveRec($rec, $docClass)
    {
    	// Записа се записва само при активация на документа със сума на сделката
    	if($rec->amountDeal){
    		$classId = $docClass::getClassId();
    		$new = array(
    			'valior' => $rec->valior,
    			'amountDeal' => $rec->amountDeal,
    			'amountPaid' => $rec->amountPaid, 
    			'state' => ($rec->state == 'draft') ? 'active' : $rec->state,
    			'docClass' => $classId,
    			'docId' => $rec->id,
    			'id' => static::fetchField("#docClass = {$classId} AND #docId = {$rec->id}", 'id'),
    		);
    		
	    	static::save((object)$new);
    	}
    }
    
    
    /**
     * След подготовка на list туулбара се добавя флага за
     * обвивката на пакета
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
    	if(Request::get('Rejected', 'int')){
    		$data->toolbar->buttons['listBtn']->url = array($mvc, 'list', 'show' => Request::get('show'));
    	}
    	
    	if(!empty($data->toolbar->buttons['binBtn'])){
    		$data->toolbar->buttons['binBtn']->url = array($mvc, 'list', 'show' => Request::get('show'), 'Rejected' => TRUE);
    	}
    }
    
    
    /**
	 * След обработка на вербалните данни
	 */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
	    	$docClass = cls::get($rec->docClass);
	    	$docRec = $docClass->fetch($rec->docId, 'folderId,threadId,currencyId');
	    	$row->client = doc_Folders::recToVerbal(doc_Folders::fetch($docRec->folderId))->title;
	    	
	    	$row->docId = $docClass->getHandle($rec->docId);
	    	if($docClass->haveRightFor('single', $rec->docId)){
	    		$icon = $docClass->getIcon($rec->docId);
	    		$attr['class'] = 'linkWithIcon';
	            $attr['style'] = 'background-image:url(' . sbf($icon) . ');';
	    		$row->docId = ht::createLink($row->docId, array($docClass, 'single', $rec->docId), NULL, $attr);
	    	}
	    	
	    	if(empty($rec->amountDeal)){
	    		$row->amountDeal = 0;
	    	}
	    	
    		if(empty($rec->amountPaid)){
	    		$row->amountPaid = 0;
	    	}
	    	
	    	$row->currencyId = $docRec->currencyId;
	    	
	    	if($rec->state == 'active'){
	    		$row->newDoc = $mvc->getNewDocBtns($docRec->threadId);
	    	}
	    	
	    	$row->ROW_ATTR['class'] = "state-{$rec->state}";
    	}
    }
    
    
    /**
     * Подготовка бутоните за генериране на нови документи
     * възоснова на продажбата/покупката
     */
    private function getNewDocBtns($threadId)
    {
    	$btns = "";
    	switch(Request::get('show')){
	    	case 'cash':
	    		$btns = ht::createBtn('ПКО', array('cash_Pko', 'add', 'threadId' => $threadId), NULL, NULL, 'ef_icon=img/16/money_add.png,title=Нов приходен касов ордер');
	    		$btns .= ht::createBtn('РКО', array('cash_Rko', 'add', 'threadId' => $threadId), NULL, NULL, 'ef_icon=img/16/money_delete.png,title=Нов разходен касов ордер');
	    		break;
	    	case 'bank':
	    		$btns = ht::createBtn('ПБД', array('bank_IncomeDocument', 'add', 'threadId' => $threadId), NULL, NULL, 'ef_icon=img/16/bank_add.png,title=Нов приходен банков документ');
	    		$btns .= ht::createBtn('РБД', array('bank_CostDocument', 'add', 'threadId' => $threadId), NULL, NULL, 'ef_icon=img/16/bank_rem.png,title=Нов разходен банков документ');
	    		break;
	    	case 'store':
	    		$btns = ht::createBtn('ЕН', array('store_ShipmentOrders', 'add', 'threadId' => $threadId), NULL, NULL, 'ef_icon=img/16/view.png,title=Ново експедиционно нареждане');
	    		break;
	    }
	    
	    return "<span style='margin-left:0.4em'>{$btns}</span>";
	}
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction(core_Mvc $mvc, &$res, $action)
    {
    	if($action != 'list') return;
    	$show = Request::get('show');
    	expect(in_array($show, array('store', 'cash', 'bank')));
    	expect(haveRole("ceo,{$show}"));
    	
    	switch($show){
    		case 'cash':
    			$menu = "Финанси";
    			$subMenu = 'Каси';
    			break;
    		case 'bank':
    			$menu = "Финанси";
    			$subMenu = 'Банки';
    			break;
    		case 'store':
    			$menu = "Логистика";
    			$subMenu = 'Складове';
    			break;
    	}
    	
    	Mode::set('pageMenu', $menu);
		Mode::set('pageSubMenu', $subMenu);
    	$mvc->load("{$show}_Wrapper");
    }
}