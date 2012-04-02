<?php



/**
 * Клас 'plg_State2' - Поддръжка на поле 'state' за състояние на ред
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_State2 extends core_Plugin
{
    
    
    /**
     * Добавя полето за състояние, ако то липсва
     */
    function on_AfterDescription(&$mvc)
    {
        if (!$mvc->fields['state']) {
            $mvc->FLD('state',
                'enum(active=Активен,closed=Затворен)',
                'caption=Видимост,input=none,notSorting');
        }
    }
    
    
    /**
     * Подрежда по state, за да могат затворените да са отзад
     */
    function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#state');
    }
    
    
    /**
     * Гарантира, че новите записи ще имат state по подразбиране - 'active'
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, $fields = NULL)
    {
        if (!$rec->state) {
            $rec->state = 'active';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        
        if ($mvc->haveRightFor('changeState', $rec)) {
            
            $add = "<img src=" . sbf("img/16/lightbulb_off.png") . " width='16' height='16'>";
            $cancel = "<img src=" . sbf("img/16/lightbulb.png") . " width='16' height='16'>";
            
            $row->state = ht::createLink($rec->state == 'active' ? $cancel : $add ,
                array($mvc, 'changeState', $rec->id, 'ret_url' => TRUE),
                NULL,
                array('title' => $rec->state == 'active' ? 'Скриване' : 'Показване'));
            $row->state = ht::createElement('div',
                array('style' => "text-align:center;"), $row->state);
        }
    }
    
    
    /**
     * Прихваща екшън-а 'changeState'
     */
    function on_BeforeAction($mvc, &$content, &$act)
    {
        if($act != 'changestate') return;
        
        $retUrl = getRetUrl();
        
        $mvc->requireRightFor($act, NULL, NULL, $retUrl);
        
        expect($id = Request::get('id'));
        
        expect($rec = $mvc->fetch($id));
        
        $mvc->requireRightFor($action, $rec, NULL, $retUrl);
        
        $rec->state = ($rec->state == 'active' ? 'closed' : 'active');
        
        $mvc->save($rec, 'state');
        
        $content = new Redirect($retUrl);
        
        return FALSE;
    }
    
    /**
     * Изпълнява се при инициализиране и подсигурява записите, които имат NULL
     * за състояние да станат 'активни'
    
     function on_AfterSetupMVC($mvc, &$res)
     {
     $query = $mvc->getQuery();
    
     $cnt = 0;
    
     while($rec = $query->fetch()) {
     if($rec->state == '') {
     $rec->state = 'active';
     $mvc->save($rec, 'state');
     $cnt++;
     }
     }
    
     if($cnt) {
     $res .= "<li style='color:green;'>Състоянието на {$cnt} записа е променено на 'активно'";
     }
     } */
    
    
    /**
     * Поставя изискване да се селектират само активните записи
     */
    function on_BeforeMakeArray4Select($mvc, &$optArr, $fields = NULL, &$where = NULL)
    {
        $where .= ($where ? " AND " : "") . " #state = 'active'";
    }
}