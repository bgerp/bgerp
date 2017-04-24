<?php


/**
 * google_Translate1 - Реализирано на базата на google_plg_Translate
 *
 * Превод чрез Google Translate API v.1
 *
 * @category  vendors
 * @package   google
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class google_Translate1 
{


    /**
     * JavaScript фунцкията за превеждане
     */
    protected static $initJs = '
        function googleSectionalElementInit() {
            console.log("in");
            new google.translate.SectionalElement({
                sectionalNodeClassName: "goog-trans-section",
                controlNodeClassName: "goog-trans-control",
                background: "#f4fa58"
            }, "google_sectional_element");
        }
        ';


    /**
     * URL' то за превеждане
     */
    protected static $elementJsUrl = '//translate.google.com/translate_a/element.js?cb=googleSectionalElementInit&amp;ug=section&amp;hl=%s';
    
    
    /**
     * Шаблона, в който се добавя текста за превеждане
     */
    protected static $markupTpl = '
        <div class="goog-trans-section" data-live="">
            <div class="goog-trans-control"></div>
            <div class="goog-trans-text">%s</div>
        </div>
        ';
    
    
    /**
     * CSS
     */
    protected static $css = '
        .goog-trans-section {
            border: none;
            padding: 5px;
            margin: -5px;
            width: 100%;
        }
        
        .goog-trans-section .goog-trans-control {
            display: block;
            float: right;
            margin: 0.1em 0.5em;
        }
        
        @media print {
            .goog-trans-control, .goog-te-sectional-gadget-checkbox-text, .goog-te-sectional-gadget-link-text {
                display:none !important;
            }
        }
        
        #goog-gt-tt {
        	display: none !important;
		}
        ';
    
    
    /**
     * Връща JAVASCRIPT функцията за превеждане
     * 
     * @param boolean $escaped - Дали да се ескейпва
     * 
     * @return string $initJs
     */
    static function getInitJs($escaped=FALSE)
    {
        // Вземаме скрипта
        $initJs = static::$initJs;
        
        // Ако е зададено да се ескейпва
        if ($escaped) {
            
            // Ескейпваме фунцкцията
            $initJs = static::htmlToJsText($initJs);
        }
        $res = new ET($initJs);

        jquery_Jquery::runAfterAjax($res, 'google');

        return $res;
    }
    
    
    /**
     * Връща линка за превеждане на текста
     * 
     * @param string $lg - Езика, на който да се превежда
     * @param boolean $escaped - Дали да се ескейпва
     * 
     * @return string $jsUrl
     */
    static function getElementJsUrl($lg=FALSE, $escaped=FALSE)
    {
        // Ако не е подаден език
        if (!$lg) {
            
            // Вземаме текущия език
            $lg = core_Lg::getCurrent();
        }

        // Вземаме URL' тп
        $jsUrl = static::$elementJsUrl;
        
        // Заместваме езика в URL' то
        $jsUrl = sprintf($jsUrl, $lg);
        
        // Ако е задедено да се ескейпва
        if ($escaped) {
            
            // Обезопасяваме стринга
            $jsUrl = static::htmlToJsText($jsUrl);
        }
        
        return $jsUrl;
    }
    
    
    /**
     * Връща шаблона, в който ще се съдържа текста за превод
     * 
     * @param string $text - Текста, който ще се превежда
     * @param boolean $escaped - Дали да се ескейпва
     * 
     * @return string $markup
     */
    static function getMarkupTpl($text='', $escaped=FALSE)
    {
        // Вземаме шаблона, в който ще се намира стринга за превеждане
        $markup = static::$markupTpl;
        
        // Заместваме текста в стринга
        $markup = sprintf($markup, $text);
        
        
        // Ако е зададено да се ескейпва
        if ($escaped) {
            
            // Ескейпваме
            $markup = static::htmlToJsText($markup);
        }
        
        return $markup;
    }
    
    
    /**
     * Връща CSS' а
     * 
     * @param boolean $escaped - Дали да се ескейпва
     * 
     * @return string $css
     */
    static function getCss($escaped=FALSE)
    {
        // Вземаме CSS' а
        $css = static::$css;
        
        // Ако е задедено да се ескейпва
        if ($escaped) {
            
            // Ескейпваме стринга
            $css = static::htmlToJsText($css);
        }
        
        return $css;
    }
    
    
    /**
     * Превръщаме HTML текста в JS текст, който да може да се използва в променлива
     * 
     * @param string $html
     * 
     * @return string $initJs
     */
    static function htmlToJsText($html)
    {
        $jsHtml = preg_replace(array("/\r?\n/", "/\//"), array("\\n", "\/"), addslashes($html));
        
        return $jsHtml;
    }
}