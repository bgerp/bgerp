<?php
/**
 * Клас 'deals_Deals'
 *
 * Мениджър за финансови сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_Deals extends core_Master
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
    public $interfaces = 'acc_RegisterIntf, doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, deals_DealsAccRegIntf, bgerp_DealIntf, bgerp_DealAggregatorIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, deals_Wrapper, plg_Printing, doc_DocumentPlg, plg_Search, doc_plg_BusinessDoc, doc_ActivatePlg';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,deals';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,deals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,deals';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,deals';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,deals';
    
	
	/**
	 * Кой може да променя състоянието
	 */
    public $canChangestate = 'ceo, deals';
    
    
    /**
     * Документа продажба може да бъде само начало на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,dealName,accountId,folderId,state,createdOn';
    

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Финансова сделка';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'deals/tpl/SingleLayoutDeals.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'dealName';
    
    
    /**
     * Брой детайли на страница
     */
    public $listDetailsPerPage = 20;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'dealName, accountId, description';
    
    
    /**
     * Позволени операции на последващите платежни документи
     */
    public $allowedPaymentOperations = array(
    		'shortTermLoansBankCredit'       => array('title' => 'Краткосрочен банков кредит', 'debit' => '503', 'credit' => '1511'),
    		'shortTermLoansBankOverdraft'    => array('title' => 'Kраткосрочни заеми - овърдрафт', 'debit' => '503', 'credit' => '1513'),
    		'shortTermLoansBankPersons'      => array('title' => 'Краткосрочен заем от свързани лица', 'debit' => '503', 'credit' => '1514'),
    		'shortTermLoansCasePersons'      => array('title' => 'Краткосрочен заем от свързани лица', 'debit' => '501', 'credit' => '1514'),
    		'longTermLoansBankCredit'        => array('title' => 'Дългосрочен банков кредит', 'debit' => '503', 'credit' => '1521'),
    		'longTermLoansOverdraft'         => array('title' => 'Дългосрочни заеми - овърдрафт', 'debit' => '503', 'credit' => '1523'),
    		'longTermLoansBankPersons'       => array('title' => 'Дългосрочен заем от свързани лица', 'debit' => '503', 'credit' => '1524'),
    		'longTermLoansCasePersons'       => array('title' => 'Дългосрочен заем от свързани лица', 'debit' => '501', 'credit' => '1524'),
    		'paidShortTermLoanBankCredit'    => array('title' => 'Платена главница по краткосрочен банков кредит', 'debit' => '1511', 'credit' => '503'),
    		'paidShortTermLoanBankOverdraft' => array('title' => 'Погасена главница по краткосрочен овърдрафт', 'debit' => '1513', 'credit' => '503'),
    		'paidShortTermLoansBankPersons'  => array('title' => 'Платена главница по краткосрочен заем от свързани лица', 'debit' => '1514', 'credit' => '503'),
    		'paidShortTermLoansCasePersons'  => array('title' => 'Платена главница по краткосрочен заем от свързани лица', 'debit' => '1514', 'credit' => '501'),
    		'paidLongTermLoanBankCredit'     => array('title' => 'Платена главница по дългосрочен банков кредит', 'debit' => '1521', 'credit' => '503'),
    		'longTermLoansBankOverdraft'     => array('title' => 'Погасена главница по дългосрочен овърдрафт', 'debit' => '1523', 'credit' => '503'),
    		'longTermLoansBankPersons'       => array('title' => 'Получени дългосрочни заеми - от свързани лица', 'debit' => '1524', 'credit' => '503'),
    		'longTermLoansCasePersons'       => array('title' => 'Платена главница по дългосрочен заем от свързани лица', 'debit' => '1524', 'credit' => '501'),
    		'creditFactoring'                => array('title' => 'Прихващане на Задължения', 'debit' => '401', 'credit' => '406'),
    		'debitFactoring'                 => array('title' => 'Прихващане на Вземания', 'debit' => '406', 'credit' => '411'),
    		'clientFactoring'                => array('title' => 'Факторинг - отписване на Вземане', 'debit' => '414', 'credit' => '411'),
    		'caseFactoring'                  => array('title' => 'Плащане по Факторинг', 'debit' => '501', 'credit' => '414'),
    		'bankFactoring'                  => array('title' => 'Плащане по Факторинг', 'debit' => '503', 'credit' => '414'),
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('dealName', 'varchar(255)', 'caption=Наименование,mandatory,width=100%');
    	$this->FLD('accountId', 'acc_type_Account(regInterfaces=deals_DealsAccRegIntf, allowEmpty)', 'caption=Сметка,mandatory');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент');
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
    	$this->FLD('contragentId', 'int', 'input=hidden');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Валута->Код');
    	$this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,width=4em');
    	$this->FLD('description', 'richtext(rows=4)', 'caption=Допълнителno->Описание');
    	
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Приключен)','caption=Статус, input=none');
    	
    	$this->setDbUnique('dealName');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	
    	$form->setDefault('contragentClassId', $coverClass::getClassId());
    	$form->setDefault('contragentId', $coverId);
    	
    	$form->rec->contragentName = $coverClass::fetchField($coverId, 'name');
    	$form->setReadOnly('contragentName');
    	
    	$form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    	$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
    	if ($form->isSubmitted()){
    		$rec  = &$form->rec;
    		if(!$rec->currencyRate){
    			// Изчисляваме курса към основната валута ако не е дефиниран
    			$rec->currencyRate = round(currency_CurrencyRates::getRate(dt::now(), $rec->currencyId, NULL), 4);
    			
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, dt::now(), $rec->currencyId, NULL)){
    				$form->setWarning('currencyRate', $msg);
    			}
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->accountId = acc_Accounts::getTitleById($rec->accountId);
    	
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$row->id}</b> ({$row->state})";
    		$row->contragentName = cls::get($rec->contragentClassId)->getHyperLink($rec->contragentId, TRUE);
    	}
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	$lastBalance = acc_Balances::getLastBalance();
    	if(acc_Balances::haveRightFor('single', $lastBalance)){
    		$accUrl = array('acc_Balances', 'single', $lastBalance->id, 'accId' => $rec->accountId);
    		$row->accountId = ht::createLink($row->accountId, $accUrl);
    	}
    	
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'AggregateDealInfo', $data->rec->id), 'ef_icon=img/16/bug.png,title=Дебъг,row=2');
    	}
    	
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
    	}
    	
    	if($mvc->haveRightFor('changeState', $rec)){
    		$title = ($rec->state == 'active') ? 'Приключване' : 'Отваряне';
    		$icon = ($rec->state == 'active') ? 'img/16/lock.png' : 'img/16/lock_unlock.png';
    		$data->toolbar->addBtn($title, array($mvc, 'toggleState', $rec->id), "ef_icon={$icon},title={$title} на финансова сделка");
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'changestate' && isset($rec)){
    		if($rec->state != 'active' && $rec->state != 'closed'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$data->masterMvc = cls::get('cash_Cases');
    	$data->masterId = $data->rec->id;
    	
    	$mvc->getHistory($data);
    }
    
    
    /**
     * Връща хронологията от журнала, където участва документа като перо
     */
    private function getHistory(&$data)
    {
    	$rec = $this->fetchRec($data->rec->id);
    	$accSysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
    	$createdOn = dt::mysql2verbal($rec->createdOn, 'Y-m-d');
    	
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	$item = acc_Items::fetchItem($this->getClassId(), $rec->id);
    	$blAmount = 0;
    	
    	// Ако документа е перо
    	if($item){
    		$data->history = array();
    		
    		// Намираме от журнала записите, където участва перото от датата му на създаване до сега
    		$jQuery = acc_JournalDetails::getQuery();
    		acc_JournalDetails::filterQuery($jQuery, $createdOn, dt::today(), $accSysId, $item->id);
    		
    		$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
    		$Pager->itemsCount = $jQuery->count();
    		$Pager->calc();
    		$data->pager = $Pager;
    		
    		// Извличаме всички записи, за да изчислим точно крайното салдо
    		$count = 0;
    		while($jRec = $jQuery->fetch()){
    			$start = $data->pager->rangeStart;
    			$end = $data->pager->rangeEnd - 1;
    			
    			$row = new stdClass();
    			try{
    				$DocType = cls::get($jRec->docType);
    				$row->docId = $DocType->getHyperLink($jRec->docId, TRUE);
    			} catch(Exception $e){
    				$row->docId = "<span style='color:red'>" . tr('Проблем при показването') . "</span>";
    			}
    			
    			$jRec->amount /= $rec->currencyRate;
    			if($jRec->debitItem1 == $item->id){
    				$row->debitA = $Double->toVerbal($jRec->amount);
    				$blAmount += $jRec->amount;
    			} elseif($jRec->creditItem1 == $item->id){
    				$row->creditA = $Double->toVerbal($jRec->amount);
    				$blAmount -= $jRec->amount;
    			}
    		
    			// Ще показваме реда, само ако отговаря на текущата страница
    			if(empty($data->pager) || ($count >= $start && $count <= $end)){
    				$data->history[] = $row;
    			}
    			$count++;
    		}
    	}
    	
    	// Обръщаме във вербален вид изчисленото крайно салдо
    	$data->row->blAmount = $Double->toVerbal($blAmount);
    	if($blAmount < 0){
    		$data->row->blAmount = "<span style='color:red'>{$data->row->blAmount}</span>";
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$fieldSet = new core_FieldSet();
    	$fieldSet->FLD('docId', 'varchar', 'tdClass=large-field');
    	$fieldSet->FLD('debitA', 'double');
    	$fieldSet->FLD('creditA', 'double');
    	$table = cls::get('core_TableView', array('mvc' => $fieldSet, 'class' => 'styled-table'));
    	$table->tableClass = 'listTable';
    	$fields = "docId=Документ,debitA=Сума ({$data->row->currencyId})->Дебит,creditA=Сума ({$data->row->currencyId})->Кредит";
    	$tpl->append($table->get($data->history, $fields), 'DETAILS');
    	
    	if($data->pager){
    		$tpl->replace($data->pager->getHtml(), 'PAGER');
    	}
    }
    
    
    /**
     * Филтър на продажбите
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
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
     * @param int $id key(mvc=deals_Deals)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    	$title = static::getRecTitle($rec);
    
    	$row = (object)array(
    			'title'    => $this->singleTitle . " \"$title\"",
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $title,
    	);
    
    	return $row;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	$rec = self::fetchRec($id);
    
    	$result = new bgerp_iface_DealResponse();
    	
    	$result->dealType = bgerp_iface_DealResponse::TYPE_DEAL;
    	$result->allowedPaymentOperations = $this->getAllowedOperations($rec);
    	
    	$result->paid->currency = $rec->currencyId;
    	$result->paid->rate = $rec->currencyRate;
    	
    	return $result;
    }
    
    
    /**
     * Връща позволените операции за последващите документи
     */
    private function getAllowedOperations($rec)
    {
    	expect(count($this->allowedPaymentOperations));
    	$sysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
    	
    	$operations = $this->allowedPaymentOperations;
    	
    	// От зададените операции премахва онези в които не участва сметката на сделката
    	foreach ($operations as $index => $op){
    		if($op['credit'] != $sysId && $op['debit'] != $sysId){
    			unset($operations[$index]);
    		}
    	}
    	
    	return $operations;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * Генерира агрегираната бизнес информация за тази сделка
     *
     * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
     * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до
     * момента.
     *
     * Списъка с въпросните документи, имащи отношение към бизнес информацията за сделката е
     * сечението на следните множества:
     *
     *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
     *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
     *  * Документите, в състояние различно от `draft` и `rejected`
     *
     * @return bgerp_iface_DealResponse
     */
    public function getAggregateDealInfo($id)
    {
    	$dealRec = self::fetchRec($id);
    	 
    	$dealDocuments = $this->getDescendants($dealRec->id);
    
    	// Извличаме dealInfo от самата сделка
    	/* @var $dealDealInfo bgerp_iface_DealResponse */
    	$dealDealInfo = $this->getDealInfo($dealRec->id);
    
    	// dealInfo-то на самата сделка е база, в/у която се натрупват някой от аспектите
    	// на породените от нея платежни документи
    	$aggregateInfo = clone $dealDealInfo;
    	
    	if(count($dealDocuments)){
    		/* @var $d core_ObjectReference */
    		foreach ($dealDocuments as $d) {
    			$dState = $d->rec('state');
    			
    			// Игнорираме черновите и оттеглените документи
    			if ($dState == 'draft' || $dState == 'rejected') {
                	
    				// Игнорираме черновите и оттеглените документи
                	continue;
            	}
    		
    			if ($d->haveInterface('bgerp_DealIntf')) {
    				$dealInfo = $d->getDealInfo();
    				$aggregateInfo->paid->push($dealInfo->paid);
    			}
    		}
    	}
    	
    	return $aggregateInfo;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$name = static::recToVerbal($rec, 'dealName')->dealName;
    
    	return $name;
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
    		$contragentName = cls::get($rec->contragentClassId)->getTitleById($rec->contragentId);
    		$result = (object)array(
    				'num' => $objectId,
    				'title' => static::getRecTitle($objectId),
    				'features' => array('Контрагент' => $contragentName)
    		);
    	}
    
    	return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
    	$self = cls::get(__CLASS__);
    	$self->recTitleTpl = NULL;
    	 
    	if ($rec = self::fetch($objectId)) {
    		$result = $self->getHyperlink($objectId);
    	} else {
    		$result = '<i>' . tr('неизвестно') . '</i>';
    	}
    
    	return $result;
    }
    
    
    /**
     * Екшън за затваряне на финансова сделка
     */
    public function act_ToggleState()
    {
    	$this->requireRightFor('changeState');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('changeState', $rec);
    	
    	$rec->state = ($rec->state == 'active') ? 'closed' : 'active';
    	$this->save($rec);
    	
    	Redirect(array($this, 'single', $id));
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Дебъг екшън показващ агрегираните бизнес данни
     */
    function act_AggregateDealInfo()
    {
    	requireRole('debug');
    	expect($id = Request::get('id', 'int'));
    	$info = $this->getAggregateDealInfo($id);
    	bp($info->allowedPaymentOperations,$info->paid);
    }
    
    
    /**
     * Поставя изискване да се селектират само активните записи
     */
    function on_BeforeMakeArray4Select($mvc, &$optArr, $fields = NULL, &$where = NULL)
    {
    	$where .= ($where ? " AND " : "") . " #state = 'active'";
    }
    
    
    /**
     * Подрежда по state, за да могат затворените да са отзад
     */
    function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
    	$data->query->orderBy('#state');
    }
}