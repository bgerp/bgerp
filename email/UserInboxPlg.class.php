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
        cls::load('email_Inboxes');
        
        //Ако се добавя потребител
        if($rec->nick) {
            $eRec = new stdClass();
            $eRec->inCharge = $rec->id;
            $eRec->access   = "private";
            $eRec->name     = $rec->nick;
            $eRec->domain   = BGERP_DEFAULT_EMAIL_DOMAIN;
            
            //Добавяме полето имейл, необходима за създаване на корица
            $eRec->email = $rec->nick;
            
            email_Inboxes::forceCoverAndFolder($eRec);
        }
    }
}