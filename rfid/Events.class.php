<?php



/**
 * class Events
 *
 * Тук се съхраняват постъпващите събития.
 * Времето на събитието се взима от четеца.
 * Извършва се конвертиране към MySql формат на времето, ако е необходимо.
 * Запазва се и информация за притежателя на rfid номера към момента на събитието,
 * видът на събитието, както и евентуално други параметри ако е необходимо.
 *
 *
 * @category  bgerp
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Events extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Събития';
    
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    var $refreshRowsTime = 5000;

    
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
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,rfid';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin,rfid';
    
    
    /**
     * Необходими плъгини и външни мениджъри
     */
    var $loadList = 'rfid_Tags,rfid_Readers,plg_RefreshRows,rfid_Wrapper,plg_Created';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Обща информация
        $this->FLD('holderId', 'key(mvc=rfid_Holders,select=objectId)', 'caption=Картодържател');
        $this->FLD('tagId', 'key(mvc=rfid_Tags,select=rfid10d)', 'caption=Карта');
        $this->FLD('readerId', 'key(mvc=rfid_Readers,select=title)', 'caption=Четец');
        $this->FLD('time', 'datetime', 'caption=Време');
        $this->FLD('action', 'varchar(16)', 'caption=Действие');
        $this->FLD('params', 'varchar(32)', 'caption=Други');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareData($mvc, &$res, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
        $data->toolbar = NULL;
    }
}
