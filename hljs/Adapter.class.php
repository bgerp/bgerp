<?php


/**
 * Оцветяването на кода
 *
 * @category  vendors
 * @package   hljs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hljs_Adapter
{
    
    
    /**
     * Активираме оцветяването на кода
     *
     * @param string $style - CSS'а, който да се използва
     */
    public static function enable($style = 'googlecode')
    {
        // Създаваме шаблона
        $tpl = new ET();
        
        // Добавяме нужните елементи на шаблона
        static::enableHlJs($tpl, $style);
        
        return $tpl;
    }

    
    /**
     * Добавям нужните елементи на шаблона
     *
     * @param core_Et $tpl   - Шаблона, към който ще се добавя
     * @param string  $style - CSS'а, който да се използва
     */
    public static function enableHlJs($tpl, $style = 'default')
    {
        $conf = core_Packs::getConfig('hljs');
        
        // CSS fajla
        $css = 'hljs/' . $conf->HLJS_VERSION . "/styles/{$style}.css";
        
        // Ако стила не е по подразбиране
        if ($style != 'default') {
            
            // Вземаме пътя до файла
            if (!getFullPath($css)) {
                
                // Ако няма такъв файл, използваме стила по подразбиране
                $css = 'hljs/' . $conf->HLJS_VERSION . '/styles/default.css';
            }
        }

        // Добавяме CSS
        $tpl->push($css, 'CSS');

        // Добавяме JS
        $tpl->push('hljs/' . $conf->HLJS_VERSION . '/highlight.pack.js', 'JS');
        jquery_Jquery::run($tpl, 'runHljs();', true);
        jquery_Jquery::runAfterAjax($tpl, 'runHljs');
    }
}
