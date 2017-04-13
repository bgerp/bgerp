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
        $dataArr['printerSelectCodetable'] = 23;
        $dataArr['printerSelectCodetableChar'] = 116;
        
        $tpl->placeArray($dataArr);
        
        parent::placePrintData($tpl);
        
        return $tpl;
    }
}
