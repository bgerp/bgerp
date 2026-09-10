<?php


/**
 * Клас 'floatingui_Floatingui' - Работа с библиотеката Floating UI
 *
 * Библиотеката се изнася в глобалното `window.FloatingUIDOM`
 *
 *
 * @category  bgerp
 * @package   floatingui
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link      https://floating-ui.com/docs/computePosition
 */
class floatingui_Floatingui
{
    /**
     * Добавя Floating UI към шаблона
     *
     * @param core_ET $tpl
     *
     * @return bool
     */
    public static function enable(&$tpl)
    {
        // Ако не е подаден обект, създаваме празен шаблон
        if (!is_object($tpl)) {
            $tpl = new ET();
        }

        // Ако не е шаблон
        if (!($tpl instanceof core_ET)) {

            return false;
        }

        foreach (static::getFiles() as $file) {
            $tpl->push($file, 'JS', true);
        }

        foreach (static::getCssFiles() as $file) {
            $tpl->push($file, 'CSS', true);
        }

        return true;
    }


    /**
     * JS файловете на пакета, в реда, в който трябва да се заредят
     *
     * @return array
     */
    public static function getFiles()
    {
        // `core` задължително преди `dom` - `dom` ползва глобалното `FloatingUICore`
        return array(static::getFilePath('core'), static::getFilePath('dom'), 'floatingui/js/Hints.js');
    }


    /**
     * CSS файловете на пакета
     *
     * @return array
     */
    public static function getCssFiles()
    {
        return array('floatingui/css/Hints.css');
    }


    /**
     * Пътя до файла на библиотеката
     *
     * @param string $file - `core` или `dom`
     *
     * @return string
     */
    public static function getFilePath($file)
    {
        $version = floatingui_Setup::get('VERSION');

        return "floatingui/{$version}/floating-ui.{$file}.umd.min.js";
    }
}
