<?php

/**
 *  class Ownerships
 *  
 *	Отговаря за текущото и миналото състояние на притежаването на RFID номера
 */
class rfid_Ownerships extends core_Manager {
    
    var $title = 'Притежания';
    
    var $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('holderId','int','caption=Притежател');
        $this->FLD('tagId','int','caption=rfid');
        $this->FLD('startOn','datetime','caption=Притежание->от');
        $this->FLD('endOn','datetime','caption=Притежание->до');
    }
}