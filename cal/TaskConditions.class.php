<?php


/**
 * Клас 'cal_TaskConditions'
 *
 * @title Задаване на условия към задачите
 *
 * @category  bgerp
 * @package   ca;
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cal_TaskConditions extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'baseId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,cal_Wrapper, plg_RowTools';
    
    
    /**
     * Заглавие
     */
    public $title = 'Условия';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Условие';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'createdOn,createdBy,message,progress,workingTime,condition';
    
    
    public $rowToolsField = 'tool';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    public $canAdd = 'powerUser';
    
    
    public $canEdit = 'powerUser';
    
    
    public $canDelete = 'powerUser';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // id на базовата задачата
        $this->FLD('baseId', 'key(mvc=cal_Tasks,select=title)', 'caption=Базова задача,input=hidden,silent,column=none');
        
        
        // Колко време е отнело изпълнението?
        $this->FLD('distTime', 'time(suggestions=1 час|2 часа|3 часа|1 ден|2 дена|3 дена|1 седм.|2 седм.|3 седм.|1 месец)', 'caption=Период, placeholder=Веднага,input=none,after=activationCond');
        
        
        // Условие за активиране
        $this->FLD('activationCond', 'enum(onProgress=При прогрес, afterTime=След началото, beforeTime=Преди началото,
        														   afterTimeEnd=След края, beforeTimeEnd=Преди края)', 'caption=Обстоятелство,silent, autoFilter');
        
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'caption=Прогрес,input=none,notNull,after=activationCond');
        
        // id на зависимата задачата
        $this->FLD('dependId', 'key(mvc=cal_Tasks,select=title)', 'caption=На задача, mandatory');
        
        
        $this->setDbIndex('baseId');
    }
    
    
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        expect($data->form->rec->baseId);
        
        $masterRec = cal_Tasks::fetch($data->form->rec->baseId);
        
        $data->form->setField('activationCond', array('removeAndRefreshForm' => 'progress|distTime'));
        
        if (!$data->form->rec->activationCond) {
            $data->form->setDefault('activationCond', 'onProgress');
            $data->form->setField('progress', 'input');
        }
        
        if ($data->form->rec->activationCond == 'onProgress') {
            $data->form->setField('progress', 'input');
        }
        
        if ($data->form->rec->activationCond == 'afterTime' || $data->form->rec->activationCond == 'beforeTime' ||
            $data->form->rec->activationCond == 'afterTimeEnd' || $data->form->rec->activationCond == 'beforeTimeEnd') {
            $data->form->setField('distTime', 'input');
        }
        
        $progressArr[''] = '';
        
        for ($i = 0; $i <= 100; $i += 10) {
            if ($data->form->progress > ($i / 100)) {
                continue;
            }
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        $data->form->setSuggestions('progress', $progressArr);
        
        // ще извадим списък с всички задачи на които може да бъде подчинена
        // текъщата задача
        // те трябва да са в същата папка
        $query = cal_Tasks::getQuery();
        
        doc_Threads::restrictAccess($query);
        
        // Да не може да се слага в звена, които са в неговия състав
        if ($data->form->rec->baseId) {
            $query->notIn('id', self::getInheritors($data->form->rec->baseId, 'dependId'));
        }
        
        $query->where($notAllowedCond);
        
        $query->where("#state != 'rejected'");
        
        $query->orderBy('#modifiedOn', 'DESC');
        
        $query->where(array("#folderId = '[#1#]'", $masterRec->folderId));
        
        $taskArr = array('' => '');
        while ($recTask = $query->fetch()) {
            $taskArr[$recTask->id] = $recTask->id. '. ' .$recTask->title;
        }
        
        if (countR($taskArr) >= 2) {
            $data->form->setOptions('dependId', $taskArr);
        } else {
            
            return redirect(array('cal_Tasks', 'single', $masterRec->id), false, '|Липсват задачи, от които да зависи задачата', 'warning');
        }
    }
    
    
    /**
     * Помощна ф-я, която връща заглавие за формата при добавяне на детайл към клас
     * Изнесена е статично за да може да се използва и от класове, които не наследяват core_Detail,
     * Но реално се добавят като детайли към друг клас
     *
     * @param mixed    $master      - ид на класа на мастъра
     * @param int      $masterId    - ид на мастъра
     * @param string   $singleTitle - еденично заглавие
     * @param int|NULL $recId       - ид на записа, ако има
     * @param string   $preposition - предлог
     * @param int|NULL $len         - максимална дължина на стринга
     *
     * @return string $title      - заглавието на формата на 'Детайла'
     */
    public static function getEditTitle($master, $masterId, $singleTitle, $recId, $preposition = null, $len = null)
    {
        $title = '|*' . cls::get($master)->getFormTitleLink($masterId) . ' |може да започне|*:';
        
        return $title;
    }
    
    
    public function renderDetail($data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        
        $tpl = getTplFromFile('cal/tpl/SingleLayoutTaskConditions.shtml');
        
        foreach ($data->recs as $rec) {
            $row = $this->recToVerbal($rec);
            
            $cTpl = $tpl->getBlock('COMMENT_LI');
            $cTpl->placeObject($row);
            $cTpl->removeBlocks();
            $cTpl->append2master();
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на вербалните стойности
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($rec->progress == '0') {
            $row->progress = '';
        }
        
        
        if (!isset($rec->baceId)) {
            $rec = cal_TaskConditions::fetch($rec->id);
        }
        $taskRec = cal_Tasks::fetch($rec->baseId);
        
        $row->condition = '<td>' . $row->tool . '</td><td>'  . $row->condition;
        
        if ($rec->activationCond == 'onProgress') {
            $row->condition .= $row->progress . tr(' от изпълнението на ') . ht::createLink($row->dependId, array('cal_Tasks', 'single', $rec->dependId, 'ret_url' => true, ''), null, 'ef_icon=img/16/task-normal.png');
        }
        
        if ($rec->activationCond == 'afterTime') {
            $row->condition .= $row->distTime . tr(' след началото на ') . ht::createLink($row->dependId, array('cal_Tasks', 'single', $rec->dependId, 'ret_url' => true, ''), null, 'ef_icon=img/16/task-normal.png');
        }
        
        if ($rec->activationCond == 'beforeTime') {
            $row->condition .= $row->distTime . tr(' преди началото на ') . ht::createLink($row->dependId, array('cal_Tasks', 'single', $rec->dependId, 'ret_url' => true, ''), null, 'ef_icon=img/16/task-normal.png');
        }
        
        if ($rec->activationCond == 'afterTimeEnd') {
            $row->condition .= $row->distTime . tr(' след края на ') . ht::createLink($row->dependId, array('cal_Tasks', 'single', $rec->dependId, 'ret_url' => true, ''), null, 'ef_icon=img/16/task-normal.png');
        }
        
        if ($rec->activationCond == 'beforeTimeEnd') {
            $row->condition .= $row->distTime . tr(' преди края на ') . ht::createLink($row->dependId, array('cal_Tasks', 'single', $rec->dependId, 'ret_url' => true, ''), null, 'ef_icon=img/16/task-normal.png');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->baseId)) {
            $taskRec = cal_Tasks::fetch($rec->baseId);
            if (!cal_Tasks::haveRightFor('single', $taskRec)) {
                $requiredRoles = 'no_one';
            } elseif ($taskRec->state != 'draft' && $taskRec->state != 'waiting') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Връща наследниците на даден запис
     */
    public static function getInheritors($id, $field, &$arr = array())
    {
        $arr[$id] = $id;
        $query = self::getQuery();
        while ($rec = $query->fetch("#{$field} = ${id}")) {
            self::getInheritors($rec->id, $field, $arr);
        }
        
        return $arr;
    }
    
    
    /**
     *
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = 'Условия';
        $data->Tab = 'top';
        
        $res = parent::prepareDetail_($data);
        
        if (empty($data->recs)) {
            $data->disabled = true;
        }
        
        return $res;
    }
}
