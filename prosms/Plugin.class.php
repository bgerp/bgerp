<?php



/**
 * Константи за изпращане на СМС-и през Pro-SMS
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
        
        sms_Sender::update($uid, $status);
    }
    
    
    /**
     * Изпраща SMS
     */
    function on_BeforeSend($mvc, &$res, $number, $message, $sender)
    {
        
        $tpl = new ET(PROSMS_URL);
        
        //        $uid = sms_Sender::add('ProSMS', $number, $message, $sender);
        
        $tpl->placeArray(array('USER' => urlencode(PROSMS_USER), 'PASS' => urlencode(PROSMS_PASS), 'FROM' => urlencode($sender), 'ID' => $uid, 'PHONE' => urlencode($number), 'MESSAGE' => urlencode($message)));
        
        $url = $tpl->getContent();
        
        $ctx = stream_context_create(array('http' => array('timeout' => 5)));
        $res = file_get_contents($url, 0, $ctx);
        
        // Дали има грешка при изпращането
        if ((int)$res != 0) {
            $res = FALSE;
            
            return TRUE;  // Ако някой друг може да изпрати СМС-а да заповяда
        }
        
        // Трябва да връща и уникален номер на СМС-а /хендлър/
        $res = TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Проба за изпращане на СМС-и през Про-СМС
     */
    function act_ProSMSTest()
    {
        return prosms_Sms::send('0887181813', 'Hello!!!', 'Proba BGERP');
    }
}
