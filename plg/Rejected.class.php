<?php

/**
 * Клас 'plg_Rejected' - Поддръжка на състоянието rejected 
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
class plg_Rejected extends core_Plugin
{
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Добавяне на необходимите полета
        if(!isset($mvc->fields['state'])) {
            $mvc->FLD('state',
            'enum(draft=Чернова,active=Активирано,rejected=Оттеглено)',
            'caption=Състояние,column=none,notNull,value=active');
        }
        if(!isset($mvc->fields['state']->type->options['rejected'])) {
            $mvc->fields['state']->type->options['rejected'] = 'Оттеглено';
        }
    }
    

    /**
     * Добавя бутон за оттегляне
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {  
        if (isset($data->rec->id) && !$mvc->haveRightFor('delete', $data->rec) && $mvc->haveRightFor('reject', $data->rec) && ($data->rec->state != 'rejected') ) {
            $data->toolbar->addBtn('Оттегляне', array(
                $mvc,
                'reject',
                $data->rec->id,
                'ret_url' => TRUE
            ),
            'id=btnDelete,class=btn-reject,warning=Наистина ли желаете да оттеглите документа?,order=32');
        }
    }


    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {  
        if(Request::get('Rejected')) {
           $data->toolbar->removeBtn('*');
           $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn,class=btn-list');
        } else {
            $data->toolbar->addBtn('Кош', array($mvc, 'list', 'Rejected' => 1), 'id=binBtn,class=btn-bin');
        }
    }


    /**
     *
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        if(Request::get('Rejected')) {
            $data->title = new ET($data->title);
            $data->title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени'). "]&nbsp;</font>");
        }
    }

 
    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction($mvc, $res, $action)
    {
        if($action == 'reject') {
        
            $id = Request::get('id', 'int');
            
            $mvc->requireRightFor('reject');

            $rec = $mvc->fetch($id);
            
            $mvc->requireRightFor('reject', $rec);
            
            if($rec->state != 'rejected') {
                $rec->state = 'rejected';
            }
             
            $mvc->save($rec);
            
            $res = new Redirect(array($mvc, 'single', $id));

            return FALSE;
        }
    }

    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->query->where("#state != 'rejected' || #state IS NULL");
            }
        }
    }

}