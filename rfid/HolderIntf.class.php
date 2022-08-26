<?php


/**
 * Интерфейс за притежатели на  RFID
 *
 *
 * @category  bgerp
 * @package   rfid
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за притежатели на  RFID
 */
class rfid_HolderIntf extends embed_DriverIntf
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
