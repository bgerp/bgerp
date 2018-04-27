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
	public $listFields = 'productId,packagings=Опаковки/Мерки,modifiedOn,modifiedBy';
	
	
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
	function description()
	{
		$this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');
		$this->FLD('productId', "key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=eshop_ProductDetails::getSellableProducts)", 'caption=Артикул,silent,removeAndRefreshForm=packagings');
		$this->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковки/Мерки,mandatory');
	}
		
     
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
	
		if(isset($rec->productId)){
			$productRec = cat_Products::fetch($rec->productId, 'canStore,measureId');
			if($productRec->canStore == 'yes'){
				$packs = cat_Products::getPacks($rec->productId);
				$form->setSuggestions('packagings', $packs);
				$form->setDefault('packagings', keylist::addKey('', key($packs)));
			} else {
				$form->setDefault('packagings', keylist::addKey('', $productRec->measureId));
				$form->setReadOnly('packagings');
			}
		}  else {
			$form->setField('packagings', 'input=none');
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
		$rec = $form->rec;
		if($form->isSubmitted()){
			$thisDomainId = eshop_Products::getDomainId($rec->eshopProductId);
			if(self::isTheProductAlreadyInTheSameDomain($rec->productId, $thisDomainId, $rec->id)){
				$form->setError('productId', 'Артикулът е вече добавен в същия домейн');
			}
		}
	}
	
	
	/**
	 * Артикулът наличен ли е в подадения домейн
	 * 
	 * @param int $productId - артикул
	 * @param int $domainId  - домейн
	 * @param int|NULL $id   - запис който да се игнорира
	 * @return boolean       - среща ли се артикулът в същия домейн?
	 */
	public static function isTheProductAlreadyInTheSameDomain($productId, $domainId, $id = NULL)
	{
		$domainIds = array();
		$query = self::getQuery();
		$query->where("#productId = {$productId} AND #id != '{$id}'");
		while($eRec = $query->fetch()){
			$eproductDomainId = eshop_Products::getDomainId($eRec->eshopProductId);
			$domainIds[$eproductDomainId] = $eproductDomainId;
		}
		
		return array_key_exists($domainId, $domainIds);
	}
	
	
	
	/**
     * Връща достъпните продаваеми артикули
     */
    public static function getSellableProducts($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
    	$products = array();
    	$pQuery = cat_Products::getQuery();
    	$pQuery->where("#state != 'closed' AND #state != 'rejected' AND #isPublic = 'yes' AND #canSell = 'yes'");
    	
    	if(is_array($onlyIds)) {
    		if(!count($onlyIds)) return array();
    		$ids = implode(',', $onlyIds);
    		expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
    		$pQuery->where("#id IN ($ids)");
    	} elseif(ctype_digit("{$onlyIds}")) {
    		$pQuery->where("#id = $onlyIds");
    	}
    	
    	$xpr = "CONCAT(' ', #name, ' ', #code)";
    	$pQuery->XPR('searchFieldXpr', 'text', $xpr);
    	$pQuery->XPR('searchFieldXprLower', 'text', "LOWER({$xpr})");
    	
    	if($q) {
    		if($q{0} == '"') $strict = TRUE;
    		$q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
    		$q = mb_strtolower($q);
    		$qArr = ($strict) ? array(str_replace(' ', '.*', $q)) : explode(' ', $q);
    	
    		$pBegin = type_Key2::getRegexPatterForSQLBegin();
    		foreach($qArr as $w) {
    			$pQuery->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
    		}
    	}
    		
    	if($limit) {
    		$pQuery->limit($limit);
    	}
    	
    	$pQuery->show('id,name,code,isPublic,searchFieldXpr');
    	
    	while($pRec = $pQuery->fetch()) {
    		$products[$pRec->id] = cat_Products::getRecTitle($pRec, FALSE);
    	}
    	
    	return $products;
	}
	
	
	/**
	 * Каква е цената във външната част
	 * 
	 * @param int $productId
	 * @param int $packagingId
	 * @param double $quantityInPack
	 * @param int|NULL $domainId
	 * @return NULL|double
	 */
	public static function getPublicDisplayPrice($productId, $packagingId = NULL, $quantityInPack = 1, $domainId = NULL)
	{
		$res = (object)array('price' => NULL, 'discount' => NULL);
		$domainId = (isset($domainId)) ? $domainId : cms_Domains::getPublicDomain()->id;
		$settings = eshop_Settings::getSettings('cms_Domains', $domainId);
		
		if(isset($settings->listId)){
			if($price = price_ListRules::getPrice($settings->listId, $productId, $packagingId)){
				$priceObject = cls::get(price_ListToCustomers)->getPriceByList($settings->listId, $productId, $packagingId, $quantityInPack);
				
				$price *= $quantityInPack;
				
				if($settings->chargeVat == 'yes'){
					$price *= 1 + cat_Products::getVat($productId);
				}
				$price = currency_CurrencyRates::convertAmount($price, NULL, NULL, $settings->currencyId);
			
				$res->price = $price;
				if(!empty($priceObject->discount)){
					$res->discount = $priceObject->discount;
				}
				
				return $res;
			}
		}
		
		return NULL;
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
		if(isset($fields['-list'])){
			$row->productId = cat_Products::getShortHyperlink($rec->productId, TRUE);
			if(!$price = self::getPublicDisplayPrice($rec->productId)){
				$row->productId = ht::createHint($row->productId, 'Артикулът няма цена и няма да се показва във външната част', 'warning');
			}
		}
	}
	
	
	/**
	 * Подготовка на опциите във външната част
	 * 
	 * @param stdClass $data
	 * @return void
	 */
	public static function prepareExternal(&$data)
	{
		$data->rows = array();
		$fields = cls::get(get_called_class())->selectFields();
		$fields['-external'] = $fields;
		
		$splitProducts = array();
		$query = self::getQuery();
		$query->where("#eshopProductId = {$data->rec->id}");
		while($rec = $query->fetch()){
			$newRec = (object)array('eshopProductId' => $rec->eshopProductId, 'productId' => $rec->productId);
			if(!self::getPublicDisplayPrice($rec->productId)) continue;
			$packagins = keylist::toArray($rec->packagings);
			
			// Всяка от посочените опаковки се разбива във отделни редове
			foreach($packagins as $packagingId){
				$clone = clone $newRec;
				$clone->packagingId = $packagingId;
				$packRec = cat_products_Packagings::getPack($rec->productId, $packagingId);
				$clone->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
				$data->rows[] = self::getExternalRow($clone);
			}
		}
	}
	
	
	/**
	 * Външното представяне на артикула
	 * 
	 * @param stdClass $rec
	 * @return stdClass $row
	 */
	private static function getExternalRow($rec)
	{
		$row = new stdClass();
		$row->productId = cat_Products::getVerbal($rec->productId, 'name');
		$row->code = cat_products::getVerbal($rec->productId, 'code');
		$row->packagingId = cat_UoM::getShortName($rec->packagingId);
		$row->quantity = ht::createTextInput("product{$rec->productId}", NULL, "size=4,class=eshop-product-option,placeholder=1");
		
		$catalogPriceInfo = self::getPublicDisplayPrice($rec->productId, $rec->packagingId, $rec->quantityInPack);
		$row->catalogPrice = core_Type::getByName('double(decimals=2)')->toVerbal($catalogPriceInfo->price);
		
		$addUrl = toUrl(array('eshop_Carts', 'addtocart'), 'local');
		$row->btn = ht::createFnBtn('Добави', NULL, FALSE, array('title'=> 'Добавяне в кошницата', 'ef_icon' => 'img/16/cart_go.png', 'data-url' => $addUrl, 'data-productid' => $rec->productId, 'data-packagingid' => $rec->packagingId, 'data-eshopproductpd' => $rec->eshopProductId, 'class' => 'cart-add-product-btn'));
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
		
		$canStore = cat_Products::fetchField($rec->productId, 'canStore');
		$settings = cms_Domains::getSettings();
		if(isset($settings->storeId) && $canStore == 'yes'){
			$quantity = store_Products::getQuantity($rec->productId, $settings->storeId, TRUE);
			if($quantity < $rec->quantityInPack){
				$notInStock = !empty($settings->notInStockText) ? $settings->notInStockText : tr(eshop_Setup::get('NOT_IN_STOCK_TEXT'));
				$row->btn = "<span class='option-not-in-stock'>" . $notInStock . " </span>";
			}
		}
		
		if(!empty($catalogPriceInfo->discount)){
			$amountWithoutDiscount = $catalogPriceInfo->price / (1 - $catalogPriceInfo->discount) ;
			$discount = ($settings->discountType == 'amount') ? core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount) : core_Type::getByName('percent(decimals=2)')->toVerbal($catalogPriceInfo->discount);
			$class = ($settings->discountType == 'amount') ? 'external-discount-amount' : 'external-discount-percent';
			$row->catalogPrice .= "<div class='{$class}'> {$discount}</dib>";
		}
		
		return $row;
	}
	
	
	/**
	 * Рендиране на опциите във външната част
	 *
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public static function renderExternal($data)
	{
		$tpl = new core_ET("");
		$count = count($data->rows);
		
		$fieldset = cls::get(get_called_class());
		$fieldset->FNC('code', 'varchar', 'smartCenter');
		$fieldset->FLD('quantity', 'varchar');
		$fieldset->setField('quantity', 'tdClass=quantity-input-column');
		
		$table = cls::get('core_TableView', array('mvc' => $fieldset, 'tableClass' => 'optionsTable'));
		
		$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
		$tpl->append($table->get($data->rows, "code=Код,productId=Артикул,packagingId=Опаковка,quantity=К-во,catalogPrice=Цена,btn=|*&nbsp;"));
		
		$cartInfo = tr('Всички цени са в') . " {$settings->currencyId}, " . (($settings->chargeVat == 'yes') ? tr('с включено ДДС') : tr('без ДДС'));
		$cartInfo = "<tr><td colspan='6' class='option-table-info'>{$cartInfo}</td></tr>";
		$tpl->replace($cartInfo, 'ROW_AFTER');
		
		return $tpl;
	}
	
	
	/**
	 * Връща достъпните артикули за избор от домейна
	 * 
	 * @param int|NULL $domainId - домейн или текущия ако не е подаден
	 * @return array $options    - възможните артикули
	 */
	public static function getAvailableProducts($domainId = NULL)
	{
		$options = array();
		$groups = eshop_Groups::getByDomain($domainId);
		$groups = array_keys($groups);
		
		$query = self::getQuery();
		$query->show('productId');
		$query->EXT('groupId', 'eshop_Products', 'externalName=groupId,externalKey=eshopProductId');
		$query->in('groupId', $groups);
		while($rec = $query->fetch()){
			
			// Трябва да имат цени по избраната политика
			if(self::getPublicDisplayPrice($rec->productId, $rec->packagingId)){
				$options[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
			}
		}
		
		return $options;
	}
}