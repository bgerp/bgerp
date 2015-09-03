<?php



/**
 * Абстрактен клас за наследяване от класове сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
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
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	*/
	public static function on_AfterDescription(core_Master &$mvc)
	{
		if(empty($mvc->fields['contoActions'])){
			$mvc->FLD('contoActions', 'set(activate,pay,ship)', 'input=none,notNull,default=activate');
		}
	}


	/**
	 * Какво е платежното състояние на сделката
	 */
	public function getPaymentState($aggregateDealInfo, $state)
	{
		$amountPaid      = $aggregateDealInfo->get('amountPaid');
		$amountBl        = $aggregateDealInfo->get('blAmount');
		$amountDelivered = $aggregateDealInfo->get('deliveryAmount');
	
		// Ако имаме платено и доставено
		$diff = round($amountDelivered - $amountPaid, 4);
	
		$conf = core_Packs::getConfig('acc');
	
		if(!empty($amountPaid) || !empty($amountDelivered)){
			
			// Ако разликата е в между -толеранса и +толеранса то състоянието е платено
			if(($diff >= -1 * $conf->ACC_MONEY_TOLERANCE && $diff <= $conf->ACC_MONEY_TOLERANCE) || $diff < -1 * $conf->ACC_MONEY_TOLERANCE){
				
				// Ако е в състояние чакаща отбелязваме я като платена, ако е била просрочена става издължена
				return ($state != 'overdue') ? 'paid' : 'repaid';
			}
		}
		
		// Ако крайното салдо е 0
		if(round($amountBl, 2) == 0){
			
			// издължени стават: платените с нулево платено, просрочените и чакащите по които има плащане или доставяне и крайното салдо е 0
			if(($state == 'paid' && round($amountPaid, 2) == 0) || $state == 'overdue' || ($state == 'pending' && (!empty($amountPaid) || !empty($amountDelivered)))){
				
				return 'repaid';
			}
		}
		
		return 'pending';
	}
	
	
	/**
	 * Задължителни полета на модела
	 */
	protected static function setDealFields($mvc)
	{
		$mvc->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
		
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
		$mvc->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,salecondSysId=deliveryTermSale');
		$mvc->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Доставка->Обект до,silent,class=contactData'); // обект, където да бъде доставено (allowEmpty)
		$mvc->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до'); // до кога трябва да бъде доставено
		$mvc->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)',  'caption=Доставка->От склад'); // наш склад, от където се експедира стоката
		
		// Плащане
		$mvc->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=description,allowEmpty)','caption=Плащане->Метод,salecondSysId=paymentMethodSale');
		$mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута');
		$mvc->FLD('currencyRate', 'double(smartRound)', 'caption=Плащане->Курс');
		$mvc->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса');
		
		// Наш персонал
		$mvc->FLD('initiatorId', 'user(roles=user,allowEmpty,rolesForAll=sales|ceo)', 'caption=Наш персонал->Инициатор');
		$mvc->FLD('dealerId', 'user(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Наш персонал->Търговец');
		
		// Допълнително
		$mvc->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=Допълнително->ДДС');
		$mvc->FLD('makeInvoice', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Фактуриране,maxRadio=2,columns=2');
		$mvc->FLD('note', 'text(rows=4)', 'caption=Допълнително->Условия', array('attr' => array('rows' => 3)));
		
		$mvc->FLD('state',
				'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен)',
				'caption=Статус, input=none'
		);
		
		$mvc->FLD('paymentState', 'enum(pending=Чакащо,overdue=Просроченo,paid=Платенo,repaid=Издължено)', 'caption=Плащане, input=none');
	}


	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		
		if($data->action === 'clone'){
			$form->rec->valior = dt::now();
		} else {
			$form->setDefault('valior', dt::now());
		}
		
		$form->setDefault('caseId', cash_Cases::getCurrent('id', FALSE));
		$form->setDefault('shipmentStoreId', store_Stores::getCurrent('id', FALSE));
		$form->setDefault('makeInvoice', 'yes');
		$form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode($form->rec->valior));
		
		// Поле за избор на локация - само локациите на контрагента по покупката
		$locations = array('' => '') + crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
		$form->setOptions('deliveryLocationId', $locations);
		
		if ($form->rec->id){
        	
        	// Не може да се сменя ДДС-то ако има вече детайли
        	$Detail = $mvc->mainDetail;
        	if($mvc->$Detail->fetch("#{$mvc->$Detail->masterKey} = {$form->rec->id}")){
        		foreach (array('chargeVat', 'currencyRate', 'currencyId', 'deliveryTermId') as $fld){
        			$form->setReadOnly($fld, isset($form->rec->{$fld}) ? $form->rec->{$fld} : $mvc->fetchField($form->rec->id, $fld));
        		}
        	}
        }
        
        $form->addAttr('currencyId', array('onchange' => "document.forms['{$form->formAttr['id']}'].elements['currencyRate'].value ='';"));
        $form->setField('sharedUsers', 'input=none');
        
        // Търговеца по дефолт е отговорника на контрагента
        $inCharge = doc_Folders::fetchField($form->rec->folderId, 'inCharge');
        $form->setDefault('dealerId', $inCharge);
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
		
		$Detail = $this->mainDetail;
		$query = $this->$Detail->getQuery();
		$query->where("#{$this->$Detail->masterKey} = '{$id}'");
		$recs = $query->fetchAll();
	
		deals_Helper::fillRecs($this, $recs, $rec);
	
		// ДДС-то е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
		$amountDeal = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
		$amountDeal -= $this->_total->discount;
		$rec->amountDeal = $amountDeal * $rec->currencyRate;
		$rec->amountVat  = $this->_total->vat * $rec->currencyRate;
		$rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
		
		$this->invoke('BeforeUpdatedMaster', array(&$rec));
		
		return $this->save($rec);
	}

    
	/**
     * Подготвя данните на хедъра на документа
     */
    private function prepareHeaderInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
        $Companies = cls::get('crm_Companies');
        $row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
        $row->MyCompany = tr(core_Lg::transliterate($row->MyCompany));
        
        $row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId)->getContent();
        $row->MyAddress = core_Lg::transliterate($row->MyAddress);
        
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	$row->uicId = $uic;
    	
    	// Данните на клиента
        $ContragentClass = cls::get($rec->contragentClassId);
        $cData = $ContragentClass->getContragentData($rec->contragentId);
    	$row->contragentName = cls::get('type_Varchar')->toVerbal(($cData->person) ? $cData->person : $cData->company);
    	
    	$row->contragentAddress = $ContragentClass->getFullAdress($rec->contragentId)->getContent();
    	$row->contragentAddress = core_Lg::transliterate($row->contragentAddress);
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$rec = static::fetchRec($rec);
    
    	// Името на шаблона е и име на документа
    	$templateId = static::getTemplate($rec);
    	$templateName = doc_TplManager::getTitleById($templateId);
    	
    	return "{$templateName} №{$rec->id}";
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if (!$form->isSubmitted()) {
            return;
        }
        
        $rec = &$form->rec;
        
        // Ако не е въведен валутен курс, използва се курса към датата на документа 
        if (empty($rec->currencyRate)) {
            $rec->currencyRate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
            if(!$rec->currencyRate){
            	$form->setError('currencyRate', "Не може да се изчисли курс");
            }
        } else {
        	if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, $rec->valior, $rec->currencyId, NULL)){
		    	$form->setWarning('currencyRate', $msg);
			}
        }
        
        $form->rec->paymentState = 'pending';
    }

    
    /**
     * Филтър на продажбите
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
        if(!Request::get('Rejected', 'int')){
        	$data->listFilter->FNC('type', 'enum(all=Всички,active=Активни,closed=Приключени,draft=Чернови,clAndAct=Активни и приключени,paid=Платени,overdue=Просрочени,unpaid=Неплатени,delivered=Доставени,undelivered=Недоставени,repaid=Издължени,invoiced=Фактурирани,notInvoiced=Нефактурирани)', 'caption=Тип');
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
						$data->query->where("#state = 'active' || #state = 'closed'");
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
						$data->query->where("#paymentState = 'paid'");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'invoiced':
						$data->query->where("#invRound >= #deliveredRound");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'notInvoiced':
						$data->query->where("#invRound < #deliveredRound OR #invRound IS NULL");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'overdue':
						$data->query->where("#paymentState = 'overdue'");
						break;
					case 'repaid':
						$data->query->where("#paymentState = 'repaid'");
						break;
					case 'delivered':
						$data->query->where("#deliveredRound = #dealRound");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'undelivered':
						$data->query->where("#deliveredRound < #dealRound");
						$data->query->where("#state = 'active'");
						break;
					case 'unpaid':
						$data->query->where("#paidRound < #deliveredRound");
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
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * Връща подзаглавието на документа във вида "Дост: ХХХ(ууу), Плат ХХХ(ууу), Факт: ХХХ(ууу)"
     * @param stdClass $rec - запис от модела
     * @return string $subTitle - подзаглавието
     */
    private function getSubTitle($rec)
    {
    	$fields = $this->selectFields();
    	$fields['-single'] = TRUE;
    	$row = $this->recToVerbal($rec, $fields);
    	
        $subTitle = "Дост: " . (($row->amountDelivered) ? $row->amountDelivered : 0) . "({$row->amountToDeliver})";
		$subTitle .= ", Плат: " . (($row->amountPaid) ? $row->amountPaid : 0) . "({$row->amountToPay})";
        if($rec->makeInvoice != 'no'){
        	$subTitle .= ", Факт: " . (($row->amountInvoiced) ? $row->amountInvoiced : 0) . "({$row->amountToInvoice})";
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
    	$res = array();
    	
    	$Detail = $this->mainDetail;
    	$dQuery = $this->$Detail->getQuery();
    	$dQuery->EXT('state', $this->className, "externalKey={$this->$Detail->masterKey}");
    	$dQuery->where("#{$this->$Detail->masterKey} = '{$id}'");
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	
    	return $res;
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
     * При нова сделка, се ънсетва threadId-то, ако има
     */
    public static function on_AfterPrepareDocumentLocation($mvc, $form)
    {   
    	if($form->rec->threadId && !$form->rec->id){
		     unset($form->rec->threadId);
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
    	if($rec->shipmentStoreId){
    		$storeRec = store_Stores::fetch($rec->shipmentStoreId);
    		if($storeRec->autoShare == 'yes'){
    			$rec->sharedUsers = keylist::merge($rec->sharedUsers, $storeRec->chiefs);
    		}
    	}
    		
    	// Ако има каса се нотифицира касиера
    	if($rec->caseId){
    		$caseRec = cash_Cases::fetch($rec->caseId);
    		if($caseRec->autoShare == 'yes'){
    			$rec->sharedUsers = keylist::merge($rec->sharedUsers, $caseRec->cashiers);
    		}
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
    public static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $self = cls::get(get_called_class());
        $title = tr(mb_strtolower($self->singleTitle));
        
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
    	$dQuery = $this->$Detail->getQuery();
    	$dQuery->where("#{$this->$Detail->masterKey} = {$rec->id}");
    	
    	while($d = $dQuery->fetch()){
        	$info = cls::get($d->classId)->getProductInfo($d->productId);
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
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$Detail = $mvc->mainDetail;
     	$query = $mvc->$Detail->getQuery();
     	
     	// точно на тази фактура детайлите търсим
     	$query->where("#{$mvc->$Detail->masterKey}  = '{$rec->id}'");
     	
	        while ($recDetails = $query->fetch()){
	        	// взимаме заглавията на продуктите
	        	$productTitle = cls::get($recDetails->classId)->getTitleById($recDetails->productId);
	        	// и ги нормализираме
	        	$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
	        }
	        
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
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
     * 
     * @param unknown $mvc
     * @param unknown $rec
     * @param unknown $nRec
     */
    public static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
    {
    	unset($nRec->contoActions,
    		  $nRec->amountDelivered, 
    		  $nRec->amountBl,  
    		  $nRec->amountPaid,
    		  $nRec->amountInvoiced,
    		  $nRec->sharedViews,
    		  $nRec->closedDocuments);
    	
    	$nRec->paymentState = 'pending';
    }


    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	//Ако потребителя не е в група доставчици го включваме
    	$rec = $mvc->fetchRec($rec);
    	cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $mvc->crmDefGroup);
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
		
		foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced', 'ToPay', 'ToDeliver', 'ToInvoice', 'Bl') as $amnt) {
            if (round($rec->{"amount{$amnt}"}, 2) == 0) {
            	$coreConf = core_Packs::getConfig('core');
            	$pointSign = $coreConf->EF_NUMBER_DEC_POINT;
            	$row->{"amount{$amnt}"} = '<span class="quiet">0' . $pointSign . '00</span>';
            } else {
            	$value = round($rec->{"amount{$amnt}"} / $rec->currencyRate, 2);
            	$row->{"amount{$amnt}"} = $amountType->toVerbal($value);
            }
        }
        
        foreach (array('ToPay', 'ToDeliver', 'ToInvoice', 'Bl') as $amnt){
        	if(round($rec->{"amount{$amnt}"}, 2) == 0) continue;
        	
        	$color = (round($rec->{"amount{$amnt}"}, 2) < 0) ? 'red' : 'green';
        	$row->{"amount{$amnt}"} = "<span style='color:{$color}'>{$row->{"amount{$amnt}"}}</span>";
        }
        
        if($rec->paymentState == 'overdue' || $rec->paymentState == 'repaid'){
        	$row->amountPaid = "<span style='color:red'>" . strip_tags($row->amountPaid) . "</span>";
        }
        
    	if($fields['-list']){
	    	$row->paymentState = ($rec->paymentState == 'overdue' || $rec->paymentState == 'repaid') ? "<span style='color:red'>{$row->paymentState}</span>" : $row->paymentState;
    	}
	    
    	if($rec->dealerId){
    		$row->dealerId = crm_Profiles::createLink($rec->dealerId, $row->dealerId);
    	}
    	
    	if($rec->initiatorId){
    		$row->initiatorId = crm_Profiles::createLink($rec->initiatorId, $row->initiatorId);
    	}
    	
	    if($fields['-single']){
	    	if($rec->originId){
	    		$row->originId = doc_Containers::getDocument($rec->originId)->getHyperLink();
	    	}
	    	
	    	if($rec->deliveryLocationId){
	    		$row->deliveryLocationId = crm_Locations::getHyperlink($rec->deliveryLocationId);
	    	}
	    	
	    	if($rec->deliveryTime){
	    		if(strstr($rec->deliveryTime, ' 00:00') !== FALSE){
	    			$row->deliveryTime = cls::get('type_Date')->toVerbal($rec->deliveryTime);
	    		}
	    	}
	    	
	    	$row->username = core_Users::getVerbal($rec->createdBy, 'names');
	    	
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
			$row->$fld = ' ';
			
			if(!Mode::is('text', 'xhtml')){
				if($rec->shipmentStoreId){
					$row->shipmentStoreId = store_Stores::getHyperlink($rec->shipmentStoreId);
				}
				
				if($rec->caseId){
					$row->caseId = cash_Cases::getHyperlink($rec->caseId);
				}
				
				if($rec->caseId){
					$row->caseId = cash_Cases::getHyperlink($rec->caseId);
				}
			}
			
			$actions = type_Set::toArray($rec->contoActions);
			
			core_Lg::push($rec->tplLang);
			
			$mvc->prepareHeaderInfo($row, $rec);
			
			if ($rec->currencyRate != 1) {
				$row->currencyRateText = '(<span class="quiet">' . tr('курс') . "</span> {$row->currencyRate})";
			}
			
			if(isset($actions['ship'])){
				$row->isDelivered .= mb_strtoupper(tr('доставено'));
				if($rec->state == 'rejected') {
					$row->isDelivered = "<span class='quet'>{$row->isDelivered}</span>";
				}
				
				if($rec->deliveryLocationId && $rec->shipmentStoreId){
					if($ourLocation = store_Stores::fetchField($rec->shipmentStoreId, 'locationId')){
						$row->ourLocation = crm_Locations::getTitleById($ourLocation);
						$ourLocationAddress = crm_Locations::getAddress($ourLocation);
						if($ourLocationAddress != ''){
							$row->ourLocationAddress = $ourLocationAddress;
						}
					}
					
					$contLocationAddress = crm_Locations::getAddress($rec->deliveryLocationId);
					if($contLocationAddress != ''){
						$row->deliveryLocationAddress = $contLocationAddress;
					}
					
					if($gln = crm_Locations::fetchField($rec->deliveryLocationId, 'gln')){
						$row->deliveryLocationAddress = $gln . ", " . $row->deliveryLocationAddress;
						$row->deliveryLocationAddress = trim($row->deliveryLocationAddress, ", ");
					}
				}
			}
			
			if(isset($actions['pay'])){
				$row->isPaid .= mb_strtoupper(tr('платено'));
				if($rec->state == 'rejected') {
					$row->isPaid = "<span class='quet'>{$row->isPaid}</span>";
				}
			}
			
			if($rec->makeInvoice == 'no'){
				$row->amountToInvoice = "<span style='font-size:0.7em'>" . tr('без фактуриране') . "</span>";
			}
			
			$row->username = core_Lg::transliterate($row->username);
			core_Lg::pop();
	    }
    }
    
    
    /**
     * След промяна в журнала със свързаното перо
     */
    public static function on_AfterJournalItemAffect($mvc, $rec, $item)
    {
    	$aggregateDealInfo = $mvc->getAggregateDealInfo($rec->id);
    	$Detail = $mvc->mainDetail;
    	
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
    	
    	$rec->paymentState = $mvc->getPaymentState($aggregateDealInfo, $rec->paymentState);
    	
    	$mvc->save_($rec);
    	
    	$dQuery = $mvc->$Detail->getQuery();
    	$dQuery->where("#{$mvc->$Detail->masterKey} = {$rec->id}");
    	
    	// Намираме всички експедирани продукти, и обновяваме на договорените колко са експедирани
    	$shippedProducts = $aggregateDealInfo->get('shippedProducts');
    	while($product = $dQuery->fetch()){
    		$delivered = 0;
    		if(count($shippedProducts)){
    			foreach ($shippedProducts as $key => $shipped){
    				if($product->classId == $shipped->classId && $product->productId == $shipped->productId){
    					$delivered = $shipped->quantity;
    					break;
    				}
    			}
    		}
    		
    		$product->quantityDelivered = $delivered;
    		$mvc->$Detail->save($product);
    	}
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
    			
    			$products = (array)$dealInfo->get('products');
    			if(count($products)){
    				foreach ($products as $p){
    
    					// Обединяваме множествата на договорените им продукти
    					$index = $p->classId . "|" . $p->productId;
    					$d = &$details[$index];
    					$d = (object)$d;
    
    					$d->classId   = $p->classId;
    					$d->productId = $p->productId;
    					$d->uomId     = $p->uomId;
    					$d->quantity += $p->quantity;
    					$d->price     = ($d->price) ? ($d->price + $p->price) / 2 : $p->price;
    					if(!empty($d->discount) || !empty($p->discount)){
    						$d->discount = ($d->discount) ? ($d->discount + $p->discount) / 2 : $p->discount;
    					}
    
    					$info = cls::get($p->classId)->getProductInfo($p->productId);
    					$p->quantityInPack = ($info->packagings[$p->packagingId]) ? $info->packagings[$p->packagingId]->quantity : 1;
    					
    					if(empty($d->packagingId)){
    						$d->packagingId = $p->packagingId;
    						$d->quantityInPack = $p->quantityInPack;
    					} else {
    						if($p->quantityInPack < $d->quantityInPack){
    							$d->packagingId = $p->packagingId;
    							$d->quantityInPack = $p->quantityInPack;
    						}
    					}
    				}
    			}
    		}
    	}
    	 
    	$Detail = $mvc->mainDetail;
    	
    	// Изтриваме досегашните детайли на сделката
    	$mvc->$Detail->delete("#{$mvc->$Detail->masterKey} = {$rec->id}");
    	 
    	// Записваме новите
    	if(count($details)){
    		foreach ($details as $d1){
    			$d1->{$mvc->$Detail->masterKey} = $rec->id;
    			$mvc->$Detail->save($d1);
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
    
    		// може да се плати с продуктите
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
    	
    	if($rec->state != 'draft'){
    		return redirect(array($this, 'single', $id), FALSE, 'Договорът вече е активиран');
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
    	$form->info = '<b>Контиране на извършени на момента действия</b> (опционално):';
    	
    	// Извличане на позволените операции
    	$options = $this->getContoOptions($rec);
    	
    	// Трябва да има избор на действие
    	expect(count($options));
    	
    	// Подготовка на полето за избор на операция и инпут на формата
    	$form->FNC('action', cls::get('type_Set', array('suggestions' => $options)), 'columns=1,input,caption=Изберете');
    	
    	$selected = array();
    	
    	// Ако има склад и експедиране и потребителя е логнат в склада, слагаме отметка
    	if($options['ship'] && $rec->shipmentStoreId){
    		if($rec->shipmentStoreId === $curStoreId){
    			$selected[] = 'ship';
    		}
    	} elseif($options['ship']){
    		$selected[] = 'ship';
    	}
    	
    	// Ако има каса и потребителя е логнат в нея, Слагаме отметка
    	if($options['pay'] && $rec->caseId){
    		if($rec->caseId === $curCaseId){
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
    		 
    		// Редирект
    		return redirect(array($this, 'single', $id));
    	}
    	
    	$form->toolbar->addSbBtn('Активиране/Контиране', 'save', 'ef_icon = img/16/tick-circle-frame.png');
    	$form->toolbar->addBtn('Отказ', array($this, 'single', $id),  'ef_icon = img/16/close16.png');
    	 
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
    	 
    	$query->EXT('threadModifiedOn', 'doc_Threads', 'externalName=last,externalKey=threadId');
    	 
    	// Закръглената оставаща сума за плащане
    	$query->XPR('toInvoice', 'double', 'ROUND(#amountDelivered - #amountInvoiced, 2)');
    	 
    	// Само активни продажби
    	$query->where("#state = 'active'");
    	$query->where("#amountDelivered IS NOT NULL AND #amountPaid IS NOT NULL");
    	 
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
    			 
    			// Ако има проблем при обновяването
    			$this->logWarning("Проблем при автоматичното приключване на сделка: '{$e->getMessage()}'", $rec->id);
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
    	$query->where("#paymentState = 'pending' || #paymentState = 'overdue'");
    	$query->where("#state = 'active'");
    	$query->where("ADDDATE(#modifiedOn, INTERVAL {$overdueDelay} SECOND) <= '{$now}'");
    	$query->show('id,amountDeal,amountPaid,amountDelivered,paymentState');
    	
    	while($rec = $query->fetch()){
    		try{
    			// Намира се метода на плащане от интерфейса
    			$dealInfo = $Class->getAggregateDealInfo($rec->id);
    		} catch(core_exception_Expect $e){
    
    			// Ако има проблем при извличането се продължава
    			$this->logWarning("Проблем при извличането 'bgerp_DealAggregatorIntf': '{$e->getMessage()}'", $rec->id);
    			continue;
    		}
    
    		$mId = $dealInfo->get('paymentMethodId');
    		$isOverdue = FALSE;
    
    		if($mId){
    			$date = NULL;
    			 
    			// Намира се датата в реда фактура/експедиция/сделка
    			foreach (array('invoicedValior', 'shippedValior', 'agreedValior') as $asp){
    				if($date = $dealInfo->get($asp)){
    					break;
    				}
    			}
    			 
    			// Извлича се платежния план
    			$plan = cond_PaymentMethods::getPaymentPlan($mId, $rec->amountDeal, $date);
    
    			try{
    				$isOverdue = cond_PaymentMethods::isOverdue($plan, round($rec->amountDelivered, 2) - round($rec->amountPaid, 2));
    			} catch(core_exception_Expect $e){
    					
    				// Ако има проблем при извличането се продължава
    				$this->logWarning("Несъществуващ платежен план': '{$e->getMessage()}'", $rec->id);
    				continue;
    			}
    		}
    
    		// Проверка дали сделката е просрочена
    		if($isOverdue){
    
    			// Ако да, то сделката се отбелязва като просрочена
    			$rec->paymentState = 'overdue';
    		} else {
    			
    			// Ако не е просрочена проверяваме дали е платена
    			$rec->paymentState = $Class->getPaymentState($dealInfo, $rec->paymentState);
    		}
    
    		try{
    			$Class->save_($rec);
    		} catch(core_exception_Expect $e){
    
    			// Ако има проблем при обновяването
    			$this->logWarning("Проблем при проверката дали е просрочена сделката: '{$e->getMessage()}'", $rec->id);
    		}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('shareLog');
    	}
    	
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
     * 		o $fields['chargeVat']          -  да се начислява ли ДДС - yes=Да,no=Не,free=Освободено (ако няма, се определя според контрагента)
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
    	
    	// Проверяваме подадените полета дали са позволени
    	if(count($fields)){
    		foreach ($fields as $fld => $value){
    			expect(array_key_exists($fld, $allowedFields));
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
    	
    	// Ако не е подадена дата, това е сегашната
    	$fields['valior'] = (empty($fields['valior'])) ? dt::today() : $fields['valior'];
    	 
    	// Записваме данните на контрагента
    	$fields['contragentClassId'] = $contragentClass->getClassId();
    	$fields['contragentId'] = $contragentId;
    	 
    	// Ако няма валута, това е основната за периода
    	$fields['currencyId'] = (empty($fields['currencyId'])) ? acc_Periods::getBaseCurrencyCode($fields['valior']) : $fields['currencyId'];
    	 
    	// Ако няма курс, това е този за основната валута
    	$fields['currencyRate'] = (empty($fields['currencyRate'])) ? currency_CurrencyRates::getRate($fields['currencyRate'], $fields['currencyId'], NULL) : $fields['currencyRate'];
    	 
    	// Форсираме папката на клиента
    	$fields['folderId'] = $contragentClass::forceCoverAndFolder($contragentId);
    	 
    	// Ако няма платежен план, това е плащане в брой
    	$fields['paymentMethodId'] = (empty($fields['paymentMethodId'])) ? cond_PaymentMethods::fetchField("#name = 'Cash on Delivery'", 'id') : $fields['paymentMethodId'];
    	 
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
     * @param mixed $pMan		   - продуктов мениджър
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
    public static function addRow($id, $pMan, $productId, $packQuantity, $price = NULL, $packagingId = NULL, $discount = NULL, $tolerance = NULL, $term = NULL, $notes = NULL)
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
    		expect($tolerance = cls::get('type_Double')->fromVerbal($tolerance));
    		expect($tolerance >= 0 && $tolerance <= 1);
    	}
    	
    	if(isset($term)){
    		expect($term = cls::get('type_Time')->fromVerbal($term));
    	}
    	
    	
    	// Трябва да има такъв продукт и опаковка
    	$ProductMan = cls::get($pMan);
    	expect($ProductMan->fetchField($productId, 'id'));
    	if(isset($packagingId)){
    		expect(cat_UoM::fetchField($packagingId, 'id'));
    	}
    	
    	if(isset($notes)){
    		$notes = cls::get('type_Richtext')->fromVerbal($notes);
    	}
    	
    	// Броя еденици в опаковка, се определя от информацията за продукта
    	$productInfo = $ProductMan->getProductInfo($productId);
    	if(!$packagingId){
    		$packagingId = $productInfo->productRec->measureId;
    	}
    	
    	$quantityInPack = ($productInfo->packagings[$packagingId]) ? $productInfo->packagings[$packagingId]->quantity : 1;
    	$productManId = $ProductMan->getClassId();
    	
    	// Ако няма цена, опитваме се да я намерим от съответната ценова политика
    	if(empty($price)){
    		$Policy = (isset($Detail->Policy)) ? $Detail->Policy : $ProductMan->getPolicy();
    		$policyInfo = $Policy->getPriceInfo($rec->contragentClassId, $rec->contragentId, $productId, $productManId, $packagingId, $packQuantity);
    		$price = $policyInfo->price;
    	}
    	
    	$packQuantity = cls::get('type_Double')->fromVerbal($packQuantity);
    	
    	// Подготвяме детайла
    	$dRec = (object)array($Detail->masterKey => $id, 
    						  'classId'          => $productManId, 
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
    	$where = "#{$Detail->masterKey} = {$id} AND #classId = {$dRec->classId} AND #productId = {$dRec->productId}";
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
		// не може да се клонира ако потребителя няма достъп до папката
    	if($action == 'clonerec' && isset($rec)){
    		if(!doc_Folders::haveRightToFolder($rec->folderId, $userId)){
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
    		$options[$rec->id] = $this->getTitleById($rec->id, TRUE);
    	}
    	
    	$retUrl = getRetUrl();
    	
    	// Ако няма опции, връщаме се назад
    	if(!count($options)){
    		$retUrl['stop'] = TRUE;
    		
    		return Redirect($retUrl);
    	}
    	
    	// Подготвяме и показваме формата за избор на чернова оферта, ако има чернови
    	$me = get_called_class();
    	$form = cls::get('core_Form');
    	$form->title = "|Избор на чернова|* " . mb_strtolower($this->singleTitle);
    	$form->FLD('dealId', "key(mvc={$me},select=id,allowEmpty)", "caption={$this->singleTitle},mandatory");
    	$form->setOptions('dealId', $options);
    	
    	$form->input();
    	if($form->isSubmitted()){
    		$retUrl['dealId'] = $form->rec->dealId;
    		
    		// Подаваме намерената форма в урл-то за връщане
    		return Redirect($retUrl);
    	}
    	
    	$form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png, title = Избор на документа');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
    	
    	return $this->renderWrapping($form->renderHtml());
    }
}
