<?php



/**
 * Плъгин позволяващ промяна на редовете на детайлите при клониране
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_EditClonedDetails extends core_Plugin
{
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		if($data->action != 'clone') return;
		$form = &$data->form;
		$rec = $form->rec;
		if(!$rec->clonedFromId) return;
		
		$Detail = cls::get($mvc->mainDetail);
		$detailId = $Detail->getClassId();
		$dQuery = $Detail->getQuery();
		$dQuery->where("#{$Detail->masterKey} = {$rec->clonedFromId}");
		
		$rec->details = array();
		
		// Ако ориджина има артикули
		$num = 1;
		while($dRec = $dQuery->fetch()){
			$caption = cat_Products::getTitleById($dRec->{$Detail->productFld});
			$caption .= " / " . cat_UoM::getShortName($dRec->packagingId);
			$caption= str_replace(',', ' ', $caption);
			$caption = "{$num}. {$caption}";
				
			$Def = batch_Defs::getBatchDef($dRec->productId);
			$subCaption = 'К-во';
			
			// Ако е инсталиран пакета за партиди, ще се показват и те
			if(core_Packs::isInstalled('batch') && is_object($Def)){
				$subCaption = 'Без партида';
				$bQuery = batch_BatchesInDocuments::getQuery();
				$bQuery->where("#detailClassId = {$detailId} AND #detailRecId = {$dRec->id} AND #productId = {$dRec->productId}");
				$bQuery->orderBy('id', "DESC");
				if(!array_key_exists($dRec->id, $rec->details)){
					$rec->details[$dRec->id] = $dRec;
				}
				$rec->details[$dRec->id]->newPackQuantity = 0;
				
				$quantity = $dRec->quantity / $dRec->quantityInPack;
				while($bRec = $bQuery->fetch()){
					$verbal = strip_tags($Def->toVerbal($bRec->batch));
					$b = str_replace(',', '', $bRec->batch);
					$b = str_replace('.', '', $b);
					$bQuantity = $bRec->quantity / $bRec->quantityInPack;
					$quantity -= $bQuantity;
					
					$max = ($Def instanceof batch_definitions_Serial) ? 'max=1' : '';
					$key = "quantity|{$b}|{$dRec->id}|";
					$form->FLD($key, "double(Min=0,{$max})","input,caption={$caption}->|*{$verbal}");
					
					$rec->details[$dRec->id]->batches[$bRec->id] = $bRec;
					$form->setDefault($key, $bQuantity);
				}
				
				// Показване на полетата без партиди
				$form->FLD("quantity||{$dRec->id}|", "double(Min=0)","input,caption={$caption}->{$subCaption}");
				
				if(!empty($quantity)){
					$form->setDefault("quantity||{$dRec->id}|", $quantity);
				}
			} else {
				// Показване на полетата без партиди
				$form->FLD("quantity||{$dRec->id}|", "double(Min=0)","input,caption={$caption}->Количество");
				$form->setDefault("quantity||{$dRec->id}|", $dRec->packQuantity);
				$rec->details["quantity||{$dRec->id}|"] = $dRec;
			}
			
			$rec->cloneAndChange = TRUE;
			$num++;
		}
		
		$clonedState = $mvc->fetchField($rec->clonedFromId, 'state');
		
		if($clonedState == 'pending'){
			$form->FLD("deduct", "enum(yes=Да,no=Не)","input,caption=Приспадане от заявката->Избор,formOrder=10000");
		}
		
		// Показване на оригиналния документ
		$data->form->layout = $data->form->renderLayout();
		$tpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Оригинален документ") . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div>");
		$document = doc_Containers::getDocument($mvc->fetchField($rec->clonedFromId, 'containerId'));
		$docHtml = $document->getInlineDocumentBody();
		$tpl->append($docHtml, 'DOCUMENT');
		$data->form->layout->append($tpl);
	}
	
	
	/**
	 * Преди запис на клонираните детайли
	 */
	public static function on_BeforeSaveCloneDetails($mvc, &$newRec, &$detailArray)
	{
		if($newRec->cloneAndChange){
			
			// Занулява се за да не се клонира нищо
			$detailArray = array();
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = NULL)
	{
		$fields = (array)$rec;
		$Detail = cls::get($mvc->mainDetail);
		$detailClassId = $Detail->getClassId();
		
		if(count($rec->details)){
			foreach ($rec->details as $det){
				$newPackQuantity = 0;
				if(is_array($det->batches)){
					foreach ($det->batches as $index => &$bRec){
						$b = str_replace(',', '', $bRec->batch);
						$b = str_replace('.', '', $b);
						$key = "quantity|{$b}|{$det->id}|";
						
						if(empty($rec->{$key})) {
							unset($det->batches[$index]);
							continue;
						}
						$newPackQuantity += $rec->{$key};
						$bRec->oldQuantity = $bRec->quantity;
						$bRec->quantity = $rec->{$key} * $bRec->quantityInPack;
						$bRec->containerId = $rec->containerId;
						$bRec->storeId = $rec->storeId;
					}
				}
				$newPackQuantity += $rec->{"quantity||{$det->id}|"};
				if(!empty($newPackQuantity)){
					$oldQuantity = $det->quantity;
					$det->quantity = $newPackQuantity * $det->quantityInPack;
					$diff = $oldQuantity - $det->quantity;
					$oldDetailId = $det->id;
					
					if($rec->deduct == 'yes'){
						if($diff <= 0){
							$Detail->delete($det->id);
							batch_BatchesInDocuments::delete("#detailClassId = {$detailClassId} AND #detailRecId = {$det->id}");
						} else {
							$updateRec = (object)array('id' => $oldDetailId, 'quantity' => $diff);
							$Detail->save_($updateRec, 'quantity');
						}
					}
					unset($det->id, $det->createdOn, $det->createdBy);
					
					$det->autoAllocate = FALSE;
					$det->{$Detail->masterKey} = $rec->id;
					$Detail->save($det);
					if(is_array($det->batches)){
						$batchesArr = array();
						foreach ($det->batches as $batch){
							$d1 = $batch->oldQuantity - $batch->quantity;
							if($rec->deduct == 'yes'){
								if($d1 <= 0){
									batch_BatchesInDocuments::delete("#id = {$batch->id}");
								} else {
									$updateRec = (object)array('id' => $batch->id, 'quantity' => $d1);
									cls::get('batch_BatchesInDocuments')->save_($updateRec, 'quantity');
								}
							}
							
							$batchesArr[$batch->batch] = $batch->quantity;
						}
						batch_BatchesInDocuments::saveBatches($detailClassId, $det->id, $batchesArr);
					}
				}
			}
		}
	}
}