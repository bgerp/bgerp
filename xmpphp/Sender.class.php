<?php



/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_VERSION', '0.1rc2-r77');




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
     * Интерфейси
     */
    public $interfaces = 'bgerp_XmppIntf';
    
    
    /**
     * Изпраща Xmpp съобщение
     */
    public static function send($user, $message)
    {
        $conf = core_Packs::getConfig('xmpphp');
        
        include(XMPPHP_VERSION . '/XMPPHP/XMPP.php');
        
        $conn = new XMPPHP_XMPP($conf->XMPPHP_SERVER, $conf->XMPPHP_PORT, $conf->XMPPHP_USER, $conf->XMPPHP_PASSWORD, 'xmpphp', $conf->XMPPHP_DOMAIN, $printlog = false, $loglevel = LEVEL_ERROR);
        
        try {
            $conn->connect();
            $conn->processUntil('session_start');
            $conn->presence();
            $conn->message($user, $message);
            $conn->disconnect();
        } catch (XMPPHP_Exception $e) {
            return($e->getMessage());
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_Test()
    {
        requireRole('admin');
        
        $res = xmpphp_Sender::send('user@gmail.com', 'Hello from BGERP');
        
        return $res;
    }
}
