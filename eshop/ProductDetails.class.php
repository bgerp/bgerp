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
class eshop_ProductDetails extends eshop_Details
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
	public $listFields = 'eshopProductId=Артикул в е-мага,productId,packagingId,packQuantity,catalogPrice,modifiedOn,modifiedBy';
	
	
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
		parent::addFields($this);
    	$this->FNC('catalogPrice', 'double(decimals=2)', 'caption=Цена,input=none,smartCenter');
    	
		$this->setDbUnique('eshopProductId,productId,packagingId');
	}
		
     
	/**
     * Връща достъпните продаваеми артикули
     *
     * @param array $params
     * @param NULL|integer $limit
     * @param string $q
     * @param NULL|integer|array $onlyIds
     * @param boolean $includeHiddens
     *
     * @return array
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
			$form->setField('packagingId', 'input');
			$form->setField('packQuantity', 'input');
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
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
			
			// Проверка на к-то
			if(!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)){
				$form->setError('packQuantity', $warning);
			}
			
			// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
			if(!$form->gotErrors()){
				$productInfo = cat_Products::getProductInfo($rec->productId);
				$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
				$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
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
		$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
		if(isset($settings->listId)){
			if($catalogPrice = price_ListRules::getPrice($settings->listId, $rec->productId, $rec->packagingId)){
				$catalogPrice *= $rec->quantityInPack;
				if($settings->chargeVat == 'yes'){
					$catalogPrice *= 1 + cat_Products::getVat($rec->productId);
				}
				$catalogPrice = currency_CurrencyRates::convertAmount($catalogPrice, NULL, NULL, $settings->currencyId);
				
				$priceVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($catalogPrice);
				$row->catalogPrice = "<span class='option-price'>" . $priceVerbal . "</span>";
			}
		}
		
		if(isset($fields['-list'])){
			if(empty($row->catalogPrice)){
				$row->catalogPrice = ht::createHint('', 'Артикулът няма цена и няма да се показва във външната част', 'warning');
			} else {
				$row->catalogPrice .= " <span class='cCode'>" . $settings->currencyId . "</span>";
			}
		} elseif(isset($fields['-external'])){
			$addUrl = toUrl(array('eshop_Carts', 'addtocart'), 'local');
			$row->btn = ht::createFnBtn('Добави', NULL, FALSE, array('title'=> 'Добавяне в кошницата', 'ef_icon' => 'img/16/cart_go.png', 'data-url' => $addUrl, 'data-productid' => $rec->productId, 'data-eshopproductpd' => $rec->eshopProductId, 'class' => 'cart-add-product-btn'));
		}
	}
	
	
	/**
	 * Преди подготовката на полетата за листовия изглед
	 */
	protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
	{
		if(isset($data->masterMvc)){
			unset($data->listFields['eshopProductId']);
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
		
		$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
		
		$query = self::getQuery();
		$query->where("#eshopProductId = {$data->rec->id}");
		while($rec = $query->fetch()){
			if(!empty($settings->listId)){
				if($price = price_ListRules::getPrice($settings->listId, $rec->productId, $rec->packagingId)){
					$data->rows[$rec->id] = self::recToVerbal($rec, $fields);
				}
			}
		}
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
		$table = cls::get('core_TableView', array('mvc' => $fieldset, 'tableClass' => 'optionsTable'));
		$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
		
		//if($count <= 10){
			$priceHead = 'Цена|* ' . $settings->currencyId;
    		$tpl->append($table->get($data->rows, "code=Код,productId=Артикул,packagingId=Опаковка,quantity=К-во,catalogPrice={$priceHead},btn=|*&nbsp;"));
		/*} else {
			$newProducts = array();
			foreach ($data->rows as $pRow){
				$newProducts[] = strip_tags("{$pRow->code} {$pRow->productId} {$pRow->packagingId}");
			}
			//$options = array('productId' => ht::createSmartSelect($newProducts, 'selectedOption'), 'quantity' => ht::createTextInput("product{$row->code}", $moq, 'size=6,class=option-quantity-input'););
			//cms_Domains::getPublicDomain()
			
			$tpl->append($table->get($data->rows, 'productId=Артикул,quantity=К-во,btn=|*&nbsp;'));
		}*/
		
		return $tpl;
	}
}