<?php


/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'csstoinline_1.0.3/css_to_inline_styles.php';


/**
 * Клас 'csstoinline_CssToInline' - Вгражда целия CSS вътре в документа
 *
 *
 * @category  vendors
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
        //Ако е файл, тогава взема съдържанието на файла
                if (is_file($html) || (URL::isValidUrl2($html))) {
            $html = file_get_contents($html);
        }
        
        //Ако е файл, тогава взема съдържанието на файла
                //Ако е линк, тогава не се взема съдържанието
                if (is_file($css)) {
            $css = file_get_contents($css);
        }
        
        $cssToInlineStyles = new CSSToInlineStyles($html, $css);
        
        //Вкарва CSS във html, като inline
                $processedHTML = $cssToInlineStyles->convert();
        
        return $processedHTML;
    }
}