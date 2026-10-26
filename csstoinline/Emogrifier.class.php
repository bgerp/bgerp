<?php


/**
 * Клас 'csstoinline_Emogrifier' - Вгражда целия CSS вътре в документа
 *
 * @category  vendors
 * @package   csstoinline
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class csstoinline_Emogrifier extends core_Manager
{
    public $interfaces = 'csstoinline_ConverterIntf';
    
    
    public $title = 'Emogrifier';
    
    
    /**
     * Вкарва външния CSS, като inline стил
     *
     * @param string $html - HTML текста
     * @param string $css  - CSS текста
     *
     * @return string @processedHTML - Обработения HTML
     */
    public static function convert($html, $css)
    {
        // Вземаме конфигурационните константи
        $conf = core_Packs::getConfig('csstoinline');

        $version = $conf->CSSTOINLINE_EMOGRIFIER_VERSION ?? '2014.11.10';
        expect(in_array($version, array('2011.10.26', '2014.11.10', '3.0.0'), true), 'Неподдържана версия на Emogrifier');
        if ($version === '3.0.0') {
            csstoinline_Setup::loadCssSelector();
            foreach (array('HtmlProcessor/AbstractHtmlProcessor', 'Utilities/CssConcatenator', 'CssInliner', 'HtmlProcessor/HtmlPruner') as $file) {
                require_once getFullPath('csstoinline/emogrifier/3.0.0/' . $file . '.php');
            }
            $inliner = \Pelago\Emogrifier\CssInliner::fromHtml($html)->inlineCss($css);
            return \Pelago\Emogrifier\HtmlProcessor\HtmlPruner::fromDomDocument($inliner->getDomDocument())
                ->removeElementsWithDisplayNone()->render();
        }

        if ($version === '2011.10.26') {
            require_once getFullPath('csstoinline/emogrifier/2011.10.26/emogrifier.php');
            return @(new \Emogrifier($html, $css))->emogrify();
        }
        
        // Пътя до кода
        $path = 'csstoinline/emogrifier/' . $version . '/Emogrifier.php';
        
        // Вкарваме пакета
        require_once getFullPath($path);
        
        // Създаваме инстанция
        $Emogrifier = new \Pelago\Emogrifier($html, $css);
        
        //Вкарва CSS във html, като inline
        $processedHTML = @$Emogrifier->emogrify();
        
        return $processedHTML;
    }
}
