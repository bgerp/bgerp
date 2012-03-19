<?php



/**
 * class Holders
 *
 * Менажира данните за обектите имащи отношение с rfid номерата - служители, валове, палети и др.
 *
 *
 * @category  all
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Holders extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Картодържател';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('classId', 'class(interface=rfid_HolderIntf)', 'caption=Тип притежател');
        $this->FLD('objectId', 'int', 'caption=Притежател');
    }
    
    //$holder = cls::getinterface('rfid_HolderIntf', $rec->classId);
}