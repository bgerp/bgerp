<?php



/**
 * Клас 'batch_plg_DocumentActions' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_DocumentMovement extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->storeFieldName, 'storeId');
		setIfNot($mvc->savedMovements, array());
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		
		// Ако има вече разпределени партиди, склада не може да се сменя
		if(isset($form->rec->containerId) && $data->action != 'clone'){
			if(batch_BatchesInDocuments::fetchField("#containerId = {$form->rec->containerId}")){
				$form->setField($mvc->storeFieldName, array('hint' => 'Склада не може да се смени, защото има разпределени партиди от него'));
				$form->setReadOnly($mvc->storeFieldName);
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
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = NULL)
	{
		if($rec->state == 'active'){
			if($mvc->hasPlugin('acc_plg_Contable')){
				if(isset($saveFileds)) return;
			}
			
			$containerId = (isset($rec->containerId)) ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
			
			// Отразяване на движението, само ако в текущия хит не е отразено за същия документ
			if(!isset($mvc->savedMovements[$containerId])){
				batch_Movements::saveMovement($containerId);
				
				// Дига се флаг в текущия хит че движението е отразено
				$mvc->savedMovements[$containerId] = TRUE;
			}
			
		} elseif($rec->state == 'rejected'){
			$containerId = (isset($rec->containerId)) ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
			$doc = doc_Containers::getDocument($containerId);
			batch_Movements::removeMovement($doc->getInstance(), $doc->that);
		}
	}
	
	
	/**
	 * След подготовка на тулбара на единичен изглед
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, $data)
	{
		if(batch_Movements::haveRightFor('list') && $data->rec->state == 'active'){
			$data->toolbar->addBtn('Партиди', array('batch_Movements', 'list', 'document' => $mvc->getHandle($data->rec->id)), 'ef_icon = img/16/wooden-box.png,title=Добавяне като ресурс,row=2');
		}
	}
}