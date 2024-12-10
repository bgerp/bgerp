<?php


/**
 * Мениджър на Етапи в проектите
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_UnsortedFolderSteps extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Етапи в папки';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, doc_Wrapper, plg_Sorting, plg_State2, plg_Modified, plg_SaveAndNew, plg_StructureAndOrder,plg_Search';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, admin';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name=Етап,saoOrder=Ред,supportUsers=Отговорници,state,lastUsedOn=Последно,modifiedOn,modifiedBy,createdOn=Създаване->На,createdBy=Създаване->От';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Етап в папка';


    /**
     * Шаблон (ET) за заглавие
     *
     * @var string
     */
    public $recTitleTpl = '[[#code#]] [#name#]';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $saoTitleField = 'name';


    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'doc/tpl/SingleUnsortedFolderSteps.shtml';


    /**
     * Заглавие в единствено число
     */
    public $details = 'StepFolders=doc_StepFolderDetails,StepTasks=cal_Tasks';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, code, description';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory');
        $this->FLD('code', 'varchar(16)', 'caption=Код,mandatory');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание');
        $this->FLD('productSteps', 'keylist(mvc=cat_ProductsProxy,select=name)', 'caption=Допълнително->Произв. етапи');

        $powerUserId = core_Roles::fetchByName('powerUser');
        $this->FLD('supportUsers', "keylist(mvc=core_Users, select=nick, where=#state !\\= \\'rejected\\' AND #roles LIKE '%|{$powerUserId}|%')", 'caption=Допълнително->Отговорници');

        $this->setDbUnique('code');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;

        $stepOptions = array();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#state = 'active' && #innerClass=" . planning_interface_StepProductDriver::getClassId());
        $pQuery->show('name,nameEn,isPublic,code');
        while($pRec = $pQuery->fetch()){
            $stepOptions[$pRec->id] = cat_Products::getRecTitle($pRec, false);
        }
        $form->setSuggestions('productSteps', array('' => '') + $stepOptions);
    }


    /**
     * Необходим метод за подреждането
     *
     * @see plg_StructureAndOrder
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        $query->where("#state = 'active'");

        while ($rec1 = $query->fetch()) {
            $res[$rec1->id] = $rec1;
        }

        return $res;
    }


    /**
     * Имплементация на метод, необходим за plg_StructureAndOrder
     */
    public function saoCanHaveSublevel($rec, $newRec = null)
    {
        return true;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-single'])){
            $row->fullName = $mvc->getSaoFullName($rec);
            if(isset($rec->saoParentId)){
                $row->saoParentId = $mvc->getSaoFullName($rec->saoParentId);
                $row->saoParentId = ht::createLink($row->saoParentId, $mvc->getSingleUrlArray($rec->saoParentId));
            }

            if(!empty($rec->productSteps)){
                $productStepsArr = keylist::toArray($rec->productSteps);
                $stepNames = array();
                foreach ($productStepsArr as $pId){
                    $stepNames[$pId] = cat_Products::getHyperlink($pId, true)->getContent() . "<br>";
                }
                $row->productSteps = implode('', $stepNames);
            }
        }
    }


    /**
     * Забранява изтриването, ако в елемента има деца
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && !empty($rec->lastUsedOn)){
            $requiredRoles = 'no_one';
        }
    }


    /**
     * Масив за избор на етап
     *
     * @param mixed $selectedKeylist - м-во за избор, null за всички
     * @param null $exId - съществуващо ид
     * @return array $options
     */
    public static function getOptionArr($selectedKeylist = null, $exId = null)
    {
        // Прави се множество от избраните етапи и техните бащи
        $me = cls::get(get_called_class());
        $unsortedFolderStepArr = keylist::toArray($selectedKeylist);
        $allStepsArr = $options = array();
        foreach ($unsortedFolderStepArr as $stepId) {
            $allStepsArr += array($stepId => $stepId) + $me->getParentsArr($stepId);
        }

        // Подреждат се и се задават като опции
        $stepQuery = $me->getQuery();
        $stepQuery->where("#state != 'rejected'");
        if(isset($selectedKeylist)){
            if(countR($allStepsArr)) {
                $stepQuery->in('id', array_keys($allStepsArr));
            } else {
                $stepQuery->where("1 = 2");
            }
        }
        if(isset($exId)) {
            $stepQuery->orWhere("#id = {$exId}");
        }

        while($stepRec = $stepQuery->fetch()) {
            $options[$stepRec->id] = $me->getSaoFullName($stepRec);
        }

        return $options;
    }


    /**
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search';
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $res .= ' ' . plg_Search::normalizeText($mvc->getSaoFullName($rec));
    }


    /**
     * Поготовка на проектите като детайл на етапите
     *
     * @param stdClass $data
     */
    public function prepareSteps(&$data)
    {
        $data->recs = $data->rows = array();
        $masterRec = $data->masterData->rec;
        $steps = $masterRec->steps;
        if(empty($steps)){
            $data->hide = true;
            return;
        }

        $stepArr = array_keys(static::getOptionArr($steps));
        $data->TabCaption = tr('Етапи') . "|* (" . countR($stepArr) . ")";

        $Tab = Request::get('Tab');
        if(!empty($Tab) && $Tab != 'Steps'){
            $data->hide = true;
            return;
        }

        $driverClassId = ($data->masterMvc instanceof support_Systems) ? support_TaskType::getClassId() : cal_TaskType::getClassId();
        $icon = ($data->masterMvc instanceof support_Systems) ? 'img/16/support.png' : 'img/16/task-normal.png';
        $addHint = ($data->masterMvc instanceof support_Systems) ? 'Създаване на нов сигнал за етапа' : 'Създаване на нова задача за етапа';
        $countHint = ($data->masterMvc instanceof support_Systems) ? 'Филтър на създадените сигнали' : 'Филтър на създадените задачи';

        $taskArr = array();
        $tQuery = cal_Tasks::getQuery();
        $tQuery->where(array("#state IN ('pending', 'active', 'waiting', 'wakeup', 'stopped') AND #folderId = '[#1#]'", $masterRec->folderId));
        $tQuery->where("#stepId IS NOT NULL AND #driverClass = {$driverClassId}");
        $tQuery->in('stepId', $stepArr);

        while($tRec = $tQuery->fetch()){
            $taskArr[$tRec->stepId][$tRec->id] = $tRec->id;
        }

        foreach ($stepArr as $stepId){
            $data->recs[$stepId] = (object)array('stepId' => $stepId, 'tasksCount' => null);
            $row = (object)array('stepId' => $this->getSaoFullName($stepId), 'tasksCount' => null);
            $row->ROW_ATTR['class'] = 'state-active';

            if(doc_UnsortedFolderSteps::haveRightFor('single', $stepId)){
                $row->stepId = ht::createLink($row->stepId, doc_UnsortedFolderSteps::getSingleUrlArray($stepId));
            }
            $countTasks = countR($taskArr[$stepId]);

            // Бутон за създаване на нова задача
            $addTaskBtn = '';
            if (cal_Tasks::haveRightFor('add', array('folderId' => $masterRec->folderId, 'driverClass' => $driverClassId))) {
                $addUrl = array('cal_Tasks', 'add', 'driverClass' => $driverClassId, 'folderId' => $masterRec->folderId, 'stepId' => $stepId);
                $addTaskBtn = ht::createLink('', $addUrl, false, "ef_icon={$icon},title={$addHint}")->getContent();
            }

            // Бутон за линк към създадените операции
            if($countTasks){
                $row->tasksCount = core_Type::getByName('varchar')->toVerbal($countTasks);
                if (cal_Tasks::haveRightFor('list')) {
                    $listUrl = array('cal_Tasks', 'list', 'driverClass' => $driverClassId, 'stateTask' => 'actPend', 'folder' => $masterRec->folderId, 'stepId' => $stepId, 'selectPeriod' => 'gr0');
                    $row->tasksCount = ht::createLink($row->tasksCount, $listUrl, false, "title={$countHint}");
                }
                $row->tasksCount = "<span class='systemFlag normal_priority'>{$row->tasksCount}</span>";
            }

            $row->tasksCount = $addTaskBtn . $row->tasksCount;
            $data->rows[$stepId] = $row;
        }

        return $data;
    }


    /**
     * Рендиране на проектите като детайл на етапите
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderSteps(&$data)
    {
        if($data->hide) return;

        $tpl = new core_ET('');

        // Рендиране на таблицата с оборудването
        $taskCaption = ($data->masterMvc instanceof support_Systems) ? 'Сигнали' : 'Задачи';
        $data->listFields = arr::make("stepId=Етап,tasksCount={$taskCaption}");

        $listTableMvc = clone $this;
        $listTableMvc->FNC('stepId', 'varchar','tdClass=leftCol');
        $listTableMvc->FNC('tasksCount', 'varchar','tdClass=leftCol');
        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));

        $tpl->append($table->get($data->rows, $data->listFields));
        if ($data->Pager) {
            $tpl->append($data->Pager->getHtml());
        }

        $resTpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $resTpl->append($tpl, 'content');
        $resTpl->append(tr("Етапи"), 'title');

        return $resTpl;
    }
}