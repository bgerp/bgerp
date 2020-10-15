<?php


/**
 * Мениджър за детайл на кошниците
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
class eshop_CartDetails extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'cartId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,plg_AlignDecimals2,plg_Modified,plg_SaveAndNew';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Заглавие
     */
    public $title = 'Артикули в кошниците';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'eshopProductId=Е-артикул,productId,packagingId,packQuantity,finalPrice=Цена,amount=Сума';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой може да изтрива от кошницата
     */
    public $canRemoveexternal = 'every_one';
    
    
    /**
     * Кой може да ъпдейтва кошницата
     */
    public $canUpdatecart = 'every_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'eshop,ceo';
    
    
    /**
     * Кой има право да чекаутва?
     */
    public $canCheckout = 'every_one';
    
    
    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'eshop,ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('cartId', 'key(mvc=eshop_Carts)', 'caption=Кошница,mandatory,input=hidden,silent');
        $this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'tdClass=productCell,caption=Артикул,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack,mandatory');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=none,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack|displayPrice');
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double', 'input=none');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=none');
        
        $this->FLD('oldPrice', 'double(decimals=2)', 'caption=Стара цена,input=none');
        $this->FLD('finalPrice', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('vat', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=ДДС %,input=none');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'input=none');
        $this->FLD('haveVat', 'enum(yes=Да, separate=Не)', 'input=none');
        
        $this->FLD('discount', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Отстъпка,input=none');
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума');
        $this->FNC('external', 'int', 'input=hidden,silent');
        
        $this->setDbUnique('cartId,eshopProductId,productId,packagingId');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
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
        
        $form->FNC('displayPrice', 'double', 'caption=Цена, input=none');
        $productOptions = eshop_ProductDetails::getAvailableProducts();
        
        $alreadyIn = array();
        if (isset($rec->external)) {
            // От наличните опции се махат тези вече в количката
            $query = self::getQuery();
            $query->where("#cartId = {$rec->cartId}");
            $query->show('productId');
            $alreadyIn = arr::extractValuesFromArray($query->fetchAll(), 'id');
        }
        
        $productOptions = array_diff_key($productOptions, $alreadyIn);
        $form->FLD('eProductId', 'key(mvc=eshop_ProductDetails)', 'caption=Е-артикул,before=productId,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack');
        $form->setOptions('eProductId', array('' => '') + $productOptions);
        $form->setField('eshopProductId', 'input=none');
        $form->setField('productId', 'input=none');
        
        if (countR($productOptions) == 1) {
            $form->setDefault('eProductId', key($productOptions));
        }
        
        $form->input('eProductId', 'silent');
        if (isset($rec->eProductId)) {
            $dRec = eshop_ProductDetails::fetch($rec->eProductId);
            
            $form->setField('packagingId', 'input');
            $form->setField('packQuantity', 'input');
            
            $packs = cat_Products::getPacks($dRec->productId);
            $form->setDefault('eshopProductId', $dRec->eshopProductId);
            $form->setDefault('productId', $dRec->productId);
            $packsSelected = keylist::toArray($dRec->packagings);
            $packs = array_intersect_key($packs, $packsSelected);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            $form->setField('displayPrice', 'input');
        }
    }
    
    
    /**
     * Изпълнява се след опаковане на съдаржанието от мениджъра
     */
    protected static function on_AfterRenderWrapping(core_Manager $mvc, &$res, &$tpl = null, $data = null)
    {
        if (isset($data->form->rec->external)) {
            $tpl->prepend("\n<meta name=\"robots\" content=\"nofollow,noindex\">", 'HEAD');
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
        
        if (isset($rec->packagingId)) {
            $rec->defaultQuantity = $rec->packQuantity;
            if(empty($rec->packQuantity)){
                $moq = cat_Products::getMoq();
                $rec->defaultQuantity = (!empty($moq)) ? $moq : 1;
                $form->setField('packQuantity', "placeholder={$rec->defaultQuantity}");
            }
            
            $productInfo = cat_Products::getProductInfo($rec->productId);
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->quantity = $rec->defaultQuantity * $rec->quantityInPack;
            
            $settings = cms_Domains::getSettings();
            if ($price = eshop_ProductDetails::getPublicDisplayPrice($rec->productId, $rec->packagingId, $rec->quantityInPack)) {
                $price->price = round($price->price, 2);
                $form->setReadOnly('displayPrice', $price->price);
                $unit = $settings->currencyId . ' ' . (($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС'));
                $form->setField('displayPrice', "unit={$unit}");
                $form->rec->haveVat = $settings->chargeVat;
                $form->rec->vat = cat_Products::getVat($rec->productId);
            }
        }
        
        if ($form->isSubmitted()) {
            
            // Проверка на к-то
            $warning = null;
            if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                $form->setWarning('packQuantity', $warning);
            }
            
            // Проверка достигнато ли е максималното количество
            $packQuantity = isset($rec->packQuantity) ? $rec->packQuantity : $rec->defaultQuantity;
            $maxQuantity = self::getMaxQuantity($rec->productId, $rec->quantityInPack, $rec->eshopProductId);
            
            
            if (isset($maxQuantity) && $maxQuantity < $packQuantity) {
                $form->setError('packQuantity', 'Избраното количество не е налично');
            }
            
            if (!$form->gotErrors()) {
                if ($id = eshop_CartDetails::fetchField("#cartId = {$rec->cartId} AND #eshopProductId = {$rec->eshopProductId} AND #productId = {$rec->productId} AND #packagingId = {$rec->packagingId}")) {
                    $exRec = self::fetch($id);
                    $rec->packQuantity += ($exRec->quantity / $exRec->quantityInPack);
                    $rec->id = $id;
                }
            }
        }
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->finalPrice) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->amount = $rec->finalPrice * ($rec->quantity / $rec->quantityInPack);
    }
    
    
    /**
     * Добавя ред в количката
     *
     * @param int    $cartId         - кошница
     * @param int    $eshopProductId - артикул от е-мага
     * @param int    $productId      - артикул от каталога
     * @param int    $packagingId    - избрана опаковка/мярка
     * @param float  $packQuantity   - к-во в избраната опаковка
     * @param int    $quantityInPack - к-во в опаковка
     * @param float  $packPrice      - ед. цена с ДДС, във валутата от настройките или NULL
     * @param string $currencyId     - код на валута
     * @param bool   $hasVat         - дали сумата е с ДДС или не
     */
    public static function addToCart($cartId, $eshopProductId, $productId, $packagingId, $packQuantity, $quantityInPack = null, $packPrice = null, $currencyId = null, $hasVat = null)
    {
        expect($cartRec = eshop_Carts::fetch("#id = {$cartId} AND #state = 'draft'"));
        expect($eshopRec = eshop_Products::fetch($eshopProductId));
        expect(cat_Products::fetch($productId));
        expect($productRec = eshop_ProductDetails::fetch("#eshopProductId = '{$eshopProductId}' AND #productId = '{$productId}'"));
        expect($productRec->state == 'active');
        expect($eshopRec->state != 'closed');
        
        if (empty($quantityInPack)) {
            $packRec = cat_products_Packagings::getPack($productId, $packagingId);
            $quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
        }
        
        $settings = eshop_Settings::getSettings('cms_Domains', $cartRec->domainId);
        $vat = cat_Products::getVat($productId);
        $quantity = $packQuantity * $quantityInPack;
        $currencyId = isset($currencyId) ? $currencyId : (isset($settings->currencyId) ? $settings->currencyId : acc_Periods::getBaseCurrencyCode());
        
        $dRec = (object) array('cartId' => $cartId,
            'eshopProductId' => $eshopProductId,
            'productId' => $productId,
            'packagingId' => $packagingId,
            'quantityInPack' => $quantityInPack,
            'vat' => $vat,
            'quantity' => $quantity,
            'currencyId' => $currencyId,
        );
        
        if (!empty($packPrice)) {
            $dRec->finalPrice = $packPrice;
            $dRec->haveVat = ($hasVat) ? (($hasVat === true) ? 'yes' : 'no') : (($settings->chargeVat) ? $settings->chargeVat : 'yes');
            $dRec->_updatePrice = false;
        } else {
            $dRec->haveVat = ($settings->chargeVat) ? $settings->chargeVat : 'yes';
        }
        
        if ($exRec = self::fetch("#cartId = {$cartId} AND #eshopProductId = {$eshopProductId} AND #productId = {$productId} AND #packagingId = {$packagingId}")) {
            $exRec->quantity += $dRec->quantity;
            self::save($exRec, 'quantity,finalPrice,oldPrice,discount');
        } else {
            $dRec->oldPrice = null;
            self::save($dRec);
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if ($rec->_updatePrice === false) {
            
            return;
        }
        
        self::updatePriceInfo($rec);
    }
    
    
    /**
     * Колко е максималното допустимо количество. Ако не е избран склад
     * или артикула не е складируем то няма максимално количество
     *
     * @param int   $productId      - ид на артикул
     * @param float $quantityInPack - кво в опаковка
     *
     * @return NULL|float $maxQuantity - максималното к-во, NULL за без ограничение
     */
    public static function getMaxQuantity($productId, $quantityInPack, $eshopProductId)
    {
        $maxQuantity = null;
        
        $canStore = cat_Products::fetchField($productId, 'canStore');
        $settings = cms_Domains::getSettings();
        if (isset($settings->storeId) && $canStore == 'yes') {
            $deliveryTime = eshop_ProductDetails::fetchField("#eshopProductId = {$eshopProductId} AND #productId = {$productId}", 'deliveryTime');
            $quantityInStore = store_Products::getQuantity($productId, $settings->storeId, true);
            
            if(isset($deliveryTime) && $quantityInStore <= 0) return $maxQuantity;
            
            $maxQuantity = round($quantityInStore / $quantityInPack, 2);
        }
        
        return $maxQuantity;
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
            $row->productId = cat_Products::getHyperlink($rec->productId, true);
            $row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, true);
        } elseif (isset($fields['-external'])) {
            core_RowToolbar::createIfNotExists($row->_rowTools);
            if ($mvc->haveRightFor('removeexternal', $rec)) {
                $removeUrl = toUrl(array('eshop_CartDetails', 'removeexternal', $rec->id), 'local');
                $row->_rowTools->addFnLink('Премахване', '', array('ef_icon' => 'img/16/deletered.png', 'title' => 'Премахване на артикул', 'data-cart' => $rec->cartId, 'data-url' => $removeUrl, 'class' => 'remove-from-cart', 'warning' => tr('Наистина ли желаете да премахнете артикула?')));
            }
            
            $row->productId = eshop_ProductDetails::getPublicProductTitle($rec->eshopProductId, $rec->productId, true);
            $row->packagingId = cat_UoM::getShortName($rec->packagingId);
            
            $quantity = (isset($rec->packQuantity)) ? $rec->packQuantity : 1;
            $dataUrl = toUrl(array('eshop_CartDetails', 'updateCart', $rec->id, 'cartId' => $rec->cartId), 'local');
            
            // Колко е максималното допустимо количество
            $maxQuantity = self::getMaxQuantity($rec->productId, $rec->quantityInPack, $rec->eshopProductId);
            $maxReachedTex = '';
            if(isset($maxQuantity)){
                $maxReachedTex = tr("Избраното количество не е налично");
            }
            
            $minus = ht::createElement('span', array('class' => 'btnDown', 'title' => 'Намаляване на количеството'), '-');
            $plus = ht::createElement('span', array('class' => 'btnUp', 'title' => 'Увеличаване на количеството'), '+');
            $row->quantity = '<span>' . $minus . ht::createTextInput("product{$rec->productId}", $quantity, "class=option-quantity-input autoUpdate,data-quantity={$quantity},data-url='{$dataUrl}',data-maxquantity={$maxQuantity},data-maxquantity-reached-text={$maxReachedTex}") . $plus . '</span>';
            
            self::updatePriceInfo($rec, null, true);
            
            $settings = cms_Domains::getSettings();
            if(isset($rec->finalPrice)){
                $finalPrice = currency_CurrencyRates::convertAmount($rec->finalPrice, null, $rec->currencyId, $settings->currencyId);
                $row->finalPrice = core_Type::getByName('double(smartRound)')->toVerbal($finalPrice);
                $row->finalPrice = currency_Currencies::decorate($row->finalPrice, $settings->currencyId);
                
                if ($rec->oldPrice) {
                    $difference = round($rec->finalPrice, 2) - round($rec->oldPrice, 2);
                    $caption = ($difference > 0) ? 'увеличена' : 'намалена';
                    $difference = abs($difference);
                    $difference = currency_CurrencyRates::convertAmount($difference, null, $rec->currencyId, $settings->currencyId);
                    $differenceVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($difference);
                    $hint = "Цената е {$caption} с|* {$differenceVerbal} {$settings->currencyId}";
                    $row->finalPrice = ht::createHint($row->finalPrice, $hint, 'warning');
                }
                
                $amount = currency_CurrencyRates::convertAmount($rec->amount, null, $rec->currencyId, $settings->currencyId);
                $row->amount = core_Type::getByName('double(decimals=2)')->toVerbal($amount);
                $row->amount = currency_Currencies::decorate($row->amount, $settings->currencyId);
            } else {
                $row->finalPrice = "<span class='red'>N/A</span>";
                $row->amount = "<span class='red'>N/A</span>";
                $row->ROW_ATTR['class'] = 'cartErrorRow';
            }
            
            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            $row->productId .= " <span class='small'>({$row->packagingId})</span>";
            
            $url = eshop_Products::getUrl($rec->eshopProductId);
            $row->productId = ht::createLinkRef($row->productId, $url);
            
            // Показване на уникалните параметри под името на артикула
            $paramsText = self::getUniqueParamsAsText($rec->eshopProductId, $rec->productId);
            if (!empty($paramsText)) {
                $row->productId .= "<br><span class='eshop-product-list-param'>{$paramsText}</span>";
            }
        }
        
        $productRec = cat_Products::fetch($rec->productId, 'canStore');
        if (isset($settings->storeId) && $productRec->canStore == 'yes') {
            $quantity = store_Products::getQuantity($rec->productId, $settings->storeId, true);
            $eshopProductRec = eshop_ProductDetails::fetch("#eshopProductId = {$rec->eshopProductId} AND #productId = {$rec->productId}", 'deliveryTime');
            
            if (is_null($maxQuantity) && $maxQuantity <= 0) {
                if(!empty($eshopProductRec->deliveryTime)){
                    $row->productId .= "<br><span  class='option-not-in-stock waitingDelivery'>" . tr('Очаква се доставка') . '</span>';
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'removeexternal' || $action == 'updatecart' || $action == 'checkout' || ($action == 'add' && isset($rec))) {
            if (empty($rec->cartId)) {
                $requiredRoles = 'no_one';
            } elseif (!eshop_Carts::haveRightFor('viewexternal', $rec->cartId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Екшън за изтриване/изпразване на кошницата
     */
    public function act_removeexternal()
    {
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        $id = Request::get('id', 'int');
        $cartId = Request::get('cartId', 'int');
        $this->requireRightFor('removeexternal', (object) array('cartId' => $cartId));
        $deleteCart = false;
        
        if (isset($id)) {
            $this->delete($id);
            cls::get('eshop_Carts')->updateMaster($cartId);
            vislog_History::add("Изтриване на артикул от количка");
            $msg = '|Артикулът е премахнат|*!';
            $dCount = $this->count("#cartId = {$cartId}");
            
            if(empty($dCount)){
                $deleteCart = true;
                eshop_Carts::delete($cartId);
                vislog_History::add("Изтриване на количката");
            }
        } else {
            $deleteCart = true;
        }
        
        core_Statuses::newStatus($msg);
        core_Lg::pop();
        
        if($deleteCart){
            $this->delete("#cartId = {$cartId}");
            cls::get('eshop_Carts')->updateMaster($cartId);
            eshop_Carts::delete($cartId);
            vislog_History::add("Изтриване на количката");
        }
        
        Mode::set('currentExternalTab', 'eshop_Carts');
        
        // Ако заявката е по ajax
        if (Request::get('ajax_mode') && $deleteCart === false) {
           
            return self::getUpdateCartResponse($cartId);
        }
        
        return followRetUrl(null, $msg);
    }
    
    
    /**
     * Какво да се върне по AJAX
     *
     * @param stdClass $cartId
     *
     * @return stdClass $res
     */
    private static function getUpdateCartResponse($cartId)
    {
        cls::get('eshop_Carts')->updateMaster($cartId);
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        // Ще реплейснем само бележката
        $resObj1 = new stdClass();
        $resObj1->func = 'smartCenter';
        
        $resObj2 = new stdClass();
        $resObj2->func = 'html';
        $resObj2->arg = array('id' => 'cart-view-single', 'html' => eshop_Carts::renderView($cartId)->getContent(), 'replace' => true);
        
        // Ще се реплейсне статуса на кошницата
        $resObj3 = new stdClass();
        $resObj3->func = 'html';
        $resObj3->arg = array('id' => 'cart-external-status', 'html' => eshop_Carts::getStatus($cartId)->getContent(), 'replace' => true);
        
        // Ще се реплейсне статуса на кошницата
        $resObj4 = new stdClass();
        $resObj4->func = 'changeInputWidth';

        // Ще забрани необходимите бутони
        $resObj5 = new stdClass();
        $resObj5->func = 'disableBtns';
        
        // Показваме веднага и чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        
        $res = array_merge(array($resObj1, $resObj2, $resObj3, $resObj4, $resObj5), (array) $statusData);
        core_Lg::pop();
        
        return $res;
    }
    
    
    /**
     * Екшън за изтриване/изпразване на кошницата
     */
    public function act_updateCart()
    {
        $id = Request::get('id', 'int');
        $cartId = Request::get('cartId', 'int');
        $quantity = Request::get('packQuantity', 'double');
        $this->requireRightFor('updatecart', (object) array('cartId' => $cartId));
        $quantity = rtrim($quantity, '.');
        $quantity = rtrim($quantity, ',');
        expect($quantity && $quantity > 0, 'Количеството трябва да е положително');
        
        $rec = self::fetch($id);
        $rec->quantity = $quantity * $rec->quantityInPack;
        self::save($rec, 'quantity');
        vislog_History::add("Обновяване на количество в количка");
        
        Mode::set('currentExternalTab', 'eshop_Carts');
        
        // Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
            
            return self::getUpdateCartResponse($cartId);
        }
        
        return followRetUrl();
    }
    
    
    /**
     * Колко ще е доставката от въведените данни
     *
     * @param stdClass $masterRec
     * @param mixed $TransCalc
     *
     * @return NULL|array
     */
    public static function getDeliveryInfo($masterRec, &$TransCalc)
    {
        $masterRec = eshop_Carts::fetchRec($masterRec);
        $query = self::getQuery();
        $query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $query->where("#cartId = {$masterRec->id} AND #canStore != 'no'");
        $query->show('productId,quantity,packagingId,canStore');
        
        if (empty($masterRec->termId) || !$query->count()) {
            
            return;
        }
        
        $TransCalc = cond_DeliveryTerms::getTransportCalculator($masterRec->termId);
        if (!$TransCalc) {
            
            return;
        }
        
        $deliveryData = array('deliveryCountry' => $masterRec->deliveryCountry, 'deliveryPCode' => $masterRec->deliveryPCode, 'deliveryPlace' => $masterRec->deliveryPlace, 'deliveryAddress' => $masterRec->deliveryAddress);
        $deliveryData += $masterRec->deliveryData;
        
        // Колко е общото тегло и обем за доставка
        $products = arr::extractSubArray($query->fetchAll(), 'productId,quantity,packagingId');
        $total = sales_TransportValues::getTotalWeightAndVolume($TransCalc, $products, $masterRec->termId, $deliveryData);
        if($total < 0){
            
            return array('amount' => -1);
        }
        
        $transportAmount = 0;
        foreach ($products as $p1) {
            $fee = sales_TransportValues::getTransportCost($masterRec->termId, $p1->productId, $p1->packagingId, $p1->quantity, $total, $deliveryData);
            if (is_array($fee)) {
                $transportAmount += $fee['totalFee'];
            }
        }
        
        $res = array('amount' => $transportAmount);
        if (isset($fee['deliveryTime'])) {
            $res['deliveryTime'] = $fee['deliveryTime'];
        }
        
        return $res;
    }
    
    
    /**
     * Обновява ценовата информация
     *
     * @param stdClass $rec
     * @param int|NULL $domainId
     * @param bool     $save
     */
    public static function updatePriceInfo(&$rec, $domainId = null, $save = false)
    {
        $settings = cms_Domains::getSettings($domainId);
        $rec->currencyId = isset($rec->currencyId) ? $rec->currencyId : $settings->currencyId;
        
        // Коя е ценовата политика
        $listId = $oldListId = $settings->listId;
        
        if ($lastActiveFolder = core_Mode::get('lastActiveContragentFolder')) {
            $Cover = doc_Folders::getCover($lastActiveFolder);
            $listId = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that);
        }
        
        // Ако има взема се цената от нея
        if (isset($listId)) {
            $price = price_ListRules::getPrice($listId, $rec->productId, $rec->packagingId);
            
            // Ако стария лист е различен от новия
            if($oldListId != $listId){
                
                // И старата цена е по-евтина, то се взима тя
                $priceOld = price_ListRules::getPrice($oldListId, $rec->productId, $rec->packagingId);
                if(!empty($priceOld) && trim(round($priceOld, 5)) < trim(round($price))){
                    $price = $priceOld;
                    $listId = $oldListId;
                }
            }
            
            $priceObject = cls::get('price_ListToCustomers')->getPriceByList($listId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            if (!empty($priceObject->discount)) {
                $discount = $priceObject->discount;
            }
           
            $finalPrice = $price * $rec->quantityInPack;
            if ($rec->haveVat == 'yes') {
                $finalPrice *= 1 + $rec->vat;
            }
           
            $finalPrice = currency_CurrencyRates::convertAmount($finalPrice, null, null, $rec->currencyId);
        }
        
        $toleranceDiff = price_Lists::fetchField($listId, 'discountComparedShowAbove');
        $toleranceDiff = !empty($toleranceDiff) ? $toleranceDiff * 100 : 1;
        
        if(empty($finalPrice) && is_null($price)){
            if(is_null($price) && is_null($rec->finalPrice)){
                
                return;
            }
            
            $rec->oldPrice = $rec->finalPrice;
            $rec->finalPrice = null;
            $rec->discount = null;
            $rec->amount = null;
            $update = true;
        } else {
           
            // Ако цената е променена, обновява се
            $update = false;
            if (!isset($rec->finalPrice) || (isset($rec->finalPrice) && abs(core_Math::diffInPercent($finalPrice, $rec->finalPrice)) > $toleranceDiff)) {
                $rec->oldPrice = $rec->finalPrice;
                $rec->finalPrice = $finalPrice;
                $rec->discount = $discount;
                $rec->amount = $rec->finalPrice * ($rec->quantity / $rec->quantityInPack);
                $update = true;
            }
        }
        
        if ($update === true && $save === true) {
            self::save($rec, 'oldPrice,finalPrice,discount');
            $rec->_updatedPrice = true;
        }
    }
    
    
    /**
     * Кои са уникалните параметри на артикула като текст
     * 
     * @param int $eshopProductId
     * @param int $productId
     * @param boolean $asRichText
     * 
     * @return string $str
     */
    public static function getUniqueParamsAsText($eshopProductId, $productId, $asRichText = false)
    {
        $displayParams = eshop_Products::getSettingField($eshopProductId, null, 'showParams');
        $commonParams = eshop_Products::getCommonParams($eshopProductId);
        $productParams = cat_Products::getParams($productId, null, true);
        
        //bp($displayParams, $commonParams, $productParams);
        
        if($asRichText){
            $pureParams = cat_Products::getParams($productId);
            $fileTypes = array(cond_type_File::getClassId(), cond_type_Image::getClassId());
        }
        
        $productParams = array_intersect_key($productParams, $displayParams);
        $diff = array_diff_key($productParams, $commonParams);
        
        $arr = array();
        foreach ($diff as $paramId => $value) {
            $paramRec = cat_Params::fetch($paramId, 'driverClass,suffix,name');
            $value = (!empty($paramRec->suffix)) ? $value .  ' ' . tr($paramRec->suffix) : $value;
           
            if($asRichText && in_array($paramRec->driverClass, $fileTypes)){
                $handler = $pureParams[$paramId];
                $fileName = strip_tags($value);
                $value = "[file={$handler}]{$fileName}[/file]";
            }
            
            $arr[] = tr(cat_Params::getVerbal($paramRec, 'name')) . ': ' . $value;
        }
        
        $str = (countR($arr)) ? implode(', ', $arr) : '';
        
        return $str;
    }
}
