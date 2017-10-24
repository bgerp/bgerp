<?php


/**
 * Интерфейс за създаване на справки във системата
 *
 *
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame2_ReportIntf extends embed_DriverIntf
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
	 * Връща заглавието на отчета
	 *
	 * @param stdClass $rec - запис
	 * @return string|NULL  - заглавието или NULL, ако няма
	 */
	public function getTitle($rec)
	{
		return $this->class->getTitle($rec);
	}
	
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @return stdClass|NULL $data - подготвените данни
	 */
	public function prepareData($rec)
	{
		return $this->class->prepareData($rec);
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{
		return $this->class->renderData($rec);
	}
	
	
	/**
	 * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
	 *
	 * @param stdClass $rec
	 * @return boolean
	 */
	public function canSendNotificationOnRefresh($rec)
	{
		return $this->class->canSendNotificationOnRefresh($rec);
	}
	
	
	/**
	 * Връща параметрите, които ще бъдат заместени в текста на нотификацията
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getNotificationParams($rec)
	{
		return $this->class->getNotificationParams($rec);
	}
	
	
	/**
	 * Връща редовете на CSV файл-а
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportRows($rec)
	{
		return $this->class->getCsvExportRows($rec);
	}
	
	
	/**
	 * Връща полетата за експортиране във csv
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportFieldset($rec)
	{
		return $this->class->getCsvExportFieldset($rec);
	}
	
	
	/**
	 * Връща следващите три дати, когато да се актуализира справката
	 *
	 * @param stdClass $rec - запис
	 * @return array|FALSE  - масив с три дати или FALSE ако не може да се обновява
	 */
	public function getNextRefreshDates($rec)
	{
		return $this->class->getNextRefreshDates($rec);
	}
}