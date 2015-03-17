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
 * @copyright 2006 - 2015 Experta OOD
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
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_Quotes';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_EmailCreatePlg, acc_plg_DocumentSummary, plg_Search, doc_plg_HidePrices, doc_plg_TplManager,
                    doc_DocumentPlg, plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc, cond_plg_DefaultValues';
       
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Поле за търсене по дата
     */
    public $filterDateField = 'date';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, date, folderId, state, createdOn,createdBy';
    

    /**
     * Детайла, на модела
     */
    public $details = 'sales_QuotationsDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оферта';
   
   
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'paymentMethodId, reff, company, person, email, folderId, id';
    
   
    /**
     * Брой оферти на страница
     */
    public $listItemsPerPage = '20';
    
    
    /**
      * Групиране на документите
      */ 
    public $newBtnGroup = "3.7|Търговия";
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'validFor'        => 'lastDocUser|lastDoc',
    	'paymentMethodId' => 'clientCondition|lastDocUser|lastDoc',
        'currencyId'      => 'lastDocUser|lastDoc|CoverMethod',
        'chargeVat'       => 'lastDocUser|lastDoc|defMethod',
    	'others'          => 'lastDocUser|lastDoc',
        'deliveryTermId'  => 'clientCondition|lastDocUser|lastDoc',
        'deliveryPlaceId' => 'lastDocUser|lastDoc|',
        'company'         => 'lastDocUser|lastDoc|clientData',
        'person' 		  => 'lastDocUser|lastDoc|clientData',
        'email' 		  => 'lastDocUser|lastDoc|clientData',
    	'tel' 			  => 'lastDocUser|lastDoc|clientData',
        'fax' 			  => 'lastDocUser|lastDoc|clientData',
        'country'		  => 'lastDocUser|lastDoc|clientData',
        'pCode' 		  => 'lastDocUser|lastDoc|clientData',
    	'place' 		  => 'lastDocUser|lastDoc|clientData',
    	'address' 		  => 'lastDocUser|lastDoc|clientData',
    	'template' 		  => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date', 'caption=Дата, mandatory'); 
        $this->FLD('reff', 'varchar(255)', 'caption=Ваш реф.,class=contactData');
        
        $this->FNC('row1', 'complexType(left=К-во,right=Цена)', 'caption=Детайли->К-во / Цена');
    	$this->FNC('row2', 'complexType(left=К-во,right=Цена)', 'caption=Детайли->К-во / Цена');
    	$this->FNC('row3', 'complexType(left=К-во,right=Цена)', 'caption=Детайли->К-во / Цена');
    	
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=description,allowEmpty)','caption=Плащане->Метод,salecondSysId=paymentMethodSale');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,oldFieldName=paymentCurrencyId');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Плащане->Курс,oldFieldName=rate');
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)','caption=Плащане->ДДС,oldFieldName=vat');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,salecondSysId=deliveryTermSale');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Място,hint=Изберете локация или въведете нова');
        
		$this->FLD('company', 'varchar', 'caption=Получател->Фирма, changable, class=contactData');
        $this->FLD('person', 'varchar', 'caption=Получател->Лице, changable, class=contactData');
        $this->FLD('email', 'varchar', 'caption=Получател->Имейл, changable, class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Получател->Тел., changable, class=contactData');
        $this->FLD('fax', 'varchar', 'caption=Получател->Факс, changable, class=contactData');
        $this->FLD('country', 'varchar', 'caption=Получател->Държава, changable, class=contactData');
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
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->showFields = 'search,' . $data->listFilter->showFields;
    	 $data->listFilter->input();
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $rec = &$data->form->rec;
       if(empty($rec->id)){
       	  $mvc->populateDefaultData($data->form);
       } else {
       		if($mvc->sales_QuotationsDetails->fetch("#quotationId = {$data->form->rec->id}")){
	       		foreach (array('chargeVat', 'currencyRate', 'currencyId', 'deliveryTermId') as $fld){
	        		$data->form->setReadOnly($fld);
	        	}
	       	}
       }
      
       $locations = crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId, FALSE);
       $data->form->setSuggestions('deliveryPlaceId',  array('' => '') + $locations);
      
       if($rec->originId){
       	
       		// Ако офертата има ориджин
       		$data->form->setField('row1,row2,row3', 'input');
       		$origin = doc_Containers::getDocument($rec->originId);
       		
       		if($origin->haveInterface('cat_ProductAccRegIntf')){
       			$Policy = $origin->getPolicy();
       			$price = $Policy->getPriceInfo($rec->contragentClassId, $rec->contragentId, $origin->that, $origin->getInstance()->getClassId())->price;
	       		
       			// Ако няма цена офертата потребителя е длъжен да я въведе от формата
	       		if(!$price){
	       			$data->form->setFieldTypeParams('row1', 'require=both');
	       			$data->form->setFieldTypeParams('row2', 'require=both');
	       			$data->form->setFieldTypeParams('row3', 'require=both');
	       		}
       		}
       }
       
       if(!$rec->person){
       	  $data->form->setSuggestions('person', crm_Companies::getPersonOptions($rec->contragentId, FALSE));
       }
       
       $data->form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    }
    
    
	/** 
	 * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
	    if($data->rec->state == 'active'){
	    	if(sales_Sales::haveRightFor('add', (object)array('folderId' => $data->rec->folderId))){
	    		$items = $mvc->getItems($data->rec->id);
	    		if(sales_QuotationsDetails::fetch("#quotationId = {$data->rec->id} AND #optional = 'yes'") || !$items){
	    			$data->toolbar->addBtn('Продажба', array($mvc, 'FilterProductsForSale', $data->rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/star_2.png,title=Създаване на продажба по офертата');
	    		} else {
	    			$warning = '';
	    			$title = 'Прехвърляне на артикулите в съществуваща чернова продажба';
	    			if(!sales_Sales::count("#state = 'draft' AND #contragentId = {$data->rec->contragentId} AND #contragentClassId = {$data->rec->contragentClassId}")){
	    				$warning = 'warning=Сигурнили сте че искате да създадете продажба?';
	    				$title = 'Създаване на продажба от офертата';
	    			}
	    			
	    			$data->toolbar->addBtn('Продажба', array($mvc, 'CreateSale', $data->rec->id, 'ret_url' => TRUE), "{$warning}", "ef_icon=img/16/star_2.png,title={$title}");
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
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	
		    if(!$rec->currencyRate){
			    $rec->currencyRate = round(currency_CurrencyRates::getRate($rec->date, $rec->currencyId, NULL), 4);
			}
		
			if(!$rec->currencyRate){
				$form->setError('currencyRate', "Не може да се изчисли курс");
				return;
			}
			
	    	if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, $rec->date, $rec->currencyId, NULL)){
			    $form->setWarning('rate', $msg);
			}
		}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	if($rec->originId){
    		$origin = doc_Containers::getDocument($rec->originId);
    		
    		// Ориджина трябва да е спецификация
    		$originRec = $origin->fetch();
    		
    		// В папка на контрагент
    		$coverClass = doc_Folders::fetchCoverClassName($originRec->folderId);
    		
    		$dRows = array($rec->row1, $rec->row2, $rec->row3);
    		if(($dRows[0] || $dRows[1] || $dRows[2])){
    			$mvc->sales_QuotationsDetails->insertFromSpecification($rec, $origin, $dRows);
			}
    	}
    }
    
    
    /**
     * Попълване на дефолт данни
     */
    public function populateDefaultData(core_Form &$form)
    {
    	$form->setDefault('date', dt::now());
    	expect($data = doc_Folders::getContragentData($form->rec->folderId), "Проблем с данните за контрагент по подразбиране");
    	$contragentClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
    	$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
    	$form->setDefault('contragentClassId', $contragentClassId);
    	$form->setDefault('contragentId', $contragentId);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
		if($fields['-single']){
			$quotDate = dt::mysql2timestamp($rec->date);
			$timeStamp = dt::mysql2timestamp(dt::verbal2mysql());
			
			if(isset($rec->validFor)){
				
				// До коя дата е валидна
				$row->validDate = dt::addSecs($rec->validFor, $rec->date);
				$row->validDate = $mvc->getFieldType('date')->toVerbal($row->validDate);
			}
			
			if(isset($rec->validFor) && (($quotDate + $rec->validFor) < $timeStamp)){
				$row->expired = tr("офертата е изтекла");
			}
			
	    	$row->number = $mvc->getHandle($rec->id);
			$row->username = core_Users::recToVerbal(core_Users::fetch($rec->createdBy), 'names')->names;
			$profRec = crm_Profiles::fetchRec("#userId = {$rec->createdBy}");
			if($position = crm_Persons::fetchField($profRec->personId, 'buzPosition')){
				$row->position = cls::get('type_Varchar')->toVerbal($position);
			}
			
			$contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
			$row->contragentAddress = $contragent->getFullAdress();
			
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
			}
			
			$ownCompanyData = crm_Companies::fetchOwnCompany();
	        $Companies = cls::get('crm_Companies');
	        $row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
	        $row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
		}
		
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    }
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Оферта №" .$this->abbr . $rec->id;
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
	  	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	$tpl->push('sales/tpl/styles.css', 'CSS');
    }
    
    
    /**
     * След проверка на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
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
    	
    	if($action == 'edit'){
    		$res = 'ceo,sales';
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
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
    	
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
	/**
     * Документи-оферти могат да се добавят само в папки с корица контрагент.
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова спецификация, активираме и нея
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	if($rec->originId){
    		$origin = doc_Containers::getDocument($rec->originId);
	    	if($origin->haveInterface('cat_ProductAccRegIntf')){
	    		$originRec = $origin->fetch();
	    		if($originRec->state == 'draft'){
	    			$originRec->state = 'active';
	    			$origin->getInstance()->save($originRec);
	    			
	    			$msg = "|Активиран е документ|* #{$origin->abbr}{$origin->that}";
	    			core_Statuses::newStatus(tr($msg));
	    		}		
	    	}
    	}
    	
    	if($rec->deliveryPlaceId){
		    if(!crm_Locations::fetchField(array("#title = '[#1#]'", $rec->deliveryPlaceId), 'id')){
		    	$newLocation = (object)array(
		    						'title'         => $rec->deliveryPlaceId,
		    						'countryId'     => drdata_Countries::fetchField("#commonNameBg = '{$rec->country}' || #commonName = '{$rec->country}'", 'id'),
		    						'pCode'         => $rec->pcode,
		    						'place'         => $rec->place,
		    						'contragentCls' => $rec->contragentClassId,
		    						'contragentId'  => $rec->contragentId,
		    						'type'          => 'correspondence');
		    		
		    	// Ако локацията я няма в системата я записваме
		    	crm_Locations::save($newLocation);
		    }
		}
    }
    
    
    /**
     * Връща масив от използваните документи в офертата
     * @param int $id - ид на оферта
     * @return param $res - масив с използваните документи
     * 					['class'] - Инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_QuotationsDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Quotations', 'externalKey=quotationId');
    	$dQuery->where("#quotationId = '{$id}'");
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
     * Помощна ф-я за връщане на всички продукти от офертата.
     * Ако има вариации на даден продукт и не може да се
     * изчисли общата сума ф-ята връща NULL
     * 
     * @param int $id - ид на оферта
     * @return array - продуктите
     */
    private function getItems($id)
    {
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$id} AND #optional = 'no'");
    	
    	$products = array();
    	while($detail = $query->fetch()){
    		$index = "{$detail->productId}|{$detail->packagingId}";
    		if(array_key_exists($index, $products) || !$detail->quantity) return NULL;
    		$products[$index] = $detail;
    	}
    	
    	return array_values($products);
    }
    
    
    /**
     * Интерфейсен метод (@see doc_ContragentDataIntf::getContragentData)
     */
	static function getContragentData($id)
    {
        //Вземаме данните от визитката
        $rec = static::fetch($id);
        if(!$rec) return;
        
        $contrData = new stdClass();
        $contrData->company = $rec->company;
         
        //Заместваме и връщаме данните
        if (!$rec->person) {
        	$contrData->companyId = $rec->contragentId;
            $contrData->tel = $rec->tel;
            $contrData->fax = $rec->fax;
            $contrData->pCode = $rec->pCode;
            $contrData->place = $rec->place;
            $contrData->address = $rec->address;
            $contrData->email = $rec->email;
        } else {
        	$contrData->person = $rec->person;
            $contrData->pTel = $rec->tel;
            $contrData->pFax = $rec->fax;
            $contrData->pCode = $rec->pCode;
            $contrData->place = $rec->place;
            $contrData->pAddress = $rec->address;
            $contrData->pEmail = $rec->email;
        }
        
        return $contrData;
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
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Оферта нормален изглед', 'content' => 'sales/tpl/QuotationHeaderNormal.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Оферта изглед за писмо', 'content' => 'sales/tpl/QuotationHeaderLetter.shtml', 'lang' => 'bg');
    	
    	$res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
     /**
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$query = sales_QuotationsDetails::getQuery();
     	
     	// точно на тази оферта детайлите търсим
     	$query->where("#quotationId  = '{$rec->id}'");
     	
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
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $rec = static::fetchRec($rec);
    	
    	return tr("|Оферта|* №{$rec->id}");
    }
    
    
    /**
     * Създаване на продажба от оферта
     * @param stdClass $rec
     * @return mixed
     */
    private function createSale($rec)
    {
    	// Подготвяме данните на мастъра на генерираната продажба
    	$fields = array('currencyId'         => $rec->currencyId,
    					'currencyRate'       => $rec->currencyRate,
    					'paymentMethodId'    => $rec->paymentMethodId,
    					'deliveryTermId'     => $rec->deliveryTermId,
    					'chargeVat'          => $rec->chargeVat,
    					'note'				 => $rec->others,
    					'originId'			 => $rec->containerId,
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
    	
    	// Опитваме се да намерим съществуваща чернова продажба
    	if(!Request::get('dealId', 'key(mvc=sales_Sales)') && !Request::get('stop')){
    		Redirect(array('sales_Sales', 'ChooseDraft', 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId, 'ret_url' => TRUE));
    	}
    	
    	// Ако няма създаваме нова
    	if(!$sId = Request::get('dealId', 'key(mvc=sales_Sales)')){
    		
    		// Създаваме нова продажба от офертата
    		$sId = $this->createSale($rec);
    	}
    	
    	// За всеки детайл на офертата подаваме го като детайл на продажбата
    	foreach ($items as $item){
    		sales_Sales::addRow($sId, $item->classId, $item->productId, $item->packQuantity, $item->price, $item->packagingId, $item->discount);
    	}
    	
    	// Редирект към новата продажба
    	return Redirect(array('sales_Sales', 'single', $sId), tr('Успешно е създадена продажба от офертата'));
    }
    
    
    /**
     * Екшън за създаване на заявка от оферта
     */
    function act_FilterProductsForSale()
    {
    	$this->requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'active');
    	
    	// Подготовка на формата за филтриране на данните
    	$form = $this->getFilterForm($rec->id, $id);
    	
    	$fRec = $form->input();
    	if($form->isSubmitted()){
    		$sId = $this->createSale($rec);
    		
    		$products = (array)$form->rec;
    		foreach ($products as $index => $quantity){
    			list($productId, $classId, $optional, $packagingId) = explode("|", $index);
    			
    			// При опционален продукт без к-во се продължава
    			if($optional == 'yes' && empty($quantity)) continue;
    			
    			$where = "#quotationId = {$id} AND #productId = {$productId} AND #classId = {$classId} AND #packagingId = {$packagingId} AND #optional = '{$optional}' AND #quantity = {$quantity}";
    			$dRec = sales_QuotationsDetails::fetch($where);
    			if(!$dRec){
    				$dRec = sales_QuotationsDetails::fetch("#quotationId = {$id} AND #productId = {$productId} AND #classId = {$classId} AND #packagingId = {$packagingId} AND #optional = '{$optional}'");
    			}
    			
    			$dRec->packQuantity = $quantity / $dRec->quantityInPack;
    			sales_Sales::addRow($sId, $dRec->classId, $dRec->productId, $dRec->packQuantity, $dRec->price, $dRec->packagingId, $dRec->discount);
    		}
    		 
    		return Redirect(array('sales_Sales', 'single', $sId));
    	}
    
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
    	$form->title = 'Създаване на продажба от оферта';
    	$form->info = tr('Моля уточнете точните количества');
    	$filteredProducts = $this->filterProducts($id);
    	
    	foreach ($filteredProducts as $index => $product){
    		
    		if($product->optional == 'yes') {
    			$product->title = "|Опционални|*->|*{$product->title}";
    			$product->options = array('' => '') + $product->options;
    			$mandatory = '';
    		} else {
    			$product->title = "|Оферирани|*->|*{$product->title}";
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
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title = Прекратяване на действията');
    	 
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
    		$index = "{$rec->productId}|{$rec->classId}|{$rec->optional}|$rec->packagingId";
    		if(!array_key_exists($index, $products)){
    			$title = cls::get($rec->classId)->getTitleById($rec->productId);
    			if($rec->packagingId){
    				$title .= " / " . cat_Packagings::getTitleById($rec->packagingId);
    			}
    			$products[$index] = (object)array('title' => $title, 'options' => array(), 'optional' => $rec->optional, 'suggestions' => FALSE);
    		}
    		
    		if($rec->optional == 'yes'){
    			$products[$index]->suggestions = TRUE;
    		}
    		
    		if($rec->quantity){
    			$products[$index]->options[$rec->quantity] = $rec->quantity / $rec->quantityInPack;
    		}
    	}
    	 
    	return $products;
    }
}
