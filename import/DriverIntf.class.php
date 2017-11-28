<?php



/**
 * Интерфейс за импортиране на данни в мениджър
 *
 *
 * @category  bgerp
 * @package   import
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за импортиране на данни в мениджър
 */
class import_DriverIntf
{
	
	
	/**
	 * Инстанция на класа
	 */
	public $class;
	
	
	/**
	 * Към кои класове може да се добавя драйвера
	 *
	 * @var string - изброените класове или празен клас за всички
	 */
	protected $allowedClasses;
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	protected $canSelectDriver;
	
	
	/**
	 * Може ли драйвера за импорт да бъде избран
	 *
	 * @param core_Manager $mvc - клас в който ще се импортира
	 * @param int|NULL $userId  - ид на потребител
	 * @return boolean          - може ли драйвера да бъде избран
	 */
	public function canSelectDriver(core_Manager $mvc, $rec, $userId = NULL)
	{
		return $this->class->canSelectDriver($mvc, $rec, $userId);
	}
	
	
	/**
	 * Добавя специфични полета към формата за импорт на драйвера
	 *
	 * @param core_Manager $mvc
	 * @param core_FieldSet $form
	 * @return void
	 */
	public function addImportFields($mvc, core_FieldSet $form)
	{
		return $this->class->addImportFields($mvc, $form);
	}
	
	
	/**
	 * Проверява събмитнатата форма
	 *
	 * @param core_Manager $mvc
	 * @param core_FieldSet $form
	 * @return void
	 */
	public function checkImportForm($mvc, core_FieldSet $form)
	{
		return $this->class->checkImportForm($mvc, $form);
	}
	
	
	/**
	 * Подготвя импортиращата форма
	 *
	 * @param core_Manager $mvc
	 * @param core_FieldSet $form
	 * @return void
	 */
	public function prepareImportForm($mvc, core_FieldSet $form)
	{
		return $this->class->prepareImportForm($mvc, $form);
	}
	
	
	/**
	 * Връща записите, подходящи за импорт в детайла.
	 * Съответстващия 'importRecs' метод, трябва да очаква
	 * същите данни (@see import_DestinationIntf)
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $rec
	 * @return array $recs
	 */
	public function getImportRecs(core_Manager $mvc, $rec)
	{
		return $this->class->getImportRecs($mvc, $rec);
	}
}