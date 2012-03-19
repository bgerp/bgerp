<?php



/**
 * class Ownerships
 *
 * Отговаря за текущото и миналото състояние на притежаването на RFID номера
 *
 *
 * @category  all
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Ownerships extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Притежания';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('holderId', 'int', 'caption=Притежател');
        $this->FLD('tagId', 'int', 'caption=rfid');
        $this->FLD('startOn', 'datetime', 'caption=Притежание->от');
        $this->FLD('endOn', 'datetime', 'caption=Притежание->до');
    }
}