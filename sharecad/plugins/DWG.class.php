<?php


/**
 * Разглеждане на DWG файлове
 *
 * @category  bgerp
 * @package   sharecad
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sharecad_plugins_DWG extends core_Plugin
{

    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc::$convertToPng = false;
    }




    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'preview') {

            Mode::set('wrapper', 'page_Empty');

            if (mode::is('screenMode', 'narrow')) {
//                $thumbWidth = fileman_Setup::get('PREVIEW_WIDTH_NARROW');
                $thumbHeight = fileman_Setup::get('PREVIEW_HEIGHT_NARROW');
            } else {
//                $thumbWidth = fileman_Setup::get('PREVIEW_WIDTH');
                $thumbHeight = fileman_Setup::get('PREVIEW_HEIGHT');
            }

            $res = sharecad_View::getFrame('cSxXYz', array('id' => 'imgIframe', 'class' => 'webdrvIframe', 'style' => "min-width: 100%; height: {$thumbHeight}px;"));

            if (!$res) {
                $res = 'Възникна грешка при визуализацията на файла.';
            }

            return false;
        }
    }
}
