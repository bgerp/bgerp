<?php



/**
 * Входящи фактури към покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Invoices extends deals_InvoiceMaster
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, acc_TransactionSourceIntf=purchase_transaction_Invoice, bgerp_DealIntf, deals_InvoiceSourceIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ini';
    
    
    /**
     * Заглавие
     */
    public $title = 'Входящи фактури';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Входяща фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, purchase_Wrapper, doc_plg_TplManager, plg_Sorting, acc_plg_Contable, doc_DocumentPlg,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues,deals_plg_DpInvoice,
                    doc_plg_HidePrices, acc_plg_DocumentSummary, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number, date, place, folderId, currencyId=Валута, dealValue=Стойност, valueNoVat=Без ДДС, vatAmount, type';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'purchase_InvoiceDetails';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,invoicer';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,purchase,acc';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,purchase,acc';
	
	
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,invoicer';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'purchaseMaster, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,invoicer';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, id, contragentName';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutInvoice.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,purchaseMaster,manager';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.3|Търговия";
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'purchase_InvoiceDetails';
    

    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,date,dueDate';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    		'place'               => 'lastDocUser|lastDoc',
    		'responsible'         => 'lastDocUser|lastDoc',
    		'contragentCountryId' => 'clientData|lastDocUser|lastDoc',
    		'contragentVatNo'     => 'clientData|lastDocUser|lastDoc',
    		'uicNo'     		  => 'clientData|lastDocUser|lastDoc',
    		'contragentPCode'     => 'clientData|lastDocUser|lastDoc',
    		'contragentPlace'     => 'clientData|lastDocUser|lastDoc',
    		'contragentAddress'   => 'clientData|lastDocUser|lastDoc',
    		'accountId'           => 'lastDocUser|lastDoc',
    		'template' 		      => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	parent::setInvoiceFields($this);
    	
    	$this->FLD('number', 'varchar', 'caption=Номер, export=Csv,hint=Номера с който идва фактурата,after=place');
    	$this->FLD('fileHnd', 'fileman_FileType(bucket=Documents)', 'caption=Документ,after=number');
    	
    	$this->FLD('accountId', 'key(mvc=bank_Accounts,select=iban, allowEmpty)', 'caption=Плащане->Банкова с-ка, export=Csv');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контирана, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none,export=Csv');
    	$this->FLD('type', 'enum(invoice=Входяща фактура, credit_note=Входящо кредитно известие, debit_note=Входящо дебитно известие, dc_note=Известие)', 'caption=Вид, input=hidden');
    }
    
    
    /**
     * Връща асоциираната форма към MVC-обекта
     */
    public static function on_AfterGetForm($mvc, &$form, $params = array())
    {
    	
    	$form->FLD('contragentSource', 'enum(company=Фирми,newContragent=Нов доставчик)', 'input,silent,removeAndRefreshForm=selectedContragentId,caption=Контрагент->Източник,before=contragentName');
    	$form->setDefault('contragentSource', 'company');
    	$form->FLD('selectedContragentId', 'int', 'input=none,silent,removeAndRefreshForm,caption=Контрагент->Избор,after=contragentSource');
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
		$form = $data->form;
    	$rec = $form->rec;
    	$origin = $mvc->getOrigin($form->rec);
    	
    	if($origin->isInstanceOf('findeals_AdvanceReports')){
    		$form->setOptions('vatRate', arr::make('separate=Отделно, exempt=Oсвободено, no=Без начисляване'));
    		$form->setField('vatRate', 'input');
    		$form->setDefault('vatRate', 'separate');
    		
    		if(isset($form->rec->id)){
    			if(purchase_InvoiceDetails::fetch("#invoiceId = {$form->rec->id}")){
    				$form->setReadOnly('vatRate');
    			}
    		}
    	}
    	
    	// Ако ф-та не е към служебен аванс не искаме да се сменя контрагента
    	$firstDocument = doc_Threads::getFirstDocument($form->rec->threadId);
    	if(!$firstDocument->isInstanceOf('findeals_AdvanceDeals')){
    		$form->setField('contragentSource', 'input=none');
    		unset($form->rec->contragentSource);
    	}
    	
    	// Ако има избрано поле за източник на контрагента
    	if(isset($rec->contragentSource)){
    		if($rec->contragentSource == 'company'){
    			$form->setField('selectedContragentId', 'input');
    			$form->setFieldType('selectedContragentId' , core_Type::getByName('key(mvc=crm_Companies,select=name,allowEmpty)'));
    		}
    	}
    	
    	parent::prepareInvoiceForm($mvc, $data);
    	
    	if($data->aggregateInfo){
    		if($data->aggregateInfo->get('bankAccountId')){
    			$form->rec->accountId = $data->aggregateInfo->get('bankAccountId');
    		}
    	}
    	
    	$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	$form->setOptions('accountId', bank_Accounts::getContragentIbans($coverId, $coverClass, TRUE));
    	
    	if($form->rec->vatRate != 'yes' && $form->rec->vatRate != 'separate'){
    		$form->setField('vatReason', 'mandatory');
    	}
    	
    	$bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
    	if($rec->contragentCountryId == $bgId){
    		$form->setFieldType('number', core_Type::getByName('bigint(size=10)'));
    	}
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	$unsetFields = FALSE;
    	
    	// Махане на дефолтните данни при нужда
    	if((empty($rec->id) && $form->cmd != 'save' && isset($rec->contragentSource) && $rec->contragentSource != 'newContragent' && empty($rec->selectedContragentId))){
    		$unsetFields = TRUE;
    	}
    	
    	if($form->cmd == 'refresh'){
    		if($rec->contragentSource == 'newContragent'){
    			$unsetFields = TRUE;
    		}
    		
    		$arr = array();
    		
    		// Ако е избран контрагент замества ме му данните
    		if(isset($rec->selectedContragentId)){
    			if($rec->contragentSource == 'company') {
    				$cData = crm_Companies::getContragentData($rec->selectedContragentId);
    				foreach (array('contragentName' => 'company', 'contragentCountryId' => 'countryId', 'contragentVatNo' => 'vatNo', 'uicNo' => 'uicId', 'contragentPCode' => 'pCode', 'contragentPlace' => 'place', 'contragentAddress' => 'address') as $k => $v){
    					$arr[$k] = $cData->{$v};
    				}
    				$arr['contragentClassId'] = crm_Companies::getClassId();
    				$arr['contragentId'] = $rec->selectedContragentId;
    			} else {
    				$arr['contragentClassId'] = NULL;
    				$arr['contragentId'] = NULL;
    			}
    			 
    			if(count($arr)){
    				foreach (array("contragentName", "contragentClassId", "contragentId", "contragentCountryId", "contragentVatNo", "uicNo", "contragentPCode", "contragentPlace", "contragentAddress")  as $fld){
    					$form->rec->{$fld} = $arr[$fld];
    				}
    			}
    		}
    	}
    	
    	// Ако е указано да махнем записаните данни, правим го
    	if($unsetFields === TRUE){
    		foreach (array("contragentName", "contragentClassId", "contragentId", "contragentCountryId", "contragentVatNo", "uicNo", "contragentPCode", "contragentPlace", "contragentAddress")  as $fld){
    			unset($rec->{$fld});
    		}
    		
    		$ownCountryId = crm_Setup::get('BGERP_OWN_COMPANY_COUNTRY', TRUE);
    		$rec->contragentCountryId = drdata_Countries::fetchField("#commonName = '{$ownCountryId}'", 'id');
    	}
    	
    	if($rec->type != 'dc_note'){
    		// Ако източника е фирма и не е избрана фирма, забраняваме определени полета
    		if($rec->contragentSource == 'company' && empty($rec->selectedContragentId)) {
    			foreach (array("contragentName", "contragentCountryId", "contragentVatNo", "uicNo", "contragentPCode", "contragentPlace", "contragentAddress")  as $fld){
    				$form->setReadOnly($fld);
    			}
    		}
    	}
    	
    	parent::inputInvoiceForm($mvc, $form);
    	
    	if($form->isSubmitted()){
    		if($rec->contragentSource == 'newContragent'){
    			$cRec = self::getContragentRec($rec);
    			
    			// Проверяваме да няма дублиране на записи
    			$resStr = crm_Companies::getSimilarWarningStr($cRec);
    			if ($resStr) {
    				$form->setWarning('contragentName,contragentCountryId,contragentVatNo,uicNo,contragentPCode,contragentPlace,contragentAddress', $resStr);
    			}
    		}
    		
    		if(empty($rec->number)){
    			$rec->number = NULL;
    		}
    		
    		if(!$mvc->isNumberFree($rec)){
    			$form->setError("{$fld},number", 'Има вече входяща фактура с този номер, за този контрагент');
    		}
    	}
    }
    
    
    /**
     * Връща запис с данните на контрагента
     * 
     * @param stdClass $rec
     * @return stdClass $cRec
     */
    private static function getContragentRec($rec)
    {
    	$cRec = (object)array('name' => $rec->contragentName, 'country' => $rec->contragentCountryId, 'vatId' => $rec->contragentVatNo, 'uicId' => $rec->uicNo, 'pCode' => $rec->contragentPCode, 'place' => $rec->contragentPlace, 'address' => $rec->contragentAddress);
    
    	return $cRec;
    }
    
    
    /**
     * Преди възстановяване, ако има затворени пера в транзакцията, не може да се възстановява
     */
    protected static function on_BeforeRestore($mvc, &$res, $id)
    {
    	// Ако има фактура с този номер, не възстановяваме
    	if(!$mvc->isNumberFree($id)){
    		core_Statuses::newStatus('Има вече входяща фактура с този номер, за този контрагент', 'error');
    		return FALSE;
    	}
    }
    
    
    /**
     * Проверява дали номера е свободен
     * 
     * @param stdClass $rec
     * @return boolean
     */
    private function isNumberFree($rec)
    {
    	$rec = $this->fetchRec($rec);
    	
    	if(empty($rec->number)) return TRUE;
    	
    	// Проверяваме дали за този контрагент има друга фактура със същия номер, която не е оттеглена
    	foreach (array('contragentVatNo', 'uicNo') as $fld){
    		if(!empty($rec->{$fld})){
    			if($this->fetchField("#{$fld}='{$rec->{$fld}}' AND #number='{$rec->number}' AND #id != '{$rec->id}' AND #state != 'rejected'")){
    				return FALSE;
    			}
    		}
    	}
    	
    	return TRUE;
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
    	parent::beforeInvoiceSave($rec);
    	
    	// Форсиране на нова фирма, ако е указано
    	if($rec->state == 'draft'){
    		if($rec->contragentSource == 'newContragent'){
    			$cRec = self::getContragentRec($rec);
    			$rec->contragentId = crm_Companies::save($cRec);
    			$rec->contragentClassId = crm_Companies::getClassId();
    			core_Statuses::newStatus("Добавена е нова фирма|* '{$rec->contragentName}'");
    		}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('purchase/tpl/invoiceStyles.css', 'CSS');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	parent::getVerbalInvoice($mvc, $rec, $row, $fields);
    	
    	if($fields['-single']){
    		if($rec->accountId){
    			$Varchar = cls::get('type_Varchar');
    			$ownAcc = bank_Accounts::fetch($rec->accountId);
    			$row->bank = $Varchar->toVerbal($ownAcc->bank);
    			$row->bic = $Varchar->toVerbal($ownAcc->bic);
    		}
    	}
    }


    /*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
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
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
	        
    	if($action == 'add' && isset($rec->threadId)){
    		 $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    		 $docState = $firstDoc->fetchField('state');
    		 
    		 if(!(($firstDoc->isInstanceOf('purchase_Purchases') || $firstDoc->isInstanceOf('findeals_AdvanceDeals')) && $docState == 'active')){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Входяща фактура нормален изглед', 'content' => 'purchase/tpl/InvoiceHeaderNormal.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Входяща фактура изглед за писмо', 'content' => 'purchase/tpl/InvoiceHeaderLetter.shtml', 'lang' => 'bg');
        
    	$res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	if(!$data->listFilter->getField('invType', FALSE)){
    		$data->listFilter->FNC('invType', 'enum(all=Всички, invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 'caption=Вид,input,silent');
    	}
    	 
    	$data->listFilter->showFields .= ',invType';
    	 
    	$data->listFilter->input(NULL, 'silent');
    	 
    	if($rec = $data->listFilter->rec){
    		if($rec->invType){
    			if($rec->invType != 'all'){
   					$data->query->where("#type = '{$rec->invType}'");
   					
   					$sign = ($rec->invType == 'credit_note') ? "<=" : ">";
   					$data->query->orWhere("#type = 'dc_note' AND #dealValue {$sign} 0");
   				}
    		}
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($rec->state == 'active'){
    		$amount = $rec->dealValue + $rec->vatAmount;
    		$amount /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
    		$amount = round($amount, 2);
    
    		if($amount < 0){
    			if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->containerId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_delete.png,title=Създаване на нов приходен касов ордер към документа');
    			}
    			
    			if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов приходен банков документ');
    			}
    		} else {
    			if(cash_Rko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("РКО", array('cash_Rko', 'add', 'originId' => $rec->containerId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов ордер към документа');
    			}
    			
    			if(bank_SpendingDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("РБД", array('bank_SpendingDocuments', 'add', 'originId' => $rec->containerId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов разходен банков документ');
    			}
    		}
    	}
    }
}