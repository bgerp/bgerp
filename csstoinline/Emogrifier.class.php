<?php


/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'emogrifier_2011.10.26/emogrifier.php';


/**
 * Клас 'csstoinline_Emogrifier' - Вгражда целия CSS вътре в документа
 *
 *
 * @category  vendors
 * @package   csstoinline
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class csstoinline_Emogrifier
{
    
    
    /**
     * Вкарва външния CSS, като inline стил
     */
    function convert($html, $css)
    {
        //Ако е файл или линк, тогава взема съдържанието на файла
                if (is_file($html) || (URL::isValidUrl2($html))) {
            $html = file_get_contents($html);
        }
        
        //Ако е файл, тогава взема съдържанието на файла
                //Ако е линк, тогава не се взема съдържанието
                if (is_file($css)) {
            $css = file_get_contents($css);
        }
        
        $Emogrifier = new Emogrifier($html, $css);
        
        //Вкарва CSS във html, като inline
                $processedHTML = $Emogrifier->emogrify();
        
        return $processedHTML;
    }
}