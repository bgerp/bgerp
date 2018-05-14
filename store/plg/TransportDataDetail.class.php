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
		$mvc->declareInterface('store_iface_DetailsTransportData');
		setIfNot($mvc->weightField, 'weight');
		setIfNot($mvc->volumeField, 'volume');
		setIfNot($mvc->productFld, 'productId');
		setIfNot($mvc->packagingFld, 'packagingId');
		setIfNot($mvc->quantityFld, 'quantity');
		
		$mvc->FLD($mvc->weightField, 'cat_type_Weight', 'input=none,caption=Логистична информация->Тегло,forceField,autohide');
		$mvc->FLD($mvc->volumeField, 'cat_type_Volume', 'input=none,caption=Логистична информация->Обем,forceField,autohide');
		$mvc->FLD('transUnitId', 'key(mvc=trans_TransportUnits,select=name,allowEmpty)', "caption=Логистична информация->Единици,forceField,autohide,smartCenter,after={$mvc->volumeField},input=none");
		$mvc->FLD('transUnitQuantity', 'int', 'caption=-,autohide,inlineTo=transUnitId,forceField,unit=бр.,input=none');
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
    			$form->setField('transUnitId', 'input');
    			$form->setField('transUnitQuantity', 'input');
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
	
		// Показване на ЛЕ на реда, ако ако не е зададена същата такава от потребителя
		$masterInputUnits = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'transUnitsInput');
		$masterInputUnits = is_array($masterInputUnits) ? $masterInputUnits : array();
		$transUnitId = isset($rec->transUnitId) ? $rec->transUnitId : trans_TransportUnits::fetchIdByName('load');
		if(!array_key_exists($transUnitId, $masterInputUnits)){
			$row->transUnitId = trans_TransportUnits::display($rec->transUnitId, $rec->transUnitQuantity);
		} else {
			unset($row->transUnitId);
		}
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
		$masterId = $mvc->Master->fetchRec($masterId)->id;
		$cWeight = $cVolume = 0;
		$query  = $mvc->getQuery();
		$query->where("#{$mvc->masterKey} = {$masterId}");
		$units = array();
		
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
			
			$unitId = (!empty($rec->transUnitId)) ? $rec->transUnitId : trans_TransportUnits::fetchIdByName('load');
			$uQuantity = (!empty($rec->transUnitQuantity)) ? $rec->transUnitQuantity : 1;
			
			$units[$unitId] += $uQuantity;
		}
			
		// Връщане на обема и теглото
		$weight = (!empty($cWeight)) ? $cWeight : NULL;
		$volume = (!empty($cVolume)) ? $cVolume : NULL;
		
		$res = (object)array('weight' => $weight, 'volume' => $volume, 'transUnits' => $units);
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
			$weight = cat_Products::getTransportWeight($productId, $quantity);
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
			$volume = cat_Products::getTransportVolume($productId, $quantity);
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
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		if($form->isSubmitted()){
			if(!empty($rec->transUnitId) && empty($rec->transUnitQuantity)){
				$form->setError('transUnitId,transUnitQuantity', 'Трябва да е попълнено к-то на ЛЕ');
			} elseif(empty($rec->transUnitId) && !empty($rec->transUnitQuantity)){
				$form->setError('transUnitId,transUnitQuantity', 'Липсва логистична еденица');
			}
		}
	}
	
	
	/**
	 * Какви са използваните ЛЕ
	 * 
	 * @param core_Mvc $mvc       - документ
	 * @param array $res          - масив с резултати
	 * @param stdClass $masterRec - ид на мастъра
	 * @return void               
	 */
	public static function on_AfterGetTransUnits($mvc, &$res, $masterRec)
	{
		if(!empty($res)) return;
		 
		$res = array();
		$dQuery = $mvc->getQuery();
		$dQuery->where("#{$mvc->masterKey} = {$masterRec->id}");
		$dQuery->show('transUnitId,transUnitQuantity');
		 
		while($dRec = $dQuery->fetch()){
			if(!empty($dRec->transUnitId)){
				$res[$dRec->transUnitId] += $dRec->transUnitQuantity;
			} else {
				$defUnitId = trans_TransportUnits::fetchIdByName('load');
				$res[$defUnitId] += 1;
			}
		}
		 
		return $res;
	}
}