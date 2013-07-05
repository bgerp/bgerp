<?php



/**
 * class Ownerships
 *
 * Отговаря за текущото и миналото състояние на притежаването на RFID номера
 *
 *
 * @category  bgerp
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
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin,rfid';
    
    
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