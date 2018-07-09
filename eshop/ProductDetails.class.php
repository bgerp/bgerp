<?php



/**
 * Мениджър за детайл в ешоп артикулите
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
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
    public $title = 'Опции на артикулите в онлайн магазина';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'productId,title,packagings=Опаковки/Мерки,modifiedOn,modifiedBy';
    
    
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
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=eshop_ProductDetails::getSellableProducts)', 'caption=Артикул,silent,removeAndRefreshForm=packagings');
        $this->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковки/Мерки,mandatory');
        $this->FLD('title', 'varchar(nullIfEmpty)', 'caption=Заглавие');
        
        $this->setDbUnique('eshopProductId,title');
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
     * @param  int      $productId - артикул
     * @param  int      $domainId  - домейн
     * @param  int|NULL $id        - запис който да се игнорира
     * @return boolean  - среща ли се артикулът в същия домейн?
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
     * Връща достъпните продаваеми артикули
     */
    public static function getSellableProducts($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $products = array();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#state != 'closed' AND #state != 'rejected' AND #isPublic = 'yes' AND #canSell = 'yes'");
        
        if (is_array($onlyIds)) {
            if (!count($onlyIds)) {
                
                return array();
            }
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            $pQuery->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $pQuery->where("#id = ${onlyIds}");
        }
        
        $xpr = "CONCAT(' ', #name, ' ', #code)";
        $pQuery->XPR('searchFieldXpr', 'text', $xpr);
        $pQuery->XPR('searchFieldXprLower', 'text', "LOWER({$xpr})");
        
        if ($q) {
            if ($q{0} == '"') {
                $strict = true;
            }
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            $q = mb_strtolower($q);
            $qArr = ($strict) ? array(str_replace(' ', '.*', $q)) : explode(' ', $q);
        
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $pQuery->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
            
        if ($limit) {
            $pQuery->limit($limit);
        }
        
        $pQuery->show('id,name,code,isPublic,searchFieldXpr');
        
        while ($pRec = $pQuery->fetch()) {
            $products[$pRec->id] = cat_Products::getRecTitle($pRec, false);
        }
        
        return $products;
    }
    
    
    /**
     * Каква е цената във външната част
     *
     * @param  int         $productId
     * @param  int         $packagingId
     * @param  double      $quantityInPack
     * @param  int|NULL    $domainId
     * @return NULL|double
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
            if ($price = price_ListRules::getPrice($listId, $productId, $packagingId)) {
                $priceObject = cls::get('price_ListToCustomers')->getPriceByList($listId, $productId, $packagingId, $quantityInPack);
                
                $price *= $quantityInPack;
                if ($settings->chargeVat == 'yes') {
                    $price *= 1 + cat_Products::getVat($productId);
                }
                $price = currency_CurrencyRates::convertAmount($price, null, null, $settings->currencyId);
            
                $res->price = $price;
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
            $row->productId = cat_Products::getShortHyperlink($rec->productId, true);
            if (!$price = self::getPublicDisplayPrice($rec->productId)) {
                $row->productId = ht::createHint($row->productId, 'Артикулът няма цена и няма да се показва във външната част', 'warning');
            }
        }
    }
    
    
    /**
     * Подготовка на опциите във външната част
     *
     * @param  stdClass $data
     * @return void
     */
    public static function prepareExternal(&$data)
    {
        $data->rows = $data->recs = $data->paramListFields = array();

        // Добавяне към колонките по една за всеки параметър
        $displayParams = eshop_Products::getParamsToDisplay($data->rec->id);
        foreach ($displayParams as $paramId){
        	$data->paramListFields["param{$paramId}"] = cat_Params::getVerbal($paramId, 'typeExt');
        }
        
        $data->listFields = $data->paramListFields + arr::make('code=Код,productId=Опция,packagingId=Опаковка,quantity=Количество,catalogPrice=Цена,btn=|*&nbsp;');
        $fields = cls::get(get_called_class())->selectFields();
        $fields['-external'] = $fields;
        
        $splitProducts = array();
        $query = self::getQuery();
        $query->where("#eshopProductId = {$data->rec->id}");
        $query->orderBy('productId');
        $data->optionsProductsCount = $query->count();
        $data->commonParams = eshop_Products::getCommonParams($data->rec->id);
        
        while ($rec = $query->fetch()) {
            $newRec = (object) array('eshopProductId' => $rec->eshopProductId, 'productId' => $rec->productId, 'title' => $rec->title);
            if (!self::getPublicDisplayPrice($rec->productId)) {
                continue;
            }
            $packagins = keylist::toArray($rec->packagings);
            
            // Кои параметри ще се показват
            $params = cat_Products::getParams($rec->productId, NULL, TRUE);
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
                foreach ($intersect as $pId => $pVal){
                	$clone->{"param{$pId}"} = $pVal;
                	$row->{"param{$pId}"} = $pVal;
                }
                
                $data->recs[] = $clone;
                $data->rows[] = $row;
                $i++;
            }
        }
        
        if (count($data->rows)) {
            uasort($data->rows, function ($obj1, $obj2) {
                if ($obj1->orderCode == $obj2->orderCode) {
                    
                    return $obj1->orderPrice > $obj2->orderPrice;
                }
                
                return strnatcmp($obj1->orderCode, $obj2->orderCode);
            });
            
            $prev = null;
            foreach ($data->rows as &$row1) {
                if (isset($prev) && $prev == $row1->productId) {
                    $row1->productId = "<span class='quiet'>{$row1->productId}</span>";
                }
                $prev = strip_tags($row1->productId);
            }
        }
    }
    
    
    /**
     * Външното представяне на артикула
     *
     * @param  stdClass $rec
     * @return stdClass $row
     */
    private static function getExternalRow($rec)
    {
        $row = new stdClass();
        $row->productId = (empty($rec->title)) ? cat_Products::getVerbal($rec->productId, 'name') : core_Type::getByName('varchar')->toVerbal($rec->title);
        $fullCode = cat_products::getVerbal($rec->productId, 'code');
        $row->code = substr($fullCode, 0, 10);
        $row->code = "<span title={$fullCode}>{$row->code}</span>";
        
        $row->packagingId = tr(cat_UoM::getShortName($rec->packagingId));
        $row->quantity = ht::createTextInput("product{$rec->productId}-{$rec->packagingId}", null, 'size=4,class=eshop-product-option,placeholder=1');
        
        $catalogPriceInfo = self::getPublicDisplayPrice($rec->productId, $rec->packagingId, $rec->quantityInPack);
        $row->catalogPrice = core_Type::getByName('double(smartRound)')->toVerbal($catalogPriceInfo->price);
        $row->catalogPrice = "<b>{$row->catalogPrice}</b>";
        $row->orderPrice = $catalogPriceInfo->price;
        $row->orderCode = $fullCode;
        $addUrl = toUrl(array('eshop_Carts', 'addtocart'), 'local');
        $row->btn = ht::createFnBtn('Купи||Buy', null, false, array('title' => 'Добавяне в|* ' . mb_strtolower(eshop_Carts::getCartDisplayName()), 'ef_icon' => 'img/16/cart_go.png', 'data-url' => $addUrl, 'data-productid' => $rec->productId, 'data-packagingid' => $rec->packagingId, 'data-eshopproductpd' => $rec->eshopProductId, 'class' => 'eshop-btn'));
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        
        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        $settings = cms_Domains::getSettings();
        if (isset($settings->storeId) && $canStore == 'yes') {
            $quantity = store_Products::getQuantity($rec->productId, $settings->storeId, true);
            if ($quantity < $rec->quantityInPack) {
                $notInStock = !empty($settings->notInStockText) ? $settings->notInStockText : tr(eshop_Setup::get('NOT_IN_STOCK_TEXT'));
                $row->btn = "<span class='option-not-in-stock'>" . $notInStock . ' </span>';
            }
        }
        
        if (!empty($catalogPriceInfo->discount)) {
            $discountType = type_Set::toArray($settings->discountType);
            $row->catalogPrice .= "<div class='external-discount'>";
            if (isset($discountType['amount'])) {
                $amountWithoutDiscount = $catalogPriceInfo->price / (1 - $catalogPriceInfo->discount);
                $discountAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount);
                $row->catalogPrice .= "<div class='external-discount-amount'> {$discountAmount}</div>";
            }

            if (isset($discountType['amount'], $discountType['percent'])) {
                $row->catalogPrice .= ' / ';
            }

            if (isset($discountType['percent'])) {
                $discountPercent = core_Type::getByName('percent(smartRound)')->toVerbal($catalogPriceInfo->discount);
                $discountPercent = str_replace('&nbsp;', '', $discountPercent);
                $row->catalogPrice .= "<div class='external-discount-percent'> (-{$discountPercent})</div>";
            }
            $row->catalogPrice .= '</div>';
        }
        
        return $row;
    }
    
    
    /**
     * Рендиране на опциите във външната част
     *
     * @param  stdClass $data
     * @return core_ET  $tpl
     */
    public static function renderExternal($data)
    {
        $tpl = new core_ET('');
        $count = count($data->rows);
        
        $fieldset = cls::get(get_called_class());
        $fieldset->FNC('code', 'varchar');
        $fieldset->FNC('catalogPrice', 'double');
        $fieldset->FNC('btn', 'varchar', 'tdClass=small-field');
        $fieldset->FNC('packagingId', 'varchar', 'tdClass=centered');
        $fieldset->FLD('quantity', 'varchar');
        $fieldset->setField('quantity', 'tdClass=quantity-input-column');
        
        $table = cls::get('core_TableView', array('mvc' => $fieldset, 'tableClass' => 'optionsTable'));
        $paramsTable = cls::get('core_TableView', array('tableClass' => 'paramsTable'));
        
        if ($data->optionsProductsCount == 1) {
            unset($data->listFields['code']);
            unset($data->listFields['productId']);
        }
        
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, $data->paramListFields);
        
        // Подготовка на общите параметри
        $commonParamRows = array();
        foreach ($data->commonParams as $paramId => $val){
        	unset($data->listFields["param{$paramId}"]);
        	$paramRow = cat_Params::recToVerbal($paramId);
        	if(!empty($paramRow->suffix)){
        		$val .= " {$paramRow->suffix}";
        	}
        	
        	$commonParamRows[] = (object)array('caption' => $paramRow->name, 'value' => $val);
        }
        
        $settings = cms_Domains::getSettings();
        if (empty($settings)) {
            unset($data->listFields['btn']);
        }
        
        $tpl->append($table->get($data->rows, $data->listFields));
        
        $colspan = count($data->listFields);
        $cartInfo = tr('Всички цени са в') . " {$settings->currencyId}, " . (($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС'));
        $cartInfo = "<tr><td colspan='{$colspan}' class='option-table-info'>{$cartInfo}</td></tr>";
        $tpl->append($cartInfo, 'ROW_AFTER');
        
        if(count($commonParamRows)){
        	$commonParamsTpl = $paramsTable->get($commonParamRows, 'caption=Параметри,value=|*&nbsp;');
        	$commonParamsTpl->removePlaces();
        	$commonParamsTpl->removeBlocks();
        	$tpl->append($commonParamsTpl, 'ROW_AFTER');
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща достъпните артикули за избор от домейна
     *
     * @param  int|NULL $domainId - домейн или текущия ако не е подаден
     * @return array    $options    - възможните артикули
     */
    public static function getAvailableProducts($domainId = null)
    {
        $options = array();
        $groups = eshop_Groups::getByDomain($domainId);
        $groups = array_keys($groups);
        
        $query = self::getQuery();
        $query->show('productId');
        $query->EXT('groupId', 'eshop_Products', 'externalName=groupId,externalKey=eshopProductId');
        $query->in('groupId', $groups);
        while ($rec = $query->fetch()) {
            
            // Трябва да имат цени по избраната политика
            if (self::getPublicDisplayPrice($rec->productId, $rec->packagingId)) {
                $options[$rec->productId] = cat_Products::getTitleById($rec->productId, false);
            }
        }
        
        return $options;
    }
}
