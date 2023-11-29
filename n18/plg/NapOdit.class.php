<?php


/**
 * Клас 'n18_plg_NapOdit' - за премахване на правата за писане на napodit ролята
 *
 *
 * @category  bgplus
 * @package   n18
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class n18_plg_NapOdit extends core_Plugin
{
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add') || ($action == 'edit') || ($action == 'delete') || ($action == 'reject') || ($action == 'activate') || ($action == 'restore') || ($action == 'write')) {
            if (haveRole('napodit', $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
