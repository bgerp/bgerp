<?php



/**
 * Документ за "Прехвърляне на задължение"
 * Могат да се добавят към нишки на покупки, продажби и финансови сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_CreditDocuments extends core_Master
{
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'deals_CreditDocument';
	
	
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public  $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf, sales_PaymentIntf, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Прехвърляне на задължения";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, deals_Wrapper, deals_WrapperDocuments, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, deals_plg_Document,
                     plg_Search, bgerp_plg_Blank,bgerp_DealIntf, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "tools=Пулт, valior, name, folderId, currencyId=Валута, amount, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, dealsMaster';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, deals';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Прехвърляне на задължение';
    
    
    /**
     * Икона на единичния изглед
     */
    //var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Cdd";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'deals, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'deals, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'deals, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    public $canRevert = 'deals, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'deals/tpl/SingleLayoutCreditDocument.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, folderId, dealId';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.6|Финанси";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory,width=30%');
    	$this->FLD('name', 'varchar(255)', 'caption=Име,mandatory,width=100%');
    	$this->FLD('dealId', 'key(mvc=deals_Deals,select=detailedName,allowEmpty)', 'mandatory,caption=Сделка,width=100%');
    	$this->FLD('amount', 'double(smartRound)', 'caption=Сума,mandatory,summary=amount');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,width=6em');
    	$this->FLD('rate', 'double(smartRound,decimals=2)', 'caption=Валута->Курс,width=6em');
    	$this->FLD('description', 'richtext(bucket=Notes,rows=6)', 'caption=Бележки');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)',
    			'caption=Статус, input=none'
    	);
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = $data->form->rec->folderId;
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$contragentId = doc_Folders::fetchCoverId($folderId);
    	$contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	$form->setDefault('contragentId', $contragentId);
    	$form->setDefault('contragentClassId', $contragentClassId);
    	
    	// Поставяме стойности по подразбиране
    	$form->setDefault('valior', dt::today());
    	
    	expect($origin = $mvc->getOrigin($form->rec));
    	expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
    	$dealInfo = $origin->getAggregateDealInfo();
    	expect(count($dealInfo->allowedPaymentOperations));
    	
    	// Показваме само тези финансови операции в които е засегнат контрагента
    	$options = deals_Deals::fetchDealOptions($dealInfo->involvedContragents);
    	expect(count($options));
    	$form->setOptions('dealId', $options);
    	
    	$form->dealInfo = $dealInfo;
    	$form->setDefault('operationSysId', 'creditDeals');
    	
    	// Използваме помощната функция за намиране името на контрагента
    	if(empty($form->rec->id)) {
    		 $form->setDefault('description', "Към документ #{$origin->getHandle()}");
    		 $form->rec->currencyId = currency_Currencies::getIdByCode($dealInfo->agreed->currency);
    		 $form->rec->rate = $dealInfo->agreed->rate;
    	}
    	
    	$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	
    	if ($form->isSubmitted()){
    		$operation = $form->dealInfo->allowedPaymentOperations[$rec->operationSysId];
    		
    		$creditAcc = deals_Deals::fetchField($rec->dealId, 'accountId');
    		
    		// Коя е дебитната и кредитната сметка
    		$rec->debitAccount = $operation['debit'];
    		$rec->creditAccount = acc_Accounts::fetchRec($creditAcc)->systemId;
    		
    		acc_Periods::checkDocumentDate($form, 'valior');
    		
    		$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
    		if(!$rec->rate){
    			$rec->rate = round(currency_CurrencyRates::getRate($rec->valior, $currencyCode, NULL), 4);
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
    				$form->setWarning('rate', $msg);
    			}
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
    	$amount = round($rec->rate * $rec->amount, 2);
    	
    	expect($origin = static::getOrigin($rec));
    	$dealInfo = $origin->getAggregateDealInfo();
    	if($dealInfo->dealType == bgerp_iface_DealResponse::TYPE_DEAL){
    		$debitFirstArr = array('deals_Deals', $origin->that);
    	} else {
    		$debitFirstArr = array($rec->contragentClassId, $rec->contragentId);
    	}
    	
    	$dealRec = deals_Deals::fetch($rec->dealId);
    	
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->name, // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => array(
    					array(
    						'amount' => $amount,	// равностойноста на сумата в основната валута
    						'debit' => array($rec->debitAccount,
    										$debitFirstArr,
    										array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->agreed->currency)),
    										'quantity' => round($amount / $dealInfo->agreed->rate, 2)),
    							
    						'credit' => array($rec->creditAccount,
    										array('deals_Deals', $rec->dealId),
    										array('currency_Currencies', currency_Currencies::getIdByCode($dealRec->currencyId)),
    										'quantity' => round($amount / $dealRec->currencyRate, 2)),
    				)
    		)
    	);
    	
    	return $result;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    	
    	return "{$self->singleTitle} №{$rec->id}";
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$row->title = $this->singleTitle . " №{$id}";
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $name;
    
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
    	 
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	 
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
    		// Ако няма позволени операции за документа не може да се създава
    		$dealInfo = $firstDoc->getAggregateDealInfo();
    		
    		// Ако няма финансови сделки в които  замесен контрагента, не може да се създава
    		$options = deals_Deals::fetchDealOptions($dealInfo->involvedContragents);
    		if(!count($options)) return FALSE;
    		
    		// Ако няма позволени операции за документа не може да се създава
    		return isset($dealInfo->allowedPaymentOperations['creditDeals']) ? TRUE : FALSE;
    	}
    
    	return FALSE;
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
    		$this->notificateOrigin($rec);
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
    	$mvc->notificateOrigin($id);
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
    	$sign = ($origin->className == 'sales_Sales') ? -1 : 1;
    	
    	$result->paid->amount   = $sign * $rec->amount * $rec->rate;
    	$result->paid->currency = currency_Currencies::getCodeById($rec->currencyId);
    	$result->paid->rate 	= $rec->rate;
    	 
    	return $result;
    }
}