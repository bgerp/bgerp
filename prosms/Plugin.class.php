<?php


/**
 * SMS-и през Pro-SMS
 *
 *
 * @category  vendors
 * @package   prosms
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     SMS-и през Pro-SMS
 */
class prosms_Plugin extends core_Plugin
{
    
    
    /**
     * Изпраща SMS
     */
    function on_BeforeSend($mvc, &$res, $number, $message, $sender)
    {
    	$conf = core_Packs::getConfig('prosms');
    	
        // Записваме в модела данните за SMS-а
        $rec = new stdClass();
        $rec->gateway = "PRO-SMS";
        $rec->uid = str::getRand('aaa');
        $rec->number = $number;
        $rec->message = $message;
        $rec->sender = $sender;
        $rec->status = 'sended';
        $rec->time = dt::verbal2mysql();
        
        $mvc->save($rec);
        
        // Ако константата за УРЛ-то не е зададена връщаме TRUE за да се пробва да бъде изпратен от друг плъгин
        if ($conf->PROSMS_URL == '') return TRUE;
        
        $tpl = new ET($conf->PROSMS_URL);
        
        // По този начин образуваме уникалният номер на SMS-а, който изпращаме за идентификация
        $uid = "{$rec->id}" . "{$rec->uid}";
        
        $tpl->placeArray(array('USER' => urlencode($conf->PROSMS_USER), 'PASS' => urlencode($conf->PROSMS_PASS), 'FROM' => urlencode($rec->sender), 'ID' => $uid, 'PHONE' => urlencode($rec->number), 'MESSAGE' => urlencode($rec->message)));
        
        $url = $tpl->getContent();
        
        $ctx = stream_context_create(array('http' => array('timeout' => 5)));
        $res = file_get_contents($url, 0, $ctx);
        
        // Дали има грешка при изпращането
        if ((int)$res != 0) {
            // Маркираме в базата - грешка при изпращането.
            $rec->status = 'sendError';
            $mvc->save($rec);
            $res = FALSE;
            
            return TRUE;  // Ако някой друг може да изпрати SMS-а - да заповяда
        }
        
        // Всичко е ОК
        $res = TRUE;
        
        return FALSE;
    }
}
