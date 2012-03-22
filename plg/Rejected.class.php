<?php



/**
 * Клас 'plg_Rejected' - Поддръжка на състоянието rejected
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
class plg_Rejected extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Добавяне на необходимите полета
        if(!isset($mvc->fields['state'])) {
            $mvc->FLD('state',
                'enum(draft=Чернова,active=Активирано,closed=Затворено,rejected=Оттеглено)',
                'caption=Състояние,column=none,input=none,notNull,value=active');
        }
        
        if(!isset($mvc->fields['state']->type->options['rejected'])) {
            $mvc->fields['state']->type->options['rejected'] = 'Оттеглено';
            $mvc->fields['state']->type->options['closed'] = 'Затворено';
        }
        
        if(!isset($mvc->fields['lastUsedOn'])) {
            $mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        }
    }
    
    
    /**
     * Добавя бутон за оттегляне
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (isset($data->rec->id) && !$mvc->haveRightFor('delete', $data->rec) && $mvc->haveRightFor('reject', $data->rec) && ($data->rec->state != 'rejected')) {
            $data->toolbar->addBtn('Оттегляне', array(
                    $mvc,
                    'reject',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnDelete,class=btn-reject,warning=Наистина ли желаете да оттеглите документа?,order=32');
        }
        
        if (isset($data->rec->id) && $mvc->haveRightFor('reject') && ($data->rec->state == 'rejected')) {
            $data->toolbar->removeBtn("*");
            $data->toolbar->addBtn('Възстановяване', array(
                    $mvc,
                    'restore',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnRestore,class=btn-restore,warning=Наистина ли желаете да възстановите документа?,order=32');
        }
    }
    
    
    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*');
            $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn,class=btn-list');
        } else {
            $data->toolbar->addBtn('Кош', array($mvc, 'list', 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
        }
    }
    
    
    /**
     * Добавя към титлата на списъчния изглед "[оттеглени]"
     */
    function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->title = new ET('[#1#]', tr($data->title));
            $data->title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени') . "]&nbsp;</font>");
        }
    }
    
    
    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        if($action == 'reject') {
            
            $id = Request::get('id', 'int');
            
            $mvc->requireRightFor('reject');
            
            $rec = $mvc->fetch($id);
            
            $mvc->requireRightFor('reject', $rec);
            
            if($rec->state != 'rejected') {
                
                $rec->state = 'rejected';
                
                $mvc->save($rec);
                
                $mvc->log('reject', $rec->id);
            }
            
            $res = new Redirect(array($mvc, 'single', $id));
            
            return FALSE;
        }
        
        if($action == 'restore') {
            
            $id = Request::get('id', 'int');
            
            $rec = $mvc->fetch($id);
            
            if (isset($rec->id) && $mvc->haveRightFor('reject') && ($rec->state == 'rejected')) {
                
                $rec->state = 'closed';
                
                $mvc->save($rec);
                
                $mvc->log('reject', $rec->id);
            }
            
            $res = new Redirect(getRetUrl() ? getRetUrl() : array($mvc, 'single', $rec->id));
            
            return FALSE;
        }
    }
    
    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->query->where("#state != 'rejected' || #state IS NULL");
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id) {
            if($action == 'delete' && $rec->lastUsedOn) {
                $requiredRoles = 'no_one';
            }
            
            // Системните записи не могат да се оттеглят или изтриват
            if($rec->createdBy == -1 && $action == 'reject') {
                $requiredRoles = 'no_one';
            }
        }
    }
}