<?php


/**
 * Интерфейс за отпечатване на принтер
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_BrowserPrinterIntf extends peripheral_PrinterIntf
{

    /**
     * Връща HTML, който ще се използва при печат
     *
     * @param stdClass $rec
     * @param string $text
     *
     * @return string
     */
    public function getHTML($rec, $text)
    {
        return $this->class->getHTML($rec, $text);
    }

    
    /**
     * Отпечатва подадени текст
     * 
     * @param stdClass $rec
     * @param string $text
     *
     * @return string
     */
    public function getJS($rec, $text)
    {
        return $this->class->getJS($rec, $text);
    }


    /**
     * Отпечатва подадени текст
     *
     * @param stdClass $rec
     * @param string $text
     * @param string $url
     *
     * @return string
     */
    public function afterResultJS($rec, $text, $url)
    {
        return $this->class->afterResultJS($rec, $text, $url);
    }
}
