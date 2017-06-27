<?php



/**
 * Абстрактен клас за наследяване от класове сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_DealMaster extends deals_DealBase
{
	
	
	/**
	 * Масив с вербалните имена при избора на контиращи операции за покупки/продажби
	 */
	private static $contoMap = array(
			'sales'    => array('pay'     => 'Прието плащане в брой в каса ',
							    'ship'    => 'Експедиране на продукти от склад ',
							    'service' => 'Изпълнение на услуги'),
	
			'purchase' => array('pay'     => 'Направено плащане в брой от каса ',
								'ship'    => 'Вкарване на продукти в склад ',
							    'service' => 'Приемане на услуги')
	);
	
	
	/**
	 * На кой ред в тулбара да се показва бутона за принтиране
	 */
	public $printBtnToolbarRow = 1;
	
	
	/**
	 * Как се казва полето в което е избран склада
	 */
	public $storeFieldName = 'shipmentStoreId';
	
	
	/**
	 * Поле за търсене по потребител
	 */
	public $filterFieldUsers = 'dealerId';
	
	
	/**
	 * Не искаме документа да се кеширва в нишката
	 */
	public $preventCache = TRUE;
	
	
	/**
	 * Полета, които при клониране да не са попълнени
	 *
	 * @see plg_Clone
	 */
	public $fieldsNotToClone = 'valior,contoActions,amountDelivered,amountBl,amountPaid,amountInvoiced,sharedViews,closedDocuments,paymentState,deliveryTime,currencyRate,contragentClassId,contragentId,state,deliveryTermTime';
	
	
	/**
	 * Кои ключове да се тракват, кога за последно са използвани
	 */
	public $lastUsedKeys = 'deliveryTermId,paymentMethodId';
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	*/
	public static function on_AfterDescription(core_Master &$mvc)
	{
		if(empty($mvc->fields['contoActions'])){
			$mvc->FLD('contoActions', 'set(activate,pay,ship)', 'input=none,notNull,default=activate');
		}
		
		setIfNot($mvc->canChangerate, 'ceo,salesMaster,purchaseMaster');
	}


	/**
	 * Какво е платежното състояние на сделката
	 */
	public function getPaymentState($aggregateDealInfo)
	{
		$amountPaid        = $aggregateDealInfo->get('amountPaid');
		$amountBl          = $aggregateDealInfo->get('blAmount');
		$amountDelivered   = $aggregateDealInfo->get('deliveryAmount');
		$amountInvoiced    = $aggregateDealInfo->get('invoicedAmount');
		$notInvoicedAmount = core_Math::roundNumber($amountDelivered) - core_Math::roundNumber($amountInvoiced);
		
		// Добавяме 0 за да елиминираме -0 ако се получи при изчислението
		$notInvoicedAmount += 0;
		
		$diff = round($amountDelivered - $amountPaid, 4);
		
		// Ако имаме фактури към сделката
		if(count($aggregateDealInfo->invoices)){
			
			
			$today = dt::today();
			$invoices = $aggregateDealInfo->invoices;
			
			// Намираме непадежиралите фактури, тези с вальор >= на днес
			$sum = 0;
			$res = array_filter($invoices, function (&$e) use ($today, &$sum) {
				if($e['dueDate'] >= $today){
					$sum += $e['total'];
					return TRUE;
				}
				return FALSE;
			});
			
			// Ще сравняваме салдото със сумата на непадежиралите фактури + нефактурираното
			$valueToCompare = $sum + $notInvoicedAmount;
			$balance = $amountBl;
			
			// За покупката гледаме баланса с обратен знак
			if($this instanceof purchase_Purchases){
				$balance = -1 * $balance;
			}
			
			// Ако е по-голямо приемаме че сделката е просрочена
			if(trim($balance) > trim($valueToCompare)) return 'overdue';
		} else {
			
			// Ако няма фактури, гледаме имали платежен план
			$methodId = $aggregateDealInfo->get('paymentMethodId');
			if(!empty($methodId)){
				// За дата на платежния план приемаме първата фактура, ако няма първото експедиране, ако няма вальора на договора
				setIfNot($date, $aggregateDealInfo->get('invoicedValior'), $aggregateDealInfo->get('shippedValior'), $aggregateDealInfo->get('agreedValior'));
				$plan = cond_PaymentMethods::getPaymentPlan($methodId, $aggregateDealInfo->get('amount'), $date);
				
				// Проверяваме дали сделката е просрочена по платежния си план
				if(cond_PaymentMethods::isOverdue($plan, $diff)) return 'overdue';
			}
		}
		
		// Ако имаме доставено или платено
		$amountBl = round($amountBl, 4);
		$tolerancePercent = deals_Setup::get('BALANCE_TOLERANCE');
		$tolerance = $amountDelivered * $tolerancePercent;
		
		// Ако салдото е в рамките на толеранса приемаме че е 0
		if(abs($amountBl) <= abs($tolerance)){
			$amountBl = 0;
		}
		
		// Правим проверка дали е платена сделката
		if($this instanceof sales_Sales){
			if($amountBl <= 0) return 'paid';
		} elseif($this instanceof purchase_Purchases){
			if($amountBl >= 0) return 'paid';
		}
		
		return 'pending';
	}
	
	
	/**
	 * Задължителни полета на модела
	 */
	protected static function setDealFields($mvc)
	{
		$mvc->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date,notChangeableByContractor');
		
		// Стойности
		$mvc->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none,summary=amount'); // Сумата на договорената стока
		$mvc->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none,summary=amount'); // Сумата на доставената стока
		$mvc->FLD('amountBl', 'double(decimals=2)', 'caption=Стойности->Крайно салдо,input=none,summary=amount');
		$mvc->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
		$mvc->FLD('amountInvoiced', 'double(decimals=2)', 'caption=Стойности->Фактурирано,input=none,summary=amount'); // Сумата която е платена
		
		$mvc->FLD('amountVat', 'double(decimals=2)', 'input=none');
		$mvc->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
		
		// Контрагент
		$mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
		$mvc->FLD('contragentId', 'int', 'input=hidden');
		
		// Доставка
		$mvc->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,notChangeableByContractor');
		$mvc->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Доставка->Обект до,silent,class=contactData'); // обект, където да бъде доставено (allowEmpty)
		$mvc->FLD('deliveryAdress', 'varchar', 'caption=Доставка->Адрес,notChangeableByContractor,placeholder=Ако е празно се взима според условието на доставка');
		$mvc->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до,notChangeableByContractor'); // до кога трябва да бъде доставено
		$mvc->FLD('deliveryTermTime', 'time(uom=days,suggestions=1 ден|5 дни|10 дни|1 седмица|2 седмици|1 месец)', 'caption=Доставка->Срок дни,after=deliveryTime,notChangeableByContractor');
		
		$mvc->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)',  'caption=Доставка->От склад,notChangeableByContractor'); // наш склад, от където се експедира стоката
		
		// Плащане
		$mvc->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)','caption=Плащане->Метод,notChangeableByContractor');
		$mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,removeAndRefreshForm=currencyRate,notChangeableByContractor');
		$mvc->FLD('currencyRate', 'double(decimals=5)', 'caption=Плащане->Курс,input=hidden');
		$mvc->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса,notChangeableByContractor');
		
		// Наш персонал
		$mvc->FLD('initiatorId', 'user(roles=user,allowEmpty,rolesForAll=sales|ceo)', 'caption=Наш персонал->Инициатор,notChangeableByContractor');
		$mvc->FLD('dealerId', 'user(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Наш персонал->Търговец,notChangeableByContractor');
		
		// Допълнително
		$mvc->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Oсвободено от ДДС, no=Без начисляване на ДДС)', 'caption=Допълнително->ДДС,notChangeableByContractor');
		$mvc->FLD('makeInvoice', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Фактуриране,maxRadio=2,columns=2,notChangeableByContractor');
		$mvc->FLD('note', 'text(rows=4)', 'caption=Допълнително->Условия,notChangeableByContractor', array('attr' => array('rows' => 3)));
		
		$mvc->FLD('state',
				'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен, pending=Заявка,stopped=Спряно)',
				'caption=Статус, input=none'
		);
		
		$mvc->FLD('paymentState', 'enum(pending=Има,overdue=Просрочено,paid=Няма,repaid=Издължено)', 'caption=Чакащо плащане, input=none,notNull,value=paid');
		$mvc->FLD('productIdWithBiggestAmount', 'varchar', 'caption=Артикул с най-голяма стойност, input=none');
		
		$mvc->setDbIndex('valior');
	}


	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$form->setDefault('valior', dt::now());
		
		if(empty($form->rec->id)){
			$form->setDefault('shipmentStoreId', store_Stores::getCurrent('id', FALSE));
		}
		$form->setDefault('makeInvoice', 'yes');
		
		// Поле за избор на локация - само локациите на контрагента по сделката
		if(!$form->getFieldTypeParam('deliveryLocationId', 'isReadOnly')){
			$locations = array('' => '') + crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
			$form->setOptions('deliveryLocationId', $locations);
		}
		
		if ($form->rec->id){
        	
        	// Не може да се сменя ДДС-то ако има вече детайли
        	$Detail = $mvc->mainDetail;
        	if($mvc->$Detail->fetch("#{$mvc->{$Detail}->masterKey} = {$form->rec->id}")){
        		foreach (array('chargeVat', 'currencyId', 'deliveryTermId') as $fld){
        			$form->setReadOnly($fld, isset($form->rec->{$fld}) ? $form->rec->{$fld} : $mvc->fetchField($form->rec->id, $fld));
        		}
        	}
        }
        
        $form->setField('sharedUsers', 'input=none');
	}
	
	
	/**
	 * Дали да се начислява ДДС
	 */
	public function getDefaultChargeVat($rec)
	{
		$coverId = doc_Folders::fetchCoverId($rec->folderId);
		$Class = cls::get(doc_Folders::fetchCoverClassName($rec->folderId));
		
		return ($Class->shouldChargeVat($coverId)) ? 'yes' : 'no';
	}
	
	
	/**
	 * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
	 */
	public function updateMaster_($id)
	{
		$rec = $this->fetchRec($id);
		
		$Detail = cls::get($this->mainDetail);
		$query = $Detail->getQuery();
		$query->where("#{$Detail->masterKey} = '{$id}'");
		$recs = $query->fetchAll();
	
		deals_Helper::fillRecs($this, $recs, $rec);
	
		// ДДС-то е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
		$amountDeal = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
		$amountDeal -= $this->_total->discount;
		$rec->amountDeal = $amountDeal * $rec->currencyRate;
		$rec->amountVat  = $this->_total->vat * $rec->currencyRate;
		$rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
		$rec->productIdWithBiggestAmount = $this->findProductIdWithBiggestAmount($rec);
		
		$this->invoke('BeforeUpdatedMaster', array(&$rec));
		
		return $this->save($rec);
	}


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {   
        $mvc = cls::get(get_called_class());

    	$rec = static::fetchRec($rec);
    
        $abbr = $mvc->abbr;
        $abbr{0} = strtoupper($abbr{0});

        if(isset($rec->contragentClassId) && isset($rec->contragentId)){
        	$crm = cls::get($rec->contragentClassId);
        	$cRec =  $crm->getContragentData($rec->contragentId);
        	
        	$contragent = str::limitLen($cRec->person ? $cRec->person : $cRec->company, 16);
        } else {
        	$contragent = tr("Проблем при показването");
        }
        
        if($escaped) {
        	$contragent = type_Varchar::escape($contragent);
        }

        $title = "{$abbr}{$rec->id}/{$contragent}";
        
        // Показване и на артикула с най-голяма стойност в продажбата
        if(isset($rec->productIdWithBiggestAmount)){
        	$pName = mb_substr($rec->productIdWithBiggestAmount, 0, 16);
        	$title .= "/{$pName}";
        }
        
    	return $title;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if (!$form->isSubmitted()) return;
        $rec = &$form->rec;
        
        if(empty($rec->currencyRate)){
        	$rec->currencyRate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
        	if(!$rec->currencyRate){
        		$form->setError('currencyRate', "Не може да се изчисли курс");
        	}
        } else {
    		if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, $rec->valior, $rec->currencyId, NULL)){
    			$form->setWarning('currencyRate', $msg);
    		}
    	}
    	
    	if(isset($rec->deliveryTermTime) && isset($rec->deliveryTime)){
    		$form->setError('deliveryTime,deliveryTermTime', 'Трябва да е избран само един срок на доставка');
    	}
    }

    
    /**
     * Филтър на продажбите
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
        if(!Request::get('Rejected', 'int')){
        	$data->listFilter->FNC('type', 'enum(all=Всички,active=Активни,closed=Приключени,draft=Чернови,clAndAct=Активни и приключени,paid=Платени,overdue=Просрочени,unpaid=Неплатени,delivered=Доставени,undelivered=Недоставени,invoiced=Фактурирани,notInvoiced=Нефактурирани)', 'caption=Състояние');
	        $data->listFilter->setDefault('type', 'active');
			$data->listFilter->showFields .= ',type';
		}
		
		$data->listFilter->input();
		if($filter = $data->listFilter->rec) {
		
			$data->query->XPR('paidRound', 'double', 'ROUND(#amountPaid, 2)');
			$data->query->XPR('dealRound', 'double', 'ROUND(#amountDeal, 2)');
			$data->query->XPR('invRound', 'double', 'ROUND(#amountInvoiced, 2)');
			$data->query->XPR('deliveredRound', 'double', 'ROUND(#amountDelivered , 2)');
			
			if($filter->type) {
				switch($filter->type){
					case "clAndAct":
						$data->query->where("#state = 'active' OR #state = 'closed'");
						break;
					case "all":
						break;
					case "draft":
						$data->query->where("#state = 'draft'");
						break;
					case "active":
						$data->query->where("#state = 'active'");
						break;
					case "closed":
						$data->query->where("#state = 'closed'");
						break;
					case 'paid':
						$data->query->where("#paymentState = 'paid' OR #paymentState = 'repaid'");
						$data->query->where("#state = 'active' OR #state = 'closed'");
						break;
					case 'invoiced':
						$data->query->where("#invRound >= #deliveredRound");
						$data->query->where("#state = 'active' OR #state = 'closed'");
						break;
					case 'notInvoiced':
						$data->query->where("#invRound < #deliveredRound OR #invRound IS NULL");
						$data->query->where("#state = 'active' OR #state = 'closed'");
						break;
					case 'overdue':
						$data->query->where("#paymentState = 'overdue'");
						break;
					case 'delivered':
						$data->query->where("#deliveredRound = #dealRound");
						$data->query->where("#state = 'active' OR #state = 'closed'");
						break;
					case 'undelivered':
						$data->query->where("#deliveredRound < #dealRound OR #deliveredRound IS NULL");
						$data->query->where("#state = 'active'");
						break;
					case 'unpaid':
						$data->query->where("#paidRound < #deliveredRound OR #paidRound IS NULL");
						$data->query->where("#state = 'active'");
						break;
				}
			}
		}
    }
    
    
	/**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	
    	$rec = &$data->rec;
    	if(empty($data->noTotal)){
    		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat, FALSE, $rec->tplLang);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    		
    		if($rec->paymentMethodId) {
    			$total = $this->_total->amount- $this->_total->discount;
    			$total = ($rec->chargeVat == 'separate') ? $total + $this->_total->vat : $total;
    			
    			cond_PaymentMethods::preparePaymentPlan($data, $rec->paymentMethodId, $total, $rec->valior, $rec->currencyId);
    		}
    	}
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
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
     * Връща подзаглавието на документа във вида "Дост: ХХХ(ууу), Плат ХХХ(ууу), Факт: ХХХ(ууу)", Реф: ХХХ"
     * 
     * @param stdClass $rec - запис от модела
     * @return string $subTitle - подзаглавието
     */
    private function getSubTitle($rec)
    {
    	$fields = arr::make('amountDelivered,amountToDeliver,amountPaid,amountToPay,amountInvoiced,amountToInvoice', TRUE);
    	$row = $this->recToVerbal($rec, $fields);
    	
        $subTitle = tr("Дост:") . " {$row->amountDelivered} ({$row->amountToDeliver})";
        if(!empty($rec->amountPaid)){
        	$subTitle .= ", " . tr('Плат:') . " {$row->amountPaid} ({$row->amountToPay})";
        }
	
        if($rec->makeInvoice != 'no' && !empty($rec->amountInvoiced)){
        	$subTitle .= ", " . tr('Факт:') . " {$row->amountInvoiced} ({$row->amountToInvoice})";
        }
        
        if(!empty($rec->reff)){
        	$reff = cls::get('type_Varchar')->toVerbal($rec->reff);
        	$subTitle .= ", " . tr('Реф:') . " {$reff}";
        }
        
        return $subTitle;
    }
    
    
    /**
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = static::getRecTitle($rec);
        
        $row = (object)array(
            'title'    => $title,
        	'subTitle' => $this->getSubTitle($rec),
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $title,
        );
        
        return $row;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в сделката
     * 
     * @param int $id - ид на сделката
     * @return param $res - масив с използваните документи
     * 					['class'] - Инстанция на документа
     * 					['id'] - Ид на документа
     */
    public function getUsedDocs_($id)
    {
    	return deals_Helper::getUsedDocs($this, $id);    	
    }
    
    
    /**
     * Кои са позволените операции за експедиране
     */
    public function getShipmentOperations($id)
    {
    	return $this->allowedShipmentOperations;
    }
    
    
    /**
     * След обработка на записите
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        // Премахваме някои от полетата в listFields. Те са оставени там за да ги намерим в 
        // тук в $rec/$row, а не за да ги показваме
        $data->listFields = array_diff_key(
            $data->listFields, 
            arr::make('initiatorId,contragentId', TRUE)
        );
        
        $data->listFields['dealerId'] = 'Търговец';
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
    
                // Търговец (чрез инициатор)
                if (!empty($rec->initiatorId)) {
                    $row->dealerId .= ' <small><span class="quiet">чрез</span> ' . $row->initiatorId . "</small>";
                }
            }
        }
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
    	// Кои потребители ще се нотифицират
    	$rec->sharedUsers = '';
		$actions = type_Set::toArray($rec->contoActions);
    	
    	// Ако има склад, се нотифицира отговорника му
    	if(isset($rec->shipmentStoreId)){
    		$storeRec = store_Stores::fetch($rec->shipmentStoreId);
    		if($storeRec->autoShare == 'yes'){
    			$rec->sharedUsers = keylist::merge($rec->sharedUsers, $storeRec->chiefs);
    		}
    	}
    		
    	// Ако има каса се нотифицира касиера
    	if(isset($rec->caseId)){
    		$caseRec = cash_Cases::fetch($rec->caseId);
    		if($caseRec->autoShare == 'yes'){
    			$rec->sharedUsers = keylist::merge($rec->sharedUsers, $caseRec->cashiers);
    		}
    	}
    	
    	if($rec->initiatorId){
    		$rec->sharedUsers = keylist::merge($rec->sharedUsers, $rec->initiatorId);
    	}
    	
    	if(isset($rec->dealerId)){
    		$rec->sharedUsers = keylist::merge($rec->sharedUsers, $rec->dealerId);
    	}
    	
    	// Текущия потребител се премахва от споделянето
    	$rec->sharedUsers = keylist::removeKey($rec->sharedUsers, core_Users::getCurrent());
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->state != 'draft'){
    		$state = $rec->state;
    		$rec = $mvc->fetch($id);
    		$rec->state = $state;
    		
    		// Записване на сделката в чакащи
    		deals_OpenDeals::saveRec($rec, $mvc);
    	}
    }
    
    
	/**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
        $handle = $this->getHandle($id);
        $title = tr(mb_strtolower($this->singleTitle));
        
        $tpl = new ET(tr("|Моля запознайте се с нашата|* {$title}") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Помощна ф-я показваща дали в сделката има поне един складируем/нескладируем артикул
     * 
     * @param int $id - ид на сделка
     * @param boolean $storable - дали се търсят складируеми или нескладируеми артикули
     * @return boolean TRUE/FALSE - дали има поне един складируем/нескладируем артикул
     */
    public function hasStorableProducts($id, $storable = TRUE)
    {
    	$rec = $this->fetchRec($id);
    	
    	$Detail = $this->mainDetail;
    	$dQuery = $this->{$Detail}->getQuery();
    	$dQuery->where("#{$this->{$Detail}->masterKey} = {$rec->id}");
    	
    	while($d = $dQuery->fetch()){
        	$info = cat_Products::getProductInfo($d->productId);
        	if($storable){
        		
        		// Връща се TRUE ако има поне един складируем продукт
        		if(isset($info->meta['canStore'])) return TRUE;
        	} else {
        		
        		// Връща се TRUE ако има поне един НЕ складируем продукт
        		if(!isset($info->meta['canStore']))return TRUE;
        	}
        }
        
        return FALSE;
    }
     
    
     /**
      * Перо в номенклатурите, съответстващо на този продукт
      *
      * Част от интерфейса: acc_RegisterIntf
      */
     static function getItemRec($objectId)
     {
     	$result = NULL;
     	$self = cls::get(get_called_class());
     
     	if ($rec = $self->fetch($objectId)) {
     		$contragentName = cls::get($rec->contragentClassId)->getTitleById($rec->contragentId, FALSE);
     		$result = (object)array(
     				'num' => $objectId . " " . mb_strtolower($self->abbr),
     				'title' => $self::getRecTitle($objectId),
     				'features' => array('Контрагент' => $contragentName)
     		);
     		
     		if($rec->dealerId){
     			$caption = $self->getField('dealerId')->caption;
     			list(, $featName) = explode("->", $caption);
     			$result->features[$featName] = $self->getVerbal($rec, 'dealerId');
     		}
     		
     		if($rec->deliveryLocationId){
     			$result->features['Локация'] = crm_Locations::getTitleById($rec->deliveryLocationId, FALSE);
     		}
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
     * Документа винаги може да се активира, дори и да няма детайли
     */
    public static function canActivate($rec)
    {
    	return TRUE;
    }


    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	// Ако потребителя не е в група доставчици го включваме
    	$rec = $mvc->fetchRec($rec);
    	cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $mvc->crmDefGroup);
    	
    	// След активиране се обновяват толеранса и срока на детайлите
    	$saveRecs = array();
    	$Detail = cls::get($mvc->mainDetail);
    	$dQuery = $Detail->getQuery();
    	$dQuery->where("#{$Detail->masterKey} = {$rec->id}");
    	$dQuery->where("#tolerance IS NULL || #term IS NULL");
    	
    	while($dRec = $dQuery->fetch()){
    		$save = FALSE;
    		
    		if(!isset($dRec->term)){
    			if($term = cat_Products::getDeliveryTime($dRec->productId, $dRec->quantity)){
    				$dRec->term = $term;
    				$save = TRUE;
    			}
    		}
    	
    		if(!isset($dRec->tolerance)){
    			if($tolerance = cat_Products::getTolerance($dRec->productId, $dRec->quantity)){
    				$dRec->tolerance = $tolerance;
    				$save = TRUE;
    			}
    		}
    	
    		if($save === TRUE){
    			$saveRecs[] = $dRec;
    		}
    	}
    	 
    	// Ако има детайли за обновяване
    	if(count($saveRecs)){
    		$Detail->saveArray($saveRecs, 'id,tolerance,term');
    	}
    	
    	$update = FALSE;
    	
    	// Запис на адреса
    	if(empty($rec->deliveryAdress) && isset($rec->deliveryTermId)){
    		$update = TRUE;
    		
    		$rec->tplLang = $mvc->pushTemplateLg($rec->template);
    		$rec->deliveryAdress = cond_DeliveryTerms::addDeliveryTermLocation($rec->deliveryTermId, $rec->contragentClassId, $rec->contragentId, $rec->shipmentStoreId, $rec->deliveryLocationId, $mvc);
    		core_Lg::pop($rec->tplLang);
    	}
    	
    	// Записване на най-големия срок на доставка
    	if(empty($rec->deliveryTime) && empty($rec->deliveryTermTime)){
    		$rec->deliveryTermTime = $mvc->getMaxDeliveryTime($rec->id);
    		if(isset($rec->deliveryTermTime)){
    			$update = TRUE;
    		}
    	}
    	
    	$saveFields = 'searchKeywords';
    	$rec->searchKeywords = $mvc->updateSearchKeywords($rec);
    	if($update === TRUE){
    		$saveFields .= ',deliveryTermTime,deliveryAdress';
    	}
    	core_Statuses::newStatus($rec->searchKeywords, 'warning');
    	$mvc->save_($rec, $saveFields);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$amountType = $mvc->getField('amountDeal')->type;
		if($rec->state == 'active'){
			$rec->amountToDeliver = round($rec->amountDeal - $rec->amountDelivered, 2);
			$rec->amountToPay = round($rec->amountDelivered - $rec->amountPaid, 2);
			$rec->amountToInvoice = $rec->amountDelivered - $rec->amountInvoiced;
		}
		
		$actions = type_Set::toArray($rec->contoActions);
		
		foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced', 'ToPay', 'ToDeliver', 'ToInvoice', 'Bl') as $amnt) {
            if (round($rec->{"amount{$amnt}"}, 2) == 0) {
            	$coreConf = core_Packs::getConfig('core');
            	$pointSign = $coreConf->EF_NUMBER_DEC_POINT;
            	$row->{"amount{$amnt}"} = '<span class="quiet">0' . $pointSign . '00</span>';
            } else {
            	if(!empty($rec->currencyRate)){
            		$value = round($rec->{"amount{$amnt}"} / $rec->currencyRate, 2);
            	} else {
            		$value = round($rec->{"amount{$amnt}"}, 2);;
            	}
            	
            	$row->{"amount{$amnt}"} = $amountType->toVerbal($value);
            }
        }
        
        foreach (array('ToPay', 'ToDeliver', 'ToInvoice', 'Bl') as $amnt){
        	if(round($rec->{"amount{$amnt}"}, 2) == 0) continue;
        	
        	$color = (round($rec->{"amount{$amnt}"}, 2) < 0) ? 'red' : 'green';
        	$row->{"amount{$amnt}"} = "<span style='color:{$color}'>{$row->{"amount{$amnt}"}}</span>";
        }
        
        // Ревербализираме платежното състояние, за да е в езика на системата а не на шаблона
        $row->paymentState = $mvc->getVerbal($rec, 'paymentState');
       
        if($rec->paymentState == 'overdue' || $rec->paymentState == 'repaid'){
			$row->amountPaid = "<span style='color:red'>" . strip_tags($row->amountPaid) . "</span>";
        	$row->paymentState = "<span style='color:red'>{$row->paymentState}</span>";
        }
	    
    	if(isset($rec->dealerId)){
    		$row->dealerId = crm_Profiles::createLink($rec->dealerId, $row->dealerId);
    	}
    	
    	if(isset($rec->initiatorId)){
    		$row->initiatorId = crm_Profiles::createLink($rec->initiatorId, $row->initiatorId);
    	}
    	
	    if($fields['-single']){
	    	if(core_Users::haveRole('partner')){
	    		unset($row->closedDocuments);
	    		unset($row->initiatorId);
	    		unset($row->dealerId);
	    	}
	    	
	    	if($rec->originId){
	    		$row->originId = doc_Containers::getDocument($rec->originId)->getHyperLink();
	    	}
	    	
	    	if($rec->deliveryLocationId){
	    		$row->deliveryLocationId = crm_Locations::getHyperlink($rec->deliveryLocationId);
	    		$row->deliveryLocationIdTop = $row->deliveryLocationId;
	    	}
	    	
	    	if($rec->deliveryTime){
	    		if(strstr($rec->deliveryTime, ' 00:00') !== FALSE){
	    			$row->deliveryTime = cls::get('type_Date')->toVerbal($rec->deliveryTime);
	    		}
	    	}
	    	
	    	$cuNames = core_Users::getVerbal($rec->createdBy, 'names');
	    	(core_Users::haveRole('partner', $rec->createdBy)) ? $row->responsible = $cuNames : $row->username = $cuNames;
	    	
		    // Ако валутата е основната валута да не се показва
		    if($rec->currencyId != acc_Periods::getBaseCurrencyCode($rec->valior)){
		    	$row->currencyCode = $row->currencyId;
		    }
	        
	    	if($rec->note){
				$notes = explode('<br>', $row->note);
				foreach ($notes as $note){
					$row->notes .= "<li>{$note}</li>";
				}
			}
			
			// Показване на допълнителните условия от артикулите
			$additionalConditions = deals_Helper::getConditionsFromProducts($mvc->mainDetail, $rec->id);
			if(is_array($additionalConditions)){
				foreach ($additionalConditions as $cond){
					$row->notes .= "<li>{$cond}</li>";
				}
			}
			
			// Взависимост начислява ли се ддс-то се показва подходящия текст
			switch($rec->chargeVat){
				case 'yes':
					$fld = 'withVat';
					break;
				case 'separate':
					$fld = 'sepVat';
					break;
				default:
					$fld = 'noVat';
					break;
			}
			$row->{$fld} = ' ';
			
			if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
				if($rec->shipmentStoreId){
					$storeVerbal = store_Stores::getHyperlink($rec->shipmentStoreId);
					if($rec->state == 'active' && isset($actions['ship'])){
						$row->shipmentStoreId = $storeVerbal;
					} else {
						unset($row->shipmentStoreId);
					}
					
					$row->shipmentStoreIdTop = $storeVerbal;
				}
				
				if($rec->caseId){
					$row->caseId = cash_Cases::getHyperlink($rec->caseId);
				}
			}

			core_Lg::push($rec->tplLang);
			$deliveryAdress = '';
			if(!empty($rec->deliveryAdress)){
				$deliveryAdress .= $mvc->getFieldType('deliveryAdress')->toVerbal($rec->deliveryAdress);
			} else {
				if(isset($rec->deliveryTermId)){
					$deliveryAdress .= cond_DeliveryTerms::addDeliveryTermLocation($rec->deliveryTermId, $rec->contragentClassId, $rec->contragentId, $rec->shipmentStoreId, $rec->deliveryLocationId, $mvc);
					$deliveryAdress = ht::createHint($deliveryAdress, 'Адреса за доставка ще се запише при активиране');
				}
			}
			
			if(!empty($deliveryAdress)){
				$deliveryAdress1 = (isset($rec->deliveryTermId)) ? (cond_DeliveryTerms::fetchField($rec->deliveryTermId, 'codeName') . ": ") : "";
				$deliveryAdress = $deliveryAdress1 . $deliveryAdress;
				$row->deliveryTermId = $deliveryAdress;
			}
			
			// Подготовка на имената на моята фирма и контрагента
			$headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId);
			$row = (object)((array)$row + (array)$headerInfo);
			
			if(isset($actions['ship'])){
				$row->isDelivered .= mb_strtoupper(tr('доставено'));
				if($rec->state == 'rejected') {
					$row->isDelivered = "<span class='quiet'>{$row->isDelivered}</span>";
				}
			}
			
			if(isset($actions['pay'])){
				$row->isPaid .= mb_strtoupper(tr('платено'));
				if($rec->state == 'rejected') {
					$row->isPaid = "<span class='quiet'>{$row->isPaid}</span>";
				}
			}
			
			// За да се използва езика на фактурата
			$noInvStr = tr('без фактуриране');
			
			$row->username = core_Lg::transliterate($row->username);
			$row->responsible = core_Lg::transliterate($row->responsible);
			
			if(empty($rec->deliveryTime) && empty($rec->deliveryTermTime)){
				$deliveryTermTime = $mvc->getMaxDeliveryTime($rec->id);
				if($deliveryTermTime){
					$deliveryTermTime = cls::get('type_Time')->toVerbal($deliveryTermTime);
					$row->deliveryTermTime = ht::createHint($deliveryTermTime, 'Времето за доставка се изчислява динамично възоснова на най-големия срок за доставка от артикулите');
				}
			}
			
			core_Lg::pop();
	    }
	    
        if($rec->makeInvoice == 'no') {
            if (!$noInvStr) {
                $noInvStr = tr('без фактуриране');
            }
			$row->amountToInvoice = "<span style='font-size:0.7em'>" . $noInvStr . "</span>";
		}
    }
    
    
    /**
     * Най-големия срок на доставка
     * 
     * @param int $id
     * @return int|NULL
     */
    public function getMaxDeliveryTime($id)
    {
    	$maxDeliveryTime = NULL;
    	
    	$Detail = cls::get($this->mainDetail);
    	$query = $Detail->getQuery();
    	$query->where("#{$Detail->masterKey} = {$id}");
    	$query->show("productId,term,quantity,{$Detail->masterKey}");
    	
    	while($rec = $query->fetch()){
    		$term = $rec->term;
    		if(!isset($term)){
    			if($term = cat_Products::getDeliveryTime($rec->productId, $rec->quantity)){
    				$cRec = tcost_Calcs::get($this, $rec->{$Detail->masterKey}, $rec->id);
    				if(isset($cRec->deliveryTime)){
    					$term = $cRec->deliveryTime + $term;
    				}
    			}
    		}
    		
    		if(isset($term)){
    			$maxDeliveryTime = max($maxDeliveryTime, $term);
    		}
    	}
    	
    	return $maxDeliveryTime;
    }
    
    
    /**
     * След промяна в журнала със свързаното перо
     */
    public static function on_AfterJournalItemAffect($mvc, $rec, $item)
    {
    	$aggregateDealInfo = $mvc->getAggregateDealInfo($rec->id);
    	$Detail = $mvc->mainDetail;
    	
    	$oldPaid = $rec->amountPaid;
    	$oldDelivered = $rec->amountDelivered;
    	// Преизчисляваме общо платената и общо експедираната сума
    	$rec->amountPaid      = $aggregateDealInfo->get('amountPaid');
    	$rec->amountDelivered = $aggregateDealInfo->get('deliveryAmount');
    	$rec->amountBl 		  = $aggregateDealInfo->get('blAmount');
    	$rec->amountInvoiced  = $aggregateDealInfo->get('invoicedAmount');
    	
    	if(!empty($rec->closedDocuments)){
    		
    		// Ако документа приключва други сделки, събираме им фактурираното и го добавяме към текущата
    		$closed = keylist::toArray($rec->closedDocuments);
    		$invAmount = 0;
    		foreach ($closed as $docId){
    			$dInfo = $mvc->getAggregateDealInfo($docId);
    			$invAmount  += $dInfo->get('invoicedAmount');
    		}
    		$rec->amountInvoiced += $invAmount;
    	} else {
    		$rec->amountInvoiced = $aggregateDealInfo->get('invoicedAmount');
    	}
    	
    	$rec->paymentState = $mvc->getPaymentState($aggregateDealInfo);
    	$rec->modifiedOn = dt::now();
    	
    	$cRec = doc_Containers::fetch($rec->containerId);
    	$cRec->modifiedOn = $rec->modifiedOn;
    	
    	cls::get('doc_Containers')->save_($cRec, 'modifiedOn');
    	$mvc->save_($rec);
    	
    	deals_OpenDeals::saveRec($rec, $mvc);
    }
    
   
    /**
     * Ако с тази сделка е приключена друга сделка
     */
    public static function on_AfterClosureWithDeal($mvc, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	
    	// Намираме всички продажби които са приключени с тази
    	$details = array();
    	$ClosedDeal = $mvc->closeDealDoc;
    	$closedDeals = $ClosedDeal::getClosedWithDeal($rec->id);
    	
    	$closedIds = array();
    	if(count($closedDeals)){
    		
    		// За всяка от тях, включително и този документ
    		foreach ($closedDeals as $doc){
    
    			// Взимаме договорените продукти от сделката начало на нейната нишка
    			$firstDoc = doc_Threads::getFirstDocument($doc->threadId);
    			$dealInfo = $firstDoc->getAggregateDealInfo();
    			$id = $firstDoc->fetchField('id');
    			$closedIds[$id] = $id;
    			
    			$products = (array)$dealInfo->get('dealProducts');
    			if(count($products)){
    				$details[] = $products;
    			}
    		}
    	}

    	// Изтриваме досегашните детайли на сделката
    	$Detail = $mvc->mainDetail;
    	$Detail::delete("#{$mvc->{$Detail}->masterKey} = {$rec->id}");
    	
    	$details = deals_Helper::normalizeProducts($details);
    	
    	if(count($details)){
    		foreach ($details as &$det1){
    			$det1->{$mvc->{$Detail}->masterKey} = $rec->id;
    			$Detail::save($det1);
    		}
    	}
    	
    	if(count($closedIds)){
    		$closedIds = keylist::fromArray($closedIds);
    		$rec->closedDocuments = $closedIds;
    	} else {
    		unset($rec->closedDocuments);
    	}
    	
    	$mvc->save($rec, 'closedDocuments');
    }
    

    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
    	$mvc->setCron($res);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$res = '';
    	$this->setTemplates($res);
    	
    	return $res;
    }
    
    
    /**
     * Преди рендиране на тулбара
     */
    public static function on_BeforeRenderSingleToolbar($mvc, &$res, &$data)
    {
    	$rec = &$data->rec;
    	 
    	// Ако има опции за избор на контирането, подмяна на бутона за контиране
    	if(isset($data->toolbar->buttons['btnConto'])){
    		$options = $mvc->getContoOptions($rec->id);
    		if(count($options)){
    			$data->toolbar->removeBtn('btnConto');
    
    			// Проверка на счетоводния период, ако има грешка я показваме
    			if(!acc_plg_Contable::checkPeriod($rec->valior, $error)){
    				$error = ",error={$error}";
    			}
    			 
    			$data->toolbar->addBtn('Активиране', array($mvc, 'chooseAction', $rec->id), "id=btnConto{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Активиране на документа');
    		}
    	}
    }


    /**
     * Какви операции ще се изпълнят с контирането на документа
     * @param int $id - ид на документа
     * @return array $options - опции
     */
    public static function on_AfterGetContoOptions($mvc, &$res, $id)
    {
    	$options = array();
    	$rec = $mvc->fetchRec($id);
    	 
    	// Заглавие за опциите, взависимост дали е покупка или сделка
    	$opt = ($mvc instanceof sales_Sales) ? self::$contoMap['sales'] : self::$contoMap['purchase'];
    	 
    	// Имали складируеми продукти
    	$hasStorable = $mvc->hasStorableProducts($rec->id);
    	 
    	// Ако има продукти за експедиране
    	if($hasStorable){
    
    		// ... и има избран склад, и потребителя може да се логне в него
    		if(isset($rec->shipmentStoreId) && store_Stores::haveRightFor('select', $rec->shipmentStoreId)){
    	   
    			// Ако има очаквано авансово плащане, не може да се експедира на момента
    			if(cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
    				$hasDp = TRUE;
    			}
    	   
    			if(empty($hasDp)){
    
    				// .. продуктите може да бъдат експедирани
    				$storeName = store_Stores::getTitleById($rec->shipmentStoreId);
    				$options['ship'] = "{$opt['ship']}\"{$storeName}\"";
    			}
    		}
    	} else {
    
    		// ако има услуги те могат да бъдат изпълнени
    		if($mvc->hasStorableProducts($rec->id, FALSE)){
    			$options['ship'] = $opt['service'];
    		}
    	}
    	
    	// ако има каса, метода за плащане е COD и текущия потрбител може да се логне в касата
    	if($rec->amountDeal && isset($rec->caseId) && cond_PaymentMethods::isCOD($rec->paymentMethodId) && cash_Cases::haveRightFor('select', $rec->caseId)){
    
    		// Може да се плати с продуктите
    		$caseName = cash_Cases::getTitleById($rec->caseId);
    		$options['pay'] = "{$opt['pay']} \"$caseName\"";
    	}
    	 
    	$res = $options;
    }
    
    
    /**
     * Екшън за избор на контиращо действие
     */
    public function act_Chooseaction()
    {
    	$id = Request::get('id', 'int');
    	expect($rec = $this->fetch($id));
    	
    	if($rec->state != 'draft' && $rec->state != 'pending'){
    		return new Redirect(array($this, 'single', $id), '|Договорът вече е активиран');
    	}
    	
    	expect(cls::haveInterface('acc_TransactionSourceIntf', $this));
    	expect(acc_plg_Contable::checkPeriod($rec->valior, $error), $error);
    	$curStoreId = store_Stores::getCurrent('id', FALSE);
    	$curCaseId  = cash_Cases::getCurrent('id', FALSE);
    	
    	// Трябва потребителя да може да контира
    	$this->requireRightFor('conto', $rec);
    	
    	// Подготовка на формата за избор на опция
    	$form = cls::get('core_Form');
    	$form->title = "|Активиране на|* <b>" . $this->getTitleById($id). "</b>" . " ?";
    	$form->info = tr('|*<b>|Контиране на извършени на момента действия|*</b> (|опционално|*):');
    	
    	// Извличане на позволените операции
    	$options = $this->getContoOptions($rec);
    	$hasSelectedBankAndCase = !empty($rec->bankAccountId) && !empty($rec->caseId);
    	if($hasSelectedBankAndCase === TRUE){
    		$form->info .= tr("|*<br><span style='color:darkgreen'>|Избрани са едновременно каса и банкова сметка! Потвърдете че плащането е на момента или редактирайте сделката|*.</span>");
    	}
    	
    	// Трябва да има избор на действие
    	expect(count($options));
    	
    	// Подготовка на полето за избор на операция и инпут на формата
    	$form->FNC('action', cls::get('type_Set', array('suggestions' => $options)), 'columns=1,input,caption=Изберете');
    	$map = ($this instanceof sales_Sales) ? self::$contoMap['sales'] : self::$contoMap['purchase'];
    	
    	$selected = array();
    	
    	// Ако има склад и експедиране и потребителя е логнат в склада, слагаме отметка
    	if($options['ship'] && $rec->shipmentStoreId){
    		if($rec->shipmentStoreId === $curStoreId && $map['service'] != $options['ship']){
    			$selected[] = 'ship';
    		}
    	} elseif($options['ship']){
    		$selected[] = 'ship';
    	}
    	
    	// Ако има каса и потребителя е логнат в нея, Слагаме отметка
    	if($options['pay'] && $rec->caseId){
    		if($rec->caseId === $curCaseId && $hasSelectedBankAndCase === FALSE){
    			$selected[] = 'pay';
    		}
    	}
    	
    	$form->setDefault('action', implode(',', $selected));
    	$form->input();
    	
    	// След като формата се изпрати
    	if($form->isSubmitted()){
    		 
    		// обновяване на записа с избраните операции
    		$form->rec->action = 'activate' . (($form->rec->action) ? "," : "") . $form->rec->action;
    		$rec->contoActions = $form->rec->action;
    		$rec->isContable = ($form->rec->action == 'activate') ? 'activate' : 'yes';
    		$this->save($rec);
    		 
    		// Ако се експедира и има склад, форсира се логване
    		if($options['ship'] && isset($rec->shipmentStoreId) && $rec->shipmentStoreId != $curStoreId){
    			store_Stores::selectCurrent($rec->shipmentStoreId);
    		}
    		 
    		// Ако има сметка и се експедира, форсира се логване
    		if($options['pay'] && isset($rec->caseId) && $rec->caseId != $curCaseId){
    			cash_Cases::selectCurrent($rec->caseId);
    		}
    		 
    		// Контиране на документа
    		$this->conto($id);
    		 
    		$this->logWrite("Активиране/Контиране на сделка", $id);
    		
    		// Редирект
    		return new Redirect(array($this, 'single', $id));
    	}
    	
    	$form->toolbar->addSbBtn('Активиране/Контиране', 'save', 'ef_icon = img/16/tick-circle-frame.png');
    	$form->toolbar->addBtn('Отказ', array($this, 'single', $id),  'ef_icon = img/16/close-red.png');
    	 
    	// Рендиране на формата
    	$tpl = $this->renderWrapping($form->renderHtml());
    	
    	return $tpl;
    }
    
    
    /**
     * Приключва остарялите сделки
     */
    public function closeOldDeals($olderThan, $closeDocName, $limit)
    {
    	$className = get_called_class();
    	
    	expect(cls::haveInterface('bgerp_DealAggregatorIntf', $className));
    	$query = $className::getQuery();
    	$ClosedDeals = cls::get($closeDocName);
    	$conf = core_Packs::getConfig('acc');
    	$tolerance = $conf->ACC_MONEY_TOLERANCE;
    	 
    	// Текущата дата
    	$now = dt::mysql2timestamp(dt::now());
    	$oldBefore = dt::timestamp2mysql($now - $olderThan);
    	 
    	// Всички нишки със заявка
    	$cQuery = doc_Containers::getQuery();
    	$cQuery->where("#state = 'pending'");
    	$cQuery->show('threadId');
    	$cQuery->groupBy('threadId');
    	$threadIds = arr::extractValuesFromArray($cQuery->fetchAll(), 'threadId');
    	
    	$query->EXT('threadModifiedOn', 'doc_Threads', 'externalName=last,externalKey=threadId');
    	if(count($threadIds)){
    	    $query->notIn("threadId", $threadIds);
    	}
    	
    	// Закръглената оставаща сума за плащане
    	$query->XPR('toInvoice', 'double', 'ROUND(#amountDelivered - #amountInvoiced, 2)');
    	 
    	// Само активни продажби
    	$query->where("#state = 'active'");
    	$query->where("#amountDelivered IS NOT NULL AND #amountPaid IS NOT NULL");
    	 
    	// Пропускат се и тези по които има още да се експедира
    	$query->where("#amountDeal <= #amountDelivered");
    	
    	// На които треда им не е променян от определено време
    	$query->where("#threadModifiedOn <= '{$oldBefore}'");
    	 
    	// Крайното салдо по сметката на сделката трябва да е в допустимия толеранс
    	$query->where("#amountBl BETWEEN -{$tolerance} AND {$tolerance}");
    	 
    	// Ако трябва да се фактурират и са доставеното - фактурираното е в допустими граници
    	$query->where("(#makeInvoice = 'yes' || #makeInvoice IS NULL) AND #toInvoice BETWEEN -{$tolerance} AND {$tolerance}");
    	 
    	// Или не трябва да се фактурират
    	$query->orWhere("#makeInvoice = 'no'");
    	
    	// Лимитираме заявката
    	$query->limit($limit);
    	 
    	// Всяка намерената сделка, се приключва като платена
    	while($rec = $query->fetch()){
    		
    		try{
    			 
    			// Създаване на приключващ документ-чернова
    			$clId = $ClosedDeals->create($className, $rec);
    			$ClosedDeals->conto($clId);
    			 
    		} catch(core_exception_Expect $e){
    			 reportException($e);
    		}
    	}
    }
    

    /**
     * Проверява дали сделките са с просрочено плащане
     */
    public function checkPayments($overdueDelay)
    {
    	$Class = cls::get(get_called_class());
    	$now = dt::now();
    	expect(cls::haveInterface('bgerp_DealAggregatorIntf', $Class));
    	 
    	// Проверяват се всички активирани и продажби с чакащо плащане или просрочените
    	$query = $Class->getQuery();
    	$query->where("#state = 'active'");
    	$query->where("ADDDATE(#modifiedOn, INTERVAL {$overdueDelay} SECOND) <= '{$now}'");
    	$query->show('id,amountDeal,amountPaid,amountDelivered,paymentState');
    	
    	while($rec = $query->fetch()){
    		try{
    			$dealInfo = $Class->getAggregateDealInfo($rec->id);
    			$rec->paymentState = $Class->getPaymentState($dealInfo);
    			$Class->save_($rec, 'paymentState');
    		} catch(core_exception_Expect $e){
                reportException($e);
    			continue;
    		}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	if($data->paymentPlan){
    		$tpl->placeObject($data->paymentPlan);
    	}
    }
    

   /*
    * API за генериране на сделка
    */
    
    
    /**
     * Метод за бързо създаване на чернова сделка към контрагент
     *
     * @param mixed $contragentClass - ид/инстанция/име на класа на котрагента
     * @param int $contragentId - ид на контрагента
     * @param array $fields - стойности на полетата на сделката
     *
     * 		o $fields['valior']             -  вальор (ако няма е текущата дата)
     * 		o $fields['reff']               -  вашия реф на продажбата
     * 		o $fields['currencyId']         -  код на валута (ако няма е основната за периода)
     * 		o $fields['currencyRate']       -  курс към валутата (ако няма е този към основната валута)
     * 		o $fields['paymentMethodId']    -  ид на платежен метод (Ако няма е плащане в брой, @see cond_PaymentMethods)
     * 		o $fields['chargeVat']          -  да се начислява ли ДДС - yes=Да, separate=Отделен ред за ДДС, exempt=Освободено,no=Без начисляване(ако няма, се определя според контрагента)
     * 		o $fields['shipmentStoreId']    -  ид на склад (@see store_Stores)
     * 		o $fields['deliveryTermId']     -  ид на метод на доставка (@see cond_DeliveryTerms)
     * 		o $fields['deliveryLocationId'] -  ид на локация за доставка (@see crm_Locations)
     * 		o $fields['deliveryTime']       -  дата на доставка
     * 		o $fields['dealerId']           -  ид на потребител търговец
     * 		o $fields['initiatorId']        -  ид на потребител инициатора (ако няма е отговорника на контрагента)
     * 		o $fields['caseId']             -  ид на каса (@see cash_Cases)
     * 		o $fields['note'] 				-  бележки за сделката
     * 		o $fields['originId'] 			-  източник на документа
     *		o $fields['makeInvoice'] 		-  изисквали се фактура или не (yes = Да, no = Не), По дефолт 'yes'
     *		o $fields['template'] 		-  бележки за сделката
     *
     * @return mixed $id/FALSE - ид на запис или FALSE
     */
    public static function createNewDraft($contragentClass, $contragentId, $fields = array())
    {
    	$contragentClass = cls::get($contragentClass);
    	expect($cRec = $contragentClass->fetch($contragentId));
    	expect($cRec->state != 'rejected');
    	 
    	// Намираме всички полета, които не са скрити или не се инпутват, те са ни позволените полета
    	$me = cls::get(get_called_class());
    	$fields = arr::make($fields);
    	$allowedFields = $me->selectFields("#input != 'none' AND #input != 'hidden'");
    	$allowedFields['originId'] = TRUE;
    	$allowedFields['currencyRate'] = TRUE;
    	$allowedFields['deliveryTermId'] = TRUE;
    	
    	// Проверяваме подадените полета дали са позволени
    	if(count($fields)){
    		foreach ($fields as $fld => $value){
    			expect(array_key_exists($fld, $allowedFields), $fld);
    		}
    	}
    	 
    	// Ако има склад, съществува ли?
    	if(isset($fields['shipmentStoreId'])){
    		expect(store_Stores::fetch($fields['shipmentStoreId']));
    	}
    	
    	// Ако има каса, съществува ли?
    	if(isset($fields['caseId'])){
    		expect(cash_Cases::fetch($fields['caseId']));
    	}
    	
    	// Ако има условие на доставка, съществува ли?
    	if(isset($fields['deliveryTermId'])){
    		expect(cond_DeliveryTerms::fetch($fields['deliveryTermId']));
    	}
    	
    	// Ако има платежен метод, съществува ли?
    	if(isset($fields['paymentMethodId'])){
    		expect(cond_PaymentMethods::fetch($fields['paymentMethodId']));
    	}
    	
    	// Форсираме папката на клиента
    	$fields['folderId'] = $contragentClass::forceCoverAndFolder($contragentId);
    	
    	// Ако е зададен шаблон, съществува ли?
    	if(isset($fields['template'])){
    		expect(doc_TplManager::fetch($fields['template']));
    	} elseif($me instanceof sales_Sales){
    		$fields['template'] = $me->getDefaultTemplate((object)array('folderId' => $fields['folderId']));
    	}
    	
    	// Ако не е подадена дата, това е сегашната
    	$fields['valior'] = (empty($fields['valior'])) ? dt::today() : $fields['valior'];
    	 
    	// Записваме данните на контрагента
    	$fields['contragentClassId'] = $contragentClass->getClassId();
    	$fields['contragentId'] = $contragentId;
    	 
    	// Ако няма валута, това е основната за периода
    	$fields['currencyId'] = (empty($fields['currencyId'])) ? acc_Periods::getBaseCurrencyCode($fields['valior']) : $fields['currencyId'];
    	 
    	// Ако няма курс, това е този за основната валута
        
    	if (empty($fields['currencyRate'])) {
    	    $fields['currencyRate'] = currency_CurrencyRates::getRate($fields['currencyRate'], $fields['currencyId'], NULL);
    	    expect($fields['currencyRate']);
    	}
    	
    	// Ако няма платежен план, това е плащане в брой
    	$paymentSysId = (get_called_class() == 'sales_Sales') ? 'paymentMethodSale' : 'paymentMethodPurchase';
    	$fields['paymentMethodId'] = (empty($fields['paymentMethodId'])) ? cond_Parameters::getParameter($contragentClass, $contragentId, $paymentSysId) : $fields['paymentMethodId'];
    	 
    	$termSysId = (get_called_class() == 'sales_Sales') ? 'deliveryTermSale' : 'deliveryTermPurchase';
    	$fields['deliveryTermId'] = (empty($fields['deliveryTermId'])) ? cond_Parameters::getParameter($contragentClass, $contragentId, $termSysId) : $fields['deliveryTermId'];
    	
    	// Ако няма търговец, това е текущия потребител
    	$fields['dealerId'] = (empty($fields['dealerId'])) ? core_Users::getCurrent() : $fields['dealerId'];
    	 
    	// Ако няма инициатор, това е отговорника на контрагента
    	$fields['initiatorId'] = (empty($fields['initiatorId'])) ? $contragentClass::fetchField($contragentId, 'inCharge') : $fields['initiatorId'];
    	 
    	// Ако не е подадено да се начислявали ддс, определяме от контрагента
    	if(empty($fields['chargeVat'])){
    		$fields['chargeVat'] = ($contragentClass::shouldChargeVat($contragentId)) ? 'yes' : 'no';
    	}
    	 
    	// Ако не е подадено да се начислявали ддс, определяме от контрагента
    	if(empty($fields['makeInvoice'])){
    		$fields['makeInvoice'] = 'yes';
    	}
    	
    	// Състояние на плащането, чакащо
    	$fields['paymentState'] = 'pending';
    	
    	// Опиваме се да запишем мастъра на сделката
    	if($id = $me->save((object)$fields)){
    		
    		// Ако е успешно, споделяме текущия потребител към новосъздадената нишка
    		$rec = $me->fetchField($id);
    		doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, core_Users::getCurrent());
    
    		return $id;
    	}
    	 
    	return FALSE;
    }

    
    /**
     * Добавя нов ред в главния детайл на чернова сделка.
     * Ако има вече такъв артикул добавен към сделката, наслагва к-то, цената и отстъпката
     * на новия запис към съществуващия (цените и отстъпките стават по средно притеглени)
     * 
     * @param int $id 			   - ид на сделка
     * @param int $productId	   - ид на артикул
     * @param double $packQuantity - количество продадени опаковки (ако няма опаковки е цялото количество)
     * @param double $price        - цена на единична бройка (ако не е подадена, определя се от политиката)
     * @param int $packagingId     - ид на опаковка (не е задължителна)
     * @param double $discount     - отстъпка между 0(0%) и 1(100%) (не е задължителна)
     * @param double $tolerance    - толеранс между 0(0%) и 1(100%) (не е задължителен)
     * @param string $term         - срок (не е задължителен)
     * @param text $notes          - забележки
     * @return mixed $id/FALSE     - ид на запис или FALSE
     */
    public static function addRow($id, $productId, $packQuantity, $price = NULL, $packagingId = NULL, $discount = NULL, $tolerance = NULL, $term = NULL, $notes = NULL)
    {
    	$me = cls::get(get_called_class());
    	$Detail = cls::get($me->mainDetail);
    	
    	expect($rec = $me->fetch($id));
    	expect($rec->state == 'draft');
    	
    	// Дали отстъпката е между 0 и 1
    	if(isset($discount)){
    		expect($discount >= 0 && $discount <= 1);
    	}
    	
    	// Дали толеранса е между 0 и 1
    	if(isset($tolerance)){
    		expect($tolerance >= 0 && $tolerance <= 1);
    	}
    	
    	if(!empty($term)){
    		expect($term = cls::get('type_Time')->fromVerbal($term));
    	}
    	
    	// Трябва да има такъв продукт и опаковка
    	expect(cat_Products::fetchField($productId, 'id'));
    	if(isset($packagingId)){
    		expect(cat_UoM::fetchField($packagingId, 'id'));
    	}
    	
    	if(isset($notes)){
    		$notes = cls::get('type_Richtext')->fromVerbal($notes);
    	}
    	
    	// Броя еденици в опаковка, се определя от информацията за продукта
    	$productInfo = cat_Products::getProductInfo($productId);
    	if(!$packagingId){
    		$packagingId = $productInfo->productRec->measureId;
    	}
    	
    	$quantityInPack = ($productInfo->packagings[$packagingId]) ? $productInfo->packagings[$packagingId]->quantity : 1;
    	
    	// Ако няма цена, опитваме се да я намерим от съответната ценова политика
    	if(empty($price)){
    		$Policy = (isset($Detail->Policy)) ? $Detail->Policy : cls::get('price_ListToCustomers');
    		$policyInfo = $Policy->getPriceInfo($rec->contragentClassId, $rec->contragentId, $productId, $packagingId, $quantityInPack * $packQuantity);
    		$price = $policyInfo->price;
    		if(!isset($discount) && isset($policyInfo->discount)){
    			$discount = $policyInfo->discount;
    		}
    	}
    	
    	$packQuantity = cls::get('type_Double')->fromVerbal($packQuantity);
    	
    	// Подготвяме детайла
    	$dRec = (object)array($Detail->masterKey => $id, 
    						  'productId'        => $productId,
    						  'packagingId'      => $packagingId,
    						  'quantity'         => $quantityInPack * $packQuantity,
    						  'discount'         => $discount,
    						  'tolerance'		 => $tolerance,
    						  'term'		     => $term,
    						  'price'            => $price,
    						  'quantityInPack'   => $quantityInPack,
    						  'notes'			 => $notes,
    	);
    	
    	// Проверяваме дали въвдения детайл е уникален
    	$where = "#{$Detail->masterKey} = {$id} AND #productId = {$dRec->productId}";
    	if($packagingId){
    		$where .= " AND #packagingId = {$packagingId}";
    	} else {
    		$where .= " AND #packagingId IS NULL";
    	}
    	
    	if($exRec = $Detail->fetch($where)){
    		
    		// Смятаме средно притеглената цена и отстъпка
    		$nPrice = ($exRec->quantity * $exRec->price +  $dRec->quantity * $dRec->price) / ($dRec->quantity + $exRec->quantity);
    		$nDiscount = ($exRec->quantity * $exRec->discount +  $dRec->quantity * $dRec->discount) / ($dRec->quantity + $exRec->quantity);
    		$nTolerance = ($exRec->quantity * $exRec->tolerance +  $dRec->quantity * $dRec->tolerance) / ($dRec->quantity + $exRec->quantity);
    		
    		// Ъпдейтваме к-то, цената и отстъпката на записа с новите
    		if($term){
    			$exRec->term = max($exRec->term, $dRec->term);
    		}
    		
    		$exRec->quantity += $dRec->quantity;
    		$exRec->price = $nPrice;
    		$exRec->discount = (empty($nDiscount)) ? NULL : round($nDiscount, 2);
    		$exRec->tolerance = (empty($nTolerance)) ? NULL : round($nTolerance, 2);
    		
    		// Ъпдейтваме съществуващия запис
    		$id = $Detail->save($exRec);
    	} else {
    		
    		// Ако е уникален, добавяме го
    		$id = $Detail->save($dRec);
    	}
    	
    	// Връщаме резултата от записа
    	return $id;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
		// Не може да се клонира ако потребителя няма достъп до папката
    	if($action == 'clonerec' && isset($rec)){
    		
    		// Ако е контрактор може да клонира документите в споделените му папки
    		if(core_Packs::isInstalled('colab') && core_Users::haveRole('partner', $userId)){
    			$colabFolders = colab_Folders::getSharedFolders($userId);
    			
    			if(!in_array($rec->folderId, $colabFolders)){
    				$res = 'no_one';
    			}
    		} else {
    			
    			// Ако не е контрактор, трябва да има достъп до папката
    			if(!doc_Folders::haveRightToFolder($rec->folderId, $userId)){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Документа не може да се прави на заявка/чернова ако няма поне един детайл
    	if($action == 'pending' && isset($rec)){
    		if($res != 'no_one'){
    			$Detail = cls::get($mvc->mainDetail);
    			if(empty($rec->id)){
    				$res = 'no_one';
    			} elseif(!$Detail->fetch("#{$Detail->masterKey} = '{$rec->id}'")){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'changerate' && isset($rec)){
    		if($rec->currencyId == 'BGN' || $rec->currencyId == 'EUR'){
    			$res = 'no_one';
    		} elseif($rec->state == 'closed' || $rec->state == 'rejected'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Екшън показващ форма за избор на чернова бележка от папката на даден контрагент
     */
    public function act_ChooseDraft()
    {
    	$this->requireRightFor('edit');
    	$contragentClassId = Request::get('contragentClassId', 'int');
    	$contragentId = Request::get('contragentId', 'int');
    	
    	$query = $this->getQuery();
    	$query->where("#state = 'draft' AND #contragentId = {$contragentId} AND #contragentClassId = {$contragentClassId}");
    	
    	$options = array();
    	while($rec = $query->fetch()){
    		if($this->haveRightFor('single', $rec)){
    			$options[$rec->id] = $this->getTitleById($rec->id, TRUE);
    		}
    	}
    	
    	$retUrl = getRetUrl();
    	
    	// Ако няма опции, връщаме се назад
    	if(!count($options)){
    		$retUrl['stop'] = TRUE;
    		
    		return new Redirect($retUrl);
    	}
    	
    	// Подготвяме и показваме формата за избор на чернова оферта, ако има чернови
    	$me = get_called_class();
    	$form = cls::get('core_Form');
    	$form->title = "|Прехвърляне в|* " . mb_strtolower($this->singleTitle);
    	$form->FLD('dealId', "key(mvc={$me},select=id,allowEmpty)", "caption={$this->singleTitle},mandatory");
    	$form->setOptions('dealId', $options);
    	
    	$form->input();
    	if($form->isSubmitted()){
    		$retUrl['dealId'] = $form->rec->dealId;
    		
    		// Подаваме намерената форма в урл-то за връщане
    		return new Redirect($retUrl);
    	}
    	
    	$quotationId = Request::get('quotationId', 'int');
    	$rejectUrl = toUrl(array('sales_Quotations', 'single', $quotationId));
    	
    	$forceUrl = $retUrl;
    	$forceUrl['force'] = TRUE;
    	
    	$form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/cart_go.png, title = Избор на документа');
    	$form->toolbar->addBtn('Нова продажба', $forceUrl, 'ef_icon = img/16/star_2.png, title = СЪздаване на нова продажба');
    	$form->toolbar->addBtn('Отказ', $rejectUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	if(core_Users::haveRole('partner')){
    		plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
    	}
    	
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param mixed $id - ид или запис на документа
     * @param deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     * @return array $details - масив с артикули готови за запис
     * 				  o productId      - ид на артикул
     * 				  o packagingId    - ид на опаковка/основна мярка
     * 				  o quantity       - количество опаковка
     * 				  o quantityInPack - количество в опаковката
     * 				  o discount       - отстъпка
     * 				  o price          - цена за единица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc)
    {
    	$details = array();
    	$rec = $this->fetchRec($id);
    	$ForMvc = cls::get($forMvc);
    	
    	$info = $this->getAggregateDealInfo($rec);
    	$products = $info->get('shippedProducts');
    	$agreed = $info->get('products');
    	$invoiced = $info->get('invoicedProducts');
    	$packs = $info->get('shippedPacks');
    	
    	if($ForMvc instanceof sales_Proformas){
    		$products = $agreed;
    		$invoiced = array();
    		foreach ($products as $product1){
    			if(!($forMvc instanceof sales_Proformas)){
    				$product1->price -= $product1->price * $product1->discount;
    				unset($product1->discount);
    			}
    		}
    	}
    	
    	if(!count($products)) return $details;
    	
    	foreach ($products as $product){
    		$quantity = $product->quantity;
    		foreach ((array)$invoiced as $inv){
    			if($inv->productId != $product->productId) continue;
    			$quantity -= $inv->quantity;
    		}
    		
    		if($quantity <= 0) continue;
    		
    		// Ако няма информация за експедираните опаковки, взимаме основната опаковка
    		if(!isset($packs[$product->productId])){
    			$packs1 = cat_Products::getPacks($product->productId);
    			$product->packagingId = key($packs1);
    			
    			$product->quantityInPack = 1;
    			if($pRec = cat_products_Packagings::getPack($product->productId, $product->packagingId)){
    				$product->quantityInPack = $pRec->quantity;
    			}
    		} else {
    			// Иначе взимаме най-удобната опаковка
    			$product->quantityInPack = $packs[$product->productId]->inPack;
    			$product->packagingId = $packs[$product->productId]->packagingId;
    		}
    		
    		$dRec = clone $product;
    		$dRec->discount        	= $product->discount;
    		$dRec->price 		  	= ($product->amount) ? ($product->amount / $product->quantity) : $product->price;
    		$dRec->quantity       	= $quantity / $product->quantityInPack;
    		$details[] = $dRec;
    	}
    	
    	return $details;
    }
    
    
    /**
     * След като документа става чакащ
     */
    public static function on_AfterSavePendingDocument($mvc, &$rec)
    {
    	// Ако потребителя е партньор, то вальора на документа става датата на която е станал чакащ
    	if(core_Users::haveRole('partner')){
    		$rec->valior = dt::today();
    		$mvc->save($rec, 'valior');
    	}
    }
    
    
    /**
     * Подготвя табовете на задачите
     */
    public function prepareDealTabs_(&$data)
    {
    	parent::prepareDealTabs_($data);
    	
    	if($data->rec->state != 'draft'){
    		$url = getCurrentUrl();
    		unset($url['export']);
    		
    		$url['dealTab'] = 'DealReport';
    		$data->tabs->TAB('DealReport', 'Поръчано / Доставено' , $url);
    	}
    }
    
    
    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec   - ид или запис на документ
     * @return array $data - логистичните данни
     *		
     *		string(2)     ['fromCountry']  - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']    - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']    - град за натоварване
     * 		string|NULL   ['fromAddress']  - адрес за натоварване
     *  	string|NULL   ['fromCompany']  - фирма 
     *   	string|NULL   ['fromPerson']   - лице
     * 		datetime|NULL ['loadingTime']  - дата на натоварване
     * 		string(2)     ['toCountry']    - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']      - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']      - град за разтоварване
     *  	string|NULL   ['toAddress']    - адрес за разтоварване
     *   	string|NULL   ['toCompany']    - фирма 
     *   	string|NULL   ['toPerson']     - лице
     * 		datetime|NULL ['deliveryTime'] - дата на разтоварване
     * 		text|NULL 	  ['conditions']   - други условия
     *		varchar|NULL  ['ourReff']      - наш реф
     */
    function getLogisticData($rec)
    {
    	$rec = $this->fetchRec($rec);
    	$ownCompany = crm_Companies::fetchOurCompany();
    	$ownCountryId = $ownCompany->country;
    	
    	$contragentData = doc_Folders::getContragentData($rec->folderId);
    	$contragentCountryId = $contragentData->countryId;
    	
    	if(isset($rec->shipmentStoreId)){
    		if($locationId = store_Stores::fetchField($rec->shipmentStoreId, 'locationId')){
    			$storeLocation = crm_Locations::fetch($locationId);
    			$ownCountryId = $storeLocation->countryId;
    		}
    	}
    	
    	if(isset($rec->deliveryLocationId)){
    		$contragentLocation = crm_Locations::fetch($rec->deliveryLocationId);
    		$contragentCountryId = $contragentLocation->countryId;
    	}
    	
    	$ownCountry = drdata_Countries::fetchField($ownCountryId, 'commonName');
    	$contragentCountry = drdata_Countries::fetchField($contragentCountryId, 'commonName');
    	
    	$ownPart = ($this instanceof sales_Sales) ? 'from' : 'to';
    	$contrPart = ($this instanceof sales_Sales) ? 'to' : 'from';
    	
    	$res["{$ownPart}Country"] = $ownCountry;
    	if(isset($storeLocation)){
    		$res["{$ownPart}PCode"]   = !empty($storeLocation->pCode) ? $storeLocation->pCode : NULL;
    		$res["{$ownPart}Place"]   = !empty($storeLocation->place) ? $storeLocation->place : NULL;
    		$res["{$ownPart}Address"] = !empty($storeLocation->address) ? $storeLocation->address : NULL;
    		$res["{$ownPart}Person"]  = !empty($storeLocation->mol) ? $storeLocation->mol : NULL;
    	} else {
    		$res["{$ownPart}PCode"]   = !empty($ownCompany->pCode) ? $ownCompany->pCode : NULL;
    		$res["{$ownPart}Place"]   = !empty($ownCompany->place) ? $ownCompany->place : NULL;
    		$res["{$ownPart}Address"] = !empty($ownCompany->address) ? $ownCompany->address : NULL;
    	}
    	$res["{$ownPart}Company"] = $ownCompany->name;
    	$personId = ($rec->dealerId) ? $rec->dealerId : (($rec->activatedBy) ? $rec->activatedBy : $rec->createdBy);
    	$res["{$ownPart}Person"] = ($res["{$ownPart}Person"]) ? $res["{$ownPart}Person"] : core_users::fetchField($personId, 'names');
    	
    	$res["{$contrPart}Country"] = $contragentCountry;
    	$res["{$contrPart}Company"] = $contragentData->company;
    	
    	if(isset($contragentLocation)){
    		$res["{$contrPart}PCode"]     = !empty($contragentLocation->pCode) ? $contragentLocation->pCode : NULL;
    		$res["{$contrPart}Place"]   = !empty($contragentLocation->place) ? $contragentLocation->place : NULL;
    		$res["{$contrPart}Address"]   = !empty($contragentLocation->address) ? $contragentLocation->address : NULL;
    		$res["{$contrPart}Person"]  = !empty($contragentLocation->mol) ? $contragentLocation->mol : NULL;
    	} else {
    		$res["{$contrPart}PCode"]   = !empty($contragentData->pCode) ? $contragentData->pCode : NULL;
    		$res["{$contrPart}Place"]   = !empty($contragentData->place) ? $contragentData->place : NULL;
    		$res["{$contrPart}Address"] = !empty($contragentData->address) ? $contragentData->address : NULL;
    		$res["{$contrPart}Person"]  = !empty($contragentData->person) ? $contragentData->person : NULL;
    	}
    	
    	$delTime = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : (!empty($rec->deliveryTermTime) ?  dt::addSecs($rec->deliveryTermTime, $rec->valior) : NULL);
    	$res["deliveryTime"]  = $delTime;
    	$res['ourReff'] = "#" . $this->getHandle($rec);
    	
    	return $res;
    }
    
    
    /**
     * Връща ид-то на артикула с най-голяма стойност в сделката
     * 
     * @param stdClass $rec
     * @return int|NULL $productName
     */
    public function findProductIdWithBiggestAmount($rec)
    {
    	$Detail = cls::get($this->mainDetail);
    	$query = $Detail->getQuery();
    	$query = $query->where("#{$Detail->masterKey} = {$rec->id}");
    	$all = $query->fetchAll();
    	
    	$arr = deals_Helper::normalizeProducts(array($all));
    	arr::order($arr, 'sumAmounts', "DESC");
    	$arr = array_values($arr);
    	
    	if($productId = $arr[0]->productId){
    		$productName = cat_Products::getTitleById($productId);
    		return $productName;
    	}
    	
    	return NULL;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	 
    	if($mvc->haveRightFor('changerate', $rec)) {
    		$data->toolbar->addBtn('Промяна на курса', array($mvc, 'changeRate', $rec->id, 'ret_url' => TRUE), "id=changeRateBtn,row=2", 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на курса на документите в нишката');
    	}
    }
    
    
    /**
     * Рекалкулиране на курса на документите в сделката
     */
    function act_Changerate()
    {
    	$this->requireRightFor('changerate');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetchRec($id));
    	$this->requireRightFor('changerate', $rec);
    	
    	$form = cls::get('core_Form');
    	$form->title = "|Преизчисляване на курса на документите в|* " . $this->getHyperlink($rec, TRUE);
    	$form->info = tr("Стар курс|*: <b>{$rec->currencyRate}</b>");
    	$form->FLD('newRate', 'double', 'caption=Нов курс,mandatory');
    	$form->input();
    	
    	if($form->isSubmitted()){
    		$fRec = $form->rec;
    		
    		// Рекалкулиране на сделката
    		deals_Helper::recalcRate($this, $rec->id, $fRec->newRate);
    		
    		// Рекалкулиране на определени документи в нишката и
    		$dealDocuments = $this->getDescendants($rec->id);
    		$arr = array(store_ShipmentOrders::getClassId(), store_Receipts::getClassId(), sales_Services::getClassId(), purchase_Services::getClassId(), sales_Invoices::getClassId(), purchase_Invoices::getClassId());
    		foreach ($dealDocuments as $d) {
    			if(!in_array($d->getClassId(), $arr)) continue;
    			deals_Helper::recalcRate($d->getInstance(), $d->fetch(), $fRec->newRate);
    		}
    		
    		followRetUrl(NULL, 'Документите са преизчислени успешно');
    	}
    	
    	$form->toolbar->addSbBtn('Преизчисли', 'save', 'ef_icon = img/16/tick-circle-frame.png,warning=Ще преизчислите всички документи в нишката по новия курс');
    	$form->toolbar->addBtn('Отказ', array($this, 'single', $id),  'ef_icon = img/16/close-red.png');
    	 
    	// Рендиране на формата
    	return $this->renderWrapping($form->renderHtml());
    }
    
    /**
     * Ъпдейтване на ключовите думи
     * 
     * @param stdClass $rec
     * @param string $keywords
     */
    protected function updateSearchKeywords($rec)
    {
    	$detailsKeywords = '';
    	
    	// заявка към детайлите
    	$Detail = cls::get($this->mainDetail);
    	$query = $Detail->getQuery();
    	$query->where("#{$Detail->masterKey}  = '{$rec->id}'");
    	$query->show('productId,notes');
    	
    	while ($dRec = $query->fetch()){
    		
    		// взимаме заглавията на продуктите
    		$productTitle = cat_Products::getTitleById($dRec->productId);
    		$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
    		if(!empty($dRec->notes)){
    			$detailsKeywords .= " " . plg_Search::normalizeText($dRec->notes);
    		}
    	}
    	 
    	// добавяме новите ключови думи към основните
    	$rec->searchKeywords = " " . $rec->searchKeywords . " " . $detailsKeywords;
    	
    	return $rec->searchKeywords;
    }
}
