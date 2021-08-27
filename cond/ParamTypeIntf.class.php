<?php


/**
 * Интерфейс за създаване на параметрични типове
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_ParamTypeIntf extends embed_DriverIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Връща инстанция на типа
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return core_Type - готовия тип
     */
    public function getType($rec, $domainClass = null, $domainId = null, $value = null)
    {
        return $this->class->getType($rec, $domainClass, $domainId, $value);
    }


    /**
     * Връща дефолтната стойност на параметъра
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return mixed    $default       - дефолтната стойност (ако има)
     */
    public function getDefaultValue($rec, $domainClass = null, $domainId = null, $value = null)
    {
        return $this->class->getDefaultValue($rec, $domainClass, $domainId, $value);
    }


    /**
     * Вербално представяне на стойноста
     *
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        return $this->class->toVerbal($rec, $domainClass, $domainId, $value);
    }
}
