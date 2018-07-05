<?php


/**
 * Помощен клас за вкарване на CSS в inline
 *
 * @category  bgerp
 * @package   csstoinlin
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class csstoinline_ToInline
{

    
    /**
     * Вкарва CSS, който се намира в html файла между CSS таговете, като inline елементи
     * <style type=text/css> ... </style>
     */
    public static function inlineCssFromHtml($html)
    {
        //Шаблона за намиране на CSS '<stle type=text/css> ... </style>' в html документа
        $pattern = '/\<style type=\"*\'*\s*text\/css\"*\'*\s*\>([.\w\W]*?)\<\/style\>/i';
        preg_match_all($pattern, $html, $match);
        
        //Ако иам намерени съвпадения от CSS в style type="text/css"
        if (count($match[1])) {
            $valueAllCss = '';
            
            foreach ($match[1] as $value) {
                $valueAllCss .= $value . "\r\n";
            }
            
            //Заместваме CSS от <style type=text/css в inline стилове
            $html = static::cssToInline($html, $valueAllCss);
        }
        
        return $html;
    }
    
    
    /**
     * Вкарва CSS, който се намира в html файла, като линк към CSS файла
     */
    public static function inlineCssFromHtmlLink($html)
    {
        //Шаблона за намиране на CSS файл в html документа
        $pattern = '%<(link|style)(?=[^<>]*?(?:type="(text/css)"|>))(?=[^<>]*?(?:media="([^<>"]*)"|>))(?=[^<>]*?(?:href="(.*?)"|>))(?=[^<>]*(?:rel="([^<>"]*)"|>))(?:.*?</\1>|[^<>]*>)%si';
        preg_match_all($pattern, $html, $match);
        
        //Ако сме открили линка
        if (is_array($match[4])) {
            foreach ($match[4] as $value) {
                
                //Тримваме линка
                $value = trim($value);
                
                //Проверяваме дали е валидно URL или е файл
                if (is_file($value) || (core_Url::isValidUrl($value))) {
                    
                    //Проверява разширението дали е CSS
                    if (($dotPos = mb_strrpos($value, '.')) !== false) {
                        $ext = mb_strtolower(mb_substr($value, $dotPos + 1));
                        
                        if ($ext == 'css') {
                            
                            //Вземаме съдържанието на файла
                            $css = file_get_contents($value);
                            
                            ////Шаблона за намиране на CSS в html документа
                            $html = static::cssToInline($html, $css);
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
    public static function cssToInline($html, $css)
    {
        // Вземаме пакета
        $conf = core_Packs::getConfig('csstoinline');
        
        // Класа
        $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
        
        // Инстанция на класа
        $inst = cls::get($CssToInline);
        
        // Стартираме процеса
        $html = $inst->convert($html, $css);
        
        return $html;
    }
}
