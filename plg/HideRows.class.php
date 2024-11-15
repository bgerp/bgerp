<?php


/**
 * Скрива съответните полета за роли различни от посочената
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_HideRows extends core_Plugin
{


    /**
     * Render single
     *
     * @param core_Mvc $mvc
     * @param core_Et  $tpl
     * @param stdClass $data
     */
    public static function on_BeforeRenderSingle($mvc, &$res, $data)
    {
        setIfNot($mvc->hideRows, 'ip=debug, brid=debug');
        $hideRowsArr = arr::make($mvc->hideRows, true);
        foreach ($hideRowsArr as $field => $role) {
            if (!is_array($role)) {
                $role = str_replace('|', ',', $role);
            }
            if (!haveRole($role)) {
                unset($data->row->{$field});
            }
        }
    }
}
