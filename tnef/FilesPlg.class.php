<?php


/**
 * Декодиране на tnef файлове
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tnef_FilesPlg extends core_Plugin
{
    /**
     * Преди записване на файловете
     *
     * @param core_Master $mvc
     * @param array       $res
     * @param string      $fileHnd
     */
    public function on_AfterGetFiles($mvc, &$res, $fileHnd)
    {
        $res = tnef_Decode::decode($fileHnd);
    }
}
