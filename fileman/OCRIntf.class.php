<?php


/**
 * Клас 'fileman_OCRIntf' - Интерфейс за разпознаване на текст с OCR
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_OCRIntf
{

    
    /**
     * Добавя бутон в тулбара за стартиране на OCR процеса
     * 
     * @param core_Toolbar $toolbar
     */
    function addOcrBtn($toolbar)
    {
        
        return $this->class->convertDoc($toolbar);
    }

    
    /**
     * Екшъна за извличане на текст чрез OCR
     */
    function acr_getTextByOcr()
    {
        
        return $this->class->acr_getTextByOcr();
    }
}
