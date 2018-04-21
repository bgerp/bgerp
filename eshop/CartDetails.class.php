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
	public $loadList = 'plg_RowTools2,plg_AlignDecimals2';//eshop_Wrapper, plg_Created, plg_Modified, plg_SaveAndNew, plg_RowTools2, plg_Select, plg_AlignDecimals2';
	
	
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
		$this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');
		$this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=eshop_ProductDetails::getSellableProducts)', 'caption=Артикул,silent,removeAndRefreshForm=packagingId,mandatory');
		$this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack');
		
		$this->FLD('quantity', 'double', 'caption=Количество,input=none');
    	$this->FLD('quantityInPack', 'double', 'input=none');
    	$this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=none,smartCenter');
    	$this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
    	$this->FLD('price', 'double', 'caption=Цена,input=none');
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
    	if (!isset($rec->price) || empty($rec->quantity)) return;
    
    	$rec->amount = $rec->price * $rec->quantity;
    }
    
    
	/**
	 * Изчисляване на цена за опаковка на реда
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
	{
		if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) return;
	
		$rec->packPrice = $rec->price * $rec->quantityInPack;
	}
	
	
	/**
	 * Изчисляване на количеството на реда в брой опаковки
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
	{
		if (empty($rec->quantity) || empty($rec->quantityInPack)) return;
	
		$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
	}
	
	
	/**
	 * Добавя артикул в кошницата
	 * 
	 * @param int $cartId          - кошница
	 * @param int $eshopProductId  - артикул от е-мага
	 * @param int $productId       - артикул от каталога
	 * @param double $packQuantity - к-во
	 * @param double $packPrice    - ед. цена с ДДС, във валутата от настройките
	 */
	public static function addToCart($cartId, $eshopProductId, $productId, $packQuantity, $packPrice)
	{
		expect($cartRec = eshop_Carts::fetch("#id = {$cartId} AND #state = 'draft'"));
		expect($eshopRec = eshop_Products::fetch($eshopProductId));
		expect(cat_Products::fetch($productId));
		expect(cat_UoM::fetch($productId));
		
		expect($productRec = eshop_ProductDetails::fetch("#eshopProductId = '{$eshopProductId}' AND #productId = '{$productId}'"));
		
		$dRec = (object)array('cartId'         => $cartId, 
				              'eshopProductId' => $eshopProductId, 
				              'productId'      => $productId,
				              'packagingId'    => $productRec->packagingId,
				              'quantityInPack' => $productRec->quantityInPack,
				              'price'          => $packPrice / $productRec->quantityInPack,
				              'quantity'       => $packQuantity * $productRec->quantityInPack,
		);
		
		if($exRec = self::fetch("#cartId = {$cartId} AND #eshopProductId = {$eshopProductId} AND #productId = {$productId} AND #packagingId = {$productRec->packagingId}")){
			$exRec->quantity += $dRec->quantity;
			self::save($exRec, 'quantity');
		} else {
			self::save($dRec);
		}
	}
}