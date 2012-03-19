<?php

defIfNot('XMPPHP_VERSION', '0.1rc2-r77');

/**
 * XMPP съобщения
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     XMPP
 */
class xmpphp_Example extends core_BaseClass
{
    
    
    /**
     * Интерфeйси
     */
    var $interfaces = 'bgerp_XmppIntf';
    
    /**
     * Изпраща Xmpp съобщение 
     */
    function send($user, $message)
    {
		
    }
    
    function act_Proba()
    {
    	require_once(XMPPHP_VERSION . "/XMPPHP/XMPP.php");
    	
		$conn = new XMPP('talk.google.com', 5222, 'username', 'password', 'xmpphp', 'gmail.com', $printlog=False, $loglevel=LOGGING_INFO);
		    	
    	$tpl = new ET("<li>[#RES#]");
    	$tpl->append('Hello!', 'RES');
    	
    	return $tpl;
    }
}
