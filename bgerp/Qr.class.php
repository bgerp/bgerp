<?php



/**
 * Клас 'bgerp_Qr' - Създаване нa QR изображения
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Qr extends core_Manager
{
    
    
    /**
     * @todo Чака за документация...
     */
    function act_C()
    {
        $cid = Request::get('cid');
        $mid = Request::get('mid');
        
        barcode_Qr::generate($cid, $mid);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function createQrLink($cid, $mid)
    {
        
        $link = toUrl(array('D', 'S', 'cid' => $cid, 'mid' => $mid), 'absolute');
        
        return $link;
    }
}