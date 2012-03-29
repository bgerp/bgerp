<?php



/**
 * Урл за изпращане на СМС-и през Мобио
 */
defIfNot('MOBIO_URL','');


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
    function on_BeforeSend($mvc, &$res, $number, $message, $sender)
	{
		// Записваме в модела данните за СМС-а
        $rec = new stdClass();
        $rec->gateway = "Mobio";
        $rec->number = $number;
        $rec->message = $message;
        $rec->sender = $sender;
        $rec->status = 'sended';
        $rec->time = dt::verbal2mysql(); 
        
        // Ако константата за УРЛ-то не е зададена връщаме TRUE за да се пробва да бъде изпратен от друг плъгин
        if (MOBIO_URL == '') return TRUE;
        
        $tpl = new ET( MOBIO_URL );

        $tpl->placeArray(array('FROM' => urlencode($sender), 'PHONE' => urlencode($number), 'MESSAGE' => urlencode($message)));
        
        $url = $tpl->getContent();
        
        $ctx = stream_context_create(array('http' => array('timeout' => 5)));
        $res = file_get_contents($url, 0, $ctx);
        
        // Ако има грешка - веднага маркираме в SMS Мениджъра
        $res = explode(':', $res);
        if ($res[0] != 'OK') {
        	$rec->status = 'sendError';
			$mvc->save($rec);     
			      
            return TRUE;
        }
        $rec->status = 'sended';
        $rec->uid = $res[1];
        $mvc->save($rec);
        
        return FALSE;
    }
}
