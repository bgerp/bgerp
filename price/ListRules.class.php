<?php


/**
 * Правилата за ценоразписите за продуктите от каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
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
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper, plg_Search, plg_LastUsedKeys';
                    
 
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, rule=Правило, validFrom, validUntil, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
        
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'powerUser';
    
    
    /**
     * Поле - ключ към мастера
     */
    var $masterKey = 'listId';

    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'productId, price';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис,input=hidden,silent');
        $this->FLD('type', 'enum(value,discount,groupDiscount)', 'caption=Тип,input=hidden,silent');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory,silent');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name,allowEmpty)', 'caption=Опаковка');
        $this->FLD('groupId', 'key(mvc=price_Groups,select=title,allowEmpty)', 'caption=Група,mandatory');
        $this->FLD('price', 'double', 'caption=Цена,mandatory');
        $this->FLD('discount', 'percent(decimals=2)', 'caption=Отстъпка,mandatory,placeholder=%');
        $this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00)', 'caption=В сила->От');
        $this->FLD('validUntil', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00)', 'caption=В сила->До');
    }
    
    
     /**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('from', 'date', 'input,caption=В сила,width=6em,silent');
		$data->listFilter->showFields = 'search, from';
		$data->listFilter->input();
		
		$data->query->orderBy('#validFrom,#id', 'DESC');
        
    	if($productId = Request::get('product', 'int')){
			$data->query->where(array("#productId = [#1#]", $productId));
		}
		
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
        price_ListToCustomers::canonizeTime($datetime);

        $datetime = price_History::canonizeTime($datetime);

        $productGroup = price_GroupOfProducts::getGroup($productId, $datetime);
        
        if(!$productGroup) {

            return NULL;
        }

        $query = self::getQuery();
        
        // Общи ограничения
        $query->where("#listId = {$listId} AND #validFrom <= '{$datetime}' AND (#validUntil IS NULL OR #validUntil > '{$datetime}')");

        // Конкретни ограничения
        if($packagingId) {
            $query->where("(#productId = $productId AND (#packagingId = $packagingId OR #packagingId IS NULL)) OR (#groupId = $productGroup)");
        } else {
            $query->where("(#productId = $productId AND #packagingId IS NULL) OR (#groupId = $productGroup)");
        }
        
        // Вземаме последното правило
        $query->orderBy("#validFrom,#id", "DESC");
        $query->limit(1);

        $rec = $query->fetch();
 
        if($rec) {
            if($rec->type == 'value') {
                $price = $rec->price; // TODO конвертиране
                $listRec = price_Lists::fetch($listId);
                list($date, $time) = explode(' ', $datetime);
                $currency = $listRec->currency;
                if(!$currency) {
                    $currency = acc_Periods::getBaseCurrencyCode($listRec->createdOn);
                }
                $price = currency_CurrencyRates::convertAmount($price, $date, $currency);
                if($listRec->vat == 'yes') {

                }
            } else {
                expect($parent = price_Lists::fetchField($listId, 'parent'));
                $price  = self::getPrice($parent, $productId, $packagingId, $datetime); 
                $price  = $price * (1 - $rec->discount); 
            }
        } else {
            if($parent = price_Lists::fetchField($listId, 'parent')) {
            	$conf = core_Packs::getConfig('price');
            	if($parent == price_ListRules::PRICE_LIST_COST){
            		
            		// Ако няма запис за продукта или групата
            		// му и бащата на ценоразписа е "себестойност"
            		// връщаме NULL
            		return NULL;
            	}
            	
                $price  = self::getPrice($parent, $productId, $packagingId, $datetime);
            }
        }
        
        // Записваме току-що изчислената цена в историята;
        price_History::setPrice($price, $listId, $datetime, $productId, $packagingId);

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

    	if(!$rec->id){
    		$form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form); document.forms['{$form->formAttr['id']}'].elements['packagingId'].value ='';this.form.submit();"));
    	}
    	
        $masterRec = price_Lists::fetch($rec->listId);
		$masterTitle = price_Lists::getVerbal($masterRec, 'title');
		
        $availableProducts = price_GroupOfProducts::getAllProducts();
        if(count($availableProducts)){
        	$form->setOptions('productId', $availableProducts);
        } else {
        	$form->fields['productId']->type->options = array('' => '');
        }
        
    	if(Request::get('productId') && $form->cmd != 'refresh'){
			$form->setReadOnly('productId');
		}
		
        switch($type) {
            case 'groupDiscount' :
                $form->setField('productId,packagingId,price', 'input=none');
                $title = "Групова отстъпка в ценова политика|* \"$masterTitle\"";
                break;
            case 'discount' :
                $form->setField('groupId,price', 'input=none');
                $title = "Продуктова отстъпка в ценова политика|* \"$masterTitle\"";
                break;
            case 'value' :
                $form->setField('groupId,discount', 'input=none');
                $title = "Продуктова цена в ценова политика|* \"$masterTitle\"";
                break;
        }

        $form->title = $title;

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
            $rec->validUntil = Mode::get('PRICE_VALID_UNTIL');
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
    	if($rec->productId){
    		$form->setOptions('packagingId', cat_Products::getPacks($rec->productId));
        }
    	
    	if($form->isSubmitted()) {
            $now = dt::verbal2mysql();
            
            if(!$rec->validFrom) {
                $rec->validFrom = $now;
                Mode::setPermanent('PRICE_VALID_FROM', NULL);
            }

            if($rec->validFrom < $now) {
                $form->setError('validFrom', 'Не могат да се задават правила за минал момент');
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
			
            if($rec->productId){
	            if(!cat_Products::getProductInfo($rec->productId, $rec->packagingId)){
	            	$form->setError('packagingId', 'Избрания продукт не се предлага в тази опаковка');
	            }
            }
        }
    }

  
    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('*'); 
        $data->toolbar->addBtn('Стойност', array($mvc, 'add', 'type' => 'value', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE));
        if($data->masterData->rec->parent) {
            $data->toolbar->addBtn('Отстъпка', array($mvc, 'add', 'type' => 'discount', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE));
            $data->toolbar->addBtn('Групова отстъпка', array($mvc, 'add', 'type' => 'groupDiscount', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE));
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
                if($rec->productId && $rec->packagingId) {
                    $query->where("{$pgCond}(#productId = $rec->productId AND (#packagingId = $rec->packagingId OR #packagingId IS NULL))");
                } else {
                    $query->where("{$pgCond}(#productId = $rec->productId AND #packagingId IS NULL)");
                }
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
        	$mvc->fields['price']->type->params['decimals'] = 5;
        } else {
        	$mvc->fields['price']->type->params['decimals'] = 2;
        }
        
        $price = $mvc->fields['price']->type->toVerbal($rec->price);
        
        $discount = $mvc->getVerbal($rec, 'discount');
        
        if($rec->discount < 0) {$discount = $mvc->getVerbal($rec, 'discount');
            $discount = "|Надценка|* <font color='#000066'>" . (-$discount) . " %</font>";
        } else {
            $discount = "|Отстъпка|*  <font color='#660000'>{$discount}</font>";
        }
        
        if($rec->productId) {
            $product = $mvc->getVerbal($rec, 'productId');
            $product = ht::createLink($product, array('cat_Products', 'single', $rec->productId));
        }

        if($rec->packagingId) {
            $packaging = mb_strtolower($mvc->getVerbal($rec, 'packagingId'));
            $product = "{$packaging} $product";
        }
        
        if($rec->groupId) {
            $group = 'група ' . $mvc->getVerbal($rec, 'groupId');
            $group = ht::createLink($group, array('price_Groups', 'single', $rec->groupId));
        }

        $currency = price_Lists::fetchField($rec->listId, 'currency');

        switch($rec->type) {
            case 'groupDiscount' :
                $row->rule = tr("{$discount} |за|* {$group}");
                break;
            case 'discount' :
                $row->rule = tr("{$discount} |за|* {$product}");
                break;
            case 'value' :
                $row->rule = tr("|Цена|* {$price} {$currency} |за|* {$product}");
                break;
        }        
        
        if($state == 'active') {
            $row->rule = "<b>{$row->rule}</b>";
        }

        // Линк към продукта
        if($rec->productId) {
            $row->productId = ht::createLink($row->productId, array('cat_Products', 'Single', $rec->productId));
        }

        $row->ROW_ATTR['class'] .= " state-{$state}";
    }

    
   /**
    * Задаваме надценки/отстъпки за началните категории
    */
    static function setup()
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
            $res .= "<li style='color:green;'>Записани {$inserted} нови групови наддценки/отстъпки</li>";
        } else {
                $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
    
    
    /**
     * Подготовка на историята на себестойностите на даден продукт
     */
	public function preparePriceList($data)
	{
		$data->TabCaption = 'Себестойност';
		$pRec = $data->masterData->rec;
		$listId = static::PRICE_LIST_COST;
		$data->priceLists = new stdClass();
		
		// Може да се добавя нова себестойност, ако продукта е в група и може да се променя
		if(price_GroupOfProducts::getGroup($pRec->id, dt::now())){
			if(cat_Products::haveRightFor('edit', $pRec)){
				$data->priceLists->addUrl = array('price_ListRules', 'add', 'type' => 'value', 
												  'listId' => $listId, 'productId' => $pRec->id, 'ret_url' => TRUE);
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
		$tpl = $table->get($data->priceLists->rows, "rule=Правило,validFrom=В сила->От,validUntil=В сила->До");
		
		$title = 'Себестойности';
		if($data->priceLists->addUrl){
			$title .= ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->priceLists->addUrl);
		}
		
        $wrapTpl->append($title, 'TITLE');
        $wrapTpl->append($tpl, 'CONTENT');
		
		return $wrapTpl;
	}
}
