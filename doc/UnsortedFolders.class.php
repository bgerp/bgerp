<?php


/**
 * Клас 'doc_UnsortedFolders' - Корици на папки с несортирани документи
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_UnsortedFolders extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,plg_Clone,doc_FolderPlg,plg_RowTools2,plg_Search, plg_Modified, plg_Sorting';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Заглавие
     */
    public $title = 'Проекти';
    
    
    /**
     * var $listFields = 'id,title,inCharge=Отговорник,threads=Нишки,last=Последно';
     */
    public $oldClassName = 'email_Unsorted';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, description, contragentFolderId';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Проект';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/project-archive.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'doc/tpl/SingleLayoutUnsortedFolder.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кое поле да се използва за линк към нишките на папката
     */
    public $listFieldForFolderLink = 'folder';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,folder=Папка,inCharge=Отговорник,createdOn,createdBy';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го види?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin';
    
    
    /**
     * Кой може да го оттегли?
     */
    public $canReject = 'powerUser';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'powerUser';
    
    
    /**
     * Кой има права Rip
     */
    public $canWrite = 'powerUser';
    
    
    /**
     * Кои полета можем да редактираме, ако записът е системен
     */
    public $protectedSystemFields = 'none';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin';
    
    
    /**
     * Кой има право да оттегля системните данни?
     */
    public $canRejectsysdata = 'admin';
    
    
    /**
     * масив с цветове
     */
    public static $colors = array('#610b7d',
        '#1b7d23',
        '#4a4e7d',
        '#7d6e23',
        '#33757d',
        '#211b7d',
        '#72142d',
        '#EE82EE',
        '#0080d0',
        '#FF1493',
        '#C71585',
        '#0d777d',
        '#4B0082',
        '#7d1c24',
        '#483D8B',
        '#7b237d',
        '#8B008B',
        '#FFC0CB',
        '#cc0000',
        '#00cc00',
        '#0000cc',
        '#cc00cc',
        '#3366CC',
        '#FF9999',
        '#FF3300',
        '#9999FF',
        '#330033',
        '#003300',
        '#0000FF',
        '#FFFF33',
        '#66CDAA',
        '#98FB98',
        '#4169E1',
        '#D2B48C',
        '#9ACD32',
        '#00FF7F',
        '#4169E1',
        '#EEE8AA',
        '#9370DB',
        '#3CB371',
        '#FFB6C1',
        '#DAA520',
        '#483D8B',
        '#8B0000',
        '#00FFFF',
        '#DC143C',
        '#8A2BE2',
        '#D2B48C',
        '#3CB371',
        '#AFEEEE',
    );
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'folderId';
    
    
    /**
     * Описание на полетата на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование,mandatory');
        $this->FLD('description', 'richtext(rows=3, passage=Общи,bucket=Notes)', 'caption=Описание');
        $this->FLD('contragentFolderId', 'key2(mvc=doc_Folders,select=title,allowEmpty,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент');
        $this->FLD('receiveEmail', 'enum(yes=Да, no=Не)', 'caption=Получаване на имейли->Избор');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        if($data->action == 'clone'){
            $form->FNC('newStartDate', 'datetime', 'mandatory,caption=Клониране на задачи->Ново начало,input,before=taskCloneState');
            $form->FNC('taskCloneState', 'enum(draft=Чернова,active=Активно)', 'mandatory,caption=Клониране на задачи->Състояние,input,before=receiveEmail,unit=след клониране');
        
            $form->setDefault('newStartDate', dt::now());
            
        }
    }
    
    
    /**
     * След клониране на записа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec  - клонирания запис
     * @param stdClass $nRec - новия запис
     */
    protected static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
        cal_Tasks::cloneFromFolder($rec->folderId, $nRec->folderId, $nRec->newStartDate, $nRec->taskCloneState);
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $cu = core_Users::getCurrent();
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent,autoFilter');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета на модела ще се появят
        $data->listFilter->showFields = 'search,selectedUsers';
        $data->listFilter->input('selectedUsers,search', 'silent');
        
        if (!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = '|' . $cu . '|';
        }
        
        if (!$data->listFilter->rec->search) {
            $data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #inCharge, '|%')");
            $data->query->orLikeKeylist('shared', $data->listFilter->rec->selectedUsers);
            $data->title = 'Проектите на |*<span class="green">' .
                    $data->listFilter->getFieldType('selectedUsers')->toVerbal($data->listFilter->rec->selectedUsers) . '</span>';
        } else {
            $data->title = 'Търсене на проект отговарящи на |*<span class="green">"' .
                    $data->listFilter->getFieldType('search')->toVerbal($data->listFilter->rec->search) . '"</span>';
        }
        
        $data->query->orderBy('#createdOn=DESC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function recToVerbal_($rec, &$fields = array())
    {
        $row = parent::recToVerbal_($rec, $fields);
        $row->folder = 'Папка';
        if(isset($rec->contragentFolderId)){
            $row->contragentFolderId = doc_Folders::recToVerbal($rec->contragentFolderId)->title;
        }
        
        return $row;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // от текущото Url, намираме папката
        $currUrl = getCurrentUrl();
        
        // правим заявка към таблицата на Задачите
        $queryTasks = cal_Tasks::getQuery();
        $queryTasks->where("#folderId = '{$data->rec->folderId}'");
        
        // ако можем да извлечем данни, то ще определим,
        // кой е най-подходящия тип на Гант-а и
        // ще сложим бутон за него
        $source = array();
        while ($recs = $queryTasks->fetch()) {
            $source[] = $recs;
        }
        
        if (!empty($source)) {
            // намираме типа на Ганта
            $ganttType = cal_Tasks::getGanttTimeType($source);
            
            $data->toolbar->addBtn(
                
                'Гант',
                
                array(
                    $mvc,
                    'Gant',
                    $data->rec->id,
                    'View' => $ganttType
                ),
                        'ef_icon = img/16/barchart-multicolor-16.png'
            
            );
        }
    }
    
    
    public function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата
        if ($data->form && $data->form->isSubmitted()) {
            
            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
        }
    }
    
    
    /**
     * Екшън изчертаване на Гант графика
     */
    public static function act_Gant()
    {
        $currUrl = getCurrentUrl();
        
        $data = self::fetch($currUrl['id']);
        
        //Очакваме потребителя да има права за спиране
        $tpl = getTplFromFile('doc/tpl/SingleLayoutUnsortedFolderGantt.shtml');
        
        //Права за работа с екшън-а
        requireRole('powerUser');
        
        $form = self::prepareFilter();
        $tpl->replace($form->renderHtml(), 'FILTER');
        
        // слагаме бутони на къстам тулбара
        $btns = ht::createBtn(
            'Редакция',
            array(
                $mvc,
                'edit',
                $data->id
            ),
            null,
            null,
                    'ef_icon = img/16/edit-icon.png'
        );
        $btns .= ht::createBtn(
            'Папка',
            array(
                'doc_Threads',
                'list',
                'folderId' => $data->folderId
            ),
            null,
            null,
                    'ef_icon = img/16/folder-y.png'
        );
        
        $btns .= ht::createBtn(
            
            'Корица',
            
            array(
                $mvc,
                'single',
                $data->id
            ),
            
            null,
            
            null,
                    'ef_icon = img/16/project-archive.png'
        
        );
        
        // иконата за пред името на проекта
        $icon = sbf('img/24/barchart-multicolor-24.png', '', '');
        
        // подготвените данни за Гант
        $res = self::prepareGantt($data);
        
        // изчертаваме Гант
        $chart = gantt_Adapter::render($res);
        
        // Заместваме в шаблона
        $tpl->replace($btns, 'SingleToolbarGantt');
        $tpl->replace('state-'.$data->state, 'STATE_CLASS_GANTT');
        $tpl->replace("<img alt='' src='{$icon}'>", 'SingleIconGantt');
        $tpl->replace($data->name, 'nameGantt');
        $tpl->append($listFilter, 'FILTER');
        $tpl->replace($chart, 'Gantt');
        
        
        // Редиректваме
        return static::renderWrapping($tpl);
    }
    
    
    /**
     * Подготвяме филтъра в Гант изгледа
     *
     */
    public static function prepareFilter()
    {
        $form = cls::get('core_Form');
        
        $form->FNC('order', 'enum(start=По начало,
					        	  end=По край,
					        	  alphabetic=Азбучно)', 'caption=Подредба,width=100%,input,silent,autoFilter');
        
        $form->view = 'horizontal';
        
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $recForm = $form->input('order', 'silent');
        
        if (!$form->rec->order) {
            $form->setDefault('order', 'start');
        }
        
        return $form;
    }
    
    
    /**
     * Подготовка на данните за Гант табличния изглед
     *
     * @param StdClass $folderData
     *
     * @return StdClass
     */
    public static function prepareGantt($folderData)
    {
        $idTaskDoc = core_Classes::getId('cal_Tasks');
        
        // заявка към базата на "контейнерите"
        $queryContainers = doc_Containers::getQuery();
        
        // заявка към базата на "нишките"
        $queryTasks = cal_Tasks::getQuery();
        
        // търсим всички дали в тази папка има задачи
        $queryContainers->where("#folderId = '{$folderData->folderId}' AND #docClass = '{$idTaskDoc}'");
        
        $resTask = array();
        while ($recContainers = $queryContainers->fetch()) {
            $queryTasks->where("#folderId = '{$folderData->folderId}' AND (#state = 'waiting' OR #state = 'active' OR #state = 'closed')");
            $recs = array();
            
            // заявка към таблицата на Задачите
            while ($recTask = $queryTasks->fetch()) {
                if ($recTask->timeStart) {
                    $timeStart = $recTask->timeStart;
                } else {
                    $timeStart = $recTask->expectationTimeStart;
                }
                
                if ($recTask->timeEnd) {
                    $timeEnd = $recTask->timeEnd;
                } else {
                    $timeEnd = $recTask->expectationTimeEnd;
                }
                
                if ($timeStart) {
                    // ако няма продължителност на задачата
                    if (!$recTask->timeDuration && !$timeEnd) {
                        // продължителността на задачата е края - началото
                        $timeDuration = 1800;
                    } elseif (!$recTask->timeDuration && $timeEnd) {
                        $timeDuration = dt::mysql2timestamp($timeEnd) - dt::mysql2timestamp($timeStart);
                    } else {
                        $timeDuration = $recTask->timeDuration;
                    }
                    
                    // Ако имаме права за достъп до сингъла
                    if (cal_Tasks::haveRightFor('single', $recTask)) {
                        // ще се сложи URL
                        $flagUrl = 'yes';
                    } else {
                        $flagUrl = false;
                    }
                    
                    
                    $resTask[] = array(
                        'taskId' => $recTask->id,
                        'rowId' => '',
                        'timeline' => array(
                            '0' => array(
                                'duration' => $timeDuration,
                                'startTime' => dt::mysql2timestamp($timeStart))),
                        
                        'color' => self::$colors[$recTask->id % 50],
                        'hint' => $recTask->title,
                        'url' => $flagUrl,
                        'progress' => $recTask->progress
                    );
                    
                    
                    $recs[$recTask->id] = $recTask;
                    $forTask = (object) array('recs' => $recs);
                    $i++;
                }
            }
        }
        
        $others = cal_Tasks::renderGanttTimeType($forTask);
        
        $params = $others->otherParams;
        $header = $others->headerInfo;
        
        $cntResTask = count($resTask);
        
        for ($i = 0; $i <= ($cntResTask); $i++) {
            // Проверка дали ще има URL
            if ($resTask[$i]['url'] == 'yes') {
                // Слагаме линк
                $resTask[$i]['url'] = toUrl(array('cal_Tasks', 'single', $resTask[$i]['taskId']));
            } else {
                // няма да има линк
                unset($resTask[$i]['url']);
            }
        }
        
        $resources = array();
        $attr = array();
        
        if (is_array($resTask)) {
            // намираме, какво е избрано във формата за филтриране
            $form = self::prepareFilter();
            
            // за всеки един от случаите правим сортировка намасива
            // понеже структурата не е оптимална
            // трябва да сортираме при всеки от случаите отделни 3 масива
            switch ($form->rec->order) {
                case 'start':
                    usort($resTask, function ($a, $b) {
                        for ($i = 0; $i < count($a['timeline']); $i++) {
                            
                            return ($a['timeline'][$i]['startTime'] < $b['timeline'][$i]['startTime']) ? -1 : 1;
                        }
                    });
                    
                    $i = 0;
                    foreach ($resTask as $id => $task) {
                        $rowArr = array();
                        $rowArr[$id] = $i;
                        
                        $resTask[$id]['rowId'] = $rowArr;
                        
                        $icon = cal_Tasks::getIcon($task['taskId']);
                        
                        $recTitle = cal_Tasks::fetchField($task['taskId'], 'title');
                        
                        $attr = array();
                        $attr['ef_icon'] = $icon;
                        $attr['title'] = $recTitle;
                        
                        $title = ht::createLink(
                            
                            str::limitLen($recTitle, 35),
                                array('cal_Tasks', 'single', $task['taskId']),
                                null,
                            
                            $attr
                        
                        );
                        
                        $resources[$id] = array('name' => $title->content, 'id' => $task['taskId']);
                        
                        $i++;
                    }
                    
                    break;
                case 'end':
                    
                    usort($resTask, function ($a, $b) {
                        for ($i = 0; $i < count($a['timeline']); $i++) {
                            $cmpA = $a['timeline'][$i]['startTime'] + $a['timeline'][$i]['duration'];
                            $cmpB = $b['timeline'][$i]['startTime'] + $b['timeline'][$i]['duration'];
                            
                            return ($cmpA < $cmpB) ? -1 : 1;
                        }
                    });
                        
                        $i = 0;
                        foreach ($resTask as $id => $task) {
                            $rowArr = array();
                            $rowArr[$id] = $i;
                            
                            $resTask[$id]['rowId'] = $rowArr;
                            
                            $icon = cal_Tasks::getIcon($task['taskId']);
                            $recTitle = cal_Tasks::fetchField($task['taskId'], 'title');
                            $attr = array();
                            $attr['ef_icon'] = $icon;
                            $attr['title'] = $recTitle;
                            
                            $title = ht::createLink(
                                
                                str::limitLen($recTitle, 25),
                                    array('cal_Tasks', 'single', $task['taskId']),
                                    null,
                                
                                $attr
                            
                            );
                            
                            $resources[$id] = array('name' => $title->content, 'id' => $task['taskId']);
                            
                            $i++;
                        }
                    break;
                case 'alphabetic':
                    
                    usort($resTask, function ($a, $b) {
                        
                        return strnatcmp(mb_strtolower($a['hint'], 'UTF-8'), mb_strtolower($b['hint'], 'UTF-8'));
                    });
                        
                        $i = 0;
                        foreach ($resTask as $id => $task) {
                            $rowArr = array();
                            $rowArr[$id] = $i;
                            
                            $resTask[$id]['rowId'] = $rowArr;
                            
                            $icon = cal_Tasks::getIcon($task['taskId']);
                            $recTitle = cal_Tasks::fetchField($task['taskId'], 'title');
                            $attr = array();
                            $attr['ef_icon'] = $icon;
                            $attr['title'] = $recTitle;
                            
                            $title = ht::createLink(
                                
                                str::limitLen($recTitle, 25),
                                    array('cal_Tasks', 'single', $task['taskId']),
                                    null,
                                
                                $attr
                            
                            );
                            
                            $resources[$id] = array('name' => $title->content, 'id' => $task['taskId']);
                            $i++;
                        }
                    break;
            }
        }
        
        // само в случайте когато имаме 'година'=>'месец'
        // искаме да добавим още един параметър, който
        // ще ни указва, кога е 1 ден от новия месец в милисекунди
        if ($params['mainHeaderCaption'] == 'година' && $params['subHeaderCaption'] = 'седмица') {
            
            // от първия пълен месец намерен от началото на ганта
            // до края на ганта
            // добавяме по един месец и търсим 1 ден в милисекунди
            for ($t = strtotime('+1 months', $params['startTime']); $t < $params['endTime']; $t = strtotime('+1 months', $t)) {
                $a = dt::mysql2timestamp(date('Y-m-01', $t));
                $params['monthDelimiter'][] = $a;
            }
        }
        
        // връщаме един обект от всички масиви
        return (object) array('tasksData' => $resTask, 'headerInfo' => $header, 'resources' => $resources, 'otherParams' => $params);
    }
    
    
    /**
     * Кои документи да се показват като бързи бутони в папката на корицата
     *
     * @param doc_UnsortedFolders $mvc
     * @param null|array $res
     * @param int $id - ид на корицата
     *
     * @return array $res - възможните класове
     */
    public function on_AfterGetDocButtonsInFolder($mvc, &$res, $id)
    {
        setIfNot($res, array());
        $rec = $mvc->fetchRec($id);
        
        if (empty($res)) {
            if (cal_Tasks::haveRightFor('add')) {
                $res[] = (object)array('class' => 'cal_Tasks');
            }
        }
        
        // Ако има клас с името на проекта, връщаме и него
        if ($defClassId = core_Classes::fetchField(array("#title = '[#1#]'", $rec->name), 'id')) {
            $res[] = (object)array('class' => $defClassId);
        } else {
            
            // Ако името на папката е зададено в defaultFolder
            
            $docSuggestionsArr = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'id');
            foreach ($docSuggestionsArr as $clsId) {
                if (!cls::load($clsId, true)) continue;
                
                $clsInst = cls::get($clsId);
                
                if (!$clsInst->defaultFolder) continue;
                
                if ($clsInst->defaultFolder != $rec->name) continue;
                
                if ($clsInst->haveRightFor('add')) {
                    $res[] = (object)array('class' => $clsId);
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $query = self::getQuery();
        $query->orderBy('modifiedOn', 'DESC');
        
        
        $viewAccess = true;
        if ($params['restrictViewAccess'] == 'yes') {
            $viewAccess = false;
        }
        
        $me = cls::get(get_called_class());
        $me->restrictAccess($query, null, $viewAccess);
        
        if (!$includeHiddens) {
            $query->where("#state != 'rejected' AND #state != 'closed'");
        }
        
        if ($params['where']) {
            $query->where($params['where']);
        }
        
        if (is_array($onlyIds)) {
            if (!count($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        }
        
        if ($threadId = $params['moveThread']) {
            $tRec = doc_Threads::fetch($threadId);
            expect($doc = doc_Containers::getDocument($tRec->firstContainerId));
            $doc->getInstance()->restrictQueryOnlyFolderForDocuments($query);
        }
        
        $titleFld = $params['titleFld'];
        $query->XPR('searchFieldXpr', 'text', "LOWER(CONCAT(' ', #{$titleFld}))");
        
        if ($q) {
            if ($q[0] == '"') {
                $strict = true;
            }
            
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            
            $q = mb_strtolower($q);
            
            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $query->where(array("#searchFieldXpr REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $query->show($titleFld);
        
        $res = array();
        
        while ($rec = $query->fetch()) {
            $res[$rec->id] = trim($rec->{$titleFld});
        }
        
        return $res;
    }
    
    
    /**
     * Подготовка на клиентските проекти
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareContragentUnsortedFolders($data)
    {
        if(empty($data->masterData->rec->folderId)) return;
        
        $data->recs = $data->rows = array();
        $query = self::getQuery();
        $query->where("#contragentFolderId = {$data->masterData->rec->folderId}");
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = doc_Folders::fetch($rec->folderId);
            $data->rows[$rec->id] = doc_Folders::recToVerbal($data->recs[$rec->id]);
            $data->rows[$rec->id]->created = $data->rows[$rec->id]->createdOn . " " . tr('от') . " " . $data->rows[$rec->id]->createdBy;
        }
    }
    
    
    /**
     * Рендиране на клиентските проекти
     * 
     * @param stdClass $data
     * @return void|ET $tpl
     */
    public function renderContragentUnsortedFolders($data)
    {
        if(!countr($data->recs)) return;
        
        $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Проекти') . "</legend>
                                <div class='groupList clearfix21'>
                                 [#PROJECT_TABLE#]
                            </div>
                         </fieldset>");
       
        $table = cls::get('core_TableView', array('mvc' => cls::get('doc_Folders')));
        $dTpl = $table->get($data->rows, 'title=Наименование,allThreadsCnt=Нишки,inCharge=Отговорник,created=Създаване');
        $dTpl->append("style='width:100%'", 'TABLE_ATTR');
        
        $tpl->append($dTpl, 'PROJECT_TABLE');
        
        return $tpl;
    }
    /**
     * Връща данните на получателя
     * return object
     *
     * $obj->company    - Името на компанията
     * $obj->companyId  - Id' то на компанията - key(mvc=crm_Companies)
     * $obj->country    - Името на държавата
     * $obj->countryId  - Id' то на
     * $obj->vatNo      - ДДС номер на компанията
     * $obj->uicId      - Национален номер на компанията
     * $obj->pCode      - код
     * $obj->place      -
     * $obj->email      - Имейл
     * $obj->tel        - Телефон
     * $obj->fax        - Факс
     * $obj->address    - Адрес
     *
     * $obj->name       - Име на физическо лице
     * $obj->personId   - ИД на лице - key(mvc=crm_Persons)
     * $obj->pTel       - Персонален телефон
     * $obj->pMobile    - Мобилен
     * $obj->pFax       - Персонален
     * $obj->pAddress   - Персонален адрес
     * $obj->pEmail     - Персонален имейл
     * 
     * @see doc_ContragentDataIntf
     */
    public static function getContragentData($id)
    {
        $contragentData = null;
        
        if ($id) {
            $rec = self::fetchRec($id);
            
            if ($rec->contragentFolderId) {
                $contragentData = doc_Folders::getContragentData($rec->contragentFolderId);
            }
        }
        
        return $contragentData;
    }
    
    
    /**
     * Връща пълния конкатениран адрес на контрагента
     *
     * @param int       $id            - ид на контрагент
     * @param bool      $translitarate - дали да се транслитерира адреса
     * @param bool|NULL $showCountry   - да се показвали винаги държавата или Не, NULL означава че автоматично ще се определи
     *
     * @return core_ET $tpl - адреса
     * 
     * @see doc_ContragentDataIntf
     */
    public function getFullAdress($id, $translitarate = false, $showCountry = null)
    {
        
        return null;
    }
    
    
    /**
     * Връща дали на контрагента се начислява ДДС
     * 
     * @see doc_ContragentDataIntf
     */
    public function shouldChargeVat($id)
    {
        
        return null;
    }
    
    
    /**
     * Форсира контрагент в дадена група
     *
     * @param int    $id         -ид на продукт
     * @param string $groupSysId - sysId на група
     * 
     * @see doc_ContragentDataIntf
     */
    public function forceGroup($id, $groupSysId)
    {
        
        return null;
    }
}
