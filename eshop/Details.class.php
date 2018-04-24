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
abstract class eshop_Details extends core_Detail
{
	
	
	protected static function addFields(&$mvc)
	{
		$mvc->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');
		$mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=eshop_ProductDetails::getSellableProducts)', 'caption=Артикул,silent,removeAndRefreshForm=packagingId,mandatory');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack');
		$mvc->FLD('quantity', 'double', 'caption=Количество,input=none');
		$mvc->FLD('quantityInPack', 'double', 'input=none');
		$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=none,smartCenter');
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
			$row->productId = cat_Products::getVerbal($rec->productId, 'name');
			$row->code = cat_products::getVerbal($rec->productId, 'code');
			$row->packagingId = cat_UoM::getShortName($rec->packagingId);
				
			$quantity = (isset($rec->packQuantity)) ? $rec->packQuantity : 1;
			if($mvc instanceof eshop_ProductDetails){
				$value = NULL;
				$placeholder = $quantity;
			} else {
				$value = $quantity;
				$placeholder = NULL;
			}
			$row->quantity = ht::createTextInput("product{$row->code}", $value, "size=4,class=option-quantity-input,placeholder={$placeholder}");
		}
		
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
	}
}