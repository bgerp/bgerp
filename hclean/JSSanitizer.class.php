<?php


/**
 * Санитаризиране на HTML с javascript
 *
 * @category  vendors
 * @package   hclean
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hclean_JSSanitizer extends core_Manager
{
    
    
	/**
     * Санитаризира HTML линка
     * 
     * @placeholder core_Et - SANITIZEJS - JS който ще се изпълнява
     * @placeholder core_Et - SANITIZEID - id' то на елемента
     * 
     * @return core_ET - Шаблон със санитаризиран текст
     */
    static function sanitizeHtml($tpl, $link)
    {
        // Подготвяме HTML'а
        $jsHtml = static::prepareHtml($link);
        
        // Вземаме скрипта, който санитаризира HTML' а
        $sanitizer = new ET(static::JSSanitizer());
        
        // Заместваме в плейсхолдера за JS със самия JS
        $tpl->replace($sanitizer, 'SANITIZEJS');
        
        // Генерираме уникално id за атрибута
        $uniqId = core_Os::getUniqId('emb');
        
        // Заместваме уникалното id
        $tpl->replace($uniqId, 'SANITIZEID');
        
        // Заместваме стринга, който ще санитаризирме в плейсхолдера
        $tpl->replace($jsHtml, 'SANITIZEHTML');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяме HTML
     * 
     * @param link $htmlLink - Линк към HTML файла
     */
    static function prepareHtml($htmlLink)
    {
        // Вземаме съдържанието на линка
        $content = static::getHtmlFromLink($htmlLink);
        
        // Конфигурационни константи
        $conf = core_Packs::getConfig('hclean');        
        
        // Ако е зададено да се вкара CSS' а като inline
        if ($conf->HCLEAN_PLACE_CSS_TO_INLINE == 'yes') {
            $content = csstoinline_ToInline::inlineCssFromHtml($content);
            $content = csstoinline_ToInline::inlineCssFromHtmlLink($content);
        }
        
        // Преобразуваме HTML' а в текст, който може да се използва в променливи на JS
        $jsHtml = static::htmlToJsText($content);
        
        return $jsHtml;
    }
    
    
    /**
     * Превръщаме HTML текста в JS текст, който да може да се използва в променлива
     */
    static function htmlToJsText($html)
    {
        $jsHtml = preg_replace("/\r?\n/", "\\n", addslashes($html));
        
        return $jsHtml;
    }
    
    
    /**
     * Връща HTML'а от линка
     * 
     * @param string $htmlLink - Линка до HTML файла
     */
    static function getHtmlFromLink($htmlLink)
    {
        // Извличаме съдържанието на линка
        $content = @file_get_contents($htmlLink);
        
        return $content;
    }
    
    
    /**
     * Скрипта, който санитаризира HTML' а
     * 
     * @placeholder core_Et - SANITIZEHTML - HTML'а който ще се санитаризира
     * @placeholder core_Et - SANITIZEID - id' то на елемента
     */
    static function JSSanitizer()
    {
        // Скрипта за санитаризирана на HTML
        $script = " <script src='http://caja.appspot.com/html-css-sanitizer-minified.js'></script>
                				
    				<script>
    					
    					init();
    				
    					function urlX(url) { return url }
    					
    					function init() {
    						
                    		var sanitized = html_sanitize('[#SANITIZEHTML#]', urlX);
                    		
                    		var emb = document.getElementById('[#SANITIZEID#]');
                    		
                    		emb.contentDocument.write(sanitized);
                    	}
    				</script>";
        
        return $script;
    }
}