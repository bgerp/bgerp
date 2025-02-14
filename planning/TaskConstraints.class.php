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


    const NOT_FOUND_DATE = '9999-12-21 23:59:59';


    const NOT_PLANNABLE = '9999-12-31 23:59:59';


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


    /**
     * Връща масив с планируемите операции (активни+спрени+събудени+завка)
     *
     * @param array $tasks
     * @param array $fields
     * @return array
     */
    public static function getDefaultArr($tasks = array(), $fields = null)
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


    /**
     * Калкулира и кешира продължителноста на операциите
     *
     * @param array $tasks
     * @return void
     */
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


    /**
     * Калкулира планираните времена
     *
     * @param array $tasks   - масив с операции
     * @param $previousTasks - масив със зависимости на операциите с предходни такива
     * @return object
     */
    public static function calcScheduledTimes($tasks, $previousTasks)
    {
        core_Debug::startTimer('SCHEDULE_CALC_TIMES');
        core_Debug::startTimer('SCHEDULE_PREPARE_INTERVALS');

        // Извличат се графиците на всички ПО с интервали за планиране
        $assetIds = arr::extractValuesFromArray($tasks, 'assetId');
        $intervals = $assets = $idleTimes = array();

        // Извличане на времето за престой
        $idleQuery = planning_AssetIdleTimes::getQuery();
        $idleQuery->in('assetId', $assetIds);
        while ($iRec = $idleQuery->fetch()) {
            $idleTimes[$iRec->assetId][$iRec->id] = $iRec;
        }

        // Извличане на графиците на оборудването
        $debugRes = 'Графици';
        $assetQuery = planning_AssetResources::getQuery();
        $assetQuery->in('id', $assetIds);
        $assetQuery->show("code,taskQuantization,scheduleId,code");
        while ($aRec = $assetQuery->fetch()) {
            $assets[$aRec->id] = $aRec;
            $scheduleId = null;
            if ($Interval = planning_AssetResources::getWorkingInterval($aRec, null, null, $scheduleId)) {
                $assets[$aRec->id]->scheduleName = hr_Schedules::getTitleById($scheduleId);
                $debugRes .= "<li>[$aRec->code]: " . $assets[$aRec->id]->scheduleName;
                if (array_key_exists($aRec->id, $idleTimes)) {
                    arr::sortObjects($idleTimes[$aRec->id], 'date', 'ASC');
                    foreach ($idleTimes[$aRec->id] as $idRec) {
                        $debugRes .= "<li>----Престой {$idRec->date} - {$idRec->duration}";

                        // Времето за престой се премахва
                        $idleBegin = strtotime($idRec->date);
                        $idleEnd = strtotime(dt::addSecs($idRec->duration, $idRec->date));
                        $Interval->cut($idleBegin, $idleEnd);
                    }
                }

                $intervals[$aRec->id] = $Interval;
            }
        }

        core_Debug::stopTimer('SCHEDULE_PREPARE_INTERVALS');
        core_Debug::log("END SCHEDULE_PREPARE_INTERVALS " . round(core_Debug::$timers["SCHEDULE_PREPARE_INTERVALS"]->workingTime, 6));

        // Извлича се ръчната подредба по машини
        $manualQuery = planning_TaskManualOrderPerAssets::getQuery();
        while ($manualRec = $manualQuery->fetch()) {
            if (isset($assets[$manualRec->assetId])) {
                $assets[$manualRec->assetId]->manualOrder = $manualRec->data;
            }
        }

        // От операциите остават само тези, които са на машини с закачени графици
        // Попринцип не би трябвало да има машина без график, но за всеки случай
        $tasksWithActualStart = $tasksWithoutActualStartByAssetId = array();
        $assetsWithIntervals = array_keys($intervals);
        $allTasks = $taskLinks = array();
        $withoutIntervals = array();
        array_walk($tasks, function ($task) use ($assetsWithIntervals, &$allTasks, &$tasksWithActualStart, &$tasksWithoutActualStartByAssetId, &$taskLinks, &$withoutIntervals) {
            if (in_array($task->assetId, $assetsWithIntervals)) {
                $taskLinks[$task->id] = ht::createLink("Opr{$task->id}", array('planning_Tasks', 'single', $task->id), false, 'target=_blank')->getContent();
                $allTasks[$task->id] = $task;

                // Тези които са с фактическо начало се отделят от тези без (спрените ги броим че са без фактическо начало)
                if (!empty($task->actualStart) && $task->state != 'stopped') {
                    $tasksWithActualStart[$task->id] = $task;
                } else {
                    $tasksWithoutActualStartByAssetId[$task->assetId][$task->id] = $task;
                }
            } else {
                $withoutIntervals[$task->id] = $task;
            }
        });

        // Тези с фактическо начало се сортират по възходящ ред
        $interruptionArr = planning_Steps::getInterruptionArr($tasks);
        arr::sortObjects($tasksWithActualStart, 'actualStart', 'ASC');

        // Първо ще се наместят в графика тези с фактическо начало
        $debugRes .= "<hr>Без графици:" . countR($withoutIntervals);
        $debugRes .= "<hr />ВСИЧКИ: " . countR($tasks);
        $debugRes .= "<hr />1. Разполагане на тези с ФАКТИЧЕСКО начало <b>" . countR($tasksWithActualStart) . "</b> <hr />";

        core_Debug::startTimer('START_CYCLE');
        $planned = $plannedByAssets = array();
        $now = dt::now();

        foreach ($tasksWithActualStart as $taskRec1) {
            $begin = max($taskRec1->actualStart, $now);

            // Захранват се графиците със задачите с фактическо начало
            if ($Interval = $intervals[$taskRec1->assetId]) {
                $interruptOffset = array_key_exists($taskRec1->productId, $interruptionArr) ? $interruptionArr[$taskRec1->productId] : null;
                $debugRes .= "{$taskLinks[$taskRec1->id]} храни <b>[{$assets[$taskRec1->assetId]->code}]($taskRec1->assetId)</b> с начало {$begin} / прод. {$taskRec1->calcedCurrentDuration} ";
                $debugRes .= static::feedToInterval($taskRec1, $begin, $interruptOffset, $Interval, $planned);
                $plannedByAssets[$taskRec1->assetId][$taskRec1->id] = $planned[$taskRec1->id];
            }
        }

        // Ще се разполагат след това тези БЕЗ фактическо начало
        $countWithoutActualStart = array_sum(array_map('count', $tasksWithoutActualStartByAssetId));
        $debugRes .= " <hr />2. Разполагане на тези с БЕЗ начало <b>{$countWithoutActualStart}</b>";

        $i = 1;
        do {
            $haveChange = false;
            $debugRes .= "<hr />2.{$i} ИТЕРАЦИЯ НАЧАЛО <b>{$i}</b> <hr />";

            // За всяка операция без начало на всяка машина
            foreach ($tasksWithoutActualStartByAssetId as $assetId => $assetTasks) {
                if (!countR($assetTasks)) continue;

                $debugRes .= " Слагане на задачи на <b>{$assets[$assetId]->code} [{$assets[$assetId]->scheduleName}]</b><br />";
                $Interval = $intervals[$assetId];

                // След това тези с желано начало се преместват най-отпред
                $withStart = $withoutStart = array();
                foreach ($assetTasks as $t1) {
                    if (!empty($t1->timeStart)) {
                        $withStart[$t1->id] = $t1;
                    } else {
                        $withoutStart[$t1->id] = $t1;
                    }
                }
                $sortedArr = $withStart + $withoutStart;

                // Сортираните задачи се обикалят и се проверява изпълнени ли са им ограниченията
                $plannable = array();
                foreach ($sortedArr as $task) {
                    $isPlannable = true;

                    // Ако нямат зависимости от предходни задачи - ще се захранят с по-голямото от желантото начало и сега
                    if (!array_key_exists($task->id, $previousTasks)) {
                        $startTime = max($now, $task->timeStart);
                        $debugRes .= "{$taskLinks[$task->id]} - Няма ограничения <br />";
                    } else {

                        // Ако има ограничения от предходни операции се проверява те изпълнени ли са
                        $debugStr = "";
                        $calcedTimes = array();
                        foreach ($previousTasks[$task->id] as $prevId => $prevTask) {
                            if (!isset($taskLinks[$prevId])) continue;

                            // Предходната операция има ли планирано начало
                            $plannedPrevTime = $planned[$prevId]->expectedTimeStart;
                            if (empty($plannedPrevTime)) {

                                // Ако НЯМА, значи текущата задача не може да се планира, ще се провери на следващата итерация
                                $isPlannable = false;
                                $debugStr .= "|{$taskLinks[$prevId]} not planned|";
                            } else {
                                // Ако е планирана предходната се калкулира за какво време е планирана
                                $debugStr .= "|{$taskLinks[$prevId]} planned: {$plannedPrevTime} - offset {$prevTask->waitingTime}|";
                                $plannedPrevTime = ($plannedPrevTime == static::NOT_FOUND_DATE) ? static::NOT_FOUND_DATE : dt::addSecs($prevTask->waitingTime, $plannedPrevTime);
                                $calcedTimes[$plannedPrevTime] = $plannedPrevTime;
                            }
                        }

                        // Ако не може да се планира текущата - пропуска се, ще се прави опит на следващата итерация
                        if (!$isPlannable) {
                            $debugRes .= "{$taskLinks[$task->id]} - <b>НЕ МОЖЕ ДА СЕ ПЛАНИРА</b> предходни ({$debugStr})<br />";
                            continue;
                        }

                        // Ако може да се планира се взима най-голямото от желаното ѝ начало, сега и времената на предходните ѝ
                        $debugRes .= "{$taskLinks[$task->id]} - <b>МОЖЕ да се планира</b> предходни ({$debugStr})<br />";
                        $calcedTimes[$now] = $now;
                        $calcedTimes[$task->timeStart] = $task->timeStart;
                        $startTime = max($calcedTimes);
                    }

                    $task->_plannedTime = $startTime;
                    $plannable[$task->id] = $task;
                }

                $debugRes .= "{$i}. Планируеми: " . countR($plannable) . "<br />";
                if (!countR($plannable)) continue;

                // Сортират се по-планирано след и после се квантуват спрямо указаното в оборудването
                arr::sortObjects($plannable, '_plannedTime', 'ASC');
                $quantizedArr = static::quantizeByDate($plannable, '_plannedTime', $assets[$assetId]->taskQuantization);

                $quants = countR($quantizedArr);
                $debugRes .= "{$i}.-----Квантуване по: {$assets[$assetId]->taskQuantization} [{$quants}]<br />";
                $carryOver = array();
                foreach ($quantizedArr as $quant => $objects) {
                    // Обединяваме текущите остатъци с обектите от текущия квант
                    $objects = $carryOver + $objects;

                    // Преподреждане на кванта
                    $manualOrderStr = "";
                    if (isset($assets[$assetId]->manualOrder)) {
                        $manualOrderStr = "приложена ръчна подредба " . countR($assets[$assetId]->manualOrder);
                        $objects = arr::reorderArrayByOrderedKeys($objects, $assets[$assetId]->manualOrder);
                    }

                    $debugRes .= "-----Квант: {$quant} - " . implode(',', array_keys($objects)) . " [{$manualOrderStr}]<br />";

                    // Разделяне обектите на две половини
                    $half = (int)ceil(count($objects) / 2);
                    $firstHalf = array_slice($objects, 0, $half, true);
                    $carryOver = array_slice($objects, $half, null, true);

                    // Първата половина ще захранят графика
                    foreach ($firstHalf as $task) {
                        $haveChange = true;
                        $interruptOffset = array_key_exists($task->productId, $interruptionArr) ? $interruptionArr[$task->productId] : null;
                        $debugRes .= "{$taskLinks[$task->id]} храни <b>[{$assets[$task->assetId]->code}]($task->assetId)</b> с начало {$task->_plannedTime} / прод. {$task->calcedCurrentDuration} <br />";
                        $debugRes .= self::feedToInterval($task, $task->_plannedTime, $interruptOffset, $Interval, $planned);

                        // Веднъж сметнати, че са планирани - махат се от масива
                        $plannedByAssets[$assetId][$task->id] = $planned[$task->id];
                        unset($tasksWithoutActualStartByAssetId[$assetId][$task->id]);
                    }
                }

                if (countR($carryOver)) {
                    $debugRes .= "-----Квант ОСТАТЪК - " . implode(',', array_keys($carryOver)) . "<br />";
                }

                // Ако има остатъчен квант захранва се и той на графика
                foreach ($carryOver as $t1) {
                    $haveChange = true;
                    $interruptOffset = array_key_exists($t1->productId, $interruptionArr) ? $interruptionArr[$t1->productId] : null;
                    $debugRes .= "{$taskLinks[$t1->id]} храни <b>[{$assets[$t1->assetId]->code}]($t1->assetId)</b> с начало {$t1->_plannedTime} / прод. {$t1->calcedCurrentDuration} <br />";

                    $debugRes .= self::feedToInterval($t1, $t1->_plannedTime, $interruptOffset, $Interval, $planned);
                    $plannedByAssets[$assetId][$t1->id] = $planned[$t1->id];
                    unset($tasksWithoutActualStartByAssetId[$assetId][$t1->id]);
                }
            }

            $countWithoutActualStart = array_sum(array_map('count', $tasksWithoutActualStartByAssetId));
            $debugRes .= "<hr />ИТЕРАЦИЯ КРАЙ <b>{$i}</b> ПЛАНИРАНИ " . countR($planned) . " / НЕПЛАНИРАНИ {$countWithoutActualStart}";
            $i++;
        } while ($haveChange);

        // Накрая се добавят и непланираните
        $notPlanned = array();
        foreach ($tasksWithoutActualStartByAssetId as $assetId => $notPlannedTasks) {
            $notPlanned += $notPlannedTasks;
            foreach ($notPlannedTasks as $notPlannedTask) {
                $plannedByAssets[$assetId][$notPlannedTask->id] = (object)array('id' => $notPlannedTask->id, 'assetId' => $notPlannedTask->assetId, 'calcedCurrentDuration' => $notPlannedTask->calcedCurrentDuration, 'expectedTimeStart' => self::NOT_PLANNABLE, 'expectedTimeEnd' => self::NOT_PLANNABLE);
            }
        }

        $debugRes .= "<hr />КРАЙНО НЕПЛАНИРАНИ: " . implode(', ', array_keys($notPlanned)) . "<br />";

        core_Debug::stopTimer('SCHEDULE_CALC_TIMES');
        core_Debug::log("END SCHEDULE_CALC_TIMES " . round(core_Debug::$timers["SCHEDULE_CALC_TIMES"]->workingTime, 6));

        return (object)array('tasks' => $plannedByAssets, 'notPlanned' => $notPlanned, 'debug' => $debugRes);
    }


    /**
     * Храни графика с и извлича планираното начало/край
     *
     * @param stdClass $task           - записа на задачата
     * @param string $begin            - изчисленото начало на задачата
     * @param int $interrupedOffset    - отместването при прекъсване
     * @param core_Intervals $Interval - инстанцията на интервала
     * @param array $planned
     * @return string
     */
    private static function feedToInterval($task, $begin, $interrupedOffset, &$Interval, &$planned)
    {
        $planned[$task->id] = (object)array('id' => $task->id, 'assetId' => $task->assetId, 'calcedCurrentDuration' => $task->calcedCurrentDuration, 'expectedTimeStart' => self::NOT_FOUND_DATE, 'expectedTimeEnd' => self::NOT_FOUND_DATE);

        if($begin != self::NOT_FOUND_DATE) {
            $begin = strtotime($begin);
            $timeArr = $Interval->consume($task->calcedCurrentDuration, $begin, null, $interrupedOffset);

            if(is_array($timeArr)){
                $planned[$task->id]->expectedTimeStart = date('Y-m-d H:i:00', $timeArr[0]);
                $planned[$task->id]->expectedTimeEnd = date('Y-m-d H:i:00', $timeArr[1]);
                return "--------Изчислено за S: <b>{$planned[$task->id]->expectedTimeStart}</b> / Е: <b>{$planned[$task->id]->expectedTimeEnd}</b> <br />";
            }

            return "--------Не е изчислено начало/край<br />";
        } else {
            return "--------Е ИЗВЪН ГРАФИКА<br />";
        }
    }


    /**
     * Групиране на операците по кванти
     *
     * @param array $plannable  - масив с операции
     * @param string $field     - кое поле да се използва за квантуване
     * @param string $type      - какъв да е кванта: ден/седмица/месец/
     * @return array  $result   - групирани записите по кванти
     */
    private static function quantizeByDate($plannable, $field, $type)
    {
        expect(in_array($type, array('day', 'weekly', 'month')), $type);
        $result = array();

        foreach ($plannable as $key => $object) {
            if (!isset($object->{$field}) || strtotime($object->{$field}) === false) continue;

            $date = new DateTime($object->$field);
            switch ($type) {
                case 'day':
                    $bucket = $date->format('Y-m-d');
                    break;
                case 'weekly':
                    $bucket = $date->format('o-\WW'); // Година + седмица
                    break;
                default:
                    $bucket = $date->format('Y-m');
            }

            $result[$bucket][$key] = $object;
        }

        return $result;
    }


    /**
     * Дебъг екшън за ръчна преподредба
     */
    function act_Order()
    {
        requireRole('debug');

        Mode::push('debugOrder', true);
        $res = cls::get('planning_AssetResources')->cron_RecalcTaskTimes();
        Mode::pop('debugOrder');

        echo $res->debug;
        bp($res->tasks);
    }
}
