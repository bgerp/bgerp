<?php



/**
 * Помощен клас-имплементация на интерфейса import_DriverIntf
 *
 * @category  bgerp
 * @package   import
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Базов клас за драйвери за импорт
 */
abstract class import_drivers_Proto
{
	
	
	/**
	 * Към кои класове може да се добавя драйвера
	 *
	 * @var string - изброените класове или празен клас за всички
	 */
	protected $allowedClasses = '';
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	protected $canSelectDriver = 'powerUser';
	
	
	/**
	 * Може ли драйвера за импорт да бъде избран
	 * 
	 * @param core_Manager $mvc - клас в който ще се импортира
	 * @param int|NULL $userId  - ид на потребител
	 * @return boolean          - може ли драйвера да бъде избран
	 */
	public function canSelectDriver(core_Manager $mvc, $rec, $userId = NULL)
	{
		$allowed = arr::make($this->allowedClasses);
		if(count($allowed) && !in_array($mvc->className, $allowed)) return FALSE;
		if(!core_Users::haveRole($this->canSelectDriver, $userId)) return FALSE;
		
		return TRUE;
	}
	
	
	/**
	 * Добавя специфични полета към формата за импорт на драйвера
	 * 
	 * @param core_Manager $mvc
	 * @param core_FieldSet $form
	 * @return void
	 */
	abstract function addImportFields($mvc, core_FieldSet $form);
	
	
	/**
	 * Проверява събмитнатата форма
	 *
	 * @param core_Manager $mvc
	 * @param core_FieldSet $form
	 * @return void
	 */
	public function checkImportForm($mvc, core_FieldSet $form)
	{
		
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
	
	}
}