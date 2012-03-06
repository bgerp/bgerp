<?php

/**
 * Константи за изпращане на СМС-и през Pro-SMS
 */

defIfNot('PROSMS_URL');

defIfNot('PROSMS_USER');

defIfNot('PROSMS_PASS');

/**
 * SMS-и през Pro-SMS
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     SMS
 */
class sms_ProSMS extends core_BaseClass
{
    
    
    /**
     * Интерфeйси
     */
    var $interfaces = 'bgerp_SMSIntf';
    
    /**
     * Обратна информация за SMS-а
     */
    function act_Dlr()
    {
    	
    	$uid = request::get('idd', 'varchar');
		$status = request::get('status', 'varchar');
		$code = request::get('code', 'varchar');

		if ((int)$code !== 0) {
			$status = 'error';
		} else {
			$status = 'sended';
		} 
		
    	sms_Log::update($uid, $status);
    }    

    /**
     * Изпраща SMS
     */
    function send($number, $message, $sender)
    {
		
		$tpl = new ET( PROSMS_URL );
		
		/**
		 * @todo Определяме уникален номер на СМС-а
		 * 
		 */
		
		$uid = sms_Log::add('ProSMS', $number, $message, $sender);
		
		$tpl->placeArray(array( 'USER' => urlencode(PROSMS_USER), 'PASS' => urlencode(PROSMS_PASS), 'FROM' => urlencode($sender), 'ID' => $uid, 'PHONE' => urlencode($number), 'MESSAGE' => urlencode($message)));
		
		$url = $tpl->getContent();
		
		$ctx = stream_context_create(array('http' => array( 'timeout' => 5 )));
		//$res = file_get_contents($url, 0, $ctx);
		$res = 0;
		// Ако има грешка - веднага маркираме в Log-a
		if ((int)$res != 0) {
			sms_Log::update($uid, 'error');
			
			return FALSE;
		}
		
		return TRUE;
    }
    
    /**
     * 
     * Проба за изпращане на СМС-и през Про-СМС
     */
    function act_ProSMSTest()
    {
    	return sms_ProSMS::send('0887181813', 'Hello!!!', 'Proba BGERP');
    }
}
