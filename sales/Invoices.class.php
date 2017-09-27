<?php



/**
 * Изходящи фактури
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Invoices extends deals_InvoiceMaster
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, acc_TransactionSourceIntf=sales_transaction_Invoice, bgerp_DealIntf, deals_InvoiceSourceIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Inv';


    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Заглавие
     */
    public $title = 'Фактури за продажби';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, plg_Sorting, acc_plg_Contable, plg_Clone, doc_DocumentPlg, bgerp_plg_Export,
					doc_EmailCreatePlg, doc_plg_MultiPrint, recently_Plugin, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues,deals_plg_DpInvoice,
                    doc_plg_HidePrices, doc_plg_TplManager, acc_plg_DocumentSummary, change_Plugin,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number, date, dueDate=Срок, place, folderId, currencyId=Валута, dealValue=Общо, valueNoVat=Без ДДС, vatAmount, type';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_InvoiceDetails' ;
    
    
    /**
     * Старо име на класа
     */
    public $oldClassName = 'acc_Invoices';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'salesMaster, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,invoicer';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales,acc';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,invoicer';
	
	
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,invoicer';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canExport = 'ceo,invoicer';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,invoicer';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, contragentName';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.3|Търговия";
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'sales_InvoiceDetails';
    
    
    /**
     * Дефолт диапазон за номерацията на фактурите от настройките на пакета
     */
    public $defaultNumRange = 1;
    
    
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
    		'template' 		      => 'lastDocUser|lastDoc|defMethod',
    );
    
    
    /**
     * Кои полета ако не са попълнени във визитката на контрагента да се попълнят след запис
     */
    public static $updateContragentdataField = array(
    				    'vatId'   => 'contragentVatNo',
    				    'uicId'   => 'uicNo',
    					'egn'     => 'uicNo',
    					'pCode'   => 'contragentPCode',
		    		    'place'   => 'contragentPlace',
		    		    'address' => 'contragentAddress',
    );
    
    
    /**
     * Кои полета да могат да се експортират в CSV формат
     * 
     * @see bgerp_plg_CsvExport
     */
    public $exportableCsvFields = 'date,contragentName,contragentVatNo,uicNo,dealValue,accountId,number,state';
    

    /**
     * Кой може да променя активирани записи
     * @see change_Plugin
     */
    public $canChangerec = 'accMaster, ceo, invoicer';
    
    
    /**
     * Кои полета да могат да се променят след активация
     */
    public $changableFields = 'responsible,contragentCountryId, contragentPCode, contragentPlace, contragentAddress, dueTime, dueDate, additionalInfo,accountId,paymentType,template';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'sales_InvoiceDetails';
    
    
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
    	
    	$this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=title, allowEmpty)', 'caption=Плащане->Банкова с-ка, changable');
    	
    	$this->FLD('numlimit', 'enum(1,2)', 'caption=Диапазон, after=template,input=hidden,notNull,default=1');
    	
    	$this->FLD('number', 'bigint(21)', 'caption=Номер, after=place,input=none');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none');
        $this->FLD('type', 'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие,dc_note=Известие)', 'caption=Вид, input=hidden');
        
        $this->setDbUnique('number');
    }
	
	
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Фактура нормален изглед', 'content' => 'sales/tpl/InvoiceHeaderNormal.shtml', 
    			'narrowContent' =>  'sales/tpl/InvoiceHeaderNormalNarrow.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Фактура кратък изглед', 'content' => 'sales/tpl/InvoiceHeaderNormalShort.shtml',
    			'narrowContent' =>  'sales/tpl/InvoiceHeaderNormalNarrow.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Фактура за факторинг', 'content' => 'sales/tpl/InvoiceFactoring.shtml',
    			'narrowContent' =>  'sales/tpl/InvoiceFactoringNarrow.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Invoice', 'content' => 'sales/tpl/InvoiceHeaderNormalEN.shtml',
    			'narrowContent' =>  'sales/tpl/InvoiceHeaderNormalNarrowEN.shtml', 'lang' => 'en' , 'oldName' => 'Фактурa EN');
        $tplArr[] = array('name' => 'Invoice short', 'content' => 'sales/tpl/InvoiceHeaderShortEN.shtml', 
        		'narrowContent' =>  'sales/tpl/InvoiceHeaderShortNarrowEN.shtml', 'lang' => 'en');
        $tplArr[] = array('name' => 'Фактура с цени във евро', 'content' => 'sales/tpl/InvoiceHeaderEuro.shtml', 'lang' => 'bg');
        
    	$res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     * Попълва дефолт данните от проформата
     */
    private function prepareFromProforma($proformaRec, &$form)
    {
    	if(isset($form->rec->id)) return;
    	
    	$unsetFields = array('id', 'number', 'state', 'searchKeywords', 'containerId', 'brState', 'lastUsedOn', 'createdOn', 'createdBy', 'modifiedOn', 'modifiedBy', 'dealValue', 'vatAmount', 'discountAmount', 'sourceContainerId', 'additionalInfo', 'dueDate', 'dueTime', 'template');
    	foreach ($unsetFields as $fld){
    		unset($proformaRec->{$fld});
    	}
    	
    	foreach (($proformaRec) as $k => $v){
    		$form->rec->{$k} = $v;
    	}
    	if($form->rec->dpAmount){
    		$form->rec->dpAmount = abs($form->rec->dpAmount);
    	}
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	$defInfo = "";
    	
    	if($form->rec->sourceContainerId){
    		$Source = doc_Containers::getDocument($form->rec->sourceContainerId);
    		if($Source->isInstanceOf('sales_Proformas')){
    			if($proformaRec = $Source->fetch()){
    				$mvc->prepareFromProforma($proformaRec, $form);
    				$handle = sales_Proformas::getHandle($Source->that);
    				$mvc->pushTemplateLg($form->rec->template);
    				$defInfo .= (($defInfo) ? ' ' : '') . tr("По проформа|* #") . $handle . "\n";
    				core_Lg::pop();
    			}
    		}
    	}
    	
    	parent::prepareInvoiceForm($mvc, $data);
    	
    	$form->setField('contragentPlace', 'mandatory');
    	$form->setField('contragentAddress', 'mandatory');
    	
    	$conf = core_Packs::getConfig('sales');
    	$options = array();
    	$options[1] = "{$conf->SALE_INV_MIN_NUMBER1} - {$conf->SALE_INV_MAX_NUMBER1}";
    	$options[2] = "{$conf->SALE_INV_MIN_NUMBER2} - {$conf->SALE_INV_MAX_NUMBER2}";
    	$form->setOptions('numlimit', $options);
    	$form->setDefault('numlimit', $mvc->defaultNumRange);
    	
    	if(haveRole('ceo,accMaster')){
    		$form->setField('numlimit', 'input');
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
    			$form->setField('vatReason', 'mandatory');
    		}
    	}
    	
    	$firstDoc = doc_Threads::getFirstDocument($form->rec->threadId);
    	$firstRec = $firstDoc->rec();
    	 
    	$tLang = doc_TplManager::fetchField($form->rec->template, 'lang');
    	core_Lg::push($tLang);
    	
    	$showSale = core_Packs::getConfigValue('sales', 'SALE_INVOICES_SHOW_DEAL');
    	
    	if($showSale == 'yes' && empty($form->rec->sourceContainerId)){
    		// Ако продажбата приключва други продажби също ги попълва в забележката
    		if($firstRec->closedDocuments){
    			$docs = keylist::toArray($firstRec->closedDocuments);
    			$closedDocuments = '';
    			foreach ($docs as $docId){
    				$dRec = sales_Sales::fetch($docId);
    				$date = sales_Sales::getVerbal($dRec, 'valior');
    				$handle = sales_Sales::getHandle($dRec->id);
    				$closedDocuments .= " #{$handle}/{$date},";
    			}
    			$closedDocuments = trim($closedDocuments, ", ");
    			$defInfo .= tr('|Съгласно сделки|*: ') . $closedDocuments . PHP_EOL;
    		} else {
    			$handle = sales_Sales::getHandle($firstRec->id);
    			Mode::push('text', 'plain');
    			$valior = $firstDoc->getVerbal('valior');
    			Mode::pop('text');
    			$defInfo .= tr("Съгласно сделка") . ": #{$handle}/{$valior}";
    			
    			// Ако продажбата има референтен номер, попълваме го в забележката
    			if($firstRec->reff){
    				
    				// Ако рефа е по офертата на сделката към която е фактурата
    				if(isset($firstRec->originId)){
    					$origin = doc_Containers::getDocument($firstRec->originId);
    					if($firstRec->reff == $origin->getHandle()){
    						$firstRec->reff = "#" . $firstRec->reff;
    					}
    				}
    				$defInfo .= " " . tr("({$firstRec->reff})") . PHP_EOL;
    			}
    		}
    	}
    	
    	core_Lg::pop();
    	
    	// Ако има дефолтен текст за фактура добавяме и него
    	if($invText = cond_Parameters::getParameter($firstRec->contragentClassId, $firstRec->contragentId, 'invoiceText')){
    		$defInfo .= "\n" .$invText;
    	}
    	
    	// Задаваме дефолтния текст
    	$form->setDefault('additionalInfo', $defInfo);
    	
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = $form->rec;
    	parent::inputInvoiceForm($mvc, $form);
    	
    	if($form->isSubmitted()){
    		if($rec->type != 'dc_note' && empty($rec->accountId)){
    			if($paymentMethodId = doc_Threads::getFirstDocument($rec->threadId)->fetchField('paymentMethodId')){
    				$paymentPlan = cond_PaymentMethods::fetch($paymentMethodId);
    				
    				if(!empty($paymentPlan->timeBalancePayment) || $paymentPlan->type == 'bank' || $rec->paymentType == 'bank'){
    					$form->setWarning('accountId', "Сигурни ли сте, че не е нужно да се посочи и банкова сметка|*?");
    				}
    			}
    		}
    	}
	}
    
    
    /**
     * Валидиране на полето 'number' - номер на фактурата
     * 
     * Предупреждение при липса на ф-ра с номер едно по-малко от въведения.
     */
    public function on_ValidateNumber(core_Mvc $mvc, $rec, core_Form $form)
    {
        if (empty($rec->number)) return;
        
        $prevNumber = intval($rec->number)-1;
        if (!$mvc->fetchField("#number = {$prevNumber}")) {
            $form->setWarning('number', 'Липсва фактура с предходния номер!');
        }
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        parent::beforeInvoiceSave($rec);
        
        if($rec->state == 'active'){
        	if(empty($rec->number)){
        		$rec->number = self::getNextNumber($rec);
        		$rec->searchKeywords .= " " . plg_Search::normalizeText($rec->number);
        	}
        }
    }
    
    
    /**
     * Преди подготовката на обобщението на фактурата
     */
    public function on_BeforePrepareSummary($mvc, &$total)
    {
    	if(count($total->vats) == 1){
    		$conf = core_Packs::getConfig('sales');
    		
    		// Ако сме задали ддс сумата да е твърд процент от сумата без ддс и всички артикули са със една и съща ставка на ддс
    		if($conf->SALE_INV_VAT_DISPLAY == 'yes'){
    			$total->vat = $total->amount * key($total->vats);
    		}
    	}
    }
    
    
   /**
    * Извиква се преди рендирането на 'опаковката'
    */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	if(!Mode::is('printing')){
    		$original = tr('ОРИГИНАЛ');
    		$tpl->replace($original, 'INV_STATUS');
    	}
    	
    	$tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	if($rec->type == 'invoice' && $rec->state == 'active' && $rec->dpOperation != 'accrued'){
    		
    		if(dec_Declarations::haveRightFor('add', (object)array('originId' => $data->rec->containerId, 'threadId' => $data->rec->threadId))){
    			$data->toolbar->addBtn('Декларация', array('dec_Declarations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/declarations.png, row=2, title=Създаване на декларация за съответсвие');
    		}
    	}
    	
    	if($rec->state == 'active'){
    		$amount = ($rec->dealValue - $rec->discountAmount) + $rec->vatAmount;
    		$amount /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
    		$amount = round($amount, 2);
    		
    		if($amount < 0){
    			if(cash_Rko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("РКО", array('cash_Rko', 'add', 'originId' => $rec->originId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов разходен касов ордер към документа');
    			}
    			if(bank_SpendingDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("РБД", array('bank_SpendingDocuments', 'add', 'originId' => $rec->originId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов разходен банков документ');
    			}
    		} else {
    			if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->originId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер към документа');
    			}
    			if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->originId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ');
    			}
    		}
    	}
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
    			$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
    			
    			$row->accountId = cls::get('iban_Type')->toVerbal($ownAcc->iban);
    			$row->bank = $Varchar->toVerbal($ownAcc->bank);
    			core_Lg::push($rec->tplLang);
    			$row->bank = transliterate(tr($row->bank));
    			$row->place = transliterate($row->place);
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
     * Ф-я връщаща следващия номер на фактурата, ако той е в границите
     * 
     * @return int - следващия номер на фактура
     */
    protected static function getNextNumber($rec)
    {
    	$conf = core_Packs::getConfig('sales');
    	if($rec->numlimit == 2){
    		$min = $conf->SALE_INV_MIN_NUMBER2;
    		$max = $conf->SALE_INV_MAX_NUMBER2;
    	} else {
    		$min = $conf->SALE_INV_MIN_NUMBER1;
    		$max = $conf->SALE_INV_MAX_NUMBER1;
    	}
    	
    	$query = static::getQuery();
    	$query->XPR('maxNum', 'int', 'MAX(#number)');
    	$query->between("number", $min, $max);
    	
    	if(!$maxNum = $query->fetch()->maxNum){
    		$maxNum = $min;
    	}
    	$nextNum = $maxNum + 1;
    	
    	if($nextNum > $max) return NULL;
    	
    	return $nextNum;
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
    		 
    		 if(!($firstDoc->isInstanceOf('sales_Sales') && $docState == 'active')){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'restore' && isset($rec)){
    		$lastDate = $mvc->getNewestInvoiceDate($rec->numlimit);
    		if($lastDate > $rec->date) {
    			$res = 'no_one';
    		}
    	}
    	
    	// Само ceo,salesmaster и acc могат да оттеглят контирана фактура
    	if($action == 'reject' && isset($rec)){
    		if($rec->state == 'active'){
    			if(!haveRole('ceo,sales,invoicer', $userId)){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Само ceo,salesmaster и acc могат да възстановят фактура
    	if($action == 'restore' && isset($rec)){
    		if($rec->brState == 'active'){
    			if(!haveRole('ceo,sales,invoicer', $userId)){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	if ($action == 'changerec' && $rec) {
    	    $period = acc_Periods::fetchByDate($rec->date);
    	    if (!$period || $period->state == 'closed') {
    	        $res = 'no_one';
    	    }
    	}
    }
    
    
    /**
     * След рендиране на копия за принтиране
     * @see doc_plg_MultiPrint
     * 
     * @param core_Mvc $mvc - мениджър
     * @param core_ET $copyTpl - копие за рендиране
     * @param int $copyNum - пореден брой на копието за принтиране
     */
    public static function on_AfterRenderPrintCopy($mvc, &$copyTpl, $copyNum, $rec)
    {
    	if($rec->tplLang == 'bg'){
    		$inv_status = ($copyNum == '1') ?  'ОРИГИНАЛ' : 'КОПИЕ';
    	} else {
    		$inv_status = ($copyNum == '1') ?  'ORIGINAL' : 'COPY';
    	}
    	
    	$copyTpl->replace($inv_status, 'INV_STATUS');
    }
    
    
    /**
     * Преди експортиране като CSV
     */
   	public static function on_BeforeExportCsv($mvc, &$recs)
   	{
   	    if (!$recs) return ;
   	    
   	    foreach ($recs as &$rec) {
   	        $rec->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
   	        $rec->dealValue = round($rec->dealValue - $rec->discountAmount, 2);
   	    }
   	}
   	
   	
   	/**
   	 * След подготвяне на заявката за експорт
   	 */
   	public static function on_AfterPrepareExportQuery($mvc, &$query)
   	{
   		// Искаме освен фактурите показващи се в лист изгледа да излизат и тези,
   		// които са били активни, но сега са оттеглени
   		$query->where("#state != 'draft' OR (#state = 'rejected' AND #brState = 'active')");
   	}
   	
   	
   	/**
   	 * Връща сумата на ддс-то на платените в брой фактури, в основната валута
   	 * 
   	 * @param date $from - от
   	 * @param date $to - до
   	 * @return double $amount - сумата на ддс-то на платените в брой фактури
   	 */
   	public static function getVatAmountInCash($from, $to = NULL)
   	{
   		if(empty($to)){
   			$to = dt::today();
   		}
   		
   		$amount = 0;
   		$query = static::getQuery();
   		
   		$query->where("#paymentType = 'cash' OR (#paymentType IS NULL AND #autoPaymentType = 'cash')");
   		$query->where("#state = 'active'");
   		$query->between("date", $from, $to);
   		
   		while($rec = $query->fetch()){
   			$total = $rec->vatAmount;
   			$amount += $total;
   		}
   		
   		return round($amount, 2);
   	}


	/**
   	 * Връща датата на последната ф-ра
   	 */
   	protected function getNewestInvoiceDate($diapason)
   	{
   		$query = $this->getQuery();
   		$query->where("#state = 'active'");
   		$query->where("#numlimit = {$diapason}");
   		$query->orderBy('date', 'DESC');
   		$query->limit(1);
   		$lastRec = $query->fetch();
   		 
   		return $lastRec->date;
   	}
   	
   	
   	/**
   	 * Валидиране на полето 'date' - дата на фактурата
   	 * Предупреждение ако има фактура с по-нова дата (само при update!)
   	 */
   	public static function on_ValidateDate(core_Mvc $mvc, $rec, core_Form $form)
   	{
   		// Ако фактурата е вече контирана не правим проверка за дата
   		if($form->rec->state == 'active') return;
   		
   		$newDate = $mvc->getNewestInvoiceDate($rec->numlimit);
   		if($newDate > $rec->date) {
   			
   			// Най-новата валидна ф-ра в БД е по-нова от настоящата.
   			$form->setError('date',
   					'Не може да се запише фактура с дата по-малка от последната активна фактура в диапазона|* (' .
   					dt::mysql2verbal($newDate, 'd.m.y') .
   					')'
   			);
   		}
   	}
    
    
    /**
     * Текст за грешка при бутон за контиране
     */
    public static function on_AfterGetBtnErrStr($mvc, &$res, $rec)
    {
    	if (empty($rec->number) && $rec->state != 'active' && $mvc->haveRightFor('conto', $rec)) {
            
            if ($rec->numlimit && $rec->date) {
                $newDate = $mvc->getNewestInvoiceDate($rec->numlimit);
                
                if ($newDate && ($newDate > $rec->date)) {
                    $res = 'Не може да се запише фактура с дата по-малка от последната активна фактура в диапазона|* (' .
                                    dt::mysql2verbal($newDate, 'd.m.y') .
                                    ')';
                }
            }
        }
        
        if(empty($res)){
        	if($rec->date > dt::today()){
        		$res = 'Фактурата е с бъдещата дата и не може да бъде контирана';
        	}
        }
    }
   	
   	
   	/**
   	 * Метод по подразбиране за намиране на дефолт шаблона
   	 */
   	public function getDefaultTemplate_($rec)
   	{
   		if($rec->folderId){
   			$cData = doc_Folders::getContragentData($rec->folderId);
   		}
   		
   		$bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
   		$conf = core_Packs::getConfig('sales');
   		$def = (empty($cData->countryId) || $bgId === $cData->countryId) ? $conf->SALE_INVOICE_DEF_TPL_BG : $conf->SALE_INVOICE_DEF_TPL_EN;
   		 
   		return $def;
   	}
   	
   	
   	/**
   	 * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
   	 */
   	public static function getHandle($id)
   	{
   		$self = cls::get(get_called_class());
   		$rec = $self->fetch($id);
   	
   		if (!$rec->number) {
   			$hnd = $self->abbr . $rec->id;
   		} else {
   			$number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
   			$hnd = $self->abbr . $number;
   		}
   	
   		return $hnd;
   	}
   	
   	
   	/**
   	 * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
   	 */
   	public static function fetchByHandle($parsedHandle)
   	{
   		if ($parsedHandle['endDs'] && (strlen($parsedHandle['id']) != 10)) {
   			$rec = static::fetch($parsedHandle['id']);
   		} else {
   			$number = ltrim($parsedHandle['id'], '0');
   			if ($number) {
   				$rec = static::fetch("#number = '{$number}'");
   			}
   		}
   	
   		return $rec;
   	}
}