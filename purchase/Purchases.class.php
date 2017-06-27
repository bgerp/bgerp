<?php



/**
 * Документ 'Покупка'
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Покупки
 */
class purchase_Purchases extends deals_DealMaster
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Договори за покупка';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, bgerp_DealAggregatorIntf, bgerp_DealIntf, acc_TransactionSourceIntf=purchase_transaction_Purchase, deals_DealsAccRegIntf, acc_RegisterIntf, deals_InvoiceSourceIntf,colab_CreateDocumentIntf,acc_AllowArticlesCostCorrectionDocsIntf,trans_LogisticDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, acc_plg_Registry, plg_Sorting, doc_plg_MultiPrint, doc_plg_TplManager, doc_DocumentPlg, acc_plg_Contable, plg_Printing,
				        cond_plg_DefaultValues, recently_Plugin, doc_plg_HidePrices, doc_SharablePlg, plg_Clone,
				        doc_EmailCreatePlg, bgerp_plg_Blank, acc_plg_DocumentSummary, plg_Search, doc_plg_Close, plg_LastUsedKeys';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'purchase_Requests';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Pur';
    
    
    /**
     * Кой може да го активира?
     */
    public $canConto = 'ceo,purchase,acc';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,purchase,acc';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,purchase,acc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
	/**
	* Кои роли могат да филтрират потребителите по екип в листовия изглед
	*/
	public $filterRolesForTeam = 'ceo,purchaseMaster,manager';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, valior, title=Документ, currencyId=Валута, amountDeal, amountDelivered, amountPaid,amountInvoiced,dealerId,initiatorId,paymentState,createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_PurchasesDetails';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Покупка';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/cart_put.png';


    /**
     * Лейаут на единичния изглед 
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutPurchase.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.2|Логистика";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'deliveryTermId, deliveryLocationId, deliveryTime, shipmentStoreId, paymentMethodId,
    					 currencyId, bankAccountId, caseId, dealerId, folderId, note';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDeal,amountDelivered,amountPaid,amountInvoiced,amountToPay,amountToDeliver,amountToInvoice';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'deliveryTermId'     => 'clientCondition|lastDocUser|lastDoc',
    	'paymentMethodId'    => 'clientCondition|lastDocUser|lastDoc',
    	'currencyId'         => 'lastDocUser|lastDoc|CoverMethod',
    	'bankAccountId'      => 'lastDocUser|lastDoc',
    	'dealerId'           => 'lastDocUser',
    	'makeInvoice'        => 'lastDocUser|lastDoc',
    	'deliveryLocationId' => 'lastDocUser|lastDoc',
    	'chargeVat'			 => 'lastDocUser|lastDoc|defMethod',
    	'template' 			 => 'lastDocUser|lastDoc|defMethod',
    	'shipmentStoreId' 	 => 'clientCondition',
    );
    
    
    /**
     * В коя група по дефолт да влизат контрагентите, към които е направен документа
     */
    public $crmDefGroup = 'suppliers';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'purchase_PurchasesDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
    public $cloneDetails = 'purchase_PurchasesDetails';
    
    
    /**
     * Поле в което се замества шаблона от doc_TplManager
     */
    public $templateFld = 'SINGLE_CONTENT';
    
    
    /**
     * Позволени операции на последващите платежни документи
     */
    public $allowedPaymentOperations = array(
    		'case2supplierAdvance' => array('title' => 'Авансово плащане към Доставчик', 'debit' => '402', 'credit' => '501'),
    		'bank2supplierAdvance' => array('title' => 'Авансово плащане към Доставчик', 'debit' => '402', 'credit' => '503'),
    		'case2supplier'        => array('title' => 'Плащане към Доставчик', 'debit' => '401', 'credit' => '501'),
    		'bank2supplier'        => array('title' => 'Плащане към Доставчик', 'debit' => '401', 'credit' => '503'),
    		'supplier2case'        => array('title' => 'Връщане от Доставчик', 'debit' => '501', 'credit' => '401', 'reverse' => TRUE),
    		'supplier2bank'        => array('title' => 'Връщане от Доставчик', 'debit' => '503', 'credit' => '401', 'reverse' => TRUE),
    		'supplierAdvance2case' => array('title' => 'Връщане на аванс от Доставчик', 'debit' => '501', 'credit' => '402', 'reverse' => TRUE),
    		'supplierAdvance2bank' => array('title' => 'Връщане на аванс от Доставчик', 'debit' => '503', 'credit' => '402', 'reverse' => TRUE),
    		'debitDeals'           => array('title' => 'Прихващане на вземания', 'debit' => '*', 'credit' => '401', 'reverse' => TRUE),
    		'creditDeals'          => array('title' => 'Прихващане на задължение', 'debit' => '401', 'credit' => '*'),
    );
    
    
    /**
     * Как се казва приключващия документ
     */
    public $closeDealDoc = 'purchase_ClosedDeals';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'purchase,ceo,distributor';
    
    
    /**
     * Позволени операции за посследващите складови документи/протоколи
     */
    public $allowedShipmentOperations = array('stowage'         => array('title' => 'Засклаждане на стока', 'debit' => 'store', 'credit' => '401'),
    										  'buyServices'     => array('title' => 'Покупка на услуги', 'debit' => 'service', 'credit' => '401'),
									    	  'deliveryService' => array('title' => 'Връщане на направени услуги', 'debit' => '401', 'credit' => 'service', 'reverse' => TRUE),
									    	  'delivery'    	=> array('title' => 'Връщане на доставена стока', 'debit' => '401', 'credit' => 'store', 'reverse' => TRUE),
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	parent::setDealFields($this);
    	$this->FLD('bankAccountId', 'iban_Type(64)', 'caption=Плащане->Към банк. сметка,after=currencyRate');
    	$this->setField('dealerId', 'caption=Наш персонал->Закупчик,notChangeableByContractor');
    	$this->setField('shipmentStoreId', 'caption=Доставка->В склад,notChangeableByContractor,salecondSysId=defaultStorePurchase');
    	$this->setField('deliveryTermId', 'salecondSysId=deliveryTermPurchase');
    	$this->setField('paymentMethodId', 'salecondSysId=paymentMethodPurchase');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        $form->setDefault('valior', dt::now());
        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        $form->setSuggestions('bankAccountId', bank_Accounts::getContragentIbans($form->rec->contragentId, $form->rec->contragentClassId));
        $form->setDefault('makeInvoice', 'yes');
        $form->setField('deliveryLocationId', 'caption=Доставка->Обект от');
        $form->setField('shipmentStoreId', 'caption=Доставка->До склад');
        
        $hideRate = core_Packs::getConfigValue('purchase', 'PURCHASE_USE_RATE_IN_CONTRACTS');
        if($hideRate == 'yes' && !haveRole('partner')){
        	$form->setField('currencyRate', 'input');
        }
        
        // Търговеца по дефолт е отговорника на контрагента
        $inCharge = doc_Folders::fetchField($form->rec->folderId, 'inCharge');
        $form->setDefault('dealerId', $inCharge);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if(empty($rec->threadId)){
    		$rec->threadId = $mvc->fetchField($rec->id, 'threadId');
    	}
    	
    	if($rec->state == 'active'){
    		$closeArr = array('purchase_ClosedDeals', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
    		
    		if(purchase_ClosedDeals::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
	    		$data->toolbar->addBtn('Приключване', $closeArr, "row=2,ef_icon=img/16/closeDeal.png,title=Приключване на покупката");
	    	} else {
	    		$exClosedDeal = purchase_ClosedDeals::fetchField("#threadId = {$rec->threadId} AND #state != 'rejected'", 'id');
	    		
	    		// Ако разликата е над допустимата но потребителя има права 'purchase', той вижда бутона но не може да го използва
	    		if(!purchase_ClosedDeals::isPurchaseDiffAllowed($rec) && haveRole('purchase') && empty($exClosedDeal)){
	    			$data->toolbar->addBtn('Приключване', $closeArr, "row=2,ef_icon=img/16/closeDeal.png,title=Приключване на покупката,error=Нямате право да приключите покупка с разлика над допустимото|*!");
	    		}
	    	}
    		
	    	if (store_Receipts::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
	    		$receiptUrl = array('store_Receipts', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
	            $data->toolbar->addBtn('Засклаждане', $receiptUrl, 'ef_icon = img/16/store-receipt.png,title=Засклаждане на артикулите в склада,order=9.21');
	        }
	    	
    		if(purchase_Services::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
    			$serviceUrl = array('purchase_Services', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
	            $data->toolbar->addBtn('Приемане', $serviceUrl, 'ef_icon = img/16/shipment.png,title=Покупка на услуги,order=9.22');
	        }
	        
    		if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("РКО", array('cash_Rko', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов ордер');
		    }
		    
    		if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("РБД", array('bank_SpendingDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов разходен банков документ');
		    }
		    
    		// Ако експедирането е на момента се добавя бутон за нова фактура
	        $actions = type_Set::toArray($rec->contoActions);
	    	
	        if(purchase_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
	    		$data->toolbar->addBtn("Вх. фактура", array('purchase_Invoices', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/invoice.png,title=Създаване на входяща фактура,order=9.9993');
		    }
		}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->state == 'draft'){
    		
	    	// Ако има въведена банкова сметка, която я няма в системата я вкарваме
	    	if($rec->bankAccountId && strlen($rec->bankAccountId)){
	    		if(!bank_Accounts::fetch(array("#iban = '[#1#]'", $rec->bankAccountId))){
	    			$newAcc = new stdClass();
	    			$newAcc->currencyId = currency_Currencies::getIdByCode($rec->currencyId);
	    			$newAcc->iban = $rec->bankAccountId;
	    			$newAcc->contragentCls = $rec->contragentClassId;
	    			$newAcc->contragentId = $rec->contragentId;
	    			bank_Accounts::save($newAcc);
	    			core_Statuses::newStatus('Успешно е добавена нова банкова сметка на контрагента');
	    		}
	    	}
    	}
    }
    
    
    /**
     * Кои са позволените платежни операции за тази сделка
     */
    public function getPaymentOperations($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$allowedPaymentOperations = $this->allowedPaymentOperations;
    	
    	if($rec->paymentMethodId){
    		
    		// Ако има метод за плащане и той няма авансова част, махаме авансовите операции
    		if(!cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
    			unset($allowedPaymentOperations['case2supplierAdvance'],
    				$allowedPaymentOperations['bank2supplierAdvance'],
    				$allowedPaymentOperations['supplierAdvance2case'],
    				$allowedPaymentOperations['supplierAdvance2bank']);
    		}
    	}
    	
    	return $allowedPaymentOperations;
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
    	$rec = $this->fetchRec($id);
        $actions = type_Set::toArray($rec->contoActions);
        
        // Извличаме продуктите на покупката
        $dQuery = purchase_PurchasesDetails::getQuery();
        $dQuery->where("#requestId = {$rec->id}");
        $dQuery->orderBy("id", 'ASC');
        $detailRecs = $dQuery->fetchAll();
        
        // Ако платежния метод няма авансова част, авансовите операции 
        // не са позволени за платежните документи
        $downPayment = NULL;
        if(cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
        	
        	// Колко е очакваното авансово плащане
        	$downPayment = cond_PaymentMethods::getDownpayment($rec->paymentMethodId, $rec->amountDeal);
        }
        
        // Кои са позволените операции за последващите платежни документи
        $result->set('allowedPaymentOperations', $this->getPaymentOperations($rec));
        $result->set('allowedShipmentOperations', $this->getShipmentOperations($rec));
        $result->set('involvedContragents', array((object)array('classId' => $rec->contragentClassId, 'id' => $rec->contragentId)));
        
        $result->setIfNot('amount', $rec->amountDeal);
        $result->setIfNot('currency', $rec->currencyId);
        $result->setIfNot('rate', $rec->currencyRate);
        $result->setIfNot('vatType', $rec->chargeVat);
        $result->setIfNot('agreedValior', $rec->valior);
        $result->setIfNot('deliveryLocation', $rec->deliveryLocationId);
        $result->setIfNot('deliveryTime', $rec->deliveryTime);
        $result->setIfNot('deliveryTerm', $rec->deliveryTermId);
        $result->setIfNot('storeId', $rec->shipmentStoreId);
        $result->setIfNot('paymentMethodId', $rec->paymentMethodId);
        $result->setIfNot('caseId', $rec->caseId);
        $result->setIfNot('bankAccountId', bank_Accounts::fetchField(array("#iban = '[#1#]'", $rec->bankAccountId), 'id'));
       
        purchase_transaction_Purchase::clearCache();
        $entries = purchase_transaction_Purchase::getEntries($rec->id);
        
        $deliveredAmount = purchase_transaction_Purchase::getDeliveryAmount($entries, $rec->id);
        $paidAmount = purchase_transaction_Purchase::getPaidAmount($entries, $rec);
        
        $result->set('agreedDownpayment', $downPayment);
        $result->set('downpayment', purchase_transaction_Purchase::getDownpayment($entries));
        $result->set('amountPaid', $paidAmount);
        $result->set('deliveryAmount', $deliveredAmount);
        $result->set('blAmount', purchase_transaction_Purchase::getBlAmount($entries, $rec->id));
        
        // Опитваме се да намерим очакваното плащане
        $expectedPayment = NULL;
        if($deliveredAmount > $paidAmount){
        	
        	// Ако доставеното > платено това е разликата
        	$expectedPayment = $deliveredAmount - $paidAmount;
        } else {
        	
        	// В краен случай това е очаквания аванс от метода на плащане
        	$expectedPayment = $downPayment;
        }
        
        // Ако има очаквано плащане, записваме го
        if($expectedPayment){
        	if(empty($deliveredAmount)){
        		$expectedPayment = $expectedPayment - $paidAmount;
        	}
        	 
        	if($expectedPayment > 0){
        		$result->set('expectedPayment', $expectedPayment);
        	}
        }
        
        $agreedDp = $result->get('agreedDownpayment');
        $actualDp = $result->get('downpayment');
        
        // Дефолтните платежни операции са плащания към доставчик
        $result->set('defaultCaseOperation', 'case2supplier');
        $result->set('defaultBankOperation', 'bank2supplier');
        
        // Ако се очаква авансово плащане и платения аванс е под 80% от аванса,
        // очакваме още да се плаща по аванаса
        if($agreedDp){
        	if(empty($actualDp) || $actualDp < $agreedDp * 0.8){
        		$result->set('defaultCaseOperation', 'case2supplierAdvance');
        		$result->set('defaultBankOperation', 'bank2supplierAdvance');
        	}
        }
        
        if (isset($actions['ship'])) {
            $result->setIfNot('shippedValior', $rec->valior);
        }
        
        $agreed = array();
        $agreed2 = array();
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            foreach (array('productId', 'packagingId', 'discount', 'quantity', 'quantityInPack', 'price', 'notes', 'expenseItemId') as $fld){
            	$p->{$fld} = $dRec->{$fld};
            }
           
            $info = cat_Products::getProductInfo($p->productId);
            $p->weight  = cat_Products::getWeight($p->productId, $p->packagingId, $p->quantity);
            $p->volume  = cat_Products::getVolume($p->productId, $p->packagingId, $p->quantity);
            
            $agreed[] = $p;
            
            $p1 = clone $p;
            unset($p1->notes);
            $agreed2[] = $p1;
            
        	$push = TRUE;
            $index = $p->productId;
            $shipped = $result->get('shippedPacks');
            	
            $inPack = $p->quantityInPack;
            if($shipped && isset($shipped[$index])){
            	if($shipped[$index]->inPack < $inPack){
            		$push = FALSE;
            	}
            }
            	
            if($push){
            	$arr = (object)array('packagingId' => $p->packagingId, 'inPack' => $inPack);
            	$result->push('shippedPacks', $arr, $index);
            }
        }
       
        $result->set('dealProducts', $agreed);
        $agreed = deals_Helper::normalizeProducts(array($agreed2));
        $result->set('products', $agreed);
        $result->set('contoActions', $actions);
        $result->set('shippedProducts', purchase_transaction_Purchase::getShippedProducts($entries, $rec->id));
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate'){
    		if(isset($rec)){
    			if(!$mvc->purchase_PurchasesDetails->fetch("#requestId = {$rec->id}")){
    				$res = 'no_one';
    			}
    		} else {
    			$res = 'no_one';
    		}
    	}

    	if($action == 'closewith' && isset($rec)){
    		if(purchase_PurchasesDetails::fetch("#requestId = {$rec->id}")){
    			$res = 'no_one';
    		} elseif(!haveRole('purchase,ceo', $userId)){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Нагласяне на крон да приключва продажби и да проверява дали са просрочени
     */
    protected function setCron(&$res)
    {
    	// Крон метод за затваряне на остарели покупки
    	$rec = new stdClass();
        $rec->systemId = "Close purchases";
        $rec->description = "Затваряне на приключените покупки";
        $rec->controller = "purchase_Purchases";
        $rec->action = "CloseOldPurchases";
        $rec->period = 180;
        $rec->offset = mt_rand(0,30);
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);

        // Проверка по крон дали покупката е просрочена
        $rec2 = new stdClass();
        $rec2->systemId = "IsPurchaseOverdue";
        $rec2->description = "Проверява дали покупката е просрочена";
        $rec2->controller = "purchase_Purchases";
        $rec2->action = "CheckPurchasePayments";
        $rec2->period = 60;
        $rec2->offset = mt_rand(0,30);
        $rec2->delay = 0;
        $rec2->timeLimit = 100;
        $res .= core_Cron::addOnce($rec2);
    }
    
    
    /**
     * Проверява дали покупки е просрочена или платени
     */
    public function cron_CheckPurchasePayments()
    {
    	core_App::setTimeLimit(300);
    	$conf = core_Packs::getConfig('purchase');
    	$overdueDelay = $conf->PURCHASE_OVERDUE_CHECK_DELAY;
    	
    	$this->checkPayments($overdueDelay);
    }
    
    
    /**
     * Приключва всички приключени покупки
     */
    public function cron_CloseOldPurchases()
    {
    	$conf = core_Packs::getConfig('purchase');
    	$olderThan = $conf->PURCHASE_CLOSE_OLDER_THAN;
    	$limit 	   = $conf->PURCHASE_CLOSE_OLDER_NUM;
    	$ClosedDeals = cls::get('purchase_ClosedDeals');
    	
    	$this->closeOldDeals($olderThan, $ClosedDeals, $limit);
    }
    
    
    /**
     * Зарежда шаблоните на покупката в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr[] = array('name' => 'Договор за покупка', 'content' => 'purchase/tpl/purchases/Purchase.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/purchases/PurchaseNarrow.shtml');
    	$tplArr[] = array('name' => 'Договор за покупка на услуга', 'content' => 'purchase/tpl/purchases/Service.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/purchases/ServiceNarrow.shtml');
    	$tplArr[] = array('name' => 'Purchase contract', 'content' => 'purchase/tpl/purchases/PurchaseEN.shtml', 'lang' => 'en', 'narrowContent' => 'purchase/tpl/purchases/PurchaseNarrowEN.shtml');
    	$tplArr[] = array('name' => 'Purchase of service contract', 'content' => 'purchase/tpl/purchases/ServiceEN.shtml', 'lang' => 'en', 'oldName' => 'Purchase of Service contract', 'narrowContent' => 'purchase/tpl/purchases/ServiceNarrowEN.shtml');
    	$tplArr[] = array('name' => 'Заявка за транспорт', 'content' => 'purchase/tpl/purchases/Transport.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/purchases/TransportNarrow.shtml');
    	
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	// Изкарваме езика на шаблона от сесията за да се рендира статистиката с езика на интерфейса
    	core_Lg::pop();
    	$statisticTpl = getTplFromFile('purchase/tpl/PurchaseStatisticLayout.shtml');
    	$tpl->replace($statisticTpl, 'STATISTIC_BAR');
    	
    	// Отново вкарваме езика на шаблона в сесията
    	core_Lg::push($data->rec->tplLang);
    }
    
    
    /**
     * След вербализиране на записа
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-single'])){
    		if($cond = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, "commonConditionPur")){
    			$row->commonCondition = cls::get('type_Url')->toVerbal($cond);
    		}
    	}
    }
    
    
    /**
	 * Списък с артикули върху, на които може да им се коригират стойностите
	 * @see acc_AllowArticlesCostCorrectionDocsIntf
	 *
	 * @param mixed $id               - ид или запис
	 * @return array $products        - масив с информация за артикули
	 * 			    o productId       - ид на артикул
	 * 				o name            - име на артикула
	 *  			o quantity        - к-во
	 *   			o amount          - сума на артикула
	 *   			o inStores        - масив с ид-то и к-то във всеки склад в който се намира
	 *    			o transportWeight - транспортно тегло на артикула
	 *     			o transportVolume - транспортен обем на артикула
	 */
	function getCorrectableProducts($id)
	{
		$rec = $this->fetchRec($id);
		
		$products = array();
		$entries = purchase_transaction_Purchase::getEntries($rec->id);
		$shipped = purchase_transaction_Purchase::getShippedProducts($entries, $rec->id, '321', TRUE);
		
		if(count($shipped)){
			foreach ($shipped as $ship){
				unset($ship->price);
				$ship->name = cat_Products::getTitleById($ship->productId, FALSE);
				
				if($transportWeight = cat_Products::getParams($ship->productId, 'transportWeight')){
					$ship->transportWeight = $transportWeight;
				}
				
				if($transportVolume = cat_Products::getParams($ship->productId, 'transportVolume')){
					$ship->transportVolume = $transportVolume;
				}
				
				$products[$ship->productId] = $ship;
			}
		}
		
		return $products;
	}
}
