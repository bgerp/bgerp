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
     * 
     */
    public $ocrType = 'textOcr';
    
    
    /**
     * Проверка дали може да се пуска OCR обработка
     * 
     * @param stdObject|string $fRec
     */
    function canExtract($fRec)
    {
        
        return $this->class->canExtract($fRec);
    }

    
    /**
     * Екшъна за извличане на текст чрез OCR
     */
    function act_getTextByOcr()
    {
        
        return $this->class->act_getTextByOcr();
    }

    
    /**
     * Функция за извличане на текст
     * 
     * @param stdObject|string $fRec
     */
    function getTextByOcr($fRec)
    {
        
        return $this->class->getTextByOcr($fRec);
    }

    
    /**
     * Бърза проврка дали има смисъл от OCR-ване на текста
     * 
     * @param stdObject|string $fRec
     */
    function haveTextForOcr($fRec)
    {
        
        return $this->class->haveTextForOcr($fRec);
    }
}
