<?php



/**
 * Клас 'plg_State' - Поддръжка на поле 'state' за състояние на ред
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
class plg_State extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        self::setStateField($mvc);
    }


    static function setStateField($mvc)
    {
        if (!$mvc->fields['state']) {
            $mvc->FLD('state',
                'enum(draft=Чернова,
                  pending=Чакащо,
                  active=Активно,
                  opened=Отворено,
                  closed=Приключено,
                  hidden=Скрито,
                  rejected=Оттеглено,
                  stopped=Спряно,
                  wakeup=Събудено,
                  free=Освободено)',
                'caption=Състояние,column=none,input=none');
        }

        foreach($mvc->fields['state']->type->options as $state => $verbal) {
            if(is_object($verbal)) {
                $optArr[$state] = $verbal;
            } else {
                $opt = new stdClass();
                $opt->title = $verbal;
                $opt->attr = array('class' => "state-{$state}");
                $optArr[$state] = $opt;
            }
            
        }
 
        $mvc->fields['state']->type->options = $optArr; 
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