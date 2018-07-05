<?php


/**
 * Клас 'minify_Plugin'
 *
 * Плъгин за минифициране на Статичните Браузърни Файлове
 *
 *
 * @category  vendors
 * @package   minify
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class minify_Plugin extends core_Plugin
{
    
    /**
     * Минифициране на css и js файлове
     */
    public static function on_BeforeSaveFile($mvc, $res, &$content, $path, $isFullPath = null)
    {
        $ext = str::getFileExt($path);

        if ($ext == 'css') {
            $content = minify_Css::process($content);
        } elseif ($ext == 'js') {
            $content = minify_Js::process($content);
        }
    }
}
