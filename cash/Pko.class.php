<?php



/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Pko extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf, sales_PaymentIntf, bgerp_DealIntf, email_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Приходни касови ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper, plg_Sorting, doc_plg_BusinessDoc2, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, doc_SequencerPlg,acc_plg_DocumentSummary,
                     plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank,
                     bgerp_DealIntf, doc_EmailCreatePlg, cond_plg_DefaultValues';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number, valior, reason, folderId, currencyId=Валута, amount, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, cash';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, cash';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Приходен касов ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Pko";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cash, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cash, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'cash, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    var $canRevert = 'cash, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'cash/tpl/Pko.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'number, valior, contragentName, reason';
    
    
    /**
     * Параметри за принтиране
     */
    var $printParams = array( array('Оригинал'), array('Копие')); 

    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'currencyId' 	 => 'lastDocUser|lastDoc',
    	'depositor'      => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'customKey(mvc=acc_Operations,key=systemId, select=name)', 'caption=Операция,width=100%,mandatory');
    	
    	// Платена сума във валута, определена от полето `currencyId`
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=30%,summary=amount');
    	
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory,width=30%');
    	$this->FLD('number', 'int', 'caption=Номер,width=50%,width=30%');
    	$this->FLD('peroCase', 'key(mvc=cash_Cases, select=name)', 'caption=Каса');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Вносител,mandatory,width=100%');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('contragentAdress', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPlace', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPcode', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentCountry', 'varchar(255)', 'input=hidden');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    	$this->FLD('creditAccount', 'acc_type_Account()', 'input=none');
    	$this->FLD('debitAccount', 'acc_type_Account()', 'input=none');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,width=6em');
    	$this->FLD('rate', 'double', 'caption=Валута->Курс,width=6em');
    	$this->FNC('tempRate', 'double', 'caption=Валута->Курс,width=6em');
    	$this->FLD('notes', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    	 
        // Поставяне на уникален индекс
    	$this->setDbUnique('number');
    }
	
	
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		cash_Cases::prepareCaseFilter($data, array('peroCase'));
	}
	
	
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = $data->form->rec->folderId;
    	$form = &$data->form;
    	
    	$contragentId = doc_Folders::fetchCoverId($folderId);
        $contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	$form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
    	
        $options = acc_Operations::getPossibleOperations(get_called_class());
        $options = acc_Operations::filter($options, $contragentClassId);
    	
        // Използваме помощната функция за намиране името на контрагента
    	if(empty($form->rec->id) && $origin = $mvc->getOrigin($form->rec)) {
    		 $form->setDefault('reason', "Към документ #{$origin->getHandle()}");
    		 if($origin->haveInterface('bgerp_DealAggregatorIntf')){
    		 	$dealInfo = $origin->getAggregateDealInfo();
    		 	$amount = ($dealInfo->shipped->amount - $dealInfo->paid->amount) / $dealInfo->shipped->rate;
    		 	if($amount <= 0) {
    		 		$amount = 0;
    		 	}
    		 	
    		 	if($dealInfo->dealType == bgerp_iface_DealResponse::TYPE_SALE){
    		 		$defaultOperation = (!$dealInfo->hasDownpayment) ? 'customer2caseAdvance' : 'customer2case';
    		 	} else {
    		 		$defaultOperation = (!$dealInfo->hasDownpayment) ? 'supplierAdvance2case' : 'supplier2case';
    		 	}
    		 	
    		 	// Ако операциите на документа не са позволени от интерфейса, те се махат
    		 	foreach ($options as $index => $op){
    		 		if(!in_array($index, $dealInfo->allowedPaymentOperations)){
    		 			unset($options[$index]);
    		 		}
    		 	}
    		 	
    		 	if($caseId = $dealInfo->agreed->payment->caseId){
    		 		$cashRec = cash_Cases::fetch($caseId);
	    		 	
    		 		// Ако потребителя има права, логва се тихо
    		 		cash_Cases::selectSilent($caseId);
    		 	}
    		 	
    		 	$form->rec->currencyId = currency_Currencies::getIdByCode($dealInfo->shipped->currency);
    		 	$form->rec->tempRate = $dealInfo->shipped->rate;
    		 	
    		 	if($dealInfo->dealType != bgerp_iface_DealResponse::TYPE_PURCHASE){
    		 		$form->rec->amount = currency_Currencies::round($amount, $dealInfo->shipped->currency);
    		 	}
    		 }
    	}
    	
    	// Поставяме стойности по подразбиране
    	$form->setDefault('valior', dt::today());
        
        if($contragentClassId == crm_Companies::getClassId()){
    		$form->setSuggestions('depositor', crm_Companies::getPersonOptions($contragentId, FALSE));
    	}
        
    	$form->setOptions('operationSysId', $options);
    	if(isset($defaultOperation) && array_key_exists($defaultOperation, $options)){
    		$form->rec->operationSysId = $defaultOperation;	
        }
    	$form->setReadOnly('peroCase', cash_Cases::getCurrent());
    	$form->setReadOnly('contragentName', cls::get($contragentClassId)->getTitleById($contragentId));
    	
    	$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['tempRate'].value ='';"));
    }

    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
	    	
    		// Коя е дебитната и кредитната сметка
	        $operation = acc_Operations::fetchBySysId($rec->operationSysId);
    		
	        $rec->debitAccount = $operation->debitAccount;
    		$rec->creditAccount = $operation->creditAccount;
    		
    		$contragentData = doc_Folders::getContragentData($rec->folderId);
	    	$rec->contragentCountry = $contragentData->country;
	    	$rec->contragentPcode = $contragentData->pCode;
	    	$rec->contragentPlace = $contragentData->place;
	    	$rec->contragentAdress = $contragentData->adress;
	    	$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
	    	
		    if(!$rec->rate){
		    	if($rec->tempRate){
		    		$rec->rate = $rec->tempRate;
		    	} else {
		    		
		    		// Изчисляваме курса към основната валута ако не е дефиниран
		    		$rec->rate = round(currency_CurrencyRates::getRate($rec->valior, $currencyCode, NULL), 4);
		    	}
		    } else {
		    	if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
		    		$form->setWarning('rate', $msg);
		    	}
		    }
	    }
    	
	    acc_Periods::checkDocumentDate($form, 'valior');
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
    	
    	if($fields['-single']){
    		
    		// Адреса на контрагента
    		$row->contragentName .= trim(
                sprintf("<br>%s<br>%s %s<br> %s", 
                 	$row->contragentCountry,
                    $row->contragentPcode,
                    $row->contragentPlace,
                    $row->contragentAdress
                )
            );
    	
            if($rec->rate != 1) {
            	$double = cls::get('type_Double');
            	$double->params['decimals'] = 0;
		   		$rec->equals = round($rec->amount * $rec->rate, 2);
		   		$row->equals = $double->toVerbal($rec->equals);
		   		$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->valior);
		    } 
		    
            if(!$rec->equals) {
	    		
	    		// Ако валутата на документа съвпада с тази на периода не се показва курса
	    		unset($row->rate);
	    		unset($row->baseCurrency);
	    	} 
           
	    	$spellNumber = cls::get('core_SpellNumber');
		    $amountVerbal = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
		    $row->amountVerbal = $amountVerbal;
		    	
    		// Вземаме данните за нашата фирма
        	$ourCompany = crm_Companies::fetchOurCompany();
        	$row->organisation = $ourCompany->name;
        	$row->organisation .= trim(
                sprintf("<br>%s %s<br> %s", 
                    $ourCompany->place,
                    $ourCompany->pCode,
                    $ourCompany->address
                )
            );
            
    		// Извличаме имената на създателя на документа (касиера)
    		$cashierRec = core_Users::fetch($rec->createdBy);
    		$cashierRow = core_Users::recToVerbal($cashierRec);
	    	$row->cashier = $cashierRow->names;
	    }
       
        // Показваме заглавието само ако не сме в режим принтиране
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	}
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('cash/tpl/styles.css', 'CSS');
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
            'reason' => $rec->reason, // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => array(
                array(
                    'amount' => $rec->rate * $rec->amount,	// равностойноста на сумата в основната валута
                    
                    'debit' => array(
                        $rec->debitAccount, // дебитната сметка
                            array('cash_Cases', $rec->peroCase),
                            array('currency_Currencies', $rec->currencyId),
                        'quantity' => $rec->amount,
                    ),
                    
                    'credit' => array(
                        $rec->creditAccount, // кредитна сметка
                            array($rec->contragentClassId, $rec->contragentId), // Перо контрагент
                            array('currency_Currencies', $rec->currencyId),
                        'quantity' => $rec->amount,
                    ),
                )
            )
        );
        
        // Ако кредитната сметка не поддържа втора номенклатура, премахваме
        // от масива второто перо на кредитната сметка
        $cAcc = acc_Accounts::getRecBySystemId($rec->creditAccount);
        
        if(!$cAcc->groupId2){
        	unset($result->entries[0]['credit'][2]);
        }
        
        return $result;
    }
    
	
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'active';
        
        if ($this->save($rec)) {
            // Нотифицираме origin-документа, че някой от веригата му се е променил
            if ($origin = $this->getOrigin($rec)) {
                $ref = new core_ObjectReference($this, $rec);
                $origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
            }
        }
    }
    
    
    /**
     * След оттегляне на документа
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param object|int $id
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        // Нотифицираме origin-документа, че някой от веригата му се е променил
        if ($origin = $mvc->getOrigin($id)) {
            $ref = new core_ObjectReference($mvc, $id);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
        }
    }
    
    
   	/*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
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
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        // Можем да добавяме или ако корицата е контрагент или сме в папката на текущата каса
        $cover = doc_Folders::getCover($folderId);
        
        return $cover->haveInterface('doc_ContragentDataIntf') || 
            ($cover->className == 'cash_Cases' && 
             $cover->that == cash_Cases::getCurrent('id', FALSE) );
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
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->number;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function fetchByHandle($parsedHandle)
    {
        return static::fetch("#number = '{$parsedHandle['id']}'");
    } 
    
    
   	/*
     * Реализация на интерфейса sales_PaymentIntf
     */
    
    
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
            'amount' 	   => $rec->amount,
            'currencyCode' => currency_Currencies::getCodeById($rec->currencyId),
        	'currencyRate' => $rec->rate,
            'valior'       => $rec->valior,
        );
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
    
        /* @var $result bgerp_iface_DealResponse */
        $result = new bgerp_iface_DealResponse();
    	
        // При продажба платеното се увеличава, ако е покупка се намалява
        $origin = static::getOrigin($rec);
    	$sign = ($origin->className == 'purchase_Purchases') ? -1 : 1;
    	
        $result->paid->amount          = $sign * $rec->amount * $rec->rate;
        $result->paid->currency        = currency_Currencies::getCodeById($rec->currencyId);
        $result->paid->rate 	       = $rec->rate;
        $result->paid->payment->caseId = $rec->peroCase;
        $result->paid->operationSysId  = $rec->operationSysId;
        
        if($rec->operationSysId == 'customer2caseAdvance' || $rec->operationSysId == 'supplierAdvance2case'){
    		$result->paid->downpayment = $result->paid->amount;
    	} 
    	
    	$hasDownpayment = ($rec->operationSysId == 'customer2caseAdvance') ? TRUE : FALSE;
        $result->hasDownpayment = $hasDownpayment;
    	
        return $result;
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
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия приходен касов ордер") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
    
    
	/**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
    	
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
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Документа неможе да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['btnNewThread'])){
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
}