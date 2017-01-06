<?php



/**
 * Мениджър за изпратените SMS-и
 *
 *
 * @category  vendors
 * @package   mobio
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     История на SMS-ите
 */
class mobio_SmsDlr extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Обратна връзка от Mobio';
    
    
    /**
     * Обратна информация за SMS-а
     */
    function act_Dlr()
    {
        
        $uid = request::get('msgid', 'varchar');
        $oldStatus = request::get('oldstats', 'varchar');
        $number = request::get('tonum', 'varchar');
        $code = request::get('newstatus', 'varchar');
        
        expect($rec = sms_Sender::fetch(array("#uid = '[#1#]'", $uid)), "Невалидна заявка.");
        
        if ((int)$code !== 1) {
            $status = 'receiveError';
        } else {
            $status = 'received';
        }
        
        sms_Sender::update($rec->id, $status);
    }
}