<?php


/**
 * Правилата за ценоразписите за продуктите от каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Правилата за ценоразписите за продуктите от каталога
 */
class price_ListRules extends core_Detail
{
	
	
    /**
     * Ид на политика "Себестойност"
     */
    const PRICE_LIST_COST = 1;

    
    /**
     * Ид на политика "Каталог"
     */
    const PRICE_LIST_CATALOG = 2;

    
    /**
     * Заглавие
     */
    public $title = 'Ценоразписи->Правила';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Правило';
    
    
    /**
     * Брой елементи на страница
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, price_Wrapper, plg_SaveAndNew, plg_PrevAndNext';
                    
 
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'domain=Обхват, rule=Правило, validFrom, validUntil, createdOn, createdBy, priority';
   
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'ceo,sales,price';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales,price';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Поле - ключ към мастера
     */
    public $masterKey = 'listId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис,input=hidden,silent');
        $this->FLD('type', 'enum(value,discount,groupDiscount)', 'caption=Тип,input=hidden,silent');
        
        // Цена за продукт 
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory,silent,remember=info');
        $this->FLD('price', 'double(Min=0)', 'caption=Цена,mandatory,silent');
        $this->FLD('currency', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'notNull,caption=Валута');
        $this->FLD('vat', 'enum(yes=Включено,no=Без ДДС)', 'caption=ДДС'); 
        
        // Марж за група
        $this->FLD('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група,mandatory,remember=info');
        $this->FLD('calculation', 'enum(forward,reverse)', 'caption=Изчисляване,remember');
        $this->FLD('discount', 'percent(decimals=2,min=-1)', 'caption=Марж,placeholder=%');
        $this->FLD('priority', 'enum(1,2,3)', 'caption=Приоритет,input=hidden,silent');
        
        $this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime)', 'caption=В сила->От,remember');
        $this->FLD('validUntil', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime,defaultTime=23:59:59)', 'caption=В сила->До,remember');
    
        $this->setDbIndex('priority');
        $this->setDbIndex('validFrom');
        $this->setDbIndex('productId');
        $this->setDbIndex('groupId');
    }
     
    
    /**
     * Метод за добавяне на продуктово правило
     * 
     * @param int $listId          - към кой ценоразпис
     * @param string $productCode  - код на артикул
     * @param double $price        - цена във валута
     * @param string $currencyCode - код на валута, ако няма от бащата
     * @param string $vat          - с или без ДДС в цената
     * @param string $validFrom    - дата на валидност
     * @param string $validUntill  - крайна дата на валидност
     */
    public static function addProductRule($listId, $productCode, $price, $currencyCode = NULL, $vat = NULL, $validFrom = NULL, $validUntill = NULL)
    {
    	return self::addRuleToList($listId, 'value', $productCode, NULL, $price, NULL, $currencyCode, $vat, NULL, $validFrom, $validUntill, 1);
    }
    
    
    /**
     * Метод за добавяне на продуктов марж
     *
     * @param int $listId                  - към кой ценоразпис
     * @param string $productCode          - код на артикул
     * @param double $discount             - марж
     * @param string $calculation - Изчисляване спрямо бащата
     * @param string $validFrom            - дата на валидност
     * @param string $validUntill          - крайна дата на валидност
     */
    public static function addProductDiscountRule($listId, $productCode, $discount, $calculation = 'forward', $validFrom = NULL, $validUntill = NULL)
    {
    	return self::addRuleToList($listId, 'discount', $productCode, NULL, NULL, $discount, NULL, NULL, $calculation, $validFrom, $validUntill, 1);
    }
    
    
    /**
     * Метод за добавяне на групов марж
     *
     * @param int $listId                  - към кой ценоразпис
     * @param string $groupName            - име на група
     * @param double $discount             - марж
     * @param forward|reverse $calculation - Изчисляване спрямо бащата
     * @param string $validFrom            - дата на валидност
     * @param string $validUntill          - крайна дата на валидност
     * @param int $priority                - приоритет
     */
    public static function addGroupRule($listId, $groupName, $discount, $calculation = 'forward', $validFrom = NULL, $validUntill = NULL, $priority = 3)
    {
    	return self::addRuleToList($listId, 'groupDiscount', NULL, $groupName, NULL, $discount, NULL, NULL, $calculation, $validFrom, $validUntill, $priority);
    }
    
    
    /**
     * Метод за добавяне на ценово правило
     */
    private static function addRuleToList($listId, $type, $productCode = NULL, $groupName = NULL, $price = NULL, $discount = NULL, $currencyCode = NULL, $vatPercent = NULL, $calculation = NULL, $validFrom = NULL, $validUntill = NULL, $priority = NULL)
    {
    	expect($listRec = price_Lists::fetch($listId));
    	expect(in_array($type, array('value', 'discount', 'groupDiscount')));
    	
    	if(!isset($validFrom)){
    		$validFrom = dt::now();
    	} else {
    		expect($validFrom = dt::verbal2mysql($validFrom));
    	}
    	
    	$rec = (object)array('listId' => $listId, 'type' => $type, 'validFrom' => $validFrom);
    	
    	if(isset($validUntill)){
    		expect($validUntill = dt::verbal2mysql($validUntill));
    		$rec->validUntil = $validUntill;
    	}
 
        if($type != 'groupDiscount'){
    		expect($productRec = cat_Products::getByCode($productCode));
    		$productRec = cat_Products::fetch($productRec->productId);
    		// expect($productRec->canSell == 'yes', 'Артикулът не е продаваем');
    		$rec->productId = $productRec->id;
    		$rec->priority = 1;
    	}
    	
    	if($type == 'value'){
    		if(isset($currencyCode)){
    			$currencyCode = mb_strtoupper($currencyCode);
    			expect(currency_Currencies::getIdByCode($currencyCode));
    		} else {
    			$currencyCode = $listRec->currency;
    		}
    	
    		if(isset($vatPercent)){
    			expect(is_bool($vatPercent));
    			$vat = ($vatPercent === TRUE) ? 'yes' : 'no';
    		} else {
    			$vat = $listRec->vat;
    		}
    		
    		$rec->currency = $currencyCode;
    		$rec->vat = $vat;
    		$rec->price = $price;
    	}
    	
    	if($type == 'discount'){
            if(!isset($discount)) return FALSE;

    		if(isset($calculation)){
    			expect(in_array($calculation, array('forward', 'reverse')));
    		}
    		expect(isset($listRec->parent));
    		$rec->calculation = $calculation;
    		$rec->discount = $discount;
    	}
    	
    	if($type == 'groupDiscount'){
            if(!isset($discount)) return FALSE;
     		expect($gRec = cat_Groups::fetch(cat_Groups::forceGroup($groupName)));
    		$rec->groupId = $gRec->id;
    		$rec->discount = $discount;
    		
    		if(isset($calculation)){
    			expect(in_array($calculation, array('forward', 'reverse')));
    		}
    		expect(isset($listRec->parent));
    		$rec->calculation = $calculation;
    		expect(in_array($priority, array(2, 3)));
    		$rec->priority = $priority;
    	}
    	
    	// Форсиране на правилото ако има такова за ценовата политика със същата дата на валидност
    	$where = "#listId = {$listId} AND #type = '{$type}' AND #validFrom = '{$validFrom}'";
    	if($type != 'groupDiscount'){
    		$where .= " AND #productId = {$rec->productId}";
    	} else{
    		$where .= " AND #groupId = {$rec->groupId}";
    	}
    	
    	$exRec = static::fetch($where);
    	if(!empty($exRec)){
    		$rec->id = $exRec->id;
    	}
    	
    	return static::save($rec);
    }
    
    
    /**
	 *  Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		if(Mode::is('inlineDocument')) return;
		
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('product', "key2(mvc=cat_Products,select=name,listId={$data->masterId},selectSource=price_ListRules::getProductFilterOptions)", 'input,caption=Артикул,silent');
        $data->listFilter->FNC('threadId', 'int', 'input=hidden,silent');
        $data->listFilter->setDefault('threadId', $data->masterData->rec->threadId);
        $data->listFilter->showFields = 'product';
        $data->listFilter->input(NULL, 'silent');
        
		$data->listFilter->input();
		
		$data->query->orderBy('#validFrom,#id', 'DESC');
		
		if($rec = $data->listFilter->rec){
			
			if(isset($rec->product)){
				$groups = keylist::toArray(cat_Products::fetchField($rec->product, 'groups'));
				if(count($groups)){
					$data->query->in("groupId", $groups);
					$data->query->orWhere("#productId = {$rec->product}");
				} else {
					$data->query->where("#productId = {$rec->product}");
				}
			}
		}
	}
	
	
	/**
	 * Подготовка на опции за key2
	 */
	public static function getProductFilterOptions($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
	{
		if(!empty($onlyIds)) return array($onlyIds => cat_Products::getTitleById($onlyIds, FALSE));
		$options = self::getProductOptions($params['listId'], $limit);
		$options = array('' => '') + $options;
		
		return $options;
	}
	
	
    /**
     * Връща цената за посочения продукт според ценовата политика
     */
    public static function getPrice($listId, $productId, $packagingId = NULL, $datetime = NULL, &$validFrom = NULL)
    {  
        price_ListToCustomers::canonizeTime($datetime);
        $datetime = price_History::canonizeTime($datetime);
        
        $query = self::getQuery();
        $query->where("#listId = {$listId} AND #validFrom <= '{$datetime}' AND (#validUntil IS NULL OR #validUntil > '{$datetime}')");
        $query->where("#productId = {$productId}");
        
        if($listId != price_ListRules::PRICE_LIST_COST){
        	$groups = keylist::toArray(cat_Products::fetchField($productId, 'groups'));
        	if(count($groups)){
        		$query->in('groupId', $groups, FALSE, TRUE);
        	}
        }
        
        $query->orderBy("#priority", "ASC");
        $query->orderBy("#validFrom,#id", "DESC");
       
        $query->limit(1);
       
        $rec = $query->fetch();
       	$listRec = price_Lists::fetch($listId, 'parent,vat,defaultSurcharge,significantDigits,minDecimals');
        $round = TRUE;
       	
        if($rec) {
        	if($rec->type == 'value') {
        		$vat = cat_Products::getVat($productId, $datetime);
        		$price = self::normalizePrice($rec, $vat, $datetime);
        		
        		if($listRec->vat == 'yes'){
        			$round = FALSE;
        			$price = $price * (1 + $vat);
        			$price = price_Lists::roundPrice($listRec, $price);
        			$price = $price / (1 + $vat);
        		}
        		
        		$validFrom = $rec->validFrom;
        	} else{
        		$validFrom = $rec->validFrom;
        		expect($parent = $listRec->parent);
        		$price = self::getPrice($parent, $productId, $packagingId, $datetime, $validFrom);
        		
        		if(isset($price)){
        			if($rec->calculation == 'reverse') {
        				$price  = $price / (1 + $rec->discount);
        			} else {
        				$price  = $price * (1 + $rec->discount);
        			}
        		}
        	}
        	
        } else{
        	$defaultSurcharge = $listRec->defaultSurcharge;
        	
        	// Ако има дефолтна надценка и има наследена политика
        	if(isset($defaultSurcharge)){ 
        		if($parent = $listRec->parent) {
        			
        			// Ако няма запис за продукта или групата
        			// му и бащата на ценоразписа е "себестойност"
        			// връщаме NULL
        			// Дали е необходима тази защита или тя може да създаде проблеми?
        			if($parent == price_ListRules::PRICE_LIST_COST) return NULL;
        			 
        			// Питаме бащата за цената
        			$price  = self::getPrice($parent, $productId, $packagingId, $datetime, $validFrom);
        			
        			// Ако има цена добавяме и дефолтната надценка
        			if(isset($price)){
        				$price = $price * (1 + $defaultSurcharge);
        			}
        		}
        	}
        }
        
        // Ако има цена
        if(isset($price)){
        	
        	// Ако има указано закръгляне на ценоразписа, закръгляме
        	if($round === TRUE){
        		$price = price_Lists::roundPrice($listRec, $price);
        	}
        	
        	// Записваме току-що изчислената цена в историята;
        	//price_History::setPrice($price, $listId, $datetime, $productId);
        }
        
        // Връщаме намерената цена
        return $price;
    }
    
    
    /**
     * Обръща цената от записа в основна валута без ддс
     * 
     * @param stdClass $rec
     * @param double $vat
     * @param datetime $datetime
     * @return double $price
     */
    public static function normalizePrice($rec, $vat, $datetime)
    {
    	$price = $rec->price;
    	
    	$listRec = price_Lists::fetch($rec->listId, 'currency,createdOn,vat');
    	list($date, $time) = explode(' ', $datetime);
    	
    	// В каква цена е този ценоразпис?
    	$currency = $rec->currency;
    	
    	if(!$currency) {
    		$currency = $listRec->currency;
    	}
    	
    	if(!$currency) {
    		$currency = acc_Periods::getBaseCurrencyCode($listRec->createdOn);
    	}
    	
    	// Конвертираме в базова валута
    	$price = currency_CurrencyRates::convertAmount($price, $date, $currency);
    	
    	// Ако правилото е с включен ват или не е зададен, но ценовата оферта е с VAT, той трябва да се извади
    	if($rec->vat == 'yes' || (!$rec->vat && $listRec->vat == 'yes')) {
    		// TODO: Тук трябва да се извади VAT, защото се смята, че тези цени са без VAT
    		$price = $price / (1 + $vat);
    	}
    	
    	return $price;
    }
    
    
    /**
     * Подготвя формата за въвеждане на правила
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
		$rec = &$form->rec;

        $type = $rec->type;
    	
        $masterRec = price_Lists::fetch($rec->listId);
		$masterTitle = $masterRec->title;

        if($masterRec->parent) {
            $parentRec = price_Lists::fetch($masterRec->parent);
		    $parentTitle = $parentRec->title;
        }
		
        if(Request::get('productId') && $form->rec->type == 'value' && $form->cmd != 'refresh'){
        	$form->setReadOnly('productId');
        } else {
        	$availableProducts = self::getProductOptions($form->rec->listId);
        	if(isset($rec->productId) && !array_key_exists($rec->productId, $availableProducts)){
        		$availableProducts[$rec->productId] = cat_Products::getRecTitle(cat_Products::fetch($rec->productId, 'id,name,isPublic,code,createdOn'), FALSE);
        	}
        	
        	if(count($availableProducts)){
        		$form->setOptions('productId', array('' => '') + $availableProducts);
        	} else {
        		$form->setReadOnly('productId');
        	}
        }
        
        $form->FNC('targetPrice', 'double(Min=0)', 'caption=Желана цена,after=discount,input');

        if($type == 'groupDiscount' || $type == 'discount') {
            $calcOpt['forward'] = "[{$masterTitle}] = [{$parentTitle}] ± %";
            $calcOpt['reverse'] = "[{$parentTitle}] = [{$masterTitle}] ± %";
            $form->setOptions('calculation', $calcOpt);
        }
 	    
        switch($type) {
            case 'groupDiscount' :
                $form->setField('productId,price,currency,vat,targetPrice', 'input=none');
                $data->singleTitle = "правило за групов марж";
                break;
            case 'discount' :
                $form->setField('groupId,price,currency,vat', 'input=none');
                $data->singleTitle = "правило за марж";
                
                $form->getField('targetPrice')->unit = "|*" . $masterRec->currency . ", ";
                $form->getField('targetPrice')->unit .= ($masterRec->vat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
                
                break;
            case 'value' :
                $form->setField('groupId,discount,calculation,targetPrice', 'input=none');
                $data->singleTitle = "правило за продуктова цена";
                if(!$rec->id){
                    $form->setDefault('currency', $masterRec->currency);
                    $form->setDefault('vat', $masterRec->vat);
                } else {
                	$form->setReadOnly('currency');
                	$form->setReadOnly('vat');
                }

                break;
        }

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
            $rec->validUntil = Mode::get('PRICE_VALID_UNTIL');
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, &$data)
    {
    	$form = $data->form;
    	if(Request::get('productId') && $form->rec->type == 'value' && $form->cmd != 'refresh'){
    		$data->form->toolbar->removeBtn('saveAndNew');
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()) {
            $now = dt::verbal2mysql();
            
            if(!$rec->validFrom) {
                $rec->validFrom = $now;
                Mode::setPermanent('PRICE_VALID_FROM', NULL);
            }
           
            // Проверка за грешки и изчисляване на отстъпката, ако е зададена само желаната цена
            if($rec->type == 'discount' || $rec->type == 'groupDiscount') {
                if(!isset($rec->discount) && !isset($rec->targetPrice)) {
                    $form->setError('discount,targetPrice', 'Трябва да се зададе стойност или за отстъка или за желана цена');
                } elseif($rec->discount && $rec->targetPrice) {
                    $form->setError('discount,targetPrice', 'Не може да се зададе стойност едновременно за отстъка и за желана цена');
                } elseif($rec->targetPrice) {
                    $listRec = price_Lists::fetch($rec->listId);
                    expect($listRec->parent);
                    $parentPrice = self::getPrice($listRec->parent, $rec->productId,  NULL, $rec->validFrom);                

                    if(!$parentPrice) {
                        $parentRec = price_Lists::fetch($listRec->parent);
                        $parentTitle = price_Lists::getVerbal($parentRec, 'title');
                        $form->setError('targetPrice', "Липсва цена за продукта от политика|* \"{$parentTitle}\"");
                    } else {
                         
                        // Начисляваме VAT, ако политиката е с начисляване
                        if($listRec->vat == 'yes') {
                            $vat         = cat_Products::getVat($rec->productId, $rec->validFrom);
                            $parentPrice = $parentPrice * (1 + $vat);
                        }
                        // В каква валута е този ценоразпис?
                        $currency = $listRec->currency;
                        if(!$currency) {
                            $currency = acc_Periods::getBaseCurrencyCode($listRec->validFrom);
                        }
                        
                        // Конвертираме в базова валута
                        $parentPrice = currency_CurrencyRates::convertAmount($parentPrice, $listRec->validFrom, $currency);
                        $parentPrice = round($parentPrice, 10);
     
                        if($rec->calculation == 'reverse') {
                            $rec->discount = ($parentPrice / $rec->targetPrice) - 1;
                        } else {
                            $rec->discount = ($rec->targetPrice / $parentPrice) - 1;
                        }
                    }
                }
            }

            if($rec->validUntil && ($rec->validUntil <= $rec->validFrom)) {
                $form->setError('validUntil', 'Правилото трябва да е в сила до по-късен момент от началото му');
            }
            
            if($rec->validFrom && !$form->gotErrors() && $rec->validFrom > $now) {
                Mode::setPermanent('PRICE_VALID_FROM', $rec->validFrom);
            }

            if(!$form->gotErrors()) {
                Mode::setPermanent('PRICE_VALID_UNTIL', $rec->validUntil);
            }
        }
    }


    /**
     * Премахва кеша за интервалите от време
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
        price_History::removeTimeline();
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec->validFrom)) {
    		if($rec->validFrom <= dt::verbal2mysql()) {
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if(($action == 'add' || $action == 'delete') && isset($rec->productId)){
        	if(cat_Products::fetchField($rec->productId, 'state') != 'active'){
        		$requiredRoles = 'no_one';
        	} else {
        		$isPublic = cat_Products::fetchField($rec->productId, 'isPublic');
        		if($isPublic == 'no' && $rec->listId != price_ListRules::PRICE_LIST_COST){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
        
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->listId)){
        	$folderId = price_Lists::fetchField($rec->listId, 'folderId');
        	
        	if(!price_Lists::haveRightFor('edit', (object)array('id' => $rec->listId, 'folderId' => $folderId))){
        		$requiredRoles = 'no_one';
        	}
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {   
        $now = dt::verbal2mysql();

        if($rec->validFrom > $now) {
            $state = 'draft';
        } else {
			
        	$state = 'active';
        	if(isset($rec->validUntil) && $rec->validUntil < $now){
        		$state = 'closed';
        	} else {
        		if($rec->type == 'groupDiscount'){
        			if($mvc->fetchField("#listId = {$rec->listId} AND #type = 'groupDiscount' AND #groupId = {$rec->groupId} AND #validFrom > '{$rec->validFrom}' AND #validFrom <= '{$now}'")){
        				$state = 'closed';
        			}
        		} else {
        			if($mvc->fetchField("#listId = {$rec->listId} AND (#type = 'discount' OR #type = 'value') AND #productId = {$rec->productId} AND #validFrom > '{$rec->validFrom}' AND #validFrom <= '{$now}'")){
        				$state = 'closed';
        			}
        		}
        	}
        }

        // Ако цената има повече от 2 дробни цифри, показва се до 5-я знак, иначе до втория
        $strlen = strlen(substr(strrchr($rec->price, "."), 1));
        if($strlen > 2){
        	$mvc->getFieldType('price')->params['decimals'] = 5;
        } else {
        	$mvc->getFieldType('price')->params['decimals'] = 2;
        }
        
        $price = $mvc->getFieldType('price')->toVerbal($rec->price);
        
        // Област
        if($rec->productId) {
        	$row->domain = cat_Products::getShortHyperlink($rec->productId);
        	
        	if(cat_Products::fetchField($rec->productId, 'state') == 'rejected'){
        		$row->domain = "<span class= 'state-rejected-link'>{$row->domain}</span>";
        	}
        } elseif($rec->groupId) {
            $row->domain = "<b>" . $mvc->getVerbal($rec, 'groupId') . "</b>";
        }
        
		$masterRec = price_Lists::fetch($rec->listId);
		$masterTitle = price_Lists::getVerbal($masterRec, 'title');

        if($masterRec->parent) {
            $parentRec = price_Lists::fetch($masterRec->parent);
		    $parentTitle = price_Lists::getVerbal($parentRec, 'title');
        }

        switch($rec->type) {
            case 'groupDiscount' :
            case 'discount':
                $signDiscount = ($rec->discount > 0 ? "+ " : "- ");
                $rec->discount = abs($rec->discount);
                $discount = $mvc->getVerbal($rec, 'discount');
                $signDiscount = $signDiscount . $discount;
                
                if($rec->calculation == 'reverse') {
                	$row->rule = tr("|*[|{$parentTitle}|*] / (1 {$signDiscount})");
                } else {
                    $row->rule = tr("|*[|{$parentTitle}|*] " . $signDiscount);
                }
                break;

            case 'value' :
                if(!$currency = $rec->currency) {
                    $currency = price_Lists::fetchField($rec->listId, 'currency');
                }
                if(!$vat = $rec->vat) {
                    $vat = price_Lists::fetchField($rec->listId, 'vat');
                }

                $vat = ($vat == 'yes') ? "с ДДС" : "без ДДС";

                $row->rule = tr("|*{$price} {$currency} |{$vat}|*");
                break;
        }        
        
        // Линк към продукта
        if(isset($rec->productId)) {
            $row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
        }

        if($rec->productId) {
        	$isPublic = cat_Products::fetchField($rec->productId, 'isPublic');
        	if($isPublic == 'no'){
        		$row->domain = ht::createHint($row->domain, 'Артикулът е нестандартен и цената му вече не се определя от ценовата политика', 'warning', FALSE);
        		$state = 'closed';
        	}
        }
        
        $row->ROW_ATTR['class'] .= " state-{$state}";
        
        if($state == 'active') {
        	$row->rule = "<b>{$row->rule}</b>";
        }
    }
	
	
	/**
	 * Създава запис на себестойност на артикул
	 * 
	 * @param int    $productId    - ид на продукт
	 * @param double $primeCost    - себестойност
	 * @param date   $validFrom    - от кога е валидна
	 * @param string $currencyCode - код на валута
	 * @param yes|no $vat          - с ДДС или без
	 * @return int				   - ид на създадения запис
	 */
	public static function savePrimeCost($productId, $primeCost, $validFrom, $currencyCode = NULL, $vat = 'no')
	{
        // По подразбиране задаваме в текуща валута
        if(empty($currencyCode)) {
            $currencyCode = acc_Periods::getBaseCurrencyCode();
        }
        
        // Във всяка API функция проверките за входните параметри са задължителни
        expect(!empty($productId) && !empty($validFrom) && !empty($primeCost), $productId, $primeCost, $validFrom, $currencyCode, $vat);
 
		$obj = (object)array('productId' => $productId,
				             'type'      => 'value',
				             'validFrom' => $validFrom,
							 'listId'    => price_ListRules::PRICE_LIST_COST,
							 'price'     => $primeCost,
							 'vat'       => $vat,
				             'priority'  => 1,
							 'createdBy' => -1,
							 'priority'  => 1,
							 'currency'  => $currencyCode);
		
		return self::save($obj);
	}
	
	
	/**
	 * Подготвяме  общия изглед за 'List'
	 */
	function prepareDetail_($data)
	{
		setIfNot($data->masterKey, $this->masterKey);
		setIfNot($data->masterMvc, $this->Master);
		
		// Ще разделяме записите според техните приоритети
		$masterRec = $data->masterData->rec;
		$data->recs1 = $data->recs2 = $data->recs3 = array();
		$data->rows1 = $data->rows2 = $data->rows3 = array();
		
		// Подготовка на заявката
		$this->prepareDetailQuery($data);
		
		// Подготовка на полетата за спицъчния изглед
		$this->prepareListFields($data);
		
		// Подготовка на филтъра
		$this->prepareListFilter($data);
		
		// За всеки приоритет
		foreach (array(1, 2, 3) as $priority){
			
			// Подготвяме пейджър само за него
			$pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
			$pager->setPageVar($this->className, $priority);
			
			// Извличаме записите само с този приоритет
			$query = clone $data->query;
			$query->where("#priority = {$priority}");
			$pager->setLimit($query);
			
			// Вербализираме ги
			while($rec = $query->fetch()){
				$data->{"recs{$priority}"}[$rec->id] = $rec;
				$data->{"rows{$priority}"}[$rec->id] = $this->recToVerbal($rec, arr::combine($data->listFields, '-list'));
				if(is_object($data->{"rows{$priority}"}[$rec->id]->_rowTools)){
					$data->{"rows{$priority}"}[$rec->id]->_rowTools = $data->{"rows{$priority}"}[$rec->id]->_rowTools->renderHtml();
				}
			}
			
			// Записваме подготвения пейджър
			$data->{"pager{$priority}"} = $pager;
		}
		
		return $data;
	}
	
	
	/**
	 * Рендираме детайла
	 * 
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public function renderDetail_($data)
	{
		$masterRec = $data->masterData->rec;
		$tpl = getTplFromFile("price/tpl/ListRules.shtml");
		$rows = &$data->rows;
		unset($data->listFields['priority']);
		$display = (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf') && !Mode::is('inlineDocument')) ? TRUE : FALSE;
		
		if($masterRec->state != 'rejected' && $display === TRUE){
			$img = ht::createElement('img', array('src'=> sbf('img/16/tools.png', "")));
			$data->listFields =  arr::combine(array('_rowTools' => '|*' . $img->getContent()), arr::make($data->listFields, TRUE));
		}
		
		$tpl->append($this->renderListFilter($data), 'ListFilter');
		
		// За всеки приоритет
		foreach (array(1 => 'Правила с висок приоритет', 2 => 'Правила със среден приоритет', 3 => 'Правила с нисък приоритет') as $priority => $title){
			$block = clone $tpl->getBlock('PRIORITY');
			$appendTable = TRUE;
			$fRows = $data->{"rows{$priority}"};
			
			$data->listFields['rule'] = 'Стойност';
			$table = cls::get('core_TableView', array('mvc' => $this));
			$toolbar = cls::get('core_Toolbar');
			
			// Добавяме бутони за добавяне към всеки приоритет
			if($priority == 1){
				$data->listFields['domain'] = 'Артикул';
				if($display === TRUE && $this->haveRightFor('add', (object)array('listId' => $masterRec->id))){
					$toolbar->addBtn('Стойност', array($this, 'add', 'type' => 'value', 'listId' => $masterRec->id, 'priority' => $priority,'ret_url' => TRUE), NULL, 'title=Задаване на цена на артикул,ef_icon=img/16/wooden-box.png');
				}
			} else {
				$data->listFields['domain'] = 'Група';
			}
			
			// Ако политиката наследява друга, може да се добавят правила за марж
			if($masterRec->parent) {
				if($priority == 1){
					if($display === TRUE && $this->haveRightFor('add', (object)array('listId' => $masterRec->id))){
						$toolbar->addBtn('Продуктов марж', array($this, 'add', 'type' => 'discount', 'listId' => $masterRec->id, 'priority' => $priority, 'ret_url' => TRUE), NULL, 'title=Задаване на правило с % за артикул,ef_icon=img/16/tag.png');
					}
				} else {
					if($display === TRUE && $this->haveRightFor('add', (object)array('listId' => $masterRec->id))){
						$toolbar->addBtn('Групов марж', array($this, 'add', 'type' => 'groupDiscount', 'listId' => $masterRec->id, 'priority' => $priority, 'ret_url' => TRUE), NULL, 'title=Задаване на групово правило с %,ef_icon=img/16/grouping.png');
					}
				}
			} else {
				if(!count($fRows) && $priority != 1){
					$appendTable = FALSE;
				}
			}
			
			// Дали да показваме таблицата
			if($appendTable === TRUE){
				$style = ($priority == 1) ? '' : "margin-top:20px;margin-bottom:2px";
				$block->append("<div style='{$style}'><b>{$title}</b></div>", 'TABLE');
				
				$fields = $data->listFields;
				if(!count($fRows)){
					unset($fields['_rowTools']);
				}
				
				$block->append($table->get($fRows, $fields), 'TABLE');
				if(isset($data->{"pager{$priority}"})){
					$block->append($data->{"pager{$priority}"}->getHtml(), 'TABLE_PAGER');
				}
			}
			
			// Рендираме тулбара
			$block->append($toolbar->renderHtml(), 'TABLE_TOOLBAR');
			$block->removeBlocks();
			
			$tpl->append($block, 'TABLES');
		}
		
		// Връщаме шаблона
		return $tpl;
	}
	
	
	/**
	 * Връща масив с възможните за избор артикули (стандартни и продаваеми)
	 * 
	 * @param int $listId
	 * @return array $options
	 */
	public static function getProductOptions($listId, $limit = NULL)
	{
		$options = array();
		$pQuery = cat_Products::getQuery();
		$pQuery->where("#state = 'active'");
		if($listId != self::PRICE_LIST_COST){
			$pQuery->where("#isPublic = 'yes' AND #canSell = 'yes'");
		}
		$pQuery->show('id,name,isPublic,code,createdOn');
		if(isset($limit)){
			$pQuery->limit($limit);
		}
		
		while($pRec = $pQuery->fetch()){
			$options[$pRec->id] = cat_Products::getRecTitle($pRec, FALSE);
		}
		
		return $options;
	}
	
	
	/**
	 * След началното установяване на този мениджър
	 */
	static function loadSetupData()
	{
		// Ако няма правила създаваме дефолтни
		if(!self::count()){
			cls::get('cat_Groups')->setupMvc();
			$path = getFullPath('price/csv/CatalogRules.csv');
			$csv = csv_Lib::getCsvRowsFromFile(file_get_contents($path), array('firstRow' => FALSE, 'delimiter' => ','));
			$csvRows = $csv['data'];
			if(is_array($csvRows)){
				foreach ($csvRows as $row){
					self::addGroupRule(self::PRICE_LIST_CATALOG, $row[1], $row[2]);
				}
			}
		}
	}
}
