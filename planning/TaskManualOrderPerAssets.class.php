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
    public $listFields = 'assetId,data,createdOn,createdBy';


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
     * @param $assetId
     * @param $recs
     * @return array
     */
    public static function getOrderedRecs($assetId, $recs)
    {
        // Най-отпред ще са тези с фактическо начало (неспрените)
        $manualOrder = planning_TaskManualOrderPerAssets::fetchField("#assetId = {$assetId}", 'data');
        $newRecs = array_filter($recs, function ($a) {return isset($a->actualStart) && $a->state != 'stopped';});
        arr::sortObjects($newRecs, 'actualStart', 'ASC');

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
}