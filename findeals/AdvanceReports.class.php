<?php



/**
 * Клас 'findeals_AdvanceReports'
 *
 * Мениджър за Авансови отчети
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_AdvanceReports extends core_Master
{
    
	
    /**
     * Заглавие
     */
    public $title = 'Авансови отчети';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Adr';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=findeals_transaction_AdvanceReport, bgerp_DealIntf, email_DocumentIntf, deals_InvoiceSourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, findeals_Wrapper, plg_Printing, acc_plg_Contable, 
                    doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,pettyCashReport,acc';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,pettyCashReport,acc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,pettyCashReport';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,pettyCashReport';


    /**
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,pettyCashReport';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,pettyCashReport';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'pettyCashReport, ceo';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ,amount,currencyId,folderId,createdOn,createdBy';

    
   /**
    * Основна сч. сметка
    */
    public static $baseAccountSysId = '422';
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'findeals_AdvanceReportDetails';
    

    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'findeals_AdvanceReportDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Авансов отчет';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'findeals/tpl/SingleAdvanceReportLayout.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Финанси";
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'value,discountValue,neto,vat02BaseAmount,vat02Amount,vat009BaseAmount,vat009Amount,vat0BaseAmount,vat0Amount,total,sayWords';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior,folderId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
    	$this->FLD("valior", 'date', 'caption=Дата, mandatory');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,removeAndRefreshForm=currencyRate');
    	$this->FLD('currencyRate', 'double(decimals=5)', 'caption=Валута->Курс,input=hidden,oldFieldName=rate');
    	$this->FLD('amount', 'double(decimals=2)', 'input=none,caption=Общо,notNull');
    	$this->FLD('amountVat', 'double(decimals=2)', 'input=none');
    	$this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
    	
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none');
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
    	$this->FLD('contragentId', 'int', 'input=hidden');
    	$this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Oсвободено от ДДС, no=Без начисляване на ДДС)', 'caption=Допълнително->ДДС');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$form->setDefault('valior', dt::now());
    	
    	expect($origin = $mvc->getOrigin($form->rec));
    	expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
    	$dealInfo = $origin->getAggregateDealInfo();
    	$options = self::getOperations($dealInfo->get('allowedPaymentOperations'));
    	expect(count($options));
    	
    	$form->dealInfo = $dealInfo;
    	$form->setDefault('operationSysId', 'debitDeals');
    	
    	$form->setDefault('currencyId', $dealInfo->get('currency'));
    	$Cover = doc_Folders::getCover($form->rec->folderId);
    	
    	$form->setDefault('contragentClassId', $Cover->getClassId());
    	$form->setDefault('contragentId', $Cover->that);
    	
    	if(isset($form->rec->id)){
    		if(findeals_AdvanceReportDetails::fetchField("#reportId = {$form->rec->id}", 'id')){
    			$form->setReadOnly('currencyId');
    			$form->setReadOnly('chargeVat');
    		}
    	}
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	 
    	if ($form->isSubmitted()){
    		$operations = $form->dealInfo->get('allowedPaymentOperations');
    		$operation = $form->dealInfo->allowedPaymentOperations[$rec->operationSysId];
    		$rec->creditAccount = $operation['credit'];
    		$rec->currencyRate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
    		
    		if(!$rec->currencyRate){
    			$form->setError('currencyRate', "Не може да се изчисли курс");
    		}
    	}
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetchRec($id);
		 
		$query = findeals_AdvanceReportDetails::getQuery();
		$query->where("#reportId = '{$id}'");
		$recs = $query->fetchAll();
	
		deals_Helper::fillRecs($this, $recs, $rec);
	
		// ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
		$amount = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
		$amount -= $this->_total->discount;
		
		$rec->amount = $amount * $rec->currencyRate;
		$rec->amountVat = $this->_total->vat * $rec->currencyRate;
		$rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
		
		return $this->save($rec);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    	 
    	return "{$self->singleTitle} №{$rec->id}";
    }
    	
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
    		
    		if($firstDoc->className != 'findeals_AdvanceDeals') return FALSE;
    		
    		$options = self::getOperations($firstDoc->getPaymentOperations());
    			
    		return count($options) ? TRUE : FALSE;
    	}
    
    	return FALSE;
    }
    
    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции в коитос е дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['credit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	 
    	return $options;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$row->title    = $this->singleTitle . " №{$id}";
    	$row->authorId = $rec->createdBy;
    	$row->author   = $this->getVerbal($rec, 'createdBy');
    	$row->state    = $rec->state;
    	$row->recTitle = $row->title;
    
    	return $row;
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	 
    	$rec = &$data->rec;
    	if(empty($data->noTotal)){
    		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat, FALSE, $rec->tplLang);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$rec = $data->rec;
    	
    	$headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId);
    	$data->row = (object)((array)$data->row + (array)$headerInfo);
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
    	$handle = $this->getHandle($id);
    	$tpl = new ET(tr("Моля запознайте се с нашия авансов отчет") . ': #[#handle#]');
    	$tpl->append($handle, 'handle');
    
    	return $tpl->getContent();
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
    	 
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($rec->state == 'active'){
    		if(purchase_Invoices::haveRightFor('add', (object)array('sourceContainerId' => $rec->containerId, 'threadId' => $rec->threadId))){
    			$data->toolbar->addBtn('Вх. фактура', array('purchase_Invoices', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE, '', 'rate' => $rec->currencyRate), NULL, 'ef_icon = img/16/invoice.png,title=Създаване на нова входяща фактура');
    		}
    	}
    }
    
    
    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param mixed $id - ид или запис на документа
     * @param deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     * @return array $details - масив с артикули готови за запис
     * 				  o productId      - ид на артикул
     * 				  o packagingId    - ид на опаковка/основна мярка
     * 				  o quantity       - количество опаковка
     * 				  o quantityInPack - количество в опаковката
     * 				  o discount       - отстъпка
     * 				  o price          - цена за единица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc)
    {
    	$details = array();
    	$rec = static::fetchRec($id);
    	 
    	$query = findeals_AdvanceReportDetails::getQuery();
    	$query->where("#reportId = {$rec->id}");
    	while($dRec = $query->fetch()){
    		$dRec->quantity /= $dRec->quantityInPack;
    		if(!($forMvc instanceof sales_Proformas)){
    			$dRec->price -= $dRec->price * $dRec->discount;
    			unset($dRec->discount);
    		}
    
    		unset($dRec->id);
    		unset($dRec->reportId);
    		unset($dRec->createdOn);
    		unset($dRec->createdBy);
    		$details[] = $dRec;
    	}
    	 
    	return $details;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->title = $mvc->getLink($rec->id, 0);
    		
    		$amount = ($rec->amount + $rec->amountVat) / $rec->currencyRate;
    		$row->amount = $mvc->getFieldType('amount')->toVerbal($amount);
    		
    		if($amount ==- 0){
    			$row->amount = "<span class='quiet'><b>{$row->amount}</b></span>";
    		}
    	}
    }
}
