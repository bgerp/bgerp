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
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'deals_AdvanceReports';
	
    
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
    public $loadList = 'plg_RowTools2, findeals_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable, 
                    doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,pettyCashReport';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,pettyCashReport';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,pettyCashReport';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ,number,currencyId=Валута, total,folderId,createdOn,createdBy';

    
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
    public $details = 'findeals_AdvanceReportDetails' ;
    

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
    public $priceFields = 'total';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior,number,folderId, id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
    	$this->FLD("valior", 'date', 'caption=Дата, mandatory');
    	$this->FLD("number", 'int', 'caption=Номер');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,removeAndRefreshForm=rate');
    	$this->FLD('rate', 'double(decimals=5)', 'caption=Валута->Курс,input=hidden');
    	$this->FLD('total', 'double(decimals=2)', 'input=none,caption=Общо,notNull');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
    	$this->FLD('contragentId', 'int', 'input=hidden');
    	
    	$this->setDbUnique('number');
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
    	
    	$form->setDefault('currencyId', currency_Currencies::getIdByCode($dealInfo->get('currency')));
    	$Cover = doc_Folders::getCover($form->rec->folderId);
    	
    	$form->setDefault('contragentClassId', $Cover->getClassId());
    	$form->setDefault('contragentId', $Cover->that);
    	
    	if(isset($form->rec->id)){
    		if(findeals_AdvanceReportDetails::fetchField("#reportId = {$form->rec->id}", 'id')){
    			$form->setReadOnly('currencyId');
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
    		
    		$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
    		$rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, NULL);
    		
    		if(!$rec->rate){
    			$form->setError('rate', "Не може да се изчисли курс");
    		}
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Ако след запис, няма номер, тогава номера му става ид-то на документа
    	if(!$rec->number){
    		$rec->number = $rec->id;
    		$mvc->save($rec);
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$rec->total /= $rec->rate;
    	$row->total = $mvc->getFieldType('total')->toVerbal($rec->total);
    	$row->title = $mvc->getLink($rec->id, 0);
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
    	$rec->total = 0;
    	
    	$query = findeals_AdvanceReportDetails::getQuery();
    	$query->where("#reportId = '{$id}'");
    	while($dRec = $query->fetch()){
    		$rec->total += $dRec->amount * (1 + $dRec->vat);
    	}
    
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
    	$row->title = $this->singleTitle . " №{$id}";
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $row->title;
    
    	return $row;
    }
    
    
    /**
     * Документа не може да се активира ако има детайл с количество 0
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
    	if(!$rec->total) $res = FALSE;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$rec = $data->rec;
    	
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$Companies = cls::get('crm_Companies');
    	$data->row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$data->row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId, TRUE);
    	
    	$ContragentClass = cls::get($data->rec->contragentClassId);
    	$cData = $ContragentClass->getContragentData($data->rec->contragentId);
    	$data->row->contragentName = cls::get('type_Varchar')->toVerbal(($cData->person) ? $cData->person : $cData->company);
    	$data->row->contragentAddress = $ContragentClass->getFullAdress($data->rec->contragentId)->getContent();
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
    			$data->toolbar->addBtn('Вх. фактура', array('purchase_Invoices', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE, '', 'rate' => $rec->rate), NULL, 'ef_icon = img/16/invoice.png,title=Създаване на нова входяща фактура');
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
     * 				  o price          - цена за еденица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc)
    {
    	$details = array();
    	$rec = static::fetchRec($id);
    
    	$query = findeals_AdvanceReportDetails::getQuery();
    	$query->where("#reportId = {$rec->id}");
    	while($dRec = $query->fetch()){
    		$nRec = new stdClass();
    		$nRec->productId = $dRec->productId;
    		$nRec->packagingId = cat_Products::fetchField($dRec->productId, 'measureId');
    		$nRec->quantityInPack = 1;
    		$nRec->quantity = $dRec->quantity;
    		$nRec->price = $dRec->amount / $dRec->quantity;
    		
    		$details[] = $nRec;
    	}
    	
    	return $details;
    }
}
