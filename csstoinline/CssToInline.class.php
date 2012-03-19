<?php


/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'csstoinline_1.0.3/css_to_inline_styles.php';


/**
 * Клас 'csstoinline_CssToInline' - Вгражда целия CSS вътре в документа
 *
 *
 * @category  all
 * @package   csstoinline
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class csstoinline_CssToInline
{
    
    
    /**
     * Вкарва външния CSS, като inline стил
     */
    function convert($html, $css)
    {
        $cssToInlineStyles = new CSSToInlineStyles($html, $css);
        
        //Вкарва CSS във html, като inline
        $processedHTML = $cssToInlineStyles->convert();
        
        return $processedHTML;
    }
}