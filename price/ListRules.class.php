<?php


/**
 * Правилата за ценоразписите за продуктите от каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Правилата за ценоразписите за продуктите от каталога
 */
class price_ListRules extends core_Detail
{
	
	
    /**
     * ид на политика "Себестойност"
     */
    const PRICE_LIST_COST = 1;

    
    /**
     * ид на политика "Каталог"
     */
    const PRICE_LIST_CATALOG = 2;

    
    /**
     * Заглавие
     */
    var $title = 'Ценоразписи->Правила';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Правило';
    
    
    /**
     * Брой елементи на страница
     */
    var $listItemsPerPage = 40;
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper, plg_Search, plg_LastUsedKeys, plg_SaveAndNew';
                    
 
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, domain=Обхват, rule=Правило, validFrom, validUntil, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo,price';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'ceo,price';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,price';
    
    
    /**
     * Поле - ключ към мастера
     */
    var $masterKey = 'listId';

    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'productId, groupId, price';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис,input=hidden,silent');
        $this->FLD('type', 'enum(value,discount,groupDiscount)', 'caption=Тип,input=hidden,silent');
        
        // Цена за продукт 
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory,silent,remember=info');
        $this->FLD('price', 'double(Min=0)', 'caption=Цена,mandatory');
        $this->FLD('currency', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'notNull,caption=Валута,noChange');
        $this->FLD('vat', 'enum(yes=Включено,no=Без ДДС)', 'caption=ДДС,noChange'); 
        
        // Марж за група
        $this->FLD('groupId', 'key(mvc=price_Groups,select=title,allowEmpty)', 'caption=Група,mandatory,remember=info');
        $this->FLD('calculation', 'enum(forward,reverse)', 'caption=Изчисляване,remember');
        $this->FLD('discount', 'percent(decimals=2)', 'caption=Марж,placeholder=%');

