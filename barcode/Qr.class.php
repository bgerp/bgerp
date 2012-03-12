<?php


/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'phpqrcode1.1.4/qrlib.php';


/**
 * 
 */
defIfNot('BARCODE_SALT', EF_SALT . '_BARCODE');


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
class barcode_Qr extends core_Manager
{
    
  
    /**
     * Екшън за генериране на QR изображения
     */
    function act_generate()
    {
        //Текстова част
        $text = Request::get('text');
        
        //Пиксли на изображението
        $pixelPerPoint = Request::get('pixelPerPoint');
        
        //Широчината на рамката на изображението
        $outerFrame = Request::get('outerFrame');
        
        //Променливата за проверка дали кода е генериран от системата
        $protect = Request::get('protect');

        //Генерираме код с параемтите
        $salt = self::getProtectSalt($text, $pixelPerPoint, $outerFrame);

        //Ако двата кода си съвпадат, тогава генерираме QR изображението
        if ($salt == $protect) {
            self::getImg($text, $pixelPerPoint, $outerFrame);    
        }
    }
    
    
    /**
     * Връща QR изображението
     */
    static function getImg($text, $pixelPerPoint=3, $outerFrame=0, $quality='L', $outFileName=NULL)
    {
        //Генерира QR изображение
        QRcode::png($text, $outFileName, $quality, $pixelPerPoint, $outerFrame);
    }
    
    
 	/**
     * Връща ключа за защита
     */
    static function getProtectSalt($text, $pixelPerPoint=NULL, $outerFrame=NULL)
    {
        //Ако нямаме въведена стойност на параметрите, въвеждаме им стойности по подразбиране
        //За да може системата да работи коректно
        $outerFrame = $outerFrame ? $outerFrame : $outerFrame=0;
        $pixelPerPoint = $pixelPerPoint ? $pixelPerPoint : $pixelPerPoint=0;
        $text = $text ? $text : $text='';
        
        //Съединяваме трита стринга
        $str = $text . $pixelPerPoint . $outerFrame;
        
        //Към получения стринг, добавяме ключа за защита
        $str .= BARCODE_SALT;
        
        //Вземам md5' а на получения стринг
        $md5Str = md5($str);
        
        //Вземаме първите 8 символа
        $salt = substr($md5Str, 0, 8);

        return $salt;
    }
}