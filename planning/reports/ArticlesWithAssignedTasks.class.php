<?php


/**
 * Мениджър на отчети относно задания за артикули с възложени задачи
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
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
     *
     * @var string
     */
    protected $hashField = 'productId , jobsId';
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'productId';
    
    
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
        $fieldset->FLD('assignedUsers', 'userList(roles=powerUser)', 'caption=Отговорници,mandatory,after = title,single=none');
        $fieldset->FLD(
            'typeOfSorting',
            'enum(up=Възходящо,down=Низходящо)',
            'caption=Подредени по->Ред,maxRadio=2,columns=2,mandatory,after=title,single = none'
        );
        $fieldset->FLD(
            'orderingDate',
            'enum(activated=Дата на активиране,pay=Дата на падеж)',
            'caption=Подредени по->Дата,maxRadio=2,columns=2,mandatory,after=typeOfSorting,single=none'
        );
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
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
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        core_App::setTimeLimit(300);
        $recs = array();
        $assignedUsers = keylist::toArray($rec->assignedUsers ?? '');
        if (!count($assignedUsers)) return $recs;

        $currentUser = core_Users::getCurrent();
        $reportUser = $rec->createdBy ?? $currentUser;
        /** @var core_Query $jobsQuery */
        $jobsQuery = planning_Jobs::getQuery();
        $jobsQuery->in('state', 'active,wakeup');
        $jobsQuery->show('id,productId,folderId,saleId,containerId,dueDate,deliveryDate,activatedOn,history');
        if ($jobsQuery->getField('designers', false)) {
            $jobsQuery->show('designers');
        }
        $jobsQuery->selectOnReplica();
        $jobs = $jobsQuery->fetchAll();
        if (!count($jobs)) return $recs;

        $products = self::loadReportRecords(cls::get('cat_Products'), arr::extractValuesFromArray($jobs, 'productId'), 'id,containerId');
        $sourceIds = arr::extractValuesFromArray($jobs, 'containerId');
        $sourceIds = array_merge($sourceIds, arr::extractValuesFromArray($products, 'containerId'));
        $linksBySource = self::loadReportLinks($sourceIds);
        $targetIds = array();
        foreach ($linksBySource as $links) {
            foreach ($links as $link) {
                if (($link->inType ?? null) == 'doc') $targetIds[$link->inVal] = $link->inVal;
            }
        }
        $containers = self::loadReportRecords(cls::get('doc_Containers'), $targetIds, 'id,docId,docClass');
        $taskClassId = cal_Tasks::getClassId();
        $taskIds = array();
        $taskClasses = array($taskClassId => true);
        foreach ($containers as $container) {
            $classId = $container->docClass ?? null;
            if (!$classId) continue;
            if (!array_key_exists($classId, $taskClasses)) {
                $taskClasses[$classId] = cls::get($classId) instanceof cal_Tasks;
            }
            if ($taskClasses[$classId]) $taskIds[$container->docId ?? 0] = $container->docId ?? 0;
        }
        // Пълните записи са нужни и за проверката на права от плъгините на задачите.
        $tasks = self::loadReportRecords(cls::get('cal_Tasks'), $taskIds);
        $rights = $tasksBySource = array();
        foreach ($linksBySource as $sourceId => $links) {
            $tasksBySource[$sourceId] = array();
            foreach ($links as $link) {
                if (($link->inType ?? null) != 'doc') continue;
                $container = $containers[$link->inVal ?? 0] ?? null;
                if (!$container || empty($taskClasses[$container->docClass ?? 0])) continue;
                $task = $tasks[$container->docId ?? 0] ?? null;
                if (!$task || ($task->state ?? null) == 'rejected' || !keylist::isIn($assignedUsers, $task->assign ?? '')) continue;
                if ($currentUser != ($link->createdBy ?? null)) {
                    $rightsKey = ($container->docClass ?? 0) . '|' . ($task->id ?? 0);
                    if (!array_key_exists($rightsKey, $rights)) {
                        $rights[$rightsKey] = ($container->docClass ?? null) == $taskClassId
                            ? cal_Tasks::haveRightFor('single', $task, $reportUser)
                            : doc_Containers::getDocument($container)->haveRightFor('single', $reportUser);
                    }
                    if (!$rights[$rightsKey]) continue;
                }
                $tasksBySource[$sourceId][] = $task;
            }
        }

        foreach ($jobs as $job) {
            $jobId = $job->id;
            $row = (object) array(
                'productId' => $job->productId ?? null,
                'jobsId' => $jobId,
                'folderId' => $job->folderId ?? null,
                'saleId' => $job->saleId ?? null,
                'containerId' => $job->containerId ?? null,
                'dueDate' => $job->dueDate ?? null,
                'deliveryDate' => $job->deliveryDate ?? null,
                'activatedDate' => self::getActivationDate($job),
            );
            if (keylist::isIn($assignedUsers, $job->designers ?? '')) $recs[$jobId] = $row;
            $product = $products[$job->productId ?? 0] ?? null;
            $sources = array('job' => $job->containerId ?? 0, 'art' => $product->containerId ?? 0);
            foreach ($sources as $linkFrom => $sourceId) {
                foreach ($tasksBySource[$sourceId] ?? array() as $task) {
                    if (!isset($recs[$jobId])) {
                        $row->tasksFolderId = $task->folderId ?? null;
                        $row->tasksContainerId = $task->containerId ?? null;
                        $row->linkFrom = $linkFrom;
                        $recs[$jobId] = $row;
                    } else {
                        $row->tasksFolderId = ($row->tasksFolderId ?? '') . ',' . ($task->folderId ?? '');
                        $row->tasksContainerId = ($row->tasksContainerId ?? '') . ',' . ($task->containerId ?? '');
                        $row->linkFrom = ($row->linkFrom ?? '') . ',' . $linkFrom;
                    }
                }
            }
        }

        // Подрежда по дата на падеж
        if (($rec->orderingDate ?? 'activated') == 'pay') {
            if (($rec->typeOfSorting ?? 'up') == 'up') {
                $sorting = 'orderByPayDateUp';
            } else {
                $sorting = 'orderByPayDateDown';
            }
            
            usort($recs, array(
                $this, $sorting
            ));
        }
        
        // Подрежда по дата на активиране
        if (($rec->orderingDate ?? 'activated') == 'activated') {
            if (($rec->typeOfSorting ?? 'up') == 'up') {
                $sorting = 'orderByActivatedDateUp';
            } else {
                $sorting = 'orderByActivatedDateDown';
            }
            
            usort($recs, array($this, $sorting));
        }
        
        return $recs;
    }
    

    /**
     * @param core_Manager $mvc
     * @param array $ids
     * @param string|null $fields
     * @return array
     */
    private static function loadReportRecords($mvc, $ids, $fields = null)
    {
        $records = array();
        foreach (array_chunk(array_unique(array_filter($ids)), 500) as $chunk) {
            /** @var core_Query $query */
            $query = $mvc->getQuery();
            $query->in('id', $chunk);
            if ($fields !== null) $query->show($fields);
            $query->selectOnReplica();
            $records += $query->fetchAll();
        }

        return $records;
    }


    /**
     * Запазва двете посоки, реда и лимита 100 от doc_Linked::getRecsForType().
     */
    private static function loadReportLinks($sourceIds)
    {
        $result = array();
        foreach (array_chunk(array_unique(array_filter($sourceIds)), 500) as $chunk) {
            $ids = implode(',', array_map('intval', $chunk));
            /** @var core_Query $query */
            $query = doc_Linked::getQuery();
            $query->where("#state != 'rejected'");
            $query->setUnion("#outType = 'doc' AND #outVal IN ({$ids})");
            $query->setUnion("#inType = 'doc' AND #inVal IN ({$ids})");
            $query->orderBy('createdOn', 'DESC');
            $query->show('id,outType,outVal,inType,inVal,createdBy,createdOn');
            $query->selectOnReplica();
            $wanted = array_fill_keys($chunk, true);
            foreach ($query->fetchAll() as $link) {
                $sources = array();
                if (($link->outType ?? null) == 'doc' && isset($wanted[$link->outVal ?? 0])) $sources[$link->outVal] = true;
                if (($link->inType ?? null) == 'doc' && isset($wanted[$link->inVal ?? 0])) $sources[$link->inVal] = true;
                foreach (array_keys($sources) as $sourceId) $result[$sourceId][] = $link;
            }
        }
        foreach ($result as $sourceId => $links) {
            if (count($links) >= 100) {
                // При граничния лимит оставяме SQL да избере същите 100 връзки, включително равните дати.
                /** @var core_Query $query */
                $query = doc_Linked::getQuery();
                $query->where("#state != 'rejected'");
                $query->setUnion(array("#outType = 'doc' AND #outVal = '[#1#]'", $sourceId));
                $query->setUnion(array("#inType = 'doc' AND #inVal = '[#1#]'", $sourceId));
                $query->orderBy('createdOn', 'DESC');
                $query->limit(100);
                $query->selectOnReplica();
                $result[$sourceId] = array_values($query->fetchAll());
            }
        }

        return $result;
    }


    /**
     * Историята е резервен източник само когато липсва activatedOn.
     */
    private static function getActivationDate($job)
    {
        $date = $job->activatedOn ?? null;
        if (!$date) {
            foreach ($job->history ?? array() as $event) {
                if (($event['action'] ?? null) == 'Активиране') $date = $event['date'] ?? null;
            }
        }

        return $date;
    }


    // Подреждане на масива по дата на падеж
    public function orderByPayDateUp($a, $b)
    {
        return ($a->dueDate ?? '') <=> ($b->dueDate ?? '');
    }
    
    public function orderByPayDateDown($a, $b)
    {
        return ($b->dueDate ?? '') <=> ($a->dueDate ?? '');
    }
    
    // Подреждане на масива по дата на активиране
    public function orderByActivatedDateUp($a, $b)
    {
        return ($a->activatedDate ?? '') <=> ($b->activatedDate ?? '');
    }
    
    public function orderByActivatedDateDown($a, $b)
    {
        return ($b->activatedDate ?? '') <=> ($a->activatedDate ?? '');
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        /** @var core_FieldSet $fld */
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('jobsId', 'varchar', 'caption=Задание');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        if ($export === false) {
            $fld->FLD('btn', 'varchar', 'caption=Връзка');
        } else {
            $fld->FLD('tasks', 'varchar', 'caption=Задачи');
        }
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущатаfyh страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        /** @var type_Date $Date */
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        if (($rec->orderingDate ?? 'activated') == 'pay') {
            $typeOfDateText = 'Падеж : ';
            $typeOfDate = $dRec->dueDate ?? null;
        } else {
            $typeOfDateText = 'Активиране : ';
            $typeOfDate = $dRec->activatedDate ?? null;
        }
        
        $tasksContainerIdArr = !empty($dRec->tasksContainerId) ? explode(',', $dRec->tasksContainerId) : array();
        
        $tasksFolderIdArr = !empty($dRec->tasksFolderId) ? explode(',', $dRec->tasksFolderId) : array();
        
        $linkFromArr = !empty($dRec->linkFrom) ? explode(',', $dRec->linkFrom) : array();
        
        $row->jobsId = planning_Jobs::getHyperlink($dRec->jobsId) . '<br>';
        
        if (!empty($dRec->saleId)) {
            $saleRec = sales_Sales::fetch($dRec->saleId);
            $Sale = doc_Containers::getDocument($saleRec->containerId ?? null);
            
            $saleNandle = sales_Sales::getHandle($dRec->saleId);
            $saleState = $saleRec->state ?? '';
            $singleUrl = $Sale->getUrlWithAccess($Sale->getInstance(), $Sale->that);
            
            $row->jobsId = ($row->jobsId ?? '') . "<span class= 'small' >" . "{$typeOfDateText}" . $Date->toVerbal($typeOfDate) . '</span>' .
                 ' »  ' . "<span class= 'state-{$saleState} document-handler' >" . ht::createLink(
                     "#{$saleNandle}",
                    $singleUrl,
                     false,
                     "ef_icon={$Sale->getSingleIcon()}"
                 ) . '</span>';
        } else {
            $row->jobsId = ($row->jobsId ?? '') . "<span class= 'small' >" . "{$typeOfDateText}" . $Date->toVerbal($typeOfDate) . '</span>';
        }
        
        $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name') . '<br>';
        // Кешът е само за текущото визуализиране, потребител, език и режим.
        static $taskLinks = array();
        static $folderLinks = array();
        $context = ($rec->id ?? 0) . '|' . core_Users::getCurrent() . '|' . core_Lg::getCurrent()
            . '|' . Mode::get('text') . '|' . (int) Mode::is('printing');
        foreach ($tasksContainerIdArr as $k => $containerId) {
            $linkFrom = $linkFromArr[$k] ?? null;
            if (!$containerId || !in_array($linkFrom, array('job', 'art'))) continue;
            $folderId = $tasksFolderIdArr[$k] ?? null;
            $cacheKey = $context . '|' . $containerId . '|' . $folderId;
            if (!isset($taskLinks[$cacheKey])) {
                $folderKey = $context . '|' . $folderId;
                if (!isset($folderLinks[$folderKey])) {
                    $folderRec = $folderId ? doc_Folders::fetch($folderId) : null;
                    $folderRow = $folderRec ? doc_Folders::recToVerbal($folderRec) : null;
                    $folderLinks[$folderKey] = $folderRow->title ?? '';
                }
                $folderLink = $folderLinks[$folderKey];
                $Task = doc_Containers::getDocument($containerId);
                $taskRec = cal_Tasks::fetch($Task->that);
                $state = $taskRec->state ?? '';
                $handle = $Task->getHandle();
                $singleUrl = $Task->getUrlWithAccess($Task->getInstance(), $Task->that);
                $taskLinks[$cacheKey] = "<span class= 'state-{$state} document-handler' >" .
                    ht::createLink("#{$handle}", $singleUrl, false, "ef_icon={$Task->getSingleIcon()}") .
                    "</span> » <span class= 'quiet small'>" . $folderLink . '</span>';
            }
            if ($linkFrom == 'job') {
                $row->jobsId .= "<div style='margin-top: 2px;'>" . $taskLinks[$cacheKey] . ' » </div>';
            } else {
                $row->productId .= '<div >' . $taskLinks[$cacheKey] . '</div>';
            }
        }

        // Добавяме бутон за създаване на задача
        
        if (!empty($dRec->containerId) && doc_Linked::haveRightFor('addlink')) {
            Request::setProtected(
                array(
                    'inType',
                    'foreignId'
                )
            
            );
            
            $doc = doc_Containers::getDocument($dRec->containerId);
            
            if ($doc->haveRightFor('single')) {
                $row->btn = ht::createBtn(
                    
                    'Връзка',
                    array(
                        'doc_Linked',
                        'Link',
                        'foreignId' => $dRec->containerId,
                        'inType' => 'doc',
                        'ret_url' => true
                    ),
                    
                    false,
                    
                    false,
                    
                    'ef_icon = img/16/doc_tag.png, title=Връзка към документа'
                
                );
            }
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN assignedUsers--><div>|Възложено на|*: [#assignedUsers#]</div><!--ET_END assignedUsers-->
                                        <!--ET_BEGIN orderingDate--><div>|Подредени по|*: [#orderingDate#]</div><!--ET_END orderingDate-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->assignedUsers)) {
            $marker = 0;
            $assignedUsers = keylist::toArray($data->rec->assignedUsers);
            $assignedCount = count($assignedUsers);
            foreach ($assignedUsers as $val) {
                $marker++;
                $valVerb = core_Users::getTitleById($val) ;
                
                if ($assignedCount - $marker != 0) {
                    $valVerb .= ', ';
                }
                
                
                $fieldTpl->append('<b>' .$valVerb. '</b>', 'assignedUsers');
            }
        }
        
        if (isset($data->rec->orderingDate)) {
            $text = ($data->rec->orderingDate == 'activated')?'Дата на активиране' : 'Дата на падеж';
            
            $fieldTpl->append('<b>' . $text . '</b>', 'orderingDate');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');

        $tpl->appendOnce('var forceReloadAfterBack = true;', 'SCRIPTS');
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     *                                         - драйвер
     * @param stdClass            $res
     *                                         - резултатен запис
     * @param stdClass            $rec
     *                                         - запис на справката
     * @param stdClass            $dRec
     *                                         - запис на реда
     * @param core_BaseClass      $ExportClass
     *                                         - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->jobsId = planning_Jobs::getTitleById($dRec->jobsId);
        if (! empty($dRec->tasksContainerId)) {
            $taskArr = array();
            $tasks = array_filter(explode(',', $dRec->tasksContainerId));
            static $handles = array();
            foreach ($tasks as $contId) {
                if (!isset($handles[$contId])) $handles[$contId] = '#' . doc_Containers::getDocument($contId)->getHandle();
                $taskArr[] = $handles[$contId];
            }
            $res->tasks = implode(', ', $taskArr);
        }
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        // Намира се последните две версии
        /** @var core_Query $query */
        $query = frame2_ReportVersions::getQuery();
        $query->where("#reportId = {$rec->id}");
        $query->orderBy('id', 'DESC');
        $query->limit(2);
        
        // Маха се последната
        $all = $query->fetchAll();
        unset($all[key($all)]);
        
        // Ако няма предпоследна, бие се нотификация
        if (! countR($all)) {
            
            return true;
        }
        $oldRec = $all[key($all)]->oldRec ?? null;
        $dataRecsNew = $rec->data->recs ?? null;
        $dataRecsOld = $oldRec->data->recs ?? null;
        
        if (! is_array($dataRecsOld)) {
            
            return true;
        }
        
        $oldByJob = array();
        foreach ($dataRecsOld as $oldRow) $oldByJob[$oldRow->jobsId ?? 0] = $oldRow;
        if (is_array($dataRecsNew)) {
            foreach ($dataRecsNew as $new) {
                $old = $oldByJob[$new->jobsId ?? 0] ?? null;
                
                // Ако има нов документ - известяване
                if (!$old) {
                    
                    return true;
                }
                
                // Ако има промяна в крайния срок - известяване
                if (($new->dueDate ?? null) != ($old->dueDate ?? null)) {
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec
     *                      - запис
     *
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
