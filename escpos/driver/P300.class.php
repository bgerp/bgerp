<?php


/**
 * Драйвер за escpos принтер
 *
 * @category  bgerp
 * @package   escprint
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_driver_P300 extends escpos_driver_Ddp250
{
    
    
    /**
     * Заглавие
     */
    public $title = 'P300';
    
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
        
        // Това ще се сетва при изпращане на текст
        $tpl->removeBlock('printerSelectCodetableChar');
        $tpl->removeBlock('printerSelectCodetable');
        
        // Това е за кодово таблица 17
        // За 23 - cp1251
        $dataArr['printerPrintTaggedTextEncoding'] = 'cp866';
        
        $tpl->placeArray($dataArr);
        
        parent::placePrintData($tpl);
        
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
        $res = chr(27) . 't' . chr(17) . $res;
        
        return $res;
    }
}
