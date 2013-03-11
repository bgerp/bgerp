<?php



/**
 * Клас 'core_Converter'
 *
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Converter extends core_BaseClass
{
    
    
    /**
     * Конвертира sass файловете в css
     * 
     * @param string $file - Файла
     * @param string $type - Типа
     */
    static function convertSass($file, $type='scss')
    {
        // Инстанция на самия клас
        $me = cls::get('core_Converter');

        // Извикваме функцията
        $converted = $me->invoke('AfterConvertSass', array(&$res, $file, $type));
        
        // Ако няма такава функция
        if ($converted === -1) {
            
            // Записваме в лога
            core_Logs::log('Няма функция за конвертиране на SCSS файлове');
        }
        
        // Връщаме конвертирания CSS
        return $res;
    }
}