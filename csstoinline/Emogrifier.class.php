<?php


/**
 * Клас 'csstoinline_Emogrifier' - Вгражда целия CSS вътре в документа
 *
 * @category  vendors
 * @package   csstoinline
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
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
        
        // Пътя до кода
        $path = 'csstoinline/emogrifier/'. $conf->CSSTOINLINE_EMOGRIFIER_VERSION . '/Emogrifier.php';
        
        // Вкарваме пакета
        require_once getFullPath($path);
        
        // Създаваме инстанция
        $Emogrifier = new \Pelago\Emogrifier($html, $css);
        
        //Вкарва CSS във html, като inline
        $processedHTML = @$Emogrifier->emogrify();
        
        return $processedHTML;
    }
}
