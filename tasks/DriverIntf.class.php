<?php

/**
 * Интерфейс за създаване на драйвери за задачи
 *
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за драйвери на задачи
 */
class tasks_DriverIntf extends embed_DriverIntf
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'planning_TaskDetailIntf';
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver;
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		return $this->class->addFields($fieldset);
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function canSelectDriver($userId = NULL)
	{
		return $this->class->canSelectDriver($userId);
	}


	/**
	 * Връща дефолтното име на задача от драйвера
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return $this->class->getDefaultTitle();
	}
	
	
	/**
	 * Обновяване на данните на мастъра
	 *
	 * @param stdClass $rec - запис
	 * @param void
	 */
	public function updateEmbedder(&$rec)
	{
		return $this->class->updateEmbedder($rec);
	}
	
	
	/**
	 * Кои детайли да се закачат динамично
	 * 
	 * @return array $details - масив с детайли за закачане
	 */
	public function getDetails()
	{
		return $this->class->getDetails();
	}
}