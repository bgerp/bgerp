<?php


/**
 * class Ownerships
 *
 * Отговаря за текущото и миналото състояние на притежаването на RFID номера
 *
 *
 * @category  bgerp
 * @package   rfid
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rfid_Assignments extends embed_Manager
{

    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface = 'rfid_HolderIntf';

    /**
     * Заглавие
     */
    public $title = 'Присвоявания';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,rfid';
    
    
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
     * Полета за листовия изглед
     */
    public $listFields = 'driverClass=Притежател,tag,startOn,endOn,createdOn,createdBy';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('tag', 'varchar(64)', 'caption=Таг,mandatory');
        $this->FLD('startOn', 'datetime', 'caption=Присвояване->От');
        $this->FLD('endOn', 'datetime(defaultTime=23:59:59)', 'caption=Присвояване->До');
    }
    
}
