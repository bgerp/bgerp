<?php

/**
 * Мениджър на отчети относно задания за артикули с възложени задачи
 *
 * @category  bgerp
 * @package   planning
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания » Задания за артикули с възложени задачи
 */
class planning_reports_ArticlesWithAssignedTasks extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,powerUser';

    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields;

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting';

    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var string
     */
    protected $hashField = 'productId , jobsId';

    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck = 'productId';

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;

    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = '';

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset            
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('assignedUsers', 'userList(roles=powerUser)', 'caption=Отговорници,mandatory,after = title');
        $fieldset->FLD('typeOfSorting', 'enum(up=Възходящо,down=Низходящо)', 
            'caption=Подредени,maxRadio=2,columns=2,mandatory,after=title');
        $fieldset->FLD('orderingDate', 'enum(activated=Дата на активиране,pay=Дата на падеж)', 
            'caption=Подредени,maxRadio=2,columns=2,mandatory,after=title');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver            
     * @param embed_Manager $Embedder            
     * @param stdClass $data            
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        $form->setDefault('typeOfSorting', 'up');
        $form->setDefault('orderingDate', 'activated');
    }

    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec            
     * @param stdClass $data            
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();
        $productsForJobs = array();
        
        $jobsQuery = planning_Jobs::getQuery();
        
        $jobsQuery->where("#state = 'active' OR #state = 'wakeup'");
        $jobsQuery->where("#saleId IS NOT NULL");
        
        /*
         * Масив с артикули по задания за производство
         */
        while ($jobses = $jobsQuery->fetch()) {
            
            $deliveryDate = $jobses->deliveryDate;
            
            if (! $jobses->activatedOn) {
                
                foreach ($jobses->history as $v) {
                    
                    if ($v['action'] == 'Активиране');
                    {
                        
                        $activatedDate = $v['date'];
                    }
                }
            } else {
                
                $activatedDate = $jobses->activatedOn;
            }
            
            $jobsProdId = $jobses->productId;
            
            $jobsesId = $jobses->id;
            
            // Връзки към задачи от задание
            $resArrJobses = doc_Linked::getRecsForType('doc', $jobses->containerId, FALSE);
            
            foreach ($resArrJobses as $d) {
                
                $linkFrom = 'job';
                
                if ($d->inType != 'doc')
                    continue;
                $Document = doc_Containers::getDocument($d->inVal);
                
                if (core_Users::getCurrent() != $d->credatedBy) {
                    
                    if (! $Document->haveRightFor('single', $rec->createdBy))
                        continue;
                }
                
                if (! $Document->isInstanceOf('cal_Tasks'))
                    continue;
                
                $task = cal_Tasks::fetch($Document->that);
                
                if ($task->state == 'rejected')
                    continue;
                
                $assignedUsers = keylist::toArray($rec->assignedUsers);
                
                if (keylist::isIn($assignedUsers, $task->assign)) {
                    
                    if (! array_key_exists($jobsesId, $recs)) {
                        
                        $recs[$jobsesId] = (object) array(
                            
                            'productId' => $jobsProdId,
                            'jobsId' => $jobses->id,
                            'folderId' => $jobses->folderId,
                            'containerId' => $jobses->containerId,
                            'tasksFolderId' => $task->folderId,
                            'tasksContainerId' => $task->containerId,
                            'linkFrom' => $linkFrom,
                            'deliveryDate' => $deliveryDate,
                            'activatedDate' => $activatedDate
                        );
                    } else {
                        
                        $obj = &$recs[$jobsesId];
                        
                        $obj->tasksFolderId .= ',' . $task->folderId;
                        
                        $obj->tasksContainerId .= ',' . $task->containerId;
                        
                        $obj->linkFrom .= ',' . $linkFrom;
                    }
                }
            }
            
            // Връзки към задачи от артикул
            $recArt = cat_Products::fetch($jobses->productId);
            
            $resArrProduct = doc_Linked::getRecsForType('doc', $recArt->containerId, FALSE);
            
            foreach ($resArrProduct as $d) {
                
                $linkFrom = 'art';
                
                if ($d->inType != 'doc')
                    continue;
                $Document = doc_Containers::getDocument($d->inVal);
                
                if (core_Users::getCurrent() != $d->credatedBy) {
                    
                    if (! $Document->haveRightFor('single', $rec->createdBy))
                        continue;
                }
                
                if (! $Document->isInstanceOf('cal_Tasks'))
                    continue;
                
                $task = cal_Tasks::fetch($Document->that);
                
                if ($task->state == 'rejected')
                    continue;
                
                $assignedUsers = keylist::toArray($rec->assignedUsers);
                
                if (keylist::isIn($assignedUsers, $task->assign)) {
                    
                    if (! array_key_exists($jobsesId, $recs)) {
                        
                        $recs[$jobsesId] = (object) array(
                            
                            'productId' => $jobsProdId,
                            'jobsId' => $jobses->id,
                            'folderId' => $jobses->folderId,
                            'containerId' => $jobses->containerId,
                            'tasksFolderId' => $task->folderId,
                            'tasksContainerId' => $task->containerId,
                            'linkFrom' => $linkFrom,
                            'deliveryDate' => $deliveryDate,
                            'activatedDate' => $activatedDate
                        );
                    } else {
                        
                        $obj = &$recs[$jobsesId];
                        
                        $obj->tasksFolderId .= ',' . $task->folderId;
                        
                        $obj->tasksContainerId .= ',' . $task->containerId;
                        
                        $obj->linkFrom .= ',' . $linkFrom;
                    }
                }
            }
        }
        
        // Подрежда по дата на падеж
        if ($rec->orderingDate == 'pay') {
            if ($rec->typeOfSorting == 'up') {
                
                $sorting = 'orderByPayDateUp';
            } else {
                
                $sorting = 'orderByPayDateDown';
            }
            
            usort($recs, array(
                $this,
                "$sorting"
            ));
        }
        
        // Подрежда по дата на активиране
        if ($rec->orderingDate == 'activated') {
            if ($rec->typeOfSorting == 'up') {
                
                $sorting = 'orderByActivatedDateUp';
            } else {
                
                $sorting = 'orderByActivatedDateDown';
            }
            
            usort($recs, array(
                $this,
                "$sorting"
            ));
        }
        
        return $recs;
    }
    
    // Подреждане на масива по дата на падеж
    function orderByPayDateUp($a, $b)
    {
        return $a->deliveryDate > $b->deliveryDate;
    }

    function orderByPayDateDown($a, $b)
    {
        return $a->deliveryDate < $b->deliveryDate;
    }
    
    // Подреждане на масива по дата на активиране
    function orderByActivatedDateUp($a, $b)
    {
        return $a->activatedDate > $b->activatedDate;
    }

    function orderByActivatedDateDown($a, $b)
    {
        return $a->activatedDate < $b->activatedDate;
    }

    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *            - записа
     * @param boolean $export
     *            - таблицата за експорт ли е
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('jobsId', 'varchar', 'caption=Задание');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        if ($export === FALSE) {
            $fld->FLD('btn', 'varchar', 'caption=Връзка');
        } else {
            $fld->FLD('tasks', 'varchar', 'caption=Задачи');
        }
        
        return $fld;
    }

    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *            - записа
     * @param stdClass $dRec
     *            - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        if ($rec->orderingDate == 'activated') {
            $typeOfDateText = 'Активиран : ';
            $typeOfDate = $dRec->activatedDate;
        }
        
        if ($rec->orderingDate == 'pay') {
            $typeOfDateText = 'Падеж : ';
            $typeOfDate = $dRec->deliveryDate;
        }
        
        $tasksContainerIdArr = explode(',', $dRec->tasksContainerId);
        
        $tasksFolderIdArr = explode(',', $dRec->tasksFolderId);
        
        $linkFromArr = explode(',', $dRec->linkFrom);
        
        $row->jobsId = planning_Jobs::getHyperlink($dRec->jobsId) . "<br>";
        
        $row->jobsId .= "<span class= 'small' >" . "$typeOfDatеText" . $Date->toVerbal($typeOfDate) . "</span>";
        
        foreach ($tasksContainerIdArr as $k => $v) {
            
            if ($linkFromArr[$k] != 'job')
                continue;
            
            $folderLink = doc_Folders::recToVerbal(doc_Folders::fetch($tasksFolderIdArr[$k]))->title;
            
            $Task = doc_Containers::getDocument($v);
            
            $state = cal_Tasks::fetch($Task->that)->state;
            
            $handle = $Task->getHandle();
            
            $folder = doc_Folders::fetch($tasksFolderIdArr[$k])->title;
            
            $singleUrl = $Task->getUrlWithAccess($Task->getInstance(), $Task->that);
            
            $row->jobsId .= "<div style='margin-top: 2px;'><span class= 'state-{$state} document-handler' >" . ht::createLink(
                "#{$handle}", $singleUrl, FALSE, "ef_icon={$Task->singleIcon}") . "</span>" . ' »  ' .
                 "<span class= 'quiet small'>" . $folderLink . "</span>" . "</div>";
        }
        
        $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name') . '<br>';
        
        foreach ($tasksContainerIdArr as $k => $v) {
            
            if ($linkFromArr[$k] != 'art')
                continue;
            
            $folderLink = doc_Folders::recToVerbal(doc_Folders::fetch($tasksFolderIdArr[$k]))->title;
            
            $Task = doc_Containers::getDocument($v);
            
            $state = cal_Tasks::fetch($Task->that)->state;
            
            $handle = $Task->getHandle();
            
            $folder = doc_Folders::fetch($tasksFolderIdArr[$k])->title;
            
            $singleUrl = $Task->getUrlWithAccess($Task->getInstance(), $Task->that);
            
            $row->productId .= "<div ><span class= 'state-{$state} document-handler' >" .
                 ht::createLink("#{$handle}", $singleUrl, FALSE, "ef_icon={$Task->singleIcon}") . "</span>" . ' »  ' .
                 "<span class= 'quiet small'>" . $folderLink . "</span></div>";
        }
        
        // Добавяме бутон за създаване на задача
        
        if ($dRec->containerId && doc_Linked::haveRightFor('addlink')) {
            
            Request::setProtected(
                array(
                    'inType',
                    'foreignId'
                ));
            
            $doc = doc_Containers::getDocument($dRec->containerId);
            
            if ($doc->haveRightFor('single')) {
                
                $row->btn = ht::createBtn('Връзка', 
                    array(
                        'doc_Linked',
                        'Link',
                        'foreignId' => $dRec->containerId,
                        'inType' => 'doc',
                        'ret_url' => TRUE
                    ), FALSE, FALSE, 'ef_icon = img/16/doc_tag.png, title=Връзка към документа');
            }
        }
        
        return $row;
    }

    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     *            - драйвер
     * @param stdClass $res
     *            - резултатен запис
     * @param stdClass $rec
     *            - запис на справката
     * @param stdClass $dRec
     *            - запис на реда
     * @param core_BaseClass $ExportClass
     *            - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->jobsId = planning_Jobs::getTitleById($dRec->jobsId);
        if (! empty($dRec->tasksContainerId)) {
            $taskArr = array();
            $tasks = explode(',', $dRec->tasksContainerId);
            foreach ($tasks as $contId) {
                $taskArr[] = "#" . doc_Containers::getDocument($contId)->getHandle();
            }
            $res->tasks = implode(', ', $taskArr);
        }
    }

    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec            
     * @return boolean $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        // Намира се последните две версии
        $query = frame2_ReportVersions::getQuery();
        $query->where("#reportId = {$rec->id}");
        $query->orderBy('id', 'DESC');
        $query->limit(2);
        
        // Маха се последната
        $all = $query->fetchAll();
        unset($all[key($all)]);
        
        // Ако няма предпоследна, бие се нотификация
        if (! count($all))
            return TRUE;
        $oldRec = $all[key($all)]->oldRec;
        $dataRecsNew = $rec->data->recs;
        $dataRecsOld = $oldRec->data->recs;
        
        if (! is_array($dataRecsOld))
            return TRUE;
        
        if (is_array($dataRecsNew)) {
            foreach ($dataRecsNew as $index => $new) {
                $old = $dataRecsNew[$index];
                
                // Ако има нов документ - известяване
                if (! array_key_exists($index, $dataRecsOld))
                    return TRUE;
                    
                    // Ако има промяна в крайния срок - известяване
                if ($new->dueDate != $old->dueDate)
                    return TRUE;
            }
        }
        
        return FALSE;
    }

    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec
     *            - запис
     * @return array|FALSE - масив с три дати или FALSE ако не може да се обновява
     */
    public function getNextRefreshDates($rec)
    {
        $date = new DateTime(dt::now());
        $date->add(new DateInterval('P0DT0H5M0S'));
        $d1 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval('P0DT0H5M0S'));
        $d2 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval('P0DT0H5M0S'));
        $d3 = $date->format('Y-m-d H:i:s');
        
        return array(
            $d1,
            $d2,
            $d3
        );
    }
}