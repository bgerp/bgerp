<?php


/**
 * Дефолтно разпечатване на бърз етикет
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_printer_Browser extends peripheral_DeviceDriver
{


    /**
     *
     */
    public $interfaces = 'peripheral_BrowserPrinterIntf';


    /**
     *
     */
    public $title = 'Браузърен принтер';
    
    
    /**
     *
     */
    public $loadList = 'peripheral_DeviceWebPlg';

    
    
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
        return $text;
    }


    /**
     * Отпечатва подадени текст
     *
     * @param stdClass $pRec
     * @param string $text
     */
    public function getJS($pRec, $text)
    {
        $js = "setTimeout(function(){window.print();}, 200);";

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

        $res = "window.onafterprint = function(){
                   getEfae().process({resUrl: '{$url}'}, {type:  'unknown', res: 'OK'});
                }";

        return $res;
    }
}
