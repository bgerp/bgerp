<?php



/**
 * Клас 'plg_State' - Поддръжка на поле 'state' за състояние на ред
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_State extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        if (!$invoker->fields['state']) {
            $invoker->FLD('state',
                'enum(draft=Чернова,
                  pending=Чакащо,
                  active=Активирано,
                  opened=Отворено,
                  waiting=Чакащо,
                  closed=Приключено,
                  hidden=Скрито,
                  rejected=Оттеглено,
                  stopped=Спряно,
                  wakeup=Събудено,
                  free=Освободено)',
                'caption=Състояние,column=none,input=none');
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, &$id, &$rec, &$fields = NULL)
    {
        if (!$rec->state && !$rec->id) {
            $rec->state = $mvc->defaultState ? $mvc->defaultState : 'draft';
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$invoker, &$row, &$rec)
    {
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        $row->STATE_CLASS .= " state-{$rec->state}";
    }
    
    
    /**
     * Поставя класа за състоянието на единичния изглед
     */
    function on_AfterRenderSingleTitle($mvc, &$res, $data)
    {
        $res = new ET("<div style='padding:5px;' class='state-{$data->rec->state}'>[#1#]</div>", $res);
    }
}