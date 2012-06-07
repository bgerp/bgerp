<?php



/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_VERSION', '0.1rc2-r77');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_SERVER', 'talk.google.com');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_PORT', '5222');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_USER', '');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_PASSWORD', '');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_DOMAIN', 'gmail.com');


/**
 * XMPP съобщения
 *
 *
 * @category  vendors
 * @package   xmpphp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     XMPP
 */
class xmpphp_Sender extends core_Manager
{
    
    
    /**
     * Интерфeйси
     */
    var $interfaces = 'bgerp_XmppIntf';
    
    
    /**
     * Изпраща Xmpp съобщение
     */
    static function send($user, $message)
    {
        include (XMPPHP_VERSION . "/XMPPHP/XMPP.php");
        
        $conn = new XMPPHP_XMPP(XMPPHP_SERVER, XMPPHP_PORT, XMPPHP_USER, XMPPHP_PASSWORD, 'xmpphp', XMPPHP_DOMAIN, $printlog = False, $loglevel = LEVEL_ERROR);
        
        try {
            $conn->connect();
            $conn->processUntil('session_start');
            $conn->presence();
            $conn->message($user, $message);
            $conn->disconnect();
        } catch(XMPPHP_Exception $e) {
            return($e->getMessage());
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Test()
    {
        requireRole('admin');
        
        $res = xmpphp_Sender::send('ebh.ggl@gmail.com', 'Hello from BGERP');
        
        return $res;
    }
}