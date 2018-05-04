<?php



/**
 * Мениджър за артикул в кошница
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
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
	public $loadList = 'plg_RowTools2,plg_AlignDecimals2,plg_Modified';
	
	
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
	public $listFields = 'eshopProductId=Артикул в е-мага,productId,packagingId,packQuantity,finalPrice=Цена,amount=Сума';
	
	
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
	public $canAdd = 'every_one';
	
	
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
	function description()
	{
		$this->FLD('cartId', 'key(mvc=eshop_Carts)', 'caption=Кошница,mandatory,input=hidden,silent');
		$this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Артикул,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack,mandatory');
		$this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack|displayPrice');
		$this->FLD('quantity', 'double', 'caption=Количество,input=none');
		$this->FLD('quantityInPack', 'double', 'input=none');
		$this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=none');
		
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
    	if (empty($rec->quantity) || empty($rec->quantityInPack)) return;
    
    	$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
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
    	
    	if(isset($rec->external)){
    		Mode::set('wrapper', 'cms_page_External');
    		$lang = cms_Domains::getPublicDomain('lang');
    		core_Lg::push($lang);
    	}
    	
    	$form->FNC('displayPrice', 'double', 'caption=Цена, input=none');
    	$productOptions = eshop_ProductDetails::getAvailableProducts();
    	
    	// От наличните опции се махат тези вече в количката
    	$query = self::getQuery();
    	$query->where("#cartId = {$rec->cartId}");
    	$query->show('productId');
    	$alreadyIn = arr::extractValuesFromArray($query->fetchAll(), 'productId');
    	$productOptions = array_diff_key($productOptions, $alreadyIn);
    	
    	$form->setOptions('productId', array('' => '') + $productOptions);
    	$form->setField('eshopProductId', 'input=none');
    	
    	if(count($productOptions) == 1){
    		$form->setDefault('productId', key($productOptions));
    	}
    	
    	if(isset($rec->productId)){
    		$form->setField('packagingId', 'input');
    		$form->setField('packQuantity', 'input');
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		$form->setField('displayPrice', 'input');
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	if(isset($data->form->rec->external)){
    		$data->form->title = 'Добавяне на артикул в|* ' . mb_strtolower(eshop_Carts::getCartDisplayName());
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
    
    	if(isset($rec->packagingId)){
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    	
    		$settings = cms_Domains::getSettings();
    		if($price = eshop_ProductDetails::getPublicDisplayPrice($rec->productId, $rec->packagingId, $rec->quantityInPack)){
    			$price->price = round($price->price, 2);
    			$form->setReadOnly('displayPrice', $price->price);
    			$unit = $settings->currencyId . " " . (($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС'));
    			$form->setField('displayPrice', "unit={$unit}");
    			$form->rec->haveVat = $settings->chargeVat;
    			$form->rec->vat = cat_Products::getVat($rec->productId);
    		}
    	}
    	
    	if($form->isSubmitted()){
    		$rec->eshopProductId = eshop_ProductDetails::fetchField("#productId = {$rec->productId}", 'eshopProductId');

    		// Проверка на к-то
    		if(!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)){
    			$form->setError('packQuantity', $warning);
    		}
    		
    		if(!$form->gotErrors()){
    			if($id = eshop_CartDetails::fetchField("#cartId = {$rec->cartId} AND #eshopProductId = {$rec->eshopProductId} AND #productId = {$rec->productId} AND #packagingId = {$rec->packagingId}")){
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
    	if (!isset($rec->finalPrice) || empty($rec->quantity) || empty($rec->quantityInPack)) return;
    
    	$rec->amount = $rec->finalPrice * ($rec->quantity / $rec->quantityInPack);
    }
    
    
	/**
	 * Добавя артикул в кошницата
	 * 
	 * @param int $cartId          - кошница
	 * @param int $eshopProductId  - артикул от е-мага
	 * @param int $productId       - артикул от каталога
	 * @param double $packQuantity - к-во
	 * @param int $quantityInPack  - к-во в опаковка
	 * @param double $packPrice    - ед. цена с ДДС, във валутата от настройките или NULL
	 * @param int|NULL $domainId   - домейн
	 */
	public static function addToCart($cartId, $eshopProductId, $productId, $packagingId, $packQuantity, $quantityInPack = NULL, $packPrice = NULL, $domainId = NULL)
	{
		expect($cartRec = eshop_Carts::fetch("#id = {$cartId} AND #state = 'active'"));
		expect($eshopRec = eshop_Products::fetch($eshopProductId));
		expect(cat_Products::fetch($productId));
		
		expect($productRec = eshop_ProductDetails::fetch("#eshopProductId = '{$eshopProductId}' AND #productId = '{$productId}'"));
		
		if(empty($quantityInPack)){
			$packRec = cat_products_Packagings::getPack($productId, $packagingId);
			$quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
		}
		
		$domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
		$settings = eshop_Settings::getSettings('cms_Domains', $domainId);
		$vat = cat_Products::getVat($productId);
		$quantity = $packQuantity * $quantityInPack;
		$currencyId = isset($settings->currencyId) ? $settings->currencyId : acc_Periods::getBaseCurrencyCode();
		
		$dRec = (object)array('cartId'         => $cartId, 
				              'eshopProductId' => $eshopProductId, 
				              'productId'      => $productId,
				              'packagingId'    => $packagingId,
				              'quantityInPack' => $quantityInPack,
							  'vat'            => $vat,
				              'quantity'       => $quantity,
							  'currencyId'     => $currencyId, 
							  'haveVat'        => ($settings->chargeVat) ? $settings->chargeVat : 'yes',      
		);
		
		if($exRec = self::fetch("#cartId = {$cartId} AND #eshopProductId = {$eshopProductId} AND #productId = {$productId} AND #packagingId = {$packagingId}")){
			$exRec->quantity += $dRec->quantity;
			self::save($exRec, 'quantity');
		} else {
			self::save($dRec);
		}
	}
	
	
	/**
	 * Преди запис на документ, изчислява стойността на полето `isContable`
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $rec
	 */
	protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		$settings = cms_Domains::getSettings();
		$cartRec = eshop_Carts::fetch($rec->cartId);
		$vat = cat_Products::getVat($rec->productId);
		
		if(!isset($rec->finalPrice)){
			if(isset($settings->listId)){
				expect($price = price_ListRules::getPrice($settings->listId, $rec->productId, $rec->packagingId), 'Няма цена');
				
				$priceObject = cls::get(price_ListToCustomers)->getPriceByList($settings->listId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
				if(!empty($priceObject->discount)){
					$rec->discount = $priceObject->discount;
				}
					
				$rec->finalPrice = $price * $rec->quantityInPack;
				if($rec->haveVat == 'yes'){
					$rec->finalPrice *= 1 + $vat;
				}
		
				$rec->finalPrice = currency_CurrencyRates::convertAmount($rec->finalPrice, NULL, NULL, $rec->currencyId);
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
		if(isset($fields['-list'])){
			$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
			$row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, TRUE);
		} elseif(isset($fields['-external'])){
			core_RowToolbar::createIfNotExists($row->_rowTools);
			if($mvc->haveRightFor('removeexternal', $rec)){
				$removeUrl = toUrl(array('eshop_CartDetails', 'removeexternal', $rec->id), 'local');
				$row->_rowTools->addFnLink('Премахване', '', array('ef_icon' => "img/16/delete.png", 'title' => "Изтриване на реда", 'data-cart' => $rec->cartId, "data-url" => $removeUrl, "class" => 'remove-from-cart'));
			}
			
			$row->productId = cat_Products::getVerbal($rec->productId, 'name');
			$row->packagingId = tr(cat_UoM::getShortName($rec->packagingId));
			
			$quantity = (isset($rec->packQuantity)) ? $rec->packQuantity : 1;
			$dataUrl = toUrl(array('eshop_CartDetails', 'updateCart', $rec->id, 'cartId' => $rec->cartId), 'local');

			$minus = ht::createElement('img', array('src' => sbf('img/16/minus-black.png', ''), 'class' => 'btnDown', 'title' => 'Намяляване на количеството'));
			$plus = ht::createElement('img', array('src' => sbf('img/16/plus-black.png', ''), 'class' => 'btnUp', 'title' => 'Увеличаване на количеството'));
			$row->quantity = $minus . ht::createTextInput("product{$rec->productId}", $quantity, "size=4,class=option-quantity-input,data-quantity={$quantity},data-url='{$dataUrl}'") . $plus;
		
			$settings = cms_Domains::getSettings();
			$finalPrice = currency_CurrencyRates::convertAmount($rec->finalPrice, NULL, $rec->currencyId, $settings->currencyId);
			$row->finalPrice = $mvc->getFieldType('finalPrice')->toVerbal($finalPrice);
		}
		
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'removeexternal' || $action == 'updatecart' || $action == 'checkout' || ($action == 'add' && isset($rec))){
			if(empty($rec->cartId)){
				$requiredRoles = 'no_one';
			} elseif(!eshop_Carts::haveRightFor('viewexternal', $rec->cartId)){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * Екшън за изтриване/изпразване на кошницата
	 */
	function act_removeexternal()
	{
		$id = Request::get('id', 'int');
		$cartId = Request::get('cartId', 'int');
		$this->requireRightFor('removeexternal', (object)array('cartId' => $cartId));
		
		if(isset($id)){
			$this->delete($id);
			$msg = 'Артикулът е премахнат|*!';
		} else {
			$this->delete("#cartId = {$cartId}");
			cls::get('eshop_Carts')->updateMaster($cartId);
			eshop_Carts::delete($cartId);
			$msg = 'Кошницата е изпразнена|*!';
		}
		
		core_Statuses::newStatus($msg);
		
		// Ако заявката е по ajax
		if (Request::get('ajax_mode')) return self::getUpdateCartResponse($cartId);
		
		return followRetUrl(NULL, NULL, $msg);
	}
	
	
	/**
	 * Какво да се върне по AJAX
	 * 
	 * @param stdClass $cartId
	 * @return stdClass $res
	 */
	private static function getUpdateCartResponse($cartId)
	{
		cls::get('eshop_Carts')->updateMaster($cartId);
		$lang = cms_Domains::getPublicDomain('lang');
		core_Lg::push($lang);
		
		// Ще реплейснем само бележката
		$resObj = new stdClass();
		$resObj->func = "html";
		$resObj->arg = array('id' => 'cart-view-table', 'html' => eshop_Carts::renderViewCart($cartId)->getContent(), 'replace' => TRUE);
			
		// Ще реплейснем само бележката
		$resObj1 = new stdClass();
		$resObj1->func = "html";
		$resObj1->arg = array('id' => 'cart-view-count', 'html' => eshop_Carts::renderCartSummary($cartId, TRUE)->getContent(), 'replace' => TRUE);
			
		// Ще реплейснем само бележката
		$resObj2 = new stdClass();
		$resObj2->func = "html";
		$resObj2->arg = array('id' => 'cart-view-total', 'html' => eshop_Carts::renderCartSummary($cartId)->getContent(), 'replace' => TRUE);
			
		// Ще реплейснем само бележката
		$resObj3 = new stdClass();
		$resObj3->func = "html";
		$resObj3->arg = array('id' => 'cart-view-buttons', 'html' => eshop_Carts::renderCartToolbar($cartId)->getContent(), 'replace' => TRUE);

		// Ще реплейснем само бележката
		$resObj4 = new stdClass();
		$resObj4->func = "smartCenter";

		// Показваме веднага и чакащите статуси
		$hitTime = Request::get('hitTime', 'int');
		$idleTime = Request::get('idleTime', 'int');
		$statusData = status_Messages::getStatusesData($hitTime, $idleTime);
			
		$res = array_merge(array($resObj, $resObj1, $resObj2, $resObj3, $resObj4), (array)$statusData);
		core_Lg::pop();
		
		return $res;
	}
	
	
	/**
	 * Екшън за изтриване/изпразване на кошницата
	 */
	function act_updateCart()
	{
		$id = Request::get('id', 'int');
		$cartId = Request::get('cartId', 'int');
		$quantity = Request::get('packQuantity', 'double');
		$this->requireRightFor('updatecart', (object)array('cartId' => $cartId));
		
		$rec = self::fetch($id);
		$rec->quantity = $quantity * $rec->quantityInPack;
		self::save($rec, 'quantity');
		
		// Ако заявката е по ajax
		if (Request::get('ajax_mode')) return self::getUpdateCartResponse($cartId);
		
		return followRremoveexternaletUrl($retUrl);
	}
}
