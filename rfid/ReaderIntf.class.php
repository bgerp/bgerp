<?php


/**
 * Интерфейс за RFID рийдър
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
 * @title     Интерфейс за драйвер на RFID четец
 */
class rfid_ReaderIntf extends embed_DriverIntf
{
    
    /**
     * Връща запис RFID четеца или база данни
     */
    public function getData($date)
    {
        $this->class->getData($date); bp($this->class);
    }
}