        $this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00)', 'caption=В сила->От,remember');
        $this->FLD('validUntil', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00)', 'caption=В сила->До,remember');
    }
    
    
    /**
     * След генериране на ключовите думи
     */
    function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
     	if($rec->productId){
     		$code = cat_Products::getVerbal($rec->productId, 'code');
     		$res .= " " . plg_Search::normalizeText($code);
     	}
    }
     
     
    /**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('from', 'date', 'input,caption=В сила');
		$data->listFilter->setField('id', 'input=none');
		$data->listFilter->setField('type', 'input=none');
        $data->listFilter->showFields = 'search, from';
		
		$data->listFilter->input();
		
		$data->query->orderBy('#validFrom,#id', 'DESC');
		if($from = $data->listFilter->rec->from){
			$data->query->where(array("#validFrom >= '[#1#]'", $from));
		}
		
    	if($search = $data->listFilter->rec->search){
			plg_Search::applySearch($search, $data->query);
		}
	}
	
	
    /**
     * Връща цената за посочения продукт
     */
    static function getPrice($listId, $productId, $packagingId = NULL, $datetime = NULL)
    {  
        // Проверка, дали цената я няма в кеша
    	$price = price_History::getPrice($listId, $datetime, $productId);
    	
        if(isset($price)) return $price;

        price_ListToCustomers::canonizeTime($datetime);

        $datetime = price_History::canonizeTime($datetime);
        
        // В коя ценова група се е намирал продукта към посочената дата?
        $productGroup = price_GroupOfProducts::getGroup($productId, $datetime);

        if(!$productGroup) return;
 
        $query = self::getQuery();
        
        // Общи ограничения
        $query->where("#listId = {$listId} AND #validFrom <= '{$datetime}' AND (#validUntil IS NULL OR #validUntil > '{$datetime}')");

        // Конкретни ограничения
        $query->where("(#productId = {$productId}) OR (#groupId = {$productGroup})");
        
        // Вземаме последното правило
        $query->orderBy("#validFrom,#id", "DESC");
        $query->limit(1);

        $rec = $query->fetch();
 
        if($rec) {
            if($rec->type == 'value') {
                
                $price = $rec->price;

                $listRec = price_Lists::fetch($listId);
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
                    $vat = cat_Products::getVat($productId, $date);
                    $price = $price / (1 + $vat);
                }

			} else {
                expect($parent = price_Lists::fetchField($listId, 'parent'));
                $price  = self::getPrice($parent, $productId, $packagingId, $datetime);
                
                if($rec->calculation == 'reverse') {
                    $price  = $price / (1 + $rec->discount);
                } else {
                    $price  = $price * (1 + $rec->discount);
                }
            }
        } else {
            if($parent = price_Lists::fetchField($listId, 'parent')) {
            	
            	if($parent == price_ListRules::PRICE_LIST_COST){
            		
            		// Ако няма запис за продукта или групата
            		// му и бащата на ценоразписа е "себестойност"
            		// връщаме NULL
                    // Дали е необходима тази защита или тя може да създаде проблеми?
            		return NULL;
            	}
            	
                $price  = self::getPrice($parent, $productId, $packagingId, $datetime);
            }
        }
        
        $listRec = price_Lists::fetch($listId);
        	
        // По дефолт правим някакво машинно закръгляне
        $price = round($price, 8);
        
        // Записваме току-що изчислената цена в историята;
        price_History::setPrice($price, $listId, $datetime, $productId);

        return $price;
    }
    
    
    /**
     * Подготвя формата за въвеждане на правила
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
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
		
        $availableProducts = price_GroupOfProducts::getAllProducts();
        if(count($availableProducts)){
        	$form->setOptions('productId', $availableProducts);
        } else {
        	$form->getFieldType('productId')->options = array('' => '');
        }
        
    	if(Request::get('productId') && $form->rec->type == 'value' && $form->cmd != 'refresh'){
			$form->setReadOnly('productId');
		}
        
        $form->FNC('targetPrice', 'double', 'caption=Желана цена,after=discount,input');

        if($type == 'groupDiscount' || $type == 'discount') {
            $calcOpt['forward'] = "[{$masterTitle}] = [{$parentTitle}] ± %";
            $calcOpt['reverse'] = "[{$parentTitle}] = [{$masterTitle}] ± %";
            $form->setOptions('calculation', $calcOpt);
        }
 	
 	$masterTitle = type_Users::escape($masterTitle);
 		
        switch($type) {
            case 'groupDiscount' :
                $form->setField('productId,price,currency,vat,targetPrice', 'input=none');
                $title = "Правило за групов марж в ценова политика|* \"$masterTitle\"";
                break;
            case 'discount' :
                $form->setField('groupId,price,currency,vat', 'input=none');
                $title = "Правило за марж в ценова политика|* \"$masterTitle\"";
                
                $form->getField('targetPrice')->unit = "|*" . $masterRec->currency . ", ";
                $form->getField('targetPrice')->unit .= ($masterRec->vat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
                
                break;
            case 'value' :
                $form->setField('groupId,discount,calculation,targetPrice', 'input=none');
                $title = "Продуктова цена в ценова политика|* \"$masterTitle\"";
                if(!$rec->id){
                    $form->setDefault('currency', $masterRec->currency);
                    $form->setDefault('vat', $masterRec->vat);
                }

                break;
        }

        $form->title = $title;

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
            $rec->validUntil = Mode::get('PRICE_VALID_UNTIL');
        }
    }

    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if($rec->type == 'groupDiscount'){
    		$msg = 'Правилото ще анулира всички индивидуални правила за артикулите, включени в групата!';
    		$data->form->toolbar->setWarning('save', $msg);
    		$data->form->toolbar->setWarning('saveAndNew', $msg);
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()) {
            $now = dt::verbal2mysql();
            

            if(!$rec->validFrom) {
                $rec->validFrom = $now;
                Mode::setPermanent('PRICE_VALID_FROM', NULL);
            }

            if($rec->validFrom < $now) {
                $form->setError('validFrom', 'Не могат да се задават правила за минал момент');
            }
            
            // Проверка за грешки и изчисляване на отстъпката, ако е зададена само желаната цена
            if($rec->type == 'discount' || $rec->type == 'groupDiscount') {
                if(!$rec->discount && !$rec->targetPrice) {
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
                            $rec->discount = $parentPrice/$rec->targetPrice - 1;
                        } else {
                            $rec->discount = $rec->targetPrice/$parentPrice - 1;
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
     *
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('*'); 
        $data->toolbar->addBtn('Стойност', array($mvc, 'add', 'type' => 'value', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE), NULL, 'title=Продуктова цена');
        if($data->masterData->rec->parent) {
            $data->toolbar->addBtn('Марж', array($mvc, 'add', 'type' => 'discount', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE), NULL, 'title=Задаване на правило с %');
            $data->toolbar->addBtn('Групов марж', array($mvc, 'add', 'type' => 'groupDiscount', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE), NULL, 'title=Задаване на групово правило с %');
        }
    }


    /**
     * Премахва кеша за интервалите от време
     */
    public static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
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
        if($rec->validFrom && ($action == 'edit' || $action == 'delete')) {
            if($rec->validFrom <= dt::verbal2mysql()) {
                $requiredRoles = 'no_one';
            }
        }
        
        if(($action == 'add' || $action == 'delete') && isset($rec->productId)){
        	if(cat_Products::fetchField($rec->productId, 'state') != 'active'){
        		$requiredRoles = 'no_one';
        	} elseif(!cat_Products::haveRightFor('single', $rec->productId)){
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {   
        $now = dt::verbal2mysql();

        if($rec->validFrom > $now) {
            $state = 'draft';
        } else {

            $query = $mvc->getQuery();
            $query->orderBy('#validFrom,#id', 'DESC');
            $query->limit(1);
            
            $query->where("#listId = {$rec->listId}");

            $query->where("#validFrom <= '{$now}' AND (#validUntil IS NULL OR #validUntil > '{$now}')");

            if($rec->groupId) {
                $query->where("#groupId = $rec->groupId");
            } else {
                $productGroup = price_GroupOfProducts::getGroup($rec->productId, $now);
                if($productGroup) {
                    $pgCond = "#groupId = $productGroup OR ";
                }

                expect($rec->productId);

                $query->where("{$pgCond}(#productId = $rec->productId)");
            }

            expect($actRec = $query->fetch());
 
            if($actRec->id == $rec->id) {
                $state = 'active';
            } else {
                $state = 'closed';
            }
        }

        // Ако цената има повече от 2 дробни цифри, показва се до 5-я знак, иначе до втория
        if(strlen(substr(strrchr($rec->price, "."), 1) > 2)){
        	$mvc->getFieldType('price')->params['decimals'] = 5;
        } else {
        	$mvc->getFieldType('price')->params['decimals'] = 2;
        }
        
        $price = $mvc->getFieldType('price')->toVerbal($rec->price);
        
        // Област
        if($rec->productId) {
        	$row->domain = cat_Products::getHyperlink($rec->productId, TRUE);
        	
        	if(cat_Products::fetchField($rec->productId, 'state') == 'rejected'){
        		$row->domain = "<span class= 'state-rejected-link'>{$row->domain}</span>";
        	}
        } elseif($rec->groupId) {
            $row->domain = tr('група') . " <b>\"" . $mvc->getVerbal($rec, 'groupId') . "\"</b>";
            $row->domain = ht::createLink($row->domain, array('price_Groups', 'single', $rec->groupId));
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
                    $row->rule = tr("|*[|{$parentTitle}|*] = [|{$masterTitle}|*] " . $signDiscount);
                } else {
                    $row->rule = tr("|*[|{$masterTitle}|*] = [|{$parentTitle}|*] " . $signDiscount);
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

                $row->rule = tr("|*[|{$masterTitle}|*] = {$price} {$currency} |{$vat}");
                break;
        }        
        
        if($state == 'active') {
            $row->rule = "<b>{$row->rule}</b>";
        }

        // Линк към продукта
        if($rec->productId) {
            $row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
        }

        $row->ROW_ATTR['class'] .= " state-{$state}";
    }

    
   /**
    * Задаваме надценки/отстъпки за началните категории
    */
    function loadSetupData()
    {
        $csvFile = __DIR__ . "/setup/csv/Groups.csv";
        $inserted = 0;
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 2000, ",")) !== FALSE) {
                    if($groupId = price_Groups::fetchField("#title = '{$csvRow[0]}'", 'id')){
                            
                            if(!$gRec = static::fetch("#discount = '$csvRow[2]' AND #listId = " . price_ListRules::PRICE_LIST_CATALOG . " AND #groupId = {$groupId}")){
                                 $rec = new stdClass();
                                 $rec->listId = price_ListRules::PRICE_LIST_CATALOG;
                                 $rec->groupId = $groupId;
                                 $rec->discount = $csvRow[2]; // Задаваме груповата наддценка в проценти
                                 $rec->type = 'groupDiscount';
                                 $rec->validFrom = dt::now();
                                 $rec->createdBy = -1;
                         
                                 static::save($rec);
                                 $inserted++;
                            }
                    }
            }
            if($inserted) {
                $res .= "<li class='debug-info'>Записани {$inserted} нови групови наддценки/отстъпки</li>";
            } else {
                $res .= "<li>Не са добавени нови групови наддценки/отстъпки</li>";
            }
        } else {
            $res = "<li class='debug-error'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
    
    
    /**
     * Подготовка на историята на себестойностите на даден продукт
     */
	public function preparePriceList($data)
	{
		$pRec = $data->masterData->rec;
		$listId = static::PRICE_LIST_COST;
		$data->priceLists = new stdClass();
		
		// Може да се добавя нова себестойност, ако продукта е в група и може да се променя
		if(price_GroupOfProducts::getGroup($pRec->id, dt::now())){
			if($this->haveRightFor('add', (object)array('productId' => $pRec->id))){
				$data->priceLists->addUrl = array('price_ListRules', 'add', 'type' => 'value', 
												  'listId' => $listId, 'productId' => $pRec->id, 'ret_url' => array('cat_Products', 'single', $pRec->id));
			}
		}
		
		$query = static::getQuery();
		$query->where("#listId = {$listId}");
		$query->where("#productId = {$pRec->id}");
		$query->orderBy("validFrom", "DESC");
		while($rec = $query->fetch()){
			$row = price_ListRules::recToVerbal($rec);
			$data->priceLists->rows[$rec->id] = $row;
		}
	}
	
	
	/**
	 *  Рендира на историята на себестойностите на даден продукт
	 */
	public function renderPriceList($data)
	{
		$wrapTpl = getTplFromFile('cat/tpl/ProductDetail.shtml');
		$table = cls::get('core_TableView', array('mvc' => $this));
		$tpl = $table->get($data->priceLists->rows, "rule=Правило,validFrom=От,validUntil=До");
		
		$title = 'Себестойности';
		if($data->priceLists->addUrl){
			$title .= ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->priceLists->addUrl, FALSE, 'title=Добавяне на нова себестойност');
		}
		
        $wrapTpl->append($title, 'TITLE');
        $wrapTpl->append($tpl, 'CONTENT');
		
		return $wrapTpl;
	}
}
