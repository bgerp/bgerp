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
class planning_TaskDetailIntf extends core_InnerObjectIntf
{
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
	/**
	 * Добавяне на полета към формата на детайла
	 *
	 * @param core_FieldSet $form
	 */
	public function addDetailFields(core_FieldSet &$form)
	{
		 return $this->class->addDetailFields($form);
	}
	
	
	/**
	 * Проверява въведената форма
	 *
	 * @param core_Form $form
	 */
	public function checkDetailForm(core_Form &$form)
	{
		return $this->class->checkDetailForm($form);
	}
	
	
	/**
	 * Промяна на подготовката на детайла
	 *
	 * @param stdClass $data
	 */
	public function prepareDetailData($data)
	{
		return $this->class->prepareDetailData($data);
	}
	
	
	/**
	 * Рендиране на информацията на детайла
	 */
	public function renderDetailData($data)
	{
		return $this->class->renderDetailData($data);
	}
	
	
	/**
	 * Обновяване на данните на мастъра
	 */
	public function updateEmbedder()
	{
		 return $this->class->updateEmbedder();
	}
}