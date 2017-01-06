<?php



/**
 * Клас 'batch_plg_InventoryNotes' - Добавяне на партиди към протокол за инвентаризация
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_InventoryNotes extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->FLD('batch', 'varchar', 'input=none');
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
		$masterRec = $data->masterRec;
		
		// Ако има артикул
		if(isset($rec->productId)){
			$Def = batch_Defs::getBatchDef($rec->productId);
			if(!$Def) return;
			
			$quantities = batch_Items::getBatchQuantitiesInStore($rec->productId, $masterRec->storeId, $masterRec->valior);
			if(!empty($rec->batch) && !array_key_exists($rec->batch, $quantities)){
				$selected = $Def->makeArray($rec->batch);
				foreach ($selected as $b){
					if(!array_key_exists($b, $quantities)){
						$quantities[$b] = 0;
					}
				}
			}
			
			$form->FNC('batchEx', 'varchar', 'caption=Партида');
			
			$autohide = count($quantities) ? 'autohide' : '';
			$caption = ($Def->getFieldCaption()) ? $Def->getFieldCaption() : 'Партида';
			$form->FNC('batchNew', 'varchar', "caption=Установена нова партида->{$caption},input,placeholder={$Def->placeholder},{$autohide}");
			$form->setFieldType('batchNew', $Def->getBatchClassType());
			
			if(count($quantities)){
				$batches = array_keys($quantities);
				$form->setField('batchEx', 'input');
				
				if($Def instanceof batch_definitions_Serial){
					$imploded = implode(',', $batches);
					$Set = core_Type::getByName("set({$imploded})");
					$form->setFieldType('batchEx', $Set);
					
					if(isset($rec->batch)){
						$selected = $Def->makeArray($rec->batch);
						$form->setDefault('batchEx', $selected);
					}
				} else {
					$batches = arr::make($batches, TRUE);
					$form->setOptions('batchEx', array('' => '') + $batches);
					if(!empty($rec->batch)){
						$form->setDefault('batchEx', $rec->batch);
					}
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterAfterInputEditForm($mvc, &$form)
	{
		if($form->isSubmitted()){
			$rec = $form->rec;
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			
			if(!empty($rec->batchEx) && !empty($rec->batchNew)){
				$form->setError('batchNew,batchEx', 'Само едното поле може да е попълнено, или никое');
			}
			
			if(!empty($rec->batchNew)){
				$quantity = $rec->quantity;
				
				// Трябва да е валидна
				if(!$BatchClass->isValid($rec->batchNew, $quantity, $msg)){
					$form->setError('batchNew', $msg);
				}
			}
			
			if(!empty($rec->batchEx)){
				if($BatchClass instanceof batch_definitions_Serial){
					$batches = type_Set::toArray($rec->batchEx);
					$quantity = $rec->packQuantity * $rec->quantityInPack;
					
					if($quantity != count($batches)){
						$Double = cls::get('type_Double', array('params' => array('smartRound' => TRUE)));
						$form->setError('batchEx', "Броя на избраните серийни номера не отговаря на въведеното количество от|* {$Double->toVerbal($quantity)} |бр.|*");
					}
					
					$rec->batchEx = implode('|', $batches);
				}
			}
			
			if(!$form->gotErrors()){
				$rec->batch = (!empty($rec->batchEx)) ? $rec->batchEx : $rec->batchNew;
				if($rec->batch === ''){
					$rec->batch = NULL;
				}
			}
		}
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		setIfNot($mvc->hideListFieldsIfEmpty, 'batch');
		arr::placeInAssocArray($data->listFields, array('batch' => 'Партида'), 'packQuantity');
		
		$data->listTableMvc->setField('batch', 'smartCenter');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		if(empty($rec->batch)) return;
		
		$Def = batch_Defs::getBatchDef($rec->productId);
		if(!$Def) return;
		
		$batches = $Def->makeArray($rec->batch);
		
		foreach ($batches as $key => $b){
			if(!Mode::isReadOnly() && haveRole('powerUser')){
				if(!haveRole('batch,ceo')){
					Request::setProtected('batch');
				}
				
				$batches[$key] = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $key));
			}
		}
		
		if(count($batches) > 1){
			$row->batch = implode("<br>", $batches);
		} else {
			$row->batch = $batches[key($batches)];
		}
	}
	
	
	public static function on_AfterExpandRows($mvc, &$res, &$summaryRecs, &$summaryRows, $masterRec)
	{
		
		return;
		
		if(!count($summaryRows)) return;
		
		$storeId = $masterRec->storeId;
		$valior = $masterRec->valior;
		
		$res = array();
		$recs = array();
		foreach ($summaryRows as $id => $sRow){
			$sRec = $summaryRecs[$id];
			$Def = batch_Defs::getBatchDef($sRec->productId);
			if(!$Def) continue;
			
			$batchesInDetail = array();
			$query = $mvc->getQuery();
			$query->where("#noteId = {$sRec->noteId} AND #productId = {$sRec->productId}");
			while($rec = $query->fetch()){
				if(!empty($rec->batch)){
					$batches = $Def->makeArray($rec->batch);
					$quantity = $rec->quantity / count($batches);
					
					foreach ($batches as $k => $v){
						if(!array_key_exists($k, $batchesInDetail)){
							$batchesInDetail[$k] = (object)array('quantity' => 0, 'batch' => $v);
						}
						$batchesInDetail[$k]->quantity += $quantity;
					}
					
					
					//bp($rec->batch, $batches);
				}
			}
			
			if(count($batchesInDetail)){
				$allBatches = batch_Items::getBatchQuantitiesInStore($sRec->productId, $storeId, $valior);
				
				if(count($allBatches)){
					bp($allBatches, $batchesInDetail);
				}
				
				
				//echo "<pre>";
				//print_r($batchesInDetail);
				//echo "/<pre>";
				
			}
			
			
			$res[$id] = $sRow;
			$recs[$id] = $sRec;
		}
		//bp($batchesInDetail);
		$summaryRecs = $recs;
		$summaryRows = $res;
	}
}