<?php



/**
 * Клас 'batch_plg_DirectProductionNoteMovement' - За генериране на партидни движения на протокола за производство
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_plg_DirectProductionNoteMovement extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{return;
		setIfNot($mvc->productFieldName, 'productId');
		setIfNot($mvc->batchMovementDocument, 'in');
		$mvc->declareInterface('batch_MovementSourceIntf');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{return;
		$form = &$data->form;
		$rec = &$form->rec;
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
		
				if(isset($BatchClass->fieldCaption)){
					$form->setField('batch', "caption={$BatchClass->fieldCaption}");
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
		return;
		
		if(isset($rec->productId)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			
			if(is_object($BatchClass)){
				$form->setFieldType('batch', $BatchClass->getBatchClassType());
			} else {
				$form->setField('batch', 'input=none,class=w50');
				unset($rec->batch);
			}
		}
		
		if($form->isSubmitted()){
			if(is_object($BatchClass)){
				if(!empty($rec->batch)){
					$measureId = cat_Products::fetchField($rec->productId, 'measureId');
					if(!$BatchClass->isValid($rec->batch, $rec->quantity, $msg)){
						$form->setError('batch', $msg);
					}
				}
			}
		}
	}
	
	
	/**
	 * Променяме шаблона в зависимост от мода
	 *
	 * @param core_Mvc $mvc
	 * @param core_ET $tpl
	 * @param object $data
	 */
	public static function on_BeforeRenderSingleLayout($mvc, &$tpl, &$data)
	{
		return;
		$BatchClass = batch_Defs::getBatchDef($data->rec->productId);
		if(is_object($BatchClass)){
			
			// Ако не е въведена партида, сетваме грешка
			if(empty($data->rec->batch) && $data->rec->state == 'draft'){
				$data->row->productId = ht::createHint($data->row->productId, 'Не е въведен партиден номер', 'warning');
			}
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{return;
		// Нормализираме полето за партидата
		if(!empty($rec->batch)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(is_object($BatchClass)){
				if($rec->batch != $BatchClass->getAutoValueConst()){
					$rec->batch = $BatchClass->normalize($rec->batch);
				}
			}
		} else {
			$rec->batch = NULL;
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
	{return;
		// След запис ако партидата е за автоматично обновяване, обновява се
		if(!empty($rec->batch)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(is_object($BatchClass)){
				if($rec->batch == $BatchClass->getAutoValueConst()){
					$rec->batch = $BatchClass->getAutoValue($mvc, $rec->id);
					$mvc->save_($rec, 'batch');
				}
			}
		}
	}
}