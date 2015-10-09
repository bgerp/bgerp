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
		$this->class->addFields($fieldset);
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function canSelectDriver($userId = NULL)
	{
		$this->class->canSelectDriver($userId);
	}


	/**
	 * Връща дефолтното име на задача от драйвера
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		$this->class->getDefaultTitle();
	}
	
	
	/**
	 * Обновяване на данните на мастъра
	 *
	 * @param stdClass $rec - запис
	 * @param void
	 */
	public function updateEmbedder(&$rec)
	{
		$this->class->updateEmbedder($rec);
	}
	
	
	/**
	 * Добавяне на полета към формата на детайла
	 *
	 * @param core_FieldSet $form
	 */
	public function addDetailFields(core_FieldSet &$form)
	{
		$this->class->addDetailFields($form);
	}
	

	/**
	 * Възможност за промяна след събмита на формата на детайла
	 *
	 * @param core_Form $form
	 * @return void
	 */
	public function inputEditFormDetail(core_Form $form)
	{
		$this->class->inputEditFormDetail($form);
	}


	/**
	 * Възможност за промяна след подготовката на детайла
	 *
	 * @param core_ET $tpl
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareDetail(&$data)
	{
		$this->class->prepareDetail($data);
	}
	
	
	/**
	 * Възможност за промяна след подготовката на лист тулбара
	 *
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareListToolbarDetail(&$data)
	{
		$this->class->prepareListToolbarDetail($data);
	}
	
	
	/**
	 * Възможност за промяна след подготовката на формата на детайла
	 *
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareEditFormDetail(&$data)
	{
		$this->class->prepareEditFormDetail($data);
	}
	
	
	/**
	 * Възможност за промяна след обръщането на данните във вербален вид
	 *
	 * @param stdClass $row
	 * @param stdClass $rec
	 * @return void
	 */
	public function recToVerbalDetail(&$row, $rec)
	{
		$this->class->recToVerbalDetail($row, $rec);
	}
	
	
	/**
	 * Възможност за промяна след рендирането на детайла
	 *
	 * @param core_ET $tpl
	 * @param stdClass $data
	 * @return void
	 */
	public function renderDetail(&$tpl, $data)
	{
		$this->class->renderDetail($tpl, $data);
	}
	
	
	/**
	 * Възможност за промяна след рендирането на шаблона на детайла
	 *
	 * @param core_ET $tpl
	 * @param stdClass $data
	 * @return void
	 */
	public function renderDetailLayout(&$tpl, $data)
	{
		$this->class->renderDetailLayout($tpl, $data);
	}
	
	
	/**
	 * Кой детайл да бъде добавен към мастъра
	 * 
	 * @return varchar - името на детайла
	 */
	public function getDetail()
	{
		$this->class->getDetail();
	}
}