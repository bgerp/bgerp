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
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_Deals extends deals_DealBase
{
	
	
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
    public $interfaces = 'acc_RegisterIntf, doc_DocumentIntf, deals_DealsAccRegIntf, bgerp_DealIntf, bgerp_DealAggregatorIntf,acc_TransactionSourceIntf=findeals_transaction_Deal';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, acc_plg_Registry, findeals_Wrapper, plg_Printing, doc_DocumentPlg, acc_plg_Contable, acc_plg_DocumentSummary, plg_Search, bgerp_plg_Blank, doc_plg_Close, cond_plg_DefaultValues, plg_Clone, doc_plg_Prototype';
    
    
    /**
     * Кои сметки не могат да се избират
     */
    public static $exceptAccSysIds = '401,411,402,412';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,findeals';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,findeals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,findeals';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,acc';
    
    
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
    public $listFields = 'detailedName,folderId,state,createdOn,createdBy';
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
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
     * Файл с шаблон за единичен изглед
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
    public $searchFields = 'dealName, accountId, description, folderId';
    
    
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
    public  $allowedPaymentOperations = array(
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
     * Сметки с какви интерфейси да се показват за избор
     */
    protected $accountListInterfaces = 'crm_ContragentAccRegIntf,deals_DealsAccRegIntf,currency_CurrenciesAccRegIntf';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('currencyId'   => 'lastDocUser|lastDoc|CoverMethod');
    
    
    /**
     * Документа продажба може да бъде само начало на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'amountDeal';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('valior', 'date', 'caption=Дата');
    	$this->FLD('dealName', 'varchar(255)', 'caption=Наименование');
    	$this->FLD('amountDeal', 'double(decimals=2)', 'input=none,notNull,oldFieldName=blAmount');
    	$this->FLD('accountId', "acc_type_Account", 'caption=Сметка,mandatory,silent');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент');
    	
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Валута->Код,silent,removeAndRefreshForm=currencyRate');
    	$this->FLD('currencyRate', 'double(decimals=5)', 'caption=Валута->Курс');
    	
    	$this->FNC('contragentItemId', 'acc_type_Item(select=titleNum,allowEmpty)', 'caption=Втори контрагент,input');
    	
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
    	$this->FLD('contragentId', 'int', 'input=hidden');
    	
    	$this->FLD('baseAccountId', "acc_type_Account(regInterfaces=none,allowEmpty)", 'silent,caption=Начално салдо->Сметка,input=none,before=description');
    	$this->FLD('baseAmount', "double(decimals=2, Min=0)", 'caption=Начално салдо->Сума,input=none,before=description');
    	$this->FLD('baseAmountType', "enum(debit=Дебит,credit=Кредит)", 'caption=Начално салдо->Тип,input=none,before=description');
    	
    	$this->FLD('secondContragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=none');
    	$this->FLD('secondContragentId', 'int', 'input=none');
    	
    	$this->FLD('description', 'richtext(rows=4)', 'caption=Допълнително->Описание,after=currencyRate');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Приключен,stopped=Спряно,template=Шаблон)','caption=Състояние, input=none');
    	
    	$this->FNC('detailedName', 'varchar', 'column=none,caption=Име');
    	$this->FLD('dealManId', 'class(interface=deals_DealsAccRegIntf)', 'input=none');
    	
    	// Индекс
    	$this->setDbIndex('dealManId');
    }
    
    
    /**
     * Метод за генериране на чернова на финансова сделка
     * 
     * @param mixed $contragentClassId
     * @param int $contragentId
     * @param int $accountId
     * @param array $fields
     * 			['valior']           - вальор, ако няма е текущата дата
     * 			['dealName']         - име на сделката (незадължително)
     * 			['description']      - описание (незадължително)
     * 			['currencyCode']     - код на валута, ако няма е основната за периода
     * 			['currencyRate']     - курса от валутата към основната валута за периода
     * 			['baseAccountSysId'] - систем ид на сметката от която ще се прехвърля салдото
     * 			['baseAmount']       - сума в основна валута, която ще стане салдото на финансовата сделка
     * 			['baseAmountType']   - дали салдото да е дебитно или кредитно
     * 
     * @return int|FALSE $id
     */
    public static function createDraft($contragentClassId, $contragentId, $accountId, $fields = array())
    {
    	$me = cls::get(get_called_class());
    	
    	// Проверки
    	$contragentClass = cls::get($contragentClassId);
    	expect($cRec = $contragentClass->fetch($contragentId));
    	expect($cRec->state != 'rejected');
    	expect($accRec = acc_Accounts::getRecBySystemId($accountId), 'Невалидна сметка');
    	if($me instanceof findeals_AdvanceDeals){
    		expect($contragentClass instanceof crm_Persons, "Служебен аванс може да е само в папка на лице");
    	}
    	
    	$options = acc_Accounts::getOptionsByListInterfaces($me->accountListInterfaces);
    	expect(array_key_exists($accRec->id, $options), "{$accountId} разбивките нямат нужните интерфейси {$me->accountListInterfaces}");
    	
    	$Double = cls::get('type_Double');
    	
    	// Кои полета ще се записват
    	$fields = arr::make($fields);
    	$newFields = array();
    	$newFields['contragentClassId'] = $contragentClass->getClassId();
    	$newFields['contragentId'] = $contragentId;
    	$newFields['accountId'] = $accRec->id;
    	$newFields['dealManId'] = $me->getClassId();
    	$newFields['folderId'] = $contragentClass->forceCoverAndFolder($contragentId);
    	$newFields['contragentName'] = $contragentClass::fetchField($contragentId, 'name');
    	
    	// Записваме данните на контрагента
    	$newFields['valior'] = (isset($fields['valior'])) ? dt::verbal2mysql($fields['valior'], FALSE) : dt::today();
    	
    	// Девербализация на името, ако има
    	if(!empty($fields['dealName'])){
    		$Varchar = cls::get('type_Varchar');
    		$newFields['dealName'] = $Varchar->fromVerbal($fields['dealName']);
    	}
    	
    	// Девербализация на описанието, ако има
    	if(!empty($fields['description'])){
    		$Richtext = cls::get('type_Richtext');
    		$newFields['description'] = $Richtext->fromVerbal($fields['description']);
    	}
    	
    	$newFields['currencyId'] = (empty($fields['currencyCode'])) ? acc_Periods::getBaseCurrencyCode($fields['valior']) : $fields['currencyCode'];
    	$newFields['currencyRate'] = (empty($fields['currencyRate'])) ? currency_CurrencyRates::getRate($newFields['valior'], $newFields['currencyId'], NULL) : $fields['currencyRate'];
    	
    	expect(currency_Currencies::getIdByCode($newFields['currencyId']), 'Невалидна валута');
    	expect($Double->fromVerbal($newFields['currencyRate']), 'Невалиден курс');
    	
    	if(isset($fields['baseAccountSysId'])){
    		expect($accRec = acc_Accounts::getRecBySystemId($fields['baseAccountSysId']), 'Невалидна сметка');
    		expect(is_null($accRec->groupId1) && is_null($accRec->groupId2) && is_null($accRec->groupId1), 'Сметката трябва да няма разбивки');
    		$newFields['baseAccountId'] = $accRec->id;
    	}
    	
    	if(isset($fields['baseAmount'])){
    		$newFields['baseAmount'] = $Double->fromVerbal($fields['baseAmount']);
    	}
    	
    	if(isset($fields['baseAmountType'])){
    		$newFields['baseAmountType'] = $fields['baseAmountType'];
    		expect(in_array($newFields['baseAmountType'], array('debit', 'credit')));
    	}
    	
    	if(isset($fields['baseAccountSysId']) || $fields['baseAmount'] || $fields['baseAmountType']){
    		expect(isset($fields['baseAccountSysId']) && isset($fields['baseAmount']) && isset($fields['baseAmountType']));
    	}
    	
    	// Опиваме се да запишем мастъра на сделката
    	if($id = $me->save((object)$newFields)){
    		
    		// Ако е успешно, споделяме текущия потребител към новосъздадената нишка
    		$rec = $me->fetch($id);
    		doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, core_Users::getCurrent());
    
    		return $id;
    	}
    	 
    	return FALSE;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	$options = acc_Accounts::getOptionsByListInterfaces($mvc->accountListInterfaces);
    	
    	// Премахваме от избора упоменатите сметки, които трябва да се изключат
    	$except = arr::make(static::$exceptAccSysIds);
    	foreach ($except as $sysId){
    		$accId = acc_Accounts::getRecBySystemId($sysId)->id;
    		unset($options[$accId]);
    	}
    	
    	$form->setOptions('accountId', $options);
    	
    	if(count($options) == 2){
    		$form->setField('accountId', 'input=hidden');
    		foreach ($options as $key => $opt){
    			if(!is_object($opt)){
    				$form->setDefault('accountId', $key);
    			}
    		}
    	}
    	
    	// Само контрагенти могат да се избират
    	$contragentListNum = acc_Lists::fetchBySystemId('contractors')->num;
    	$form->setFieldTypeParams('contragentItemId', array('lists' => $contragentListNum));
    	
    	$form->setField('baseAmount', "unit={$rec->currencyId}");
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     * Документи-финансови сделки могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return cls::haveInterface('crm_ContragentAccRegIntf', $coverClass);
    }
    
    
    /**
     * Име за избор
     */
    protected static function on_CalcDetailedName($mvc, &$rec) 
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
    	
    	// Само определени потребители може да задават начално салдо
    	if($this->haveRightFor('conto')){
    		$form->setField('baseAccountId', 'input');
    		$form->setField('baseAmount', 'input');
    		$form->setField('baseAmountType', 'input');
    		
    		// Ако е записано в сесията сметка за начално салдо, попълва се
    		$form->setDefault('baseAccountId', Mode::get('findealCorrespondingAccId'));
    	}
    	
    	return $data;
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec  = &$form->rec;
    	
    	if ($form->isSubmitted()){
    		if(isset($rec->contragentItemId)){
    			$item = acc_Items::fetch($rec->contragentItemId);
    			$rec->secondContragentClassId = $item->classId;
    			$rec->secondContragentId = $item->objectId;
    		} else {
    			$rec->secondContragentClassId = NULL;
    			$rec->secondContragentId = NULL;
    		}
    		
    		if(!$rec->currencyRate){
    			
    			// Изчисляваме курса към основната валута ако не е дефиниран
    			$rec->currencyRate = currency_CurrencyRates::getRate(dt::now(), $rec->currencyId, NULL);
    			if(!$rec->currencyRate){
    				$form->setError('rate', "Не може да се изчисли курс");
    			}
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, dt::now(), $rec->currencyId, NULL)){
    				$form->setWarning('currencyRate', $msg);
    			}
    		}
    		
    		$rec->dealManId = $mvc->getClassId();
    		
    		// Проверки на данните
    		if(isset($rec->baseAccountId)){
    			if(!isset($rec->baseAmount)){
    				$form->setError('baseAmount', 'Трябва да е зададено начално салдо, ако е избрана сметка');
    			}
    		}
    		
    		// Проверка имали зададен вальор, ако ще задаваме начално салдо
    		if((isset($rec->baseAccountId) || isset($rec->baseAmount)) && !isset($rec->valior)){
    			$form->setError('valior', 'При прехвърляне на салда, трябва да има дата на сделката');
    		}
    		
    		if(isset($rec->baseAmount) && !isset($rec->baseAccountId)){
    			$form->setError('baseAccountId', 'Зададено е начално салдо, без да е избрана кореспондираща сметка');
    		}
    		
    		if(empty($rec->baseAccountId)){
    			$rec->baseAmountType = NULL;
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$row->contragentName = cls::get($rec->contragentClassId)->getHyperLink($rec->contragentId, TRUE);
    		
    		if($rec->secondContragentClassId){
    			$row->secondContragentId = cls::get($rec->secondContragentClassId)->getHyperLink($rec->secondContragentId, TRUE);
    		}

    		$row->accountId = acc_Balances::getAccountLink($rec->accountId, NULL, TRUE, TRUE);
    		if(empty($row->contragentCaption)){
    			$row->contragentCaption = tr('Контрагент');
    		}
    		
    		if($rec->currencyRate == 1){
    			unset($row->currencyRate);
    		}
    	}
    	
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	
    	if(isset($rec->baseAccountId)){
    		$row->bCurrencyId = $row->currencyId;
    		$row->baseAccountId = acc_Balances::getAccountLink($rec->baseAccountId, NULL, TRUE, TRUE);
    	} else {
    		unset($row->baseAccountId);
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
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
    			$data->toolbar->addBtn("Отчет", array('findeals_AdvanceReports', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/legend.png,title=Създаване на нов авансов отчет');
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
    	if($action == 'closewith' && isset($rec)){
    		
    		// Ако сделката има начално салдо тя не може да приключва други сделки
    		if(isset($rec->baseAccountId)){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'clonerec' && isset($rec)){
    		if(isset($rec->baseAccountId) && !$mvc->haveRightFor('conto')){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
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
    			if($jRec->debitItem2 == $item->id && $jRec->debitAccId == $rec->accountId){
    				$r->debitA += $jRec->amount;
    			}
    			
    			if($jRec->creditItem2 == $item->id && $jRec->creditAccId == $rec->accountId){
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
    	$row->ROW_ATTR['class'] = 'state-active';
    	
    	try{
    		$DocType = cls::get($jRec->docType);
    		$row->docId = $DocType->getHyperLink($jRec->docId, TRUE);
    	} catch(core_exception_Expect $e){
    		$row->docId = "<span style='color:red'>" . tr('Проблем при показването') . "</span>";
    	}
    	
    	if($jRec->debitA){
    		$row->debitA = $Double->toVerbal($jRec->debitA);
    		if($jRec->debitA < 0){
    			$row->debitA = "<span class='red'>{$row->debitA}</span>";
    		}
    	}
    	
    	if($jRec->creditA){
    		$row->creditA = $Double->toVerbal($jRec->creditA);
    		if($jRec->creditA < 0){
    			$row->creditA = "<span class='red'>{$row->creditA}</span>";
    		}
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
    	$tpl->append($table->get($data->history, $fields), 'HISTORY');
    	
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
     * Изпълнява се след възстановяване на документа
     */
    protected static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
    	// След възстановяване се предизвиква събитие в модела
    	$mvc->invoke('AfterActivation', array($id));
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
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    protected static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	
    	// Кои са документите в нишката
    	$descendents = $mvc->getDescendants($rec->id);
    	$documentsInClosedPeriod = array();
    	
    	// Ако има наследници
    	if(is_array($descendents)){
    		foreach ($descendents as $desc){
    			
    			// Които са контиращи документи
    			if($desc->haveInterface('acc_TransactionSourceIntf') && $desc->fetchField('state') == 'active'){
    				$date = $desc->getValiorValue();
    				
    				// И вальора им е в приключен период
    				if(acc_Periods::isClosed($date)){
    					$handle = $desc->getHandle();
    					
    					// Запомняме ги
    					$documentsInClosedPeriod[$handle] = "#" . $handle;
    				}
    			}
    		}
    	}
    	
    	// Ако са намерени документи в нишката контирани в приключен счетоводен период, 
    	// спираме оттеглянето и показваме съобщение за грешка
    	if(count($documentsInClosedPeriod)){
    		$msg = "Финансовата сделка не може да бъде оттеглена, защото ";
    		$msg .= (count($documentsInClosedPeriod) == 1) ? 'документа' : 'следните документи';
    		$msg .= "|* " . implode(', ', $documentsInClosedPeriod);
    		$are = (count($documentsInClosedPeriod) == 1) ? 'е контиран' : 'са контирани';
    		$msg .= " |в нишката {$are} в затворен счетоводен период|*";
    		$msg = tr($msg);
    		
    		core_Statuses::newStatus($msg, 'error');
    		
    		// Връщаме FALSE за да се стопира оттеглянето на документа
    		return FALSE;
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Ако има избрана сметка за начално салдо, записва се в сесията
    	if(isset($rec->baseAccountId)){
    		Mode::setPermanent('findealCorrespondingAccId', $rec->baseAccountId);
    	}
    }
    
    
    /**
     * След като се поготви заявката за модела
     */
    protected static function on_AfterGetQuery($mvc, $query)
    {
    	$query->where("#dealManId = {$mvc->getClassId()}");
    }
}
