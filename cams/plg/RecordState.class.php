<?php

/**
 * Клас 'cams_plg_RecordState' - Поддръжка на поле 'state' за камера
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class cams_plg_RecordState extends core_Plugin
{
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if (!$mvc->fields['state']) {
            $mvc->FLD('state',
            'enum(active=Включен,hidden=Изключен)',
            'caption=Запис,input=none,notSorting');
        }
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, $fields = NULL)
    {
        if (!$rec->state) {
            $rec->state = 'draft';
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
        switch ($rec->state) {
            case 'active':
                $bgColor = EF_ACTIVE_COLOR;
                break;
            case 'hidden':
                $bgColor = '#f0f0f0';
                break;
        }
        
        $row->ROW_ATTR = " style='background:$bgColor' ";
        
        if ($mvc->haveRightFor('changeState', $rec)) {
            $on = ht::createElement('img', array('src' => sbf('cams/img/recOn.png', '')));
            $off = ht::createElement('img', array('src' => sbf('cams/img/recOff.png', '')));
            $row->state = ht::createLink($rec->state == 'active'? $on : $off,
            array($mvc, 'changeState', $rec->id, 'ret_url' => TRUE),
            NULL,
            array('title' => $rec->state == 'active' ? 'Изключване' : 'Включване'));
            $row->state = ht::createElement('div',
            array('style' => "text-align:center;"), $row->state);
        }
    }
    
    
    /**
     * Прихваща екшъна 'changeState'
     */
    function on_BeforeAction($mvc, &$content, &$act)
    {
        if($act != 'changestate') return;
        
        $retUrl = getRetUrl();
        
        $mvc->requireRightFor($act, NULL, NULL, $retUrl);
        
        expect($id = Request::get('id'));
        
        expect($rec = $mvc->fetch($id));
        
        $mvc->requireRightFor($action, $rec, NULL, $retUrl);
        
        $rec->state = ($rec->state == 'active' ? 'hidden' : 'active');
        
        $mvc->save($rec, 'state');
        
        $content = new Redirect($retUrl);
        
        return FALSE;
    }
}