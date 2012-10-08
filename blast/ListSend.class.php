<?php 


/**
 * Лог на изпращаните писма
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_ListSend extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Лог на изпращаните писма";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, blast';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    var $canBlast = 'admin, blast';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper, plg_Sorting, plg_State';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'emailId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'listDetailId, sended, state';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('listDetailId', 'key(mvc=blast_ListDetails, select=key)', 'caption=Имейл');
        $this->FLD('emailId', 'key(mvc=blast_Emails, select=subject)', 'caption=Бласт');
        $this->FLD('sended', 'datetime', 'caption=Изпратено на, input=none');
        
        $this->setDbUnique('listDetailId,emailId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($rec->state != 'stopped') {
			
            // Бутон за спиране
			$row->state = HT::createBtn('Спиране', array($mvc, 'stop', $rec->id, 'ret_url' => TRUE));
        } else {
            
            // Бутон за активиране
            $row->state = HT::createBtn('Активиране', array($mvc, 'activate', $rec->id, 'ret_url' => TRUE));
        }
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        // id' то на записа
        $id = Request::get('id');
        $id = $this->db->escape($id);
        
        // Очакваме да има такъв запис
        $rec = $this->fetch($id);
        expect($rec, 'Няма такъв запис.');
        
        // Очакваме да имаме права за записа
        $this->requireRightFor('single', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->state = 'stopped';
        $this->save($nRec);
        
        return new Redirect(getRetUrl());
    }
    
    
    /**
     * Екшън за активиране
     */
    function act_Activate()
    {
        // id' то на записа
        $id = Request::get('id');
        $id = $this->db->escape($id);
        
        // Очакваме да има такъв запис
        $rec = $this->fetch($id);
        expect($rec, 'Няма такъв запис.');
        
        // Очакваме да имаме права за записа
        $this->requireRightFor('single', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->state = 'active';
        $this->save($nRec);
        
        return new Redirect(getRetUrl());
    }
    
    
}
