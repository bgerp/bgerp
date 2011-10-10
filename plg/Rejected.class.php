<?php

/**
 * Клас 'plg_Rejected' - Поддръжка на rejectedOn и rejectedBy
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
    function on_AfterDescription(&$invoker)
    {
        // Добавяне на необходимите полета
        $invoker->FLD('rejectedOn', 'datetime', 'caption=Оттегляне->На,input=none');
        $invoker->FLD('rejectedBy', 'key(mvc=core_Users)', 'caption=Оттегляне->От,input=none');
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
            'id=btnDelete,class=btn-reject,warning=Наистина ли желаете да оттеглите документа?');
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
            
            if(empty($rec->rejectedOn) || ($rec->state && ($rec->state != 'rejected'))) {
                $rec->state = 'rejected';
                $rec->rejectedBy = Users::getCurrent();
                $rec->rejectedOn = dt::verbal2Mysql();
            }
             
            $mvc->save($rec);
            
            $res = new Redirect(array($mvc, 'single', $id));

            return FALSE;
        }
    }
}