<?php


/**
 * Интерфейс за отпечатване на принтер
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class escpos_PrinterIntf extends peripheral_PrinterIntf
{
    
    /**
     * Отпечатва подадени текст
     * 
     * @param stdClass $rec
     * @param string $text
     */
    public function getJS($rec, $text)
    {
        return $this->class->getJS($rec, $text);
    }
}
