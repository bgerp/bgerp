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
    function clean($html, $charset = NULL, $css = NULL, $force = NULL)
    {
        //Ако няма charset тогава го определяме
                if(!$charset) {
            $charset = self::detectCharset($html);
        }
        
        //Вкарва CSS, който се намира в html файла между CSS таговете, като inline елементи
                $html = self::inlineCssFromHtml($html);

        //Ако има подаден CSS файл, тогава го вкарваме, като inline елемент
                if ($css) {
            $html = self::cssToInline($html, $css);
        }
        
        //Вкарва CSS, който се намира в html файла, като линк към CSS файла
                if ($force) {
            $html = self::inlineCssFromHtmlLink($html);
        }
        
        //Настройваме purifier' а
                $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', PURIFIER_TEMP_PATH);
        $config->set('Core.Encoding', $charset);
        
        $purifier = new HTMLPurifier($config);
        
        //Изчистваме HTML' а от зловреден код
                $clear = $purifier->purify($html);
        
        return $clear;
    }
     
    
    /**
     * Намира кой е предпологаемия charset
     */
    function detectCharset($html)
    {
        $res = lang_Encoding::analyzeCharsets($html);
        //Взема charset' а, който е с най - голяма вероятност
                $charset = arr::getMaxValueKey($res->rates);
        
        return $charset;
    }
    
    
    /**
     * Вкарва CSS, който се намира в html файла между CSS таговете, като inline елементи
     * <style type=text/css> ... </style>
     */
    function inlineCssFromHtml($html)
    {
        //Шаблона за намиране на CSS '<stle type=text/css> ... </style>' в html документа
                $pattern = '/\<style type=\"*\'*\s*text\/css\"*\'*\s*\>([.\w\W]*?)\<\/style\>/i';
        preg_match_all($pattern, $html, $match);
        
        //Ако иам намерени съвпадения от CSS в style type="text/css"
        if(count($match[1])) {
            $valueAllCss = '';
            
            foreach ($match[1] as $value) {
                $valueAllCss .= $value . "\r\n";
            }
            
            //Заместваме CSS от <style type=text/css в inline стилове
            $html = self::cssToInline($html, $valueAllCss);    
        }
        
        return $html;
    }
    
    
    /**
     * Вкарва CSS, който се намира в html файла, като линк към CSS файла
     */
    function inlineCssFromHtmlLink($html)
    {
        //Шаблона за намиране на CSS файл в html документа
                $pattern = '%<(link|style)(?=[^<>]*?(?:type="(text/css)"|>))(?=[^<>]*?(?:media="([^<>"]*)"|>))(?=[^<>]*?(?:href="(.*?)"|>))(?=[^<>]*(?:rel="([^<>"]*)"|>))(?:.*?</\1>|[^<>]*>)%si';
        preg_match_all($pattern, $html, $match);
        
        //Ако сме отркили линка
                if (is_array($match[4])) {
            foreach ($match[4] as $value) {
                
                //Тримваме линка
                                $value = str::trim($value);
                
                //Проверяваме дали е валидно URL или е файл
                                if (is_file($value) || (URL::isValidUrl2($value))) {
                    
                    //Проверява разширението дали е CSS
                                        if (($dotPos = mb_strrpos($value, '.')) !== FALSE) {
                        $ext = mb_strtolower(mb_substr($value, $dotPos + 1));
                        
                        if ($ext == 'css') {
                            
                            //Вземаме съдържанието на файла
                                                        $css = file_get_contents($value);
                            
                            ////Шаблона за намиране на CSS в html документа
                                                        $html = self::cssToInline($html, $css);
                        }
                    }
                }
            }
        }
        
        return $html;
    }
    
    
    /**
     * Вкарва посочения css, в $html' а
     */
    function cssToInline($html, $css)
    {
        $html = csstoinline_CssToInline::convert($html, $css);
        
        return $html;
    }
    
    
    /**
     * Създава директорията нужна за работа на системата
     */
    function mkdir()
    {
        if(!is_dir(PURIFIER_TEMP_PATH)) {
            if(!mkdir(PURIFIER_TEMP_PATH, 0777, TRUE)) {
                expect('Не може да се създаде директорията необходима за работа на HTML Purifier');
            }
        }
        
        return "<li>Успешно създадохте директорията: " . PURIFIER_TEMP_PATH;
    }
}