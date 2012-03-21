<?php

/**
 * Константи за изпращане на СМС-и през Pro-SMS
 */

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_URL');

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_USER');

/**
 * @todo Чака за документация...
 */
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
class prosms_Plugin extends core_Plugin
{
    
    /**
     * Изпраща SMS
     */
    function on_BeforeSend($mvc, &$res, $number, $message, $sender)
    {
		// Записваме в модела данните за СМС-а
        $rec = new stdClass();
        $rec->gateway = "PRO-SMS";
        $rec->uid = str::getRand('ddd');
        $rec->number = $number;
        $rec->message = $message;
        $rec->sender = $sender;
        $rec->status = 'sended';
        $rec->time = dt::verbal2mysql(); 
        
        $mvc->save($rec);
        
		$tpl = new ET( PROSMS_URL );
		
		// По този начин образуваме уникалният номер на СМС-а, който изпращаме за идентификация
		$uid = "{$id}" . "{$rec->uid}";
		
		$tpl->placeArray(array( 'USER' => urlencode(PROSMS_USER), 'PASS' => urlencode(PROSMS_PASS), 'FROM' => urlencode($rec->sender), 'ID' => $uid, 'PHONE' => urlencode($rec->number), 'MESSAGE' => urlencode($rec->message)));
		
		$url = $tpl->getContent();
		
		$ctx = stream_context_create(array('http' => array( 'timeout' => 5 )));
		$res = file_get_contents($url, 0, $ctx);

		// Дали има грешка при изпращането
		if ((int)$res != 0) {
			// Маркираме в базата - грешка при изпращането.
			$rec->status = 'sendError';
			$mvc->save($rec);
			$res = FALSE;
			
			return TRUE; // Ако някой друг може да изпрати СМС-а - да заповяда
		}
		// Всичко е ОК
		$res = TRUE;
		
		return FALSE;
        
    }
}
