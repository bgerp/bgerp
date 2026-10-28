<?php


/**
 * Клас 'csstoinline_CssToInline' - Вгражда целия CSS вътре в документа
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
class csstoinline_CssToInline extends core_Manager
{
    public $interfaces = 'csstoinline_ConverterIntf';
    
    
    public $title = 'CssToInline';
    
    
    /**
     * Вкарва външния CSS, като inline стил
     *
     * @param string $html - HTML текста
     * @param string $css  - CSS текста
     *
     * @return string @processedHTML - Обработения HTML
     */
    public function convert($html, $css)
    {
        $conf = core_Packs::getConfig('csstoinline');
        $version = $conf->CSSTOINLINE_CSSTOINLINE_VERSION ?? '1.0.3';
        expect(in_array($version, array('1.0.3', '2.2.2', '2.4.0'), true), 'Неподдържана версия на CssToInline');
        if ($version === '2.4.0') {
            $class = csstoinline_Setup::loadBundledLibrary('csstoinline') . 'CssToInlineStyles';
            return (new $class())->convert($html, $css);
        }
        if ($version === '2.2.2') {
            csstoinline_Setup::loadCssSelector();
            foreach (array('Css/Property/Property', 'Css/Property/Processor', 'Css/Rule/Rule', 'Css/Rule/Processor', 'Css/Processor', 'CssToInlineStyles') as $file) {
                require_once getFullPath('csstoinline/csstoinline/2.2.2/' . $file . '.php');
            }
            return (new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles())->convert($html, $css);
        }

        // За да не се разваля кирилицата
        $html = mb_encode_numericentity($html, array(0x80, 0x10FFFF, 0, 0x1FFFFF), 'UTF-8');
        
        // Пътя до кода
        $path = 'csstoinline/csstoinline/' . $version . '/css_to_inline_styles.php';
        
        // Вкарваме пакета
        require_once getFullPath($path);
        
        // Създаваме инстанция
        $cssToInlineStyles = new CSSToInlineStyles($html, $css);
        
        //Вкарва CSS във html, като inline
        $processedHTML = $cssToInlineStyles->convert();
        
        return $processedHTML;
    }
}
