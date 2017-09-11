<?php



/**
 * Клас 'store_plg_TransportDataDetail' добавящ транспортната информация на детайл на складов документ
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_plg_TransportDataDetail extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->weightField, 'weight');
		setIfNot($mvc->volumeField, 'volume');
		setIfNot($mvc->productFld, 'productId');
		setIfNot($mvc->packagingFld, 'packagingId');
		setIfNot($mvc->quantityFld, 'quantity');
		
		$mvc->FLD($mvc->weightField, 'cat_type_Weight', 'input=none,caption=Транспортнa информация->Тегло,forceField,autohide');
		$mvc->FLD($mvc->volumeField, 'cat_type_Volume', 'input=none,caption=Транспортнa информация->Обем,forceField,autohide');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = &$form->rec;
		
		if(isset($rec->{$mvc->productFld})){
			
			// Ако артикула е складируем, показват се полетата за тегло/обем
			$isStorable = cat_Products::fetchField($rec->{$mvc->productFld}, 'canStore');
    		if($isStorable == 'yes'){
    			$form->setField('weight', 'input');
    			$form->setField('volume', 'input');
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
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		// Показване на транспортното тегло/обем ако няма, се показва 'live'
		$row->weight = deals_Helper::getWeightRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->weightField});
		$row->volume = deals_Helper::getVolumeRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->volumeField});
	}
	
	
	/**
	 * Изчисляване на общото тегло и обем на редовете
	 * 
	 * @param core_Mvc $mvc
	 * @param stdClass $res
	 * 			- weight - теглото на реда
	 * 			- volume - теглото на реда
	 * @param int $masterId
	 * @param boolean $force
	 */
	public static function on_AfterGetTransportInfo($mvc, &$res, $masterId, $force = FALSE)
	{
		$cWeight = $cVolume = 0;
		$query  = $mvc->getQuery();
		$query->where("#{$mvc->masterKey} = {$masterId}");
			
		// За всеки запис
		while($rec = $query->fetch()){
			
			// Изчислява се теглото
			$w = $mvc->getWeight($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->weightField});
			
			// Форсира се при нужда
			if($force === TRUE && empty($rec->{$mvc->weightField}) && !empty($w)){
				$clone = clone $rec;
				$clone->{$mvc->weightField} = $w;
				$mvc->save_($clone, $mvc->weightField);
			}
			
			// Сумира се
			if(!empty($w) && !is_null($cWeight)){
				$cWeight += $w;
			} else {
				$cWeight = NULL;
			}
			
			// Изчислява се обема
			$v = $mvc->getVolume($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->volumeField});			
			
			// Форсира се при нужда
			if($force === TRUE && empty($rec->{$mvc->volumeField}) && !empty($v)){
				$clone = clone $rec;
				$clone->{$mvc->volumeField} = $v;
				$mvc->save_($clone, $mvc->volumeField);
			}
			
			// Сумира се
			if(!empty($v) && !is_null($cVolume)){
				$cVolume += $v;
			} else {
				$cVolume = NULL;
			}
		}
			
		// Връщане на обема и теглото
		$weight = (!empty($cWeight)) ? $cWeight : NULL;
		$volume = (!empty($cVolume)) ? $cVolume : NULL;
		
		$res = (object)array('weight' => $weight, 'volume' => $volume);
	}
	
	
	/**
	 * Връща теглото на реда, ако няма изчислява го на момента
	 * 
	 * @param core_Mvc $mvc
	 * @param double|NULL $res
	 * @param int $productId
	 * @param int $packagingId
	 * @param double $quantity
	 * @param double|NULL $weight
	 */
	public function on_AfterGetWeight($mvc, &$res, $productId, $packagingId, $quantity, $weight = NULL)
	{
		if(!isset($weight)){
			$weight = cat_Products::getWeight($productId, $packagingId, $quantity);
			$weight = deals_Helper::roundPrice($weight, 3);
		}
		
		$res = $weight;
	}
	
	
	/**
	 * Връща обема на реда, ако няма изчислява го на момента
	 *
	 * @param core_Mvc $mvc
	 * @param double|NULL $res
	 * @param int $productId
	 * @param int $packagingId
	 * @param double $quantity
	 * @param double|NULL $weight
	 */
	public function on_AfterGetVolume($mvc, &$res, $productId, $packagingId, $quantity, $volume = NULL)
	{
		if(!isset($volume)){
			$volume = cat_Products::getVolume($productId, $packagingId, $quantity);
			$volume = deals_Helper::roundPrice($volume, 3);
		}
		
		$res = $volume;
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		$masterRec = $data->masterData->rec;
		
		if(!empty($masterRec->weightInput) && $masterRec->weightInput != $masterRec->calcedWeight){
			unset($data->listFields['weight']);
		}
		
		if(!empty($masterRec->volumeInput) && $masterRec->volumeInput != $masterRec->calcedVolume){
			unset($data->listFields['volume']);
		}
	}
}