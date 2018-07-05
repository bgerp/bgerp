<?php



/**
 * Абстрактен драйвер за импортиране import2_AbstractDriver
 *
 * @category  bgerp
 * @package   import2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Абстрактен клас за драйвери за импорт
 */
abstract class import2_AbstractDriver
{

    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'import2_DriverIntf';


    /**
     * Може ли драйвера за импорт да бъде избран
     *
     * @param core_Manager $mvc      - клас в който ще се импортира
     * @param int|NULL     $masterId - ако импортираме в детайл, id на записа на мастъра му
     * @param int|NULL     $userId   - ид на потребител
     *
     * @return boolean - може ли драйвера да бъде избран
     */
    public function canSelectDriver(core_Manager $mvc, $masterId = null, $userId = null)
    {
        return true;
    }
    
    
    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param  core_Manager  $mvc
     * @param  core_FieldSet $form
     * @return void
     */
    abstract public function addImportFields($mvc, core_FieldSet $form);
    
    
    /**
     * Проверява събмитнатата форма
     *
     * @param  core_Manager  $mvc
     * @param  core_FieldSet $form
     * @return void
     */
    public function checkImportForm($mvc, core_FieldSet $form)
    {
    }
    
    
    /**
     * Подготвя импортиращата форма
     *
     * @param  core_Manager  $mvc
     * @param  core_FieldSet $form
     * @return void
     */
    public function prepareImportForm($mvc, core_FieldSet $form)
    {
    }
}
