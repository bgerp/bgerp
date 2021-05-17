<?php


/**
 * Мениджър за детайл на артикулите в е-магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
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
    public $loadList = 'eshop_Wrapper, plg_Created, plg_Modified, plg_SaveAndNew, plg_RowTools2, plg_AlignDecimals2, plg_State2';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    public $listItemsPerPage = 30;
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Опция';
    
    
    /**
     * Заглавие
     */
    public $title = 'Опции на артикулите в е-магазина';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'marketing_InquirySourceIntf';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'eshopProductId=Е-артикул,productId,title,packagings=Опаковки/Мерки,moq,deliveryTime,action,state=Състояние->Детайл,pState=Състояние->Артикул,modifiedOn,modifiedBy';
    
    
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
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=price_ListRules::getSellableProducts,titleFld=name,showTemplates,onlyPublic)', 'caption=Артикул,silent,removeAndRefreshForm=packagings|action,mandatory');
        $this->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковки/Мерки,mandatory');
        $this->FLD('title', 'varchar(nullIfEmpty)', 'caption=Заглавие');
        $this->FLD('deliveryTime', 'time', 'caption=Доставка до');
        
        $this->FLD('state', 'enum(active=Активен,closed=Затворен)', 'caption=Състояние,input=none');
        $this->FLD('action', 'enum(price=Само цена,inquiry=Запитване,buy=Купуване,both=Запитване и купуване)', 'caption=Действия,mandatory');
        $this->FLD('moq', 'double(min=0)', 'caption=MKП');
        
        $this->setDbUnique('eshopProductId,title');
        $this->setDbUnique('eshopProductId,productId');
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
            $productRec = cat_Products::fetch($rec->productId, 'canStore,measureId,state');
            $defaultTitle = eshop_ProductDetails::getPublicProductTitle($rec->eshopProductId, $rec->productId);
            $form->setField('title', "placeholder={$defaultTitle}");
            
            if($productRec->state == 'template'){
                $form->setFieldType('action', 'enum(inquiry=Запитване)');
                $form->setDefault('action', 'inquiry');
            } else {
                $form->setDefault('action', 'buy');
            }
            
            if ($productRec->canStore == 'yes') {
                $packs = cat_Products::getPacks($rec->productId);
                
                $allowedPacks = eshop_Products::getSettingField($rec->eshopProductId, null, 'showPacks');
                if(countR($allowedPacks)){
                    $packs = array_intersect_key($packs, $allowedPacks);
                }
                
                $form->setSuggestions('packagings', $packs);
                $form->setDefault('packagings', keylist::addKey('', key($packs)));
            } else {
                $form->setDefault('packagings', keylist::addKey('', $productRec->measureId));
                $form->setReadOnly('packagings');
            }
        } else {
            $form->setField('packagings', 'input=none');
            $form->setField('action', 'input=none');
        }


       //
    }

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){
            $rec = $form->rec;

            if(static::hasSaleEnded($rec->productId)){
                $form->setWarning('productId', "Крайният срок за онлайн продажба на артикула е изтекъл");
            }
        }
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
            $productRec = cat_Products::fetch($rec->productId, 'state');
        	$row->ROW_ATTR['class'] = "state-{$rec->state}";
        	$row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, TRUE);
        	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
        	if ($productRec->state != 'template' && !self::getPublicDisplayPrice($rec->productId)) {
                $row->productId = ht::createHint($row->productId, 'Артикулът няма цена', 'warning');
            }
            
            $row->title = static::getPublicProductTitle($rec->eshopProductId, $rec->productId);
            if(empty($rec->title)){
                $row->title = ht::createHint("<span style='color:blue'>{$row->title}</span>", 'Заглавието е динамично определено');
            }
            
            $pState = cat_Products::fetchField($rec->productId, 'state');
            $row->pState = cls::get('cat_Products')->getFieldType('state')->toVerbal($pState);
            $row->pState = "<span class='state-{$pState} document-handler'>{$row->pState}</span>";
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'changestate' && isset($rec)){
            $pState = cat_Products::fetchField($rec->productId, 'state');
            if($pState != 'active'){
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'delete' && isset($rec)){
            if(eshop_CartDetails::fetchField("#eshopProductId = {$rec->eshopProductId} AND #productId = {$rec->productId}")){
                $requiredRoles = 'no_one';
            } elseif (marketing_Inquiries2::fetchField("#sourceClassId = {$mvc->getClassId()} AND #sourceId = {$rec->id}")){
                $requiredRoles = 'no_one';
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

        $me = cls::get(get_called_class());
        $data->listFields = arr::make('code=Код,productId=Артикул,packagingId=Опаковка,quantity=Количество,catalogPrice=Цена');
        $fields = cls::get(get_called_class())->selectFields();
        $fields['-external'] = $fields;
        
        $query = self::getQuery();
        $query->where("#eshopProductId = {$data->rec->id} AND #state = 'active'");
        $query->orderBy('productId');
        $data->optionsProductsCount = $query->count();
        $data->commonParams = eshop_Products::getCommonParams($data->rec->id);

        $orderByParam = isset($data->rec->orderByParam) ? $data->rec->orderByParam : '_code';
        $orderByDir = isset($data->rec->orderByDir) ? $data->rec->orderByDir : 'asc';
        
        $recs =  $query->fetchAll();
        
        // Подготовка на полето, по което ще се сортира
        array_walk($recs, function (&$a) use ($orderByParam) {
            if($orderByParam == '_code'){
                $a->orderField = cat_products::getVerbal($a->productId, 'code');
            } elseif($orderByParam == '_title'){
                $a->orderField = static::getPublicProductTitle($a->eshopProductId, $a->productId);
                $a->orderField = mb_strtolower($a->orderField);
            } elseif($orderByParam == '_createdBy'){
                $a->orderField = $a->createdOn;
            } else{
                $value = cat_Products::getParams($a->productId, $orderByParam);
                if(isset($value)){
                    $a->orderField = $value;
                }
            }
        });
        
        // Сортиране на резултатите
        arr::sortObjects($recs, 'orderField', $orderByDir, 'str');

        $onlyServices = true;
        foreach ($recs as $rec){
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            if($canStore == 'yes'){
                $onlyServices = false;
            }

            $newRec = (object) array('recId' => $rec->id, 'eshopProductId' => $rec->eshopProductId, 'productId' => $rec->productId, 'title' => $rec->title, 'deliveryTime' => $rec->deliveryTime, 'action' => $rec->action);
            $paramsText = eshop_CartDetails::getUniqueParamsAsText($rec->eshopProductId, $rec->productId);
            
            $packagings = keylist::toArray($rec->packagings);
            $allowedPacks = eshop_Products::getSettingField($rec->eshopProductId, 'null', 'showPacks');
            if(countR($allowedPacks)){
                $packagings = array_intersect_key($packagings, $allowedPacks);
            }
            
            // Всяка от посочените опаковки се разбива във отделни редове
            $i = 1;
            foreach ($packagings as $packagingId) {
                $clone = clone $newRec;
                $clone->first = ($i == 1) ? true : false;
                $clone->packagingId = $packagingId;
                $packRec = cat_products_Packagings::getPack($rec->productId, $packagingId);
                $clone->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
                
                $row = self::getExternalRow($clone);
                $row->paramsText = $paramsText;
                
                $me->invoke('AfterRecToVerbal', array($row, $rec));
                
                $data->recs[] = $clone;
                $data->rows[] = $row;
                $i++;
            }
        }

        if($onlyServices){
            $showServicePackColumn = eshop_Setup::get('PUBLIC_PRODUCT_SHOW_PACK_COLUMN_IF_ONLY_SERVICES');
            if($showServicePackColumn == 'no'){
                unset($data->listFields['packagingId']);
            }
        }

        if (countR($data->rows)) {
            $prev = null;
            foreach ($data->rows as &$row1) {
                if (isset($prev) && $prev == $row1->orderCode) {
                    $row1->productId = "<span class='quiet'>{$row1->productId}</span>";
                    unset($row1->code);
                    unset($row1->params);
                    unset($row1->_rowTools);
                    $row1->ROW_ATTR['class'] = "no-product-rows";
                } else {
                    if (!empty($row1->paramsText)) {
                        $row1->productId .= "<br><span class='eshop-product-list-param'>{$row1->paramsText}</span>";
                    }
                    
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
        $me = cls::get(get_called_class());
        $settings = cms_Domains::getSettings();
        $row = new stdClass();
        $row->productId = static::getPublicProductTitle($rec->eshopProductId, $rec->productId);
        $fullCode = cat_products::getVerbal($rec->productId, 'code');
        $row->code = mb_substr($fullCode, 0, 10);
        $row->code = "<span title='{$fullCode}'>{$row->code}</span>";

        $now = dt::now();
        $startSale = cat_Products::getParams($rec->productId, 'startSales');

        $productRec = cat_Products::fetch($rec->productId, 'state');
        $row->packagingId = cat_UoM::getShortName($rec->packagingId);
        
        $showPrice = ($productRec->state == 'template') ? false : true;
        $showCartBtn = in_array($rec->action, array('buy', 'both'));
        
        if($productRec->state != 'template' && $rec->action != 'inquiry'){
            $minus = ht::createElement('span', array('class' => 'btnDown', 'title' => 'Намаляване на количеството'), '-');
            $plus = ht::createElement('span', array('class' => 'btnUp', 'title' => 'Увеличаване на количеството'), '+');
            $row->quantity = '<span>' . $minus . ht::createTextInput("product{$rec->productId}-{$rec->packagingId}", 1, "class=eshop-product-option option-quantity-input") . $plus . '</span>';
        }
        
        if($showPrice){
            $catalogPriceInfo = self::getPublicDisplayPrice($rec->productId, $rec->packagingId, $rec->quantityInPack);
            if(isset($catalogPriceInfo->price)){
                $row->catalogPrice = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($catalogPriceInfo->price);
                if($catalogPriceInfo->price == 0){
                    $row->catalogPrice = "<span class='green'>" . tr('Безплатно') . "</span>";
                }
                
                $row->catalogPrice = currency_Currencies::decorate($row->catalogPrice, $settings->currencyId);
                $row->catalogPrice = "<b>{$row->catalogPrice}</b>";
            } else {
                $showCartBtn = $showPrice = false;
                if($rec->action != 'inquiry'){
                    $row->catalogPrice = "<span class=' option-not-in-stock ' style='background-color: #e6e6e6 !important;border: solid 1px #ff7070;color: #c00;margin-top: 5px;'>" . tr('Свържете се с нас') . "</span><br>";
                
                    if(eshop_Products::haveRightFor('single')){
                        $row->catalogPrice = ht::createHint($row->catalogPrice, 'Артикулът няма цена за продажба', 'error', false);
                    }
                }
            }
        }
        
        $row->orderPrice = $catalogPriceInfo->price;
        $row->orderCode = $fullCode;
        $addUrl = toUrl(array('eshop_Carts', 'addtocart'), 'local');
        $class = ($rec->_listView === true) ? 'group-row' : '';

        $stopSale = (!empty($startSale) && $now < $startSale) || static::hasSaleEnded($rec->productId);

        if($showCartBtn && !$stopSale){
            if (!empty($catalogPriceInfo->discount)) {
                $style = ($rec->_listView === true) ? 'style="display:inline-block;font-weight:normal"' : '';
                
                $row->catalogPrice = "<span class='{$class} eshop-discounted-price'>{$row->catalogPrice}</span>";
                $discountType = type_Set::toArray($settings->discountType);
                $row->catalogPrice .= "<div class='{$class} external-discount' {$style}>";
                if (isset($discountType['amount'])) {
                    if($catalogPriceInfo->discount != 1){
                        $amountWithoutDiscount = $catalogPriceInfo->price / (1 - $catalogPriceInfo->discount);
                    } else {
                        $amountWithoutDiscount = $catalogPriceInfo->price;
                    }
                    
                    $discountAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount);
                    $discountAmount = currency_Currencies::decorate($discountAmount, $settings->currencyId);
                    $row->catalogPrice .= "<div class='{$class} external-discount-amount' {$style}> {$discountAmount}</div>";
                }
                
                if (isset($discountType['amount']) && isset($discountType['percent'])) {
                    $row->catalogPrice .= ' / ';
                }
                
                if (isset($discountType['percent'])) {
                    $discountPercent = core_Type::getByName('percent(decimals=2)')->toVerbal($catalogPriceInfo->discount);
                    $discountPercent = str_replace('&nbsp;', '', $discountPercent);
                    $row->catalogPrice .= "<div class='{$class} external-discount-percent' {$style}> (-{$discountPercent})</div>";
                }
                
                $row->catalogPrice .= '</div>';
            }
        }
        
        // Подготовка на бутона за купуване
        if($showCartBtn && !$stopSale){
            $row->btn = ht::createFnBtn($settings->addToCartBtn, null, false, array('title' => 'Добавяне в|* ' . mb_strtolower(eshop_Carts::getCartDisplayName()), 'ef_icon' => 'img/16/cart_go.png', 'data-url' => $addUrl, 'data-productid' => $rec->productId, 'data-packagingid' => $rec->packagingId, 'data-eshopproductpd' => $rec->eshopProductId, 'class' => 'eshop-btn productBtn addToCard', 'rel' => 'nofollow'));
        }
        
        if(in_array($rec->action, array('inquiry', 'both')) && !$stopSale){
            $productRec = cat_Products::fetch($rec->productId, 'innerClass,state');
            $customizeProto = ($productRec->state == 'template') ? 'yes' : 'no';

            if (cls::load($productRec->innerClass, true)) {
                $title = 'Изпратете запитване за|* ' . tr($rec->name);
                Request::setProtected('classId,objectId,customizeProtoOpt');
                $url = toUrl(array('marketing_Inquiries2', 'new', 'classId' => $me->getClassId(), 'objectId' => $rec->recId, 'customizeProtoOpt' => $customizeProto, 'ret_url' => true));
                Request::removeProtected('classId,objectId,customizeProtoOpt');
                
                $row->btnInquiry = ht::createBtn('Запитване', $url, false, false, "ef_icon=img/16/help_contents.png,title={$title},class=productBtn,rel=nofollow");
            }
        }

        if($rec->_listView !== true){
            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        }

        // Проверка дали артикула е спрян от продажба
        if($stopSale){
            if(!empty($startSale) && $now < $startSale){
                $row->quantity = "<span class='quiet'>1</span>";
                $pendingTpl = new core_ET($settings->salePendingText);

                $Time = core_Type::getByName('time(uom=days)');
                $daysBetween = dt::daysBetween($startSale, $now);
                if($daysBetween == 0){
                    $secs = dt::secsBetween($startSale, $now);
                    $inHours = round($secs / 3600);
                    if($inHours == 0){
                        $inMinutes = ($secs < 60) ? 1 : round($secs / 60);
                        $afterTimeVerbal = $Time->toVerbal($inMinutes * 60);
                    } else {
                        $afterTimeVerbal = $Time->toVerbal($inHours * 60 * 60);
                    }
                } else {
                    $afterTimeVerbal = $Time->toVerbal($daysBetween * 24 * 60 * 60);
                }
                $pendingTpl->replace($afterTimeVerbal, "DAYS");

                $row->saleInfo .= "<span class='{$class} option-not-in-stock saleSoon'>{$pendingTpl->getContent()}</span>";
            } elseif(static::hasSaleEnded($rec->productId)){
                $row->saleInfo = "<span class='{$class} option-not-in-stock'>{$settings->saleEndedText}</span>";
            }
        }

        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if (isset($settings->storeId) && $canStore == 'yes' && !$stopSale) {
            $quantity = store_Products::getQuantities($rec->productId, $settings->storeId)->free;

            if ($quantity < $rec->quantityInPack) {
                if(empty($rec->deliveryTime)){
                    $notInStock = !empty($settings->notInStockText) ? $settings->notInStockText : tr(eshop_Setup::get('NOT_IN_STOCK_TEXT'));
                    $row->saleInfo = "<span class='{$class} option-not-in-stock'>" . $notInStock . ' </span>';
                    $row->quantity = 1;
                    unset($row->btn);
                } else {
                    $row->saleInfo = "<span class='{$class} option-not-in-stock waitingDelivery'>" . tr('Очаква се доставка') . '</span>';
                }
            }
        }

        if($rec->_listView !== true){
            $row->catalogPrice = "<div class='eshop-product-price-holder'>{$row->catalogPrice}</div>";
            if(!empty($row->btn)){
                $row->catalogPrice .= "<div class='eshop-product-buy-button'>{$row->btn}</div>";
            }
            
            if(!empty($row->btnInquiry)){
                $row->catalogPrice .= "<div class='eshop-product-buy-button'>{$row->btnInquiry}</div>";
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
        $me = cls::get(get_called_class());
        
        $fieldset = clone $me;
        $fieldset->FNC('code', 'varchar');
        $fieldset->setField('productId', 'tdClass=productCol');
        $fieldset->FNC('catalogPrice', 'double', 'tdClass=rightCol priceCol');
        $fieldset->FNC('packagingId', 'varchar', 'tdClass=centered');
        $fieldset->FLD('quantity', 'varchar', 'tdClass=quantity-input-column small-field');
        $data->listTableMvc = $fieldset;
        $table = cls::get('core_TableView', array('mvc' => $fieldset, 'tableClass' => 'optionsTable'));
        
        if ($data->optionsProductsCount == 1) {
            unset($data->listFields['code']);
            unset($data->listFields['productId']);
        }
        
       
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'quantity');
        
        $settings = cms_Domains::getSettings();
        $me->invoke('BeforeRenderListTable', array($tpl, &$data));
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
        $query->show('productId,title,eshopProductId');
        
        while ($rec = $query->fetch()) {
            
            // Трябва да имат цени по избраната политика
            if (self::getPublicDisplayPrice($rec->productId, $rec->packagingId)) {
                $title = eshop_Products::getTitleById($rec->eshopProductId);
                $title .= ": " .  (!empty($rec->title) ? $rec->title : cat_Products::getTitleById($rec->productId, false));
                $options[$rec->id] = $title;
            }
        }
        
        return $options;
    }
    
    
    /**
     * Какво е името на артикула във външната част
     * 
     * @param int $eProductId       - ид на е-артикул
     * @param int $productId        - ид на артикул
     * @param boolean $showFullName - дали да се показва и името на е-артикула
     * 
     * @return string $publicName - име за показване
     */
    public static function getPublicProductTitle($eProductId, $productId, $showFullName = false)
    {
        $optionRec = eshop_ProductDetails::fetch("#eshopProductId = {$eProductId} AND #productId = {$productId}");
        
        $titleParamId = eshop_Products::fetchField($eProductId, 'titleParamId');
        $title = !empty($optionRec->title) ? $optionRec->title : (!empty($titleParamId) ? cat_Products::getParams($productId, $titleParamId) : null);
        if(!isset($title) || $title === false){
            $title = cat_Products::fetchField($productId, 'name');
        }
        
        if($showFullName){
            $eProductName = eshop_Products::getVerbal($eProductId, 'name');
            
            if($eProductName != $title){
                $title = "{$eProductName}: {$title}";
            }
        }
        
        return $title;
    }
    
    
    /**
     * Подготвя показването, като детайл на артикулите
     * 
     * @param stdClass $data
     * @return void
     */
    public function prepareEshopProductDetail($data)
    {
        $isTemplate = $data->masterData->rec->state == 'template';
        $data->isNotOk = ($data->masterData->rec->canSell != 'yes' || $data->masterData->rec->isPublic != 'yes' || !in_array($data->masterData->rec->state, array('active', 'template')));
        $hasDetail = ($isTemplate) ? eshop_Products::fetch("LOCATE('|{$data->masterId}|', #proto)") : eshop_ProductDetails::fetchField("#productId = {$data->masterId}");

        if(!$hasDetail && $data->isNotOk){
            $data->hide = true;
            
            return;
        }
        
        $data->TabCaption = 'Е-маг';
        $data->Tab = 'top';
        
        // Ако таба е отворен, само тогава да се подготвят данните
        $Param = core_Request::get($data->masterData->tabTopParam, 'varchar');
        if($Param != 'eshopProductDetail'){
            $data->hide = true;
            
            if($data->isNotOk){
                $data->TabCaption = ht::createHint($data->TabCaption, 'Артикулът е бил добавен в Е-маг, но вече не отговаря на условията|*', 'warning');
            }
            
            return;
        }
        
        $data->recs = $data->rows = array();
        $data->listTableMvc = clone $this;
        
        // Ако е шаблон
        if($isTemplate){
            
            // Ще се извличат е-артикулите, където той е избран като протоип
            $data->listFields = arr::make('name=Е-артикул,domainId=Домейн,coMoq=МКП,quantityCount=Количества');
            $data->info = tr('Артикулът се използва като прототип за запитване в Е-маг');
            $query = eshop_Products::getQuery();
            $query->where("LOCATE('|{$data->masterId}|', #proto)");
            $query->orderBy('id', 'DESC');
            
            while($rec = $query->fetch()){
                $data->recs[$rec->id] = $rec;
                $row = eshop_Products::recToVerbal($rec);
                $row->domainId = cms_Domains::getHyperlink($rec->domainId, true);
                $row->quantityCount = !empty($rec->quantityCount) ? $row->quantityCount : tr('Без');
                $data->rows[$rec->id] = $row;
            }
            
        } else {
            $data->listFields = arr::make('eshopProductId=Е-артикул,title=Заглавие,packagings=Опаковки/Мерки,domainId=Домейн,deliveryTime=Доставка,created=Създаване');
            $data->info = tr('Артикулът може да бъде продаван в Е-маг');
            
            // Извличане и вербализиране на записите
            $query = eshop_ProductDetails::getQuery();
            $query->where("#productId = {$data->masterId}");
            $query->orderBy('id', 'DESC');
            
            while($rec = $query->fetch()){
                $data->recs[$rec->id] = $rec;
                $row = eshop_ProductDetails::recToVerbal($rec);
                $row->created = tr("|*{$row->createdBy} |на|* {$row->createdOn}");
                $row->domainId = cms_Domains::getHyperlink(eshop_Products::fetchField($rec->eshopProductId, 'domainId'), true);
                $row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, true);
                $row->ROW_ATTR['class'] = 'state-active';
                $data->rows[$rec->id] = $row;
            }
        }

        if(static::hasSaleEnded($data->masterData->rec->id)){
            $data->info = "<span class='red'>" . tr('Крайният срок за онлайн продажба е изтекъл') . "</span>";
        }

        // Добавяне на бутон за публикуване в Е-маг
        if (eshop_Products::haveRightFor('linktoeshop', (object) array('productId' => $data->masterId))) {
            $linkUrl = array('eshop_Products', 'linktoeshop', 'productId' => $data->masterId, 'ret_url' => true);
            $data->linkBtn = ht::createLink('', $linkUrl, false, 'ef_icon = img/16/add.png,title=Свързване в Е-маг,style=vertical-align: middle; margin-left:5px;');
        }
    }
    
    
    /**
     * Рендира показването, като детайл на артикулите
     *
     * @param stdClass $data
     * @return core_ET|null
     */
    public function renderEshopProductDetail($data)
    {
        if ($data->hide === true) {
            
            return;
        }
        
        $tpl = new ET('');
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tabTitle = tr('Онлайн магазин');
        if($data->isNotOk){
            $tabTitle = ht::createHint($tabTitle, 'Артикулът е бил добавен в Е-маг, но вече не отговаря на условията|*', 'warning');
        }
        $tpl->append($tabTitle, 'title');
        
        $fieldset = new core_FieldSet();
        $fieldset->FLD('eshopProductId', 'varchar', 'tdClass=leftCol');
        $fieldset->FLD('name', 'varchar', 'tdClass=leftCol');
        
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        
        // Рендиране на намерените резултати
        $tpl->append("<div style='margin-bottom:5px'>{$data->info}</div>", 'content');
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'title,deliveryTime');
        $details = $table->get($data->rows, $data->listFields);
        $tpl->append($details, 'content');
        
        if(isset($data->linkBtn)){
            $tpl->append($data->linkBtn, 'title');
        }
        
        return $tpl;
    }
    
    
    /**
     * Какви са дефолтните данни за създаване на запитване
     *
     * @param mixed $id - ид или запис
     *
     * @return array $res
     *               ['title']         - заглавие
     *               ['drvId']         - ид на драйвер
     *               ['lg']            - език
     *               ['protos']        - списък от прототипни артикули
     *               ['quantityCount'] - опционален брой количества
     *               ['moq']           - МКП
     *               ['measureId']     - основна мярка
     *               ['url']           - линк
     */
    public function getInquiryData($id)
    {
        $rec = $this->fetchRec($id);
        $productRec = cat_Products::fetch($rec->productId, 'innerClass,measureId');
        $eProductRec = eshop_Products::fetch($rec->eshopProductId);
        $moq = !empty($rec->moq) ? $rec->moq : cat_Products::getMoq($rec->productId);
        
        $res = array('title' => static::getPublicProductTitle($rec->eshopProductId, $rec->productId),
                     'drvId' => $productRec->innerClass,
                     'lg' => cms_Content::getLang(),
                     'protos' => $rec->productId,
                     'quantityCount' => empty($eProductRec->quantityCount) ? 0 : $eProductRec->quantityCount,
                     'moq' => $moq,
                     'measureId' => $productRec->measureId,
                     'url' => eshop_Products::getUrl($eProductRec),
        );
        
        return $res;
    }


    /**
     * Какво е заглавието на източника
     *
     * @param $id     - ид на запис
     * @return string - заглавие
     */
    public function getSourceTitle($id)
    {
        $rec = $this->fetch($id);
        try{
            $url = eshop_Products::getSingleUrlArray($rec->eshopProductId);
            $title = self::getPublicProductTitle($rec->eshopProductId, $rec->productId);
            $title = ht::createLink($title, $url, false, 'ef_icon=img/16/globe.png');
        } catch(core_exception_Expect $e){
            $title = null;
        }

        return $title;
    }


    /**
     * Премахване на артикули от онлайн магазина по разписание
     */
    function cron_RemoveProductsFromEshop()
    {
        // Кои са всички артикули, закачени към е-артикул
        $query = eshop_ProductDetails::getQuery();
        $query->show('productId');
        $eshopProducts = arr::extractValuesFromArray($query->fetchAll(), 'productId');

        if(!countR($eshopProducts)) return;

        $now = dt::now();
        $closeProducts = array();

        // За всеки
        $delaySecs = eshop_Setup::get('REMOVE_PRODUCTS_WITH_ENDED_SALES_DELAY');
        foreach ($eshopProducts as $productId){
            $saleEnd = cat_Products::getParams($productId, 'endSales');

            // Ако има краен срок за онлайн продажба
            if($saleEnd){

                // Проверява се дали е минало X време след изтичането му
                $saleEnd = str_replace(' 00:00:00', ' 23:59:59', $saleEnd);
                $lifetime = dt::addSecs($delaySecs, $saleEnd);
                if($lifetime <= $now){

                    // Ако е минало, артикула ще бъде премахнат от е-артикула
                    $closeProducts[$productId] = $productId;
                }
            }
        }

        // Ако има изтекли артикули, премахват се от всички е-артикули където са посочени
        if(countR($closeProducts)){
            $query = $this->getQuery();
            $query->in('productId', $closeProducts);
            $query->where("#state != 'closed'");
            while($rec = $query->fetch()){
                $rec->state = 'closed';
                $this->save($rec, 'state');
            }
        }
    }


    /**
     * Дали онлайн продажбата на артикула е спряла към подадената дата
     *
     * @param $productId            - ид на артикул
     * @param null|datetime $date   - дата или null за текущата
     * @return bool                 - спряна ли е продажбата към посочената дата
     */
    public static function hasSaleEnded($productId, $date = null)
    {
        $date = isset($date) ? $date : dt::now();

        $endSale = cat_Products::getParams($productId, 'endSales');
        if(empty($endSale)) return false;

        if(strpos($endSale, ' 00:00:00') !== false){
            $endSale = str_replace(' 00:00:00', ' 23:59:59', $endSale);
        } elseif(strlen($endSale) == 10){
            $endSale = "{$endSale} 23:59:59";
        }

        return $date >= $endSale;
    }
}

