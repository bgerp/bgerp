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
 * @category  all
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
     * Необходими плъгини и външни мениджъри
     */
    var $loadList = 'rfid_Tags,rfid_Readers,plg_RefreshRows,rfid_Wrapper,plg_Created';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Обща информация
        $this->FLD('holderId', 'key(mvc=rfid_Holders)', 'caption=Картодържател');
        $this->FLD('tagId', 'key(mvc=rfid_Tags,select=name)', 'caption=Карта');
        $this->FLD('readerId', 'key(mvc=rfid_Readers,select=name)', 'caption=Четец');
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
