<?php



/**
 * Документ "Проформа фактура"
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Proformas extends deals_InvoiceMaster
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf,deals_InvoiceSourceIntf';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Prf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Проформа фактури';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Проформа фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, cond_plg_DefaultValues, plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, Sale=sales_Sales,
                    doc_plg_HidePrices, doc_plg_TplManager, deals_plg_DpInvoice, doc_ActivatePlg, plg_Clone,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_ProformaDetails' ;
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
    public $cloneDetails = 'sales_ProformaDetails';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'sales_ProformaDetails';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales,acc';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
    
	/**
	 * Поле за единичния изглед
	 */
	public $rowToolsSingleField = 'number';
	
	
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, contragentName';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutProforma.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/proforma.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.8|Търговия";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number, date, place, folderId, dealValue, vatAmount';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'place'               => 'lastDocUser|lastDoc',
    	'responsible'         => 'lastDocUser|lastDoc',
    	'contragentCountryId' => 'lastDocUser|lastDoc|clientData',
    	'contragentVatNo'     => 'lastDocUser|lastDoc|clientData',
    	'uicNo' 			  => 'lastDocUser|lastDoc|clientData',
    	'contragentPCode'     => 'lastDocUser|lastDoc|clientData',
    	'contragentPlace'     => 'lastDocUser|lastDoc|clientData',
    	'contragentAddress'   => 'lastDocUser|lastDoc|clientData',
    	'accountId' 		  => 'lastDocUser|lastDoc',
    	'template' 		      => 'lastDocUser|lastDoc|defMethod',
    );
    
    
    /**
     * Кои полета ако не са попълнени във визитката на контрагента да се попълнят след запис
     */
    public static $updateContragentdataField = array('vatId'   => 'contragentVatNo',
    												 'uicId'   => 'uicNo',
    												 'egn'     => 'uicNo',
    );
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, date,dueDate,vatDate,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	parent::setInvoiceFields($this);
    	 
    	$this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
    	$this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=title, allowEmpty)', 'caption=Плащане->Банкова с-ка');
    	$this->FLD('state', 'enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    	$this->FLD('number', 'int', 'caption=Номер, export=Csv, after=place');
    
    	$this->setDbUnique('number');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Проформа', 'content' => 'sales/tpl/SingleLayoutProforma.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Pro forma', 'content' => 'sales/tpl/SingleLayoutProformaEn.shtml', 'lang' => 'en');
    	
    	$res = '';
    	$res .= doc_TplManager::addOnce($this, $tplArr);
    
    	return $res;
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	parent::prepareInvoiceForm($mvc, $data);
    	
    	$form->setField('paymentType', 'input=none');
    	foreach (array('deliveryPlaceId', 'vatDate') as $fld){
    		$form->setField($fld, 'input=hidden');
    	}
    	
    	if(!haveRole('ceo,acc')){
    		$form->setField('number', 'input=none');
    	}
    	 
    	if($data->aggregateInfo){
    		if($accId = $data->aggregateInfo->get('bankAccountId')){
    			$form->setDefault('accountId', bank_OwnAccounts::fetchField("#bankAccountId = {$accId}", 'id'));
    		}
    	}
    	
    	if(empty($data->flag)){
    		if($ownAcc = bank_OwnAccounts::getCurrent('id', FALSE)){
    			$form->setDefault('accountId', $ownAcc);
    		}
    	}
    	
    	if($form->rec->vatRate != 'yes' && $form->rec->vatRate != 'separate'){
    		if($form->rec->contragentCountryId == drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id')){
    			$form->setField('vatReason', 'input,mandatory');
    		}
    	}
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	parent::inputInvoiceForm($mvc, $form);
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
    	if(isset($rec->id)){
    		if(empty($rec->folderId)){
    			$rec->folderId = $mvc->fetchField($rec->id, 'folderId');
    		}
    		
    		if(empty($rec->dueDate) && $rec->state == 'active'){
    			$rec->dueDate = $mvc->fetchField($rec->id, 'dueDate');
    		}
    	}
    	
    	parent::beforeInvoiceSave($rec);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	$number = ($rec->number) ? $rec->number : $mvc->fetchField($rec->id, 'number');
    	
    	if(empty($number)){
    		$query = $mvc->getQuery();
    		$query->XPR('maxNumber', 'int', 'MAX(#number)');
    		
    		$number = $query->fetch()->maxNumber;
    		$number += 1;
    		$rec->number = $number;
    		$mvc->save_($rec, 'number');
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    public static function on_AfterPrepareEditToolbar($mvc, $data)
    {
    	if (!empty($data->form->toolbar->buttons['activate'])) {
    		$data->form->toolbar->removeBtn('activate');
    	}
    	
    	if (!empty($data->form->toolbar->buttons['btnNewThread'])) {
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	parent::getVerbalInvoice($mvc, $rec, $row, $fields);
		
    	if($fields['-single']){
    		if(isset($rec->accountId)){
    			$Varchar = cls::get('type_Varchar');
    			$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
    			$row->accountId = cls::get('iban_Type')->toVerbal($ownAcc->iban);
    			
    			core_Lg::push($rec->tplLang);
    			$row->bank = transliterate(tr($Varchar->toVerbal($ownAcc->bank)));
    			core_Lg::pop();
    			$row->bic = $Varchar->toVerbal($ownAcc->bic);
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	$firstDocument = doc_Threads::getFirstDocument($threadId);
    	
    	if(!$firstDocument) return FALSE;
    	
    	// Може да се добавя само към активна продажба
    	if($firstDocument->isInstanceOf('sales_Sales') && $firstDocument->fetchField('state') == 'active'){
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    	
    	if($data->paymentPlan){
    		$tpl->placeObject($data->paymentPlan);
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return tr("|Проформа фактура|* №") . $rec->id;
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	 
    	$rec = &$data->rec;
    	if(empty($rec->dpAmount)) {
    		$total = $this->_total->amount- $this->_total->discount;
    		$total = $total + $this->_total->vat;
    		
    		if($rec->paymentMethodId){
    			core_Lg::push($rec->tplLang);
    			$data->row->paymentMethodId = tr(cond_PaymentMethods::getVerbal($rec->paymentMethodId, 'title'));
    			cond_PaymentMethods::preparePaymentPlan($data, $rec->paymentMethodId, $total, $rec->date, $rec->currencyId);
    			core_Lg::pop();
    		}
    	} else {
    		unset($data->row->paymentMethodId);
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
    	$rec = $data->rec;
    
    	if(sales_Invoices::haveRightFor('add', (object)array('originId' => $rec->originId, 'sourceContainerId' => $rec->containerId))){
    		$data->toolbar->addBtn('Фактура', array('sales_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => TRUE), 'title=Създаване на фактура от проформа фактура,ef_icon=img/16/invoice.png,row=2');
    	}
    	
    	if($rec->state == 'active'){
    		$amount = ($rec->dealValue - $rec->discountAmount) + $rec->vatAmount;
    		$amount /= $rec->rate;
    		$amount = round($amount, 2);
    		
    		if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->originId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер към проформата');
		    }
		    
    		if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->originId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ към проформата');
		    }
    	}
    }
    
    
    /**
     * Намира очаквания аванс по проформа, ако има
     * Връща начисления аванс от последната проформа за начисляване на аванс,
     * ако има платежни документи след нея не връщаме сумата (не очакваме аванс)
     * 
     * @param mixed $saleId - ид или запис на продажба
     * @return NULL|double - очаквано авансово плащане
     */
    public static function getExpectedDownpayment($saleId)
    {
    	$saleRec = sales_Sales::fetchRec($saleId);
    	
    	$expectedDownpayment = NULL;
    	
    	// Намираме последната проформа към продажбата (ако има)
    	$pQuery = self::getQuery();
    	$pQuery->where("#originId = {$saleRec->containerId}");
    	$pQuery->where("#state = 'active'");
    	$pQuery->where("#dpAmount IS NOT NULL AND #dpOperation = 'accrued'");
    	$pQuery->orderBy('id', 'DESC');
    	
    	// Ако има намерена проформа
    	if($profRec = $pQuery->fetch()){
    		
    		// Ако има приходен касов ордер с вальор по-голям не намираме очакван аванс
    		if(cash_Pko::fetchField("#threadId = {$saleRec->threadId} AND #state = 'active' AND #valior > '{$profRec->date}'")) return $expectedDownpayment;
    		
    		// Ако има приходен банков ордер с вальор по-голям не намираме очакван аванс
    		if(bank_IncomeDocuments::fetchField("#threadId = {$saleRec->threadId} AND #state = 'active' AND #valior > '{$profRec->date}'")) return $expectedDownpayment;
    		
    		// Ако няма платежен документ след проформата намираме очаквания и аванс
    		$expectedDownpayment += $profRec->dealValue + $profRec->vatAmount;
    		$expectedDownpayment = round($expectedDownpayment, 2);
    	}
    	
    	// Връщаме очаквания аванс
    	return $expectedDownpayment;
    }
}