<?php


/**
 * Плъгин за конвертиране на SASS файлве в CSS
 *
 * @category  vendors
 * @package   sass
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sass_Plugin extends core_Plugin
{
    
    
    /**
     * Прихваща извикването на AfterConvertSass и конвертира SASS към CSS
     */
    function on_AfterConvertSass($mvc, &$res, $file, $type)
    {
        
        // Конвертира
        $res = sass_Converter::convert($file, $type);
    }
}