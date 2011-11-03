<?php


/**
 * Клас 'email_UserInboxPlg' - Създава пощенска кутия след създаване на нов потребител
 *
 * @category   Experta Framework
 * @package    email
 * @author     Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 */
class email_UserInboxPlg extends core_Plugin 
{
	
	
	/**
     *  Извиква се след вкарване на запис в таблицата на модела users
     */
    function on_AfterSave($mvc, &$id, $rec)
    {	
    	$eRec = new stdClass();
    	$eRec->inCharge = $rec->id;
    	$eRec->access = "private";
    	$eRec->name = $rec->nick;
    	//$eRec->id = $rec->id;
    	
    	email_Inboxes::forceCoverAndFolder($eRec);
    }
}
	