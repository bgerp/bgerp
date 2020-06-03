<?php


/**
 * Мениджър за детайл на артикулите в е-магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_ProductDetails extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'eshopProductId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'eshop_Wrapper, plg_Created, plg_Modified, plg_SaveAndNew, plg_RowTools2, plg_Select, plg_AlignDecimals2';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'опция';
    
    
    /**
     * Заглавие
     */
    public $title = 'Опции на артикулите в е-магазина';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'eshopProductId=Е-артикул,productId,title,packagings=Опаковки/Мерки,deliveryTime,state=Състояние,modifiedOn,modifiedBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'title';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'eshop,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'eshop,ceo';
    
    
    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'eshop,ceo';
    
    
    /**
     * Поле за артикула
     */
    public $productFld = 'productId';
    
    
    /**
     * Поле за забележки
     */
    public $notesFld = 'title';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Е-артикул,mandatory,silent');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=price_ListRules::getSellableProducts,titleFld=name)', 'caption=Артикул,silent,removeAndRefreshForm=packagings,mandatory');
        $this->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковки/Мерки,mandatory');
        $this->FLD('title', 'varchar(nullIfEmpty)', 'caption=Заглавие');
        $this->FLD('deliveryTime', 'time', 'caption=Доставка до');
        $this->FLD('state', 'enum(active=Активен,closed=Затворен,rejected=Оттеглен)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('eshopProductId,title');
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $rec->state = cat_Products::fetchField($rec->productId, 'state');
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
    	if (isset($data->masterMvc)){
    		unset($data->listFields['eshopProductId']);
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        if (isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'canStore,measureId');
            if ($productRec->canStore == 'yes') {
                $packs = cat_Products::getPacks($rec->productId);
                $form->setSuggestions('packagings', $packs);
                $form->setDefault('packagings', keylist::addKey('', key($packs)));
            } else {
                $form->setDefault('packagings', keylist::addKey('', $productRec->measureId));
                $form->setReadOnly('packagings');
            }
        } else {
            $form->setField('packagings', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            $thisDomainId = eshop_Products::getDomainId($rec->eshopProductId);
            if (self::isTheProductAlreadyInTheSameDomain($rec->productId, $thisDomainId, $rec->id)) {
                $form->setError('productId', 'Артикулът е вече добавен в същия домейн');
            }
        }
    }
    
    
    /**
     * Артикулът наличен ли е в подадения домейн
     *
     * @param int      $productId - артикул
     * @param int      $domainId  - домейн
     * @param int|NULL $id        - запис който да се игнорира
     *
     * @return bool - среща ли се артикулът в същия домейн?
     */
    public static function isTheProductAlreadyInTheSameDomain($productId, $domainId, $id = null)
    {
        $domainIds = array();
        $query = self::getQuery();
        $query->where("#productId = {$productId} AND #id != '{$id}'");
        while ($eRec = $query->fetch()) {
            $eproductDomainId = eshop_Products::getDomainId($eRec->eshopProductId);
            $domainIds[$eproductDomainId] = $eproductDomainId;
        }
        
        return array_key_exists($domainId, $domainIds);
    }
    
    
    /**
     * Каква е цената във външната част
     *
     * @param int      $productId
     * @param int      $packagingId
     * @param float    $quantityInPack
     * @param int|NULL $domainId
     *
     * @return NULL|float
     */
    public static function getPublicDisplayPrice($productId, $packagingId = null, $quantityInPack = 1, $domainId = null)
    {
        $res = (object) array('price' => null, 'discount' => null);
        $domainId = (isset($domainId)) ? $domainId : cms_Domains::getPublicDomain()->id;
        $settings = cms_Domains::getSettings($domainId);
        
        // Ценовата политика е от активната папка
        $listId = $settings->listId;
        if ($lastActiveFolder = core_Mode::get('lastActiveContragentFolder')) {
            $Cover = doc_Folders::getCover($lastActiveFolder);
            $listId = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that);
        }
        
        // Ако има ценоразпис
        if (isset($listId)) {
            $price = price_ListRules::getPrice($listId, $productId, $packagingId);
            
            if (isset($price)) {
                $priceObject = cls::get('price_ListToCustomers')->getPriceByList($listId, $productId, $packagingId, $quantityInPack);
                
                $price *= $quantityInPack;
                if ($settings->chargeVat == 'yes') {
                    $price *= 1 + cat_Products::getVat($productId);
                }
                $price = currency_CurrencyRates::convertAmount($price, null, null, $settings->currencyId);
                
                $res->price = round($price, 5);
                if (!empty($priceObject->discount)) {
                    $res->discount = $priceObject->discount;
                }
                
                return $res;
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
        	$row->ROW_ATTR['class'] = "state-{$rec->state}";
        	$row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, TRUE);
        	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
        	
            if (!self::getPublicDisplayPrice($rec->productId)) {
                $row->productId = ht::createHint($row->productId, 'Артикулът няма цена', 'warning');
            }
        }
    }
    
    
    /**
     * Подготовка на опциите във външната част
     *
     * @param stdClass $data
     *
     * @return void
     */
    public static function prepareExternal(&$data)
    {
        $data->rows = $data->recs = array();
        
        // Добавяне към колонките по една за всеки параметър
        $displayParams = eshop_Products::getParamsToDisplay($data->rec->id);
        
        $data->listFields = arr::make('code=Код,productId=Артикул,params=Параметри,packagingId=Опаковка,quantity=Количество,catalogPrice=Цена');
        $fields = cls::get(get_called_class())->selectFields();
        $fields['-external'] = $fields;
        
        $query = self::getQuery();
        $query->where("#eshopProductId = {$data->rec->id} AND #state = 'active'");
        $query->orderBy('productId');
        $data->optionsProductsCount = $query->count();
        $data->commonParams = eshop_Products::getCommonParams($data->rec->id);
        
        while ($rec = $query->fetch()) {
            $newRec = (object) array('eshopProductId' => $rec->eshopProductId, 'productId' => $rec->productId, 'title' => $rec->title, 'deliveryTime' => $rec->deliveryTime);
            $packagins = keylist::toArray($rec->packagings);
            
            // Кои параметри ще се показват
            $params = cat_Products::getParams($rec->productId, null, true);
            $intersect = array_intersect_key($params, $displayParams);
            
            // Всяка от посочените опаковки се разбива във отделни редове
            $i = 1;
            foreach ($packagins as $packagingId) {
                $clone = clone $newRec;
                $clone->first = ($i == 1) ? true : false;
                $clone->packagingId = $packagingId;
                $packRec = cat_products_Packagings::getPack($rec->productId, $packagingId);
                $clone->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
                
                $row = self::getExternalRow($clone);
                if(countR($intersect)){
                    foreach ($intersect as $paramId => $pVal) {
                        $paramName = cat_Params::getVerbal($paramId, 'typeExt');
                        $row->params .= "<div class='eshop-product-list-param'>{$paramName}: {$pVal}</div>";
                    }
                }
                
                $data->recs[] = $clone;
                $data->rows[] = $row;
                $i++;
            }
        }
        
        if (countR($data->rows)) {
            uasort($data->rows, function ($obj1, $obj2) {
                if ($obj1->orderCode == $obj2->orderCode) {
                    
                    return $obj1->orderPrice > $obj2->orderPrice;
                }
                
                return strnatcmp($obj1->orderCode, $obj2->orderCode);
            });
            
            $prev = null;
            foreach ($data->rows as &$row1) {
                if (isset($prev) && $prev == $row1->orderCode) {
                    unset($row1->productId);
                    unset($row1->code);
                    unset($row1->params);
                    $row1->ROW_ATTR['class'] = "no-product-rows";
                } else {
                    if(!empty($row1->saleInfo)){
                        $row1->productId .= "<br> " . $row1->saleInfo;
                    }
                }
                $prev = strip_tags($row1->orderCode);
            }
        }
    }
    
    
    /**
     * Външното представяне на артикула
     *
     * @param stdClass $rec
     *
     * @return stdClass $row
     */
    public static function getExternalRow($rec)
    {
        $settings = cms_Domains::getSettings();
        $row = new stdClass();
        $row->productId = (empty($rec->title)) ? cat_Products::getVerbal($rec->productId, 'name') : core_Type::getByName('varchar')->toVerbal($rec->title);
        $fullCode = cat_products::getVerbal($rec->productId, 'code');
        $row->code = substr($fullCode, 0, 10);
        $row->code = "<span title={$fullCode}>{$row->code}</span>";
        
        $row->packagingId = tr(cat_UoM::getShortName($rec->packagingId));
        $minus = ht::createElement('span', array('class' => 'btnDown', 'title' => 'Намаляване на количеството'), '-');
        $plus = ht::createElement('span', array('class' => 'btnUp', 'title' => 'Увеличаване на количеството'), '+');
        $row->quantity = '<span>' . $minus . ht::createTextInput("product{$rec->productId}-{$rec->packagingId}", 1, "class=eshop-product-option option-quantity-input") . $plus . '</span>';

        $showCartBtn = true;
        $catalogPriceInfo = self::getPublicDisplayPrice($rec->productId, $rec->packagingId, $rec->quantityInPack);
        if(isset($catalogPriceInfo->price)){
            $row->catalogPrice = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($catalogPriceInfo->price);
            if($catalogPriceInfo->price == 0){
                $row->catalogPrice = "<span class='green'>" . tr('Безплатно') . "</span>";
            }
            
            $row->catalogPrice = currency_Currencies::decorate($row->catalogPrice, $settings->currencyId);
            $row->catalogPrice = "<b>{$row->catalogPrice}</b>";
        } else {
            $showCartBtn = false;
            $row->catalogPrice = "<span class=' option-not-in-stock ' style='background-color: #e6e6e6 !important;border: solid 1px #ff7070;color: #c00;margin-top: 5px;'>" . tr('Свържете се с нас') . "</span><br>";
            if(eshop_Products::haveRightFor('single')){
                $row->catalogPrice = ht::createHint($row->catalogPrice, 'Артикулът няма цена за продажба', 'error', false);
            }
        }
        
        $row->orderPrice = $catalogPriceInfo->price;
        $row->orderCode = $fullCode;
        $addUrl = toUrl(array('eshop_Carts', 'addtocart'), 'local');
        $class = ($rec->_listView === true) ? 'group-row' : '';
        
        if($showCartBtn === true){
            if (!empty($catalogPriceInfo->discount)) {
                $style = ($rec->_listView === true) ? 'style="display:inline-block;font-weight:normal"' : '';
                
                $row->catalogPrice = "<b class='{$class} eshop-discounted-price'>{$row->catalogPrice}</b>";
                $discountType = type_Set::toArray($settings->discountType);
                $row->catalogPrice .= "<div class='{$class} external-discount' {$style}>";
                if (isset($discountType['amount'])) {
                    $amountWithoutDiscount = $catalogPriceInfo->price / (1 - $catalogPriceInfo->discount);
                    $discountAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount);
                    $row->catalogPrice .= "<div class='{$class} external-discount-amount' {$style}> {$discountAmount}</div>";
                }
                
                if (isset($discountType['amount']) && isset($discountType['percent'])) {
                    $row->catalogPrice .= ' / ';
                }
                
                if (isset($discountType['percent'])) {
                    $discountPercent = core_Type::getByName('percent(decimals=0)')->toVerbal($catalogPriceInfo->discount);
                    $discountPercent = str_replace('&nbsp;', '', $discountPercent);
                    $row->catalogPrice .= "<div class='{$class} external-discount-percent' {$style}> (-{$discountPercent})</div>";
                }
                
                $row->catalogPrice .= '</div>';
            }
            
            $row->btn = ht::createFnBtn($settings->addToCartBtn, null, false, array('title' => 'Добавяне в|* ' . mb_strtolower(eshop_Carts::getCartDisplayName()), 'ef_icon' => 'img/16/cart_go.png', 'data-url' => $addUrl, 'data-productid' => $rec->productId, 'data-packagingid' => $rec->packagingId, 'data-eshopproductpd' => $rec->eshopProductId, 'class' => 'eshop-btn addToCard', 'rel' => 'nofollow'));
        }
        
        if($rec->_listView !== true){
            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        }
        
        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if (isset($settings->storeId) && $canStore == 'yes') {
            $quantity = store_Products::getQuantity($rec->productId, $settings->storeId, true);
            if ($quantity < $rec->quantityInPack) {
                if(empty($rec->deliveryTime)){
                    $notInStock = !empty($settings->notInStockText) ? $settings->notInStockText : tr(eshop_Setup::get('NOT_IN_STOCK_TEXT'));
                    $row->saleInfo = "<span class='{$class} option-not-in-stock'>" . $notInStock . ' </span>';
                    $row->quantity = 1;
                    unset($row->btn);
                } else {
                    $row->saleInfo = "<span class='{$class} option-not-in-stock waitingDelivery'>" . tr('Очаквана доставка') . '</span>';
                }
            }
        }
        
        if($rec->_listView !== true){
            $row->catalogPrice = "<div class='eshop-product-price-holder'>{$row->catalogPrice}</div>";
            if(!empty($row->btn)){
                $row->catalogPrice .= "<div class='eshop-product-buy-button'>{$row->btn}</div>";
            }
        } 
        
        return $row;
    }
    
    
    /**
     * Рендиране на опциите във външната част
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public static function renderExternal($data)
    {
        $tpl = new core_ET('');
        
        $fieldset = cls::get(get_called_class());
        $fieldset->FNC('code', 'varchar');
        $fieldset->FNC('params', 'varchar', 'tdClass=paramCol');
        $fieldset->setField('productId', 'tdClass=productCol');
        $fieldset->FNC('catalogPrice', 'double', 'tdClass=rightCol priceCol');
        $fieldset->FNC('packagingId', 'varchar', 'tdClass=centered');
        $fieldset->FLD('quantity', 'varchar', 'tdClass=quantity-input-column small-field');
        $table = cls::get('core_TableView', array('mvc' => $fieldset, 'tableClass' => 'optionsTable'));
        
        if ($data->optionsProductsCount == 1) {
            unset($data->listFields['code']);
            unset($data->listFields['productId']);
        }
        
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'params');
        
        $listFields = &$data->listFields;
        array_walk(array_keys($data->commonParams), function($paramId) use (&$listFields){unset($listFields["param{$paramId}"]);});
        
        $settings = cms_Domains::getSettings();
        $tpl->append($table->get($data->rows, $data->listFields));
        
        $colspan = countR($data->listFields);
        $cartInfo = tr('Всички цени са в') . " {$settings->currencyId}, " . (($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС'));
        $cartInfo = "<tr><td colspan='{$colspan}' class='option-table-info'>{$cartInfo}</td></tr>";
        $tpl->append($cartInfo, 'ROW_AFTER');
        
        $tpl->append(eshop_Products::renderParams($data->commonParams, false), 'AFTER_PRODUCTS');
        
        return $tpl;
    }
    
    
    /**
     * Връща достъпните артикули за избор от домейна
     *
     * @param int|NULL $domainId - домейн или текущия ако не е подаден
     *
     * @return array $options    - възможните артикули
     */
    public static function getAvailableProducts($domainId = null)
    {
        $options = array();
        $groups = eshop_Groups::getByDomain($domainId);
        $groups = array_keys($groups);
        
        $query = self::getQuery();
        $query->EXT('groupId', 'eshop_Products', 'externalName=groupId,externalKey=eshopProductId');
        $query->where("#state = 'active'");
        $query->in('groupId', $groups);
        $query->show('productId,title,state');
        
        while ($rec = $query->fetch()) {
            
            // Трябва да имат цени по избраната политика
            if (self::getPublicDisplayPrice($rec->productId, $rec->packagingId)) {
                $options[$rec->productId] = !empty($rec->title) ? $rec->title : cat_Products::getTitleById($rec->productId, false);
            }
        }
        
        return $options;
    }
    
    
    /**
     * Обновява състоянието на детайлите на е-артикула с тези на Артикула
     *
     * @param int|stdClass|NULL $productId - ид или запис на артикул
     *
     * @return void
     */
    public static function syncStatesByProductId($productId = null)
    {
        $productId = is_object($productId) ? $productId->id : $productId;
        
        $productsWithDetails = array();
        $dQuery = eshop_ProductDetails::getQuery();
        if(isset($productId)){
            $dQuery->where("#productId = {$productId}");
        }
        
        while($dRec = $dQuery->fetch()){
            $productsWithDetails[$dRec->eshopProductId] = $dRec->eshopProductId;
            eshop_ProductDetails::save($dRec, 'state');
        }
        
        // Ако се ъпдейтват всички, ще се ъпдейтнат и тези без детайли
        if(empty($productId) && countR($productsWithDetails)){
            $Products = cls::get('eshop_Products');
            $eQuery = $Products->getQuery();
            $eQuery->notIn('id', $productsWithDetails);
            while($eRec = $eQuery->fetch()){
                $Products->updateMaster($eRec);
            }
        }
    }
    
    
    /**
     * Какво е името на артикула във външната част
     * 
     * @param int $eProductId     - ид на е-артикул
     * @param int $productId      - ид на артикул
     * @return string $publicName - име за показване
     */
    public static function getPublicProductName($eProductId, $productId)
    {
        $productTitle = eshop_ProductDetails::fetchField("#eshopProductId = {$eProductId} AND #productId = {$productId}", 'title');
        $publicName = !empty($productTitle) ? core_Type::getByName('varchar')->toVerbal($productTitle) : cat_Products::getVerbal($productId, 'name');
    
        return $publicName;
    }
}
