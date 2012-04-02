<?php



/**
 * Плъгин за от маркиране на прочетено известяване
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Известявания
 */
class bgerp_plg_NotificationsSeen extends core_Plugin
{
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        $id = request->get('id', 'int');
        $user = core_Users::getCurrent();
        
        bgerp_Notifications::markAsRead(array($mvc, $action, $id), $user);
    }
}