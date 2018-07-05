<?php



/**
 * class Holders
 *
 * Менажира данните за обектите имащи отношение с rfid номерата - служители, валове, палети и др.
 *
 *
 * @category  bgerp
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Holders extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Картодържател';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,rfid';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,admin,rfid';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools2';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class(interface=rfid_HolderIntf)', 'caption=Тип притежател');
        $this->FLD('objectId', 'int', 'caption=Притежател');
    }
    
    //$holder = cls::getinterface('rfid_HolderIntf', $rec->classId);
}
