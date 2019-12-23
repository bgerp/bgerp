<?php


/**
 * Интерфейс за създаване драйвери за вграждане в други обекти
 *
 *
 * @category  bgerp
 * @package   embed
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class embed_DriverIntf
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
     * Може ли вградения обект да се избере
     */
    public function canSelectDriver($userId = null)
    {
        return $this->class->canSelectDriver($userId = null);
    }
}
