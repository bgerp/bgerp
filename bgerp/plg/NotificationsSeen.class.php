<?php

/**
 * Плъгин за отмаркиране на прочетено известяване
 *
 *
 * @category   bgERP 2.0
 * @package    bgerp
 * @title:     Известявания
 * @author     Димитър Минеков <mitko@extrapack.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class bgerp_plg_NotificationsSeen extends core_Plugin
{

	function on_BeforeAction($mvc, &$res, $action)
	{
 		$id = request->get('id', 'int');
		$user = core_Users::getCurrent();
		
		bgerp_Notifications::markAsRead(array($mvc, $action, $id), $user);
	}
    
}