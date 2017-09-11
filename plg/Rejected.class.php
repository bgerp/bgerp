<?php



/**
 * Клас 'plg_Rejected' - Поддръжка на състоянието rejected
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
class plg_Rejected extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        // Добавяне на необходимите полета
        $mvc->FLD('state', 'enum(draft=Чернова,active=Активирано,closed=Затворено,rejected=Оттеглено)',
                'caption=Състояние,column=none,input=none,notNull,forceField');
        
        if(!isset($mvc->fields['state']->type->options['rejected'])) {
            $mvc->fields['state']->type->options['rejected'] = 'Оттеглено';
        }

        $mvc->FLD('exState', clone($mvc->fields['state']->type), "caption=Пред. състояние,column=none,input=none,single=none,notNull,forceField");
        
        $mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none,forceField');

        $mvc->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране->На,input=none,column=none,forceField');

        $mvc->doWithSelected = arr::make($mvc->doWithSelected) + array('reject' => '*Оттегляне', 'restore' => '*Възстановяване');

        setIfNot($invoker->canRejectsysdata, 'no_one');
    }
    
    
    /**
     * Добавя бутон за оттегляне
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (isset($data->rec->id) && $mvc->haveRightFor('reject', $data->rec)) {
            $data->toolbar->addBtn('Оттегляне', array(
                    $mvc,
                    'reject',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnDelete,class=fright,warning=Наистина ли желаете да оттеглите записа?,order=32,row=2,', 'ef_icon = img/16/reject.png, title=Оттегляне на документа');
        }
        
        if (isset($data->rec->id) && $mvc->haveRightFor('restore', $data->rec)) {
            $data->toolbar->removeBtn("*");
            $data->toolbar->addBtn('Възстановяване', array(
                    $mvc,
                    'restore',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnRestore,warning=Наистина ли желаете да възстановите записа?,order=32', 'ef_icon = img/16/restore.png');
        }
    }
    
    
    /**
     * Добавя бутон за показване на оттеглените записи
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {   
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*', 'with_selected');
            $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn', "ef_icon = img/16/application_view_list.png,title=Всички " . mb_strtolower($mvc->title));
        } else {
            $rejCnt = $data->rejQuery->count();

            if($rejCnt) {
                $data->rejQuery->orderBy('#modifiedOn', 'DESC', TRUE);
                $data->rejQuery->limit(1);
                $lastRec = $data->rejQuery->fetch();
                $color = dt::getColorByTime($lastRec->modifiedOn);
                $curUrl = getCurrentUrl();
                $curUrl['Rejected'] = 1; 
                if(isset($data->pager->pageVar)) {
                    unset($curUrl[$data->pager->pageVar]);
                }
                $data->toolbar->addBtn("Кош|* ({$rejCnt})", $curUrl, 'id=binBtn,class=fright,' . (Mode::is('screenMode', 'narrow') ? 'row=2,' : '') . 'order=50,title=Преглед на оттеглените ' . mb_strtolower($mvc->title),  "ef_icon = img/16/bin_closed.png, style=color:#{$color};");
            }
        }
        if(Request::get('Rejected')) {
            $data->title = new ET('[#1#]', tr($data->title ? $data->title : $mvc->title));
            $data->title->append("&nbsp;<span class='state-rejected stateIndicator'>&nbsp;" . tr('оттеглени') . "&nbsp;</span>");
        } 
    }
    
    
    /**
     * Оттегляне на обект
     * 
     * Реализация по подразбиране на метода $mvc->reject($id)
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|stdClass $id
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $res = FALSE;
        $rec = $mvc->fetchRec($id);
        
        if (!isset($rec->id) || $rec->state == 'rejected') {
            return;
        }
        
        $rec->exState = $rec->state;
        $rec->state = 'rejected';
        $rec->modifiedOn = dt::now();
        $res = $mvc->save($rec);

        $mvc->logWrite('Оттегляне', $rec->id);
    }
    
    
    /**
     * Възстановяване на оттеглен обект
     * 
     * Реализация по подразбиране на метода $mvc->restore($id)
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|stdClass $id
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $res = FALSE;
        $rec = $mvc->fetchRec($id);
        
        if (!isset($rec->id) || $rec->state != 'rejected') {
            return;
        }
        
        $rec->state = $rec->exState;
        $rec->modifiedOn = dt::now();
        $res = $mvc->save($rec);
    }


	/**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if ($action == 'reject') {
            expect(Request::isConfirmed());
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $mvc->requireRightFor('reject', $rec);
            $mvc->reject($rec);
            $res = new Redirect(getRetUrl() ? getRetUrl() : array($mvc, 'single', $id));

            $mvc->logInAct('Оттегляне', $rec);
            
            return FALSE;
        }
        
        if ($action == 'restore') {
            expect(Request::isConfirmed());
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $mvc->requireRightFor('restore', $rec);
            $mvc->restore($rec);
            $res = new Redirect(getRetUrl() ? getRetUrl() : array($mvc, 'single', $id));
            
            $mvc->logInAct('Възстановяване', $rec);
            
            return FALSE;
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
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id) {
            if($action == 'delete' && $rec->lastUsedOn) {
                $requiredRoles = 'no_one';
            }
            
            // Кога може да се оттеглят записи?
            if($action == 'reject') {
                // Системните записи, оттеглените и тези, които могат да се изтриват
                if($rec->state == 'rejected' || $mvc->haveRightFor('delete', $rec, $userId)) {
                    
                    $requiredRoles = 'no_one';
                }
                
                if (($requiredRoles != 'no_one') && ($rec->createdBy == core_Users::SYSTEM_USER)) {
                    $requiredRoles = $mvc->getRequiredRoles('rejectsysdata', $rec, $userId);
                }
            }

            // Не могат да се възстановяват не-оттеглении записи
            if($action == 'restore' && $rec->state != 'rejected') {
                $requiredRoles = 'no_one';
            }
            
            if ($action == 'restore' || $action == 'reject') {
                if ($mvc instanceof core_Master) {
                    if (!$mvc->haveRightFor('single', $rec, $userId)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
            
            if(!$requiredRoles && ($action == 'restore' || $action == 'reject') && $mvc->haveRightFor('single', $rec, $userId)) {
                
                $requiredRoles = 'user';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     * Добавя поле за пълнотекстово търсене
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    { 
        
        // Добавяме скрито полето за оттегляне
        if(!isset($data->listFilter->fields['Rejected'])) {
            $data->listFilter->FNC('Rejected', 'varchar', 'input=hidden,silent');
        }
        
        // Ако е зададено
        if ($rejectedId = Request::get('Rejected', 'int')) {
            
            // Задаваме стойността от заявката
            $data->listFilter->setDefault('Rejected', $rejectedId);
        }
        
    }


    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery = clone($data->query);
                $data->query->where("#state != 'rejected' OR #state IS NULL");
                $data->rejQuery->where("#state = 'rejected'");
            }
        }
    }
}