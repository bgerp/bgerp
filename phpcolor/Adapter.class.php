<?php


/**
 * Версията на програмата
 */
defIfNot("PHPCOLOR_VERSION", '0.3');



/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once PHPCOLOR_VERSION . '/Color.class.php';


/** Библиотека за работа с цветове
 * 
 * @category  bgerp
 * @package   phpcolor
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class  phpcolor_Adapter extends core_Mvc
{    

    /**
     * промяна на цвета
     * @param $hexColor - цвят в hex представяне, който ще променяме
     * @param $type - име на функцията, която ще викаме
     * @param $ammount - интензивност на промяната
     * @param $mix - цвят, с който да смесваме
     */
    static function changeColor($hexColor, $type='lighten', $ammount = 10, $mix = '#fff')
    {
        $cObj = new color_Object($hexColor);

        $hexColor = $cObj->getHex('');

        try {
            $myColor = new Color($hexColor);
        } catch (ErrorException $e) {
            reportException($e);
            self::logErr($e->getMessage() . ' - ' . $hexColor);
            
            return FALSE;
        }
    	
    	switch ($type){
    		case 'lighten':
    			return $myColor->lighten($ammount);
    			
    		case 'darken':
    			return $myColor->darken($ammount);
    			
    		case 'gradient':
    			$myColor->makeGradient($ammount);
    			return $myColor->getCssGradient($myColor);
    			
    		case 'mix':
    			return  $myColor->mix($mix, $ammount);
    		
    		default :
    			return $myColor;
    	}
    }
    
    /**
     * проверка да дадения цвят е светъл или тъмен
     * @param $hexColor - цвят в hex представяне, който ще променяме
     * @param $type - име на функцията, която ще викаме
     */
    static function checkColor($hexColor, $type='light')
    {
        $cObj = new color_Object($hexColor);

        $hexColor = $cObj->getHex('');
 
        try {
    	    $myColor = new Color($hexColor);
        } catch (ErrorException $e) {
            reportException($e);
            self::logErr($e->getMessage() . ' - ' . $hexColor);
            
            return FALSE;
        }
    	
    	switch ($type){
    		case 'light':
    			return $myColor->isLight();
    			 
    		case 'dark':
    			return $myColor->isDark();
    			
    		default :
    			return FALSE;
    	}
    }
}
