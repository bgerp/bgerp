<?php


/**
 * Драйвер за escpos принтер, който емулира печат на HTML устройство
 *
 * @category  bgerp
 * @package   escprint
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_driver_Html extends core_BaseClass
{
    
    
    /**
     * Заглавие
     */
    public $title = 'HTML ESC/POS Принтер';
    

    /**
     * Връща конанди за настройка на шрифта
     */
    public function getFont($font, $bold, $underline) 
    {
        $style = '';

        if($font == 'F') {
            $style .= 'font-size:1.2em;';
        }

        if($font == 'f') {
            $style .= 'font-size:0.8em;';
        }
        if($bold) {
            $style .= 'font-weight:bold;';
        }
        if($underline) {
            $style .= 'text-decoration:underline;';
        }

        return "<span style='{$style}'>";
    }


    /**
     * Край на задаването на шрифта
     */
    public function getFontEnd() 
    {
        return "</span>";
    }


    /**
     * Нова линия
     */
    public function getNewLine() 
    {
        return "<br>";
    }


    /**
     * Последователност за срязване на хартията
     */
    public function getCutting() 
    {
    }

    /**
     * Връща символа за интервал 
     * Преместване на позицията с един символ, без да отпечатва нищо
     */
    public function getSpace()
    {
        return '&nbsp;';
    }


    /**
     * Връща максималния брой символи, според вида на шрифта
     */
    public function getWidth($font = NULL)
    {
        $width = 32;

        if($font == 'f') {
            $width = 48;
        }

        if($font == 'F') {
            $width = 29;
        }

        return $width;
    }


    /**
     * Общо конвертиране за изходния текст
     */
    public function encode($text)
    {
        return "<div style='font-family:\"Courier New\", Courier, monospace;'>" . $text . "</div>";
    }
    
    
    /**
     * 
     * 
     * @param core_Et $tpl
     * 
     * @return core_Et
     */
    public function placePrintData($tpl)
    {
        
        return $tpl;
    }
    
    
    /**
     * Добавя необходимите настройки за преди текста за отпечатване
     * 
     * @param string $res
     * 
     * @return string
     */
    public function prepareTextSettings($res)
    {
        
        return $res;
    }
}
