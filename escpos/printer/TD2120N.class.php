<?php


/**
 *
 *
 * @category  bgerp
 * @package   polygonteam
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class escpos_printer_TD2120N extends peripheral_DeviceDriver
{
    public $interfaces = 'escpos_PrinterIntf';
    
    public $title = 'Принтер Brother TD2120N';
    
    
    /**
     *
     */
    public $loadList = 'peripheral_DeviceWebPlg';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('serverUrl', 'url', 'caption=Сървър->УРЛ, mandatory');
        $fieldset->FLD('printerIp', 'IP', 'caption=Принтер->IP, mandatory');
        $fieldset->FLD('printerPort', 'int(Min=0, max=65535)', 'caption=Принтер->Порт, mandatory');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Отпечатва подадени текст
     *
     * @param stdClass $pRec
     * @param string $text
     */
    public function getJS($pRec, $text)
    {
        $jsTpl = getTplFromFile('/escpos/js/jsPrintTpl.txt');
        
        $jsTpl->replace(json_encode($pRec->serverUrl), 'serverUrl');
        $jsTpl->replace(json_encode($pRec->printerIp), 'printerIp');
        $jsTpl->replace(json_encode($pRec->printerPort), 'printerPort');
        
        $text = escpos_Convert::process($text, 'escpos_driver_TD2120');
        $jsTpl->replace(json_encode($text), 'printText');
        
        // Минифициране на JS
        $js = minify_Js::process($jsTpl->getContent());
        
        return $js;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param peripheral_DeviceDriver     $Driver
     * @param peripheral_Devices          $Embedder
     * @param stdClass                    $data
     */
    protected static function on_AfterPrepareEditForm($Driver, $Embedder, &$data)
    {
        $data->form->setDefault('serverUrl', 'http://127.0.0.1');
        $data->form->setDefault('printerPort', 9100);
    }
}
