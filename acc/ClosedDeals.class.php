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
    protected $listFields = 'id, docId=Документ, type=Вид, amount, createdBy, createdOn';
	
	
	/**
     * Файл за единичен изглед
     */
    protected $singleLayoutFile = 'acc/tpl/ClosedDealsSingleLayout.shtml';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Още един работен кеш
     */
    protected static $incomeAmount;
    
    
    /**
     * Още един работен кеш
     */
    protected $year;
    
    
    /**
     * Кратък баланс на записите от журнала засегнали сделката
     */
    protected $shortBalance;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('notes', 'richtext(rows=2)', 'caption=Забележка,mandatory');
    	
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
    		$rec->currencyId = $info->get('currency');
    		$rec->rate = $info->get('rate');
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
    	
    	if(!$firstDoc) return FALSE;
    	
    	// Може да се добавя само към ниша с първи документ имащ 'bgerp_DealAggregatorIntf'
    	if(!$firstDoc->haveInterface('bgerp_DealAggregatorIntf')) return FALSE;
    	
    	// Може да се добавя само към активирани документи
    	if($firstDoc->fetchField('state') != 'active') return FALSE;
		
    	// Дали вече има такъв документ в нишката
    	$closedDoc = static::fetch("#threadId = {$threadId} AND #state != 'rejected'");
    	if($closedDoc !== FALSE) return FALSE;
    	
    	return TRUE;
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
		
        $diff = currency_Currencies::round($info->get('blAmount'), 2);
       
        // Разликата между платеното и доставеното
        return $diff;
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
	    	
	    	// Ако има перо сделката, затваряме го
	    	if($item = acc_Items::fetchItem($DocClass->getClassId(), $firstRec->id)){
	    		acc_Lists::removeItem($DocClass, $firstRec->id);
	    		
	    		$title = $DocClass->getTitleById($firstRec->id);
	    		core_Statuses::newStatus(tr("|Перото|* \"{$title}\" |е затворено/изтрито|*"));
	    	}
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
		    
		    // Обновяваме състоянието на сделката, само ако не е оттеглена
		    if($firstRec->state != 'rejected'){
		    	$firstRec->state = 'active';
		    	$DocClass->save($firstRec);
		    }
		    
		    // Ако има перо сделката, обновяваме му състоянието
		    if($item = acc_Items::fetchItem($DocClass->getClassId(), $firstRec->id)){
		    	acc_Lists::updateItem($DocClass, $firstRec->id, $item->lists);
		    	
		    	$msg = tr("Активирано е перо|* '") . $DocClass->getTitleById($firstRec->id) . tr("' |в номенклатура 'Сделки'|*");
    			core_Statuses::newStatus($msg);
		    }
    	}
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    static function recToVerbal_($rec, &$fields = '*')
    {
    	$row = parent::recToVerbal_($rec, $fields);
    	
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	if(!$rec->amount){
    		$rec->amount = static::getClosedDealAmount($rec->threadId);
    	}
    	
    	$row->currencyId = acc_Periods::getBaseCurrencyCode($rec->createdOn);
	    
	    $abbr = cls::get(get_called_class())->abbr;
	    $row->header = cls::get(get_called_class())->singleTitle . " #<b>{$abbr}{$row->id}</b> ({$row->state})";
	    
	    return $row;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->saleId = cls::get($rec->docClassId)->getHandle($rec->docId);
        
        return $row;
    }
    
    
 	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// Документа не може да се контира, ако ориджина му е в състояние 'closed'
    	if($action == 'conto' && isset($rec)){
    		
	    	$origin = $mvc->getOrigin($rec);
    		if($origin && $origin->haveInterface('bgerp_DealAggregatorIntf')){
	    		$originState = $origin->fetchField('state');
	    		if($originState === 'closed'){
		        	$res = 'no_one';
		        }
	    	}
        }
        
        // не може да се възстанови оттеглен документ, ако има друг неоттеглен в треда
        if($action == 'restore' && isset($rec)){
        	if($mvc->fetch("#threadId = {$rec->threadId} AND #state != 'rejected' AND #id != '{$rec->id}'")){
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
    	} elseif(isset($plugins['deals_Wrapper'])){
    		$docClassId = deals_Deals::getClassId();
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
    
    
	/**
     * Връща счетоводното основание за документа
     */
    public function getContoReason($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	return $this->getVerbal($rec, 'notes');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	
    	return $self->singleTitle . " №{$rec->id}";
    }
    
    
    /**
     * Дали документа има приключени пера в транзакцията му
     */
    public function getClosedItemsInTransaction_($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	// Намираме приключените пера от транзакцията
    	$transaction = $this->getValidatedTransaction($id);
    	if($transaction){
    		$closedItems = $transaction->getClosedItems();
    	}
    	
    	// От списъка с приключените пера, премахваме това на приключения документ, така че да може
    	// приключването да се оттегля/възстановява въпреки че има в нея приключено перо
    	$dealItemId = acc_Items::fetchItem($rec->docClassId, $rec->docId)->id;
    	unset($closedItems[$dealItemId]);
    	
    	return $closedItems;
    }
}