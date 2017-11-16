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
	 * Кои детайли да се клонират с промяна
	 *
	 * @param stdClass $rec
	 * @param mixed $Detail
	 * @return array
	 */
	public static function on_AfterGetDetailsToCloneAndChange($mvc, &$res, $rec, &$Detail = NULL)
	{
		if(!$res){
			$res = array();
			if(!$rec->clonedFromId) return;
			
			$Detail = cls::get($mvc->mainDetail);
			$dQuery = $Detail->getQuery();
			$dQuery->where("#{$Detail->masterKey} = {$rec->clonedFromId}");
			$res = $dQuery->fetchAll();
		}
	}
	
	
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
		
		$Detail = NULL;
		$detailsToClone = $mvc->getDetailsToCloneAndChange($rec, $Detail);
		setIfNot($Detail, cls::get($mvc->mainDetail));
		if(!count($detailsToClone)) return;
		setIfNot($Detail->productFld, 'productId');
		setIfNot($Detail->quantityFld, 'quantity');
		
		$rec->details = array();
		
		// Ако ориджина има артикули
		$num = 1;
		$detailId = $Detail->getClassId();
		$installedBatch = core_Packs::isInstalled('batch');
		
		foreach ($detailsToClone as $dRec){
			$caption = cat_Products::getTitleById($dRec->{$Detail->productFld});
			$caption .= " / " . cat_UoM::getShortName($dRec->packagingId);
			$caption= str_replace(',', ' ', $caption);
			$caption = "{$num}. {$caption}";
			
			if($installedBatch !== FALSE){
				$Def = batch_Defs::getBatchDef($dRec->{$Detail->productFld});
			}
			
			$subCaption = 'К-во';
			
			// Ако е инсталиран пакета за партиди, ще се показват и те
			if($installedBatch && is_object($Def) && $dRec->noBatches !== TRUE){
				$subCaption = 'Без партида';
				$bQuery = batch_BatchesInDocuments::getQuery();
				$bQuery->where("#detailClassId = {$detailId} AND #detailRecId = {$dRec->id} AND #productId = {$dRec->{$Detail->productFld}}");
				$bQuery->groupBy('batch');
				$bQuery->orderBy('id', "DESC");
				
				if(!array_key_exists($dRec->id, $rec->details)){
					$rec->details[$dRec->id] = $dRec;
				}
				$rec->details[$dRec->id]->newPackQuantity = 0;
				
				$quantity = $dRec->{$Detail->quantityFld} / $dRec->quantityInPack;
				while($bRec = $bQuery->fetch()){
					$verbal = strip_tags($Def->toVerbal($bRec->batch));
					$b = str_replace(',', '', $bRec->batch);
					$b = str_replace('.', '', $b);
					
					$bQuantity = $bRec->{$Detail->quantityFld} / $bRec->quantityInPack;
					$quantity -= $bQuantity;
					
					$max = ($Def instanceof batch_definitions_Serial) ? 'max=1' : '';
					$key = "quantity|{$b}|{$dRec->id}|";
					$form->FLD($key, "double(Min=0,{$max})","input,caption={$caption}->|*{$verbal}");
					
					$rec->details[$dRec->id]->batches[$bRec->id] = $bRec;
					$form->setDefault($key, $bQuantity);
				}
				
				// Показване на полетата без партиди
				$form->FLD("quantity||{$dRec->id}|", "double(Min=0)","input,caption={$caption}->{$subCaption}");
				
				if($quantity > 0){
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
		
		if(!isset($rec->clonedFromId)) return;
		
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
				$newPackQuantity = $updatePackQuantity = 0;
				if(is_array($det->batches) && core_Packs::isInstalled('batch')){
					foreach ($det->batches as $index => &$bRec){
						$b = str_replace(',', '', $bRec->batch);
						$b = str_replace('.', '', $b);
						$key = "quantity|{$b}|{$det->id}|";
						
						
						$q = $rec->{$key};
						if($q > ($bRec->quantity / $bRec->quantityInPack)){
							$q = $bRec->quantity / $bRec->quantityInPack;
						}
						$updatePackQuantity += $q;
						
						$newPackQuantity += $rec->{$key};
						$bRec->oldQuantity = $bRec->quantity;
						$bRec->quantity = $rec->{$key} * $bRec->quantityInPack;
						$bRec->containerId = $rec->containerId;
						$bRec->storeId = $rec->storeId;
					}
				}
				$newPackQuantity += $rec->{"quantity||{$det->id}|"};
				$updatePackQuantity += $rec->{"quantity||{$det->id}|"};
				if(!empty($newPackQuantity)){
					$oldQuantity = $det->quantity;
					$det->quantity = $newPackQuantity * $det->quantityInPack;
					$diff = $oldQuantity - $det->quantity;
					$oldDetailId = $det->id;
					$det->_clonedWithBatches = TRUE;
					
					if($rec->deduct == 'yes'){
						if($diff <= 0){
							$Detail->delete($det->id);
							if(core_Packs::isInstalled('batch')){
								batch_BatchesInDocuments::delete("#detailClassId = {$detailClassId} AND #detailRecId = {$det->id}");
							}
						} else {
							$diff1 = $oldQuantity - ($updatePackQuantity * $det->quantityInPack);
							$updateRec = (object)array('id' => $oldDetailId, 'quantity' => $diff1);
							$Detail->save_($updateRec, 'quantity');
						}
					}
					unset($det->id, $det->createdOn, $det->createdBy);
					
					
					$det->{$Detail->masterKey} = $rec->id;
					$Detail->save($det);
					if(is_array($det->batches) && core_Packs::isInstalled('batch')){
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
							
							if(!empty($batch->quantity)){
								$batchesArr[$batch->batch] = $batch->quantity;
							}
						}
						batch_BatchesInDocuments::saveBatches($detailClassId, $det->id, $batchesArr);
					}
				}
			}
		}
	}
}