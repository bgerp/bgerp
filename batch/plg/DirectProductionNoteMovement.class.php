<?php



/**
 * Клас 'batch_plg_DirectProductionNoteMovement' - За генериране на партидни движения на протокола за производство
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_plg_DirectProductionNoteMovement extends core_Plugin
{
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setField('batch', 'input');
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
		
		if(isset($rec->productId)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			
			if(is_object($BatchClass)){
				$form->setFieldType('batch', $BatchClass->getBatchClassType());
				if(!isset($rec->id)){
					$form->setDefault('batch', $BatchClass->getAutoValue($mvc, $rec));
				}
			} else {
				$form->setField('batch', 'input=none');
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
		$BatchClass = batch_Defs::getBatchDef($data->rec->productId);
		if(is_object($BatchClass)){
			
			// Ако не е въведена партида, сетваме грешка
			if(empty($data->rec->batch) && $data->rec->state == 'draft'){
				$data->row->productId = ht::createHint($data->row->productId, 'Не е въведен партиден номер', 'warning');
			}
		}
	}
}