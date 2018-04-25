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
	
	
	/**
	 * Доабвяне на полета
	 */
	protected static function addFields(&$mvc)
	{
		$mvc->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');
		$mvc->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Артикул,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack,mandatory');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack');
		$mvc->FLD('quantity', 'double', 'caption=Количество,input=none');
		$mvc->FLD('quantityInPack', 'double', 'input=none');
		$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=none');
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
		
		if(Request::get('external')){
			Mode::set('wrapper', 'cms_page_External');
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
				$dataUrl = '';
				$class = 'eshop-product-option';
			} else {
				$value = $quantity;
				$placeholder = NULL;
				$dataUrl = toUrl(array('eshop_CartDetails', 'updateCart', $rec->id, 'cartId' => $rec->{$mvc->masterKey}), 'local');
				$dataCartId = $rec->{$mvc->masterKey};
				$class = 'option-quantity-input';
			}
			$row->quantity = ht::createTextInput("product{$rec->productId}", $value, "size=4,class={$class},placeholder={$placeholder},data-quantity={$quantity},data-url='{$dataUrl}'");
		}
		
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
	}
}