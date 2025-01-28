<?php


/**
 * Модел за ограничения на ПО
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_TaskConstraints extends core_Master
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Ограничения на ПО';


    /**
     * Заглавие на мениджъра
     */
    public $singleTitle = 'Ограничение на ПО';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper, plg_Sorting';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да го променя?
     */
    public $canDelete = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $listFields = 'taskId,type,previousTaskId=Предходна,earliestTimeStart=Най-рано,waitingTime=Изчакване,updatedOn';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('taskId', 'key(mvc=planning_Tasks,select=title)', 'caption=Операция');
        $this->FLD('type', 'enum(prevId=Предходна операция,earliest=Най-рано)', 'caption=Тип');
        $this->FLD('previousTaskId', 'key(mvc=planning_Tasks,select=title)', 'caption=Предходна');
        $this->FLD('waitingTime', 'time', 'caption=Време за изчакване');
        $this->FLD('earliestTimeStart', 'datetime', 'caption=Най-ранно започване');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Обновяване');

        $this->setDbIndex('taskId');
        $this->setDbIndex('previousTaskId');
    }


    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     * @param stdClass $fields
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $taskState = planning_Tasks::fetchField($rec->taskId, 'state');
        $row->taskId = planning_Tasks::getLink($rec->taskId, 0);
        $row->taskId = ht::createElement("div", array('class' => "state-{$taskState} document-handler"), $row->taskId);

        if(isset($rec->previousTaskId)){
            $taskState = planning_Tasks::fetchField($rec->previousTaskId, 'state');
            $row->previousTaskId = planning_Tasks::getLink($rec->previousTaskId, 0);
            $row->previousTaskId = ht::createElement("div", array('class' => "state-{$taskState} document-handler"), $row->previousTaskId);
        }
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FLD('documentId', 'varchar', 'caption=Операция, silent');
        $data->listFilter->setFieldType('type', 'enum(all=Всички,prevId=Предходна операция,earliest=Най-рано)');
        $data->listFilter->showFields = 'documentId,type';
        $data->listFilter->input(null, 'silent');

        $data->listFilter->setDefault('type', 'all');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->query->orderBy('id', 'DESC');

        if($filter = $data->listFilter->rec){
            if($filter->type != 'all'){
                $data->query->where("#type = '{$filter->type}'");
            }

            if(!empty($filter->documentId)){
                $data->query->where("#taskId = '{$filter->documentId}' || #previousTaskId = '{$filter->documentId}'");
            }
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Ресинхронизиране');
            $data->toolbar->addBtn('Изпразни', array($mvc, 'truncate', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Изпразване');
        }
    }


    /**
     * Екшън за синхронизиране на записите
     */
    function act_Sync()
    {
        requireRole('debug');
        $this->sync();

        followRetUrl(null, 'Синхронизиране');
    }


    /**
     * Синхронизиране на записи на посочени операции (null за аквитните+събудените+спрените+заявка)
     *
     * @param mixed $tasks
     * @return void
     */
    public static function sync($tasks = array())
    {
        $arr = arr::make($tasks, true);
        if(!countR($arr)){
            $tQuery = planning_Tasks::getQuery();
            $tQuery->in('state', array('active', 'wakeup', 'stopped', 'pending'));
            $tQuery->show('timeStart,previousTask,productId,originId');
            $tasks = $tQuery->fetchAll();
        } else {
            $tasks = array();
            foreach ($arr as $id) {
                $taskId = is_numeric($id) ? $id : $id->id;
                $tasks[$taskId] = planning_Tasks::fetch($taskId, 'timeStart,previousTask,productId,originId');
            }

            core_Statuses::newStatus("SYNC: " . implode('-', array_keys($tasks)));
        }

        $prevSteps = $tasksByJobs = array();
        $stepIds = arr::extractValuesFromArray($tasks, 'productId');
        $jobIds = arr::extractValuesFromArray($tasks, 'originId');
        $cQuery = planning_StepConditions::getQuery();
        $cQuery->in("stepId", $stepIds);
        $cQuery->show('stepId,prevStepId');
        while($cRec = $cQuery->fetch()){
            $prevSteps[$cRec->stepId][$cRec->prevStepId] = $cRec->prevStepId;
        }

        // Всички текущи ПО към заданието за посочените етапи
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#state IN ('active', 'stopped', 'wakeup', 'closed', 'pending')");
        $tQuery->in('originId', $jobIds);
        $tQuery->show('id,originId,productId');
        while($tRec = $tQuery->fetch()){
            $tasksByJobs[$tRec->originId][$tRec->id] = (object)array('productId' => $tRec->productId, 'id' => $tRec->id);
        }

        $res = array();
        $now = dt::now();
        foreach ($tasks as $taskRec){
            if(!empty($taskRec->timeStart)){
                $timeStart = max($taskRec->timeStart, $now);
                $res["time|{$taskRec->id}"] = (object)array('taskId' => $taskRec->id, 'type' => 'earliest', 'earliestTimeStart' => $timeStart, 'waitingTime' => null, 'previousTaskId' => null, 'updatedOn' => $now);
            }

            if(isset($taskRec->previousTask)){
                $res["prev|{$taskRec->id}"] = (object)array('taskId' => $taskRec->id, 'type' => 'prevId', 'earliestTimeStart' => null, 'waitingTime' => null,  'previousTaskId' => $taskRec->previousTask, 'updatedOn' => $now);
            } else {
                $prevTaskIds = array();
                $prevStepsArr = array_key_exists($taskRec->productId, $prevSteps) ? $prevSteps[$taskRec->productId] : array();
                array_walk($tasksByJobs[$taskRec->originId], function($a) use(&$prevTaskIds, $prevStepsArr){
                    if(in_array($a->productId, $prevStepsArr)){
                        $prevTaskIds[$a->id] = $a->id;
                    }
                });

                foreach ($prevTaskIds as $prevTaskId){
                    $res["prev|{$taskRec->id}|$prevTaskId"] = (object)array('taskId' => $taskRec->id, 'type' => 'prevId', 'earliestTimeStart' => null, 'waitingTime' => null, 'previousTaskId' => $prevTaskId, 'updatedOn' => $now);
                }
            }
        }

        if(countR($arr) && !countR($res)) return;

        $taskIds = arr::extractValuesFromArray($res, 'taskId');
        $exQuery = static::getQuery();
        $exQuery->in("taskId", $taskIds);
        $exRecs = $exQuery->fetchAll();
        $me = cls::get(get_called_class());
        $synced = arr::syncArrays($res, $exRecs, 'taskId,type,previousTaskId', 'taskId,type,earliestTimeStart,waitingTime,previousTaskId');

        if(countR($synced['insert'])){
            $me->saveArray($synced['insert']);
        }
        if(countR($synced['update'])){
            $me->saveArray($synced['update'], 'id,previousTaskId,waitingTime,earliestTimeStart,updatedOn');
        }

        if(countR($synced['delete'])){
            $deleteIds = implode(',', $synced['delete']);
            $me->delete("#id IN ({$deleteIds})");
        }
    }


    /**
     * Екшън за изчистване на таблицата
     */
    function act_Truncate()
    {
        requireRole('debug');
        $this->truncate();

        followRetUrl(null, 'Записите са изтрити');
    }
}
