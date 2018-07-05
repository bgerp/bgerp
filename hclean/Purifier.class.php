<?php


/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'purifier4.3.0/HTMLPurifier.standalone.php';


/**
 * Папка за съхранение на временните файлове
 */
defIfNot('PURIFIER_TEMP_PATH', EF_TEMP_PATH . '/purifer');


/**
 * Клас 'hclean_Purifier' - Пречистване на HTML
 *
 *
 * @category  vendors
 * @package   hclean
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      https://github.com/bgerp/vendors/issues/10
 */
class hclean_Purifier
{
    
    /**
     * Изпълнява се при създаване на инстанция на класа.
     */
    //    function init()
    //    {
    //        $this->mkdir();
    //    }
    
    
    
    /**
     * Изчиства HTML кода от зловреден код (против XSS атаки)
     *
     * $html    string|link - HTML частта
     * $charset string      - Charset'a на файла
     * $css     string      - CSS, който искаме да вкараме в HTML файла
     * $force   boolean     - Ако е TRUE, тогава сваля файла от css линка в HTML файла
     *
     * @return $clear string - HTML файла, с inline CSS елементи
     */
    public static function clean($html, $charset = null, $css = null, $force = null)
    {
        //Вкарва CSS, който се намира в html файла между CSS таговете, като inline елементи
        $html = csstoinline_ToInline::inlineCssFromHtml($html);
        
        //Ако има подаден CSS файл, тогава го вкарваме, като inline елемент
        if ($css) {
            $html = csstoinline_ToInline::cssToInline($html, $css);
        }
        
        //Вкарва CSS, който се намира в html файла, като линк към CSS файла
        if ($force) {
            $html = csstoinline_ToInline::inlineCssFromHtmlLink($html);
        }
        
        //Настройваме purifier' а
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', PURIFIER_TEMP_PATH);
        // Винаги работик с конвертирани до UTF-8 текстове
        $config->set('Core.Encoding', 'UTF-8');
        
        $purifier = new HTMLPurifier($config);
        
        //Изчистваме HTML' а от зловреден код
        $clear = $purifier->purify($html);
        
        return $clear;
    }
    
    
    /**
     * Създава директорията нужна за работа на системата
     */
    public static function mkdir()
    {
        if (!is_dir(PURIFIER_TEMP_PATH)) {
            if (!mkdir(PURIFIER_TEMP_PATH, 0777, true)) {
                expect('Не може да се създаде директорията необходима за работа на HTML Purifier');
            }
        }
        
        return '<li>Успешно създадохте директорията: ' . PURIFIER_TEMP_PATH;
    }
}
