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
    public $listFields = 'taskId,type,previousTaskId=Предходна,intersect=Застъпване,earliestTimeStart=Най-рано,waitingTime=Изчакване,updatedOn';


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
        $this->FLD('intersect', 'enum(yes=Да,no=Не)', 'caption=Застъпване,input=none,notNull,default=yes');
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
     * Връща масив с планируемите операции
     *
     * @param array|null $tasks  - масив с конкретни операции или null за всички (активни+спрени+събудени+завка)
     * @param array|null $fields - кои полета
     * @return array $tasks
     */
    public static function getDefaultArr($tasks = array(), $fields = null)
    {
        $arr = arr::make($tasks, true);

        $tQuery = planning_Tasks::getQuery();
        $tQuery->EXT('innerClass', 'cat_Products', "externalName=innerClass,externalKey=productId");
        $tQuery->EXT('jobProductId', 'planning_Jobs', "externalName=productId,remoteKey=containerId,externalFieldName=originId");
        $tQuery->EXT('dueDate', 'planning_Jobs', 'externalName=dueDate,remoteKey=containerId,externalFieldName=originId,caption=Задание->Падеж');
        if(isset($fields)){
            $fields = arr::make($fields, true);
            $tQuery->show(implode(',', $fields));
        }

        if (!countR($arr)) {
            // Ако не са подадени конкретни ид-та извличат се тези, които са готови за планиране  и са към оборудване
            $stepClassId = planning_interface_StepProductDriver::getClassId();
            $tQuery->in('state', array('active', 'wakeup', 'stopped', 'pending'));
            $tQuery->where("#innerClass = {$stepClassId} AND #assetId IS NOT NULL");
        } else {
            // Иначе конкретно, които са подадени
            $ids = array();
            foreach ($arr as $id) {
                $key = is_numeric($id) ? $id : $id->id;
                $ids[$key] = $key;
            }
            $tQuery->in('id', $ids);
        }

        $tasks = $tQuery->fetchAll();

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

        $res = $prevSteps = $tasksByJobs = $previousTaskByJobOrder = $stepIds = $jobIds = $folderIds = $folderLocations = array();
        foreach ($tasks as $tRec) {
            $stepIds[$tRec->productId] = $tRec->productId;
            $jobIds[$tRec->originId] = $tRec->originId;
            $folderIds[$tRec->folderId] = $tRec->folderId;
        }

        // Извличане на локациите на които са центровете на дейност на етапа
        $cQuery = planning_Centers::getQuery();
        $cQuery->EXT('locationId', 'hr_Departments', 'externalName=locationId,externalKey=departmentId');
        if(countR($folderIds)){
            $cQuery->in('folderId', $folderIds);
        }
        $cQuery->show('locationId,folderId');
        while ($cRec = $cQuery->fetch()) {
            $folderLocations[$cRec->folderId] = $cRec->locationId ?? '-';
        }
        foreach ($folderIds as $folderId) {
            $folderLocations[$folderId] = $folderLocations[$folderId] ?? '-';
        }

        // Извличане на всички етапи, които са посочени като предишни
        $cQuery = planning_StepConditions::getQuery();
        $cQuery->in("stepId", $stepIds);
        $cQuery->show('stepId,prevStepId,delay,intersect');
        while ($cRec = $cQuery->fetch()) {
            $prevSteps[$cRec->stepId][$cRec->prevStepId] = $cRec;
        }

        // Всички текущи ПО към заданието за посочените етапи
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#state IN ('active', 'stopped', 'wakeup', 'pending')");
        $tQuery->in('originId', $jobIds);
        $tQuery->show('id,originId,productId,folderId,offsetAfter,saoOrder,assetId');
        $additionalFolderIds = array();
        while ($tRec = $tQuery->fetch()) {
            $tasksByJobs[$tRec->originId][$tRec->id] = (object)array('productId' => $tRec->productId, 'id' => $tRec->id, 'folderId' => $tRec->folderId, 'offsetAfter' => $tRec->offsetAfter, 'saoOrder' => $tRec->saoOrder, 'assetId' => $tRec->assetId);
            if (!isset($folderLocations[$tRec->folderId])) {
                $additionalFolderIds[$tRec->folderId] = $tRec->folderId;
            }
        }

        // Редът в заданието е технологичен ред, а не само ред за визуализация.
        foreach ($tasksByJobs as $originId => &$tasksInJob) {
            uasort($tasksInJob, function($a, $b) {
                if ($a->saoOrder == $b->saoOrder) {
                    return ($a->id < $b->id) ? -1 : 1;
                }

                return ($a->saoOrder < $b->saoOrder) ? -1 : 1;
            });

            $previousTaskId = null;
            foreach ($tasksInJob as $taskInJob) {
                if (isset($previousTaskId)) {
                    $previousTaskByJobOrder[$originId][$taskInJob->id] = $previousTaskId;
                }
                $previousTaskId = $taskInJob->id;
            }
        }
        unset($tasksInJob);

        if (countR($additionalFolderIds)) {
            $cQuery = planning_Centers::getQuery();
            $cQuery->EXT('locationId', 'hr_Departments', 'externalName=locationId,externalKey=departmentId');
            $cQuery->in('folderId', $additionalFolderIds);
            $cQuery->show('locationId,folderId');
            while ($cRec = $cQuery->fetch()) {
                $folderLocations[$cRec->folderId] = $cRec->locationId ?? '-';
            }
            foreach ($additionalFolderIds as $folderId) {
                $folderLocations[$folderId] = $folderLocations[$folderId] ?? '-';
            }
        }

        $offsetSameLocation = planning_Setup::get('TASK_OFFSET_IN_SAME_LOCATION');
        $offsetOtherLocation = planning_Setup::get('TASK_OFFSET_IN_OTHER_LOCATION');

        $now = dt::now();
        foreach ($tasks as $taskRec) {

            // Ако има посочено най-ранно начало и то е в бъдещето - записва се
            if (!empty($taskRec->timeStart)) {
                if ($taskRec->timeStart > $now) {
                    $res["time|{$taskRec->id}"] = (object)array('taskId' => $taskRec->id, 'type' => 'earliest', 'earliestTimeStart' => $taskRec->timeStart, 'waitingTime' => null, 'previousTaskId' => null, 'intersect' => 'yes', 'updatedOn' => $now);
                }
            }

            // Ако има ръчно посочена и все още планируема предходна - нея.
            // При приключена/оттеглена предходна се продължава назад по реда на
            // заданието до най-близката планируема операция.
            $prevTaskIds = array();
            $hasPlanableManualPrevious = isset($taskRec->previousTask) && isset($tasksByJobs[$taskRec->originId][$taskRec->previousTask]);
            if ($hasPlanableManualPrevious) {
                $prevTaskProductId = $tasksByJobs[$taskRec->originId][$taskRec->previousTask]->productId;
                $prevTaskIds[$taskRec->previousTask] = $prevSteps[$taskRec->productId][$prevTaskProductId] ?? null;
            } else {
                $prevStepsArr = array_key_exists($taskRec->productId, $prevSteps) ? $prevSteps[$taskRec->productId] : array();
                foreach ($tasksByJobs[$taskRec->originId] as $a) {
                    if (array_key_exists($a->productId, $prevStepsArr)) {
                        $prevTaskIds[$a->id] = $prevStepsArr[$a->productId];
                    }
                }

                // Ако няма ръчно избрана предходна операция, непосредствената предходна
                // по реда на заданието остава твърдо ограничение. При липса на изрично
                // условие между етапите не се допуска застъпване.
                $orderedPreviousTaskId = $previousTaskByJobOrder[$taskRec->originId][$taskRec->id] ?? null;
                if (isset($orderedPreviousTaskId) && !isset($prevTaskIds[$orderedPreviousTaskId])) {
                    $prevTaskIds[$orderedPreviousTaskId] = (object)array('delay' => 0, 'intersect' => 'no');
                }
            }

            if(countR($prevTaskIds)){

                // За всяка предходна ще се добави че операцията е зависима от нея
                $thisTaskLocationId = $folderLocations[$tasksByJobs[$taskRec->originId][$taskRec->id]->folderId] ?? '-';
                foreach ($prevTaskIds as $prevTaskId => $stepCondition) {

                    // Гледа се дали текущата и предходната са в една локация или са в различни.
                    // При едно и също оборудване няма транспорт между операциите.
                    $prevTaskLocationId = $folderLocations[$tasksByJobs[$taskRec->originId][$prevTaskId]->folderId] ?? '-';
                    $isSameAsset = isset($tasksByJobs[$taskRec->originId][$taskRec->id]->assetId, $tasksByJobs[$taskRec->originId][$prevTaskId]->assetId)
                        && $tasksByJobs[$taskRec->originId][$taskRec->id]->assetId == $tasksByJobs[$taskRec->originId][$prevTaskId]->assetId;
                    $locationOffset = $isSameAsset ? 0 : (($thisTaskLocationId == $prevTaskLocationId) ? $offsetSameLocation : $offsetOtherLocation);

                    // Времето за изчакване е по-голямото от това за локацията и зададеното в етапа време на изчакване
                    $conditionDelay = (is_object($stepCondition) && isset($stepCondition->delay)) ? $stepCondition->delay : 0;
                    $intersect = (is_object($stepCondition) && isset($stepCondition->intersect)) ? $stepCondition->intersect : 'yes';
                    $waitingTime = max($locationOffset, $tasksByJobs[$taskRec->originId][$prevTaskId]->offsetAfter, $conditionDelay);
                    $res["prev|{$taskRec->id}|$prevTaskId"] = (object)array('taskId' => $taskRec->id, 'type' => 'prevId', 'earliestTimeStart' => null, 'waitingTime' => $waitingTime, 'previousTaskId' => $prevTaskId, 'intersect' => $intersect, 'updatedOn' => $now);
                }
            }
        }

        // Извличат се записите за посочените операции
        $taskIds = arr::extractValuesFromArray($tasks, 'id');
        $exQuery = static::getQuery();
        if(countR($taskIds)){
            $exQuery->in("taskId", $taskIds);
        } else {
            $exQuery->where("1=2");
        }
        $exRecs = $exQuery->fetchAll();
        $me = cls::get(get_called_class());

        // Синхронизират се
        $synced = arr::syncArrays($res, $exRecs, 'taskId,type,previousTaskId', 'taskId,type,earliestTimeStart,waitingTime,previousTaskId,intersect');
        $i = countR($synced['insert']);
        if ($i) {
            $me->saveArray($synced['insert']);
        }

        $u = countR($synced['update']);
        if ($u) {
            $me->saveArray($synced['update'], 'id,previousTaskId,waitingTime,earliestTimeStart,intersect,updatedOn');
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
     * Checks whether the submitted resource order introduces a new reversal
     * of a hard technological dependency on the edited resource.
     *
     * @param int $assetId
     * @param array $orderedTaskIds
     * @param array $problemTaskIds
     * @param array $taskAssetOverrides
     * @param array $packageLinks
     * @return bool
     */
    public static function validateManualOrder($assetId, $orderedTaskIds, &$problemTaskIds = array(), $taskAssetOverrides = array(), $packageLinks = array())
    {
        $problemTaskIds = array();
        $tasks = static::getDefaultArr(null, 'actualStart,timeStart,assetId,dueDate,state,originId,saoOrder');
        $remaining = $tasksByAsset = $allTasksByAsset = $allTasksById = $oldTasksOnAsset = array();
        foreach ($tasks as $task) {
            if ($task->assetId == $assetId) {
                $oldTasksOnAsset[$task->id] = clone $task;
            }
            if (isset($taskAssetOverrides[$task->id])) {
                $task->assetId = $taskAssetOverrides[$task->id];
            }

            $allTasksById[$task->id] = $task;
            $allTasksByAsset[$task->assetId][$task->id] = $task;
            if (!empty($task->actualStart) && $task->state != 'stopped') {
                continue;
            }

            $remaining[$task->id] = $task;
            $tasksByAsset[$task->assetId][$task->id] = $task;
        }

        if (!countR($remaining)) {
            return true;
        }

        $constraints = array();
        $constraintQuery = static::getQuery();
        $constraintQuery->where("#type = 'prevId'");
        $constraintQuery->show('taskId,previousTaskId');
        while ($constraint = $constraintQuery->fetch()) {
            if (isset($remaining[$constraint->taskId]) && isset($remaining[$constraint->previousTaskId])) {
                $constraints[] = $constraint;
            }
        }

        $cleanOrder = array();
        foreach ((array)$orderedTaskIds as $taskId) {
            $taskId = (int)$taskId;
            if (isset($allTasksByAsset[$assetId][$taskId])) {
                $cleanOrder[$taskId] = $taskId;
            }
        }
        $unstartedBeforeActive = array();
        foreach ($cleanOrder as $taskId) {
            $task = $allTasksByAsset[$assetId][$taskId];
            $isStarted = !empty($task->actualStart) && $task->state != 'stopped';
            if ($isStarted) {
                if (count($unstartedBeforeActive)) {
                    $problemTaskIds[$taskId] = $taskId;
                    foreach ($unstartedBeforeActive as $unstartedTaskId) {
                        $problemTaskIds[$unstartedTaskId] = $unstartedTaskId;
                    }
                }
            } else {
                $unstartedBeforeActive[$taskId] = $taskId;
            }
        }
        $requiredPackageLinks = static::getSameResourceJobPackageLinks($allTasksById, $assetId);
        $cleanPositions = array_flip(array_values($cleanOrder));
        foreach ($requiredPackageLinks as $taskId => $previousTaskId) {
            if (isset($cleanPositions[$taskId], $cleanPositions[$previousTaskId])
                && $cleanPositions[$taskId] != $cleanPositions[$previousTaskId] + 1) {
                $problemTaskIds[$previousTaskId] = $previousTaskId;
                $problemTaskIds[$taskId] = $taskId;
            }
        }
        $oldManualOrder = planning_TaskManualOrderPerAssets::fetchField("#assetId = {$assetId}", 'data');
        $oldPositions = static::getManualOrderPositions($oldTasksOnAsset, $oldManualOrder);
        $newPositions = static::getManualOrderPositions($allTasksByAsset[$assetId] ?? array(), $cleanOrder);

        foreach ($constraints as $constraint) {
            $task = $remaining[$constraint->taskId];
            $previousTask = $remaining[$constraint->previousTaskId];
            if ($task->assetId != $assetId || $previousTask->assetId != $assetId) {
                continue;
            }

            if (static::isNewManualOrderConflict($task->id, $previousTask->id, $newPositions, $oldPositions)) {
                $problemTaskIds[$previousTask->id] = $previousTask->id;
                $problemTaskIds[$task->id] = $task->id;
            }
        }

        $packageLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks(
            $cleanOrder,
            static::mergeRequiredPackageLinks($requiredPackageLinks, $packageLinks)
        );
        if (countR($packageLinks)) {
            $adjacency = array();
            foreach ($constraints as $constraint) {
                $adjacency[$constraint->previousTaskId][$constraint->taskId] = $constraint->taskId;
            }

            // Existing packages on the other resources participate in the global graph.
            $manualQuery = planning_TaskManualOrderPerAssets::getQuery();
            $manualQuery->where("#assetId != {$assetId}");
            $manualQuery->show('data,packageLinks');
            while ($manualRec = $manualQuery->fetch()) {
                $existingLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks($manualRec->data, $manualRec->packageLinks);
                foreach ($existingLinks as $taskId => $previousTaskId) {
                    if (isset($remaining[$taskId], $remaining[$previousTaskId])) {
                        $adjacency[$previousTaskId][$taskId] = $taskId;
                    }
                }
            }

            foreach ($packageLinks as $taskId => $previousTaskId) {
                if (!isset($allTasksById[$taskId], $allTasksById[$previousTaskId])) {
                    continue;
                }
                if ($allTasksById[$taskId]->assetId != $assetId || $allTasksById[$previousTaskId]->assetId != $assetId
                    || (!empty($allTasksById[$taskId]->actualStart) && $allTasksById[$taskId]->state != 'stopped')) {
                    $problemTaskIds[$previousTaskId] = $previousTaskId;
                    $problemTaskIds[$taskId] = $taskId;
                    continue;
                }

                if (static::hasPlanningPath($taskId, $previousTaskId, $adjacency)) {
                    $problemTaskIds[$previousTaskId] = $previousTaskId;
                    $problemTaskIds[$taskId] = $taskId;
                    continue;
                }

                $adjacency[$previousTaskId][$taskId] = $taskId;
            }
        }

        return !countR($problemTaskIds);
    }


    /**
     * Checks for a directed path in the planning dependency graph.
     */
    private static function hasPlanningPath($fromId, $toId, $adjacency)
    {
        if ($fromId == $toId) return true;

        $stack = array($fromId);
        $visited = array();
        while (count($stack)) {
            $currentId = array_pop($stack);
            if (isset($visited[$currentId])) continue;
            $visited[$currentId] = true;

            foreach ($adjacency[$currentId] ?? array() as $nextId) {
                if ($nextId == $toId) return true;
                if (!isset($visited[$nextId])) $stack[] = $nextId;
            }
        }

        return false;
    }


    /**
     * Returns task positions after applying a resource manual order.
     */
    private static function getManualOrderPositions($tasks, $manualOrder)
    {
        $withStart = $withoutStart = array();
        foreach ($tasks as $task) {
            if (!empty($task->timeStart)) {
                $withStart[$task->id] = $task;
            } else {
                $withoutStart[$task->id] = $task;
            }
        }

        arr::sortObjects($withStart, 'timeStart', 'ASC');
        arr::sortObjects($withoutStart, 'dueDate', 'ASC');
        $orderedTasks = static::reorderTasksByManualOrder($withStart + $withoutStart, $manualOrder);
        $positions = array();
        foreach ($orderedTasks as $task) {
            $positions[$task->id] = countR($positions);
        }

        return $positions;
    }


    /**
     * Returns whether a reversed dependency was introduced by the new order.
     */
    private static function isNewManualOrderConflict($taskId, $previousTaskId, $newPositions, $oldPositions)
    {
        if (!isset($newPositions[$taskId]) || !isset($newPositions[$previousTaskId])) return false;
        if ($newPositions[$taskId] > $newPositions[$previousTaskId]) return false;

        $wasComparable = isset($oldPositions[$taskId]) && isset($oldPositions[$previousTaskId]);
        $wasReversed = $wasComparable && $oldPositions[$taskId] < $oldPositions[$previousTaskId];

        return !$wasReversed;
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

        $taskIds = $assetInTasks = $normsByTask = $jobContainers = array();
        $productIds = arr::extractValuesFromArray($tasks, 'productId');
        foreach ($tasks as $taskRec) {
            $taskIds[$taskRec->id] = $taskRec->id;
            $jobContainers[$taskRec->originId] = $taskRec->originId;
            if (isset($taskRec->assetId)) {
                $assetInTasks[$taskRec->assetId] = $taskRec->assetId;
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
        $pQuery->where("#type = 'input' AND #canStore != 'yes' AND #indTime IS NOT NULL");
        $pQuery->in('taskId', $taskIds);
        $pQuery->show('productId,taskId,plannedQuantity,indTime,totalTime');
        while ($pRec = $pQuery->fetch()) {
            $indTimeNorm = planning_type_ProductionRate::getInSecsByQuantity($pRec->indTime, $pRec->plannedQuantity);
            $totalTimeNorm = planning_type_ProductionRate::getInSecsByQuantity($pRec->totalTime, $pRec->plannedQuantity);

            $normsByTask[$pRec->taskId][$pRec->productId]['total'] = $indTimeNorm;
            $normsByTask[$pRec->taskId][$pRec->productId]['rest'] = max(round($indTimeNorm - $totalTimeNorm, 2), 0);
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
            $cMinDuration = ($t->progress >= 1) ? $minDuration : $duration;
            $duration = max($duration, $cMinDuration);

            // Към така изчислената продължителност се добавя тази от действията към машината
            if (array_key_exists($t->id, $normsByTask)) {
                $duration += arr::sumValuesArray($normsByTask[$t->id], 'rest');
                $nettDuration += arr::sumValuesArray($normsByTask[$t->id], 'total');
            }

            $t->calcedDuration = $nettDuration;
            $t->calcedCurrentDuration = $duration;
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
     * @param array $options - optional planning mode and in-memory order overrides
     * @return object
     */
    public static function calcScheduledTimes($tasks, $previousTasks, $now = null, $options = array())
    {
        $now = $now ?? dt::now();

        core_Debug::startTimer('SCHEDULE_CALC_TIMES');
        core_Debug::startTimer('SCHEDULE_PREPARE_INTERVALS');

        // Извличат се графиците на всички ПО с интервали за планиране
        $assetIds = arr::extractValuesFromArray($tasks, 'assetId');
        $intervals = $calendarIntervals = $assets = $idleTimes = array();

        // Извличане на времето за престой
        $idleQuery = planning_AssetScheduleBreaks::getQuery();
        if(countR($assetIds)){
            $idleQuery->in('assetId', $assetIds);
        }
        while ($iRec = $idleQuery->fetch()) {
            $idleTimes[$iRec->assetId][$iRec->id] = $iRec;
        }

        // Извличане на графиците на оборудването
        $debugRes = 'Графици';
        $assetQuery = planning_AssetResources::getQuery();
        if(countR($assetIds)){
            $assetQuery->in('id', $assetIds);
        }

        $assetQuery->show('code,taskQuantization,scheduleId,planningParams,planningParamSimilarity,planningParamGroupDays,groupId');
        while ($aRec = $assetQuery->fetch()) {
            $assets[$aRec->id] = $aRec;
            $scheduleId = null;
            if ($Interval = planning_AssetResources::getWorkingInterval($aRec, $now, null, $scheduleId)) {
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

                $calendarIntervals[$aRec->id] = clone $Interval;
                $intervals[$aRec->id] = $Interval;
            }
        }

        $groupIds = arr::extractValuesFromArray($assets, 'groupId');
        $groupPlanningParams = array();
        if (countR($groupIds)) {
            $groupQuery = planning_AssetGroups::getQuery();
            $groupQuery->in('id', $groupIds);
            $groupQuery->show('planningParams');
            while ($groupRec = $groupQuery->fetch()) {
                $groupPlanningParams[$groupRec->id] = $groupRec->planningParams;
            }
        }
        foreach ($assets as $assetRec) {
            $assetRec->planningParams = keylist::merge($assetRec->planningParams, $groupPlanningParams[$assetRec->groupId] ?? null);
        }
        static::setPlanningParamSignatures($tasks, $assets);

        core_Debug::stopTimer('SCHEDULE_PREPARE_INTERVALS');
        core_Debug::log("END SCHEDULE_PREPARE_INTERVALS " . round(core_Debug::$timers["SCHEDULE_PREPARE_INTERVALS"]->workingTime, 6));

        // Извлича се ръчната подредба по машини
        $manualQuery = planning_TaskManualOrderPerAssets::getQuery();
        while ($manualRec = $manualQuery->fetch()) {
            if (isset($assets[$manualRec->assetId])) {
                $assets[$manualRec->assetId]->manualOrder = $manualRec->data;
                $assets[$manualRec->assetId]->packageLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks($manualRec->data, $manualRec->packageLinks);
                $assets[$manualRec->assetId]->committedTaskId = $manualRec->committedTaskId ?? null;
                $assets[$manualRec->assetId]->autoGroupVersion = (int)($manualRec->autoGroupVersion ?? 0);
            }
        }

        $optimizeAssetIds = array_fill_keys(array_map('intval', (array)($options['optimizeAssetIds'] ?? array())), true);
        $regroupAssetIds = array_fill_keys(array_map('intval', (array)($options['regroupAssetIds'] ?? array())), true);
        foreach ($assets as $assetId => $assetRec) {
            if (isset($options['manualOrderOverrides'][$assetId])) {
                $assetRec->manualOrder = array_values((array)$options['manualOrderOverrides'][$assetId]);
            }
            if (isset($options['packageLinkOverrides'][$assetId])) {
                $assetRec->packageLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks(
                    $assetRec->manualOrder ?? array(),
                    $options['packageLinkOverrides'][$assetId]
                );
            }
            if (isset($optimizeAssetIds[$assetId])) {
                $assetRec->_forceOptimization = true;
            }
            if (isset($regroupAssetIds[$assetId])) {
                $assetRec->_forceParamRegroup = true;
            }
        }

        $plannedByAssets = $notPlanned = array();

        $manualPlanning = planning_Setup::get('MANUAL_ORDER_IN_ASSET');
        if($manualPlanning == 'no'){
            $debugRes .= self::smartPlanningGraph($plannedByAssets, $notPlanned, $intervals, $assets, $tasks, $now, $previousTasks);
        } else {
            $debugRes .= self::manualPlanning($plannedByAssets, $notPlanned, $intervals, $assets, $tasks, $now, $previousTasks);
        }

        return (object)array('tasks' => $plannedByAssets, 'notPlanned' => $notPlanned, 'assets' => $assets, 'intervals' => $intervals, 'calendarIntervals' => $calendarIntervals, 'debug' => $debugRes);
    }

    /**
     * Умно планиране на операциите на машините
     *
     * @param array $plannedByAssets
     * @param array $notPlanned
     * @param array $intervals
     * @param array $assets
     * @param array $tasks
     * @param datetime $now
     * @param array $previousTasks
     * @return string
     */
    private static function manualPlanning(&$plannedByAssets, &$notPlanned, $intervals, $assets, $tasks, $now, $previousTasks)
    {
        $planned = $reservedByAssets = array();

        $debugRes = "<hr />РЪЧНО ПЛАНИРАНЕ<hr / >";
        $assetsWithIntervals = array_keys($intervals);
        $taskLinks = $tasksWithIntervals = $tasksByAssets = array();
        foreach ($tasks as $task) {
            if (in_array($task->assetId, $assetsWithIntervals)) {
                $taskLinks[$task->id] = ht::createLink("Opr{$task->id}", array('planning_Tasks', 'single', $task->id), false, 'target=_blank')->getContent();
                $tasksWithIntervals[$task->id] = $task;
                $tasksByAssets[$task->assetId][$task->id] = $task;
            }
        }
        $interruptionArr = planning_Steps::getInterruptionArr($tasks);

        foreach ($assets as $assetRec){
            $tasksInAsset = $tasksByAssets[$assetRec->id];
            arr::sortObjects($tasksInAsset, 'dueDate', 'ASC');
            $debugRes .= "<hr />Слагане на задачи на <b>{$assets[$assetRec->id]->code} [{$assets[$assetRec->id]->scheduleName}]</b><br />";


            if(is_array($assets[$assetRec->id]->manualOrder)){
                $debugRes .=  "<hr />Приложена ръчна подредба: " . countR($assets[$assetRec->id]->manualOrder);
                $tasksInAsset = arr::reorderArrayByOrderedKeys($tasksInAsset, $assets[$assetRec->id]->manualOrder);
            } else {
                $debugRes .=  "<hr />НЯМА ръчна подредба";
            }

            foreach ($tasksInAsset as $taskRec1){
                $begin = max($taskRec1->modifiedOn, $now);
                if(!$taskRec1->calcedCurrentDuration){
                    $taskRec1->calcedCurrentDuration  = 1;
                }

                // Захранват се графиците със задачите с фактическо начало
                if ($Interval = $intervals[$taskRec1->assetId]) {
                    $interruptOffset = array_key_exists($taskRec1->productId, $interruptionArr) ? $interruptionArr[$taskRec1->productId] : null;
                    $debugRes .= "<hr />{$taskLinks[$taskRec1->id]} храни <b>[{$assets[$taskRec1->assetId]->code}]($taskRec1->assetId)</b> с начало {$begin} / прод. {$taskRec1->calcedCurrentDuration} ";
                    $debugRes .= static::feedToInterval($taskRec1, $begin, $interruptOffset, $Interval, $planned, $reservedByAssets);
                    $plannedByAssets[$taskRec1->assetId][$taskRec1->id] = $planned[$taskRec1->id];
                }
            }
        }

        return $debugRes;
    }

    /**
     * Умно планиране на операциите на машините
     *
     * @param array $plannedByAssets
     * @param array $notPlanned
     * @param array $intervals
     * @param array $assets
     * @param array $tasks
     * @param datetime $now
     * @param array $previousTasks
     * @return string
     */
    private static function smartPlanningGraph(&$plannedByAssets, &$notPlanned, $intervals, $assets, $tasks, $now, $previousTasks)
    {
        $planned = $reservedByAssets = array();
        $tasksWithActualStart = $tasksWithoutActualStartByAssetId = $allTasks = $taskLinks = array();
        $withoutIntervalCount = 0;
        $assetsWithIntervals = array_fill_keys(array_keys($intervals), true);
        $debugOrder = Mode::is('debugOrder');

        foreach ($tasks as $task) {
            $allTasks[$task->id] = $task;
            if (isset($assetsWithIntervals[$task->assetId])) {
                if ($debugOrder) {
                    $taskLinks[$task->id] = ht::createLink("Opr{$task->id}", array('planning_Tasks', 'single', $task->id), false, 'target=_blank')->getContent();
                }
                if (!empty($task->actualStart) && $task->state != 'stopped') {
                    $tasksWithActualStart[$task->id] = $task;
                } else {
                    $tasksWithoutActualStartByAssetId[$task->assetId][$task->id] = $task;
                }
            } else {
                $withoutIntervalCount++;
            }
        }

        $debugRes = "<hr />УМНО ПЛАНИРАНЕ (ГРАФ)<hr />Без графици: {$withoutIntervalCount}";
        $debugRes .= "<hr />ВСИЧКИ: " . countR($tasks);
        $interruptionArr = planning_Steps::getInterruptionArr($tasks);

        // Tasks already in progress reserve the corresponding resource intervals first.
        arr::sortObjects($tasksWithActualStart, 'actualStart', 'ASC');
        foreach ($tasksWithActualStart as $task) {
            $task->calcedCurrentDuration = max(1, (int)$task->calcedCurrentDuration);
            $begin = max($task->actualStart, $now);
            $Interval = $intervals[$task->assetId];
            $interruptOffset = $interruptionArr[$task->productId] ?? null;
            if ($debugOrder) {
                $debugRes .= "<br />{$taskLinks[$task->id]} [{$assets[$task->assetId]->code}] от {$begin}";
            }
            $debugRes .= static::feedToInterval($task, $begin, $interruptOffset, $Interval, $planned, $reservedByAssets, $debugOrder);
            $plannedByAssets[$task->assetId][$task->id] = $planned[$task->id];
        }

        // Automatic package links must not close a cycle through a technological dependency
        // or through an already persisted package on any resource.
        $packageAdjacency = array();
        foreach ($previousTasks as $taskId => $constraints) {
            foreach ($constraints as $previousTaskId => $constraint) {
                if (isset($allTasks[$taskId], $allTasks[$previousTaskId])) {
                    $packageAdjacency[$previousTaskId][$taskId] = (int)$taskId;
                }
            }
        }
        foreach ($assets as $assetId => $assetRec) {
            foreach ((array)($assetRec->packageLinks ?? array()) as $taskId => $previousTaskId) {
                if (isset($allTasks[$taskId], $allTasks[$previousTaskId])
                    && $allTasks[$taskId]->assetId == $assetId && $allTasks[$previousTaskId]->assetId == $assetId) {
                    $packageAdjacency[$previousTaskId][$taskId] = (int)$taskId;
                }
            }
        }
        $sameResourceJobLinks = static::getSameResourceJobPackageLinks($allTasks);
        foreach ($sameResourceJobLinks as $taskId => $previousTaskId) {
            $packageAdjacency[$previousTaskId][$taskId] = (int)$taskId;
        }

        // Build a preferred position for every resource. The position is not a hard edge:
        // a temporarily blocked operation must not stop the whole resource.
        $remaining = $manualPosition = array();
        foreach ($tasksWithoutActualStartByAssetId as $assetId => $assetTasks) {
            $withStart = $withoutStart = array();
            foreach ($assetTasks as $task) {
                if (!empty($task->timeStart)) {
                    $withStart[$task->id] = $task;
                } else {
                    $withoutStart[$task->id] = $task;
                }
            }

            arr::sortObjects($withStart, 'timeStart', 'ASC');
            arr::sortObjects($withoutStart, 'dueDate', 'ASC');
            $orderedTasks = $withStart + $withoutStart;
            $hasManualOrder = isset($assets[$assetId]->manualOrder) && is_array($assets[$assetId]->manualOrder);
            if ($hasManualOrder && empty($assets[$assetId]->_forceOptimization)) {
                $orderedTasks = static::reorderTasksByManualOrder($orderedTasks, $assets[$assetId]->manualOrder);
            }
            $orderedTasks = static::groupTasksByPlanningParams(
                $orderedTasks,
                $assets[$assetId],
                $packageAdjacency,
                $allTasks,
                $sameResourceJobLinks
            );

            $position = 0;
            foreach ($orderedTasks as $task) {
                $task->calcedCurrentDuration = max(1, (int)$task->calcedCurrentDuration);
                $remaining[$task->id] = $task;
                $manualPosition[$task->id] = $position;
                $position++;
            }
        }

        // Последователните операции от едно задание на една машина се третират
        // като пакет, когато втората може да продължи непосредствено след първата.
        $nextTaskByJob = $previousTaskByJob = $planableTasksByJob = array();
        foreach ($allTasks as $task) {
            if (isset($task->originId) && isset($task->saoOrder)) {
                $planableTasksByJob[$task->originId][$task->id] = $task;
            }
        }

        // Persisted user packages are directed chains on one resource.
        $manualPackageNext = $manualPackagePrevious = $manualPackageChains = array();
        foreach ($assets as $assetId => $assetRec) {
            foreach ((array)($assetRec->packageLinks ?? array()) as $taskId => $previousTaskId) {
                if (!isset($allTasks[$taskId], $allTasks[$previousTaskId])
                    || $allTasks[$taskId]->assetId != $assetId || $allTasks[$previousTaskId]->assetId != $assetId) {
                    continue;
                }

                $manualPackageNext[$previousTaskId] = (int)$taskId;
                $manualPackagePrevious[$taskId] = (int)$previousTaskId;
            }
        }
        foreach ($manualPackageNext as $taskId => $nextTaskId) {
            if (isset($manualPackagePrevious[$taskId])) continue;

            $chain = array((int)$taskId);
            $currentId = $taskId;
            while (isset($manualPackageNext[$currentId])) {
                $currentId = $manualPackageNext[$currentId];
                $chain[] = (int)$currentId;
            }
            if (count($chain) > 1) {
                $manualPackageChains[$taskId] = $chain;
            }
        }
        $manualPackageRuns = array();
        foreach ($manualPackageChains as $chain) {
            foreach ($chain as $position => $taskId) {
                if (isset($remaining[$taskId])) {
                    $manualPackageRuns[$taskId] = array_slice($chain, $position);
                    break;
                }
            }
        }
        foreach ($planableTasksByJob as $tasksInJob) {
            uasort($tasksInJob, function($a, $b) {
                if ($a->saoOrder == $b->saoOrder) {
                    return ($a->id < $b->id) ? -1 : 1;
                }

                return ($a->saoOrder < $b->saoOrder) ? -1 : 1;
            });

            $previousTaskId = null;
            foreach ($tasksInJob as $taskInJob) {
                if (isset($previousTaskId)) {
                    $nextTaskByJob[$previousTaskId] = $taskInJob->id;
                    $previousTaskByJob[$taskInJob->id] = $previousTaskId;
                }
                $previousTaskId = $taskInJob->id;
            }
        }

        // Kahn traversal: dependencies are visited once instead of rescanning every task.
        $inDegree = array_fill_keys(array_keys($remaining), 0);
        $successors = array();
        foreach ($remaining as $taskId => $task) {
            if (isset($previousTasks[$taskId])) {
                foreach ($previousTasks[$taskId] as $previousTaskId => $constraint) {
                    if (isset($remaining[$previousTaskId])) {
                        static::addPlanningEdge($previousTaskId, $taskId, $inDegree, $successors);
                    }
                }
            }
        }
        foreach ($manualPackagePrevious as $taskId => $previousTaskId) {
            if (isset($remaining[$taskId], $remaining[$previousTaskId])) {
                static::addPlanningEdge($previousTaskId, $taskId, $inDegree, $successors);
            }
        }

        // A package head waits for all independent prerequisites of its members.
        // A prerequisite which itself depends on the package is left inside the chain,
        // because delaying the head for it would create a cycle.
        foreach ($manualPackageRuns as $headTaskId => $chain) {
            if (!isset($remaining[$headTaskId])) continue;

            $chainIds = array_fill_keys($chain, true);
            foreach ($chain as $packageTaskId) {
                foreach ($previousTasks[$packageTaskId] ?? array() as $previousTaskId => $constraint) {
                    if (!isset($remaining[$previousTaskId]) || isset($chainIds[$previousTaskId])) continue;
                    if (static::hasPlanningPath($headTaskId, $previousTaskId, $successors)) continue;

                    static::addPlanningEdge($previousTaskId, $headTaskId, $inDegree, $successors);
                }
            }
        }

        $readyHeap = array();
        foreach ($inDegree as $taskId => $degree) {
            if ($degree == 0) {
                $plannedTime = static::getGraphReadyTime($remaining[$taskId], $previousTasks, $planned, $allTasks, $now);
                $previousTaskId = $previousTaskByJob[$taskId] ?? null;
                $manualPreviousTaskId = $manualPackagePrevious[$taskId] ?? null;
                $isManualContinuation = isset($planned[$manualPreviousTaskId]);
                $isResourceContinuation = $isManualContinuation || (isset($planned[$previousTaskId])
                    && static::canContinueOnResource($remaining[$taskId], $planned[$previousTaskId], $plannedTime));
                $continuationPriority = $isResourceContinuation ? 0 : 1;
                $commitmentPriority = (($assets[$remaining[$taskId]->assetId]->committedTaskId ?? null) == $taskId) ? 0 : 1;
                static::readyHeapPush($readyHeap, static::getReadyHeapItem($remaining[$taskId], $plannedTime, $manualPosition[$taskId], $continuationPriority, $commitmentPriority));
            }
        }

        while (count($readyHeap)) {
            $readyItem = static::readyHeapPop($readyHeap);
            $taskId = $readyItem['taskId'];
            if (!isset($remaining[$taskId])) {
                continue;
            }

            $packageRun = $manualPackageRuns[$taskId] ?? array($taskId);
            $task = $remaining[$taskId];
            $packageStart = $readyItem['plannedTime'];
            $manualPreviousTaskId = $manualPackagePrevious[$taskId] ?? null;
            if (isset($planned[$manualPreviousTaskId]) && $planned[$manualPreviousTaskId]->expectedTimeEnd < self::NOT_FOUND_DATE) {
                $packageStart = max($packageStart, $planned[$manualPreviousTaskId]->expectedTimeEnd);
            }
            if (count($packageRun) > 1) {
                $packageStart = static::getManualPackageStartTime($packageRun, $packageStart, $remaining, $previousTasks, $planned, $allTasks, $now);
                $packageStart = static::getManualPackageIntervalStart($packageRun, $packageStart, $remaining, $intervals[$task->assetId], $reservedByAssets);
            }

            foreach ($packageRun as $packagePosition => $packageTaskId) {
                if (!isset($remaining[$packageTaskId]) || $inDegree[$packageTaskId] != 0) break;

                $task = $remaining[$packageTaskId];
                if ($packagePosition == 0) {
                    $task->_plannedTime = $packageStart;
                } else {
                    $task->_plannedTime = static::getGraphReadyTime($task, $previousTasks, $planned, $allTasks, $now);
                    $manualPreviousTaskId = $manualPackagePrevious[$packageTaskId] ?? null;
                    if (isset($planned[$manualPreviousTaskId]) && $planned[$manualPreviousTaskId]->expectedTimeEnd < self::NOT_FOUND_DATE) {
                        $task->_plannedTime = max($task->_plannedTime, $planned[$manualPreviousTaskId]->expectedTimeEnd);
                    }
                }

                $Interval = $intervals[$task->assetId];
                $interruptOffset = $interruptionArr[$task->productId] ?? null;
                if ($debugOrder) {
                    $debugRes .= "<br />{$taskLinks[$packageTaskId]} [{$assets[$task->assetId]->code}] от {$task->_plannedTime}";
                }
                $debugRes .= static::feedToInterval($task, $task->_plannedTime, $interruptOffset, $Interval, $planned, $reservedByAssets, $debugOrder);
                $plannedByAssets[$task->assetId][$packageTaskId] = $planned[$packageTaskId];
                unset($remaining[$packageTaskId]);

                $nextPackageTaskId = $packageRun[$packagePosition + 1] ?? null;
                foreach ($successors[$packageTaskId] ?? array() as $successorId) {
                    $inDegree[$successorId]--;
                    if ($inDegree[$successorId] == 0 && isset($remaining[$successorId])) {
                        if ($successorId == $nextPackageTaskId) continue;

                        $plannedTime = static::getGraphReadyTime($remaining[$successorId], $previousTasks, $planned, $allTasks, $now);
                        $manualPreviousTaskId = $manualPackagePrevious[$successorId] ?? null;
                        $isManualContinuation = isset($planned[$manualPreviousTaskId]);
                        $isResourceContinuation = $isManualContinuation || (isset($nextTaskByJob[$packageTaskId])
                            && $nextTaskByJob[$packageTaskId] == $successorId
                            && static::canContinueOnResource($remaining[$successorId], $planned[$packageTaskId], $plannedTime));
                        $continuationPriority = $isResourceContinuation ? 0 : 1;
                        $commitmentPriority = (($assets[$remaining[$successorId]->assetId]->committedTaskId ?? null) == $successorId) ? 0 : 1;
                        static::readyHeapPush($readyHeap, static::getReadyHeapItem($remaining[$successorId], $plannedTime, $manualPosition[$successorId], $continuationPriority, $commitmentPriority));
                    }
                }
            }
        }

        // What remains is part of, or depends on, a technological dependency cycle.
        $notPlanned = $remaining;
        foreach ($remaining as $task) {
            $plannedByAssets[$task->assetId][$task->id] = (object)array(
                'id' => $task->id,
                'assetId' => $task->assetId,
                'calcedCurrentDuration' => $task->calcedCurrentDuration,
                'expectedTimeStart' => self::NOT_PLANNABLE,
                'expectedTimeEnd' => self::NOT_PLANNABLE,
            );
        }

        $debugRes .= "<hr />КРАЙНО НЕПЛАНИРАНИ: " . implode(', ', array_keys($notPlanned)) . "<br />";
        core_Debug::stopTimer('SCHEDULE_CALC_TIMES');
        core_Debug::log("END SCHEDULE_CALC_TIMES " . round(core_Debug::$timers['SCHEDULE_CALC_TIMES']->workingTime, 6));

        return $debugRes;
    }


    /**
     * Adds a unique edge to the planning graph.
     */
    private static function addPlanningEdge($fromId, $toId, &$inDegree, &$successors)
    {
        if (isset($successors[$fromId][$toId])) {
            return;
        }

        $successors[$fromId][$toId] = $toId;
        $inDegree[$toId]++;
    }


    /**
     * Applies a persisted manual order in linear time.
     */
    private static function reorderTasksByManualOrder($tasks, $manualOrder)
    {
        $ordered = array();
        foreach ((array)$manualOrder as $taskId) {
            if (isset($tasks[$taskId])) {
                $ordered[$taskId] = $tasks[$taskId];
            }
        }

        return $ordered + array_diff_key($tasks, $ordered);
    }


    /**
     * Restores package chains as contiguous blocks while retaining the position of their
     * earliest member in the supplied preferred order.
     */
    private static function reorderTasksByPackageLinks($tasks, $packageLinks)
    {
        if (!count($tasks) || !count((array)$packageLinks)) return $tasks;

        $next = $previous = array();
        foreach ((array)$packageLinks as $taskId => $previousTaskId) {
            $taskId = (int)$taskId;
            $previousTaskId = (int)$previousTaskId;
            if (!isset($tasks[$taskId], $tasks[$previousTaskId]) || $taskId == $previousTaskId) continue;
            $next[$previousTaskId] = $taskId;
            $previous[$taskId] = $previousTaskId;
        }

        $result = $used = array();
        foreach ($tasks as $taskId => $task) {
            if (isset($used[$taskId])) continue;

            $headId = (int)$taskId;
            $guard = array();
            while (isset($previous[$headId]) && !isset($guard[$headId])) {
                $guard[$headId] = true;
                $headId = $previous[$headId];
            }

            $currentId = $headId;
            $guard = array();
            while (isset($tasks[$currentId]) && !isset($guard[$currentId])) {
                $guard[$currentId] = true;
                $used[$currentId] = true;
                $result[$currentId] = $tasks[$currentId];
                if (!isset($next[$currentId])) break;
                $currentId = $next[$currentId];
            }
        }

        return $result + array_diff_key($tasks, $result);
    }


    /**
     * Calculates the earliest usable time after all already planned predecessors.
     */
    private static function getGraphReadyTime($task, $previousTasks, $planned, $allTasks, $now)
    {
        $readyTimes = array($now);
        if (!empty($task->timeStart)) {
            $readyTimes[] = $task->timeStart;
        }

        foreach ($previousTasks[$task->id] ?? array() as $previousTaskId => $constraint) {
            if (!isset($allTasks[$previousTaskId])) {
                continue;
            }
            if (!isset($planned[$previousTaskId])) {
                $readyTimes[] = self::NOT_FOUND_DATE;
                continue;
            }

            $readyTimes[] = static::getConstraintReadyTime($task, $planned[$previousTaskId], $constraint);
        }

        return max($readyTimes);
    }


    /**
     * Adds normalized planning parameter values and the inherited grouping settings to every task.
     * Task-specific parameter values override the values inherited from the job product.
     */
    private static function setPlanningParamSignatures($tasks, $assets)
    {
        $paramIds = $taskIds = $jobProductIds = $folderIds = array();
        $paramsByAsset = array();
        foreach ($assets as $assetId => $assetRec) {
            $paramsByAsset[$assetId] = keylist::toArray($assetRec->planningParams);
        }

        $defaultSimilarity = static::normalizePlanningParamSimilarity(planning_Setup::get('TASK_PARAM_GROUP_SIMILARITY'), 0.75);
        $defaultGroupDays = static::normalizePlanningParamGroupDays(planning_Setup::get('TASK_PARAM_GROUP_DAYS'), 7);
        foreach ($tasks as $task) {
            $taskIds[$task->id] = $task->id;
            if (!empty($task->jobProductId)) $jobProductIds[$task->jobProductId] = $task->jobProductId;
            if (!empty($task->folderId)) $folderIds[$task->folderId] = $task->folderId;
        }

        $centersByFolder = array();
        if (countR($folderIds)) {
            $centerQuery = planning_Centers::getQuery();
            $centerQuery->in('folderId', $folderIds);
            $centerQuery->show('folderId,planningParams,showTaskPlanningParams,planningParamSimilarity,planningParamGroupDays');
            while ($centerRec = $centerQuery->fetch()) {
                $centersByFolder[$centerRec->folderId] = $centerRec;
            }
        }

        // Определят се параметрите за всяка операция по същата йерархия като в списъка:
        // машина/вид, център на дейност и, когато е разрешено, конкретен етап.
        $paramsByTask = $stepParamsByProduct = array();
        foreach ($tasks as $task) {
            $taskParams = $paramsByAsset[$task->assetId] ?? array();
            $centerRec = $centersByFolder[$task->folderId] ?? null;
            if (is_object($centerRec)) {
                $taskParams += keylist::toArray($centerRec->planningParams);
                if (in_array($centerRec->showTaskPlanningParams, array('yes', 'yesAdd'))) {
                    if (!array_key_exists($task->productId, $stepParamsByProduct)) {
                        $stepParams = array();
                        if ($Driver = cat_Products::getDriver($task->productId)) {
                            $productionData = $Driver->getProductionData($task->productId);
                            $stepParams = is_array($productionData['planningParams'] ?? null)
                                ? $productionData['planningParams']
                                : keylist::toArray($productionData['planningParams'] ?? null);
                        }
                        $stepParamsByProduct[$task->productId] = $stepParams;
                    }

                    if ($centerRec->showTaskPlanningParams == 'yes') {
                        $taskParams = $stepParamsByProduct[$task->productId];
                    } else {
                        $taskParams += $stepParamsByProduct[$task->productId];
                    }
                }
            }

            $paramsByTask[$task->id] = $taskParams;
            $paramIds += $taskParams;

            $assetSimilarity = $assets[$task->assetId]->planningParamSimilarity ?? null;
            $centerSimilarity = is_object($centerRec) ? ($centerRec->planningParamSimilarity ?? null) : null;
            $task->_planningParamSimilarity = isset($assetSimilarity)
                ? static::normalizePlanningParamSimilarity($assetSimilarity, $defaultSimilarity)
                : (isset($centerSimilarity) ? static::normalizePlanningParamSimilarity($centerSimilarity, $defaultSimilarity) : $defaultSimilarity);

            $assetGroupDays = $assets[$task->assetId]->planningParamGroupDays ?? null;
            $centerGroupDays = is_object($centerRec) ? ($centerRec->planningParamGroupDays ?? null) : null;
            $task->_planningParamGroupDays = static::resolvePlanningParamGroupDays(
                $assetGroupDays,
                $assets[$task->assetId]->taskQuantization ?? null,
                $centerGroupDays,
                $defaultGroupDays
            );
        }
        if (!countR($paramIds)) return;

        $numericParamIds = array();
        $paramMetaQuery = cat_Params::getQuery();
        $paramMetaQuery->in('id', $paramIds);
        $paramMetaQuery->show('id,driverClass');
        while ($paramMetaRec = $paramMetaQuery->fetch()) {
            $driverClass = core_Classes::getName($paramMetaRec->driverClass);
            if (in_array($driverClass, array('cond_type_Int', 'cond_type_Double', 'cond_type_Percent'))) {
                $numericParamIds[$paramMetaRec->id] = true;
            }
        }

        $taskValues = array();
        if (countR($taskIds)) {
            $paramQuery = cat_products_Params::getQuery();
            $paramQuery->where('#classId = ' . planning_Tasks::getClassId());
            $paramQuery->in('productId', $taskIds);
            $paramQuery->in('paramId', $paramIds);
            $paramQuery->show('productId,paramId,paramValue');
            while ($paramRec = $paramQuery->fetch()) {
                $taskValues[$paramRec->productId][$paramRec->paramId] = $paramRec->paramValue;
            }
        }

        $jobValues = array();
        if (countR($jobProductIds)) {
            $paramQuery = cat_products_Params::getQuery();
            $paramQuery->where('#classId = ' . cat_Products::getClassId());
            $paramQuery->in('productId', $jobProductIds);
            $paramQuery->in('paramId', $paramIds);
            $paramQuery->show('productId,paramId,paramValue');
            while ($paramRec = $paramQuery->fetch()) {
                $jobValues[$paramRec->productId][$paramRec->paramId] = $paramRec->paramValue;
            }
        }

        foreach ($tasks as $task) {
            $signatureParts = $comparableParts = $numericParts = array();
            foreach ($paramsByTask[$task->id] ?? array() as $paramId) {
                if (array_key_exists($paramId, $taskValues[$task->id] ?? array())) {
                    $value = $taskValues[$task->id][$paramId];
                } elseif (array_key_exists($paramId, $jobValues[$task->jobProductId] ?? array())) {
                    $value = $jobValues[$task->jobProductId][$paramId];
                } else {
                    continue;
                }

                $signatureParts[$paramId] = static::normalizePlanningParamValue($value);
                $isNumeric = isset($numericParamIds[$paramId]);
                $comparableParts[$paramId] = static::planningParamValueToComparableString($signatureParts[$paramId], $isNumeric);
                if ($isNumeric) $numericParts[$paramId] = true;
            }
            if (countR($signatureParts)) {
                ksort($signatureParts);
                ksort($comparableParts);
                $task->_planningParamValues = $comparableParts;
                $task->_planningParamNumericValues = $numericParts;
                $task->_planningParamSignature = md5(serialize($comparableParts));
            }
        }
    }


    /**
     * Normalizes scalar and compound parameter values for exact grouping.
     */
    private static function normalizePlanningParamValue($value)
    {
        if (is_array($value)) {
            ksort($value);
            foreach ($value as $key => $nestedValue) {
                $value[$key] = static::normalizePlanningParamValue($nestedValue);
            }

            return $value;
        }
        if (is_object($value)) {
            return static::normalizePlanningParamValue((array)$value);
        }
        if (is_string($value)) {
            return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
        }

        return $value;
    }


    /**
     * Normalizes a configured percentage from package/center/resource level.
     */
    private static function normalizePlanningParamSimilarity($value, $fallback)
    {
        if (!is_numeric($value)) return (float)$fallback;

        $value = (float)$value;
        if ($value > 1) $value /= 100;

        return min(1, max(0, $value));
    }


    /**
     * Normalizes the inherited rolling grouping period in days.
     */
    private static function normalizePlanningParamGroupDays($value, $fallback)
    {
        if (!is_numeric($value)) $value = $fallback;

        return max(1, (int)$value);
    }


    /**
     * Resolves machine -> legacy machine -> center -> package grouping period.
     */
    private static function resolvePlanningParamGroupDays($assetValue, $legacyQuantization, $centerValue, $defaultValue)
    {
        if (isset($assetValue)) return static::normalizePlanningParamGroupDays($assetValue, $defaultValue);
        if ($legacyQuantization == 'day') return 1;
        if (in_array($legacyQuantization, array('month', 'monthly'))) return 30;
        if (isset($centerValue)) return static::normalizePlanningParamGroupDays($centerValue, $defaultValue);

        return static::normalizePlanningParamGroupDays($defaultValue, 7);
    }


    /**
     * Converts scalar and compound parameter values to a short comparable ASCII string.
     */
    private static function planningParamValueToComparableString($value, $isNumeric = false)
    {
        if ($isNumeric && !is_array($value) && !is_object($value)) {
            $numericValue = static::canonicalizePlanningNumericValue($value);
            if (isset($numericValue)) return $numericValue;
        }
        if (is_array($value)) {
            ksort($value);
            $parts = array();
            foreach ($value as $key => $nestedValue) {
                $parts[] = static::planningParamValueToComparableString($key) . '=' . static::planningParamValueToComparableString($nestedValue, $isNumeric);
            }

            return implode(' ', $parts);
        }
        if (is_object($value)) {
            return static::planningParamValueToComparableString((array)$value, $isNumeric);
        }

        $value = html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8');
        $value = str::utf2ascii(mb_strtolower($value));
        $value = strtolower(trim(preg_replace('/[^a-z0-9]+/', ' ', $value)));

        return substr($value, 0, 255);
    }


    /**
     * Converts equivalent decimal representations to one exact value.
     */
    private static function canonicalizePlanningNumericValue($value)
    {
        $value = trim((string)$value);
        if (!preg_match('/^([+-]?)(\d+)(?:[\.,](\d*))?$/', $value, $matches)) return null;

        $integer = ltrim($matches[2], '0');
        $integer = strlen($integer) ? $integer : '0';
        $fraction = isset($matches[3]) ? rtrim($matches[3], '0') : '';
        $sign = ($matches[1] == '-' && ($integer != '0' || strlen($fraction))) ? '-' : '';

        return $sign . $integer . (strlen($fraction) ? ".{$fraction}" : '');
    }


    /**
     * Turns automatic planning-parameter groups into the same persistent hard packages
     * which are edited through the "With previous" column.
     *
     * After the one-time conversion only tasks which are absent from the persisted order
     * are considered for automatic attachment. Thus a user can permanently detach an
     * existing task by clearing its checkbox.
     */
    private static function groupTasksByPlanningParams($tasks, $assetRec, &$packageAdjacency, $allTasks, $sameResourceJobLinks)
    {
        if (!count($tasks)) return $tasks;

        $hasPersistedOrder = isset($assetRec->manualOrder) && is_array($assetRec->manualOrder);
        $oldOrder = $hasPersistedOrder ? array_values($assetRec->manualOrder) : array();
        $oldLinks = (array)($assetRec->packageLinks ?? array());
        $oldVersion = (int)($assetRec->autoGroupVersion ?? 0);
        $forceOptimization = !empty($assetRec->_forceOptimization);
        $initialConversion = !empty($assetRec->_forceParamRegroup)
            || $oldVersion < planning_TaskManualOrderPerAssets::AUTO_GROUP_VERSION;
        $requiredLinks = array();
        foreach ((array)$sameResourceJobLinks as $taskId => $previousTaskId) {
            if (isset($allTasks[$taskId], $allTasks[$previousTaskId])
                && $allTasks[$taskId]->assetId == $assetRec->id && $allTasks[$previousTaskId]->assetId == $assetRec->id) {
                $requiredLinks[(int)$taskId] = (int)$previousTaskId;
            }
        }
        $requiredMembers = array_fill_keys(array_merge(array_keys($requiredLinks), array_values($requiredLinks)), true);
        // Started operations are not part of $tasks because their intervals are already reserved,
        // but they may remain the first member of a package.
        $allAssetTasks = array();
        foreach ($allTasks as $taskId => $task) {
            if ($task->assetId == $assetRec->id && !isset($tasks[$taskId])) {
                $allAssetTasks[$taskId] = $task;
            }
        }
        $allAssetTasks += $tasks;
        if ($hasPersistedOrder && !$forceOptimization) {
            $allAssetTasks = static::reorderTasksByManualOrder($allAssetTasks, $oldOrder);
        }
        $combinedLinks = static::mergeRequiredPackageLinks($requiredLinks, $oldLinks);
        $allAssetTasks = static::reorderTasksByPackageLinks($allAssetTasks, $combinedLinks);
        $fullCurrentOrder = array_combine(array_keys($allAssetTasks), array_keys($allAssetTasks));
        $fullActiveLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks($fullCurrentOrder, $combinedLinks);
        $tasks = static::reorderTasksByPackageLinks($tasks, $fullActiveLinks);
        $currentOrder = array_combine(array_keys($tasks), array_keys($tasks));
        $activeLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks($currentOrder, $fullActiveLinks);
        $protectedPackageMembers = array();
        foreach ($fullActiveLinks as $taskId => $previousTaskId) {
            $protectedPackageMembers[$taskId] = true;
            $protectedPackageMembers[$previousTaskId] = true;
        }

        if ($initialConversion) {
            // Existing user packages remain indivisible; unlinked operations are the
            // candidates of the initial automatic conversion.
            $sourceBlocks = static::buildPlanningParamPackageBlocks($tasks, $activeLinks, $requiredMembers);
            $blocks = array();
            foreach ($sourceBlocks as $block) {
                $blockTaskId = (int)key($block['tasks']);
                if (count($block['tasks']) > 1 || isset($protectedPackageMembers[$blockTaskId])
                    || isset($requiredMembers[$blockTaskId])) {
                    $blocks[] = $block;
                    continue;
                }

                $task = reset($block['tasks']);
                static::appendTaskToPlanningParamPackage($task, $blocks, $packageAdjacency, $requiredMembers);
            }
        } else {
            $knownIds = array_fill_keys($oldOrder, true);
            $knownTasks = $newTasks = array();
            foreach ($tasks as $taskId => $task) {
                if (isset($knownIds[$taskId])) {
                    $knownTasks[$taskId] = $task;
                } else {
                    $newTasks[$taskId] = $task;
                }
            }

            $knownOrder = count($knownTasks) ? array_combine(array_keys($knownTasks), array_keys($knownTasks)) : array();
            $knownLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks($knownOrder, $activeLinks);
            $blocks = static::buildPlanningParamPackageBlocks($knownTasks, $knownLinks, $requiredMembers);
            foreach ($newTasks as $task) {
                static::appendTaskToPlanningParamPackage($task, $blocks, $packageAdjacency, $requiredMembers);
            }
        }

        $result = $newPendingLinks = array();
        foreach ($blocks as $block) {
            $previousTaskId = null;
            foreach ($block['tasks'] as $taskId => $task) {
                $result[$taskId] = $task;
                if (isset($previousTaskId)) $newPendingLinks[$taskId] = (int)$previousTaskId;
                $previousTaskId = $taskId;
            }
        }

        $newPendingOrder = array_values(array_keys($result));
        $pendingIds = array_fill_keys($newPendingOrder, true);
        $newOrder = array();
        $pendingInserted = false;
        foreach ($allAssetTasks as $taskId => $task) {
            if (isset($pendingIds[$taskId])) {
                if (!$pendingInserted) {
                    foreach ($newPendingOrder as $pendingTaskId) $newOrder[] = $pendingTaskId;
                    $pendingInserted = true;
                }
                continue;
            }

            $newOrder[] = (int)$taskId;
        }
        if (!$pendingInserted) {
            foreach ($newPendingOrder as $pendingTaskId) $newOrder[] = $pendingTaskId;
        }

        $newLinks = array();
        foreach ($fullActiveLinks as $taskId => $previousTaskId) {
            if (!isset($pendingIds[$taskId]) || !isset($pendingIds[$previousTaskId])) {
                $newLinks[$taskId] = (int)$previousTaskId;
            }
        }
        $newLinks += $newPendingLinks;

        // Optimization may freely move whole packages, but it must never split an existing
        // user package or a mandatory same-job/same-resource chain. Restore those hard links
        // as a final postcondition after parameter grouping has built its preferred order.
        $hardLinks = static::mergeRequiredPackageLinks($requiredLinks, $fullActiveLinks);
        $newLinks = static::mergeRequiredPackageLinks($hardLinks, $newLinks);
        $newOrderTasks = array();
        foreach ($newOrder as $taskId) {
            if (isset($allAssetTasks[$taskId])) $newOrderTasks[$taskId] = $allAssetTasks[$taskId];
        }
        $newOrderTasks = static::reorderTasksByPackageLinks($newOrderTasks, $newLinks);
        $newOrder = array_values(array_keys($newOrderTasks));
        $result = static::reorderTasksByManualOrder($result, $newOrder);

        $newFullOrder = count($newOrder) ? array_combine($newOrder, $newOrder) : array();
        $newLinks = planning_TaskManualOrderPerAssets::sanitizePackageLinks($newFullOrder, $newLinks);
        $addedAutomaticLinks = count(array_diff_assoc($newLinks, $fullActiveLinks));
        // Do not create a manual-order record for a resource where no automatic package exists.
        // Such a record would unnecessarily freeze every otherwise freely ordered operation.
        if (!$hasPersistedOrder && !$addedAutomaticLinks && !$forceOptimization) return $tasks;

        $assetRec->manualOrder = $newFullOrder;
        $assetRec->packageLinks = $newLinks;
        $assetRec->autoGroupVersion = planning_TaskManualOrderPerAssets::AUTO_GROUP_VERSION;
        $assetRec->_saveAutomaticPackages = ($oldOrder != $newOrder)
            || ($oldLinks != $newLinks)
            || ($oldVersion != planning_TaskManualOrderPerAssets::AUTO_GROUP_VERSION);

        return $result;
    }


    /**
     * Builds consecutive package blocks from an already ordered task list.
     */
    private static function buildPlanningParamPackageBlocks($tasks, $packageLinks, $nonAttachableTaskIds = array())
    {
        $blocks = array();
        foreach ($tasks as $taskId => $task) {
            $lastIndex = count($blocks) - 1;
            $previousTaskId = $packageLinks[$taskId] ?? null;
            if ($lastIndex >= 0 && isset($previousTaskId) && $blocks[$lastIndex]['tailId'] == $previousTaskId) {
                $blocks[$lastIndex]['tasks'][$taskId] = $task;
                $blocks[$lastIndex]['tailId'] = (int)$taskId;
                if (!empty($task->timeStart) || isset($nonAttachableTaskIds[$taskId])) {
                    $blocks[$lastIndex]['attachable'] = false;
                }
                continue;
            }

            $blocks[] = array(
                'tasks' => array($taskId => $task),
                'headId' => (int)$taskId,
                'tailId' => (int)$taskId,
                'attachable' => empty($task->timeStart) && !isset($nonAttachableTaskIds[$taskId]),
            );
        }

        return $blocks;
    }


    /**
     * Appends one new operation to the first compatible package, or creates a new block.
     */
    private static function appendTaskToPlanningParamPackage($task, &$blocks, &$packageAdjacency, $nonAttachableTaskIds = array())
    {
        $taskId = (int)$task->id;
        $matchingIndexes = $otherIndexes = array();
        if (empty($task->timeStart) && !isset($nonAttachableTaskIds[$taskId])) {
            foreach ($blocks as $index => $block) {
                if (!$block['attachable']) continue;
                $headTask = reset($block['tasks']);
                if (!static::planningParamDatesMatch($task, $headTask)) continue;

                if (isset($task->_planningParamSignature, $headTask->_planningParamSignature)
                    && $task->_planningParamSignature === $headTask->_planningParamSignature) {
                    $matchingIndexes[] = $index;
                } else {
                    $otherIndexes[] = $index;
                }
            }
        }

        foreach (array_merge($matchingIndexes, $otherIndexes) as $index) {
            $matchesAll = true;
            foreach ($blocks[$index]['tasks'] as $memberTask) {
                if (!static::planningParamsMatch($task, $memberTask)) {
                    $matchesAll = false;
                    break;
                }
            }
            if (!$matchesAll) continue;

            $previousTaskId = (int)$blocks[$index]['tailId'];
            if (static::hasPlanningPath($taskId, $previousTaskId, $packageAdjacency)) continue;

            $blocks[$index]['tasks'][$taskId] = $task;
            $blocks[$index]['tailId'] = $taskId;
            $packageAdjacency[$previousTaskId][$taskId] = $taskId;

            return;
        }

        $blocks[] = array(
            'tasks' => array($taskId => $task),
            'headId' => $taskId,
            'tailId' => $taskId,
            'attachable' => empty($task->timeStart) && !isset($nonAttachableTaskIds[$taskId]),
        );
    }


    /**
     * Returns mandatory package links between consecutive technological steps on one resource.
     */
    public static function getSameResourceJobPackageLinks($allTasks, $assetId = null)
    {
        $tasksByJob = $result = array();
        foreach ($allTasks as $task) {
            if (isset($task->originId, $task->saoOrder)) {
                $tasksByJob[$task->originId][$task->id] = $task;
            }
        }

        foreach ($tasksByJob as $tasksInJob) {
            uasort($tasksInJob, function($a, $b) {
                if ($a->saoOrder == $b->saoOrder) {
                    return ($a->id < $b->id) ? -1 : 1;
                }

                return ($a->saoOrder < $b->saoOrder) ? -1 : 1;
            });

            $previousTask = null;
            foreach ($tasksInJob as $task) {
                if (isset($previousTask) && $previousTask->assetId == $task->assetId
                    && (!isset($assetId) || $task->assetId == $assetId)) {
                    $result[(int)$task->id] = (int)$previousTask->id;
                }
                $previousTask = $task;
            }
        }

        return $result;
    }


    /**
     * Places mandatory same-job/same-resource chains next to each other in a preferred order.
     */
    public static function applySameResourceJobPackageOrder($tasks, $assetId = null)
    {
        return static::reorderTasksByPackageLinks($tasks, static::getSameResourceJobPackageLinks($tasks, $assetId));
    }


    /**
     * Places the supplied hard package chains next to each other in a preferred order.
     */
    public static function applyPackageLinksOrder($tasks, $packageLinks)
    {
        return static::reorderTasksByPackageLinks($tasks, $packageLinks);
    }


    /**
     * Adds optional links around mandatory technological chains without branching or cycles.
     */
    public static function mergeRequiredPackageLinks($requiredLinks, $optionalLinks)
    {
        $result = array();
        $nextByPrevious = array();
        foreach ((array)$requiredLinks as $taskId => $previousTaskId) {
            $taskId = (int)$taskId;
            $previousTaskId = (int)$previousTaskId;
            if (!$taskId || !$previousTaskId || $taskId == $previousTaskId) continue;
            $result[$taskId] = $previousTaskId;
            $nextByPrevious[$previousTaskId] = $taskId;
        }

        foreach ((array)$optionalLinks as $taskId => $previousTaskId) {
            $taskId = (int)$taskId;
            $previousTaskId = (int)$previousTaskId;
            if (!$taskId || !$previousTaskId || $taskId == $previousTaskId
                || isset($result[$taskId]) || isset($nextByPrevious[$previousTaskId])) {
                continue;
            }

            $ancestorId = $previousTaskId;
            $visited = array();
            $hasCycle = false;
            while (isset($result[$ancestorId]) && !isset($visited[$ancestorId])) {
                $visited[$ancestorId] = true;
                $ancestorId = $result[$ancestorId];
                if ($ancestorId == $taskId) {
                    $hasCycle = true;
                    break;
                }
            }
            if ($hasCycle) continue;

            $result[$taskId] = $previousTaskId;
            $nextByPrevious[$previousTaskId] = $taskId;
        }

        return $result;
    }


    /**
     * Returns a stable calendar-day number for the operation due date.
     */
    private static function getPlanningParamDueDay($task)
    {
        if (empty($task->dueDate) || strtotime($task->dueDate) === false) return null;

        return (int)dt::mysql2UnixDays($task->dueDate);
    }


    /**
     * Checks the stricter rolling period of the two representative operations.
     */
    private static function planningParamDatesMatch($firstTask, $secondTask)
    {
        $firstDueDay = $firstTask->_planningParamDueDay ?? static::getPlanningParamDueDay($firstTask);
        $secondDueDay = $secondTask->_planningParamDueDay ?? static::getPlanningParamDueDay($secondTask);
        if (!isset($firstDueDay, $secondDueDay)) return false;

        $firstDays = static::normalizePlanningParamGroupDays($firstTask->_planningParamGroupDays ?? null, 7);
        $secondDays = static::normalizePlanningParamGroupDays($secondTask->_planningParamGroupDays ?? null, 7);

        return abs($firstDueDay - $secondDueDay) <= min($firstDays, $secondDays);
    }


    /**
     * Checks every available planning parameter against the stricter inherited threshold.
     */
    private static function planningParamsMatch($firstTask, $secondTask)
    {
        $firstValues = (array)($firstTask->_planningParamValues ?? array());
        $secondValues = (array)($secondTask->_planningParamValues ?? array());
        $firstNumericValues = (array)($firstTask->_planningParamNumericValues ?? array());
        $secondNumericValues = (array)($secondTask->_planningParamNumericValues ?? array());
        $paramIds = array_unique(array_merge(array_keys($firstValues), array_keys($secondValues)));
        if (!count($paramIds)) return false;

        $threshold = max(
            (float)($firstTask->_planningParamSimilarity ?? 1),
            (float)($secondTask->_planningParamSimilarity ?? 1)
        );
        foreach ($paramIds as $paramId) {
            $firstExists = array_key_exists($paramId, $firstValues);
            $secondExists = array_key_exists($paramId, $secondValues);
            if (!$firstExists && !$secondExists) continue;
            if (!$firstExists || !$secondExists) return false;
            if ($firstValues[$paramId] === $secondValues[$paramId]) continue;
            if (isset($firstNumericValues[$paramId]) || isset($secondNumericValues[$paramId])) return false;
            if (!static::planningParamValuesCanReachSimilarity($firstValues[$paramId], $secondValues[$paramId], $threshold)) return false;

            $percent = 0;
            similar_text($firstValues[$paramId], $secondValues[$paramId], $percent);
            if (($percent / 100) < $threshold) return false;
        }

        return true;
    }


    /**
     * Cheap upper bound which skips values that cannot possibly reach the threshold.
     */
    private static function planningParamValuesCanReachSimilarity($firstValue, $secondValue, $threshold)
    {
        if ($threshold <= 0) return true;

        $firstLength = strlen($firstValue);
        $secondLength = strlen($secondValue);
        $totalLength = $firstLength + $secondLength;
        if (!$totalLength) return true;

        $maxCommonChars = min($firstLength, $secondLength);
        if ((2 * $maxCommonChars / $totalLength) < $threshold) return false;

        $firstChars = count_chars($firstValue, 1);
        $secondChars = count_chars($secondValue, 1);
        $maxCommonChars = 0;
        foreach ($firstChars as $char => $count) {
            if (isset($secondChars[$char])) $maxCommonChars += min($count, $secondChars[$char]);
        }

        return (2 * $maxCommonChars / $totalLength) >= $threshold;
    }


    /**
     * Delays a manual package head so all already independent prerequisites
     * of the package members can be met without splitting the package.
     */
    private static function getManualPackageStartTime($chain, $plannedTime, $remaining, $previousTasks, $planned, $allTasks, $now)
    {
        $chainIds = array_fill_keys($chain, true);
        $packageStart = strtotime($plannedTime);
        if ($packageStart === false) return $plannedTime;

        $durationBefore = 0;
        foreach ($chain as $taskId) {
            $task = $remaining[$taskId] ?? ($allTasks[$taskId] ?? null);
            if (!is_object($task)) continue;

            $readyTimes = array($now);
            if (!empty($task->timeStart)) $readyTimes[] = $task->timeStart;
            foreach ($previousTasks[$taskId] ?? array() as $previousTaskId => $constraint) {
                if (isset($chainIds[$previousTaskId]) || !isset($planned[$previousTaskId])) continue;
                $readyTimes[] = static::getConstraintReadyTime($task, $planned[$previousTaskId], $constraint);
            }

            $memberReady = strtotime(max($readyTimes));
            if ($memberReady !== false) {
                $packageStart = max($packageStart, $memberReady - $durationBefore);
            }
            $durationBefore += max(1, (int)$task->calcedCurrentDuration);
        }

        return date('Y-m-d H:i:s', $packageStart);
    }


    /**
     * Finds one free resource range which can hold the whole manual package.
     * Calendar breaks are allowed, but another operation cannot be inserted between members.
     */
    private static function getManualPackageIntervalStart($chain, $plannedTime, $remaining, $Interval, $reservedByAssets)
    {
        $firstTask = $remaining[reset($chain)] ?? null;
        if (!is_object($firstTask)) return $plannedTime;

        $packageDuration = 0;
        foreach ($chain as $taskId) {
            if (isset($remaining[$taskId])) {
                $packageDuration += max(1, (int)$remaining[$taskId]->calcedCurrentDuration);
            }
        }
        if (!$packageDuration) return $plannedTime;

        $packageTask = (object)array(
            'assetId' => $firstTask->assetId,
            'calcedCurrentDuration' => $packageDuration,
        );
        $testInterval = clone $Interval;
        $timeArr = static::consumeTaskWithoutInterruption($packageTask, strtotime($plannedTime), null, $testInterval, $reservedByAssets);

        return is_array($timeArr) ? date('Y-m-d H:i:s', $timeArr[0]) : self::NOT_FOUND_DATE;
    }


    /**
     * Checks whether a task can continue immediately after another one
     * without leaving the resource idle.
     */
    private static function canContinueOnResource($task, $previousTask, $plannedTime)
    {
        if ($task->assetId != $previousTask->assetId) return false;
        if ($previousTask->expectedTimeEnd >= self::NOT_FOUND_DATE || $plannedTime >= self::NOT_FOUND_DATE) return false;

        $previousTaskEnd = strtotime($previousTask->expectedTimeEnd);
        $taskReadyTime = strtotime($plannedTime);

        return $previousTaskEnd !== false && $taskReadyTime !== false && $taskReadyTime <= $previousTaskEnd;
    }


    /**
     * Applies the technological overlap rule between two operations.
     */
    private static function getConstraintReadyTime($task, $previousTask, $constraint)
    {
        if ($previousTask->expectedTimeStart >= self::NOT_FOUND_DATE || $previousTask->expectedTimeEnd >= self::NOT_FOUND_DATE) {
            return self::NOT_FOUND_DATE;
        }

        $waitingTime = (int)($constraint->waitingTime ?? 0);
        if (($constraint->intersect ?? 'yes') == 'no') {
            return dt::addSecs($waitingTime, $previousTask->expectedTimeEnd);
        }

        $duration = max(1, (int)$task->calcedCurrentDuration);
        $fromPreviousEnd = dt::addSecs(-1 * ($duration - $waitingTime), $previousTask->expectedTimeEnd);
        $fromPreviousStart = dt::addSecs($waitingTime, $previousTask->expectedTimeStart);

        return max($fromPreviousEnd, $fromPreviousStart);
    }


    /**
     * Creates one deterministic min-heap item.
     */
    private static function getReadyHeapItem($task, $plannedTime, $manualPosition, $continuationPriority = 1, $commitmentPriority = 1)
    {
        $plannedTimestamp = strtotime($plannedTime);
        $dueTimestamp = !empty($task->dueDate) ? strtotime($task->dueDate) : false;

        return array(
            'taskId' => $task->id,
            'plannedTime' => $plannedTime,
            'plannedTimestamp' => ($plannedTimestamp === false) ? PHP_INT_MAX : $plannedTimestamp,
            'manualPosition' => $manualPosition,
            'commitmentPriority' => $commitmentPriority,
            'continuationPriority' => $continuationPriority,
            'dueTimestamp' => ($dueTimestamp === false) ? PHP_INT_MAX : $dueTimestamp,
        );
    }


    /**
     * Pushes an item to the deterministic min-heap.
     */
    private static function readyHeapPush(&$heap, $item)
    {
        $heap[] = $item;
        $index = count($heap) - 1;
        while ($index > 0) {
            $parent = (int)(($index - 1) / 2);
            if (static::compareReadyHeapItems($heap[$parent], $heap[$index]) <= 0) {
                break;
            }

            $tmp = $heap[$parent];
            $heap[$parent] = $heap[$index];
            $heap[$index] = $tmp;
            $index = $parent;
        }
    }


    /**
     * Removes the earliest item from the deterministic min-heap.
     */
    private static function readyHeapPop(&$heap)
    {
        $result = $heap[0];
        $last = array_pop($heap);
        if (count($heap)) {
            $heap[0] = $last;
            $index = 0;
            $count = count($heap);
            while (true) {
                $left = $index * 2 + 1;
                if ($left >= $count) {
                    break;
                }

                $right = $left + 1;
                $smallest = $left;
                if ($right < $count && static::compareReadyHeapItems($heap[$right], $heap[$left]) < 0) {
                    $smallest = $right;
                }
                if (static::compareReadyHeapItems($heap[$index], $heap[$smallest]) <= 0) {
                    break;
                }

                $tmp = $heap[$index];
                $heap[$index] = $heap[$smallest];
                $heap[$smallest] = $tmp;
                $index = $smallest;
            }
        }

        return $result;
    }


    /**
     * Compares two heap items by committed dispatch position, direct resource continuation, release time,
     * manual position, due date and id.
     */
    private static function compareReadyHeapItems($a, $b)
    {
        foreach (array('commitmentPriority', 'continuationPriority', 'plannedTimestamp', 'manualPosition', 'dueTimestamp', 'taskId') as $field) {
            if ($a[$field] == $b[$field]) {
                continue;
            }

            return ($a[$field] < $b[$field]) ? -1 : 1;
        }

        return 0;
    }


    /**
     * Legacy quantized planner, retained temporarily for rollback compatibility.
     */
    private static function smartPlanning(&$plannedByAssets, &$notPlanned, $intervals, $assets, $tasks, $now, $previousTasks)
    {
        $planned = $reservedByAssets = array();

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
        $debugRes = "<hr />УМНО ПЛАНИРАНЕ<hr / >Без графици:" . countR($withoutIntervals);
        $debugRes .= "<hr />ВСИЧКИ: " . countR($tasks);
        $debugRes .= "<hr />1. Разполагане на тези с ФАКТИЧЕСКО начало <b>" . countR($tasksWithActualStart) . "</b> <hr />";

        core_Debug::startTimer('START_CYCLE');

        foreach ($tasksWithActualStart as $taskRec1) {
            $begin = max($taskRec1->actualStart, $now);

            // Захранват се графиците със задачите с фактическо начало
            if ($Interval = $intervals[$taskRec1->assetId]) {
                $interruptOffset = array_key_exists($taskRec1->productId, $interruptionArr) ? $interruptionArr[$taskRec1->productId] : null;
                $debugRes .= "{$taskLinks[$taskRec1->id]} храни <b>[{$assets[$taskRec1->assetId]->code}]($taskRec1->assetId)</b> с начало {$begin} / прод. {$taskRec1->calcedCurrentDuration} ";
                $debugRes .= static::feedToInterval($taskRec1, $begin, $interruptOffset, $Interval, $planned, $reservedByAssets);
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

                arr::sortObjects($withStart, 'timeStart', 'ASC');
                arr::sortObjects($withoutStart, 'dueDate', 'ASC');
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
                        $debugRes .= self::feedToInterval($task, $task->_plannedTime, $interruptOffset, $Interval, $planned, $reservedByAssets);

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

                    $debugRes .= self::feedToInterval($t1, $t1->_plannedTime, $interruptOffset, $Interval, $planned, $reservedByAssets);
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

        return $debugRes;
    }


    /**
     * Храни графика с и извлича планираното начало/край
     *
     * @param stdClass $task           - записа на задачата
     * @param string $begin            - изчисленото начало на задачата
     * @param int $interrupedOffset    - отместването при прекъсване
     * @param core_Intervals $Interval - инстанцията на интервала
     * @param array $planned
     * @param array $reservedByAssets  - календарните диапазони, вече заети от операции
     * @param bool $withDebug
     * @return string
     */
    private static function feedToInterval($task, $begin, $interrupedOffset, &$Interval, &$planned, &$reservedByAssets, $withDebug = true)
    {
        $planned[$task->id] = (object)array('id' => $task->id, 'assetId' => $task->assetId, 'calcedCurrentDuration' => $task->calcedCurrentDuration, 'expectedTimeStart' => self::NOT_FOUND_DATE, 'expectedTimeEnd' => self::NOT_FOUND_DATE);

        if($begin != self::NOT_FOUND_DATE) {
            $begin = strtotime($begin);
            $timeArr = static::consumeTaskWithoutInterruption($task, $begin, $interrupedOffset, $Interval, $reservedByAssets);

            if(is_array($timeArr)){
                $reservedByAssets[$task->assetId][] = $timeArr;
                usort($reservedByAssets[$task->assetId], function($a, $b) {
                    return ($a[0] < $b[0]) ? -1 : (($a[0] > $b[0]) ? 1 : 0);
                });

                $planned[$task->id]->expectedTimeStart = date('Y-m-d H:i:00', $timeArr[0]);
                $planned[$task->id]->expectedTimeEnd = date('Y-m-d H:i:00', $timeArr[1]);
                return $withDebug ? "--------Изчислено за S: <b>{$planned[$task->id]->expectedTimeStart}</b> / Е: <b>{$planned[$task->id]->expectedTimeEnd}</b> <br />" : '';
            }

            return $withDebug ? "--------Не е изчислено начало/край<br />" : '';
        } else {
            return $withDebug ? "--------Е ИЗВЪН ГРАФИКА<br />" : '';
        }
    }


    /**
     * Поставя операцията в първия диапазон, в който цялата ѝ продължителност
     * се събира без да бъде прекъсната от друга операция на същата машина.
     */
    private static function consumeTaskWithoutInterruption($task, $begin, $interrupedOffset, &$Interval, $reservedByAssets)
    {
        $duration = max(1, (int)$task->calcedCurrentDuration);
        $candidateStart = $begin;
        $reservations = $reservedByAssets[$task->assetId] ?? array();

        foreach ($reservations as $reservation) {
            if ($reservation[1] < $candidateStart) {
                continue;
            }

            if ($reservation[0] <= $candidateStart) {
                $candidateStart = $reservation[1] + 1;
                continue;
            }

            $candidateEnd = $reservation[0] - 1;
            if (static::getIntervalCapacity($Interval, $candidateStart, $candidateEnd) >= $duration) {
                return $Interval->consume($duration, $candidateStart, $candidateEnd, $interrupedOffset);
            }

            $candidateStart = $reservation[1] + 1;
        }

        if (static::getIntervalCapacity($Interval, $candidateStart, PHP_INT_MAX) < $duration) {
            return false;
        }

        return $Interval->consume($duration, $candidateStart, null, $interrupedOffset);
    }


    /**
     * Връща наличното работно време в календарен диапазон.
     */
    private static function getIntervalCapacity($Interval, $begin, $end)
    {
        if ($end < $begin) return 0;

        $capacity = 0;
        foreach ($Interval->getFrame($begin, $end) as $frame) {
            $capacity += $frame[1] - $frame[0] + 1;
        }

        return $capacity;
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
