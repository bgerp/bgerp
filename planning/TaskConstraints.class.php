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
    public $loadList = 'planning_Wrapper, plg_RowTools2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да го променя?
     */
    public $canDelete = 'debug';


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
    public $listFields = 'taskId,type,previousTaskId=Предходна,earliestTimeStart=Най-рано,waitingTime=Изчакване';


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
        $row->taskId = planning_Tasks::getLink($rec->taskId, 0);
        if(isset($rec->previousTaskId)){
            $row->previousTaskId = planning_Tasks::getLink($rec->previousTaskId, 0);
        }
    }


    private static function calc($taskId, &$alreadyCalced = array())
    {
        $res = array();
        $taskRec = static::fetchRec($taskId);
        $alreadyCalced[$taskRec->id] = $taskRec->id;

        $timeStart = $taskRec->timeStart ?? (in_array($taskRec->state, array('active', 'wakeup')) ? dt::now() : null);
        if(!empty($timeStart)){
            $res[] = (object)array('taskId' => $taskRec->id, 'earliestTimeStart' => $timeStart, 'type' => 'earliest');
        }

      //  bp($taskRec->productId);
       // $notCalced = array_diff_key($previousTaskIds, $alreadyCalced);
        foreach ($notCalced as $prevTaskId) {
            $res[] = (object)array('taskId' => $taskRec->id, 'type' => 'prevId', 'previousTaskId' => $prevTaskId);
            $calcedPrev = static::calc($prevTaskId, $alreadyCalced);
            $res = array_merge($res, $calcedPrev);
        }

        return $res;
    }

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

        $now = dt::now();
        foreach ($tasks as $taskRec){



            if(!empty($taskRec->timeStart)){
                $timeStart = max($taskRec->timeStart, $now);
                $res[] = (object)array('taskId' => $taskRec->id, 'earliestTimeStart' => $timeStart, 'type' => 'earliest', 'timeStart'=>$taskRec->timeStart);
            }

            if(isset($taskRec->previousTask)){
                $res[] = (object)array('taskId' => $taskRec->id, 'previousTaskId' => $taskRec->previousTask, 'type' => 'prevId');
            } else {
                $prevTaskIds = array();
                $prevStepsArr = array_key_exists($taskRec->productId, $prevSteps) ? $prevSteps[$taskRec->productId] : array();
                array_walk($tasksByJobs[$taskRec->originId], function($a) use(&$prevTaskIds, $prevStepsArr){
                    if(in_array($a->productId, $prevStepsArr)){
                        $prevTaskIds[$a->id] = $a->id;
                    }
                });

                foreach ($prevTaskIds as $prevTaskId){
                    $res[] = (object)array('taskId' => $taskRec->id, 'previousTaskId' => $prevTaskId, 'type' => 'prevId');
                }
            }
        }

        $taskIds = arr::extractValuesFromArray($res, 'taskId');

        $exQuery = static::getQuery();
        $exQuery->in("taskId", $taskIds);

        $exRecs = $exQuery->fetchAll();
        $me = cls::get(get_called_class());
        $synced = arr::syncArrays($res, $exRecs, 'taskId,type', 'type,previousTaskId,waitingTime,earliestTimeStart');

        if(countR($synced['insert'])){
            $me->saveArray($synced['insert']);
        }
        if(countR($synced['update'])){
            $me->saveArray($synced['update'], 'id,previousTaskId,waitingTime,earliestTimeStart');
        }

        if(countR($synced['delete'])){
            $deleteIds = implode(',', $synced['delete']);
            $me->delete("#id IN ({$deleteIds})");
        }
    }

    function act_Sync()
    {
        requireRole('debug');

        $this->sync();
        $this->truncate();
    }

    function act_Truncate()
    {
        requireRole('debug');
        $this->truncate();
    }


    function act_Test()
    {
        requireRole('debug');
        $r = static::sync();
        bp($r);
    }


}
