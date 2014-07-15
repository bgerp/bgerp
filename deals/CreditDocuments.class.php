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
    public  $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=deals_transaction_CreditDocument, sales_PaymentIntf, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Прехвърляне на задължения";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, deals_Wrapper, plg_Sorting, acc_plg_Contable,
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
    	$this->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
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
    	expect(count($dealInfo->get('allowedPaymentOperations')));
    	
    	// Показваме само тези финансови операции в които е засегнат контрагента
    	$options = deals_Deals::fetchDealOptions($dealInfo->get('involvedContragents'));
    	expect(count($options));
    	$form->setOptions('dealId', $options);
    	
    	$form->dealInfo = $dealInfo;
    	$form->setDefault('operationSysId', 'creditDeals');
    	
    	// Използваме помощната функция за намиране името на контрагента
    	if(empty($form->rec->id)) {
    		 $form->setDefault('description', "Към документ #{$origin->getHandle()}");
    		 $form->rec->currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    		 $form->rec->rate = $dealInfo->get('rate');
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
    		$operations = $form->dealInfo->get('allowedPaymentOperations');
    		$operation = $operations[$rec->operationSysId];
    		
    		$creditAcc = deals_Deals::fetchField($rec->dealId, 'accountId');
    		
    		$debitAccount = empty($operation['reverse']) ? $operation['debit'] : acc_Accounts::fetchRec($creditAcc)->systemId;
    		$creditAccount = empty($operation['reverse']) ? acc_Accounts::fetchRec($creditAcc)->systemId : $operation['debit'];
    		
    		// Коя е дебитната и кредитната сметка
    		$rec->debitAccount = $debitAccount;
    		$rec->creditAccount = $creditAccount;
    		$rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
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
    		$options = deals_Deals::fetchDealOptions($dealInfo->get('involvedContragents'));
    		if(!count($options)) return FALSE;
    		
    		// Ако няма позволени операции за документа не може да се създава
    		$operations = $dealInfo->get('allowedPaymentOperations');
    		
    		return isset($operations['creditDeals']) ? TRUE : FALSE;
    	}
    
    	return FALSE;
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
}
