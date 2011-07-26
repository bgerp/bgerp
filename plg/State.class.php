<?php

/**
 * Цвят на реда за състояние "чернова"
 */
defIfNot('EF_DRAFT_COLOR', '#aaffbb');


/**
 * Цвят на реда за състояние "активно"
 */
defIfNot('EF_ACTIVE_COLOR', '#ffeedd');


/**
 * Цвят на реда за състояние "чакащо"
 */
defIfNot('EF_WAITING_COLOR', '#cceeff');


/**
 * Цвят на реда за състояние "затворено"
 */
defIfNot('EF_CLOSED_COLOR', '#ddddcc');


/**
 * Цвят на реда за състояние "спряно"
 */
defIfNot('EF_STOPPED_COLOR', '#ffaa00');


/**
 * Цвят на реда за състояние "оттеглено"
 */
defIfNot('EF_REJECTED_COLOR', '#cc6666');


/**
 * Цвят на реда за състояние "свободно"
 */
defIfNot('EF_FREE_COLOR', '#33cccc');


/**
 * Цвят на реда за състояние "скрито"
 */
defIfNot('EF_HIDDEN_COLOR', '#ffee77');


/**
 * Клас 'plg_State' - Поддръжка на поле 'state' за състояние на ред
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
class plg_State extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        if (!$invoker->fields['state']) {
            $invoker->FLD('state',
            'enum(draft=Чернова,pending=Чакащо,active=Активирано,' .
            'closed=Приключено,hidden=Скрито,rejected=Оттеглено,' .
            'stopped=Спряно,wakeup=Събудено,free=Освободено)',
            'caption=Състояние,column=none');
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
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$invoker, &$row, &$rec)
    {
        switch ($rec->state) {
            case 'draft':
                $bgColor = EF_DRAFT_COLOR;
                break;
            case 'pending':
                $bgColor = EF_WAITING_COLOR;
                break;
            case 'active':
                $bgColor = EF_ACTIVE_COLOR;
                break;
            case 'closed':
                $bgColor = EF_CLOSED_COLOR;
                break;
            case 'hidden':
                $bgColor = EF_HIDDEN_COLOR;
                break;
            case 'rejected':
                $bgColor = EF_REJECTED_COLOR;
                break;
            case 'stopped':
                $bgColor = EF_STOPPED_COLOR;
                break;
            case 'wakeup':
                $bgColor = EF_WAKEUP_COLOR;
                break;
            case 'free':
                $bgColor = EF_FREE_COLOR;
                break;
        }
        
        $row->ROW_ATTR = " style='background:$bgColor' ";
    }
}