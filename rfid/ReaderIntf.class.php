<?php

/**
 * Интерфейс за IP RFID рийдър
 *
 * @category   bgERP 2.0
 * @package    rfid
 * @title:     Драйвер на RFID четец
 * @author     Dimiter Minekov <mitko@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class rfid_ReaderIntf
{
    /**
     * Връща запис с IP четеца или база данни
     */
    function getData($date)
    {
        $this->class->getData($date);
    }
    
    
    /**
     * Задава параметрите за свръзка
     */
    function init($params)
    {
        return $this->class->init($params);
    }
}