<?php

/**
 * Интерфейс за създаване на драйвери за задачи
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за драйвери на задачи
 */
class planning_TaskDetailIntf extends embed_DriverIntf
{
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		 
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function canSelectDriver($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectDriver, $userId);
	}
	
	
	/**
	 * Обновяване на данните на мастъра
	 * 
	 * @param int $id - ид на записа
	 */
	public function updateEmbedder($id)
	{
		return $this->class->updateEmbedder($id);
	}
	
	
	/**
	 * Добавяне на полета към формата на детайла
	 *
	 * @param core_FieldSet $form
	 */
	public function addDetailFields(core_FieldSet &$form)
	{
		 return $this->class->addDetailFields($form);
	}
}