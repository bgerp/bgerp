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
    var $listFields = 'listDetailId, sentOn, state';
    
    
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
        $this->FLD('groupDetailId', 'int', 'caption=Група');
        $this->FLD('emailId', 'key(mvc=blast_Emails, select=subject)', 'caption=Бласт');
        $this->FLD('sentOn', 'datetime', 'caption=Изпратено на, input=none, oldFieldName=sended');
        
        $this->setDbUnique('listDetailId, groupDetailId, emailId');
    }
    
    function on_AfterPrepareListFields($mvc, $data)
    {
        if (!$data->masterData->rec->listId) {
            $data->listFields = arr::make($data->listFields);
            unset($data->listFields['listDetailId']);
            array_unshift($data->listFields, 'groupDetailId');
            $data->listFields['groupDetailId'] = 'Група';
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if (!$data->masterData->rec->listId) {
            
            if ($data->masterData->rec->group == 'company') {
                $class = 'crm_Companies';
            } else {
                $class = 'crm_Persons';
            }
            
            foreach ((array)$data->rows as $key => $row) {
                $groupId = $data->recs[$key]->groupDetailId;
                if ($class::haveRightFor('single', $groupId)) {
                    $name = $class::getVerbal($groupId, 'name');
                    $row->groupDetailId = ht::createLink($name, array($class, 'single', $groupId));
                }
            }
        }
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
        $id = Request::get('id', 'int');
        
        expect($id);
        
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
        $id = Request::get('id', 'int');
        
        expect($id);
        
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
    
    
    /**
     * 
     */
    function on_AfterGetQuery($mvc, $query)
    {
        $query->orderBy('state');
    }
}
