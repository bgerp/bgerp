<?php


/**
 * Клас 'cams_plg_RecordState' - Поддръжка на поле 'state' за камера
 *
 *
 * @category  bgerp
 * @package   cams
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cams_plg_RecordState extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        if (!$mvc->fields['state']) {
            $mvc->FLD(
                'state',
                'enum(active=Включен,hidden=Изключен)',
                'caption=Запис,input=none,notSorting'
            );
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public function on_BeforeSave(&$invoker, &$id, &$rec, $fields = null)
    {
        if (!$rec->state) {
            $rec->state = 'draft';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записа в машинно представяне
     */
    public function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        switch ($rec->state) {
            case 'active':
                $bgColor = EF_ACTIVE_COLOR;
                break;
            case 'hidden':
                $bgColor = '#f0f0f0';
                break;
        }
        
        $row->ROW_ATTR['style'] .= "background:${bgColor};";
        
        if ($mvc->haveRightFor('changeState', $rec)) {
            $on = ht::createElement('img', array('src' => sbf('cams/img/recOn.png', '')));
            $off = ht::createElement('img', array('src' => sbf('cams/img/recOff.png', '')));
            $row->state = ht::createLink(
                $rec->state == 'active' ? $on : $off,
                array($mvc, 'changeState', $rec->id, 'ret_url' => true),
                null,
                array('title' => $rec->state == 'active' ? 'Изключване' : 'Включване')
            );
            $row->state = ht::createElement(
                'div',
                array('style' => 'text-align:center;'),
                $row->state
            );
        }
    }
    
    
    /**
     * Прихваща екшън-а 'changeState'
     */
    public function on_BeforeAction($mvc, &$content, &$act)
    {
        if ($act != 'changestate') {
            
            return;
        }
        
        $retUrl = getRetUrl();
        
        $mvc->requireRightFor($act, null, null, $retUrl);
        
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $mvc->fetch($id));
        
        $mvc->requireRightFor($act, $rec, null, $retUrl);
        
        $rec->state = ($rec->state == 'active' ? 'hidden' : 'active');
        
        $mvc->save($rec, 'state');
        
        $content = new Redirect($retUrl);
        
        return false;
    }
}
