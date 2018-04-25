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
class eshop_CartDetails extends eshop_Details
{
	
	
	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	public $masterKey = 'cartId';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2,plg_AlignDecimals2,plg_Modified';//eshop_Wrapper, plg_Created, plg_Modified, plg_SaveAndNew, plg_RowTools2, plg_Select, plg_AlignDecimals2';
	
	
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
	public $listFields = 'eshopProductId=Артикул в е-мага,productId,packagingId,packQuantity,packPrice=Ед.цена,amount=Сума';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'eshop,ceo';
	
	
	/**
	 * Кой може да изтрива от кошницата
	 */
	public $canRemoveexternal = 'every_one';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'eshop,ceo';
	
	
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
		parent::addFields($this);
		$this->FLD('finalPrice', 'double(decimals=2)', 'caption=Цена,input,smartCenter');
		$this->FLD('finalQuantity', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
		$this->FLD('vat', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=ДДС %,input=none');
		$this->FLD('discount', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Отстъпка,input=none');
		$this->FNC('amount', 'double(minDecimals=2)', 'caption=Сума,input,smartCenter');
		
    	$this->setDbUnique('cartId,eshopProductId,productId,packagingId');
    }
	
	
    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
    	if (!isset($rec->finalPrice) || empty($rec->finalQuantity)) return;
    
    	$rec->amount = $rec->finalPrice * $rec->finalQuantity;
    }
    
    
	/**
	 * Добавя артикул в кошницата
	 * 
	 * @param int $cartId          - кошница
	 * @param int $eshopProductId  - артикул от е-мага
	 * @param int $productId       - артикул от каталога
	 * @param double $packQuantity - к-во
	 * @param double $packPrice    - ед. цена с ДДС, във валутата от настройките или NULL
	 */
	public static function addToCart($cartId, $eshopProductId, $productId, $packQuantity, $packPrice = NULL)
	{
		expect($cartRec = eshop_Carts::fetch("#id = {$cartId} AND #state = 'draft'"));
		expect($eshopRec = eshop_Products::fetch($eshopProductId));
		expect(cat_Products::fetch($productId));
		
		expect($productRec = eshop_ProductDetails::fetch("#eshopProductId = '{$eshopProductId}' AND #productId = '{$productId}'"));
		
		$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
		$vat = ($settings->chargeVat == 'yes') ? cat_Products::getVat($productId) : NULL;
		$quantity = $packQuantity * $productRec->quantityInPack;
		
		if(!isset($packPrice)){
			if(isset($settings->listId)){
				expect($price = price_ListRules::getPrice($settings->listId, $productId, $packagingId), 'Няма цена');
				$packPrice = $price * $productRec->quantityInPack;
				if(isset($vat)){
					$packPrice *= 1 + $vat;
				}
				
				$packPrice = currency_CurrencyRates::convertAmount($packPrice, NULL, NULL, $settings->currencyId);
			}
		}
		
		$dRec = (object)array('cartId'         => $cartId, 
				              'eshopProductId' => $eshopProductId, 
				              'productId'      => $productId,
				              'packagingId'    => $productRec->packagingId,
				              'quantityInPack' => $productRec->quantityInPack,
				              'finalPrice'     => $packPrice,
							  'finalQuantity'  => $packQuantity,
							  'vat'            => $vat,
				              'quantity'       => $quantity,
		);
		
		if($exRec = self::fetch("#cartId = {$cartId} AND #eshopProductId = {$eshopProductId} AND #productId = {$productId} AND #packagingId = {$productRec->packagingId}")){
			$exRec->quantity += $dRec->quantity;
			self::save($exRec, 'quantity');
		} else {
			self::save($dRec);
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
		if(isset($fields['-external'])){
			core_RowToolbar::createIfNotExists($row->_rowTools);
			if($mvc->haveRightFor('removeexternal', $rec)){
				$removeUrl = toUrl(array('eshop_CartDetails', 'removeexternal', $rec->id), 'local');
				$row->_rowTools->addFnLink('Премахване', '', array('ef_icon' => "img/16/delete.png", 'title' => "Премахване от кошницата", 'data-cart' => $rec->cartId, "data-url" => $removeUrl, "class" => 'remove-from-cart'));
			}
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'removeexternal'){
			
			if(empty($rec->cartId)){
				$requiredRoles = 'no_one';
			} elseif(!eshop_Carts::haveRightFor('view', $rec->cartId)){
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
			$msg = 'Артикулът е премахнат от кошницата|*!';
		} else {
			$this->delete("#cartId = {$cartId}");
			$msg = 'Кошницата е изпразнена успешно|*!';
		}
		core_Statuses::newStatus($msg);
		
		// Ако заявката е по ajax
		if (Request::get('ajax_mode')) {
			cls::get('eshop_Carts')->updateMaster($cartId);
			
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
			
			// Показваме веднага и чакащите статуси
			$hitTime = Request::get('hitTime', 'int');
			$idleTime = Request::get('idleTime', 'int');
			$statusData = status_Messages::getStatusesData($hitTime, $idleTime);
			
			$res = array_merge(array($resObj, $resObj1, $resObj2, $resObj3), (array)$statusData);
				
			return $res;
		}
		
		return followRetUrl($retUrl, NULL, $msg);
	}
}