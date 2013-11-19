<?php
/**
 * Клас 'acc_ClosedDeals'
 * Абстрактен клас за създаване на приключващи документи. Неговите наследници
 * могат да се създават само в тред, началото на който е документ с интерфейс
 *'bgerp_DealAggregatorIntf'. След контирането на този документ, неможе в треда
 * да се добавят документи, променящи стойностите на сделката
 * 
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
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
    protected $listFields = 'id, saleId=Продажба, amount, createdBy, createdOn';
	
	
	/**
     * Файл за единичен изглед
     */
    protected $singleLayoutFile = 'acc/tpl/ClosedDealsSingleLayout.shtml';
    
    
    /**
     * Работен кеш
     */
    protected static $cache;
    
    
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
    	
    	if($firstDoc->haveInterface('bgerp_DealAggregatorIntf')){
	    	if(empty(static::$cache)){
	    		
	    		// Запис във временния кеш
	    		expect(static::$cache = $firstDoc->getAggregateDealInfo());
	    	}
	    	
	    	return static::$cache;
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
    	$res = static::getDealInfo($threadId);
    	
    	// Дали вече има такъв документ в нишката
    	$closedDoc = static::fetch("#threadId = {$threadId} AND #state != 'rejected'");
    	
    	// може да се добавя само ако документа има 'bgerp_DealAggregatorIntf',
    	// няма друг затварящ документ и няма продукти
    	$result = $res && count($res->agreed->products) && $closedDoc === FALSE;
    	
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
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function getTransaction($id)
    {
    	// Извличаме мастър-записа
        expect($rec = self::fetchRec($id));
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
		$amount = abs(static::getClosedDealAmount($firstDoc));
        
        $result = (object)array(
            'reason'      => "Приключване на продажба " . $firstDoc->getHandle(),
            'valior'      => dt::now(),
            'totalAmount' => $amount,
            'entries'     => array()
        );
       
        return $result;
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
        $paidAmount = $info->paid->amount;
        $shippedAmount = $info->shipped->amount;
		
        // Разликата между платеното и доставеното
        return $paidAmount - $shippedAmount;
    }
    
    
	/**
	 * След като документа се активира, се променя състоянието
	 * на първия документ в треда на 'closed'
	 */
	public static function on_AfterSave($mvc, &$id, $rec)
    {
    	$rec = $mvc->fetch($id);
    	if($rec->state == 'active'){
    		$DocClass = cls::get($rec->docClassId);
	    	$firstRec = $DocClass->fetch($rec->docId);
	    	$firstRec->brState = $firstRec->state;
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
		    $firstRec->state = $firstRec->brState;
		    
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
    		$rec->baseAmount = static::getClosedDealAmount($rec->threadId);
    		$amount = $rec->baseAmount / $info->agreed->rate;
    		$row->currencyId = $info->agreed->currency;
    		$row->baseAmount = $mvc->fields['amount']->type->toVerbal($rec->baseAmount);
    	} else {
    		$row->baseAmount = $row->amount;
    		@$amount =  $rec->amount / $rec->rate;
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
    	if($action == 'conto' && isset($rec)){
    		if(!static::getClosedDealAmount($rec->threadId)){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'restore' && isset($rec)){
    		if($mvc->fetch("#threadId = {$rec->threadId} AND #state != 'rejected'")){
    			$res = 'no_one';
    		}
    	}
    }
    
    
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'active';
        
        return self::save($rec);
    }
    
    
	/**
     * Преди извличане на записите от БД
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$plugins = $mvc->getPlugins();
    	if(isset($plugins['sales_Wrapper'])){
    		$docClassId = sales_Sales::getClassId();
    	} elseif(isset($plugins['purchase_Wrapper'])){
    		$docClassId = purchase_Purchases::getClassId();
    	}
    	
    	if($docClassId){
    		$data->query->where("#docClassId = {$docClassId}");
    	}
    }
}