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
class escpos_driver_Ddp250 extends core_BaseClass
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Datecs DDP-250';
    

    /**
     * Връща конанди за настройка на шрифта
     */
    public function getFont($font, $bold, $underline) 
    {
        $f = $b = $u = 0;

        if($font == 'F') {
            $f = 32;
        }

        if($font == 'f') {
            $f = 1;
        }

        if($bold) {
            $b = 8;
        }

        if($underline) {
            $u = 128;
        }

        return chr(27) . '!' . chr($f|$b|$u);
    }
    

    /**
     * Край на задаването на шрифта
     */
    public function getFontEnd() 
    {
        return "";
    }


    /**
     * Нова линия
     */
    public function getNewLine() 
    {
        return chr(27) . 'd' . chr(1);
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
        return ' ';
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
            $width = 16;
        }

        return $width;
    }


    /**
     * Общо конвертиране за изходния текст
     */
    public function encode($text)
    {
        return $text;
//         return iconv('utf-8', 'windows-1251', htmlspecialchars_decode($text));
    }
    
}
