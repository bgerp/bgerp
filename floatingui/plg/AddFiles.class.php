<?php


/**
 * Клас 'floatingui_plg_AddFiles' - Добавя Floating UI към всяка HTML страница
 *
 *
 * @category  бгерп
 * @package   floatingui
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class floatingui_plg_AddFiles extends core_Plugin
{
    /**
     * Добавя файловете на библиотеката преди останалите
     *
     * @param core_ET  $et
     * @param mixed    $res
     * @param stdClass $files
     */
    public function on_BeforeAppendFiles($et, &$res, &$files)
    {
        $files->js = array_merge(floatingui_Floatingui::getFiles(), (array) $files->js);
        $files->css = array_merge((array) $files->css, floatingui_Floatingui::getCssFiles());
    }
}
