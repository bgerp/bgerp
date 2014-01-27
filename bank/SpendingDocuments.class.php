<?php 


/**
 * Разходен банков документ
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_SpendingDocuments extends core_Master
{
    
    
	/**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'bank_CostDocument';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf, bgerp_DealIntf, email_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Разходни банкови документи";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper, plg_Printing,
     	plg_Sorting, doc_plg_BusinessDoc2,doc_DocumentPlg, acc_plg_DocumentSummary,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable, cond_plg_DefaultValues, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, valior, reason, folderId, currencyId, amount, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Разходен банков документ';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/bank_rem.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Rbd";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'bank, ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'bank, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'bank, ceo';
    
    
    /**
     * Кой може да сторнира
     */
    var $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleCostDocument.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, contragentName';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.4|Финанси";

    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'operationSysId' => 'lastDocUser|lastDoc',
    	'currencyId'     => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'customKey(mvc=acc_Operations,key=systemId, select=name)', 'caption=Операция,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=6em,summary=amount');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('rate', 'double', 'caption=Курс,width=6em');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=bankAccountId)', 'caption=От->Б. сметка,mandatory,width=16em');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Към->Контрагент,mandatory,width=16em');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('debitAccId', 'acc_type_Account()','caption=debit,width=300px,input=none');
        $this->FLD('creditAccId', 'acc_type_Account()','caption=Кредит,width=300px,input=none');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		bank_OwnAccounts::prepareBankFilter($data, array('ownAccount'));
	}
	
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$today = dt::verbal2mysql();
    	
    	$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        $contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
    	$form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
    	
        $options = acc_Operations::getPossibleOperations(get_called_class());
        $options = acc_Operations::filter($options, $contragentClassId);
        
    	if(empty($form->rec->id) && $origin = $mvc->getOrigin($form->rec)) {
    		 $mvc->setDefaultsFromOrigin($origin, $form, $options);
    	}
    	
        $form->setDefault('valior', $today);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
    	$form->setDefault('ownAccount', bank_OwnAccounts::getCurrent());
    	$form->setOptions('operationSysId', $options);
    	$form->setReadOnly('contragentName', cls::get($contragentClassId)->getTitleById($contragentId));
        $form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
    }
	
	
	/**
     * Задава стойности по подразбиране от продажба/покупка
     * @param core_ObjectReference $origin - ориджин на документа
     * @param core_Form $form - формата
     * @param array $options - масив с сч. операции
     */
    private function setDefaultsFromOrigin(core_ObjectReference $origin, core_Form &$form, $options)
    {
    	$form->setDefault('reason', "Към документ #{$origin->getHandle()}");
        if($origin->haveInterface('bgerp_DealAggregatorIntf')){
    		 $dealInfo = $origin->getAggregateDealInfo();
    		 $amount = ($dealInfo->shipped->amount - $dealInfo->paid->amount) / $dealInfo->shipped->rate;
    		 $amount = ($amount <= 0) ? 0 : $amount;
    		 	
    		 // Ако има банкова сметка по пдоразбиране
    		 if($bankId = $dealInfo->agreed->payment->bankAccountId){
    		 	$bankRec = bank_OwnAccounts::fetch($bankId);
    		 		
    		 	// Ако потребителя е оператор на сметката, но не е логнат форсира се логването му в нея
				if($bankId != bank_OwnAccounts::getCurrent('id', FALSE) && bank_OwnAccounts::haveRightFor('select', $bankRec)){
    		 		Redirect(array('Ctr' => 'bank_OwnAccounts', 'Act' => 'SetCurrent', 'id' => $bankId, 'ret_url' => TRUE));
    		 	}
    		 }
    		 	
    		 // Ако операциите на документа не са позволени от интерфейса, те се махат
    		 foreach ($options as $index => $op){
    		 	if(!in_array($index, $dealInfo->allowedPaymentOperations)){
    		 		unset($options[$index]);
    		 	}
    		 }
    		 	
    		 $form->rec->currencyId = currency_Currencies::getIdByCode($dealInfo->shipped->currency);
    		 $form->rec->rate       = $dealInfo->shipped->rate;
    		 $form->rec->amount     = currency_Currencies::round($amount, $dealInfo->shipped->currency);
    	}
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
	        // Коя е дебитната и кредитната сметка
	        $operation = acc_Operations::fetchBySysId($rec->operationSysId);
    		$rec->debitAccId = $operation->debitAccount;
    		$rec->creditAccId = $operation->creditAccount;
    		
    		// Проверяваме дали банковата сметка е в същата валута
    		$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);	
	   	 	if($ownAcc->currencyId != $rec->currencyId) {
	   	 		$form->setError('currencyId', 'Банковата сметка е в друга валута');
	   	 	}
	   	 	
	   	 	// Ако няма валутен курс, взимаме този от системата
    		if(!$rec->rate && !$form->gotErrors()) {
	    		$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
	    		$rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, acc_Periods::getBaseCurrencyCode($rec->valior));
	    	}
    	}
    }
    
    
	/**
     * Преди подготовка на вербалното представяне
     */
    static function on_BeforeRecToVerbal($mvc, $row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$mvc->fields['rate']->type->params['decimals'] = strlen(substr(strrchr($rec->rate, "."), 1));
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if($fields['-single']) {
    		
    		$row->currencyId = currency_Currencies::getCodeById($rec->currencyId);
    		
    		if($rec->rate != '1') {
    			
			    $period = acc_Periods::fetchByDate($rec->valior);
			    $row->baseCurrency = currency_Currencies::getCodeById($period->baseCurrencyId);
    		 	$double = cls::get('type_Double');
	    		$double->params['decimals'] = 2;
	    		$row->equals = $double->toVerbal($rec->amount * $rec->rate);
    		} else {
    			
    			unset($row->rate);
    		}
    		
    		$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);	
    		$row->accCurrency = currency_Currencies::getCodeById($ownAcc->currencyId);
    	
	    	// Показваме заглавието само ако не сме в режим принтиране
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
    	}
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова".
     */
	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state == 'draft') {
	    	$operation = acc_Operations::fetchBySysId($data->rec->operationSysId);
	    	
	    	// Ако дебитната сметка е за работа с контрагент слагаме бутон за
	    	// платежно нареждане ако е подочетно лице генерираме нареждане разписка
	    	if(bank_PaymentOrders::haveRightFor('add') && acc_Lists::getPosition($operation->debitAccount, 'crm_ContragentAccRegIntf')) {
	    		$data->toolbar->addBtn('Платежно нареждане', array('bank_PaymentOrders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png,title=Създаване на ново платежно нареждане');
	    	} elseif(bank_CashWithdrawOrders::haveRightFor('add') && acc_Lists::getPosition($operation->debitAccount, 'crm_PersonAccRegIntf')) {
	    		$data->toolbar->addBtn('Нареждане разписка', array('bank_CashWithdrawOrders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png,title=Създаване на ново нареждане разписка');
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
        $cover = doc_Folders::getCover($folderId);
        
        // Можем да добавяме или ако корицата е контрагент или сме в папката на текущата сметка
        return $cover->haveInterface('doc_ContragentDataIntf') || 
            ($cover->className == 'bank_OwnAccounts' && 
             $cover->that == bank_OwnAccounts::getCurrent('id', FALSE) );
    }
    
    
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'closed';
        
    	if ($this->save($rec)) {
            // Нотифицираме origin-документа, че някой от веригата му се е променил
            if ($origin = $this->getOrigin($rec)) {
                $ref = new core_ObjectReference($this, $rec);
                $origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
            }
        }
    }
    
    
    /**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	// Извличаме записа
        expect($rec = self::fetchRec($id));
        
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => array(
                array(
                    'amount' => $rec->amount * $rec->rate,
                    
                    'debit' => array(
                        $rec->debitAccId,
                            array($rec->contragentClassId, $rec->contragentId),
                            array('currency_Currencies', $rec->currencyId),
                        'quantity' => $rec->amount,
                    ),
                    
                    'credit' => array(
                        $rec->creditAccId,
                            array('bank_OwnAccounts', $rec->ownAccount),
                        'quantity' => $rec->amount,
                    ),
                ),
            )
        );
        
    	// Ако дебитната сметка не поддържа втора номенклатура, премахваме
        // от масива второто перо на кредитната сметка
    	$dAcc = acc_Accounts::getRecBySystemId($rec->debitAccId);
        if(!$dAcc->groupId2){
        	unset($result->entries[0]['debit'][2]);
        }
        
        return $result;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->reason;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->reason;
		
        return $row;
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
    	$threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	
    	$res = cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    	if($res){
    		if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState != 'active')){
    			$res = FALSE;
    		}
    	}
		
    	return $res;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
	/**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
        $rec = self::fetchRec($id);
    
        /* @var $result bgerp_iface_DealResponse */
        $result = new bgerp_iface_DealResponse();
    
        // При продажба платеното се намалява, ако е покупка се увеличава
        $origin = static::getOrigin($rec);
        $sign = ($origin->className == 'purchase_Purchases') ? 1 : -1;
        
    	$result->paid->amount                 = $sign * $rec->amount * $rec->rate;
        $result->paid->currency               = currency_Currencies::getCodeById($rec->currencyId);
        $result->paid->rate 	              = $rec->rate;
        $result->paid->payment->bankAccountId = $rec->ownAccount;
        
    	
        return $result;
    }
    
    
	/**
     * Информация за платежен документ
     * 
     * @param int|stdClass $id ключ (int) или запис (stdClass) на модел 
     * @return stdClass Обект със следните полета:
     *
     *   o amount       - обща сума на платежния документ във валутата, зададена от `currencyCode`
     *   o currencyCode - key(mvc=currency_Currencies, key=code): ISO код на валутата
     *   o currencyRate - double - валутен курс към основната (към датата на док.) валута
     *   o valior       - date - вальор на документа
     */
    public static function getPaymentInfo($id)
    {
        $rec = self::fetchRec($id);
        
        return (object)array(
            'amount'       => -$rec->amount,
            'currencyCode' => currency_Currencies::getCodeById($rec->currencyId),
        	'currencyRate' => $rec->rate,
            'valior'       => $rec->valior,
        );
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия разходен банков документ") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
}
