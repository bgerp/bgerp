<?php



/**
 * Мениджър за изпратените SMS-и
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     СМС-и
 */
class bgerp_SmsLog extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_RowTools, plg_Sorting';
    
    
    /**
     * Заглавие
     */
    var $title = 'Изпратени СМС-и';
    
    
    /**
     * Права за запис
     */
    var $canWrite = 'bgerp, admin';
    
    
    /**
     * Права за четене
     */
    var $canRead = 'bgerp, admin';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 100;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('gateway', 'varchar(255)', 'caption=СМС шлюз');
        $this->FLD('number', 'varchar(255)', 'caption=Получател');
        $this->FLD('message', 'varchar(255)', 'caption=Съобщение');
        $this->FLD('sender', 'varchar(255)', 'caption=Изпращач');
        $this->FLD('status', 'enum(sended, error, unknown)', 'caption=Резултат');
        $this->FLD('time', 'datetime', 'caption=Време');
    }
    
    
    /**
     * Добавя запис в логовете
     */
    function add($gateway, $number, $message, $sender, $status)
    {
        $rec = new stdClass();
        $rec->gateway = $gateway;
        $rec->number = $number;
        $rec->message = $message;
        $rec->sender = $sender;
        $rec->status = $status;
        $rec->time = dt::verbal2mysql();
        
        bgerp_SmsLog::save($rec);
    }
    
    
    /**
     * Сортиране DESC - последния запис да е най-отгоре
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#time', 'DESC');
    }
    
    
    /**
     * Оцветяваме записите в зависимост от приоритета събитие
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $rowColors = array('sended' => '#ffffff',
            'unknown' => '#fff0f0',
            'error' => '#ffdddd'
        );
        
        // Променяме цвета на реда в зависимост от стойността на $row->statusAlert
        $row->ROW_ATTR['style'] .= "background-color: " . $rowColors[$rec->priority] . ";";
    }
    
    
    /**
     * Добавя филтър за изпратените SMS-и
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
    	
    }
}