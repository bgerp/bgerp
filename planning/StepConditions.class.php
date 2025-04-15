<?php


/**
 * Клас 'planning_StepConditions'
 *
 * Зависимости между производствените етапи
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_StepConditions extends core_Detail
{
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Предходен етап';


    /**
     * Заглавие
     */
    public $title = 'Зависимости между производствени етапи';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'stepId,prevStepId,delay,intersect,createdOn,createdBy,modifiedOn,modifiedBy';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Modified, plg_Created, planning_Wrapper';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planningMaster';


    /**
     * Кой може да го изтрие?
     */
    public $canList = 'ceo,planning';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'stepId';


    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Етапи->Зависимости';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('stepId', 'key2(mvc=cat_Products,select=name,selectSourceArr=planning_Steps::getSelectableSteps,allowEmpty,forceAjax,forceOpen)', 'input=hidden,silent,mandatory,caption=Производствен етап');
        $this->FLD('prevStepId', 'key2(mvc=cat_Products,select=name,selectSourceArr=planning_Steps::getSelectableSteps,allowEmpty,forceAjax,forceOpen)', 'mandatory,caption=Предходен етап,tdClass=leftCol wrapText,class=w100');
        $this->FLD('delay', 'time', 'caption=Изчакване');
        $this->FLD('intersect', 'enum(yes=Да,no=Не)', 'caption=Застъпване,notNull,default=yes');

        $this->setDbIndex('stepId');
        $this->setDbIndex('prevStepId');
        $this->setDbUnique('stepId,prevStepId');
    }


    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            if ($rec->prevStepId == $rec->stepId) {
                $form->setError('prevStepId', 'Трябва да изберете различен етап от текущия');
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->stepId = cat_Products::getHyperlink($rec->stepId, true);
        $row->prevStepId = cat_Products::getHyperlink($rec->prevStepId, true);
        $row->ROW_ATTR['class'] = "state-" . cat_Products::fetchField($rec->prevStepId, 'state');
        if (empty($rec->delay)) {
            $row->delay = "<span class='quiet'>N/A</span>";
        }
    }


    /**
     * Рендиране на детайл
     */
    public function renderDetail_($data)
    {
        unset($data->listFields['stepId']);
        unset($data->listFields['modifiedOn']);
        unset($data->listFields['modifiedBy']);

        if ($data->toolbar->haveButton('btnAdd')) {
            $data->toolbar->removeBtn('btnAdd');
        }
        $tplBlock = parent::renderDetail_($data);
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Предходни етапи'), 'title');
        if ($this->haveRightFor('add', (object)array('stepId' => $data->masterId))) {
            $newBtn = ht::createLink('', array($this, 'add', 'stepId' => $data->masterId), false, 'ef_icon=img/16/add.png');

            $tpl->append($newBtn, 'title');
        }
        $tpl->replace($tplBlock, 'content');

        return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec->stepId)) {
            $productRec = cat_Products::fetch($rec->stepId, 'state,innerClass');
            if ($productRec->innerClass != planning_interface_StepProductDriver::getClassId()) {
                $res = 'no_one';
            } elseif ($productRec->state != 'active') {
                $res = 'no_one';
            }
        }
    }


    /**
     * Помощна ф-я за извличане на групирани предходни етапи
     *
     * @param array $stepIds
     * @return array $res
     */
    public static function getConditionalArr($stepIds)
    {
        $res = array();
        $query = static::getQuery();
        $query->in('stepId', $stepIds);
        $query->show('prevStepId,stepId');
        while($rec = $query->fetch()){
            $res[$rec->stepId][$rec->prevStepId] = $rec->prevStepId;
        }

        return $res;
    }


    /**
     * Връщане на предишните и следващите ПО на подадените
     *
     * @param array $taskArr
     * @return array $res
     *              ['previous'] - масив с последните N предходни
     *              ['next']     - масив със следващите N
     */
    public static function getPrevAndNextTasks($taskArr)
    {
        $arr = is_array($taskArr) ? $taskArr : array($taskArr);

        $dependantArr = $folders = $tasks = array();
        if(!countR($arr)) return $dependantArr;

        $originIds = arr::extractValuesFromArray($arr, 'originId');

        // Извличане на всички ПО по подадените задания на подадените операции
        $taskQuery = planning_Tasks::getQuery();
        $taskQuery->in('originId', $originIds);
        $taskQuery->where("#state != 'rejected'");
        $taskQuery->show('id,progress,saoOrder,expectedTimeEnd,expectedTimeStart,state,originId,folderId,productId');
        while($tRec = $taskQuery->fetch()){
            $folders[$tRec->folderId] = $tRec->folderId;
            $saoOrder = !empty($tRec->saoOrder) ? $tRec->saoOrder : 0;
            $tasks[$tRec->originId][$tRec->id] = (object)array('id' => $tRec->id, 'state' => $tRec->state, 'productId' => $tRec->productId, 'progress' => $tRec->progress, 'saoOrder' => $saoOrder, 'expectedTimeEnd' => $tRec->expectedTimeEnd, 'expectedTimeStart' => $tRec->expectedTimeStart);
        }

        // Сортират се по подредбата им във низходящ ред
        foreach ($tasks as &$tasksByOrigin){
            arr::sortObjects($tasksByOrigin, 'saoOrder', 'ASC');
        }

        // Кеш на максималния брой предходни операции, които да се показват във всеки център на дейност
        $centerMaxPreviousArr = array();
        if(countR($folders)){
            $defaultPreviousTasks = planning_Setup::get('SHOW_PREVIOUS_TASK_BLOCKS');
            $cQuery = planning_Centers::getQuery();
            $cQuery->in('folderId', $folders);
            $cQuery->show('maxPrevious,folderId');
            $cQuery->XPR('maxPrevious', 'int', "COALESCE(#showMaxPreviousTasksInATask, {$defaultPreviousTasks})");
            while($cRec = $cQuery->fetch()){
                $centerMaxPreviousArr[$cRec->folderId] = $cRec->maxPrevious;
            }
        }

        // За всяка от подадените операции
        $res = array();
        foreach ($arr as $taskRec){
            $lessThen = $taskRec->saoOrder;
            $arr1 = array('previous' => array(), 'next' => array());

            // Намират се всички ПО с подредба преди нейната
            if(is_array($tasks[$taskRec->originId])){
                array_walk($tasks[$taskRec->originId], function($a) use ($lessThen, &$arr1) {
                    if($a->saoOrder < $lessThen){
                        $arr1['previous'][$a->id] = $a;
                    } elseif($a->saoOrder > $lessThen) {
                        $arr1['next'][$a->id] = $a;
                    };
                });
            }

            // От тях се оставят до изисквания брой от центъра на дейност, после се сортират от ляво на дясно
            arr::sortObjects($arr1['previous'], 'saoOrder', 'ASC');
            $startCut = countR($arr1['previous']) - $centerMaxPreviousArr[$taskRec->folderId];
            $prevArr = array_splice($arr1['previous'], $startCut, $centerMaxPreviousArr[$taskRec->folderId]);

            arr::sortObjects($arr1['next'], 'saoOrder', 'ASC');
            $nextArr = array_splice($arr1['next'], 0, $centerMaxPreviousArr[$taskRec->folderId]);

            $res[$taskRec->id] = array('previous' => $prevArr, 'next' => $nextArr);
        }

        return $res;
    }


    /**
     * Рендиране на блока с предходните/следващите операции
     *
     * @param array $taskArr
     * @param string $type
     * @param int|null $limit
     * @param bool $normalLink
     * @return array $res
     */
    public static function renderTaskBlock($taskArr, $type, $limit = null, $normalLink = false)
    {
        $res = array();
        $count = countR($taskArr);
        if (!$count) return $res;

        if(in_array($type, array('smallBar', 'bigBar'))){
            $count = 0;
            $width = ($type == 'smallBar') ? 90 : 150;
            $eachWith = $width / countR($taskArr);
            foreach ($taskArr as $taskRec) {
                if($limit && $count == $limit) break;
                $res[] = static::getDependantTaskBlock($eachWith, 10, $taskRec->progress, $taskRec->id);
                $count++;
            }
        } elseif($type == 'reorderBlocks'){
            $count = 0;
            foreach ($taskArr as $taskRec) {
                if($limit && $count == $limit) break;

                $prevProgressVerbal = core_Type::getByName('percent(decimals=0)')->toVerbal($taskRec->progress);
                if($taskRec->progress >= 1){
                    $prevProgressVerbal = "<span class='readyPercent'>{$prevProgressVerbal}</span>";
                }
                $prevId = "<span class='state-{$taskRec->state} document-handler'>{$prevProgressVerbal}</span>";
                $titleHint = "#" . planning_Tasks::getrecTitle($taskRec);
                if($normalLink){
                    $prevElement = ht::createLink($prevId, planning_Tasks::getSingleUrlArray($taskRec->id), false, array('target' => "_blank", 'title' => $titleHint));
                } else {
                    $singlePrevUrl = toUrl(planning_Tasks::getSingleUrlArray($taskRec->id));
                    $prevElement = ht::createElement("span", array('class' => 'doubleclicklink', 'data-doubleclick-url' => $singlePrevUrl, 'title' => $titleHint), $prevId, true);
                }

                $res[] = $prevElement->getContent();
                $count++;
            }
        }

        return $res;
    }


    /**
     * Помощна функция рендираща прогреса на подадена ПО като прогрес бар
     *
     * @param int $width   - широчина
     * @param int$height   - височина
     * @param int $percent - процент
     * @param int $taskId  - ид на операция
     * @return string $div - хтмл на прогрес бара
     */
    private static function getDependantTaskBlock($width, $height, $percent, $taskId)
    {
        $percent = $percent * 100;
        $percent = ($percent > 100) ? 100 : $percent;
        $style = "border:0.1px solid #eee;display:inline-block;width:{$width}px;height:{$height}px;background:linear-gradient(90deg, green 0%, green {$percent}%, red {$percent}%, red 100%)";
        $title = planning_Tasks::getTitleById($taskId) . " [" .planning_Tasks::getVerbal($taskId, 'state') . "]";
        $div = "<div style='{$style}' title='{$title}'></div>";
        if(planning_Tasks::haveRightFor('single', $taskId)){
            $div = ht::createLink($div, planning_Tasks::getSingleUrlArray($taskId));
        }
        $div = "<div style='display:inline-block;padding-left:0.1px;'>{$div}</div>";

        return $div;
    }
}