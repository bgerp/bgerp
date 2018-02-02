<?php

/**
 * Мениджър на отчети относно 
 * задания за артикули с възложени задачи
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
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField = 'productId , jobsId';

    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
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
        $fieldset->FLD('assignedUsers', 'userList(roles=powerUser)', 
            'caption=Отговорници,mandatory,after = title');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder            
     * @param stdClass $data            
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, 
        embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
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
            
            $jobsProdId = $jobses->productId;
            
            $jobsesId = $jobses->id;
            
            // Връзки към задачи от задание
            $resArrJobses = doc_Linked::getRecsForType('doc', $jobses->containerId, FALSE);
            
            foreach ($resArrJobses as $d) {
                
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
                
                $taskContainerId[] = ($task->containerId);
                $taskFolderId[] = ($task->folderId);
                
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
                            'linkFrom' => 'job'
                        );
                    } else {
                        
                        $obj = &$recs[$jobsesId];
                        
                        $obj->tasksFolderId .= ',' . $task->folderId;
                        
                        $obj->tasksContainerId .= ',' . $task->containerId;
                    }
                }
            }
            
            // Връзки към задачи от артикул
            $recArt = cat_Products::fetch($jobses->productId);
            
            $resArrProduct = doc_Linked::getRecsForType('doc', $recArt->containerId, FALSE);
            
            foreach ($resArrProduct as $d) {
                
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
                
                $taskContainerId[] = ($task->containerId);
                $taskFolderId[] = ($task->folderId);
                
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
                            'linkFrom' => 'art'
                        );
                    } else {
                        
                        $obj = &$recs[$jobsesId];
                        
                        $obj->tasksFolderId .= ',' . $task->folderId;
                        
                        $obj->tasksContainerId .= ',' . $task->containerId;
                    }
                }
            }
        }
        
        return $recs;
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
        
        if ($export === FALSE) {
            
            $fld->FLD('jobsId', 'varchar', 'caption=Задание');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('btn', 'varchar', 'caption=Връзка');
        } else {
            
            $fld->FLD('jobsId', 'varchar', 'caption=Задание,tdClass=centered');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
        }
        
        return $fld;
    }

    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec-
     *            записа
     * @param stdClass $dRec-
     *            чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        $tasksContainerIdArr = explode(',', $dRec->tasksContainerId);
        
        $tasksFolderIdArr = explode(',', $dRec->tasksFolderId);
        
        // class="state-draft document-handler///
        // class="{'$state'}-handler"///
        
        $row->jobsId = planning_Jobs::getHyperlink($dRec->jobsId);
        
        if ($dRec->linkFrom == 'job') {
            
            foreach ($tasksContainerIdArr as $k => $v) {
                
                $folderLink = doc_Folders::recToVerbal(
                    doc_Folders::fetch($tasksFolderIdArr[$k]))->title;
                
                $Task = doc_Containers::getDocument($v);
                
                $state = cal_Tasks::fetch($Task->that)->state;
                
                $handle = $Task->getHandle();
                
                $folder = doc_Folders::fetch($tasksFolderIdArr[$k])->title;
                
                $singleUrl = $Task->getUrlWithAccess($Task->getInstance(), $Task->that);
                // quiet small///state-{$state}-handler
                
                $row->jobsId .= '<div class=" quiet small">' . $folderLink . ' »  ' .
                     ht::createLink("#{$handle}", $singleUrl, FALSE, 
                        "ef_icon={$Document->singleIcon}") . "</div>";
            }
        }
        $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        
        if ($dRec->linkFrom == 'art') {
            
            foreach ($tasksContainerIdArr as $k => $v) {
                
                $folderLink = doc_Folders::recToVerbal(
                    doc_Folders::fetch($tasksFolderIdArr[$k]))->title;
                
                $Task = doc_Containers::getDocument($v);
                
                $state = cal_Tasks::fetch($Task->that)->state;
                
                $handle = $Task->getHandle();
                
                $folder = doc_Folders::fetch($tasksFolderIdArr[$k])->title;
                
                $singleUrl = $Task->getUrlWithAccess($Task->getInstance(), $Task->that);
                // quiet small///state-{$state}-handler
                
                $row->productId .= '<div class=" quiet small">' . $folderLink . ' »  ' .
                     ht::createLink("#{$handle}", $singleUrl, FALSE, 
                        "ef_icon={$Document->singleIcon}") . "</div>";
            }
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