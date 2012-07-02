<?php



/**
 * Клас 'rfid_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'rfid'
 *
 *
 * @category  bgerp
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class rfid_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
              
        $this->TAB('rfid_Events', 'Събития', 'admin,rfid');
        $this->TAB('rfid_Tags', 'Карти', 'admin,rfid');
        $this->TAB('rfid_Readers', 'Четци', 'admin,rfid');
        $this->TAB('rfid_Holders', 'Обекти', 'admin,rfid');
        $this->TAB('rfid_Ownerships', 'Собственици','admin,rfid');
        
        $this->title = 'Мониторинг';
    }
}