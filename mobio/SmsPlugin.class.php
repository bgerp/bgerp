<?php

/**
 * Урл за изпращане на СМС-и през Мобио
 */

defIfNot('MOBIO_URL');

/**
 * SMS-и през Мобио
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
class mobio_SmsPlugin extends core_Plugin
{
    
    
    /**
     * Изпраща SMS 
     */
    function send($number, $message, $sender)
    {

		$tpl = new ET( MOBIO_URL );
		
		$uid = sms_Log::add('Mobio', $number, $message, $sender);
		
		$tpl->placeArray(array( 'FROM' => urlencode($sender), 'PHONE' => urlencode($number), 'MESSAGE' => urlencode($message), 'ID' => $uid));
		
		$url = $tpl->getContent();
		
		$ctx = stream_context_create(array('http' => array( 'timeout' => 5 )));
		$res = file_get_contents($url, 0, $ctx);
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
    function act_MobioTest()
    {
    	return sms_Mobio::send('359887181813', 'Hello from Mobio', 'Proba BGERP');
    }
}
