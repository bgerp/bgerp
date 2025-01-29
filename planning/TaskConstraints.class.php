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
            $data->toolbar->addBtn('Преизч. продължителност', array($mvc, 'recalcDuration', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на продължителност');
        }
    }


    function act_recalcDuration()
    {
        requireRole('debug');
        $this->calcTaskDuration();

        followRetUrl(null, 'Синхронизиране');
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


    private static function getDefaultArr($tasks)
    {
        $arr = arr::make($tasks, true);
        if(!countR($arr)){
            $tQuery = planning_Tasks::getQuery();
            $tQuery->in('state', array('active', 'wakeup', 'stopped', 'pending'));
            $tasks = $tQuery->fetchAll();
        } else {
            $tasks = array();
            foreach ($arr as $id) {
                $taskId = is_numeric($id) ? $id : $id->id;
                $tasks[$taskId] = planning_Tasks::fetch($taskId);
            }
        }

        return $tasks;
    }


    /**
     * Синхронизиране на записи на посочени операции (null за аквитните+събудените+спрените+заявка)
     *
     * @param mixed $tasks
     * @return void
     */
    public static function sync($tasks = array())
    {
        $tasks = self::getDefaultArr($tasks);

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
                if($taskRec->timeStart > $now){
                    $res["time|{$taskRec->id}"] = (object)array('taskId' => $taskRec->id, 'type' => 'earliest', 'earliestTimeStart' => $taskRec->timeStart, 'waitingTime' => null, 'previousTaskId' => null, 'updatedOn' => $now);
                }
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

        if(countR($tasks) && !countR($res)) return;

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

    public static function calcTaskDuration($tasks = array())
    {
        $tasks = self::getDefaultArr($tasks);
        if(!count($tasks)) return;

        $taskIds = $productIds = $assetIds = $stepsData = $normsByTask = $jobProductIds = array();
        foreach ($tasks as $taskRec){

            $taskIds[$taskRec->id] = $taskRec->id;
            $productIds[$taskRec->productId] = $taskRec->productId;
            if(!array_key_exists($taskRec->originId, $jobProductIds)){
                $jobProductIds[$taskRec->originId] = planning_Jobs::fetchField("#containerId = {$taskRec->originId}", 'productId');
            }

            if(isset($taskRec->assetId)){
                if(!array_key_exists($taskRec->assetId, $assetIds)){
                    $assetIds[$taskRec->assetId] = planning_AssetResources::fetch($taskRec->assetId);
                }
            }

            // За всяка ПО се извличат планиращите ѝ действия
            if(!array_key_exists($taskRec->productId, $stepsData)){
                if($Driver = cat_Products::getDriver($taskRec->productId)){
                    $stepsData[$taskRec->productId] = $Driver->getProductionData($taskRec->productId);
                }
            }

            if(is_array($stepsData[$taskRec->productId]['actions'])){
                foreach ($stepsData[$taskRec->productId]['actions'] as $actionProductId){
                    $normsByTask[$taskRec->id][$actionProductId] = 0;
                }
            }
        }

        $productIds += $jobProductIds;

        // Еднократно кеширане на продуктовите опаковки
        $pPacks = array();
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->in('productId', $productIds);
        $packQuery->show('quantity,productId,packagingId');
        while($pRec = $packQuery->fetch()){
            $pPacks["{$pRec->productId}|{$pRec->packagingId}"] = $pRec->quantity;
        }

        // Изчисляват се времената на планираните операции за задачата
        $pQuery = planning_ProductionTaskProducts::getQuery();
        $pQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey=productId");
        $pQuery->where("#type = 'input' AND #canStore != 'yes'");
        $pQuery->in('taskId', $taskIds);
        $pQuery->show('productId,taskId,plannedQuantity,indTime,totalTime');

        while($pRec = $pQuery->fetch()){

            // Ако планираното влагане е от планиращите операции на артикула
            if(isset($normsByTask[$pRec->taskId][$pRec->productId])){
                $indTimeNorm = planning_type_ProductionRate::getInSecsByQuantity($pRec->indTime, $pRec->plannedQuantity);
                $totalTimeNorm = planning_type_ProductionRate::getInSecsByQuantity($pRec->totalTime, $pRec->plannedQuantity);
                $normsByTask[$pRec->taskId][$pRec->productId] = max($indTimeNorm, $totalTimeNorm);
            }
        }

        // За всяка операция
        $minDuration = planning_Setup::get('MIN_TASK_DURATION');
        foreach ($tasks as $t){
            // Ако има зададена продължителност - това е
            $duration = $t->timeDuration;

            // Ако няма изчислява се от нормата за планираното количество
            if(empty($duration)){
                if($t->indPackagingId == $t->measureId){
                    $calcedPlannedQuantity = $t->plannedQuantity;
                } else {

                    // Ако мярката за нормиране е същата като тази от етикета - взема се неговото к-во
                    $indProductIdKey = ($t->isFinal == 'yes') ? $t->jobProductId : $t->productId;
                    if($t->indPackagingId == $t->labelPackagingId && $t->labelQuantityInPack){
                        $indQuantityInPack = $t->labelQuantityInPack;
                    } else {
                        $indQuantityInPack = $pPacks["{$indProductIdKey}|{$t->indPackagingId}"] ?? 1;
                    }

                    $quantityInPack = $pPacks["{$indProductIdKey}|{$t->measureId}"] ?? 1;
                    $calcedPlannedQuantity = round(($t->plannedQuantity * $quantityInPack) / $indQuantityInPack);
                }

                $indTime = planning_type_ProductionRate::getInSecsByQuantity($t->indTime, $calcedPlannedQuantity);
                $simultaneity = $t->simultaneity ?? $assetIds[$t->assetId]->simultaneity;
                $duration = round($indTime / $simultaneity);
            }

            // От продължителността, се приспада произведеното досега
            $nettDuration = $duration;
            $duration = round((1 - $t->progress) * $duration);

            // Ако мин прогреса е под 100%, то се използва мин. продължителността, иначе за мин. прод. се използва 0
            $minDuration = ($t->progress >= 1) ? 1 : $minDuration;
            $duration = max($duration, $minDuration);

            // Към така изчислената продължителност се добавя тази от действията към машината
            if(array_key_exists($t->id, $normsByTask)){
                $duration += array_sum($normsByTask[$t->id]);
                $nettDuration += array_sum($normsByTask[$t->id]);
            }
            $t->calcedDuration = $nettDuration;
            $t->calcedCurrentDuration = $duration;
        }

        core_Statuses::newStatus("RECALC_TIMES-" . countR($tasks), 'warning');

        // Кешира се нетната продължителност
        cls::get('planning_Tasks')->saveArray($tasks, 'id,calcedDuration,calcedCurrentDuration');
    }


    /**
     * Преизчисляване на продължителноста на операциите по разписание
     */
    public function cron_RecalcTaskDuration()
    {
        self::calcTaskDuration();
    }
}
