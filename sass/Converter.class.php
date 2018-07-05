<?php

/**
 * Вкарваме файловете необходими за работа с програмата.
 */
require_once 'phpsass/SassParser.php';


/**
 * Конвертира sass файлове в css
 *
 * @category  vendors
 * @package   sass
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sass_Converter
{
    
    
    /**
     * Конвертира sass в css файл
     *
     * @param string $file   - Линк към файла или стринг от стилове
     * @param string $syntax - Синтаксиса sass или scss
     * @param string $style  - nested, expanded, compact, compressed
     *
     * @return string - Конвертиран css стринг
     */
    public static function convert($file, $syntax = false, $style = 'nested')
    {
        // Опциите
        $options = array(
            'style' => $style,
            'cache' => false,
            'syntax' => $syntax,
            'debug' => false,
            'callbacks' => array(
                'warn' => false,
                'debug' => false,
            ),
        );
        
        // Инстанция на класа
        $parser = new SassParser($options);
        
        // Парсираме и връщаме резултата
        return $parser->toCss($file);
    }
}
