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
        $this->FLD('prevStepId', 'key2(mvc=cat_Products,select=name,selectSourceArr=planning_Steps::getSelectableSteps,allowEmpty,forceAjax,forceOpen)', 'mandatory,caption=Предходен етап,tdClass=leftCol,class=w100');
        $this->FLD('delay', 'time', 'caption=Изчакване');
        $this->FLD('intersect', 'enum(yes=Да,no=Не)', 'caption=Застъпване,notNull,default=yes');

        $this->setDbIndex('prevStepId');
        $this->setDbUnique('stepId,prevStepId');
    }


    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){
            $rec = &$form->rec;

            if($rec->prevStepId == $rec->stepId){
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
       if(empty($rec->delay)){
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

        if($data->toolbar->haveButton('btnAdd')){
            $data->toolbar->removeBtn('btnAdd');
        }
        $tplBlock = parent::renderDetail_($data);
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Предходни етапи'), 'title');
        if($this->haveRightFor('add', (object)array('stepId' => $data->masterId))){
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
        if($action == 'add' && isset($rec->stepId)){
            $productRec = cat_Products::fetch($rec->stepId, 'state,innerClass');
            if($productRec->innerClass != planning_interface_StepProductDriver::getClassId()){
                $res = 'no_one';
            } elseif($productRec->state != 'active'){
                $res = 'no_one';
            }
        }
    }


    /**
     * Проверка дали за изпълнени условията за зависимост
     *
     * @return array
     */
    public static function checkTaskConditions()
    {
        // Всички производствени етапи
        $sQuery = cat_Products::getQuery();
        $sQuery->where("#state != 'closed' AND #innerClass = " . planning_interface_StepProductDriver::getClassId());
        $sQuery->show('id');
        $stepArr = $jobArr = array();
        while ($sRec = $sQuery->fetch()) {
            $stepArr[$sRec->id] = array();
        }

        // Всички условия за зависимости
        $cQuery = planning_StepConditions::getQuery();
        $cQuery->show('stepId,prevStepId,delay,intersect');
        while ($cRec = $cQuery->fetch()) {
            $stepArr[$cRec->stepId][$cRec->id] = $cRec;
        }

        $minDuration = planning_Setup::get('MIN_TASK_DURATION');
        $endOfHorizon = dt::addSecs(planning_Setup::get('ASSET_HORIZON'), dt::now());

        // ОТ текущите ПО, се взимат тези за ПЕ
        $tQuery = planning_Tasks::getQuery();
        $tQuery->in("productId", array_keys($stepArr));
        $tQuery->in("state", array('wakeup', 'stopped', 'active', 'pending'));
        $tQuery->where("#timeClosed IS NULL");
        $tQuery->show('expectedTimeStart,expectedTimeEnd,originId,productId,prevErrId,nextErrId');

        while ($tRec = $tQuery->fetch()) {

            // Ако нямат начало/край то се приема, че това е края на търсения период
            $tRec->expectedTimeStart = !empty($tRec->expectedTimeStart) ? $tRec->expectedTimeStart : $endOfHorizon;
            $tRec->expectedTimeEnd = !empty($tRec->expectedTimeEnd) ? $tRec->expectedTimeEnd : dt::addSecs($minDuration, $tRec->expectedTimeStart);
            $jobArr[$tRec->originId][$tRec->id] = $tRec;
        }

        // Цикли се по всички Задания и след това по всяка ПО от едно задание. Вземаме ПЕ за текущата операция
        $tasksEarliestTime = array();
        foreach ($jobArr as $jobTasks) {
            foreach ($jobTasks as $taskId => $taskRec) {
                if(!array_key_exists($taskId, $tasksEarliestTime)){
                    $tasksEarliestTime[$taskId] = array('prevErr' => array(), 'nextErr' => array(), 'exPrevErrId' => $taskRec->prevErrId, 'exNextErrId' => $taskRec->nextErrId, 'taskRec' => $taskRec);
                }

                // Колко е оставащата продължителност
                $duration = dt::secsBetween($taskRec->expectedTimeEnd, $taskRec->expectedTimeStart);

                // Ако имам записи в масива със зависимостите за съответния ПЕ цикли се по тях
                if (array_key_exists($taskRec->productId, $stepArr)) {
                    foreach ($stepArr[$taskRec->productId] as $stepRec) {

                        // За всеки запис се търси в текущото Задание ПО която има същия ПЕ като prevStepId
                        $tasks4StepInSameJob = array_filter($jobTasks, function($a) use ($stepRec) { return $a->productId == $stepRec->prevStepId;});

                        // Ако се намерят такива (предходни операция)
                        if(countR($tasks4StepInSameJob)){
                            foreach ($tasks4StepInSameJob as $prevStepTask){

                                if($stepRec->intersect == 'no'){
                                    $earlierTime = dt::addSecs($stepRec->delay, $prevStepTask->expectedTimeEnd);
                                } else {
                                    $prevEndCalc = dt::addSecs(-1 * ($duration - $stepRec->delay), $prevStepTask->expectedTimeEnd);
                                    $prevStartCalc = dt::addSecs($stepRec->delay, $prevStepTask->expectedTimeStart);
                                    $earlierTime = max($prevEndCalc, $prevStartCalc);
                                }

                                // Ако $earlierTime е по-голямо от началото на текущата операция
                                if($earlierTime > $taskRec->expectedTimeStart){
                                    $tasksEarliestTime[$taskRec->id]['prevErr'][$prevStepTask->id] = $earlierTime;
                                    if(!array_key_exists($prevStepTask->id, $tasksEarliestTime)){
                                        $tasksEarliestTime[$prevStepTask->id] = array('prevErr' => array(), 'nextErr' => array(), 'exPrevErrId' => $prevStepTask->prevErrId, 'exNextErrId' => $prevStepTask->nextErrId, 'taskRec' => $prevStepTask);
                                    }
                                    $tasksEarliestTime[$prevStepTask->id]['nextErr'][$taskRec->id] = $earlierTime;
                                }
                            }
                        }
                    }
                }
            }
        }

        $toUpdate = array();
        foreach ($tasksEarliestTime as $taskId => $taskData){

            // Ако има колизия с предходна/последваща ПО взима се тази с минималната дата
            $prevNewErrId = countR($taskData['prevErr']) ? array_search(min($taskData['prevErr']), $taskData['prevErr']) : null;
            $nextNewErrId = countR($taskData['nextErr']) ? array_search(min($taskData['nextErr']), $taskData['nextErr']) : null;

            // Ако има промяна между съществуващите записи, ще се обновява
            if($taskData['exPrevErrId'] != $prevNewErrId || $taskData['exNextErrId'] != $nextNewErrId){
                $toUpdate[$taskId] = (object)array('id' => $taskId, 'prevErrId' => $prevNewErrId, 'nextErrId' => $nextNewErrId);
            }
        }

        // Ако има записи за обновяване - обновяват се
        if(countR($toUpdate)){
            $Tasks = cls::get('planning_Tasks');
            $Tasks->saveArray($toUpdate, 'id,prevErrId,nextErrId');
        }

        return $tasksEarliestTime;
    }


    /**
     * Върща масив с прогреса на предходните операции на подадените такива
     *
     * @param array|stdClass $taskArr
     * @param bool $verbal
     * @return array $res
     */
    public static function getDependantTasksProgress($taskArr, $verbal = false)
    {
        $arr = is_array($taskArr) ? $taskArr : array($taskArr);

        $dependantArr = $conditions = $tasks = array();
        if(!countR($arr)) return $dependantArr;

        $ids = arr::extractValuesFromArray($arr, 'productId');
        $originIds = arr::extractValuesFromArray($arr, 'originId');

        $query = static::getQuery();
        $query->in('stepId', $ids);
        $query->show('prevStepId,stepId');
        while($rec = $query->fetch()){
            $conditions[$rec->stepId][$rec->prevStepId] = $rec->prevStepId;
        }

        $taskQuery = planning_Tasks::getQuery();
        $taskQuery->in('originId', $originIds);
        $taskQuery->where("#state != 'rejected'");
        while($tRec = $taskQuery->fetch()){
            $timeStart = !empty($rec->timeStart) ? $rec->timeStart : "9999-99-{$tRec->id}";
            $tasks[$tRec->originId][$tRec->productId][$tRec->id] = array('id' => $tRec->id, 'progress' => $tRec->progress, 'timeStart' => $timeStart);
        }

        // За всяка от посочените ОП се прави масив с предходните им ПО от същото задание
        foreach ($arr as $taskRec){
            $dependantArr[$taskRec->id] = array();
            if(array_key_exists($taskRec->productId, $conditions)){
                foreach ($conditions[$taskRec->productId] as $stepId){
                    if(array_key_exists($stepId, $tasks[$taskRec->originId])){
                        foreach ($tasks[$taskRec->originId][$stepId] as $condTaskArr){
                            $dependantArr[$taskRec->id][$condTaskArr['id']] = $condTaskArr;
                        }
                    }
                }

                arr::sortObjects($dependantArr[$taskRec->id], 'timeStart', 'ASC');
            }
        }

        $res = array();
        foreach ($dependantArr as $taskId => $depArr){
            $count = countR($depArr);
            if($count){
                $eachWith = 90 / $count;
                foreach ($depArr as $depTaskArr){
                    if($verbal){
                        $depTaskArr = static::getDependantTaskBlock($eachWith, 10, $depTaskArr['progress'], $depTaskArr['id']);
                    }
                    $res[$taskId][] = $depTaskArr;
                }
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