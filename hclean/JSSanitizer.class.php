<?php


/**
 * Санитаризиране на HTML с javascript
 *
 * @category  vendors
 * @package   hclean
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
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
    public static function sanitizeHtml($tpl, $link)
    {
        // Подготвяме HTML'а
        $jsHtml = static::prepareHtml($link);

        // Вземаме скрипта, който санитаризира HTML' а
        $sanitizer = new ET(static::JSSanitizer());
        
        // Заместваме в плейсхолдера за JS със самия JS
        $tpl->replace($sanitizer, 'SANITIZEJS');
        
        // Генерираме уникално id за атрибута
        $uniqId = ht::getUniqId('emb');
        
        // Заместваме уникалното id
        $tpl->replace($uniqId, 'SANITIZEID');
        
        // Заместваме стринга, който ще санитаризирме в плейсхолдера
        $tpl->replace($jsHtml, 'SANITIZEHTML');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяме HTML
     *
     * @param string $htmlLink - Линк към HTML файла
     */
    public static function prepareHtml($htmlLink)
    {
        // Вземаме съдържанието на линка
        $content = static::getHtmlFromLink($htmlLink);
        
        $content = trim($content);
        
        $content = i18n_Charset::convertToUtf8($content, array(), true);
        
        // Конфигурационни константи
        $conf = core_Packs::getConfig('hclean');
        
        // Ако е зададено да се вкара CSS' а като inline
        if ($conf->HCLEAN_JSSANITIZER_PLACE_CSS_TO_INLINE == 'yes') {
            $content = csstoinline_ToInline::inlineCssFromHtml($content);
            $content = csstoinline_ToInline::inlineCssFromHtmlLink($content);
        }
        
        $content = i18n_Charset::convertToUtf8($content, '', true);
        
        $content = html_entity_decode($content);

        // Премахваме всичко коментари
        $content = preg_replace("/<!--.*?-->/si", '', $content);
        $content = preg_replace("/<!\\[.*?]>/si", '', $content);

        // Преобразуваме HTML' а в текст, който може да се използва в променливи на JS
        $jsHtml = static::htmlToJsText($content);
        
        return $jsHtml;
    }
    
    
    /**
     * Превръщаме HTML текста в JS текст, който да може да се използва в променлива
     */
    public static function htmlToJsText($html)
    { 
        $html = str_replace('/<![^>]+>/', '', $html);
 
        $jsHtml = preg_replace(array("/\r?\n/", "/\//", "/\r/"), array('\\n', "\/", ""), addslashes($html));
        
        return $jsHtml;
    }
    
    
    /**
     * Връща HTML'а от линка
     *
     * @param string $htmlLink - Линка до HTML файла
     */
    public static function getHtmlFromLink($htmlLink)
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
    public static function JSSanitizer()
    {
        $jsSanitize = sbf('hclean/jssanitize/html-css-sanitizer-minified.js', "'", true);
        $imagePolicy = static::getImagePolicyJs();
        
        // Скрипта за санитаризирана на HTML
        $script = " <script>
                        (function () {
                        if (typeof html_sanitize === 'function') {
                            init();
                        } else {
                            var script = document.createElement('script');
                            script.src = {$jsSanitize};
                            script.onload = init;
                            document.head.appendChild(script);
                        }
    				
    					function urlX(url) { return url; }
                        {$imagePolicy}
    					
    					function init() {
    						
                            standardPolicy = html.makeTagPolicy(urlX);
                            var sanitized = html.sanitizeWithPolicy('[#SANITIZEHTML#]', imagePolicy);
                    		
                    		// Добавяме линковете да се отварят в нов таб
                    		sanitized = sanitized.replace(/(<a\s)((.|\s)+?)(<\/a>)/ig, '$1 target=\"_blank\" $2$4');
                    		
                    		var emb = document.getElementById('[#SANITIZEID#]');
                            if (!emb) return;
                    		
                    		var emb = emb.contentDocument ? emb.contentDocument : (emb.contentWindow.document || emb.document);
                    		
                    		emb.open();
                    		emb.write(sanitized);
                    		emb.close();
                    	}
                        })();
    				</script>";
        
        return $script;
    }


    /**
     * Допуска само base64 растерни картинки в img src след стандартното пречистване.
     *
     * @return string - JavaScript политика за съществуващия HTML филтър
     */
    protected static function getImagePolicyJs()
    {
        return <<<'JS'
                        var standardPolicy;

                        /** Проверява формата и сигнатурата, без да зарежда външен ресурс. */
                        function embeddedImage(value) {
                            var uri = value.replace(/[\t\r\n ]/g, '');
                            var match = /^data:image\/(png|jpeg|gif|webp);base64,([a-z0-9+/]+={0,2})$/i.exec(uri);
                            if (!match || match[2].length % 4) return null;
                            var signatures = {
                                png: /^\x89PNG\r\n\x1a\n/,
                                jpeg: /^\xff\xd8\xff/,
                                gif: /^GIF8[79]a/,
                                webp: /^RIFF[\s\S]{4}WEBP/
                            };
                            try {
                                if (!signatures[match[1].toLowerCase()].test(atob(match[2].slice(0, 16)))) return null;
                            } catch (e) { return null; }
                            return uri;
                        }

                        /** Запазва всички останали ограничения на стандартната политика. */
                        function imagePolicy(tagName, attribs) {
                            var images = {}, i;
                            if (tagName === 'img') {
                                for (i = 0; i < attribs.length; i += 2) {
                                    if (attribs[i] === 'src') images[i] = embeddedImage(attribs[i + 1]);
                                }
                            }
                            var result = standardPolicy(tagName, attribs);
                            if (result && tagName === 'img') {
                                for (i = 0; i < result.attribs.length; i += 2) {
                                    if (result.attribs[i] === 'src' && images[i]) result.attribs[i + 1] = images[i];
                                }
                            }
                            return result;
                        }
JS;
    }
}
