<?php


/**
 * Драйвер за escpos принтер, който емулира печат на HTML устройство
 *
 * @category  bgerp
 * @package   escprint
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class escpos_driver_TD2120 extends core_BaseClass
{
    /**
     * Заглавие
     */
    public $title = 'Brother TD-2120';
    
    
    /**
     * Връща конанди за настройка на шрифта
     */
    public function getFont($font, $bold, $underline)
    {
        $f = $b = $u = 0;
        
        if ($font == 'F') {
            $f = 32;
            $lf = chr(27) . chr(48);
        } elseif ($font == 'f') {
            $f = 1;
            $lf = chr(27) . chr(50);
        } else {
            $lf = chr(27) . chr(48);
        }
        
        if ($bold) {
            $b = 8;
        }
        
        if ($underline) {
            $u = 128;
        }
        
        return $lf . chr(27) . '!' . chr($f | $b | $u) . chr(27) . chr(107) . chr(11);
    }
    
    
    /**
     * Край на задаването на шрифта
     */
    public function getFontEnd()
    {
        return '';
    }
    
    
    /**
     * Нова линия
     */
    public function getNewLine()
    {
        return chr(13) . chr(10);
    }
    
    
    /**
     * Последователност за срязване на хартията
     */
    public function getCutting()
    {
//         chr(27).chr(105).chr(67).'1'
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
    public function getWidth($font = null)
    {
        $width = 22;
        
        if ($font == 'f') {
            $width = 27;
        }
        
        if ($font == 'F') {
            $width = 11;
        }
        
        return $width;
    }
    
    
    /**
     * Общо конвертиране за изходния текст
     */
    public function encode($text)
    {
        // Кирилицата я конвертираме към латиница, защото този принтер не поддържа първото
        $text = str::utf2ascii($text);
        
        return $text;
    }
    
    
    /**
     * Отпечатване на QR код
     * 
     * @param string $text
     * @param integer $q
     * 
     * @return string
     */
    public function getQr($text, $q=6)
    {
        expect((($q <= 11) && ($q >= 0)), $q);
        
        $eCorr = 0;
        
        if ($q >= 9) {
            $eCorr = 4;
        } elseif ($q >= 6) {
            $eCorr = 3;
        }
        
        $res = chr(27) . chr(105) . chr(81) . chr($q) . chr(0x02) . chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00) . chr($eCorr) . chr(0x00) . $text . "\\\\\\";
        
        return $res;
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
        $dataArr = array();

//         $dataArr['printerSelectCodetable'] = 17;

// //         $dataArr['printerSelectCodetableChar'] = 117;
//         $dataArr['printerPrintTaggedTextEncoding'] = 'cp1251';
//         $dataArr['printerFeedPaper'] = 110;
//         $dataArr['printerInputTextEncoding'] = 'UTF-8';
//         $dataArr['printerTextAppendBegin'] = '{reset}';
//         $dataArr['printerTextAppendEnd'] = '{br}';
        
        $tpl->placeArray($dataArr);
        
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
//         $res = chr(27) . chr(116) . chr(1) . $res;
        
        $res = chr(27) . chr(105) . chr(97) . chr(0) . chr(27) . chr(64) . $res . chr(12);
        
        return $res;
    }
}
