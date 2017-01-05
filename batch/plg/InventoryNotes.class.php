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
		$mvc->FLD('batches', 'blob(serialize, compress)', 'input=none');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		
		// Ако има артикул
		if(isset($rec->productId)){
			
			
			return;
			// Зареждане на класа на партидата
			expect($Def = batch_Defs::getBatchDef($rec->productId));
			
			$quantities = batch_Items::getBatchQuantitiesInStore($rec->productId, $masterRec->storeId, $masterRec->valior);
			$rec->data = (is_array($rec->data)) ? $rec->data : array();
			$quantities += $rec->data;
			
			$form->counts = array();
			
			if(is_array($quantities)){
				$count = 1;
				foreach ($quantities as $batch => $quantity){
					$form->counts[] = $count;
					$verbal = strip_tags($Def->toVerbal($batch));
					$form->FLD("quantity{$count}", "double", "caption=Установени партиди->{$verbal},unit={$packName}");
					if(array_key_exists($batch, $rec->data)){
						$form->setDefault("quantity{$count}", $rec->data["{$batch}"] / $rec->quantityInPack);
					}
					$form->FLD("batch{$count}", 'varchar', "input=hidden");
					$form->setDefault("batch{$count}", $batch);
					$count++;
				}
			}
			
			// Добавяне на поле за нова партида
			$autohide = count($quantities) ? 'autohide' : '';
			$caption = ($Def->getFieldCaption()) ? $Def->getFieldCaption() : 'Партида';
			$form->FLD('newBatch', 'varchar', "caption=Установена нова партиди->{$caption},placeholder={$Def->placeholder},{$autohide}");
			$form->setFieldType('newBatch', $Def->getBatchClassType());
			
			// Ако е сериен номер полето за к-во се скрива
			if(!($Def instanceof batch_definitions_Serial)){
				$form->FLD('newBatchQuantity', 'double(min=0)', "caption=Установена нова партиди->К-во,placeholder={$Def->placeholder},unit={$packName},{$autohide}");
			}
		}
	}
}