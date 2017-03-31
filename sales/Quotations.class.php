<?php



/**
 * Документ "Оферта"
 *
 * Мениджър на документи за Оферта за продажба
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Quotations extends core_Master
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Изходящи оферти';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Q';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, doc_plg_Close, doc_EmailCreatePlg, acc_plg_DocumentSummary, plg_Search, doc_plg_HidePrices, doc_plg_TplManager,
                    doc_DocumentPlg, plg_Printing, doc_ActivatePlg, crm_plg_UpdateContragentData, plg_Clone, bgerp_plg_Blank, cond_plg_DefaultValues,doc_plg_SelectFolder,plg_LastUsedKeys';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,sales';
    
    
    /**
     * Поле за търсене по дата
     */
    public $filterDateField = 'date';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/document_quote.png';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canWrite = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date, title=Документ, folderId, state, createdOn, createdBy';
    

    /**
     * Детайла, на модела
     */
    public $details = 'sales_QuotationsDetails';
    

    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'sales_QuotationsDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оферта';
   
   
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'paymentMethodId, reff, company, person, email, folderId';
    
    
    /**
      * Групиране на документите
      */ 
    public $newBtnGroup = "3.7|Търговия";
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
    public $cloneDetails = 'sales_QuotationsDetails';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Кой може да клонира
     */
    public $canClonerec = 'ceo, sales';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'crm_ContragentAccRegIntf';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'validFor'            => 'lastDocUser|lastDoc',
    	'paymentMethodId'     => 'clientCondition|lastDocUser|lastDoc',
        'currencyId'          => 'lastDocUser|lastDoc|CoverMethod',
        'chargeVat'           => 'lastDocUser|lastDoc|defMethod',
    	'others'              => 'lastDocUser|lastDoc',
        'deliveryTermId'      => 'clientCondition|lastDocUser|lastDoc',
        'deliveryPlaceId'     => 'lastDocUser|lastDoc|',
        'company'             => 'lastDocUser|lastDoc|clientData',
        'person' 		      => 'lastDocUser|lastDoc|clientData',
        'email' 		      => 'lastDocUser|lastDoc|clientData',
    	'tel' 			      => 'lastDocUser|lastDoc|clientData',
        'fax' 			      => 'lastDocUser|lastDoc|clientData',
        'contragentCountryId' => 'lastDocUser|lastDoc|clientData',
        'pCode' 		      => 'lastDocUser|lastDoc|clientData',
    	'place' 		      => 'lastDocUser|lastDoc|clientData',
    	'address' 		      => 'clientData',
    	'template' 		      => 'lastDocUser|lastDoc|defMethod',
    );
    
    
    /**
     * Кои полета ако не са попълнени във визитката на контрагента да се попълнят след запис
     */
    public static $updateContragentdataField = array(
				    		    'email'   => 'email',
				    			'tel'     => 'tel',
				    			'fax'     => 'fax',
				    			'pCode'   => 'pCode',
				    			'place'   => 'place',
				    			'address' => 'address',
    );
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'date';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'deliveryTermId, paymentMethodId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date', 'caption=Дата'); 
        $this->FLD('reff', 'varchar(255)', 'caption=Ваш реф.,class=contactData');
        
        $this->FNC('row1', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
    	$this->FNC('row2', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
    	$this->FNC('row3', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
    	
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)','caption=Плащане->Метод,salecondSysId=paymentMethodSale');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Плащане->Банкова с-ка');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,removeAndRefreshForm=currencyRate');
        $this->FLD('currencyRate', 'double(decimals=5)', 'caption=Плащане->Курс,input=hidden');
        $this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Oсвободено от ДДС, no=Без начисляване на ДДС)','caption=Плащане->ДДС,oldFieldName=vat');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,salecondSysId=deliveryTermSale');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Място,hint=Изберете локация или въведете нова');
        
		$this->FLD('company', 'varchar', 'caption=Получател->Фирма, changable, class=contactData');
        $this->FLD('person', 'varchar', 'caption=Получател->Име, changable, class=contactData');
        $this->FLD('email', 'varchar', 'caption=Получател->Имейл, changable, class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Получател->Тел., changable, class=contactData');
        $this->FLD('fax', 'varchar', 'caption=Получател->Факс, changable, class=contactData');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Получател->Държава,mandatory,contactData,contragentDataField=countryId');
        $this->FLD('pCode', 'varchar', 'caption=Получател->П. код, changable, class=contactData');
        $this->FLD('place', 'varchar', 'caption=Получател->Град/с, changable, class=contactData');
        $this->FLD('address', 'varchar', 'caption=Получател->Адрес, changable, class=contactData');
    	
    	$this->FLD('validFor', 'time(uom=days,suggestions=10 дни|15 дни|30 дни|45 дни|60 дни|90 дни)', 'caption=Допълнително->Валидност');
    	$this->FLD('others', 'text(rows=4)', 'caption=Допълнително->Условия');
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
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $form = $data->form;
       $rec = &$data->form->rec;
       
       // При клониране
       if($data->action == 'clone'){
       	
       		// Ако няма reff взимаме хендлъра на оригиналния документ
	       	if(empty($rec->reff)){
	       		$rec->reff = $mvc->getHandle($rec->id);
	       	}
	       	
	       	// Инкрементираме reff-а на оригинална
	       	$rec->reff = str::addIncrementSuffix($rec->reff, 'v', 2);
       }
       
       $contragentClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
       $contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
       $form->setDefault('contragentClassId', $contragentClassId);
       $form->setDefault('contragentId', $contragentId);
       
       if(isset($form->rec->id)){
       		if($mvc->sales_QuotationsDetails->fetch("#quotationId = {$form->rec->id}")){
       			foreach (array('chargeVat', 'currencyRate', 'currencyId', 'deliveryTermId', 'deliveryPlaceId') as $fld){
       				$form->setReadOnly($fld);
       			}
       		}
       }
      
       $locations = crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId, FALSE);
       $form->setSuggestions('deliveryPlaceId',  array('' => '') + $locations);
      
       if(isset($rec->originId) && $data->action != 'clone' && empty($form->rec->id)){
       	
       		// Ако офертата има ориджин
       		$form->setField('row1,row2,row3', 'input');
       		$origin = doc_Containers::getDocument($rec->originId);
       		
       		if($origin->haveInterface('cat_ProductAccRegIntf')){
       			
       			// Ако продукта има ориджин който е запитване вземаме количествата от него по дефолт
       			if($productOrigin = $origin->fetchField('originId')){
       				$productOrigin = doc_Containers::getDocument($productOrigin);
       				if($productOrigin->haveInterface('marketing_InquiryEmbedderIntf')){
       					$productOriginRec = $productOrigin->fetch();
       					$form->setDefault('row1', $productOriginRec->quantity1);
       					$form->setDefault('row2', $productOriginRec->quantity2);
       					$form->setDefault('row3', $productOriginRec->quantity3);
       				}
       			}
       		}
       }
       
       if(!$rec->person){
       	  $form->setSuggestions('person', crm_Companies::getPersonOptions($rec->contragentId, FALSE));
       }
       
       $form->setDefault('bankAccountId', bank_OwnAccounts::getCurrent('id', FALSE));
    }
    
    
	/** 
	 * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
	    if($data->rec->state == 'active'){
	    	if(sales_Sales::haveRightFor('add', (object)array('folderId' => $data->rec->folderId))){
	    		$items = $mvc->getItems($data->rec->id);
	    		
	    		// Ако има поне един опционален артикул или има варианти на задължителните, бутона сочи към екшън за определяне на количествата
	    		if(sales_QuotationsDetails::fetch("#quotationId = {$data->rec->id} AND #optional = 'yes'") || !$items){
	    			$data->toolbar->addBtn('Продажба', array($mvc, 'FilterProductsForSale', $data->rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/star_2.png,title=Създаване на продажба по офертата');
	    		
	    		// Иначе, към създаването на нова продажба
	    		} else {
	    			$warning = '';
	    			$title = 'Прехвърляне на артикулите в съществуваща продажба чернова';
	    			if(!sales_Sales::count("#state = 'draft' AND #contragentId = {$data->rec->contragentId} AND #contragentClassId = {$data->rec->contragentClassId}")){
	    				$warning = "Сигурни ли сте, че искате да създадете продажба?";
	    				$title = 'Създаване на продажба от офертата';
	    				$efIcon = 'img/16/star_2.png';
	    			} else {
	    				$efIcon = 'img/16/cart_go.png';
	    			}
	    			
	    			$data->toolbar->addBtn('Продажба', array($mvc, 'CreateSale', $data->rec->id, 'ret_url' => TRUE), array('warning' => $warning), "ef_icon={$efIcon},title={$title}");
	    		}
	    	}
	    }
    }
    
    
    /** 
	 * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	if($data->sales_QuotationsDetails->summary){
    		$data->row = (object)((array)$data->row + (array)$data->sales_QuotationsDetails->summary);
    	}
    	
    	$dData = $data->sales_QuotationsDetails;
    	if($dData->countNotOptional && $dData->notOptionalHaveOneQuantity){
    		$firstProductRow = $dData->rows[key($dData->rows)][0];
    		if($firstProductRow->tolerance){
    			$data->row->others .= "<li>" . tr('Толеранс:') ." {$firstProductRow->tolerance}</li>";
    		}
    		
    		if(isset($firstProductRow->term)){
    			$data->row->others .= "<li>" . tr('Срок:') ." {$firstProductRow->term}</li>";
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	
	    	// Ако има проверка на к-та от запитването
	    	$errorFields = $allQuantities = array();
	    	$checArr = array('1' => $rec->row1, '2' => $rec->row2, '3' => $rec->row3);
	    	foreach ($checArr as $k => $v){
	    		if(!empty($v)){
	    			$parts = type_ComplexType::getParts($v);
	    			$rec->{"quantity{$k}"} = $parts['left'];
	    			$rec->{"price{$k}"} = ($parts['right'] === '') ? NULL : $parts['right'];
	    			
	    			if(in_array($parts['left'], $allQuantities)){
	    				$errorFields[] = "row{$k}";
	    			} else {
	    				$allQuantities[] = $parts['left'];
	    			}
	    		}
	    	}
	    	
	    	// Ако има повтарящи се полета
	    	if(count($errorFields)){
	    		$form->setError($errorFields, "Количествата трябва да са различни");
	    	}
	    	
	    	if(empty($rec->currencyRate)){
	    		$rec->currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, NULL);
	    		if(!$rec->currencyRate){
	    			$form->setError('currencyRate', "Не може да се изчисли курс");
	    		}
	    	}
	    	
	    	if(isset($rec->date) && isset($rec->validFor)){
	    		$expireOn = dt::verbal2mysql(dt::addSecs($rec->validFor, $rec->date), FALSE);
	    		if($expireOn < dt::today()){
	    			$form->setWarning('date,validFor', 'Валидноста на офертата е преди текущата дата');
	    		}
	    	}
		}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if(isset($rec->originId)){
    		
    		// Намиране на ориджина
    		$origin = doc_Containers::getDocument($rec->originId);
    		$originRec = $origin->fetch('id,measureId');
    		$vat = cat_Products::getVat($origin->that, $rec->date);
    		
    		// Ако в река има 1 от 3 к-ва
    		foreach (range(1, 3) as $i){
    			
    			// Ако има дефолтно количество
    			$quantity = $rec->{"quantity{$i}"};
    			$price = $rec->{"price{$i}"};
    			if(!$quantity) continue;
    				 
    			// Прави се опит за добавянето на артикула към реда
    			try{
    				if(!empty($price)){
    					$price = deals_Helper::getPurePrice($price, $vat, $rec->currencyRate, $rec->chargeVat);
    				}
    				sales_Quotations::addRow($rec->id, $originRec->id, $quantity, $originRec->measureId, $price);
    			} catch(core_exception_Expect $e){
    				reportException($e);
    		
    				if(haveRole('debug')){
    					$dump  = $e->getDump();
    					core_Statuses::newStatus($dump[0], 'warning');
    				}
    			}
    		}
    		
    		// Споделяме текущия потребител със нишката на заданието
    		$cu = core_Users::getCurrent();
    		doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    	}
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
    	$row = parent::recToVerbal_($rec, $fields);
    	$mvc = cls::get(get_called_class());
    	
    	if($fields['-single']){
    		if(isset($rec->validFor)){
    	
    			// До коя дата е валидна
    			$validDate = dt::addSecs($rec->validFor, $rec->date);
    			$row->validDate = $mvc->getFieldType('date')->toVerbal($validDate);
    		
    			$date = dt::verbal2mysql($validDate, FALSE);
    			if($date < dt::today()){
    				if(!Mode::isReadOnly()){
    					$row->validDate = "<span class='red'>{$row->validDate}</span>";
    					
    					if($rec->state == 'draft'){
    						$row->validDate = ht::createHint($row->validDate, 'Валидноста на офертата е преди текущата дата', 'warning');
    					} elseif($rec->state != 'rejected'){
    						$row->validDate = ht::createHint($row->validDate, 'Офертата е изтекла', 'warning');
    					}
    				}
    			}
    		}
    		
    		$row->number = $mvc->getHandle($rec->id);
    		$row->username = core_Users::recToVerbal(core_Users::fetch($rec->createdBy), 'names')->names;
			$row->username = transliterate(tr($row->username));
    		
    		$profRec = crm_Profiles::fetchRec("#userId = {$rec->createdBy}");
    		if(!empty($profRec)){
    			if($position = crm_Persons::fetchField($profRec->personId, 'buzPosition')){
    				$row->position = cls::get('type_Varchar')->toVerbal($position);
    			}
    		}
    			
    		$ownCompanyData = crm_Companies::fetchOwnCompany();
    			
    		$Varchar = cls::get('type_Varchar');
    		$row->MyCompany = $Varchar->toVerbal($ownCompanyData->company);
    		$row->MyCompany = transliterate(tr($row->MyCompany));
    		
    		$contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
    		$cData = $contragent->getContragentData();
    			
    		$fld = ($rec->tplLang == 'bg') ? 'commonNameBg' : 'commonName';
    		$row->mycompanyCountryId = drdata_Countries::getVerbal($ownCompanyData->countryId, $fld);
    		
    		foreach (array('pCode', 'place', 'address') as $fld){
    			if($cData->{$fld}){
    				$row->{"contragent{$fld}"} = $Varchar->toVerbal($cData->{$fld});
    			}
    	
    			if($ownCompanyData->{$fld}){
    				$row->{"mycompany{$fld}"} = $Varchar->toVerbal($ownCompanyData->{$fld});
    				$row->{"mycompany{$fld}"} = transliterate(tr($row->{"mycompany{$fld}"}));
    			}
    		}
    			
    		if($rec->currencyRate == 1){
    			unset($row->currencyRate);
    		}
    			
    		if($rec->others){
    			$others = explode('<br>', $row->others);
    			$row->others = '';
    			foreach ($others as $other){
    				$row->others .= "<li>{$other}</li>";
    			}
    		}
    			
    		if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    			if($rec->deliveryPlaceId){
    				if($placeId = crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id')){
    					$row->deliveryPlaceId = ht::createLinkRef($row->deliveryPlaceId, array('crm_Locations', 'single', $placeId), NULL, 'title=Към локацията');
    				}
    			}
    			
    			if(isset($rec->bankAccountId)){
    				$ownAccount = bank_OwnAccounts::getOwnAccountInfo($rec->bankAccountId);
    				$url = bank_OwnAccounts::getSingleUrlArray($rec->bankAccountId);
    				$row->bankAccountId = ht::createLink($ownAccount->iban, $url);
    			}
    		}
    		 
    		if(!empty($profRec)){
    			$createdRec = crm_Persons::fetch($profRec->id);
    		}
    		
    		$buzAddress = ($createdRec->buzAddress) ? $createdRec->buzAddress : $ownCompanyData->place;
    		if($buzAddress){
    			$row->buzPlace = cls::get('type_Varchar')->toVerbal($buzAddress);
    			$row->buzPlace = core_Lg::transliterate($row->buzPlace);
    		}
    	
    		if($cond = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'commonConditionSale')){
    			$row->commonConditionQuote = cls::get('type_Varchar')->toVerbal($cond);
    		}
    		 
    		if(empty($rec->date)){
    			$row->date = $mvc->getFieldType('date')->toVerbal(dt::today());
    		}
    		
    		$items = $mvc->getItems($rec->id, TRUE, TRUE);
    		
    		if(is_array($items)){
    			$row->transportCurrencyId = $row->currencyId;
    			if ($rec->currencyRate) {
    			    $rec->hiddenTransportCost = tcost_Calcs::calcInDocument($mvc, $rec->id) / $rec->currencyRate;
    			    $rec->expectedTransportCost = $mvc->getExpectedTransportCost($rec) / $rec->currencyRate;
    			    $rec->visibleTransportCost = $mvc->getVisibleTransportCost($rec) / $rec->currencyRate;
    			}
    			
    			tcost_Calcs::getVerbalTransportCost($row, $leftTransportCost, $rec->hiddenTransportCost, $rec->expectedTransportCost, $rec->visibleTransportCost);
    			
    			// Ако има транспорт за начисляване
    			if($leftTransportCost > 0){
    				
    				// Ако може да се добавят артикули в офертата
    				if(sales_QuotationsDetails::haveRightFor('add', (object)array('quotationId' => $rec->id))){
    				
    					// Добавяне на линк, за добавяне на артикул 'транспорт' със цена зададената сума
    					$transportId = cat_Products::fetchField("#code = 'transport'", 'id');
    					$packPrice = $leftTransportCost * $rec->currencyRate;
    				
    					$url = array('sales_QuotationsDetails', 'add', 'quotationId' => $rec->id, 'productId' => $transportId, 'packPrice' => $packPrice, 'optional' => 'no','ret_url' => TRUE);
    					$link = ht::createLink('Добавяне', $url, FALSE, array('ef_icon' => 'img/16/lorry_go.png', "style" => 'font-weight:normal;font-size: 0.8em', 'title' => 'Добавяне на допълнителен транспорт'));
    					$row->btnTransport = $link->getContent();
    				
    				}
    			}
    		}
    	}
    	
    	if($fields['-list']){
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    	
    	return $row;
    }

    
    /**
     * Колко е сумата на очаквания транспорт. 
     * Изчислява се само ако няма вариации в задължителните артикули
     *
     * @param stdClass $rec - запис на ред
     * @return double $expectedTransport - очаквания транспорт без ддс в основна валута
     */
    private function getExpectedTransportCost($rec)
    {
    	$expectedTransport = 0;
    	
    	// Ако няма калкулатор в условието на доставка, не се изчислява нищо
    	$TransportCalc = cond_DeliveryTerms::getCostDriver($rec->deliveryTermId);
    	if(!is_object($TransportCalc)) return $expectedTransport;
    	
    	// Подготовка на заявката, взимат се само задължителните складируеми артикули
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$rec->id}");
    	$query->where("#optional = 'no'");
    	$query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
    	$query->where("#canStore = 'yes'");
    	
    	$products = $query->fetchAll();
    	
    	// Изчисляване на общото тегло на офертата
    	$totalWeight = tcost_Calcs::getTotalWeight($products, $TransportCalc);
    	$locationId  = NULL;
    	if(isset($rec->deliveryPlaceId)){
    		$locationId  = crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id');
    	}
    	$codeAndCountryArr = tcost_Calcs::getCodeAndCountryId($rec->contragentClassId, $rec->contragentId, $rec->pCode, $rec->contragentCountryId, $locationId);
    	 
    	// За всеки артикул се изчислява очаквания му транспорт
    	foreach ($products as $p2){
    		$fee = tcost_Calcs::getTransportCost($rec->deliveryTermId, $p2->productId, $p2->packagingId, $p2->quantity, $totalWeight, $codeAndCountryArr['countryId'], $codeAndCountryArr['pCode']);
    
    		// Сумира се, ако е изчислен
    		if(is_array($fee) && $fee['totalFee'] != tcost_CostCalcIntf::CALC_ERROR){
    			$expectedTransport += $fee['totalFee'];
    		}
    	}
    	 
    	// Връщане на очаквания транспорт
    	return $expectedTransport;
    }
    
    
    /**
     * Колко е видимия транспорт начислен в сделката
     *
     * @param stdClass $rec - запис на ред
     * @return double - сумата на видимия транспорт в основна валута без ДДС
     */
    private function getVisibleTransportCost($rec)
    {
    	// Извличат се всички детайли и се изчислява сумата на транспорта, ако има
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$rec->id}");
    	$query->where("#optional = 'no'");
    	
    	return tcost_Calcs::getVisibleTransportCost($query);
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        
        $row->title = self::getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$hasTransport = !empty($data->rec->hiddenTransportCost) || !empty($data->rec->expectedTransportCost) || !empty($data->rec->visibleTransportCost);
    	
    	$isReadOnlyMode = Mode::isReadOnly();
    	
    	if($isReadOnlyMode){
    		$tpl->removeBlock('header');
    	}
    	
    	if($hasTransport === FALSE || $isReadOnlyMode || core_Users::haveRole('partner')){
    		$tpl->removeBlock('TRANSPORT_BAR');
    	}
    	
    	$tpl->push('sales/tpl/styles.css', 'CSS');
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($res == 'no_one') return;
    	
    	if($action == 'activate'){
    		if(!$rec->id) {
    			
    			// Ако документа се създава, то не може да се активира
    			$res = 'no_one';
    		} else {
    			
    			// За да се активира, трябва да има детайли
    			if(!sales_QuotationsDetails::fetchField("#quotationId = {$rec->id}")){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Ако офертата е изтекла и е затврорена, не може да се отваря
    	if($action == 'close' && isset($rec)){
    		if($rec->state == 'closed' && isset($rec->validFor) && isset($rec->date)){
    			$validTill = dt::verbal2mysql(dt::addSecs($rec->validFor, $rec->date), FALSE);
    			if($validTill < dt::today()){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Може да се създава към артикул само ако артикула е продаваем
    	if($action == 'add' && isset($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		if($origin->isInstanceOf('cat_Products')){
    			$canSell = $origin->fetchField('canSell');
    			if($canSell == 'no'){
    				$res = 'no_one';
    			}
    		}
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
        $tpl = new ET(tr("Моля запознайте се с нашата оферта") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
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
    	
    	return cls::haveInterface('crm_ContragentAccRegIntf', $coverClass);
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова  артикула, активираме и нея
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
    	if($rec->deliveryPlaceId){
		    if(!crm_Locations::fetchField(array("#title = '[#1#]'", $rec->deliveryPlaceId), 'id')){
		    	$newLocation = (object)array(
		    						'title'         => $rec->deliveryPlaceId,
		    						'countryId'     => $rec->contragentCountryId,
		    						'pCode'         => $rec->pcode,
		    						'place'         => $rec->place,
		    						'contragentCls' => $rec->contragentClassId,
		    						'contragentId'  => $rec->contragentId,
		    						'type'          => 'correspondence');
		    		
		    	// Ако локацията я няма в системата я записваме
		    	crm_Locations::save($newLocation);
		    }
		}
		
		// Ако няма дата попълваме текущата след активиране
		if(empty($rec->date)){
			$rec->date = dt::today();
			$mvc->save($rec, 'date');
		}
		
		// Ако запитването е в папка на контрагент вкарва се в група запитвания
		$groupId = crm_Groups::force('Клиенти » Оферти');
		cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $groupId, FALSE);
    }
    
    
    /**
     * Връща масив от използваните документи в офертата
     * 
     * @param int $id - ид на оферта
     * @return param $res - масив с използваните документи
     * 					['class'] - Инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	return deals_Helper::getUsedDocs($this, $id);
    }
    
    
    /**
     * Помощна ф-я за връщане на всички продукти от офертата.
     * Ако има вариации на даден продукт и не може да се
     * изчисли общата сума ф-ята връща NULL
     * 
     * @param int $id - ид на оферта
     * @param boolean $onlyStorable - дали да са само складируемите
     * @return array|NULL - продуктите
     */
    private function getItems($id, $onlyStorable = FALSE, $groupByProduct = FALSE)
    {
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$id} AND #optional = 'no'");
    	
    	if($onlyStorable === TRUE){
    		$query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
    		$query->where("#canStore = 'yes'");
    	}
    	
    	$products = array();
    	while($detail = $query->fetch()){
    		$index = ($groupByProduct === TRUE) ? $detail->productId : "{$detail->productId}|{$detail->packagingId}";
    		
    		if(array_key_exists($index, $products) || !$detail->quantity) return NULL;
    		$products[$index] = $detail;
    	}
    	
    	return (count($products)) ? array_values($products) : NULL;
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Оферта нормален изглед', 'content' => 'sales/tpl/QuotationHeaderNormal.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Оферта изглед за писмо', 'content' => 'sales/tpl/QuotationHeaderLetter.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Quotation', 'content' => 'sales/tpl/QuotationHeaderNormalEng.shtml', 'lang' => 'en');
    	
    	$res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
     /**
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$query = sales_QuotationsDetails::getQuery();
     	
     	// точно на тази оферта детайлите търсим
     	$query->where("#quotationId  = '{$rec->id}'");
     	
	        while ($recDetails = $query->fetch()){
	        	// взимаме заглавията на продуктите
	        	$productTitle = cat_Products::getTitleById($recDetails->productId);
	        	// и ги нормализираме
	        	$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
	        }
	         
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
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

        $date = dt::mysql2verbal($rec->date, 'd.m.year'); 

        $crm = cls::get($rec->contragentClassId);

        $cRec =  $crm->getContragentData($rec->contragentId);
        
        $contragent = str::limitLen($cRec->company ? $cRec->company : $cRec->person, 32);
        
        if($escaped) {
            $contragent = type_Varchar::escape($contragent);
        }

    	return "{$abbr}{$rec->id}/{$date} {$contragent}";
    }
    
    
    /**
     * Създаване на продажба от оферта
     * @param stdClass $rec
     * @return mixed
     */
    private function createSale($rec)
    {
    	$templateId = sales_Sales::getDefaultTemplate((object)array('folderId' => $rec->folderId));
    	
    	// Подготвяме данните на мастъра на генерираната продажба
    	$fields = array('currencyId'         => $rec->currencyId,
    					'currencyRate'       => $rec->currencyRate,
    					'reff'       		 => ($rec->reff) ? $rec->reff : $this->getHandle($rec->id),
    					'paymentMethodId'    => $rec->paymentMethodId,
    					'deliveryTermId'     => $rec->deliveryTermId,
    					'chargeVat'          => $rec->chargeVat,
    					'note'				 => $rec->others,
    					'originId'			 => $rec->containerId,
    					'template'			 => $templateId,
    					'deliveryLocationId' => crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id'),
    	);
    	
    	// Създаваме нова продажба от офертата
    	return sales_Sales::createNewDraft($rec->contragentClassId, $rec->contragentId, $fields);
    }
    
    
    /**
     * Екшън генериращ продажба от оферта
     */
    function act_CreateSale()
    {
    	sales_Sales::requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetchRec($id));
    	expect($rec->state = 'active');
    	expect($items = $this->getItems($id));
    	
    	$force = Request::get('force', 'int');
    	
    	// Ако не форсираме нова продажба
    	if(!$force){
    		// Опитваме се да намерим съществуваща чернова продажба
    		if(!Request::get('dealId', 'key(mvc=sales_Sales)') && !Request::get('stop')){
    			return new Redirect(array('sales_Sales', 'ChooseDraft', 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId, 'ret_url' => TRUE, 'quotationId' => $rec->id));
    		}
    	}
    	
    	// Ако няма създаваме нова
    	if(!$sId = Request::get('dealId', 'key(mvc=sales_Sales)')){
    		
    		// Създаваме нова продажба от офертата
    		$sId = $this->createSale($rec);
    	}
    	
    	// За всеки детайл на офертата подаваме го като детайл на продажбата
    	foreach ($items as $item){
    		$addedRecId = sales_Sales::addRow($sId, $item->productId, $item->packQuantity, $item->price, $item->packagingId, $item->discount, $item->tolerance, $item->term, $item->notes);
    		
    		// Копира се и транспорта, ако има
    		$cRec = tcost_Calcs::get($this, $item->quotationId, $item->id);
    		if(isset($cRec)){
    			tcost_Calcs::sync('sales_Sales', $sId, $addedRecId, $cRec->fee, $cRec->deliveryTime);
    		}
    	}
    	
    	// Записваме, че потребителя е разглеждал този списък
    	$this->logWrite("Създаване на продажба от оферта", $id);
    	
    	// Редирект към новата продажба
    	return new Redirect(array('sales_Sales', 'single', $sId), '|Успешно е създадена продажба от офертата');
    }
    
    
    /**
     * Екшън за създаване на заявка от оферта
     */
    public function act_FilterProductsForSale()
    {
    	sales_Sales::requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'active');
    	sales_Sales::requireRightFor('add', (object)array('folderId' => $rec->folderId));
    	
    	// Подготовка на формата за филтриране на данните
    	$form = $this->getFilterForm($rec->id, $id);
    	
    	$fRec = $form->input();
    	
    	if($form->isSubmitted()){
    		
    		// Създаваме продажба от офертата
    		$sId = $this->createSale($rec);
    		
    		$products = (array)$form->rec;
    		foreach ($products as $index => $quantity){
    			list($productId, $optional, $packagingId, $quantityInPack) = explode("|", $index);
    			$quantityInPack = str_replace('_', '.', $quantityInPack);
    			
    			// При опционален продукт без к-во се продължава
    			if($optional == 'yes' && empty($quantity)) continue;
    			$quantity = $quantity * $quantityInPack;
    			
    			// Опитваме се да намерим записа съотвестващ на това количество
    			$where = "#quotationId = {$id} AND #productId = {$productId} AND #optional = '{$optional}' AND #quantity = {$quantity}";
    			$where .= ($packagingId) ? " AND #packagingId = {$packagingId}" : " AND #packagingId IS NULL";
    			$dRec = sales_QuotationsDetails::fetch($where);
    			
    			if(!$dRec){
    				
    				// Ако няма (к-то е друго) се намира първия срещнат
    				$dRec = sales_QuotationsDetails::fetch("#quotationId = {$id} AND #productId = {$productId} AND #packagingId = {$packagingId} AND #optional = '{$optional}'");
    				
    				// Тогава приемаме, че подаденото количество е количество за опаковка
    				$dRec->packQuantity = $quantity;
    			} else {
    				
    				// Ако има такъв запис, изчисляваме колко е количеството на опаковката
    				$dRec->packQuantity = $quantity / $dRec->quantityInPack;
    			}
    			
    			// Добавяме детайла към офертата
    			$addedRecId = sales_Sales::addRow($sId, $dRec->productId, $dRec->packQuantity, $dRec->price, $dRec->packagingId, $dRec->discount, $dRec->tolerance, $dRec->term, $dRec->notes);
    			
    			// Копира се и транспорта, ако има
    			$fee = tcost_Calcs::get($this, $id, $dRec->id)->fee;
    			if(isset($fee)){
    				tcost_Calcs::sync('sales_Sales', $sId, $addedRecId, $fee);
    			}
    		}
    		 
    		// Редирект към сингъла на новосъздадената продажба
    		return new Redirect(array('sales_Sales', 'single', $sId));
    	}
    
    	if(core_Users::haveRole('partner')){
    		plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
    	}
    	
    	// Рендираме опаковката
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     * 
     * @param int $id - ид на записа
     * @return core_Form - готовата форма
     */
    private function getFilterForm($id)
    {
    	$form = cls::get('core_Form');
    	
    	$form->title = 'Създаване на продажба от|* ' . sales_Quotations::getFormTitleLink($id);
    	$form->info = tr('Моля уточнете количествата');
    	$filteredProducts = $this->filterProducts($id);
    	
    	foreach ($filteredProducts as $index => $product){
    		
    		if($product->optional == 'yes') {
    			$product->title = "Опционални->{$product->title}";
    			$product->options = array('' => '') + $product->options;
    			$mandatory = '';
    		} else {
    			$product->title = "Оферирани->{$product->title}";
    			if(count($product->options) > 1) {
    				$product->options = array('' => '') + $product->options;
    				$mandatory = 'mandatory';
    			} else {
    				$mandatory = '';
    			}
    		}
    
    		$form->FNC($index, "double(decimals=2)", "input,caption={$product->title},{$mandatory}");
    		if($product->suggestions){
    			$form->setSuggestions($index, $product->options);
    		} else {
    			$form->setOptions($index, $product->options);
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Създай', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title = Прекратяване на действията');
    	 
    	return $form;
    }
    
    
    /**
     * Групира продуктите от офертата с техните к-ва
     * 
     * @param int $id - ид на оферта
     * @return array $products - филтрираните продукти
     */
    private function filterProducts($id)
    {
    	$products = array();
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$id}");
    	$query->orderBy('optional', 'ASC');
    	
    	while ($rec = $query->fetch()){
    		$quantityInPack = str_replace('.', '_', $rec->quantityInPack);
    		$index = "{$rec->productId}|{$rec->optional}|{$rec->packagingId}|{$quantityInPack}";
    		
    		if(!array_key_exists($index, $products)){
    			$title = cat_Products::getTitleById($rec->productId);
    			if($rec->packagingId){
    				$title .= " / " . cat_UoM::getShortName($rec->packagingId);
    			}
    			$products[$index] = (object)array('title' => $title, 'options' => array(), 'optional' => $rec->optional, 'suggestions' => FALSE);
    		}
    		
    		if($rec->optional == 'yes'){
    			$products[$index]->suggestions = TRUE;
    		}
    		
    		if($rec->quantity){
    			$pQuantity = $rec->quantity / $rec->quantityInPack;
    			$products[$index]->options[$pQuantity] = $pQuantity;
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * След извличане на името на документа за показване в RichText-а
     */
    protected static function on_AfterGetDocNameInRichtext($mvc, &$docName, $id)
    {
    	// Ако има реф да се показва към името му
    	$reff = $mvc->getVerbal($id, 'reff');
    	if(strlen($reff) != 0){
    		$docName .= "({$reff})";
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if($rec->reff === ''){
    		$rec->reff = NULL;
    	}
    }
    
    
    /**
     * Затваряне на изтекли оферти по крон
     */
    function cron_CloseQuotations()
    {
    	$today = dt::today();
    	
    	// Селектираме тези фактури, с изтекла валидност
    	$query = $this->getQuery();
    	$query->where("#state = 'active'");
    	$query->where("#validFor IS NOT NULL");
    	$query->XPR('expireOn', 'datetime', 'CAST(DATE_ADD(#date, INTERVAL #validFor SECOND) AS DATE)');
    	$query->where("#expireOn < '{$today}'");
    	$query->show("id");
    	
    	// Затваряме ги
    	while($rec = $query->fetch()){
    		try{
    			$rec->state = 'closed';
    			$this->save_($rec, 'state');
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	if(Request::get('Rejected', 'int')) return;
    	
    	$data->listFilter->FNC('sState', 'enum(all=Всички,draft=Чернова,active=Активен,closed=Приключен)', 'caption=Състояние,autoFilter');
    	$data->listFilter->showFields .= ',sState';
    	$data->listFilter->setDefault('sState', 'active');
    	$data->listFilter->input();
    	
    	if($rec = $data->listFilter->rec){
    		if(isset($rec->sState) && $rec->sState != 'all'){
    			$data->query->where("#state = '{$rec->sState}'");
    		}
    	}
    }
    
    
    /*
     * API за генериране на оферти
    */
    
    
    /**
     * Метод за бързо създаване на чернова сделка към контрагент
     *
     * @param mixed $contragentClass - ид/инстанция/име на класа на котрагента
     * @param int $contragentId      - ид на контрагента
     * @param int $date              - дата
     * @param array $fields          - стойности на полетата на сделката
     *
     *   o $fields['originId']        - вальор (ако няма е текущата дата)
     *   o $fields['reff']            - вашия реф на продажбата
     *   o $fields['currencyCode']    - код на валута (ако няма е основната за периода)
     * 	 o $fields['rate']            - курс към валутата (ако няма е този към основната валута)
     * 	 o $fields['paymentMethodId'] - ид на платежен метод (Ако няма е плащане в брой, @see cond_PaymentMethods)
     * 	 o $fields['chargeVat']       - да се начислява ли ДДС - yes=Да, separate=Отделен ред за ДДС, exempt=Освободено,no=Без начисляване(ако няма, се определя според контрагента)
     * 	 o $fields['deliveryTermId']  - ид на метод на доставка (@see cond_DeliveryTerms)
     * 	 o $fields['validFor']        - срок на годност
     *   o $fields['company']         - фирма
     *   o $fields['person']          - лице
     *   o $fields['email']           - имейли
     *   o $fields['tel']             - телефон
     *   o $fields['fax']             - факс
     *   o $fields['pCode']           - пощенски код
     *   o $fields['place']           - град
     *   o $fields['address']         - адрес
     *  
     * @return mixed $id/FALSE       - ид на запис или FALSE
     */
    public static function createNewDraft($contragentClass, $contragentId, $date = NULL, $fields = array())
    {
    	// Проверки
    	expect($Cover = cls::get($contragentClass), 'Невалиден клас');
    	expect(cls::haveInterface('crm_ContragentAccRegIntf', $Cover), 'Класа не е на контрагент');
    	expect($Cover->fetch($contragentId), 'Няма такъв контрагент');
    	expect($data = $Cover->getContragentData($contragentId), 'Няма данни за контрагента');
    	
    	// Подготовка на мастъра
    	$newRec = new stdClass();
    	$newRec->date = (isset($date)) ? $date : NULL;
    	$newRec->reff = (isset($fields['reff'])) ? $fields['reff'] : NULL;
    	$newRec->contragentClassId = $Cover->getClassId();
    	$newRec->contragentId = $contragentId;
    	$newRec->originId = (isset($fields['originId'])) ? $fields['originId'] : NULL;
    	if(isset($newRec->originId)){
    		$origin = doc_Containers::getDocument($newRec->originId);
    		$newRec->folderId = $origin->fetchField('folderId');
    		$newRec->threadId = $origin->fetchField('threadId');
    	} else {
    		$newRec->folderId = $Cover->forceCoverAndFolder($contragentId);
    	}
    	
    	$newRec->currencyId = (isset($fields['currencyCode'])) ? $fields['currencyCode'] : $Cover->getDefaultcurrencyId($contragentId);
    	expect(currency_Currencies::getIdByCode($newRec->currencyId), 'Невалиден код');
    	$newRec->currencyRate = (isset($fields['rate'])) ? $fields['rate'] : currency_CurrencyRates::getRate($newRec->date, $newRec->currencyId, NULL);
    	expect(cls::get('type_Double')->fromVerbal($newRec->currencyRate), 'Невалиден курс');
    	$newRec->chargeVat = (isset($fields['chargeVat'])) ? $fields['chargeVat'] : (($Cover->shouldChargeVat($contragentId)) ? 'yes' : 'no');
    	expect(in_array($newRec->chargeVat, array('yes', 'no', 'exempt', 'separate')), 'Невалидно ДДС');
    	
    	// Намиране на метода за плащане
    	$newRec->paymentMethodId = (isset($fields['paymentMethodId'])) ? $fields['paymentMethodId'] : cond_Parameters::getParameter($Cover->getClassId(), $contragentId, 'paymentMethodSale');
    	if(isset($newRec->paymentMethodId)){
    		expect(cond_PaymentMethods::fetch($newRec->paymentMethodId), 'Невалиден метод за плащане');
    	}
    	
    	// Условието на доставка
    	$newRec->deliveryTermId = (isset($fields['deliveryTermId'])) ? $fields['deliveryTermId'] : cond_Parameters::getParameter($Cover->getClassId(), $contragentId, 'deliveryTermSale');
    	if(isset($newRec->deliveryTermId)){
    		expect(cond_DeliveryTerms::fetch($newRec->deliveryTermId), 'Невалидно условие на доставка');
    	}
    	
    	// Срока на валидност
    	$newRec->validFor = (isset($fields['validFor'])) ? $fields['validFor'] : NULL;
    	if(isset($newRec->validFor)){
    		expect(type_Int::isInt($newRec->validFor), 'Срока ан валидност трябва да е в секунди');
    	}
    	
    	// Адресните данни
    	foreach (array('company', 'person', 'email', 'tel', 'fax', 'pCode', 'place', 'address') as $fld){
    		if(isset($fields[$fld])){
    			expect($newRec->{$fld} = cls::get('type_Varchar')->fromVerbal($fields[$fld]), 'Невалидни адресни данни');
    		} else {
    			if(($Cover instanceof crm_Persons) && $fld == 'address'){
    				$fld = "p".ucfirst($fld);
    			}
    			if(!empty($data->{$fld})){
    				$newRec->{$fld} = $data->{$fld};
    			}
    		}
    	}
    	
    	// Държавата
    	$newRec->contragentCountryId = (isset($fields['countryId'])) ? $fields['countryId'] : $data->countryId;
    	expect(drdata_Countries::fetch($newRec->contragentCountryId), 'Невалидна държава');
    	$newRec->template = self::getDefaultTemplate($newRec);
    	
    	// Създаване на запис
    	self::route($newRec);
    	
    	return self::save($newRec);
    }
    
    
    /**
     * Добавя нов ред в главния детайл на чернова сделка.
     * Ако има вече такъв артикул добавен към сделката, наслагва к-то, цената и отстъпката
     * на новия запис към съществуващия (цените и отстъпките стават по средно притеглени)
     *
     * @param int $id 			   - ид на сделка
     * @param int $productId	   - ид на артикул
     * @param double $packQuantity - количество продадени опаковки (ако няма опаковки е цялото количество)
     * @param int $packagingId     - ид на опаковка (не е задължителна)
     * @param double $price        - цена на единична бройка, без ДДС в основна валута
     * @param boolean $optional    - дали артикула е опционален или не
     * @param array $fields        - масив с допълнителни параметри
     * 		 double ['discount']       - отстъпка (опционална)
     *       double ['tolerance']      - толеранс (опционален)
     *       mixed  ['term']           - срок на доставка (опционален)
     *       html   ['notes']          - забележки (опционален)
     *       double ['quantityInPack'] - к-во в опаковка (опционален)
     *          
     * @return mixed $id/FALSE     - ид на запис или FALSE
     */
    public static function addRow($id, $productId, $packQuantity, $packagingId = NULL, $price = NULL, $optional = FALSE, $other = array())
    {
    	// Проверка на параметрите
    	expect($rec = self::fetch($id), "Няма такава оферта");
    	expect($rec->state == 'draft', 'Офертата трябва да е чернова');
    	expect($productId, 'Трябва да е подаден артикул');
    	expect($productRec = cat_Products::fetch($productId, 'id,canSell,measureId'), 'Няма такъв артикул');
    	expect($productRec->canSell == 'yes', 'Артикулът не е продаваем');
    	expect($packQuantity = cls::get('type_Double')->fromVerbal($packQuantity), 'Невалидно количество');
    	
    	// Подготовка на записа
    	$newRec = new stdClass();
    	$newRec->quotationId = $rec->id;
    	$newRec->productId = $productId;
    	$newRec->showMode = 'auto';
    	$newRec->vatPercent = cat_Products::getVat($productId, $rec->date);
    	$newRec->optional = ($optional === TRUE) ? 'yes' : 'no';
    	expect(in_array($newRec->optional, array('yes', 'no')));
    	
    	// Проверка на опаковката
    	$newRec->packagingId = isset($packagingId) ? $packagingId : $productRec->measureId;
    	$packs = cat_Products::getPacks($productId);
    	expect(array_key_exists($newRec->packagingId, $packs), 'Артикулът няма такава опаковка');
    	
    	// Намиране на к-то в опаковка
    	$pack = cat_products_Packagings::getPack($productId, $packagingId);
    	$newRec->quantityInPack = (isset($other['quantityInPack'])) ? $other['quantityInPack'] : ((is_object($pack)) ? $pack->quantity : 1);
    	expect($newRec->quantityInPack = cls::get('type_Double')->fromVerbal($newRec->quantityInPack), 'Проблем с количеството в опаковка');
    	
    	// Колко е общото количество
    	$newRec->quantity = $newRec->quantityInPack * $packQuantity;
    	
    	// Дали отстъпката е между 0 и 1
    	if(isset($other['discount'])){
    		expect($newRec->discount = cls::get('type_Double')->fromVerbal($other['discount']));
    		expect($newRec->discount >= 0 && $newRec->discount <= 1, 'Отстъпката трябва да е между 0 и 1');
    	}
    	
    	// Дали толеранса е между 0 и 1
    	if(isset($other['tolerance'])){
    		expect($newRec->tolerance = cls::get('type_Double')->fromVerbal($other['tolerance']));
    		expect($newRec->tolerance >= 0 && $newRec->tolerance <= 1);
    	}
    	
    	if(isset($other['term'])){
    		expect($newRec->term = cls::get('type_Time')->fromVerbal($other['term']));
    	}
    	
    	if(isset($other['notes'])){
    		$newRec->notes = cls::get('type_Richtext')->fromVerbal($other['notes']);
    	}
    	
    	// Ако няма цена, прави се опит да се намери
    	if(isset($price)){
    		$newRec->price = $price;
    		expect($newRec->price = cls::get('type_Double')->fromVerbal($newRec->price), 'Невалидна цена');
    	}
    	
    	// Изчисляване на транспортните разходи
    	if(core_Packs::isInstalled('tcost')){
    		$form = sales_QuotationsDetails::getForm();
    		tcost_Calcs::prepareFee($newRec, $form, $rec, array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId'));
    	}
    	
    	// Проверки на записите
    	if($sameProduct = sales_QuotationsDetails::fetch("#quotationId = {$newRec->quotationId} AND #productId = {$newRec->productId}")){
    		if($newRec->optional == 'no' && $sameProduct->optional == 'yes'){
    			expect(FALSE, 'Не може да добавите продукта като задължителен, защото фигурира вече като опционален');
    		}
    	}
    	
    	if($sameProduct = sales_QuotationsDetails::fetch("#quotationId = {$newRec->quotationId} AND #productId = {$newRec->productId}  AND #quantity='{$newRec->quantity}'")){
    		expect(FALSE, 'Избрания продукт вече фигурира с това количество');
    	}
    	
    	// Запис на детайла
    	return sales_QuotationsDetails::save($newRec);
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     *
     * @param unknown_type $mvc
     * @param unknown_type $rec
     */
    public static function on_BeforeActivation($mvc, $res)
    {
    	$quotationId = $res->id;
    	$rec = $mvc->fetch($quotationId);
    	
    	$error = array();
    	$saveRecs = array();
    	$dQuery = sales_QuotationsDetails::getQuery();
    	$dQuery->where("#quotationId = {$quotationId}");
    	$dQuery->where("#price IS NULL || #tolerance IS NULL || #term IS NULL");
    	while($dRec = $dQuery->fetch()){
    		if(!isset($dRec->price)){
    			sales_QuotationsDetails::calcLivePrice($dRec, $rec);
    			
    			if(!isset($dRec->price)){
    				$error[] = cat_Products::getTitleById($dRec->productId);
    			}
    		}
    		
    		if(!isset($dRec->term)){
    			if($term = cat_Products::getDeliveryTime($dRec->productId, $dRec->quantity)){
    				if($deliveryTime = tcost_Calcs::get('sales_Quotations', $dRec->quotationId, $dRec->id)->deliveryTime){
    					$term += $deliveryTime;
    				}
    				$dRec->term = $term;
    			}
    		}
    		
    		if(!isset($dRec->tolerance)){
    			if($tolerance = cat_Products::getTolerance($dRec->productId, $dRec->quantity)){
    				$dRec->tolerance = $tolerance;
    			}
    		}
    		
    		$saveRecs[] = $dRec;
    	}
    	
    	if(count($error)){
    		$imploded = implode(', ', $error);
    		$start = (count($error) == 1) ? 'артикулът' : 'артикулите';
    		$mid = (count($error) == 1) ? 'му' : 'им';
    		$msg = "На {$start}|* <b>{$imploded}</b> |трябва да {$mid} се въведе цена|*";
    		
    		core_Statuses::newStatus($msg, 'error');
    		return FALSE;
    	}
    	
    	cls::get(sales_QuotationsDetails)->saveArray($saveRecs);
    }
}