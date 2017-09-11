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
		$mvc->FLD('batch', 'varchar', 'input=none,tdClass=nowrap,smartCenter');
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
			
			$form->notMandatoryQ = TRUE;
			if(isset($form->rec->id)){
				$form->setReadOnly('productId');
			}
			
			// Ако има налични партиди
			$valior = dt::addDays(-1, $masterRec->valior);
			$valior = dt::verbal2mysql($valior, FALSE);
			
			$quantities = batch_Items::getBatchQuantitiesInStore($rec->productId, $masterRec->storeId, $valior);
			$selected = $Def->makeArray($rec->batch);
			if(!empty($rec->batch) && !array_key_exists($rec->batch, $quantities)){
				
				foreach ($selected as $k => $b){
					if(!array_key_exists($k, $quantities)){
						$quantities[$k] = 0;
					}
				}
			}
			
			// Добавяне на поле за избор на съществуваща партида
			$form->FNC('batchEx', 'varchar', 'caption=Партида');
			$autohide = count($quantities) ? 'autohide' : '';
			$caption = ($Def->getFieldCaption()) ? $Def->getFieldCaption() : 'Партида';
			$form->FNC('batchNew', 'varchar', "caption=Установена нова партида->{$caption},input,placeholder={$Def->placeholder}");
			
			// Ако е сериен номер само едно поле се показва
			if($Def instanceof batch_definitions_Serial){
				$form->setField('batchEx', 'input=none');
				$form->setFieldType('batchNew', $Def->getBatchClassType());
				$form->setField('batchNew', 'caption=Серийни номера');
				$autohide = '';
				
				if(count($selected)){
					$batches = implode(' ', $selected);
					$form->setDefault('batchNew', $batches);
				}
				
				if(count($quantities)){
					$suggestions = array_combine(array_keys($quantities), array_keys($quantities));
					$form->setSuggestions('batchNew', $suggestions);
				}
			} else {
				
				// Иначе се добавя полето за нова партида
				$form->setFieldType('batchNew', $Def->getBatchClassType());
				
				if(count($quantities)){
					$options = array();
					foreach ($quantities as $k => $v){
						$options[$k] = strip_tags($Def->toVerbal($k));
					}
					
					$form->setField('batchEx', 'input');
					$form->setOptions('batchEx', array('' => '') + $options);
				}
				
				if(isset($rec->batch)){
					$form->setDefault('batchEx', $rec->batch);
				}
			}
			
			$form->setField('batchNew', $autohide);
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
			
			// Ако артикула е партиден
			$rec = $form->rec;
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(!$BatchClass) return;
			
			if(!empty($rec->batchEx) && !empty($rec->batchNew)){
				$form->setError('batchNew,batchEx', 'Само едното поле може да е попълнено, или никое');
			}
			
			if(!isset($rec->quantity)){
				$b = $BatchClass->normalize($rec->batchNew);
				$b = $BatchClass->makeArray($b);
				$rec->quantity = count($b);
			}
			
			if(!empty($rec->batchNew)){
				
				// Трябва да е валидна
				if(!$BatchClass->isValid($rec->batchNew, $rec->quantity, $msg)){
					$form->setError('batchNew', $msg);
				}
			}
			
			if(!$form->gotErrors()){
				$rec->batch = (!empty($rec->batchEx)) ? $rec->batchEx : $rec->batchNew;
				if($rec->batch === ''){
					$rec->batch = NULL;
				}
				
				if(is_object($BatchClass)){
					$rec->batch = $BatchClass->normalize($rec->batch);
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
	
	
	/**
	 * Взима съмарите на артикула
	 * 
	 * @param int $noteId
	 * @param int $productId
	 * @param double $expectedQuantity
	 * @param int $storeId
	 * @param date $valior
	 * @param boolean $alwaysShowBatches
	 * @return array|FALSE
	 */
	public static function getBatchSummary($noteId, $productId, $expectedQuantity, $storeId, $valior, $alwaysShowBatches = FALSE)
	{
		$Def = batch_Defs::getBatchDef($productId);
		if(!$Def) return FALSE;
		
		$batchesInDetail = array();
		
		// Извличане на всички записи
		$query = store_InventoryNoteDetails::getQuery();
		$query->where("#noteId = {$noteId} AND #productId = {$productId}");
		$count = $query->count();
		
		// Сумиране на партидите
		while($rec = $query->fetch()){
			if(!empty($rec->batch)){
				$batches = $Def->makeArray($rec->batch);
				$quantity = $rec->quantity / count($batches);
			} else {
				if($count == 1 && $alwaysShowBatches !== TRUE) return FALSE;
				$batches = array('' => '');
				$quantity = $rec->quantity;
			}
				
			foreach ($batches as $k => $v){
				if(!array_key_exists($k, $batchesInDetail)){
					$batchesInDetail[$k] = (object)array('quantity' => 0, 'batch' => $v);
				}
				$batchesInDetail[$k]->quantity += $quantity;
			}
		}
		
		// Засичане на очакваните колчества с въведените
		if($alwaysShowBatches !== TRUE){
			if(!count($batchesInDetail)) return FALSE;
		}
		
		$allBatches = batch_Items::getBatchQuantitiesInStore($productId, $storeId, $valior, NULL, array('store_InventoryNotes', $noteId));
		$allBatches[''] = $expectedQuantity - array_sum($allBatches);
		
		$summary = array();
		$combinedKeys = array_keys($batchesInDetail + $allBatches);
		
		// Засичане
		$expected = $expectedQuantity;
		foreach ($combinedKeys as $batch){
			$summary[$batch] = new stdClass();
			$summary[$batch]->blQuantity = (isset($allBatches[$batch])) ? $allBatches[$batch] : 0;
			$summary[$batch]->quantity = (isset($batchesInDetail[$batch])) ? $batchesInDetail[$batch]->quantity : 0;
			$summary[$batch]->delta = $summary[$batch]->quantity - $summary[$batch]->blQuantity;
			
			if($batch !== ''){
				$expected -= $summary[$batch]->blQuantity;
			}
		}
		
		// Без партидата отива най-отдоло
		if(isset($summary[''])){
			$noBatch = $summary[''];
			unset($summary['']);
			$summary[''] = $noBatch;
		}
		
		return $summary;
	}
	
	
	/**
	 * При разширяване на записите
	 */
	public static function on_ExpandRows($mvc, &$summaryRecs, &$summaryRows, $masterRec)
	{
		if(!count($summaryRows)) return;
		$Double = cls::get('type_Double');
		
		$storeId = $masterRec->storeId;
		$valior = dt::addDays(-1, $masterRec->valior);
		$valior = dt::verbal2mysql($valior, FALSE);
		
		$alwaysShowBatches = (Mode::is('blank') && Request::get('showBatches')) ? TRUE : FALSE;
		
		$r = array();
		$recs = array();
		foreach ($summaryRows as $id => $sRow){
			$sRec = $summaryRecs[$id];
			$recs[$id] = $sRec;
			$r[$id] = $sRow;
			
			$summary = self::getBatchSummary($sRec->noteId, $sRec->productId, $sRec->blQuantity, $storeId, $valior, $alwaysShowBatches);
			if(!is_array($summary)) continue;
			
			$Def = batch_Defs::getBatchDef($sRec->productId);
			foreach ($summary as $batch => $bRec){
				$bRec->noteId = $sRec->noteId;
				$bRec->orderCode = $sRec->orderCode;
				$bRec->verbalCode = $sRec->verbalCode;
				$bRec->orderName = $sRec->noteId;
				$bRec->groupName = $sRec->groupName;
				$bRec->isBatch = TRUE;
				
				$clone = clone $sRow;
				$productId = new core_ET("<span class='note-batch-row'><span class='note-batch-product-name'>[#product#]</span> <span class='note-batch-name'>[#batch#]</span></span>");
				$productId->replace(strip_tags($clone->productId), 'product');
				$productId->replace(($batch) ? $Def->toVerbal($batch) : tr('Без партида'), 'batch');
				$clone->productId = $productId;
				
				$clone->blQuantity = $Double->toVerbal($bRec->blQuantity);
				$clone->quantity = $Double->toVerbal($bRec->quantity);
				$clone->delta = $Double->toVerbal($bRec->delta);
				unset($clone->code);
				
				$k = "{$id}|{$batch}";
				$recs[$k] = $bRec;
				$r[$k] = $clone;
			}
		}
		
		$summaryRecs = $recs;
		$summaryRows = $r;
	}
	
	
	/**
	 * Добавя ключови думи за пълнотекстово търсене
	 */
	public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
	{
		if(isset($rec->batch) && isset($rec->productId)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(is_object($BatchClass)){
				$batches = $BatchClass->makeArray($rec->batch);
				foreach ($batches as $b){
					$res .= " " . plg_Search::normalizeText($b);
				}
			}
		}
	}
	
	
	/**
	 * След контиране на мастъра
	 */
	public static function on_AfterContoMaster($mvc, $rec)
	{
		$storeId = isset($rec->storeId) ? $rec->storeId : store_InventoryNotes::fetchField($rec->id, 'storeId');
		$valior = isset($rec->valior) ? $rec->valior : store_InventoryNotes::fetchField($rec->id, 'valior');
		$obj = (object)array('docId' => $rec->id, 'docType' => store_InventoryNotes::getClassId(), 'date' => $valior);
				
		$valior = dt::addDays(-1, $valior);
		$valior = dt::verbal2mysql($valior, FALSE);
		
		$dQuery = store_InventoryNoteSummary::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while ($dRec = $dQuery->fetch()){
			try{
				$summary = self::getBatchSummary($dRec->noteId, $dRec->productId, 0, $storeId, $valior);
				if(!is_array($summary)) continue;
		
				foreach ($summary as $batch => $o){
					if($batch == '') continue;
					if($o->delta == 0) continue;
							
					$move = clone $obj;
					$move->operation = ($o->delta < 0) ? 'out' : 'in';
					$move->quantity = abs($o->delta);
					$move->itemId = batch_Items::forceItem($dRec->productId, $batch, $storeId);
							
					// Запис на движението
					$id = batch_Movements::save($move);
							
					// Ако има проблем със записа, сетваме грешка
					if(!$id){
						$result = FALSE;
						break;
					}
				}
			} catch(core_exception_Expect $e){
		
				// Ако е изникнала грешка
				$result = FALSE;
			}
		}
				
		// При грешка изтриваме всички записи до сега
		if($result === FALSE){
			batch_Movements::removeMovement('store_InventoryNotes', $rec);
		}
	}
	
	
	/**
	 * След оттегляне на мастъра
	 */
	public static function on_AfterRejectMaster($mvc, $rec)
	{
		batch_Movements::removeMovement('store_InventoryNotes', $rec);
	}
}