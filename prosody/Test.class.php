<?php


/**
 * Клас 'prosody_Test' - тестване на RESTful API-то на admin_rest
 *
 *
 * @category  bgerp
 * @package   prosody
 *
 * @author    Dimitar Minekov <mitko@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class prosody_Test extends core_Manager
{
    public function act_Default()
    {
        requireRole('admin');
        
        //$res .= "Delete roster result: " . self::deleteRoster("dimitar_minekov");
        $res .= '<br>';
        
        //$res .= self::getRoster("dimitar_minekov");
        $res .= '<br>';
        
        //$res .= self::getRoster("mitko_virtual");
        $res .= '<br>';
        
        //$res .= "Add roster result: " . self::addRoster("dimitar_minekov", array("contact" => "mitko_virtual@jabber.bags.bg"));
        //         $res .= "<br>";
        //$res .= "Add roster result: " . self::addRoster("mitko_virtual", array("contact" => "mitko_mob@jabber.bags.bg"));
        $res .= '<br>';
        $res .= '<pre>' . print_r(prosody_RestApi::getConnectedUsers(), true) . '</pre>';
        
        //bp($res);
        return ($res);
    }
}
