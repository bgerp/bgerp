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
 * @title     История на СМС-ите
 */
class sms_Sender extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_Sorting';
    
    
    /**
     * Интерфeйси
     */
    var $interfaces = 'bgerp_SMSIntf';
    
    
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
        $this->FLD('gateway', 'varchar(255)', 'caption=Шлюз');
        $this->FLD('uid', 'varchar(16)', 'caption=Хендлър');
        $this->FLD('number', 'varchar(255)', 'caption=Получател');
        $this->FLD('message', 'varchar(255)', 'caption=Съобщение');
        $this->FLD('sender', 'varchar(255)', 'caption=Изпращач');
        $this->FLD('status', 'enum(received=Получен, sended=Изпратен, receiveError=Грешка при получаване, sendError=Грешка при изпращане)', 'caption=Резултат, column=none');
        $this->FLD('time', 'datetime', 'caption=Време');
    }
    
    
    /**
     * Обновява запис в логовете
     * Използва се от изпращачите за обновяване на състоянието
     */
    function update($uid, $status)
    {
        $rec = new stdClass();
        $rec->id = (int) substr($uid, 0, strlen($uid) - 3);
        $rec->status = $status;
        
        sms_Sender::save($rec);
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
        $rowColors = array(
            'received'        => '#a0ffa0',
            'sended'        => '#e0ffa0',
            'receiveError'    => '#ffdddd',
            'sendError'        => '#ffdddd'
        );
        
        // Променяме цвета на реда в зависимост от стойността на $row->status
        $row->ROW_ATTR['style'] .= "background-color: " . $rowColors[$rec->status] . ";";
    }
    
    
    /**
     * Добавя филтър за изпратените SMS-и
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        
        $data->listFilter->showFields = 'status';
        
        $data->listFilter->toolbar->addSbBtn('Филтър');
        
        $data->listFilter->view = 'horizontal';
        
        $rec = $data->listFilter->input();
        
        if($rec->status) {
            $data->query->where("#status = '{$rec->status}'");
        }
    }
    
    
    /**
     * 
     * Проба за изпращане на СМС-и през Про-СМС
     */
    function act_Test()
    {
    	requireRole('admin');
    	
    	return sms_Sender::send('359884340143', '0899130110 - Aksinia Cvetanova', 'Proba BGERP');
    }    
}