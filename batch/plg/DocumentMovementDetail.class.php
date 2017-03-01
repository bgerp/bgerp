<?php



/**
 * Клас 'batch_plg_DocumentMovementDetail' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_DocumentMovementDetail extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->productFieldName, 'productId');
		setIfNot($mvc->storeFieldName, 'storeId');
		setIfNot($mvc->batchMovementDocument, 'out');
		$mvc->declareInterface('batch_MovementSourceIntf');
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
		$storeId = ($mvc instanceof core_Detail) ? ($mvc->Master->fetchField($rec->{$mvc->masterKey}, $mvc->Master->storeFieldName)) : $rec->{$mvc->storeFieldName};
		if(!$storeId) return;
		
		if($mvc->getBatchMovementDocument($rec) == 'out') return;
		$form->FNC('batch', 'text', 'caption=Партида,after=productId,input=none');
		
		// Задаване на типа на партидата на полето
		if(isset($rec->{$mvc->productFieldName})){
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if($BatchClass){
				$form->setField('batch', 'input');
				$form->setFieldType('batch', $BatchClass->getBatchClassType());
				
				if(isset($BatchClass->fieldPlaceholder)){
					$form->setField('batch', "placeholder={$BatchClass->fieldPlaceholder}");
				}
				
				// Ако има налични партиди в склада да се показват като предложения
				$exBatches = batch_Items::getBatchQuantitiesInStore($rec->{$mvc->productFieldName}, $storeId);
				if(count($exBatches)){
					$suggestions = array_combine(array_keys($exBatches), array_keys($exBatches));
					$form->setSuggestions('batch', array('' => '') + $suggestions);
				}
				
				$fieldCaption = $BatchClass->getFieldCaption();
				if(!empty($fieldCaption)){
					$form->setField('batch', "caption={$fieldCaption}");
				}
				
				if(isset($rec->id)){
					$batch = batch_BatchesInDocuments::fetchField("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$rec->id}", 'batch');
					$form->setDefault('batch', $batch);
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
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		$storeId = ($mvc instanceof core_Detail) ? ($mvc->Master->fetchField($rec->{$mvc->masterKey}, $mvc->Master->storeFieldName)) : $rec->{$mvc->storeFieldName};
		if(haveRole('partner')) return;
		
		if($mvc->getBatchMovementDocument($rec) == 'out') {
			$rec->isEdited = TRUE;
			return;
		}
		
		if(!$storeId) return;
		
		if(isset($rec->{$mvc->productFieldName})){
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if($BatchClass){
				$form->setField('batch', 'input,class=w50');
				if(!empty($rec->batch)){
					$rec->batch = $BatchClass->denormalize($rec->batch);
				}
			} else {
				$form->setField('batch', 'input=none');
				unset($rec->batch);
			}
			
			if($form->isSubmitted()){
				$rec->isEdited = TRUE;
				if(is_object($BatchClass)){
					if(!empty($rec->batch)){
						$productInfo = cat_Products::getProductInfo($rec->{$mvc->productFieldName});
						$quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
						$quantity = ($rec->packQuantity) ? $rec->packQuantity * $quantityInPack : $quantityInPack;
							
						if(!$BatchClass->isValid($rec->batch, $quantity, $msg)){
							$form->setError('batch', $msg);
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Преразпределяне на партидите
	 */
	private static function autoAllocate($mvc, $rec)
	{
		// След създаване се прави опит за разпределяне на количествата според наличните партиди
		$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
		if(is_object($BatchClass)){
			$info = $mvc->getRowInfo($rec->id);
			if(count($info->operation)){
				$batches = $BatchClass->allocateQuantityToBatches($info->quantity, $info->operation['out'], $info->date);
				batch_BatchesInDocuments::saveBatches($mvc, $rec->id, $batches);
			}
		}
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		if($mvc->getBatchMovementDocument($rec) == 'out'){
			self::autoAllocate($mvc, $rec);
		} else {
			
			// Ако се създава нова партида, прави се опит за автоматичното и създаване
			if(empty($rec->batch)){
				$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
				if(is_object($BatchClass)){
					if($mvc instanceof core_Master){
						$rec->batch = $BatchClass->getAutoValue($mvc, $rec->id, $rec->{$mvc->storeFieldName}, $rec->{$mvc->valiorFld});
					} else {
						$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, "{$mvc->Master->storeFieldName},{$mvc->Master->valiorFld}");
						$rec->batch = $BatchClass->getAutoValue($mvc->Master, $rec->{$mvc->masterKey}, $masterRec->{$mvc->Master->storeFieldName}, $masterRec->{$mvc->Master->valiorFld});
					}
				}
			}
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		// Нормализираме полето за партидата
		if(!empty($rec->batch)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(is_object($BatchClass)){
				$rec->batch = $BatchClass->normalize($rec->batch);
			}
		} else {
			$rec->batch = NULL;
		}
		
		// Ако записа е редактиран и к-то е променено
		if($rec->isEdited === TRUE && isset($rec->id)){
			if($rec->quantity != $mvc->fetchField($rec->id, 'quantity') && batch_Defs::getBatchDef($rec->productId)){
				$rec->autoAllocate = TRUE;
			}
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
	{
		if($mvc->getBatchMovementDocument($rec) == 'out') {
			if($rec->autoAllocate === TRUE){
				batch_BatchesInDocuments::delete("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$rec->id}");
				self::autoAllocate($mvc, $rec);
				core_Statuses::newStatus('Преразпределени партиди, поради променено количество');
			}
			
			return;
		}
		
		if($rec->isEdited === TRUE){
			if(empty($rec->batch)){
				batch_BatchesInDocuments::delete("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$rec->id}");
			} else {
				if(!isset($rec->quantity)){
					$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
				}
				
				batch_BatchesInDocuments::saveBatches($mvc, $rec->id, array($rec->batch => $rec->quantity), TRUE);
			}
		}
	}
	
	
	/**
	 * Преди подготовка на полетата за показване в списъчния изглед
	 */
	public static function on_AfterPrepareListRows($mvc, $data)
	{
		// Само за детайли
		if($mvc instanceof core_Master) return;
		
		if(!count($data->rows) || haveRole('partner')) return;
		
		foreach ($data->rows as $id => &$row){
			$rec = &$data->recs[$id];
			
			$storeId = (isset($rec->{$mvc->storeFieldName})) ? $rec->{$mvc->storeFieldName} : $data->masterData->rec->{$mvc->Master->storeFieldName};
			
			if(batch_BatchesInDocuments::haveRightFor('modify', (object)array('detailClassId' => $mvc->getClassId(), 'detailRecId' => $rec->id, 'storeId' => $storeId))){
				core_RowToolbar::createIfNotExists($row->_rowTools);
				core_Request::setProtected('detailClassId,detailRecId,storeId');
				$url = array('batch_BatchesInDocuments', 'modify', 'detailClassId' => $mvc->getClassId(), 'detailRecId' => $rec->id, 'storeId' => $storeId, 'ret_url' => TRUE);
				$row->_rowTools->addLink('Партиди', $url, array('ef_icon' => "img/16/wooden-box.png", 'title' => "Избор на партиди"));
				core_Request::removeProtected('detailClassId,detailRecId,storeId');
			}
		}
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$res, &$data)
	{
		if($mvc instanceof core_Master) return;
		if(!count($data->rows) || haveRole('partner')) return;
		
		$rows = &$data->rows;
		
		foreach ($rows as $id => &$row){
			$rec = &$data->recs[$id];
			
			$storeId = (isset($rec->{$mvc->storeFieldName})) ? $rec->{$mvc->storeFieldName} : $data->masterData->rec->{$mvc->Master->storeFieldName};
			if(!$storeId) return;
			
			if(!batch_Defs::getBatchDef($rec->{$mvc->productFieldName})) continue;
			
			$row->{$mvc->productFieldName} = new core_ET($row->{$mvc->productFieldName});
			$row->{$mvc->productFieldName}->append(batch_BatchesInDocuments::renderBatches($mvc, $rec->id, $storeId));
		}
	}
	
	
	
	/**
	 * Метод по реализация на определянето на движението генерирано от реда
	 * 
	 * @param core_Mvc $mvc
	 * @param string $res
	 * @param stdClass $rec
	 * @return void
	 */
	public static function on_AfterGetBatchMovementDocument($mvc, &$res, $rec)
	{
		if(!$res){
			$res = $mvc->batchMovementDocument;
		}
	}
	
	
	/**
	 * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
	 */
	public static function on_AfterGetRowInfo($mvc, &$res, $rec)
	{
		if(isset($res)) return;
		
		$rec = $mvc->fetchRec($rec);
		if(isset($mvc->rowInfo[$rec->id])){
			$res = $mvc->rowInfo[$rec->id];
			return;
		}
		
		$operation = ($mvc->getBatchMovementDocument($rec) == 'out') ? 'out' : 'in';
		if($mvc instanceof core_Detail){
			$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, "{$mvc->Master->storeFieldName},containerId,{$mvc->Master->valiorFld},state");
			$Master = $mvc->Master;
		} else {
			$masterRec = $rec;
			$Master = $mvc;
		}
		
		$res = (object)array('productId'      => $rec->{$mvc->productFieldName},
		                     'packagingId'    => $rec->packagingId,
		                     'quantity'       => $rec->quantity,
		                     'quantityInPack' => $rec->quantityInPack,
		                     'containerId'    => $masterRec->containerId,
		                     'date'           => $masterRec->{$Master->valiorFld},
		                     'state'          => $masterRec->state,
		                     'operation'      => array($operation => $masterRec->{$Master->storeFieldName}),
		                     );
		
		$mvc->rowInfo[$rec->id] = $res;
		$res = $mvc->rowInfo[$rec->id];
	}
	
	
	/**
	 * Кои роли могат да променят групово партидите на изходящите документи
	 */
	public static function on_AfterGetRolesToModfifyBatches($mvc, &$res, $rec)
	{
		$rec = $mvc->fetchRec($rec);
		if(!batch_Defs::getBatchDef($rec->{$mvc->productFieldName})){
			$res = 'no_one';
		} else {
			// Ако има склад и документа е входящ, не може
			$info = $mvc->getRowInfo($rec);
			$storeId = (isset($rec->{$mvc->storeFieldName})) ? $rec->{$mvc->storeFieldName} : $mvc->Master->fetchField($rec->{$mvc->masterKey}, $mvc->Master->storeFieldName);
			
			if(!$storeId || !count($info->operation)){
				$res = 'no_one';
			} elseif($mvc->getBatchMovementDocument($rec) != 'out'){
				$res = 'no_one';
			} else {
				$res = $mvc->getRequiredRoles('edit', $rec);
			}
		}
	}
	
	
	/**
	 * Филтриране по подразбиране на наличните партиди
	 */
	public static function on_AfterFilterBatches($mvc, &$res, $rec, &$batches)
	{
		
	}
	
	
	/**
	 * След изтриване на запис
	 */
	protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
	{
		foreach ($query->getDeletedRecs() as $id => $rec) {
			batch_BatchesInDocuments::delete("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$id}");
		}
	}
	
	
	/**
	 * След подготовка на сингъла
	 */
	public static function on_AfterPrepareSingle($mvc, &$res, $data)
	{
		// Ако документа има сингъл добавя му се информацията за партидата
		$row = &$data->row;
		$rec = &$data->rec;
		
		if(!batch_Defs::getBatchDef($rec->{$mvc->productFieldName})) return;
		$row->{$mvc->productFieldName} = new core_ET($row->{$mvc->productFieldName});
		$row->{$mvc->productFieldName}->append(batch_BatchesInDocuments::renderBatches($mvc, $rec->id, $rec->{$mvc->storeFieldName}));
	}
}