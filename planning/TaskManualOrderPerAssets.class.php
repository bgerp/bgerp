<?php


/**
 * Модел за Ръчната подредба на ПО
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_TaskManualOrderPerAssets extends core_Master
{
    /**
     * Version of the automatic planning-parameter package conversion.
     */
    const AUTO_GROUP_VERSION = 1;


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Ръчни подредби на ПО по оборудване';


    /**
     * Заглавие на мениджъра
     */
    public $singleTitle = 'Ръчна подредба на ПО по оборудване';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper, plg_GroupByField, plg_Created';


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
    public $listFields = 'assetId,data,packageLinks,committedTaskId,autoGroupVersion,createdOn,createdBy';


    /**
     * По-кое поле да се групират листовите данни
     */
    public $groupByField = 'assetId';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни,input=none');
        $this->FLD('packageLinks', 'blob(serialize, compress)', 'caption=Пакетни връзки,input=none');
        $this->FLD('committedTaskId', 'key(mvc=planning_Tasks,select=id,allowEmpty)', 'caption=Ангажирана следваща операция,input=none');
        $this->FLD('autoGroupVersion', 'int', 'caption=Версия на автоматичното групиране,input=none');
        $this->FLD('order', 'int', 'caption=Подредба');

        $this->setDbUnique('assetId');
    }


    /**
     * Ако отговорника на папката е системата
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->assetId = planning_AssetResources::getTitleById($rec->assetId);
        if(planning_Tasks::haveRightFor('list')){
            $url = array('planning_Tasks', 'list', 'assetId' => $rec->assetId, 'isFinalSelect' => 'all', 'state' => 'manualOrder', 'selectPeriod' => 'gr0', 'reorder' => true, 'ret_url' => true);
            $row->assetId = ht::createLink($row->assetId, $url);
        }

        if(is_array($rec->data)){
            $tableHtml = "<table>";
            $count = 1;
            foreach ($rec->data as $taskId){
                $taskState = planning_Tasks::fetchField($taskId, 'state');
                $taskLink = planning_Tasks::getLink($taskId, 0);
                $taskHandle = "<span class= 'state-{$taskState} document-handler'>{$taskLink->getContent()}</span>";
                $tableHtml .= "<tr><td>{$count}.</td><td>{$taskHandle}</td></tr>";
                $count++;
            }
            $tableHtml .= "</table>";
            $row->data = $tableHtml;
        }

        if (is_array($rec->packageLinks)) {
            $linksHtml = array();
            foreach ($rec->packageLinks as $taskId => $previousTaskId) {
                $previousTaskLink = planning_Tasks::getLink($previousTaskId, 0);
                $taskLink = planning_Tasks::getLink($taskId, 0);
                $linksHtml[] = $previousTaskLink->getContent() . ' → ' . $taskLink->getContent();
            }
            $row->packageLinks = implode('<br>', $linksHtml);
        }
        if (!empty($rec->committedTaskId)) {
            $row->committedTaskId = planning_Tasks::getLink($rec->committedTaskId, 0);
        }
    }


    /**
     * Подготовка на филтър формата
     *
     * @param bgerp_Bookmark $mvc
     * @param object         $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $assetOptions = planning_AssetResources::getUsedAssetsInTasks();
        $data->listFilter->setOptions('assetId', $assetOptions);
        $data->listFilter->showFields = 'assetId';
        $data->listFilter->input();

        $rec = $data->listFilter->rec;
        if(isset($rec->assetId)){
            $data->query->where("#assetId = {$rec->assetId}");
        }
    }


    /**
     * Подредба на операциите спрямо тяхната ръчна подредба
     *
     * @param int $assetId - ид на оборудване
     * @param array $recs  - записи
     * @param bool $placeWithActualStartFirst - дали тези с факт. начало да са най-отпред
     * @return array
     */
    public static function getOrderedRecs($assetId, $recs, $placeWithActualStartFirst = true)
    {
        $newRecs = $recs;
        $manualOrder = planning_TaskManualOrderPerAssets::fetchField("#assetId = {$assetId}", 'data');

        // Най-отпред ще са тези с фактическо начало (неспрените)
        if($placeWithActualStartFirst){
            $newRecs = array_filter($recs, function ($a) {return isset($a->actualStart) && $a->state != 'stopped';});
            arr::sortObjects($newRecs, 'actualStart', 'ASC');
        }

        // След това са останалите, които присъстват в потребителската подредба
        $alreadyOrdered = array();
        $withoutActualStart = array_diff_key($recs, $newRecs);
        if(is_array($manualOrder)){
            foreach ($manualOrder as $taskId){
                if(isset($withoutActualStart[$taskId])){
                    $alreadyOrdered[$taskId] = $withoutActualStart[$taskId];
                }
            }
        }

        // Операциите ще са подредени накрая така: първо с фактическо начало, после ръчно подредените, после останалите
        $notOrdered = array_diff_key($withoutActualStart, $alreadyOrdered);

        return $newRecs + $alreadyOrdered + $notOrdered;
    }


    /**
     * Форсиране на ръчна подредба
     *
     * @param int $assetId
     * @param array $arr
     * @return int
     */
    public static function force($assetId, $arr, $packageLinks = null)
    {
        $arr = array_values((array)$arr);
        $manualRec = planning_TaskManualOrderPerAssets::fetch("#assetId = {$assetId}");
        $manualRec = is_object($manualRec) ? $manualRec : (object)array('assetId' => $assetId);
        $manualRec->data = countR($arr) ? array_combine($arr, $arr) : array();
        $packageLinks = isset($packageLinks) ? $packageLinks : ($manualRec->packageLinks ?? array());
        $manualRec->packageLinks = static::sanitizePackageLinks($manualRec->data, $packageLinks);
        // A user save is authoritative. Automatic grouping may subsequently add only new tasks.
        $manualRec->autoGroupVersion = static::AUTO_GROUP_VERSION;
        $manualRec->createdOn = dt::now();
        $manualRec->createdBy = core_Users::getCurrent();

        return self::save($manualRec);
    }


    /**
     * Persists packages created by the automatic planner without attributing them to a user.
     *
     * @param int $assetId
     * @param array $arr
     * @param array $packageLinks
     * @param int $version
     * @return int|null
     */
    public static function forceAutomatic($assetId, $arr, $packageLinks, $version)
    {
        $arr = array_values((array)$arr);
        $data = countR($arr) ? array_combine($arr, $arr) : array();
        $packageLinks = static::sanitizePackageLinks($data, $packageLinks);
        $manualRec = static::fetch("#assetId = {$assetId}");

        if (is_object($manualRec)) {
            if (array_values((array)$manualRec->data) === array_values($data)
                && (array)($manualRec->packageLinks ?? array()) == $packageLinks
                && (int)($manualRec->autoGroupVersion ?? 0) == (int)$version) {
                return null;
            }

            $manualRec->data = $data;
            $manualRec->packageLinks = $packageLinks;
            $manualRec->autoGroupVersion = (int)$version;

            return static::save($manualRec, 'data,packageLinks,autoGroupVersion');
        }

        $manualRec = (object)array(
            'assetId' => $assetId,
            'data' => $data,
            'packageLinks' => $packageLinks,
            'autoGroupVersion' => (int)$version,
        );

        return static::save($manualRec);
    }


    /**
     * Returns the persisted package links for a resource.
     * Every element is taskId => previousTaskId.
     */
    public static function getPackageLinks($assetId, $manualOrder = null)
    {
        $rec = static::fetch("#assetId = {$assetId}", 'data,packageLinks');
        if (!is_object($rec)) {
            return array();
        }

        $manualOrder = isset($manualOrder) ? $manualOrder : $rec->data;

        return static::sanitizePackageLinks($manualOrder, $rec->packageLinks ?? array());
    }


    /**
     * Returns the next operation which is already announced to the resource operators.
     */
    public static function getCommittedTaskId($assetId)
    {
        if (empty($assetId)) return null;

        return static::fetchField("#assetId = {$assetId}", 'committedTaskId');
    }


    /**
     * Persists the next operation which must not be displaced by an automatic recalculation.
     */
    public static function setCommittedTaskId($assetId, $taskId = null)
    {
        if (empty($assetId)) return null;

        $manualRec = static::fetch("#assetId = {$assetId}");
        $isNew = !is_object($manualRec);
        if ($isNew) {
            if (empty($taskId)) return null;
            $manualRec = (object)array('assetId' => $assetId, 'data' => array(), 'packageLinks' => array());
        }

        $taskId = !empty($taskId) ? (int)$taskId : null;
        if (($manualRec->committedTaskId ?? null) == $taskId) return null;

        $manualRec->committedTaskId = $taskId;

        return $isNew
            ? static::save($manualRec)
            : static::save($manualRec, 'committedTaskId');
    }


    /**
     * Chooses the first not-started operation after an active one in the accepted resource order.
     */
    public static function refreshCommittedTask($assetId, $orderedTaskIds = null, $taskRecs = null)
    {
        if (empty($assetId)) return null;

        if (!is_array($orderedTaskIds)) {
            $orderedTaskIds = array_values((array)static::fetchField("#assetId = {$assetId}", 'data'));
        }
        if (!count($orderedTaskIds)) return static::setCommittedTaskId($assetId, null);

        if (!is_array($taskRecs)) {
            $query = planning_Tasks::getQuery();
            $query->in('id', $orderedTaskIds);
            $query->show('id,assetId,state,actualStart');
            $taskRecs = $query->fetchAll();
        }

        $hasStarted = false;
        foreach ($orderedTaskIds as $taskId) {
            $task = $taskRecs[$taskId] ?? null;
            if (!is_object($task) || $task->assetId != $assetId) continue;
            if (!empty($task->actualStart) && $task->state != 'stopped') {
                $hasStarted = true;
                continue;
            }
            if ($hasStarted && in_array($task->state, array('active', 'pending', 'wakeup', 'stopped'))) {
                return static::setCommittedTaskId($assetId, $taskId);
            }
        }

        return static::setCommittedTaskId($assetId, null);
    }


    /**
     * Keeps only links between adjacent tasks from the same submitted order.
     */
    public static function sanitizePackageLinks($manualOrder, $packageLinks)
    {
        $orderedIds = array_values((array)$manualOrder);
        $positions = array_flip($orderedIds);
        $result = array();
        foreach ((array)$packageLinks as $taskId => $previousTaskId) {
            $taskId = (int)$taskId;
            $previousTaskId = (int)$previousTaskId;
            if (!$taskId || !$previousTaskId || !isset($positions[$taskId]) || !isset($positions[$previousTaskId])) {
                continue;
            }
            if ($positions[$taskId] != $positions[$previousTaskId] + 1) {
                continue;
            }

            $result[$taskId] = $previousTaskId;
        }

        return $result;
    }


    /**
     * Removes operations which no longer belong to the resource.
     *
     * @param int $assetId
     * @param array|int $taskIds
     * @return int|null
     */
    public static function removeTasks($assetId, $taskIds)
    {
        if (empty($assetId)) {
            return null;
        }

        $manualRec = static::fetch("#assetId = {$assetId}");
        if (!is_object($manualRec) || !is_array($manualRec->data)) {
            return null;
        }

        $remove = array();
        foreach ((array)$taskIds as $taskId) {
            $remove[(int)$taskId] = true;
        }
        $removeCommitted = isset($remove[(int)($manualRec->committedTaskId ?? 0)]);

        $newData = array();
        foreach ($manualRec->data as $key => $taskId) {
            if (!isset($remove[(int)$key]) && !isset($remove[(int)$taskId])) {
                $newData[$key] = $taskId;
            }
        }
        $packageLinks = (array)($manualRec->packageLinks ?? array());
        foreach ($remove as $removeTaskId => $dummy) {
            $previousTaskId = $packageLinks[$removeTaskId] ?? null;
            $nextTaskId = null;
            foreach ($packageLinks as $taskId => $linkedPreviousTaskId) {
                if ((int)$linkedPreviousTaskId == $removeTaskId) {
                    $nextTaskId = (int)$taskId;
                    break;
                }
            }

            unset($packageLinks[$removeTaskId]);
            if (isset($nextTaskId)) {
                if (isset($previousTaskId) && !isset($remove[(int)$previousTaskId]) && !isset($remove[$nextTaskId])) {
                    $packageLinks[$nextTaskId] = (int)$previousTaskId;
                } else {
                    unset($packageLinks[$nextTaskId]);
                }
            }
        }
        $packageLinks = static::sanitizePackageLinks($newData, $packageLinks);
        if (count($newData) == count($manualRec->data) && $packageLinks == (array)($manualRec->packageLinks ?? array()) && !$removeCommitted) {
            return null;
        }

        $manualRec->data = $newData;
        $manualRec->packageLinks = $packageLinks;
        if ($removeCommitted) $manualRec->committedTaskId = null;

        return static::save($manualRec, 'data,packageLinks,committedTaskId');
    }
}
