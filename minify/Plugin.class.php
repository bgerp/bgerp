<?php


/**
 * Клас 'minify_Plugin'
 *
 * Плъгин за минифициране на Статичните Браузърни Файлове
 *
 *
 * @category  vendors
 * @package   minify
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class minify_Plugin extends core_Plugin
{
    /**
     * Минифициране на css и js файлове
     */
    public static function on_BeforeSaveFile($mvc, $res, &$content, $path, $isFullPath = null)
    {
        // Официалният TinyMCE 8 build използва синтаксис, който JSMin/JShrink повреждат.
        if (preg_match('#(?:^|/)tinymce/8\.10\.1/.*\.(?:js|css)$#', $path ?? '')) {
            return;
        }

        $ext = str::getFileExt($path);

        try {
            if ($ext == 'css') {
                $content = minify_Css::process($content);
            } elseif ($ext == 'js') {
                $content = minify_Js::process($content);
            }
        } catch (core_exception_Redirect $t) {
            throw $t;
        } catch (Exception $t) {
            reportException('Грешка при minify', $t);
        } catch (Error $t) {
            reportException('Грешка при minify', $t);
        } catch (Throwable $t) {
            reportException('Грешка при minify', $t);
        }
    }
}
