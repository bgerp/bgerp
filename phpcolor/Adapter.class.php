<?php


/** Библиотека за работа с цветове
 *
 * @category  bgerp
 * @package   phpcolor
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class phpcolor_Adapter extends core_Mvc
{
    /** Зарежда избраната библиотека и запазва нормализирането на цветовете. */
    protected static function getColor($hexColor)
    {
        $version = phpcolor_Setup::get('VERSION');
        if ($version === '1.0.4') {
            require_once __DIR__ . '/1.0.4/Color.php';
            $class = 'Mexitek\\PHPColors\\Color';
        } else {
            require_once __DIR__ . '/0.3/Color.class.php';
            $class = 'Color';
        }

        $cObj = new color_Object($hexColor ?? '');
        $hexColor = $cObj->getHex('');

        try {
            return new $class($hexColor);
        } catch (ErrorException $e) {
            reportException($e);
            self::logErr($e->getMessage() . ' - ' . $hexColor);

            return false;
        }
    }


    /**
     * промяна на цвета
     *
     * @param $hexColor - цвят в hex представяне, който ще променяме
     * @param $type - име на функцията, която ще викаме
     * @param $ammount - интензивност на промяната
     * @param $mix - цвят, с който да смесваме
     */
    public static function changeColor($hexColor, $type = 'lighten', $ammount = 10, $mix = '#fff')
    {
        $myColor = self::getColor($hexColor);
        if (!$myColor) {
            return false;
        }
        
        switch ($type) {
            case 'lighten':
                
                return $myColor->lighten($ammount);
            
            case 'darken':
                
                return $myColor->darken($ammount);
            
            case 'gradient':
                return $myColor->getCssGradient($ammount);
            
            case 'mix':
                
                return  $myColor->mix($mix, $ammount);
            
            default:
                
                return $myColor;
        }
    }
    
    
    /**
     * проверка да дадения цвят е светъл или тъмен
     *
     * @param $hexColor - цвят в hex представяне, който ще променяме
     * @param $type - име на функцията, която ще викаме
     */
    public static function checkColor($hexColor, $type = 'light')
    {
        $myColor = self::getColor($hexColor);
        if (!$myColor) {
            return false;
        }
        
        switch ($type) {
            case 'light':
                
                return $myColor->isLight();
            
            case 'dark':
                
                return $myColor->isDark();
            
            default:
                
                return false;
        }
    }
}
