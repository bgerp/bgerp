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
        $this->setDbIndex('taskId,type');
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

        if (isset($rec->previousTaskId)) {
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

        if ($filter = $data->listFilter->rec) {
            if ($filter->type != 'all') {
                $data->query->where("#type = '{$filter->type}'");
            }

            if (!empty($filter->documentId)) {
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
            $data->toolbar->addBtn('ПОДРЕДБА', array($mvc, 'order', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Подредба');
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


    private static function getDefaultArr($tasks = array(), $fields = null)
    {
        $arr = arr::make($tasks, true);
        if (!countR($arr)) {
            $stepClassId = planning_interface_StepProductDriver::getClassId();
            $tQuery = planning_Tasks::getQuery();
            $tQuery->in('state', array('active', 'wakeup', 'stopped', 'pending'));
            $tQuery->EXT('innerClass', 'cat_Products', "externalName=innerClass,externalKey=productId");
            $tQuery->EXT('dueDate', 'planning_Jobs', 'externalName=dueDate,remoteKey=containerId,externalFieldName=originId,caption=Задание->Падеж');
            $tQuery->where("#innerClass = {$stepClassId} AND #assetId IS NOT NULL");
            if(isset($fields)){
                $fields = arr::make($fields, true);
                $tQuery->show(implode(',', $fields));
            }
            $tasks = $tQuery->fetchAll();
        } else {
            $tasks = array();
            foreach ($arr as $id) {
                $fields = $fields ? arr::make($fields, true) : '*';
                $taskId = is_numeric($id) ? $id : $id->id;
                $tasks[$taskId] = planning_Tasks::fetch($taskId, $fields);
            }
        }

        return $tasks;
    }


    /**
     * Синхронизиране на записи на посочени операции (null за аквитните+събудените+спрените+заявка)
     *
     * @param mixed $tasks
     * @return string
     */
    public static function sync($tasks = array())
    {
        core_Debug::startTimer('SYNC_TASK_CONSTRAINTS');

        $tasks = self::getDefaultArr($tasks);
        $taskCount = countR($tasks);
        core_App::setTimeLimit($taskCount * 0.3, false, 60);

        $res = $prevSteps = $tasksByJobs = $stepIds = $jobIds = $folderIds = $folderLocations = $offsetArr = array();
        foreach ($tasks as $tRec) {
            $stepIds[$tRec->productId] = $tRec->productId;
            $jobIds[$tRec->originId] = $tRec->originId;
            $folderIds[$tRec->folderId] = $tRec->folderId;
        }

        // Извличане на локациите на които са центровете на дейност на етапа
        $cQuery = planning_Centers::getQuery();
        $cQuery->EXT('locationId', 'hr_Departments', 'externalName=locationId,externalKey=departmentId');
        $cQuery->in('folderId', $folderIds);
        $cQuery->show('locationId,folderId');
        while ($cRec = $cQuery->fetch()) {
            $folderLocations[$cRec->folderId] = $cRec->locationId ?? '-';
        }

        // Извличане на всички етапи, които са посочени като предишни
        $cQuery = planning_StepConditions::getQuery();
        $cQuery->in("stepId", $stepIds);
        $cQuery->show('stepId,prevStepId');
        while ($cRec = $cQuery->fetch()) {
            $prevSteps[$cRec->stepId][$cRec->prevStepId] = $cRec->prevStepId;
        }

        // Извличане на всички изчаквания след на всеки етап
        $productClassId = cat_Products::getClassId();
        $sQuery = planning_Steps::getQuery();
        $sQuery->where("#classId = {$productClassId}");
        $sQuery->show('objectId,offsetAfter');
        $sQuery->in("objectId", $stepIds);
        while ($sRec = $sQuery->fetch()) {
            $offsetArr[$sRec->objectId] = $sRec->offsetAfter ?? 0;
        }

        // Всички текущи ПО към заданието за посочените етапи
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#state IN ('active', 'stopped', 'wakeup', 'pending')");
        $tQuery->in('originId', $jobIds);
        $tQuery->show('id,originId,productId,folderId');
        while ($tRec = $tQuery->fetch()) {
            $tasksByJobs[$tRec->originId][$tRec->id] = (object)array('productId' => $tRec->productId, 'id' => $tRec->id, 'folderId' => $tRec->folderId);
        }

        $offsetSameLocation = planning_Setup::get('TASK_OFFSET_IN_SAME_LOCATION');
        $offsetOtherLocation = planning_Setup::get('TASK_OFFSET_IN_OTHER_LOCATION');

        $now = dt::now();
        foreach ($tasks as $taskRec) {

            // Ако има посочено най-ранно начало и то е в бъдещето - записва се
            if (!empty($taskRec->timeStart)) {
                if ($taskRec->timeStart > $now) {
                    $res["time|{$taskRec->id}"] = (object)array('taskId' => $taskRec->id, 'type' => 'earliest', 'earliestTimeStart' => $taskRec->timeStart, 'waitingTime' => null, 'previousTaskId' => null, 'updatedOn' => $now);
                }
            }

            // Ако има ръчно посочена предходна - нея, иначе се търсят всички предходни от заданието
            $prevTaskIds = array();
            if (isset($taskRec->previousTask)) {
                if(isset($tasks[$taskRec->previousTask])){
                    $prevTaskIds[$taskRec->previousTask] = $taskRec->previousTask;
                }
            } else {
                $prevStepsArr = array_key_exists($taskRec->productId, $prevSteps) ? $prevSteps[$taskRec->productId] : array();
                array_walk($tasksByJobs[$taskRec->originId], function ($a) use (&$prevTaskIds, $prevStepsArr) {
                    if (in_array($a->productId, $prevStepsArr)) {
                        $prevTaskIds[$a->id] = $a->id;
                    }
                });
            }

            if(countR($prevTaskIds)){

                // За всяка предходна ще се добави че операцията е зависима от нея
                $thisTaskLocationId = $folderLocations[$tasks[$taskRec->id]->folderId];
                foreach ($prevTaskIds as $prevTaskId) {

                    // Гледа се дали текущата и предходната са в една локация или са в различни
                    $prevTaskLocationId = $folderLocations[$tasks[$prevTaskId]->folderId];
                    $locationOffset = ($thisTaskLocationId == $prevTaskLocationId) ? $offsetSameLocation : $offsetOtherLocation;

                    // Времето за изчакване е по-голямото от това за локацията и зададеното в етапа време на изчакване
                    $waitingTime = max($locationOffset,  $offsetArr[$tasks[$prevTaskId]->productId]);
                    $res["prev|{$taskRec->id}|$prevTaskId"] = (object)array('taskId' => $taskRec->id, 'type' => 'prevId', 'earliestTimeStart' => null, 'waitingTime' => $waitingTime, 'previousTaskId' => $prevTaskId, 'updatedOn' => $now);
                }
            }
        }

        // Извличат се записите за посочените операции
        $taskIds = arr::extractValuesFromArray($res, 'taskId');
        $exQuery = static::getQuery();
        $exQuery->in("taskId", $taskIds);
        $exRecs = $exQuery->fetchAll();
        $me = cls::get(get_called_class());

        // Синхронизират се
        $synced = arr::syncArrays($res, $exRecs, 'taskId,type,previousTaskId', 'taskId,type,earliestTimeStart,waitingTime,previousTaskId');

        $i = countR($synced['insert']);
        if ($i) {
            $me->saveArray($synced['insert']);
        }

        $u = countR($synced['update']);
        if ($u) {
            $me->saveArray($synced['update'], 'id,previousTaskId,waitingTime,earliestTimeStart,updatedOn');
        }

        $d = countR($synced['delete']);
        if ($d) {
            $deleteIds = implode(',', $synced['delete']);
            $me->delete("#id IN ({$deleteIds})");
        }

        core_Debug::stopTimer('SYNC_TASK_CONSTRAINTS');
        core_Debug::log("SYNC_TASK_CONSTRAINTS " . round(core_Debug::$timers["SYNC_TASK_CONSTRAINTS"]->workingTime, 6));

        return "Синхронизирани ограничения I:{$i} / U: {$u} / D: {$d}";
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
        core_Debug::startTimer('SYNC_TASK_DURATIONS');
        $tasks = self::getDefaultArr($tasks);
        if (!count($tasks)) return;

        $taskCount = countR($tasks);
        core_App::setTimeLimit($taskCount * 0.3, false, 60);

        $taskIds = $assetInTasks = $stepsData = $normsByTask = $jobContainers = array();
        $productIds = arr::extractValuesFromArray($tasks, 'productId');

        // Еднократно извличане на планиращите действия
        $stepQuery = planning_Steps::getQuery();
        $stepQuery->where("#classId = " . cat_Products::getClassId());
        $stepQuery->in("objectId", $productIds);
        $stepQuery->show('objectId,planningActions');
        while($stepRec = $stepQuery->fetch()){
            $stepsData[$stepRec->objectId] = keylist::toArray($stepRec->planningActions);
        }

        foreach ($tasks as $taskRec) {
            $taskIds[$taskRec->id] = $taskRec->id;
            $jobContainers[$taskRec->originId] = $taskRec->originId;
            if (isset($taskRec->assetId)) {
                $assetInTasks[$taskRec->assetId] = $taskRec->assetId;
            }
            if (is_array($stepsData[$taskRec->productId])) {
                foreach ($stepsData[$taskRec->productId] as $actionProductId) {
                    $normsByTask[$taskRec->id][$actionProductId] = 0;
                }
            }
        }

        // Еднократно извличане на оборудванията
        $aQuery = planning_AssetResources::getQuery();
        $aQuery->in('id', $assetInTasks);
        $assetIds = $aQuery->fetchAll();

        // Еднократно извличане на артикулите от заданията
        $jQuery = planning_Jobs::getQuery();
        $jQuery->in('containerId', $jobContainers);
        $jQuery->show('productId');
        $jQuery->groupBy('productId');
        $jobProductIds = arr::extractValuesFromArray($jQuery->fetchAll(), 'productId');
        $productIds += $jobProductIds;

        // Еднократно кеширане на продуктовите опаковки
        $pPacks = array();
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->in('productId', $productIds);
        $packQuery->show('quantity,productId,packagingId');
        while ($pRec = $packQuery->fetch()) {
            $pPacks["{$pRec->productId}|{$pRec->packagingId}"] = $pRec->quantity;
        }

        // Изчисляват се времената на планираните операции за задачата
        $pQuery = planning_ProductionTaskProducts::getQuery();
        $pQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey=productId");
        $pQuery->where("#type = 'input' AND #canStore != 'yes'");
        $pQuery->in('taskId', $taskIds);
        $pQuery->show('productId,taskId,plannedQuantity,indTime,totalTime');
        while ($pRec = $pQuery->fetch()) {

            // Ако планираното влагане е от планиращите операции на артикула
            if (isset($normsByTask[$pRec->taskId][$pRec->productId])) {
                $indTimeNorm = planning_type_ProductionRate::getInSecsByQuantity($pRec->indTime, $pRec->plannedQuantity);
                $totalTimeNorm = planning_type_ProductionRate::getInSecsByQuantity($pRec->totalTime, $pRec->plannedQuantity);
                $normsByTask[$pRec->taskId][$pRec->productId] = max($indTimeNorm, $totalTimeNorm);
            }
        }

        // За всяка операция
        $minDuration = planning_Setup::get('MIN_TASK_DURATION');

        foreach ($tasks as $t) {
            // Ако има зададена продължителност - това е
            $duration = $t->timeDuration;

            // Ако няма изчислява се от нормата за планираното количество
            if (empty($duration)) {
                if ($t->indPackagingId == $t->measureId) {
                    $calcedPlannedQuantity = $t->plannedQuantity;
                } else {

                    // Ако мярката за нормиране е същата като тази от етикета - взема се неговото к-во
                    $indProductIdKey = ($t->isFinal == 'yes') ? $t->jobProductId : $t->productId;
                    if ($t->indPackagingId == $t->labelPackagingId && $t->labelQuantityInPack) {
                        $indQuantityInPack = $t->labelQuantityInPack;
                    } else {
                        $indQuantityInPack = $pPacks["{$indProductIdKey}|{$t->indPackagingId}"] ?? 1;
                    }

                    $quantityInPack = $pPacks["{$indProductIdKey}|{$t->measureId}"] ?? 1;
                    $calcedPlannedQuantity = round(($t->plannedQuantity * $quantityInPack) / $indQuantityInPack);
                }

                $indTime = planning_type_ProductionRate::getInSecsByQuantity($t->indTime, $calcedPlannedQuantity);
                $simultaneity = $t->simultaneity ?? ($assetIds[$t->assetId]->simultaneity ?? 1);
                $duration = round($indTime / $simultaneity);
            }


            // От продължителността, се приспада произведеното досега
            $nettDuration = $duration;
            $duration = round((1 - $t->progress) * $duration);

            // Ако мин прогреса е под 100%, то се използва мин. продължителността, иначе за мин. прод. се използва 0
            $cMinDuration = ($t->progress >= 1) ? 1 : $minDuration;
            $duration = max($duration, $cMinDuration);

            // Към така изчислената продължителност се добавя тази от действията към машината
            if (array_key_exists($t->id, $normsByTask)) {
                $duration += array_sum($normsByTask[$t->id]);
                $nettDuration += array_sum($normsByTask[$t->id]);
            }
            $t->calcedDuration = $nettDuration;
            $t->calcedCurrentDuration = $duration;
        }

        if (haveRole('debug')) {
            core_Statuses::newStatus("RECALC_TIMES-" . countR($tasks), 'warning');
        }

        // Кешира се нетната продължителност
        cls::get('planning_Tasks')->saveArray($tasks, 'id,calcedDuration,calcedCurrentDuration');

        core_Debug::stopTimer('SYNC_TASK_DURATIONS');
        core_Debug::log("SYNC_TASK_DURATIONS " . round(core_Debug::$timers["SYNC_TASK_DURATIONS"]->workingTime, 6));
    }


    /**
     * Рекалкулиране на ограниченията на операциите по разписание
     */
    public function cron_RecalcTaskConstraints()
    {
        return self::sync();
    }


    /**
     * Преизчисляване на продължителноста на операциите по разписание
     */
    public function cron_RecalcTaskDuration()
    {
        self::calcTaskDuration();
    }


    function act_Order()
    {
        requireRole('debug');

        // Извличане на всички ПО годни за планиране
        $tasks = self::getDefaultArr(null, 'actualStart,timeStart,calcedCurrentDuration,assetId,dueDate');

        // Еднократно извличане на всички ограничения
        $query = static::getQuery();
        $constraintsArr = $query->fetchAll();

        // Разделяне на ограниченията на ПО-та
        $earliestTimeStart = $previousTasks = array();
        foreach ($constraintsArr as $cRec){
            if($cRec->type == 'earliest'){
                if(!empty($cRec->earliestTimeStart)){
                    $earliestTimeStart[$cRec->taskId] = $cRec->earliestTimeStart;
                }
            } elseif($cRec->type == 'prevId') {
                if(!empty($cRec->previousTaskId)){
                    $previousTasks[$cRec->taskId][$cRec->previousTaskId] = (object)array('previousTaskId' => $cRec->previousTaskId, 'waitingTime' => $cRec->waitingTime);
                }
            }
        }

        // Извличат се графиците на всички ПО с интервали за планиране
        $assetIds = arr::extractValuesFromArray($tasks, 'assetId');
        $intervals = array();
        foreach ($assetIds as $assetId) {
            if($Interval = planning_AssetResources::getWorkingInterval($assetId)) {
                $intervals[$assetId] = $Interval;
            }
        }

        // От операциите остават само тези, които са на машини с закачени графици
        // Попринцип не би трябвало да има машина без график, но за всеки случай
        $tasksWithActualStart = $tasksWithoutActualStartByAssetId = array();
        $assetsWithIntervals = array_keys($intervals);
        $allTasks = array();
        array_walk($tasks, function ($task) use ($assetsWithIntervals, &$allTasks, &$tasksWithActualStart, &$tasksWithoutActualStartByAssetId) {
            if(in_array($task->assetId, $assetsWithIntervals)) {
                $allTasks[$task->id] = $task;
                if(!empty($task->actualStart)){
                    $tasksWithActualStart[$task->id] = $task;
                } else {
                    $tasksWithoutActualStartByAssetId[$task->assetId][$task->id] = $task;
                }
            }
        });

        // Тези с фактическо начало се сортират по възходящ ред
        $interruptionArr = planning_Steps::getInterruptionArr($tasks);
        arr::sortObjects($tasksWithActualStart, 'actualStart', 'ASC');

        // Първо ще се наместят в графика тези с фактическо начало


        $debugArr = array();
        $planned = array();
        $notFoundDate = '9999-12-31 23:59:59';
        $now = dt::now();
        foreach ($tasksWithActualStart as $taskRec1){
            $begin = max($taskRec1->actualStart, $now);
            if($Interval = $intervals[$taskRec1->assetId]){
                $offset = isset($interruptionArr[$taskRec1->productId]) ?? null;
                $begin = strtotime($begin);
                $timeArr = $Interval->consume($taskRec1->calcedCurrentDuration, $begin, null, $offset);

                // Опит за смятане на очакваното начало/край. Ако не може значи е `9999-12-31 23:59:59`
                $planned[$taskRec1->id] = (object)array('assetId' => $taskRec1->assetId, 'calcedCurrentDuration' => $taskRec1->calcedCurrentDuration, 'expectedTimeStart' => $notFoundDate, 'expectedTimeEnd' => $notFoundDate);
                if(is_array($timeArr)){
                    $planned[$taskRec1->id]->expectedTimeStart = date('Y-m-d H:i:00', $timeArr[0]);
                    $planned[$taskRec1->id]->expectedTimeEnd = date('Y-m-d H:i:00', $timeArr[1]);
                }

                $debugArr[$taskRec1->assetId][$taskRec1->id] = $planned[$taskRec1->id]->expectedTimeStart;
            }
        }

        foreach ($tasksWithoutActualStartByAssetId as $assetId => $assetTasks){
            $Interval = $intervals[$assetId];

            //@todo потребителската подредба

            // Подредба по падеж във възходящ ред
            arr::sortObjects($assetTasks, 'dueDate', 'ASC');

            // След това тези с желано начало се преместват най-отпред
            $withStart = $withoutStart =array();
            foreach ($assetTasks as $t1){
                if(!empty($t1->timeStart)){
                    $withStart[$t1->id] = $t1;
                } else {
                    $withoutStart[$t1->id] = $t1;
                }
            }
            $sortedArr = $withStart + $withoutStart;

            foreach ($sortedArr as $task){

                $isPlannable = true;
                if(!array_key_exists($task->id, $previousTasks)){
                    $startTime = max($now, $task->timeStart);
                } else {
                    $calcedTimes = array();
                    foreach ($previousTasks[$task->id] as $prevId => $prevTask){
                        $plannedPrevTime = $planned[$prevId]->expectedTimeStart;
                        if(empty($plannedPrevTime)) {
                            $isPlannable = false;
                        } else {
                            $plannedPrevTime = dt::addSecs($prevTask->waitingTime, $plannedPrevTime);
                            $calcedTimes[$plannedPrevTime] = $plannedPrevTime;
                        }
                    }

                    if(!$isPlannable) continue;

                    $calcedTimes[$now] = $now;
                    $calcedTimes[$task->timeStart] = $task->timeStart;
                    $startTime = max($calcedTimes);
                }

                $offset = isset($interruptionArr[$task->productId]) ?? null;
                $begin = strtotime($startTime);

                $timeArr = $Interval->consume($task->calcedCurrentDuration, $begin, null, $offset);
                $planned[$task->id] = (object)array('assetId' => $task->assetId, 'calcedCurrentDuration' => $task->calcedCurrentDuration, 'expectedTimeStart' => $notFoundDate, 'expectedTimeEnd' => $notFoundDate);
                if(is_array($timeArr)){
                    $planned[$task->id]->expectedTimeStart = date('Y-m-d H:i:00', $timeArr[0]);
                    $planned[$task->id]->expectedTimeEnd = date('Y-m-d H:i:00', $timeArr[1]);
                }
                $debugArr[$assetId][$task->id] = $planned[$task->id]->expectedTimeStart;
                unset($tasksWithoutActualStartByAssetId[$assetId][$task->id]);
            }
        }


        bp($debugArr, $tasksWithoutActualStartByAssetId);
        bp($tasksWithoutActualStartByAssetId);


        /*
         *3. Прави се един голям подреден масив (Подредба) със всички подредени от потребителя ПО,
         * като подредбата по машини няма? значение. Той се допълва в края от ПО, които не са включени в масива
         * по реда на падежите на техните задания. От целия масив най-напред се изнасят ПО, които имат забити
         *  от потребителя "Най-ранно започване"
         * 4. Цикли се по всички операции, които нямат "Планирано начало". Ако за операцията няма записи
         *  в таблицата с ограниченията, то тя получава поле "Планиране след" - текущото време.
         * Ако има записи за ограничения, то изчисляваме всяко едно ограничение. Ако има ограничение
         * , което не може да се изчисли, защото предходната операция няма планирано начало, то тази ПО се пропуска. От всички изчисления за най-голямо време се определя полето "Планиране след".
         * 5. След като се извлекат всички операции, за които е изчислено "Планиране след",
         * те се подреждат първо по "Планиране след" и след това по реда в който се срещат в "Подредба".
         *  В получената последователност те хранят графиците на машините и получават времена "Планирано начало"
         *  и "Планиран край"
         * 6. Ако в т. 5 е определено планираното начало/край на поне една ПО, то се връщаме на т. 4.
         * 7. След последната итерация, при която няма планирана нито една нова операция,
         * то се записват на всички операции в модела ПО новите времена "Планирано начало" и "Планиран край"
         */






       // foreach ($tas)



        bp($planned);


        $res = array();


        bp($tasksWithActualStart, $tasksWithoutActualStart);

        /*
         * $res = (object)array('id' => $taskRec->id,
                             'expectedTimeStart' => null,
                             'expectedTimeEnd' => null, 'progress' => $taskRec->progress, 'actionNorms' => $taskRec->actionNorms, 'calcedDuration' => $taskRec->calcedDuration, 'calcedCurrentDuration' => $taskRec->calcedCurrentDuration,
                             'indTime' => $taskRec->indTime,
                             'indPackagingId' => $taskRec->indPackagingId,
                             'plannedQuantity' => $taskRec->plannedQuantity,
                             'duration' => $taskRec->timeDuration,
                             'timeStart' => $taskRec->timeStart, 'orderByAssetId' => $taskRec->orderByAssetId);

        // Колко ще е отместването при прекъсване
        $interruptOffset = array_key_exists($taskRec->productId, $interruptionArr) ? $interruptionArr[$taskRec->productId] : null;

        // Прави се опит за добавяне на операцията в графика
        $now = dt::now();
        $begin = null;
        if(!empty($taskRec->timeStart)){
            $begin = $taskRec->timeStart;
        } elseif(!empty($taskRec->timeEnd)){
            $begin = dt::addSecs(-1 * $taskRec->calcedCurrentDuration, $taskRec->timeEnd);
        }

        $begin = max($begin, $now);
        $begin = strtotime($begin);
        $timeArr = $Interval->consume($taskRec->calcedCurrentDuration, $begin, null, $interruptOffset);

        // Ако е успешно записват се началото и края
        if(is_array($timeArr)){
            $res->expectedTimeStart = date('Y-m-d H:i:00', $timeArr[0]);
            $res->expectedTimeEnd = date('Y-m-d H:i:00', $timeArr[1]);
        }

        return $res;
         */







        bp($intervals);
    }


    function act_Test()
    {
        cls::get('planning_Setup')->migrateTaskActualTime2505();
    }
}
