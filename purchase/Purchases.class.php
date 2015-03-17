<?php



/**
 * Документ 'Покупка'
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Покупки
 */
class purchase_Purchases extends deals_DealMaster
{
    
    
	const AGGREGATOR_TYPE = 'purchase';
	
	
    /**
     * Заглавие
     */
    public $title = 'Договори за покупка';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealAggregatorIntf, bgerp_DealIntf, acc_TransactionSourceIntf=purchase_transaction_Purchase, deals_DealsAccRegIntf, acc_RegisterIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, acc_plg_Registry, plg_Sorting, doc_plg_MultiPrint, doc_plg_TplManager, doc_DocumentPlg, acc_plg_Contable, plg_Printing,
				        plg_ExportCsv, cond_plg_DefaultValues, recently_Plugin, doc_plg_HidePrices, doc_SharablePlg, plg_Clone,
				        doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc, acc_plg_DocumentSummary, plg_Search';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'purchase_Requests';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Pur';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
     * Кой може да го активира?
     */
    public $canConto = 'ceo,purchase,acc';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, purchase';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, currencyId=Валута, amountDeal, amountDelivered, amountPaid,amountInvoiced,dealerId,initiatorId,paymentState,createdOn, createdBy, modifiedOn, modifiedBy';


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
    					 currencyId, bankAccountId, caseId, dealerId, folderId, id';
    
    
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
    	'template' 			 => 'lastDocUser|lastDoc|LastDocSameCuntry',
    	'activityCenterId'   => 'lastDocUser|lastDoc',
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
     * (@see plg_Clone)
     */
    public $cloneDetailes = 'purchase_PurchasesDetails';
    
    
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
     * Кое поле показва сумата на сделката
     */
    public $canClosewith = 'ceo,purchaseMaster';
    
    
    /**
     * Как се казва приключващия документ
     */
    public $closeDealDoc = 'purchase_ClosedDeals';
    
    
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
    	$this->FLD('activityCenterId', 'key(mvc=hr_Departments, select=name, allowEmpty)', 'caption=Доставка->Център на дейност,mandatory,after=shipmentStoreId');
    	$this->setField('dealerId', 'caption=Наш персонал->Закупчик');
    	$this->setField('shipmentStoreId', 'caption=Доставка->В склад');
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
        
        $form->setDefault('activityCenterId', hr_Departments::fetchField("#systemId = 'myOrganisation'", 'id'));
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
	    		
	    		// Ако разликата е над допустимата но потребителя има права 'purchase', той вижда бутона но не може да го използва
	    		if(!purchase_ClosedDeals::isPurchaseDiffAllowed($rec) && haveRole('purchase')){
	    			$data->toolbar->addBtn('Приключване', $closeArr, "row=2,ef_icon=img/16/closeDeal.png,title=Приключване на покупката,error=Нямате право да приключите покупка с разлика над допустимото");
	    		}
	    	}
    		
	    	if (store_Receipts::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
	    		$receiptUrl = array('store_Receipts', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
	            $data->toolbar->addBtn('Засклаждане', $receiptUrl, 'ef_icon = img/16/shipment.png,title=Засклаждане на артикулите в склада,order=9.21');
	        }
	    	
    		if(purchase_Services::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
    			$serviceUrl = array('purchase_Services', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
	            $data->toolbar->addBtn('Приемане', $serviceUrl, 'ef_icon = img/16/shipment.png,title=Покупка на услуги,order=9.22');
	        }
	        
    		if(cash_Pko::haveRightFor('add')){
		    	$data->toolbar->addBtn("РКО", array('cash_Rko', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов ордер');
		    }
		    
    		if(bank_IncomeDocuments::haveRightFor('add')){
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
        
        $result->setIfNot('dealType', self::AGGREGATOR_TYPE);
        
        // Извличаме продуктите на покупката
        $dQuery = purchase_PurchasesDetails::getQuery();
        $dQuery->where("#requestId = {$rec->id}");
        $detailRecs = $dQuery->fetchAll();
        
        // Ако платежния метод няма авансова част, авансовите операции 
        // не са позволени за платежните документи
        if($rec->paymentMethodId){
        	if(cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
        		// Колко е очакваното авансово плащане
        		$paymentRec = cond_PaymentMethods::fetch($rec->paymentMethodId);
        		$downPayment = round($paymentRec->downpayment * $rec->amountDeal, 4);
        	}
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
        $result->setIfNot('activityCenterId', $rec->activityCenterId);
        
        purchase_transaction_Purchase::clearCache();
        $result->set('agreedDownpayment', $downPayment);
        $result->set('downpayment', purchase_transaction_Purchase::getDownpayment($rec->id));
        $result->set('amountPaid', purchase_transaction_Purchase::getPaidAmount($rec->id));
        $result->set('deliveryAmount', purchase_transaction_Purchase::getDeliveryAmount($rec->id));
        $result->set('blAmount', purchase_transaction_Purchase::getBlAmount($rec->id));
        
        $agreedDp = $result->get('agreedDownpayment');
        $actualDp = $result->get('downpayment');
        if($agreedDp && ($actualDp < $agreedDp)){
        	$result->set('defaultCaseOperation', 'case2supplierAdvance');
        	$result->set('defaultBankOperation', 'bank2supplierAdvance');
        } else {
        	$result->set('defaultCaseOperation', 'case2supplier');
        	$result->set('defaultBankOperation', 'bank2supplier');
        }
        
        if (isset($actions['ship'])) {
            $result->setIfNot('shippedValior', $rec->valior);
        }
        
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId           = $dRec->classId;
            $p->productId         = $dRec->productId;
            $p->packagingId       = $dRec->packagingId;
            $p->discount          = $dRec->discount;
            $p->quantity          = $dRec->quantity;
            $p->quantityDelivered = $dRec->quantityDelivered;
            $p->price             = $dRec->price;
            $p->uomId             = $dRec->uomId;
            $p->notes			  = $dRec->notes;
            
            $ProductMan = cls::get($p->classId);
            $info = $ProductMan->getProductInfo($p->productId, $p->packagingId);
            $p->weight  = $ProductMan->getWeight($p->productId, $p->packagingId);
            $p->volume  = $ProductMan->getVolume($p->productId, $p->packagingId);
            
            $result->push('products', $p);
            
        	if (!empty($p->packagingId)) {
        		$push = TRUE;
            	$index = $p->classId . "|" . $p->productId;
            	$shipped = $result->get('shippedPacks');
            	
            	$inPack = ($p->packagingId) ? $info->packagingRec->quantity : 1;
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
        }
        
        $result->set('contoActions', $actions);
        $result->set('shippedProducts', purchase_transaction_Purchase::getShippedProducts($rec->id));
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
        $rec->offset = 0;
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
        $rec2->offset = 0;
        $rec2->delay = 0;
        $rec2->timeLimit = 100;
        $res .= core_Cron::addOnce($rec2);
    }
    
    
    /**
     * Проверява дали покупки е просрочена или платени
     */
    public function cron_CheckPurchasePayments()
    {
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
    	$tplArr[] = array('name' => 'Договор за покупка', 'content' => 'purchase/tpl/purchases/Purchase.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Договор за покупка на услуга', 'content' => 'purchase/tpl/purchases/Service.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Purchase contract', 'content' => 'purchase/tpl/purchases/PurchaseEN.shtml', 'lang' => 'en');
    	$tplArr[] = array('name' => 'Purchase of service contract', 'content' => 'purchase/tpl/purchases/ServiceEN.shtml', 'lang' => 'en', 'oldName' => 'Purchase of Service contract');
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
}
