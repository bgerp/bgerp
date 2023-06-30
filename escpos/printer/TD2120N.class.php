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
        $fieldset->FLD('printerSerial', 'varchar', 'caption=Принтер->Сериен порт');
        $fieldset->FLD('printerIp', 'IP', 'caption=Принтер->IP');
        $fieldset->FLD('printerPort', 'int(Min=0, max=65535)', 'caption=Принтер->Порт');
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
     * Връща HTML, който ще се използва при печат
     *
     * @param stdClass $rec
     * @param string $text
     *
     * @return string
     */
    public function getHTML($rec, $text)
    {

        return '<div class="fullScreenBg" style="position: fixed; top: 0; z-index: 1002; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: block;"><h3 style="color: #fff; font-size: 56px; text-align: center; position: absolute; top: 30%; width: 100%">Разпечатва се етикет ...<br> Моля, изчакайте!</h3></div>';
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
        
        $conf = new stdclass();
        
        $jsTpl->replace(json_encode($pRec->serverUrl), 'serverUrl');

        $conf->DEVICE = $pRec->printerSerial;
        $conf->IP_ADDRESS = $pRec->printerIp;
        $conf->PORT = $pRec->printerPort;
        $conf->OUT = escpos_Convert::process($text, 'escpos_driver_TD2120');

        $DATA = base64_encode(gzcompress(serialize($conf)));
        
        $jsTpl->replace(json_encode($DATA), 'DATA');

        $js = $jsTpl->getContent();

        return $js;
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
        $url = toUrl($url, 'local');
        $url = urlencode($url);

        $res = " function escPrintOnSuccess(res) {
                    if (res == 'OK') {
                        getEfae().process({resUrl: '{$url}'}, {res:  res});
                    } else {
                        escPrintOnError(res);
                    }
                }
                
                function escPrintOnError(res) {
                    if($.isPlainObject(res)){
                        res = res.status  + '. ' +  res.statusText;
                    }
                    getEfae().process({resUrl: '{$url}'}, {type: 'error', res:  res});
                }";

        return $res;
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
        $data->form->setDefault('serverUrl', 'http://localhost:8080');
        $data->form->setDefault('printerSerial', '/dev/usb/lp0');
        $data->form->setDefault('printerPort', 9100);
    }
}
