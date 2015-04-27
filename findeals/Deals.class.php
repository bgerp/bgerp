<?php
/**
 * Клас 'findeals_Deals'
 *
 * Мениджър за финансови сделки
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_Deals extends deals_DealBase
{
	
	const AGGREGATOR_TYPE = 'deal';
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'deals_Deals';
	
	
    /**
     * Заглавие
     */
    public $title = 'Финансови сделки';


    /**
     * Абревиатура
     */
    public $abbr = 'Fd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_RegisterIntf, doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, deals_DealsAccRegIntf, bgerp_DealIntf, bgerp_DealAggregatorIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, acc_plg_Registry, findeals_Wrapper, plg_Printing, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search, doc_plg_BusinessDoc, doc_ActivatePlg, plg_Sorting';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,findeals';
    
    
    /**
     * Кои сметки не могат да се избират
     */
    public static $exceptAccSysIds = '401,411,402,412';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,findeals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,findeals';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,findeals';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,findealsMaster';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,findeals';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,detailedName,folderId,state,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Финансова сделка';
   

    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/stock_new_meeting.png';


    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'findeals/tpl/SingleLayoutDeals.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'detailedName';
    
    
    /**
     * Брой детайли на страница
     */
    public $listDetailsPerPage = 20;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'dealName, accountId, description, folderId, id';
    
    
    /**
     * Как се казва приключващия документ
     */
    public $closeDealDoc = 'findeals_ClosedDeals';
    
    
    /**
     * По кое поле да се филтрира по дата
     */
    public $filterDateField = 'createdOn';
     
     
    /**
     * Позволени операции на последващите платежни документи
     */
    protected $allowedPaymentOperations = array(
    		'debitDealCase'      => array('title' => 'Приход по финансова сделка', 'debit' => '501', 'credit' => '*'),
    		'debitDealBank'      => array('title' => 'Приход по финансова сделка', 'debit' => '503', 'credit' => '*'),
    		'creditDealCase'     => array('title' => 'Разход по финансова сделка', 'debit' => '*', 'credit' => '501'),
    		'creditDealBank'     => array('title' => 'Разход по финансова сделка', 'debit' => '*', 'credit' => '503'),
	);
    
    
    /**
     * Позволени операции за посследващите складови документи/протоколи
     */
    public $allowedShipmentOperations = array('delivery' => array('title' => 'Експедиране на стока', 'debit' => '*', 'credit' => 'store'),
    										  'stowage'  => array('title' => 'Засклаждане на стока', 'debit' => 'store', 'credit' => '*'),
    );
    
    
    /**
     * Кое поле показва сумата на сделката
     */
    public $canClosewith = 'ceo,findealsMaster';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('dealName', 'varchar(255)', 'caption=Наименование');
    	$this->FLD('amountDeal', 'double(decimals=2)', 'input=none,notNull,oldFieldName=blAmount');
    	$this->FLD('accountId', 'acc_type_Account(regInterfaces=crm_ContragentAccRegIntf|deals_DealsAccRegIntf|currency_CurrenciesAccRegIntf)', 'caption=Сметка,mandatory,silent');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент');
    	
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Валута->Код');
    	$this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс');
    	
    	$this->FLD('companyId', 'key(mvc=crm_Companies,select=name,allowEmpty)', 'caption=Втори контрагент->Фирма,input');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Втори контрагент->Лице,input');
    	
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
    	$this->FLD('contragentId', 'int', 'input=hidden');
    	
    	$this->FLD('secondContragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=none');
    	$this->FLD('secondContragentId', 'int', 'input=none');
    	
    	$this->FLD('description', 'richtext(rows=4)', 'caption=Допълнително->Описание');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Приключен)','caption=Състояние, input=none');
    	
    	$this->FNC('detailedName', 'varchar', 'column=none,caption=Име');
    	$this->FLD('dealManId', 'class(interface=deals_DealsAccRegIntf)', 'input=none');
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * Документи-финансови сделки могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * Име за избор
     */
    static function on_CalcDetailedName($mvc, &$rec) 
    {
     	if (!$rec->contragentName || !$rec->createdOn) return;
     	
     	$createdOn = dt::mysql2verbal($rec->createdOn, 'Y-m-d');
     	
     	$rec->detailedName = $rec->id . "." . $rec->contragentName . " / {$createdOn} / " . $rec->dealName;
     	$rec->detailedName = trim($rec->detailedName, '/ ');
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareEditForm_($data)
    {
    	parent::prepareEditForm_($data);
    	
    	$form = &$data->form;
    	$rec = &$form->rec;
    	 
    	$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	 
    	$form->setDefault('contragentClassId', $coverClass::getClassId());
    	$form->setDefault('contragentId', $coverId);
    	 
    	$form->rec->contragentName = $coverClass::fetchField($coverId, 'name');
    	$form->setReadOnly('contragentName');
    	 
    	$form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    	$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    	 
    	$options = cls::get('acc_Accounts')->makeArray4Select($select, array("#num LIKE '[#1#]%' AND state NOT IN ('closed')", $root));
    	
    	acc_type_Account::filterSuggestions('crm_ContragentAccRegIntf|deals_DealsAccRegIntf|currency_CurrenciesAccRegIntf', $options);
    	
    	// Премахваме от избора упоменатите сметки, които трябва да се изключат
    	$except = arr::make(static::$exceptAccSysIds);
    	foreach ($except as $sysId){
    		$accId = acc_Accounts::getRecBySystemId($sysId)->id;
    		unset($options[$accId]);
    	}
    	
    	$form->setOptions('accountId', array('' => '') + $options);
    	
    	return $data;
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if ($form->isSubmitted()){
    		$rec  = &$form->rec;
    		
    		if($rec->companyId && $rec->personId){
    			$form->setError('companyId,personId', 'Моля изберете само един втори контрагент');
    		}
    		
    		if($rec->companyId){
    			$rec->secondContragentClassId = crm_Companies::getClassId();
    			$rec->secondContragentId = $rec->companyId;
    		}
    		
    		if($rec->personId){
    			$rec->secondContragentClassId = crm_Persons::getClassId();
    			$rec->secondContragentId = $rec->personId;
    		}
    		
    		if(empty($rec->companyId) && empty($rec->personId)){
    			$rec->secondContragentClassId = NULL;
    			$rec->secondContragentId = NULL;
    		}
    		
    		if(!$rec->currencyRate){
    			
    			// Изчисляваме курса към основната валута ако не е дефиниран
    			$rec->currencyRate = round(currency_CurrencyRates::getRate(dt::now(), $rec->currencyId, NULL), 4);
    			if(!$rec->currencyRate){
    				$form->setError('rate', "Не може да се изчисли курс");
    			}
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, dt::now(), $rec->currencyId, NULL)){
    				$form->setWarning('currencyRate', $msg);
    			}
    		}
    		
    		$rec->dealManId = $mvc->getClassId();
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->accountId = acc_Accounts::getTitleById($rec->accountId);
    	
    	if($fields['-single']){
    		$row->contragentName = cls::get($rec->contragentClassId)->getHyperLink($rec->contragentId, TRUE);
    		
    		if($rec->secondContragentClassId){
    			$row->secondContragentId = cls::get($rec->secondContragentClassId)->getHyperLink($rec->secondContragentId, TRUE);
    		}

    		$lastBalance = acc_Balances::getLastBalance();
    		if(acc_Balances::haveRightFor('single', $lastBalance)){
    			$accUrl = array('acc_Balances', 'single', $lastBalance->id, 'accId' => $rec->accountId);
    			$row->accountId = ht::createLink($row->accountId, $accUrl);
    		}
    	}
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($rec->state == 'active'){
    		if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    			$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер');
    		}
    		
    		if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    			$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ');
    		}
    		
    		if(cash_Rko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    			$data->toolbar->addBtn("РКО", array('cash_Rko', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов разходен касов ордер');
    		}
    		
    		if(bank_SpendingDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    			$data->toolbar->addBtn("РБД", array('bank_SpendingDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов разходен банков документ');
    		}
    		
    		if(findeals_AdvanceReports::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    			$data->toolbar->addBtn("Ав. отчет", array('findeals_AdvanceReports', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/legend.png,title=Създаване на нов авансов отчет');
    		}
    		
    		if(findeals_ClosedDeals::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    			$data->toolbar->addBtn('Приключване', array('findeals_ClosedDeals', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), "ef_icon=img/16/closeDeal.png,title=Приключване на финансова сделка");
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// При създаване на сделка, тя не може да се активира
    	if($action == 'activate' && empty($rec)){
    		$res = 'no_one';
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$mvc->getHistory($data);
    }
    
    
    /**
     * Връща хронологията от журнала, където участва документа като перо
     */
    private function getHistory(&$data)
    {
    	$rec = $this->fetchRec($data->rec->id);
    	
    	$entries = acc_Journal::getEntries(array(get_called_class(), $rec->id), $item);
    	$data->rec->debitAmount = $data->rec->creditAmount = 0;
    	
    	if(count($entries)){
    		$data->history = array();
    		
    		$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
    		$Pager->itemsCount = count($entries);
    		$Pager->calc();
    		$data->pager = $Pager;
    		
    		$recs = array();
    		
    		// Групираме записите по документ
    		foreach($entries as $jRec){
    			$index = $jRec->docType . "|" . $jRec->docId;
    			if(empty($recs[$index])){
    				$recs[$index] = $jRec;
    			}
    			$r = &$recs[$index];
    			
    			$jRec->amount /= $rec->currencyRate;
    			if($jRec->debitItem2 == $item->id){
    				$r->debitA += $jRec->amount;
    			}
    			
    			if($jRec->creditItem2 == $item->id){
    				$r->creditA += $jRec->amount;
    			}
    		}
    		
    		// За всеки резултат, ако е в границите на пейджъра, го показваме
    		if(count($recs)){
    			$count = 0;
    			foreach ($recs as $rec){
    				$start = $data->pager->rangeStart;
    				$end = $data->pager->rangeEnd - 1;
    				if(empty($data->pager) || ($count >= $start && $count <= $end)){
    					$data->rec->debitAmount += $rec->debitA;
    					$data->rec->creditAmount += $rec->creditA;
    					$data->history[] = $this->getHistoryRow($rec);
    				}
    				$count++;
    			}
    		}
    	}
    	
    	foreach (array('amountDeal', 'debitAmount', 'creditAmount') as $fld){
    		if($fld == 'amountDeal'){
    			$data->rec->$fld /= $data->rec->currencyRate;
    		}
    		$data->row->$fld = $this->getFieldType('amountDeal')->toVerbal($data->rec->$fld);
    		if($data->rec->$fld == 0){
    			$data->row->$fld = "<span class='quiet'>{$data->row->$fld}</span>";
    		} elseif($data->rec->$fld < 0){
    			$data->row->$fld = "<span class='red'>{$data->row->$fld}</span>";
    		}
    	}
    }
    
    
    /**
     * Вербално представяне на ред от историята
     */
    private function getHistoryRow($jRec)
    {
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	$row = new stdClass();
    	$row->valior = dt::mysql2verbal($jRec->valior, 'd.m.Y');
    	
    	try{
    		$DocType = cls::get($jRec->docType);
    		$row->docId = $DocType->getHyperLink($jRec->docId, TRUE);
    	} catch(core_exception_Expect $e){
    		$row->docId = "<span style='color:red'>" . tr('Проблем при показването') . "</span>";
    	}
    	
    	if($jRec->debitA){
    		$row->debitA = $Double->toVerbal($jRec->debitA);
    	}
    	
    	if($jRec->creditA){
    		$row->creditA = $Double->toVerbal($jRec->creditA);
    	}
    	
    	return $row;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	$fieldSet = new core_FieldSet();
    	$fieldSet->FLD('docId', 'varchar', 'tdClass=large-field wrap');
    	$fieldSet->FLD('debitA', 'double', 'tdClass=amount-field');
    	$fieldSet->FLD('creditA', 'double', 'tdClass=amount-field');
    	$table = cls::get('core_TableView', array('mvc' => $fieldSet, 'class' => 'styled-table'));
    	$table->tableClass = 'listTable';
    	$fields = "valior=Вальор,docId=Документ,debitA=Сума ({$data->row->currencyId})->Дебит,creditA=Сума ({$data->row->currencyId})->Кредит";
    	$tpl->append($table->get($data->history, $fields), 'DETAILS');
    	
    	if($data->pager){
    		$tpl->replace($data->pager->getHtml(), 'PAGER');
    	}
    }
    
    
    /**
     * Филтър на продажбите
     */
    protected static function on_AfterPrepareListFilter(core_Mvc $mvc, &$data)
    {
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активиран, closed=Приключен', TRUE));
    		$data->listFilter->setField('state', 'placeholder=Всички');
    		$data->listFilter->showFields .= ',state';
    		 
    		$data->listFilter->input();
    		
    		if($state = $data->listFilter->rec->state){
    			$data->query->where("#state = '{$state}'");
    		}
    	}
    	
    	$data->query->where("#dealManId = {$mvc->getClassId()}");
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * 
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
    /**
     * @param int $id key(mvc=findeals_Deals)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    
    	$row = (object)array(
    			'title'    => $this->singleTitle . " №{$rec->id}",
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $this->singleTitle . " №{$rec->id}",
    	);
    
    	return $row;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$result)
    {
    	$rec = self::fetchRec($id);
    	
    	$result->set('dealType', self::AGGREGATOR_TYPE);
    	
    	$result->set('allowedPaymentOperations', $this->getPaymentOperations($id));
    	$result->set('allowedShipmentOperations', $this->getShipmentOperations($id));
    	
    	$involvedContragents = array((object)array('classId' => $rec->contragentClassId, 'id' => $rec->contragentId));
    	if($rec->secondContragentClassId){
    		$involvedContragents[] = (object)array('classId' => $rec->secondContragentClassId, 'id' => $rec->secondContragentId);
    	}
    	$result->set('involvedContragents', $involvedContragents);
    	
    	// Обновяваме крайното салдо на сметката на сделката
    	$entries = acc_Journal::getEntries(array($this->className, $rec->id));
    	$blAmount = acc_Balances::getBlAmounts($entries, acc_Accounts::fetchField($rec->accountId, 'systemId'))->amount;
    	$paid = acc_Balances::getBlAmounts($entries, '501,503')->amount;
    	
    	$result->set('amount', 0);
    	$result->set('amountPaid', $paid);
    	$result->set('blAmount', $blAmount);
    	$result->set('agreedValior', $rec->createdOn);
    	$result->set('currency', $rec->currencyId);
    	$result->set('rate', $rec->currencyRate);
    	$result->set('contoActions', FALSE);
    	
    	//@TODO Временно, докато се премахне от фактурите
    	$result->setIfNot('vatType', 'yes');
    }
    
    
    /**
     * Връща позволените операции за последващите документи
     */
    private function getAllowedOperations($rec, &$paymentOperations, &$shipmentOperations)
    {
    	expect(count($this->allowedPaymentOperations));
    	expect(count($this->allowedShipmentOperations));
    	$sysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
    	
    	$paymentOperations = $this->allowedPaymentOperations;
    	$shipmentOperations = $this->allowedShipmentOperations;
    	
    	foreach (array('paymentOperations', 'shipmentOperations') as $opVar){
    		// На местата с '*' добавяме сметката на сделката
    		foreach (${$opVar} as $index => &$op){
    			if($op['debit'] == '*'){
    				$op['debit'] = $sysId;
    			}
    			if($op['credit'] == '*'){
    				$op['credit'] = $sysId;
    			}
    		}
    	}
    	
    	$paymentOperations['debitDeals'] = array('title' => 'Приход по финансова сделка', 'debit' => '*', 'credit' => $sysId);
    	$paymentOperations['creditDeals'] = array('title' => 'Разход по финансова сделка', 'debit' => $sysId, 'credit' => '*');
    }
    
    
    /**
     * Кои са позволените платежни операции за тази сделка
     */
    public function getPaymentOperations($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$this->getAllowedOperations($rec, $paymentOperations, $shipmentOperations);
    	
    	return $paymentOperations;
    }
    
    
    /**
     * Кои са позволените операции за експедиране
     */
    public function getShipmentOperations($id)
    {
    	$rec = $this->fetchRec($id);
    	 
    	$this->getAllowedOperations($rec, $paymentOperations, $shipmentOperations);
    	 
    	return $shipmentOperations;
    }
    
    
    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    static function getItemRec($objectId)
    {
    	$result = NULL;
    	$self = cls::get(__CLASS__);
    
    	if ($rec = self::fetch($objectId)) {
    		$contragentName = cls::get($rec->contragentClassId)->getTitleById($rec->contragentId, FALSE);
    		$result = (object)array(
    				'num' => $objectId . " " . mb_strtolower($self->abbr),
    				'title' => static::getRecTitle($objectId),
    				'features' => array('Контрагент' => $contragentName)
    		);
    	}
    
    	return $result;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
	    return static::recToVerbal($rec, 'detailedName')->detailedName;
    }
    	
    	
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
    	$self = cls::get(__CLASS__);
    	$self->recTitleTpl = NULL;
    	 
    	if (self::fetch($objectId)) {
    		$detailedName = "<span style='color:red'>" . tr('Нямате права') . "</span>";
    		if ($self->haveRightFor('single', $objectId)) {
    			$detailedName = ht::createLink(tr('Връзка'), array($self, 'single', $objectId));
    		}
    		
    		$result = $detailedName;
    	} else {
    		$result = "<span style='color:red'>" . tr('Проблем с показването') . "</span>";
    	}
    	
    	return $result;
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Подрежда по state, за да могат затворените да са отзад
     */
    public static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
    	$data->query->orderBy('#state');
    }
    
    
    /**
     * Връща опции на всички сделки в които са замесени посочените контрагенти
     * 
     * @param array $involvedContragents - масив от обекти с 'classId' и 'id'
     */
    public static function fetchDealOptions($involvedContragents)
    {
    	$where = "#state = 'active' && (";
    	foreach ($involvedContragents as $i => $contragent){
    		if($i) $where .= " OR ";
    		$where .= "((#contragentClassId = '{$contragent->classId}' && #contragentId = '{$contragent->id}') || (#secondContragentClassId IS NOT NULL && #secondContragentClassId = '{$contragent->classId}' && #secondContragentId = '{$contragent->id}'))";
    	}
    	$where .= ")";
    	
    	$options = array();
    	$query = self::getQuery();
    	while($rec = $query->fetch($where)){
    		$handle = self::getHandle($rec->id);
    		$options[$handle] = $handle;
    	}
    	
    	return $options;
    }
    
    
    /**
     * След промяна в журнала със свързаното перо
     */
    public static function on_AfterJournalItemAffect($mvc, $rec, $item)
    {
    	$aggregateDealInfo = $mvc->getAggregateDealInfo($rec->id);
    	$rec->amountDeal = $aggregateDealInfo->get('blAmount');
    	
    	$mvc->save($rec);
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    	// Попълва информация за мениджъра от който е направен записа
    	if($mvc->count()){
    		$sysId = findeals_AdvanceReports::$baseAccountSysId;
    		$exceptId = acc_Accounts::getRecBySystemId($sysId)->id;
    		
    		$query = $mvc->getQuery();
    		while($rec = $query->fetch()){
    			if(empty($rec->dealManId)){
    				if($rec->accountId == $exceptId){
    					$rec->dealManId = findeals_AdvanceDeals::getClassId();
    				} else {
    					$rec->dealManId = findeals_Deals::getClassId();
    				}
    				
    				$mvc->save($rec);
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след възстановяване на документа
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
    	// След възстановяване се предизвиква събитие в модела
    	$mvc->invoke('AfterActivation', array($id));
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	if($rec->state != 'draft'){
    		
    		$rec = $mvc->fetchRec($id);
    
    		// Записване на продажбата като отворена сделка
    		deals_OpenDeals::saveRec($rec, $mvc);
    	}
    }
    
    
    /**
     * Да се показвали бърз бутон за създаване на документа в папка
     */
    public function mustShowButton($folderRec, $userId = NULL)
    {
    	$Cover = doc_Folders::getCover($folderRec->id);
    
    	// Ако папката е на контрагент
    	if($Cover->haveInterface('doc_ContragentDataIntf')){
    		$groupList = $Cover->fetchField($Cover->getInstance()->groupsField);
    		$debitGroupId = crm_Groups::fetchField("#sysId = 'debitors'");
    		$creditGroupId = crm_Groups::fetchField("#sysId = 'creditors'");
    		
    		// и той е в група 'дебитори' или 'кредитори'
    		if(keylist::isIn($debitGroupId, $groupList) || keylist::isIn($creditGroupId, $groupList)){
    
    			return TRUE;
    		}
    	}
    
    	// Ако не е контрагент или не е в група 'дебитори' или 'кредитори' не слагаме бутон
    	return FALSE;
    }
    
    
    /**
     * Кои сделки ще могатд а се приключат с документа
     * 
     * @param findeals_Deals $mvc
     * @param array $res
     * @param object $rec
     */
    public static function on_AfterGetDealsToCloseWith($mvc, &$res, $rec)
    {
    	if(!count($res)) return;
    	
    	// Сделките трябва да са със същата избрана сметка
    	foreach ($res as $id => $title){
    		$accId = $mvc->fetchField($id, 'accountId');
    		if($accId != $rec->accountId){
    			unset($res[$id]);
    		}
    	}
    }
}
