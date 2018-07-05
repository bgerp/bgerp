<?php


/**
 * Клас 'csstoinline_CssToInline' - Вгражда целия CSS вътре в документа
 *
 * @category  vendors
 * @package   csstoinline
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
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
        // За да не се разваля кирилицата
        $html = mb_convert_encoding($html, 'html-entities', 'UTF-8');
        
        // Вземаме конфигурационните константи
        $conf = core_Packs::getConfig('csstoinline');
        
        // Пътя до кода
        $path = 'csstoinline/csstoinline/'. $conf->CSSTOINLINE_CSSTOINLINE_VERSION . '/css_to_inline_styles.php';
        
        // Вкарваме пакета
        require_once getFullPath($path);
        
        // Създаваме инстанция
        $cssToInlineStyles = new CSSToInlineStyles($html, $css);
        
        //Вкарва CSS във html, като inline
        $processedHTML = $cssToInlineStyles->convert();
        
        // Връщаме към UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'html-entities');
        
        return $processedHTML;
    }
}
