<?php


/**
 * Клас 'csstoinline_Emogrifier' - Вгражда целия CSS вътре в документа
 *
 * @category  vendors
 * @package   csstoinline
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class csstoinline_Emogrifier extends core_Manager
{
    
    
    /**
     * 
     */
    var $interfaces = 'csstoinline_ConverterIntf';
    
    
    /**
     * 
     */
    var $title = 'Emogrifier';
    
    
    /**
     * Вкарва външния CSS, като inline стил
     * 
     * @param string $html - HTML текста
     * @param string $css - CSS текста
     * 
     * @return string @processedHTML - Обработения HTML
     */
    static function convert($html, $css)
    {
        // Вземаме конфигурационните константи
        $conf = core_Packs::getConfig('csstoinline');
        
        // Пътя до кода
        $path = "csstoinline/emogrifier/". $conf->CSSTOINLINE_EMOGRIFIER_VERSION . "/emogrifier.php";
        
        // Вкарваме пакета
        require_once getFullPath($path);
        
        // Създаваме инстанция
        $Emogrifier = new Emogrifier($html, $css);
        
        // Създава проблеми при енкодинга на някои файлове
        // Запазваме енкодинга
//        $Emogrifier->preserveEncoding = TRUE;
        
        //Вкарва CSS във html, като inline
        $processedHTML = @$Emogrifier->emogrify();
        
        return $processedHTML;
    }
}