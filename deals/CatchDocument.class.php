<?php



/**
 * Документ за "Прихващания"
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_CatchDocument extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf, sales_PaymentIntf, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Прихващания";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, deals_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary,
                     plg_Search, bgerp_plg_Blank,bgerp_DealIntf, doc_EmailCreatePlg, cond_plg_DefaultValues';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, valior, name, folderId, currencyId=Валута, amount, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, deals';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, deals';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Прихващане';
    
    
    /**
     * Икона на единичния изглед
     */
    //var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Cdc";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'deals, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'deals, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'deals, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    var $canRevert = 'deals, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'deals/tpl/SingleLayoutCatchDocument.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //var $searchFields = 'number, valior, contragentName, reason';

    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.5|Финанси";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	//'depositor'      => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,width=100%,mandatory,silent');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory,width=30%');
    	$this->FLD('name', 'varchar(255)', 'caption=Име,mandatory,width=100%');
    	$this->FLD('dealId', 'key(mvc=deals_Deals,select=dealName,allowEmpty)', 'mandatory,caption=Сделка,width=100%');
    	$this->FLD('amount', 'double(smartRound)', 'caption=Сума,mandatory');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,width=6em');
    	$this->FLD('rate', 'double(smartRound,decimals=2)', 'caption=Валута->Курс,width=6em');
    	$this->FLD('description', 'richtext(bucket=Notes,rows=6)', 'caption=Бележки');
    	$this->FLD('operationName', 'varchar(255)', 'input=none');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
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
    	$form->dealInfo = $dealInfo;
    	
    	$options = self::getOperations($dealInfo->allowedPaymentOperations);
    	expect(count($options));
    	
    	$form->fields['operationSysId']->type = cls::get('type_Enum', array('options' => array('' => ' ') + $options));
    	
    	// Използваме помощната функция за намиране името на контрагента
    	if(empty($form->rec->id)) {
    		 $form->setDefault('description', "Към документ #{$origin->getHandle()}");
    		 
    		 $cId = ($dealInfo->shipped->currency) ? $dealInfo->shipped->currency : $dealInfo->paid->currency;
    		 $form->rec->currencyId = currency_Currencies::getIdByCode($cId);
    		 
    		 $rate = ($dealInfo->shipped->rate) ? $dealInfo->shipped->rate : $dealInfo->paid->rate;
    		 $form->rec->rate = $rate;
    	} else {
    		$form->setReadOnly('operationSysId');
    	}
    	
    	$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
    	
    	if(!$form->rec->operationSysId){
    		$form->setReadOnly('dealId');
    	}
    	
    	$form->addAttr('operationSysId', array('onchange' => "addCmdRefresh(this.form); document.forms['{$form->formAttr['id']}'].elements['dealId'].value ='';this.form.submit();"));
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	$operation = $form->dealInfo->allowedPaymentOperations[$rec->operationSysId];
    	
    	if($rec->operationSysId){
    		$dAccId = acc_Accounts::getRecBySystemId($operation['debit']);
    		$cAccId = acc_Accounts::getRecBySystemId($operation['credit']);
    		
    		$deals = deals_Deals::makeArray4Select($select, "(#accountId = {$dAccId->id} || #accountId = {$cAccId->id}) AND #state = 'active'");
    		if(!count($deals)){
    			$form->setError('dealId', 'Няма финансови сделки, по които може да се направи оепрацията');
    			$form->setReadOnly('dealId');
    		} else {
    			$form->setOptions('dealId', $deals);
    		}
    		
    	}
    	
    	if ($form->isSubmitted()){
    		// Коя е дебитната и кредитната сметка
    		
    		$rec->debitAccount = $operation['debit'];
    		$rec->creditAccount = $operation['credit'];
    		$rec->operationName = $form->dealInfo->allowedPaymentOperations[$rec->operationSysId]['title'];
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
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if($fields['-single']){
    		$row->dealId = deals_Deals::getHyperLink($rec->dealId, TRUE);
    		
    		// Показваме заглавието само ако не сме в режим принтиране
    		if(!Mode::is('printing')){
    			$row->header = $row->operationName . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    		}
    		
    		$baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
    		
    		if($baseCurrencyId != $rec->currencyId) {
    			$Double = cls::get('type_Double');
    			$Double->params['decimals'] = 2;
    			$rec->amountBase = round($rec->amount * $rec->rate, 2);
    			$row->amountBase = $Double->toVerbal($rec->amountBase);
    			$row->baseCurrency = currency_Currencies::getCodeById($baseCurrencyId);
    		} else {
    			unset($row->rate);
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
    	if($rec->operationSysId == 'creditFactoring'){
    		$debitFirstArr  = array($rec->contragentClassId, $rec->contragentId);
    		$creditFirstArr = array('deals_Deals', $rec->dealId);
    	} else {
    		$debitFirstArr  = array('deals_Deals', $rec->dealId);
    		$creditFirstArr = array($rec->contragentClassId, $rec->contragentId);
    	}
    	
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->reason, // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => array(
    					array(
    						'amount' => $rec->rate * $rec->amount,	// равностойноста на сумата в основната валута
    						'debit' => array($rec->debitAccount,
    										$debitFirstArr,
    										array('currency_Currencies', $rec->currencyId),
    										'quantity' => $rec->amount),
    							
    						'credit' => array($rec->creditAccount,
    										$creditFirstArr,
    										array('currency_Currencies', $rec->currencyId),
    										'quantity' => $rec->amount),
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
    	$name = static::getVerbal($rec, 'operationName');
    	
    	return "{$name} №{$rec->id}";
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$name = $this->getVerbal($rec, 'operationName');
    	$row = new stdClass();
    	$row->title = $name . " №{$id}";
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $name;
    
    	return $row;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
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
    	 
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
    			
    		// Ако няма позволени операции за документа не може да се създава
    		$dealInfo = $firstDoc->getAggregateDealInfo();
    		$options = self::getOperations($dealInfo->allowedPaymentOperations);
    		 
    		return count($options) ? TRUE : FALSE;
    	}
    
    	return FALSE;
    }
    
    
    /**
     * Връща платежните операции
     */
    private static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции в коитос е дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['debit'] == '406' || $op['credit'] == '406' || $op['debit'] == '414'|| $op['credit'] == '414'){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	
    	return $options;
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
    	 
    	$result->paid->amount          = $rec->amount * $rec->rate;
    	$result->paid->currency        = currency_Currencies::getCodeById($rec->currencyId);
    	$result->paid->rate 	       = $rec->rate;
    	$result->paid->operationSysId  = $rec->operationSysId;
    	 
    	return $result;
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'restore' && isset($rec)){
    		$dealState = deals_Deals::fetchField($rec->dealId, 'state');
    		if($dealState != 'active'){
    			$res = 'no_one';
    		}
    	}
    }
}