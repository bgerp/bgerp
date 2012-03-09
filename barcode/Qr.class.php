<?php


/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'phpqrcode1.1.4/qrlib.php';


/**
 * Клас 'barcode_Qr' - Генериране на QR изображения
 *
 *
 * @category  vendors
 * @package   barcode
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class barcode_Qr
{
    
    
    /**
     * 
     */
    static function generate($cid, $mid, $output=NULL, $quality='L', $size=3, $margin=0)
    {
        $link = bgerp_Qr::createQrLink($cid, $mid);
        
        QRcode::png($link, $output, $quality, $size, $margin);
    }
    
    

}