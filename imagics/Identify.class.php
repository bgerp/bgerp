<?php


/**
 * Работа с identify от пакета на ImageMagic
 *
 * @category  vendors
 * @package   imagics
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class imagics_Identify extends core_Plugin
{
    
    
    /**
     * Използва identify от пакета на ImageMagic за определяне на разширението на файла от подаден път
     *
     * @param core_Mvc $mvc
     * @param string   $ext  - Откритото разширение за файла
     * @param string   $path - Пътя до файла
     */
    public function on_IdentifyFileExt($mvc, &$ext, $path)
    {
        // Очакваме да е валиден път
        expect(fileman::isCorrectPath($path));
        
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('imagics');
        
        $identifyCmd = $conf->IMAGICS_IDENTIFY_FILE_COMMAND;
        
        $identifyCmd = escapeshellcmd($identifyCmd);
        
        // Изпълняваме командата
        $ext = exec("{$identifyCmd} -format \"%m\" \"{$path}\"", $a, $b);
        
        // Разширението в долен регистър
        $ext = strtolower($ext);
    }
}
