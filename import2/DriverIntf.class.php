<?php



/**
 * Интерфейс за импортиране на данни в мениджър
 *
 *
 * @category  bgerp
 * @package   import
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за импортиране на данни в мениджър
 */
class import2_DriverIntf
{
	
	/**
	 * Инстанция на класа
	 */
	public $class;
	
		
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
	 * Изпълнява импортирането
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $rec
	 * @return array $recs
	 */
	public function doImport(core_Manager $mvc, $rec)
	{ 
		return $this->class->doImport($mvc, $rec);
	}


	/**
	 * Може ли драйвера за импорт да бъде избран
	 *
	 * @param   core_Manager    $mvc        - клас в който ще се импортира
     * @param   int|NULL   $masterId   - ако импортираме в детайл, id на мастъра му
	 * @param   int|NULL        $userId     - ид на потребител
     *
	 * @return boolean          - може ли драйвера да бъде избран
	 */
	public function canSelectDriver(core_Manager $mvc, $masterId = NULL, $userId = NULL)
	{
		return $this->class->canSelectDriver($mvc, $masterId, $userId);
	}

}