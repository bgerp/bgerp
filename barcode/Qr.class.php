<?php



/**
 * "Подправка" за кодиране на баркода
 */
defIfNot('BARCODE_SALT', md5(EF_SALT . '_BARCODE'));


/**
 * Клас 'barcode_Qr' - Генериране на QR изображения
 *
 *
 * @category  bgerp
 * @package   barcode
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class barcode_Qr extends core_Manager
{
    
    
    /**
     * Екшън за генериране на QR изображения
     */
    public function act_Generate()
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
    public static function getImg($text, $pixelPerPoint = 3, $outerFrame = 0, $quality = 'L', $outFileName = null)
    {
        // Параметри за генериране на QR изображение
        $params = array(
            'pixelPerPoint' => $pixelPerPoint,
            'outFileName' => $outFileName,
            'quality' => $quality,
            'outerFrame' => $outerFrame,
        );
        
        //Генерира QR изображение
        barcode_Generator::printImg('qr', $text, null, $params);
    }
    
    
    /**
     * Връща ключа за защита
     */
    public static function getProtectSalt($text, $pixelPerPoint = null, $outerFrame = null)
    {
        // "Почистваме" текста от pid, за да не зависи от него солта.
        $text = preg_replace('/[&\?]pid=[^&]+/i', '', $text);
        
        //Ако нямаме въведена стойност на параметрите, въвеждаме им стойности по подразбиране
        //За да може системата да работи коректно
        $outerFrame = $outerFrame ? $outerFrame : $outerFrame = 0;
        $pixelPerPoint = $pixelPerPoint ? $pixelPerPoint : $pixelPerPoint = 0;
        $text = $text ? $text : $text = '';
        
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
    
    
    /**
     * Помощна функция, която конвертира gd image към html table
     *
     * Оригиналната идея е на: http://sstaynov.com/posts/image-2-html-browser-abuse/
     */
    public function img2html($image, $zoom = 3)
    {
        $imgWidth = imagesx($image);
        $imgHeight = imagesy($image);
        
        $html .= '<table border="0" cellpadding="0" cellspacing="0">';
        
        for ($y = 0; $y < $imgHeight; $y++) {
            $html .= '<tr>';
            $haveTd = false;
            $counter = 0;
            
            for ($x = 0; $x < $imgWidth; $x++) {
                $pixel_index = imagecolorat($image, $x, $y);
                $rgbArr = imagecolorsforindex($image, $pixel_index);
                
                $color =
                
                str_pad(dechex($rgbArr['red']), 2, '0', STR_PAD_LEFT) .
                str_pad(dechex($rgbArr['green']), 2, '0', STR_PAD_LEFT) .
                str_pad(dechex($rgbArr['blue']), 2, '0', STR_PAD_LEFT);
                
                $c = round(($rgbArr['red'] + $rgbArr['red'] + $rgbArr['red']) / 3);
                
                // if($c>128) $color = 'ffffff'; else $color = '000000';
                
                if ($counter == 0) {
                    $prev_color = $color;
                    $counter++;
                } else {
                    if ($prev_color == $color) {
                        $counter++;
                    } else {
                        $haveTd = true;
                        $html .= '<td width="' . $zoom . '" height="' . 0 . '" style="border-bottom:' . $zoom . 'px solid #' .
                        $prev_color . '"' . ($counter > 1 ? ' colspan="' . $counter . '"' : '') . '></td>';
                        $prev_color = $color;
                        $counter = 1;
                    }
                }
            }
            
            if ($counter) {
                $haveTd = true;
                $html .= '<td width="' . $zoom . '" height="' . 0 . '" style="border-bottom:' . $zoom . 'px solid #' . $color . '"' . ($counter > 1 ? ' colspan="' . $counter  . '"' : '') . '></td>';
            }
            
            if (!$haveTd) {
                $html .= '<td width="' . $zoom . '" height="' . 0 . '" style="border-bottom:' . $zoom . 'px solid #' . $color . '"' .  ' colspan="' . $imgWidth  . '"'   . '></td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        return $html;
    }
}
