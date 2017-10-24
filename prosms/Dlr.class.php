<?php



/**
 * Мениджър за изпратените SMS-и
 *
 *
 * @category  vendors
 * @package   prosms
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Мениджър за изпратените SMS-и
 */
class prosms_Dlr extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Обратна връзка от Про-SMS';
    
    
    /**
     * Обратна информация за SMS-а
     */
    function act_Dlr()
    {
        
        $uid = request::get('idd', 'varchar');
        $status = request::get('status', 'varchar');
        $code = request::get('code', 'varchar');
        
        expect(sms_Sender::fetch(array("#uid = '[#1#]'", substr($uid, -3))), "Невалидна заявка.");
        
        if ((int)$code !== 0) {
            $status = 'receiveError';
        } else {
            $status = 'received';
        }
        $id = (int) substr($uid, 0, strlen($uid) - 3);
        
        sms_Sender::update($id, $status);
    }
}