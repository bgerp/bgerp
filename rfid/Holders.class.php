<?php

/**
 *  class Holders
 *  
 *	Менажира данните за обектите имащи отношение с rfid номерата - служители, валове, палети и др. 
 */

class rfid_Holders extends core_Manager {
    
    var $title = 'Картодържател';
    
    var $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('classId','class(interface=rfid_HolderIntf)','caption=Тип притежател');
        $this->FLD('objectId','int','caption=Притежател');
    }
    
    //$holder = cls::getinterface('rfid_HolderIntf', $rec->classId);
}