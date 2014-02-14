<?php
/**
 * Клас 'acc_ClosedDeals'
 * Абстрактен клас за създаване на приключващи документи. Неговите наследници
 * могат да се създават само в тред, началото на който е документ с интерфейс
 *'bgerp_DealAggregatorIntf'. След контирането на този документ, не може в треда
 * да се добавят документи, променящи стойностите на сделката
 * 
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class acc_ClosedDeals extends core_Master
{
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canWrite = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	protected $canList = 'no_one';
    
    
	/**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/closeDeal.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $listFields = 'id, saleId=Документ, type=Вид, amount, createdBy, createdOn';
	
	
	/**
     * Файл за единичен изглед
     */
    protected $singleLayoutFile = 'acc/tpl/ClosedDealsSingleLayout.shtml';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('notes', 'richtext(rows=2)', 'caption=Забележка,width=100%,mandatory');
    	
    	// Класа на документа, който се затваря
    	$this->FLD('docClassId', 'class(interface=doc_DocumentIntf)', 'input=none');
    	
    	// Ид-то на документа, който се затваря
    	$this->FLD('docId', 'class(interface=doc_DocumentIntf)', 'input=none');
    	$this->FLD('amount', 'double(decimals=2)', 'input=none,caption=Сума');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,input=none');
        $this->FLD('rate', 'double', 'caption=Плащане->Курс,input=none');
    	
    	// От кой клас наследник на acc_ClosedDeals идва записа
    	$this->FLD('classId', 'key(mvc=core_Classes)', 'input=none');
    }
    
    
    /**
     * Връща информацията 'bgerp_DealAggregatorIntf' от първия документ
     * в нишката ако го поддържа
     * @param mixed  $threadId - ид на нишката или core_ObjectReference
     * 							 към първия документ в нишката
     * @return stdClass - бизнес информацията от документа
     */
    public static function getDealInfo($threadId)
    {
    	$firstDoc = (is_numeric($threadId)) ? doc_Threads::getFirstDocument($threadId) : $threadId;
    	
    	expect($firstDoc instanceof core_ObjectReference, $firstDoc);
    	$threadId = $firstDoc->fetchField('threadId');
    	if($firstDoc->haveInterface('bgerp_DealAggregatorIntf')){
	    	if(empty(static::$cache[$threadId])){
	    		
	    		// Запис във временния кеш
	    		expect($dealInfo = $firstDoc->getAggregateDealInfo());
	    		static::$cache[$threadId] = $dealInfo;
	    	}
	    	
	    	return static::$cache[$threadId];
    	}
    	
    	return FALSE;
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	$rec->docId = $firstDoc->that;
    	$rec->docClassId = $firstDoc->instance()->getClassId();
    	$rec->classId = $mvc->getClassId();
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, &$rec)
    {
    	if($rec->state == 'active'){
    		$info = static::getDealInfo($rec->threadId);
    		$rec->amount = $mvc::getClosedDealAmount($mvc->fetchField($rec->id, 'threadId'));
    		$rec->currencyId = $info->agreed->currency;
    		$rec->rate = $info->agreed->rate;
    	}
    }
    
    
	/**
     * Може ли документ-продажба да се добави в посочената папка?
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     */
	public static function canAddToThread($threadId)
    {
    	// Първия документ в треда трябва да е активиран
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	// Може да се добавя само към ниша с първи документ имащ 'bgerp_DealAggregatorIntf'
    	if(!$firstDoc->haveInterface('bgerp_DealAggregatorIntf')) return FALSE;
    	
    	// Може да се добавя само към активирани документи
    	if($firstDoc->fetchField('state') != 'active') return FALSE;
		
    	$res = static::getDealInfo($threadId);
    	
    	// Дали вече има такъв документ в нишката
    	$closedDoc = static::fetch("#threadId = {$threadId} AND #state != 'rejected'");
    	
    	// Няма друг затварящ документ и няма продукти
    	$result = $res && count($res->agreed->products) && $closedDoc === FALSE;
    	
    	// Не може да се приключва документ, по който нищо не е платено и експедирано
    	$amount = static::getClosedDealAmount($firstDoc);
    	$result = ($amount == 0) ? FALSE : $result;
    	
    	return $result;
    }
	
	
    /**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, $data) 
	{
		if (!empty ($data->toolbar->buttons ['btnAdd'])) {
			unset($data->toolbar->buttons['btnAdd']);
		}
	}
    
    
    /**
     * Връща разликата с която ще се приключи сделката
     * @param mixed  $threadId - ид на нишката или core_ObjectReference
     * 							 към първия документ в нишката
     * @return double $amount - разликата на платеното и експедираното
     */
    public static function getClosedDealAmount($threadId)
    {
    	expect($info = static::getDealInfo($threadId));
        $paidAmount = currency_Currencies::round($info->paid->amount, 2);
        $shippedAmount = currency_Currencies::round($info->shipped->amount, 2);
		
        // Разликата между платеното и доставеното
        return $paidAmount - $shippedAmount;
    }
    
    
	/**
	 * Изпълнява се след запис
	 */
	public static function on_AfterSave($mvc, &$id, $rec)
    {
    	// При активация на документа
    	$rec = $mvc->fetch($id);
    	if($rec->state == 'active'){
    		
    		// Пораждащия документ става closed
    		$DocClass = cls::get($rec->docClassId);
	    	$firstRec = $DocClass->fetch($rec->docId);
	    	$firstRec->state = 'closed';
	    	$DocClass->save($firstRec);
    	}
    }
    
    
    /**
     * След оттегляне на документа, възстановява предишното
     * състояние на първия документ в нишката
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
    	$rec = $mvc->fetch((is_object($id)) ? $id->id : $id);
    	if($rec->brState == 'active'){
    		$DocClass = cls::get($rec->docClassId);
		    $firstRec = $DocClass->fetch($rec->docId);
		    $firstRec->state = 'active';
		    
		    $DocClass->save($firstRec);
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	if(!$rec->amount){
    		$info = static::getDealInfo($rec->threadId);
    		$rec->baseAmount = abs(static::getClosedDealAmount($rec->threadId));
    		$amount = abs($rec->baseAmount / $info->agreed->rate);
    		$row->currencyId = $info->agreed->currency;
    		$row->baseAmount = $mvc->fields['amount']->type->toVerbal($rec->baseAmount);
    	} else {
    		$row->baseAmount = $mvc->fields['amount']->type->toVerbal(abs($rec->amount));
    		@$amount =  abs($rec->amount / $rec->rate);
    	}
    	
    	$row->amount = $mvc->fields['amount']->type->toVerbal($amount);
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->lastModifiedOn);
    	if($row->baseCurrencyId == $row->currencyId){
    		unset($row->baseAmount, $row->baseCurrencyId);
    	}

    	if($rec->state == 'draft'){
    		unset($row->modifiedOn);
    	}
    	
    	$docRec = $firstDoc->fetch();
    	if($firstDoc->instance()->haveRightFor('single', $docRec->id)){
	    	$icon = $firstDoc->instance()->getIcon($docRec->id);
	    	$attr['class'] = 'linkWithIcon';
	        $attr['style'] = 'background-image:url(' . sbf($icon) . ');';
	    	$row->saleId = ht::createLink($firstDoc->getHandle(), array('sales_Sales', 'single', $rec->docId), NULL, $attr);
	    }
	    
	    $row->header = $mvc->singleTitle . " №<b>{$firstDoc->getHandle()}</b> ({$row->state})";
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	
        $row->authorId = $rec->createdBy;
        $row->author = $this->recToVerbal($rec)->createdBy;
        $row->state = $rec->state;
		$row->saleId = cls::get($rec->docClassId)->getHandle($rec->docId);
        
        return $row;
    }
    
    
 	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'restore' || $action == 'reject') && isset($rec)){
    		if(!haveRole('ceo,sales')){
    			$res = 'no_one';
    		}
    	}
    }
    
    
	/**
     * Преди извличане на записите от БД
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$plugins = $mvc->getPlugins();
    	if(isset($plugins['sales_Wrapper'])){
    		$docClassId = sales_Sales::getClassId();
    	} elseif(isset($plugins['purchase_Wrapper'])){
    		$docClassId = purchase_Purchases::getClassId();
    	}
    	
    	if($docClassId){
    		$data->query->where("#docClassId = {$docClassId}");
    		if(!$data->rejQuery){
    			$data->rejQuery = clone $data->query;
    			$data->rejQuery->where("#state = 'rejected'");
    		}
    		
    		$data->rejQuery->where("#docClassId = {$docClassId}");
    	}
    }
    
    
    /**
     * Нов приключващ документ в същия тред на даден документ, и
     * приключващ продажбата/покупката
     * @param mixed $Class - покупка или продажба
     * @param stdClass $docRec - запис на покупка или продажба
     */
    public function create($Class, $docRec)
    {
    	$Class = cls::get($Class);
    	
    	// Създаване на приключващ документ, само ако има остатък/излишък
    	$newRec = new stdClass();
    	
	    $newRec->notes      = "Автоматично приключване";
	    $newRec->docClassId = $Class->getClassId();
	    $newRec->docId      = $docRec->id;
	    $newRec->amount     = $docRec->toPay;
	    $newRec->currencyId = $docRec->currencyId;
	    $newRec->rate       = $docRec->currencyRate;
	    $newRec->folderId   = $docRec->folderId;
	    $newRec->threadId   = $docRec->threadId;
	    $newRec->state      = 'draft';
	    $newRec->classId    = $this->getClassId();
	    	
	    // Създаване на документа
	    return static::save($newRec);
    }
}