<?php


/**
 * Клас 'cal_Tasks' - Документ - задача
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Tasks extends core_Master
{
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    public $defaultFolder = FALSE;


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, cal_Wrapper,doc_plg_SelectFolder, doc_plg_Prototype, doc_DocumentPlg, planning_plg_StateManager, plg_Printing, 
    				 doc_SharablePlg, bgerp_plg_Blank, plg_Search, change_Plugin, plg_Sorting, plg_Clone,doc_AssignPlg';
    
    
    /**
     * Какви детайли има този мастер
     */
    public $details = 'cal_TaskProgresses, cal_TaskConditions, cal_TaskDocuments';


    /**
     * Заглавие
     */
    public $title = "Задачи";


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Задача";


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, timeStart, timeEnd, timeDuration, progress, sharedUsers';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, description';


    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'title';


    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id';


    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'powerUser';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'powerUser';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';


    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'powerUser';


    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'powerUser, admin, ceo';

    
    /**
     * Кой може да възлага задачата
     */
    public $canAssign = 'powerUser';

    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task-normal.png';


    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'cal/tpl/SingleLayoutTasks.shtml';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = '*';


    /**
     * Абревиатура
     */
    public $abbr = "Tsk";


    /**
     * Групиране на документите
     */
    public $newBtnGroup = "1.3|Общи";

    
    /**
     * Изгледи
     */
    public static $view = array('WeekHour'  => 1,
    							'WeekHour4' => 2,
    							'WeekHour6' => 3,
    							'WeekDay'   => 4,
    							'Months'    => 5,
    							'YearWeek'  => 6,
    							'Years'     => 7,);

    
    /**
     * Поле за филтър по дата - начало
     */
    public $filterFieldDateFrom = 'timeStart';

    
    /**
     * Поле за филтър по дата - край
     */
    public $filterFieldDateTo = 'timeEnd';
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = TRUE;
    

    /**
     * Предефинирани подредби на листовия изглед
     */
    public $listOrderBy = array(
        'endStart' => array('Всички стари->нови', 'all=Всички стари->нови'),
        'startEnd' => array('Всички нови->стари', 'all=Всички нови->стари'),
        'onStart' => array('По началото', 'timeStart=По началото'),
        'onEnd' => array('По края', 'timeEnd=По края'),
        'noStartEnd' => array('Без начало и край', 'noStartEnd=Без начало и край'),
    );
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'timeStart,timeDuration,timeEnd,expectationTimeEnd, expectationTimeStart, expectationTimeDuration,timeClosed';
    
    
    /**
     * 
     */
    public $canPending = 'powerUser';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory,width=100%,changable,silent');
        $this->FLD('priority', 'enum(low=Нисък,
                                    normal=Нормален,
                                    high=Висок,
                                    critical=Критичен)',
            'caption=Приоритет,mandatory,maxRadio=4,columns=4,notNull,value=normal');
        if(Mode::is('screenMode', 'narrow')) {
            $this->setField('priority', "columns=2");
            $this->setFieldTypeParams('priority',"columns=2" );
        }
        $this->FLD('description', 'richtext(bucket=calTasks, passage=Общи)', 'caption=Описание,changable');

        // Споделяне
        $this->FLD('sharedUsers', 'userList', 'caption=Споделяне,changable');
        
        // Отговорноици
        $this->FLD('assign', 'user(role=powerUser,allowEmpty)', 'caption=Възложено на,changable,input=none');

        // Начало на задачата
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00, format=smartTime)',
            'caption=Времена->Начало, silent, changable, tdClass=leftColImportant');

        // Продължителност на задачата
        $this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност,changable');

        // Краен срок на задачата
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00, format=smartTime, defaultTime=23:59:59)', 'caption=Времена->Край,changable, tdClass=leftColImportant');

        // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');

        // Дали началото на задачата не е точно определено в рамките на деня?
        $this->FLD('allDay', 'enum(no,yes)', 'caption=Цял ден?,input=none');

        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'caption=Прогрес,input=none,notNull,value=0');

        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time', 'caption=Отработено време,input=none');

        // Очакван край на задачата
        $this->FLD('expectationTimeEnd', 'datetime(format=smartTime)', 'caption=Времена->Очакван край,input=none');

        // Очаквано начало на задачата
        $this->FLD('expectationTimeStart', 'datetime(format=smartTime)', 'caption=Времена->Очаквано начало,input=none');

        // Изчислен старт  на задачата
        $this->FLD('timeCalc', 'datetime(format=smartTime)', 'caption=Времена->Изчислен старт,input=none');

        // Точното време на активация на задачата
        $this->FLD('timeActivated', 'datetime(format=smartTime)', 'caption=Времена->Активирана на,input=none');

        // Точното време на затваряне
        $this->FLD('timeClosed', 'datetime(format=smartTime)', 'caption=Времена->Затворена на,input=none');
    }


    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->FNC('foreignId', 'key(mvc=doc_Containers)', 'silent, input=hidden');
        
        $cu = core_Users::getCurrent();
        $data->form->setDefault('priority', 'normal');
        
        if ($defUsers = Request::get('DefUsers')) {
            if (type_Keylist::isKeylist($defUsers) && $mvc->fields['sharedUsers']->type->toVerbal($defUsers)) {
                $data->form->setDefault('sharedUsers', $defUsers);
            }
        }
        
        if (Mode::is('screenMode', 'narrow')) {
            $data->form->fields[priority]->maxRadio = 2;
        }

        $rec = $data->form->rec;

        if ($rec->allDay == 'yes') {
            list($rec->timeStart,) = explode(' ', $rec->timeStart);
        }
    }


    /**
     * Подготвяне на вербалните стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $grey = new color_Object("#bbb");
        $blue = new color_Object("#2244cc");

        $progressPx = min(100, round(100 * $rec->progress));
        $progressRemainPx = 100 - $progressPx;
        $row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$blue}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";

        if ($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
            $remainingTime = dt::mysql2timestamp($rec->timeEnd) - time();
            $rec->remainingTime = self::roundTime($remainingTime);

            $typeTime = cls::get('type_Time');
            if ($rec->remainingTime > 0) {
                $row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
            } else {

                $row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';

            }
        }
        
        $bold = '';
        if($rec->progress) {
            $grey->setGradient($blue, $rec->progress);
    
            $lastTime = bgerp_Recently::getLastDocumentSee($rec);
            if($lastTime < $rec->modifiedOn) {
                $bold = 'font-weight:bold;';
            }
    
        }
        $row->progress = "<span style='color:{$grey};{$bold}'>{$row->progress}</span>";

        // Ако имаме само начална дата на задачата
        if ($rec->timeStart && !$rec->timeEnd) {
            // я парвим хипервръзка към календара- дневен изглед
            $row->timeStart = ht::createLink($row->timeStart, array('cal_Calendar', 'day', 'from' => $rec->timeStart, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
            // Ако имаме само крайна дата на задачата
        } elseif ($rec->timeEnd && !$rec->timeStart) {
            // я правим хипервръзка към календара - дневен изглед
            $row->timeEnd = ht::createLink($row->timeEnd, array('cal_Calendar', 'day', 'from' => $rec->timeEnd, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
            // Ако задачата е с начало и край едновременно
        } elseif ($rec->timeStart && $rec->timeEnd) {
            // и двете ги правим хипервръзка към календара - дневен изглед
            $row->timeStart = ht::createLink($row->timeStart, array('cal_Calendar', 'day', 'from' => $rec->timeStart, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
            $row->timeEnd = ht::createLink($row->timeEnd, array('cal_Calendar', 'day', 'from' => $rec->timeEnd, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
        }
    }


    /**
     * Показване на задачите в портала
     */
    static function renderPortal($userId = NULL)
    {

        if (empty($userId)) {
            $userId = core_Users::getCurrent();
        }

        // Създаваме обекта $data
        $data = new stdClass();

        // Създаваме заявката
        $data->query = self::getQuery();

        // Подготвяме полетата за показване
        $data->listFields = 'groupDate,title,progress';

        $now = dt::verbal2mysql();

        if (Mode::is('listTasks', 'by')) {
            $data->query->where("#createdBy = $userId");
        } else {
            $data->query->where("#sharedUsers LIKE '%|{$userId}|%'");
        }

        $data->query->where("#state = 'active'");

        // Време за подредба на записите в портала
        $data->query->orderBy("modifiedOn", "DESC");
        $data->query->orderBy("createdOn", "DESC");
        
        // Подготвяме навигацията по страници
        self::prepareListPager($data);

        // Подготвяме филтър формата
        self::prepareListFilter($data);

        // Подготвяме записите за таблицата
        self::prepareListRecs($data);

        if (is_array($data->recs)) {

            foreach ($data->recs as &$rec) {
                $rec->savedState = $rec->state;
                $rec->state = '';

            }
        }

        // Подготвяме редовете на таблицата
        self::prepareListRows($data);

        if (is_array($data->recs)) {
            foreach ($data->recs as $id => $rec) {
                $row = $data->rows[$id];
                if ($rec->savedState == 'waiting') {
                    $row->title = "<div class='state-pending-link'>{$row->title}</div>";
                }
            }
        }

        $tpl = new ET("
            [#PortalPagerTop#]
            [#PortalTable#]
        	[#PortalPagerBottom#]
          ");

        // Попълваме таблицата с редовете

        if ($data->listFilter && $data->pager->pagesCount > 1) {
            $formTpl = $data->listFilter->renderHtml();
            $formTpl->removeBlocks();
            $formTpl->removePlaces();
            $tpl->append($formTpl, 'ListFilter');
        }

        $tpl->append(self::renderListPager($data), 'PortalPagerTop');
        $tpl->append(self::renderListTable($data), 'PortalTable');
        $tpl->append(self::renderListPager($data), 'PortalPagerBottom');

        return $tpl;
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $cu = core_Users::getCurrent();
        $rec = $form->rec;

        $rec->allDay = (strlen($rec->timeStart) == 10) ? 'yes' : 'no';

        if ($form->isSubmitted()) {
            
            if ($form->cmd == 'active') {
                $sharedUsersArr = type_UserList::toArray($form->rec->sharedUsers);
            }
            
            if ($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
                $form->setError('timeEnd', 'Не може крайния срок да е преди началото на задачата');
            }
            
            if ($rec->timeStart && $rec->timeEnd && $rec->timeDuration) {
                $form->setError('timeEnd,timeStart,timeDuration', 'Не може задачата да има едновременно начало, продължителност и край. Попълнете само две от тях');
            }

            // при активиране на задачата
            if ($rec->state == 'active') {

                // проверява дали сме и задали начало и край
                // или сме и задали начало и продължителност
                if (($rec->timeStart && $rec->timeEnd) || ($rec->timeStart && $rec->timeDuration)) {
                    // ако имаме зададена продължителност
                    if ($rec->timeDuration) {

                        // то изчисляваме края на задачата
                        // като към началото добавяме продължителността
                        $taskEnd = dt::timestamp2Mysql(dt::mysql2timestamp($rec->timeStart) + $rec->timeDuration);
                    } else {
                        $taskEnd = $rec->timeEnd;
                    }

                    // правим заявка към базата
                    $query = self::getQuery();

                    // търсим всички задачи, които са шернати на текущия потребител
                    // и имат някаква стойност за начало и край
                    // или за начало и продължителност
                    $query->likeKeylist('sharedUsers', $rec->sharedUsers);

                    if ($rec->id) {
                        $query->where("#id != {$rec->id}");
                    }

                    $query->where("(#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$rec->timeStart}' AND #timeEnd >= '{$rec->timeStart}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL  AND #timeStart <= '{$rec->timeStart}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$rec->timeStart}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$taskEnd}' AND #timeEnd >= '{$taskEnd}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL AND #timeStart <= '{$taskEnd}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$taskEnd}')");


                    $query->where("#state = 'active'");

                    // за всяка една задача отговаряща на условията проверяваме
                    if ($recTask = $query->fetch()) {

                        $link = ht::createLink($recTask->title, array('cal_Tasks', 'single', $recTask->id, 'ret_url' => TRUE, ''), NULL, "ef_icon=img/16/task-normal.png");
                        // и изписваме предупреждение
                        $form->setWarning('timeStart, timeDuration, timeEnd', "|Засичане по време с |*{$link}");
                    }
                }
            }
        }
        
        if ($fId = Request::get('foreignId')) {
            $form->rec->foreignId = $fId;
        }
    }


    /**
     *
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if(cal_TaskProgresses::haveRightFor('add', (object)array('taskId' => $data->rec->id))){
        	$data->toolbar->addBtn('Прогрес', array('cal_TaskProgresses', 'add', 'taskId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/progressbar.png', 'title=Добавяне на прогрес към задачата');
        }
       
        if(cal_TaskConditions::haveRightFor('add', (object)array('baseId' => $data->rec->id))){
        	$data->toolbar->addBtn('Условие', array('cal_TaskConditions', 'add', 'baseId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/task-option.png, row=2', 'title=Добавяне на зависимост между задачите');
        }

        if($data->rec->timeEnd) {
            $taskEnd = $data->rec->timeEnd;
        } else {
            $taskEnd = dt::now();
        }
        
        if($data->rec->timeStart) {
            $taskStart = $data->rec->timeStart;
        } else {
            $taskStart = dt::now();
        }
        
        // ако имаме зададена продължителност
        if ($data->rec->timeDuration) {

            if(!$data->rec->timeEnd) {
                // то изчисляваме края на задачата
                // като към началото добавяме продължителността
                $taskEnd = dt::timestamp2Mysql(dt::mysql2timestamp($data->rec->timeStart) + $data->rec->timeDuration);
            } 
            
            if(!$data->rec->timeStart) {
                // то изчисляваме началото на задачата
                // като от края на задачата вадим продължителността
                $taskStart = dt::timestamp2Mysql(dt::mysql2timestamp($data->rec->timeEnd) - $data->rec->timeDuration);
            } 
        } 

        // ако имаме бутон "Активиране"
        if (isset($data->toolbar->buttons['Активиране'])) {

            // заявка към базата
            $query = self::getQuery();

            // при следните условия
            $query->likeKeylist('sharedUsers', $data->rec->sharedUsers);
            $query->where("(#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$taskStart}' AND #timeEnd >= '{$taskStart}')
                            OR
                           (#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$taskEnd}' AND #timeEnd >= '{$taskEnd}')");

            // и намерим такъв запис
            if ($query->fetch()) { 
                // променяме бутона "Активиране"
                $data->toolbar->buttons['Активиране']->warning = "По същото време има и други задачи с някои от същите споделени потребители";
            }
        }
        
        // Добавяме бутон за създаване на задача
        if (($data->rec->state != 'rejected') && cal_TaskDocuments::haveRightFor('add')) {
            $data->toolbar->addBtn('Документ', array(
                    'cal_TaskDocuments',
                    'add',
                    'taskId' => $data->rec->id,
                    'ret_url'=> TRUE
            ), 'ef_icon = img/16/doc_stand.png, title=Добавяне на документ към задачата, row=2, order=19.99');
        }
    }

    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ($rec->foreignId) {
            cal_TaskDocuments::add($rec->id, $rec->foreignId);
        }
        
        $mvc->updateTaskToCalendar($rec->id);
    }

    
    /**
     * След изтриване на запис
     */
    static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $id => $rec) {

            // изтриваме всички записи за тази задача в календара
            $mvc->updateTaskToCalendar($rec->id);
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId = NULL)
    {
        if ($action == 'postpone') {
            if ($rec->id) {
                if ($rec->state !== 'active' || (!$rec->timeStart)) {
                    $requiredRoles = 'no_one';
                }
            }
        }

        if ($action == 'edit') {
            if ($rec->id) {
                if (!cal_Tasks::haveRightFor('single', $rec)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'changestate') {
            if ($rec->id) {
                if (!$mvc->haveRightFor('single', $rec->id, $userId)) {
                    $requiredRoles = 'no_one';
               }
            }
        }
        
        // Ако няма потребители, да не може да се активира - ще се промени състоянието на заявка
        if ($action == 'activate' && $rec->id) {
            $sharedUsersArr = keylist::toArray($rec->sharedUsers);
            
            if ($rec->assign) {
                $sharedUsersArr[$rec->assign] = $rec->assign;
            }
            
            if (empty($sharedUsersArr)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     *
     * Функция, която се извиква преди активирането на документа
     *
     * @param unknown_type $mvc
     * @param unknown_type $rec
     */
    public static function on_BeforeActivation($mvc, $rec)
    {
        $now = dt::verbal2mysql();

        // изчисляваме очакваните времена
        self::calculateExpectationTime($rec);

        // проверяваме дали може да стане задачата в активно състояние
        $canActivate = self::canActivateTask($rec);
        
        if ($now >= $canActivate && $canActivate !== NULL) {

            $rec->timeCalc = $canActivate->calcTime;

            // ако не може, задачата ставачакаща
        } else {
            $rec->state = 'waiting';
        }

        if ($rec->id) {
            $mvc->updateTaskToCalendar($rec->id);
        }
    }


    /**
     * Игнорираме pager-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListPager($mvc, &$res, $data)
    {
        // Ако искаме да видим графиката на структурата
        // не ни е необходимо страницирване
        if (Request::get('Chart') == 'Gantt') {
            // Задаваме броя на елементите в страница
            $mvc->listItemsPerPage = 1000000;
        }

        if (Request::get('Ctr') == 'Portal') {
            // Задаваме броя на елементите в страница
            $portalArrange = core_Setup::get('PORTAL_ARRANGE');
            if($portalArrange == 'recentlyNotifyTaskCal') {
                $mvc->listItemsPerPage = 10;
            } else {
                $mvc->listItemsPerPage = 20;
            }
        }
    }


    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $cu = core_Users::getCurrent();

        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input=none');
        $data->listFilter->FNC('to', 'date', 'caption=До,input=none');
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent,autoFilter');
        $data->listFilter->FNC('Chart', 'varchar', 'caption=Таблица,input=hidden,silent,autoFilter');
        $data->listFilter->FNC('View', 'varchar', 'caption=Изглед,input=hidden,silent,autoFilter');
        $data->listFilter->FNC('stateTask', 'enum(all=Всички,active=Активни,draft=Чернови,waiting=Чакащи,actPend=Активни+Чакащи,closed=Приключени)', 'caption=Състояние,input,silent,autoFilter');
        
        $options = array();
        
        // Подготовка на полето за подредба
        foreach ($mvc->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $orderType = cls::get('type_Enum');

        $orderType->options = $options;

        $data->listFilter->FNC('order', $orderType, 'caption=Подредба,input,silent', array('removeAndRefreshForm' => "from|to|selectedUsers|Chart|View|stateTask"));

        $data->listFilter->view = 'vertical';
        $data->listFilter->title = 'Задачи';
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));

        // по подразбиране е текущия потребител
        if (!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = keylist::fromArray(arr::make(core_Users::getCurrent('id'), TRUE));
        }

        // задачи с всякакъв статус
        if (!$data->listFilter->rec->stateTask) {
            $data->listFilter->rec->stateTask = 'all';
        }

        // по критерий "Всички"
        if (!$data->listFilter->rec->order) {
            $data->listFilter->rec->order = 'all';
        }

        // филтъра по дата е -1/+1 месец от днещната дата
        $data->listFilter->setDefault('from', date('Y-m-01', strtotime("-1 months", dt::mysql2timestamp(dt::now()))));
        $data->listFilter->setDefault('to', date("Y-m-t", strtotime("+1 months", dt::mysql2timestamp(dt::now()))));

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        if ($data->action === "list") {

            $data->listFilter->showFields .= 'search,selectedUsers,order, stateTask';
        } else {
            $data->listFilter->showFields .= 'selectedUsers';
        }
        $data->listFilter->input('selectedUsers, Chart, View, stateTask, order', 'silent');

        // размяна на датите във филтъра
        $dateRange = array();

        if ($data->listFilter->rec->from) {
            $dateRange[0] = $data->listFilter->rec->from;
        }

        if ($data->listFilter->rec->to) {
            $dateRange[1] = $data->listFilter->rec->to;
        }

        if (count($dateRange) == 2) {
            sort($dateRange);
        }

        // сега
        $now = dt::now();
        // поле което прави подредба по очакваните времена
        $data->query->XPR('relativeDate', 'datetime', "if(#expectationTimeStart, #expectationTimeStart, '{$now}')");

        // възможност за подредба "най-нови->стари"
        if ($data->listFilter->rec->order == 'endStart') {
            $data->query->orderBy("#state, #priority=DESC, #relativeDate=ASC, #createdOn=DESC");
            // възможност за подредба "стари->най-нови"
        } else {
            $data->query->orderBy("#state, #priority=DESC, #relativeDate=DESC, #createdOn=DESC");
        }

        if ($data->action === 'list') {
            $chart = Request::get('Chart');

            // ако ще подреждаме по "начало" или "край" на задачата ще показваме и филтъра за дата
            if ($data->listFilter->rec->order == 'onStart' || $data->listFilter->rec->order == 'onEnd') {
                $data->listFilter->showFields = 'search,selectedUsers,order, from, to,stateTask';
                $data->listFilter->input('from, to', 'silent');
            }

            
            if(($data->listFilter->rec->selectedUsers != 'all_users') && (strpos($data->listFilter->rec->selectedUsers, '|-1|') === FALSE)) {
                $data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #createdBy, '|%')");
                $data->query->orLikeKeylist('sharedUsers', $data->listFilter->rec->selectedUsers);
            }
          

            if ($data->listFilter->rec->stateTask != 'all' && $data->listFilter->rec->stateTask != 'actPend') {
                $data->query->where(array("#state = '[#1#]'", $data->listFilter->rec->stateTask));
            } elseif ($data->listFilter->rec->stateTask == 'actPend') {
                $data->query->where("#state = 'active' OR #state = 'waiting'");
            } else {
                $data->query->fetchAll();
            }

            if ($data->listFilter->rec->order == 'onStart') {

                $data->query->where("(#timeStart IS NOT NULL AND #timeStart <= '{$dateRange[1]}' AND #timeStart >= '{$dateRange[0]}')");
                $data->query->orderBy("#timeStart=ASC,#state=DESC");
            }

            if ($data->listFilter->rec->order == 'noStartEnd') {

                $data->query->where("(#timeStart IS NULL AND #timeDuration IS NULL AND #timeEnd IS NULL)");
            }

            if ($data->listFilter->rec->order == 'onEnd') {
                $data->query->where("(#timeEnd IS NOT NULL AND #timeEnd <= '{$dateRange[1]}' AND #timeEnd >= '{$dateRange[0]}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL  AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) <= '{$dateRange[1]}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$dateRange[0]}')
	        		              ");
                $data->query->orderBy("#state=DESC,#timeEnd=ASC");
            }

            if ($data->listFilter->rec->order == 'onStart') {
                $data->title = 'Търсене на задачи по начало на задачата в периода |*<span class="green">"' .
                    $data->listFilter->getFieldType('from')->toVerbal($data->listFilter->rec->from) . ' -
    			' . $data->listFilter->getFieldType('to')->toVerbal($data->listFilter->rec->to) . '"</span>';
            } elseif ($data->listFilter->rec->order == 'onEnd') {
                $data->title = 'Търсене на задачи по края на задачата в периода |*<span class="green">"' .
                    $data->listFilter->getFieldType('from')->toVerbal($data->listFilter->rec->from) . ' -
    			' . $data->listFilter->getFieldType('to')->toVerbal($data->listFilter->rec->to) . '"</span>';
            } elseif ($data->listFilter->rec->order == 'noStartEnd') {
                $data->title = 'Търсене на задачи |*<span class="green">"' .
                    'без начало и край"</span>';
            } elseif ($data->listFilter->rec->search) {
                $data->title = 'Търсене на задачи отговарящи на |*<span class="green">"' .
                    $data->listFilter->getFieldType('search')->toVerbal($data->listFilter->rec->search) . '"</span>';
            } else {
                //$data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #sharedUsers, '|%')");
                $data->title = 'Задачите на |*<span class="green">' .
                    $data->listFilter->getFieldType('selectedUsers')->toVerbal($data->listFilter->rec->selectedUsers) . '</span>';
            }

            if ($chart == 'Gantt') {
                $data->query->where("(#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$dateRange[1]}' AND #timeEnd >= '{$dateRange[0]}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL  AND #timeStart <= '{$dateRange[1]}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$dateRange[0]}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeStart <= '{$dateRange[1]}' AND  #timeStart >= '{$dateRange[0]}')
	        		              ");
            }
        }
    }


    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $currUrl = getCurrentUrl();
        $needOneOnly = 0;

        if ($currUrl['Ctr'] == "cal_Tasks") {
            $chartType = Request::get('Chart');

            $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

            $currUrl['Act'] = 'list';
            $currUrl['Chart'] = 'List';
            $tabs->TAB('List', 'Таблица', $currUrl);

            $queryClone = clone $data->listSummary->query;

            $queryClone->where("#timeStart IS NOT NULL");

            if ($queryClone->fetch()) {

                // ще може ли да определим типа на Ганта
                $ganttType = self::getGanttTimeType($data);

                // и ще имаме активен бутон за него
                $currUrl['Act'] = 'list';
                $currUrl['Chart'] = 'Gantt';
                $currUrl['View'] = $ganttType;
                $tabs->TAB('Gantt', 'Гант', $currUrl);

                if ($chartType == 'Gantt') {
                    // и ще го изчертаем
                    $tpl = static::getGantt($data);

                }
                // в противен слувачай бутона ще е неактивен
            } else {
                $tabs->TAB('Gantt', 'Гант', '');
            }

            $tpl = $tabs->renderHtml($tpl, $chartType);

            $mvc->currentTab = 'Задачи';
        }
    }

    
    /**
     * Прихваща извикването на AfterInputChanges в change_Plugin
     * 
     * @param core_MVc $mvc
     * @param object $oldRec - Стария запис
     * @param object $newRec - Новия запис
     */
    function on_AfterInputChanges($mvc, $oldRec, $newRec)
    {
    	// Ако не е обект, а е подаден id
        if (!is_object($newRec)) {
            
            // Опитваме се да извлечем данните
            $newRec = cal_Tasks::fetch($newRec);
        }
        
        // Очакваме да има такъв запис
        expect($newRec, 'Няма такъв запис');
        
    	if ($newRec->notifySent === 'yes') {
    		$newRec->notifySent = 'no';
    	}
    }
    
    
    /**
     * Обновява информацията за задачата в календара
     */
    static function updateTaskToCalendar($id)
    {
        $rec = static::fetch($id);
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);

        // Начална дата
        $fromDate = "{$cYear}-01-01";

        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на клучовете за записите в календара от тази задача
        $prefix = "TSK-{$id}";
        
        // Подготвяме запис за началната дата
        if($rec->timeStart && $rec->timeStart >= $fromDate && $rec->timeStart <= $toDate && ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'draft'|| $rec->state == 'waiting') ||
           $rec->timeCalc && $rec->timeCalc >= $fromDate && $rec->timeCalc <= $toDate && ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'draft'|| $rec->state == 'waiting') ||
           $rec->expectationTimeStart && $rec->expectationTimeStart >= $fromDate && $rec->expectationTimeStart <= $toDate && ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'draft'|| $rec->state == 'waiting')) {
            
            $calRec = new stdClass();
                
            // Ключ на събитието
            $calRec->key = $prefix . '-Start';
            
            if ($rec->timeStart) {
	            // Начало на задачата
	            $calRec->time = $rec->timeStart;
            } else {
            	$calRec->time = $rec->timeCalc;
            }
            
            // Дали е цял ден?
            $calRec->allDay = $rec->allDay;
            
            // Икона на записа
            $calRec->type  = 'task';

            // Заглавие за записа в календара
            $calRec->title = "{$rec->title}";

            // В чии календари да влезе?
            $calRec->users = $rec->sharedUsers;
            
            // Статус на задачата
            $calRec->state = $rec->state;

            // Какъв да е приоритета в числово изражение
            $calRec->priority = self::getNumbPriority($rec);

            // Url на задачата
            $calRec->url = array('cal_Tasks', 'Single', $id); 
            
            $events[] = $calRec;
        }
        
        // Подготвяме запис за Крайния срок
        if($rec->timeEnd && $rec->timeEnd >= $fromDate && $rec->timeEnd <= $toDate && ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'waiting') ) {
            
            $calRec = new stdClass();
                
            // Ключ на събитието
            $calRec->key = $prefix . '-End';
            
            // Начало на задачата
            $calRec->time = $rec->timeEnd;
            
            // Дали е цял ден?
            $calRec->allDay = $rec->allDay;
            
            // Икона на записа
            $calRec->type  = 'end-date';

            // Заглавие за записа в календара
            $calRec->title = "Краен срок за \"{$rec->title}\"";

            // В чии календари да влезе?
            $calRec->users = $rec->sharedUsers;
            
            // Статус на задачата
            $calRec->state = $rec->state;
            
            // Какъв да е приоритета в числово изражение
            $calRec->priority = self::getNumbPriority($rec) - 1;

            // Url на задачата
            $calRec->url = array('cal_Tasks', 'Single', $id); 
            
            $events[] = $calRec;
        }
  
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }


    /**
     * Връща приоритета на задачата за отразяване в календара
     */
    static function getNumbPriority($rec)
    {
        if($rec->state == 'active' || $rec->state == 'waiting') {

            switch($rec->priority) {
                case 'low':
                    $res = 100;
                    break;
                case 'normal':
                    $res = 200;
                    break;
                case 'high':
                    $res = 300;
                    break;
                case 'critical':
                    $res = 400;
                    break;
            }
        } else {

            $res = 0;
        }

        return $res;
    }



    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
        
        $usersArr = array();
        if ($rec->assign) {
            $usersArr[$rec->assign] = $rec->assign;
        }
        if ($rec->sharedUsers) {
            $usersArr += type_Keylist::toArray($rec->sharedUsers);
        }
        if (!empty($usersArr)) {
            $Users = cls::get('type_userList');
            // В заглавието добавяме потребителя
            $row->subTitle = $Users->toVerbal(type_userList::fromArray($usersArr));
        }
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Връща иконата на документа
     */
    function getIcon_($id)
    {
        $rec = self::fetch($id);

        $icon =  "img/16/task-" . $rec->priority . ".png";

        if(log_Browsers::isRetina()) {
            $tempIcon = "img/32/task-" . $rec->priority . ".png";
            if(getFullPath($tempIcon)) {
                $icon =  $tempIcon;
            }
        }

        return $icon;
    }

    
    /**
     * Изпращане на нотификации за започването на задачите
     */
    function cron_SendNotifications()
    {
       // Обикаляме по всички чакащи задачи 
       $query = $this->getQuery();
       $query->where("#state = 'waiting'");
       
	   $activatedTasks = array ();
	   $now = dt::verbal2mysql();
       
	   while ($rec = $query->fetch()) { 

   	   	   // изчисляваме очакваните времена
		   self::calculateExpectationTime($rec);
		   // обновяваме в календара
		   self::updateTaskToCalendar($rec->id);
		   // и проверяваме дали може да я активираме
		   $canActivate = self::canActivateTask($rec);
           
		   if ($canActivate != FALSE) { 
		   	   if ($now >= $canActivate) {  
				   $rec->state = 'active';
				   $rec->timeActivated = $now;

				   $activatedTasks[] = $rec;
				       
				   // и да изпратим нотификация на потребителите
       			   self::doNotificationForActiveTasks($activatedTasks);
		       }       
		   }
		   
		   self::save($rec, 'state, timeActivated, expectationTimeEnd, expectationTimeStart');
	   }    
    }


    /**
     * Сменя задачите в сесията между 'поставените към', на 'поставените от' и обратно
     */
    function act_SwitchByTo()
    {
        if (Mode::is('listTasks', 'by')) {
            Mode::setPermanent('listTasks', 'to');
        } else {
            Mode::setPermanent('listTasks', 'by');
        }

        return new Redirect(array('Portal', 'Show', '#' => Mode::is('screenMode', 'narrow') ? 'taskPortal' : NULL));
    }


    /**
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = "StartTasks";
        $rec->description = "Известяване за стартирани задачи";
        $rec->controller = "cal_Tasks";
        $rec->action = "SendNotifications";
        $rec->period = 1;
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);
        
        // Създаваме, кофа, където ще държим всички прикачени файлове в задачи
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('calTasks', 'Прикачени файлове в задачи', NULL, '104857600', 'user', 'user');
    }
    
   
    /**
     * Изчертаване на структурата с данни от базата
     */
    static function getGantt ($data)
    {
        // масив с цветове
    	$colors = array( "#610b7d", 
				    	"#1b7d23",
				    	"#4a4e7d",
				    	"#7d6e23", 
				    	"#33757d",
				    	"#211b7d", 
				    	"#72142d",
				    	"#EE82EE",
				    	"#0080d0",
				    	"#FF1493",
				    	"#C71585",
				    	"#0d777d",
				    	"#4B0082",
				    	"#7d1c24",
				    	"#483D8B",
				    	"#7b237d", 
				    	"#8B008B",
	    				"#FFC0CB",
	    				"#cc0000",
	    				"#00cc00",
	    				"#0000cc",
	    				"#cc00cc",
		    			"#3366CC",
		    			"#FF9999",
		    			"#FF3300",
		    			"#9999FF",
		    			"#330033",
		    			"#003300",
		    			"#0000FF",
		    			"#FFFF33",
		    			"#66CDAA",
		    			"#98FB98",
		    			"#4169E1",
		    			"#D2B48C",
		    			"#9ACD32",
		    			"#00FF7F",
		    			"#4169E1",
		    			"#EEE8AA",
		    			"#9370DB",
		    			"#3CB371",
		    			"#FFB6C1",
		    			"#DAA520",
		    			"#483D8B",
		    			"#8B0000",
		    			"#00FFFF",
		    			"#DC143C",
		    			"#8A2BE2",
		    			"#D2B48C",
		    			"#3CB371",
		    			"#AFEEEE",
    	                );
        if($data->recs){
    	    // за всеки едиин запис от базата данни
        	foreach($data->recs as $v=>$rec){ 
        		if($rec->timeStart){
        			// ако няма продължителност на задачата
    	    		if(!$rec->timeDuration && !$rec->timeEnd) {
    	    			// продължителността на задачата е края - началото
    	    			$timeDuration = 1800;
    	    		} elseif(!$rec->timeDuration && $rec->timeEnd ) {
    	    			$timeDuration = dt::mysql2timestamp($rec->timeEnd) - dt::mysql2timestamp($rec->timeStart);
    	    		} else {
    	    			$timeDuration = $rec->timeDuration;
    	    		}
    	    		
	        		// ако нямаме край на задачата
		    		if(!$rec->timeEnd){
		    			// изчисляваме края, като начало + продължителност
		    			$timeEnd = dt::timestamp2Mysql(dt::mysql2timestamp($rec->timeStart) + $timeDuration);
		    		} else {
		    			$timeEnd = $rec->timeEnd;
		    		}
    	    	            
    	    		// масив с шернатите потребители
    	    		$sharedUsers[$rec->sharedUsers] = keylist::toArray($rec->sharedUsers);
    	    		
    	    		// Ако имаме права за достъп до сингъла
    	    		if (cal_Tasks::haveRightFor('single', $rec)) {
    	    			// ще се сложи URL
		           		$flagUrl = 'yes';
		            } else {
		            	$flagUrl = FALSE;
		            }
    	    		
		            	// масива със задачите
    		    		$resTask[]=array( 
    			    					'taskId' => $rec->id,
    			    					'rowId' =>  keylist::toArray($rec->sharedUsers),
    		    						'timeline' => array (
    		    											'0' => array(
    		                								'duration' => $timeDuration,  
    		                								'startTime'=> dt::mysql2timestamp($rec->timeStart))),
    		    		                
    			    					'color' => $colors[$v % 50],
    			    					'hint' => $rec->title,
    		    						'url' =>  $flagUrl,
    			    					'progress' => $rec->progress
    		    		);
        		}
        	} 
        	
        	if (is_array($sharedUsers)) {
	        	// правим масив с ресурсите или в нашия случай това са потребителитя
	        	foreach($sharedUsers as $key=>$users){
	        		if(count($users) >=2 ) {
	        			unset ($sharedUsers[$key]);
	        		}
	        		
	        		// има 2 полета ид = номера на потребителя
	        		// и линк към профила му
	        		foreach($users as $id => $resors){
	                    $link = crm_Profiles::createLink($resors);
	    	    		$resorses[$id]['name'] = (string) crm_Profiles::createLink($resors);
	    	    		$resorses[$id]['id'] = $resors;
	        		}
	        	}
        	}
        	
        	if(is_array($resorses)) {
	        	// номерирваме ги да почват от 0
	        	foreach($resorses as $res) {
	        		$resUser[] = $res;
	        	}
        	}
        	
        	$cntResTask = count($resTask);
        	
        	// правим помощен масив = на "rowId" от "resTasks"
        	for($i = 0; $i < $cntResTask; $i++) { $j = 0;
        		$rowArr[] = $resTask[$i]['rowId'];
        		
        		// Проверка дали ще има URL
        		if ($resTask[$i]['url'] == 'yes') {
        			// Слагаме линк
        			$resTask[$i]['url'] = toUrl(array('cal_Tasks', 'single' , $resTask[$i]['taskId']));
        		} else {
        			// няма да има линк
        			unset ($resTask[$i]['url']);
        		}
        	}
        	
        	if (is_array($rowArr)) {
	        	// за всяко едно ид от $rowArr търсим отговарящия му ключ от $resUser
	        	foreach($rowArr as $k => $v){
	        		
	        		foreach($v as $a=>$t){
	        			foreach($resUser as $key=>$value){
	        				if($t == $value['id']) {
	        					$resTask[$k]['rowId'][$a] = $key; 
	        				}
	        			}
	        		}
	        	}
        	}
        }
    	
	    // други параметри
	    $others = self::renderGanttTimeType($data);

	    $params = $others->otherParams;
	    $header = $others->headerInfo;

	    // връщаме един обект от всички масиви
	    $res = (object) array('tasksData' => $resTask, 'headerInfo' => $header , 'resources' => $resUser, 'otherParams' => $params);

	    $chart = gantt_Adapter::render($res);
	
	    return $chart;
    	
    }
    
    
    /**
     * Определяне на системното имен на гантовете
     * @param stdClass $data
     */
    static function getGanttTimeType($data)
    {
    	$dateTasks = self::calcTasksMinStartMaxEndTime($data);
    	
    	// Масив [0] - датата
    	//       [1] - часа
    	$startTasksTime = dt::timestamp2Mysql($dateTasks->minStartTaskTime);
    	$endTasksTime = dt::timestamp2Mysql($dateTasks->maxEndTaskTime);
    	
    	// ако периода на таблицата е в рамките на една една седмица
   		if (dt::daysBetween($endTasksTime,$startTasksTime) < 3) {
    		
    		$type = 'WeekHour';
    		
    	  // ако периода на таблицата е в рамките на седмица - месец
    	}elseif (dt::daysBetween($endTasksTime,$startTasksTime) >= 3  && dt::daysBetween($endTasksTime,$startTasksTime) < 5) {
    		
    		$type = 'WeekHour4';
    		
    	  // ако периода на таблицата е в рамките на седмица - месец
    	}elseif (dt::daysBetween($endTasksTime,$startTasksTime) >= 5  && dt::daysBetween($endTasksTime,$startTasksTime) < 7) {
    		
    		$type = 'WeekHour6';
    		
    	  // ако периода на таблицата е в рамките на седмица - месец
    	} elseif (dt::daysBetween($endTasksTime,$startTasksTime) >= 7  && dt::daysBetween($endTasksTime,$startTasksTime) < 28) {
       		
    		$type = 'WeekDay';
    		
    	  // ако периода на таблицата е в рамките на месец - 3 месеца	
    	} elseif (dt::daysBetween($endTasksTime,$startTasksTime) >= 28 && dt::daysBetween($endTasksTime,$startTasksTime) < 84) {
    		
    		$type = 'Months';
    		
    	  // ако периода на таблицата е в рамките на година - седмици	
    	} elseif (dt::daysBetween($endTasksTime,$startTasksTime) >= 84 && dt::daysBetween($endTasksTime,$startTasksTime) < 168) { 
    	    
    		$type = 'YearWeek';
    		
    	  // ако периода на таблицата е по-голям от година
    	} elseif (dt::daysBetween($endTasksTime,$startTasksTime) >= 168) {
    		
    		$type = 'Years';
    	}
    	
    	return  $type;
    }
    
    
    /**
     * Прави линкове към по-голям и по-маък тип гант
     * @param varchar $ganttType
     */
    static public function getNextGanttType ($ganttType)
    {
    
    	$currUrl = getCurrentUrl();

    	// текущия ни гант тайп
        $ganttType = Request::get('View');
        
        // намираме го в масива
    	$curIndex = self::$view[$ganttType];
        
    	// следващия ще е с индекс текущия +1
    	$next = $curIndex + 1;
        
        if ($next <= count (self::$view)) {
       		$nextType = array_search($next, self::$view);
	        $currUrl['View'] = $nextType;
	       
       		$nextUrl = $currUrl;
        }
        
        // предишния ще е с индекс текущия - 1
        $prev = $curIndex - 1;
    	
        if ($prev >= 1) {
       		$prevType = array_search($prev, self::$view);
	        $currUrl['View'] = $prevType;
       		$prevUrl = $currUrl;
        }
        
        // връщаме 2-те URL-та
        return (object) array('prevUrl' => $prevUrl, 'nextUrl' =>$nextUrl);
    }
    
    
    /**
     * Изчисляване на необходимите параметри за изчертаването на ганта
     * @param stdClass $data
     */
    static function renderGanttTimeType($data)
    {
        // Сетваме времевата зона
        date_default_timezone_set('UTC');
        
    	$ganttType = Request::get('View');
    	
    	$url = self::getNextGanttType($ganttType);
    	
    	$dateTasks = self::calcTasksMinStartMaxEndTime($data);
    	
    	// Масив [0] - датата
    	//       [1] - часа
    	$startTasksTime = explode(" ", dt::timestamp2Mysql($dateTasks->minStartTaskTime));
    	$endTasksTime = explode(" ", dt::timestamp2Mysql($dateTasks->maxEndTaskTime));
    
    	// Масив [0] - година
    	//       [1] - месец
    	//       [2] - ден
    	$startExplode =  explode("-", $startTasksTime[0]);
    	$endExplode = explode("-", $endTasksTime[0]);

    	// иконите на стрелките
    	$iconPlus = sbf("img/16/gantt-arr-down.png",'');
    	$iconMinus = sbf("img/16/gantt-arr-up.png",'');
    	
    	$imgPlus = ht::createElement('img', array('src' => $iconPlus));
        $imgMinus = ht::createElement('img', array('src' => $iconMinus));
        
    	switch ($ganttType) {
    		
    	// ако периода на таблицата е по-голям от година
    		case 'Years': 
    		    
    		    date_default_timezone_set('UTC');
    		    
	    		// делението е година/месец
	    		$otherParams['mainHeaderCaption'] = tr('година');
	    		$otherParams['subHeaderCaption'] = tr('месеци');
	    		
	    		// таблицата започва от първия ден на стартовия месец
	    		$otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], 1, $startExplode[0]);
	    		// до последния ден на намерения месец за край
	    		$otherParams['endTime'] = dt::mysql2timestamp(dt::getLastDayOfMonth($endTasksTime[0]). " 23:59:59");

	    		// урл-тата на стрелките
	    		$otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
	    		$otherParams['biggerPeriod'] = " ";
	    		
	    		// кое време е сега?
	    		$otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
	
	    		$curDate = dt::timestamp2mysql(mktime(0, 0, 0, $startExplode[1], 1, $startExplode[0])); 
	    		$toDate = dt::getLastDayOfMonth($endTasksTime[0]). " 23:59:59"; 
	    		
	    		// генерираме номерата на седмиците между началото и края
	    		while ($curDate < $toDate){
	    		   
	    			$w = date("Y", dt::mysql2timestamp($curDate));
	    		 	$res[$w]['mainHeader'] = $w;
	    		 	$res[$w]['subHeader'][] = dt::getMonth(date("m", dt::mysql2timestamp($curDate)), $format = 'M');
	    		 	$curDate = dt::addMonths(1, $curDate);
	    		 	
	    		}
	    		
	    		foreach ($res as $headerArr) {
	    			$headerInfo[] = $headerArr;
	    		}
    		
    		break;
    		
    		// ако периода на таблицата е в рамките на една една седмица
    		case 'WeekHour4' :
    		    
    		    date_default_timezone_set('UTC');
    		    
	    		// делението е ден/час
	    		$otherParams['mainHeaderCaption'] = tr('ден');
	    		$otherParams['subHeaderCaption'] = tr('часове');
	    		
	    		// таблицата започва от 00ч на намерения за начало ден
	    		$otherParams['startTime'] = dt::mysql2timestamp($startTasksTime[0]);
	    		
	    		// до 23:59:59ч на намерения за край ден
	    		$otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);

	    		//урл-тата на стрелките
	    		$otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
	    		$otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
	    		
	    		// кое време е сега?
	    		$otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
	    		
	    		for($i = 0; $i <= dt::daysBetween($endTasksTime[0],$startTasksTime[0]); $i++) {
	    			$color = cal_Calendar::getColorOfDay(dt::addDays($i, $startTasksTime[0]));
	    			
	    			if(isset($color)){
			    		// оформяме заглавните части като показваме всеки един ден 
		    			$headerInfo[$i]['mainHeader'] = "<span class = '{$color}'>" . date("d.m. ", dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0]))) . "</span>";
	    			} else {
	    			    $headerInfo[$i]['mainHeader'] =  date("d.m. ", dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0])));
	    			}
		    		for ($j = 0; $j <=23; $j = $j +4) {
		    			// започваме да чертаем от 00ч на намерения за начало ден, до 23ч на намерения за край ден
		    			$headerInfo[$i]['subHeader'][$j] = date("H", mktime($j, $j, 0, $startExplode[1], $i, $endExplode[0])) . ":00";
		    		}
	    		}
    		
    		break;
    		
    		// ако периода на таблицата е в рамките на една една седмица
    		case 'WeekHour6' :
    		
    		    date_default_timezone_set('UTC');
    		    
	    		// делението е ден/час
	    		$otherParams['mainHeaderCaption'] = tr('ден');
	    		$otherParams['subHeaderCaption'] = tr('часове');
	    		
	    		// таблицата започва от 00ч на намерения за начало ден
	    		$otherParams['startTime'] = dt::mysql2timestamp($startTasksTime[0]);
	    		
	    		// до 23:59:59ч на намерения за край ден
	    		$otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);

	    		//урл-тата на стрелките
	    		$otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
	    		$otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
	    		
	    		// кое време е сега?
	    		$otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
	    		
	    		for($i = 0; $i <= dt::daysBetween($endTasksTime[0],$startTasksTime[0]); $i++) {
	    			$color = cal_Calendar::getColorOfDay(dt::addDays($i, $startTasksTime[0]));
	    			
	    			if(isset($color)){
			    		// оформяме заглавните части като показваме всеки един ден 
		    			$headerInfo[$i]['mainHeader'] = "<span class = '{$color}'>" . date("d.m. ", dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0]))) . "</span>";
	    			} else {
	    				$headerInfo[$i]['mainHeader'] = date("d.m. ", dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0])));
	    			}
		    		for ($j = 0; $j <=23; $j = $j + 6) {
		    			// започваме да чертаем от 00ч на намерения за начало ден, до 23ч на намерения за край ден
		    			$headerInfo[$i]['subHeader'][$j] = date("H", mktime($j, $j, 0, $startExplode[1], $i, $endExplode[0])) . ":00";
		    		}
	    		}
    		
    		break;
    		
    		// ако периода на таблицата е в рамките на една една седмица
    		case 'WeekHour' :
    		    
    		    date_default_timezone_set('UTC');
    		    
	    		// делението е ден/час
	    		$otherParams['mainHeaderCaption'] = tr('ден');
	    		$otherParams['subHeaderCaption'] = tr('часове');
	    		
	    		// таблицата започва от 00ч на намерения за начало ден
	    		$otherParams['startTime'] = dt::mysql2timestamp($startTasksTime[0]);
	    		
	    		// до 23:59:59ч на намерения за край ден
	    		$otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);

	    		//урл-тата на стрелките
	    		$otherParams['smallerPeriod'] = " ";
	    		$otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
	    		
	    		// кое време е сега?
	    		$otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
	    		
	    		for($i = 0; $i <= dt::daysBetween($endTasksTime[0],$startTasksTime[0]); $i++) {
	    			$color = cal_Calendar::getColorOfDay(dt::addDays($i, $startTasksTime[0]));
	    			
	    			if(isset($color)){
			    		// оформяме заглавните части като показваме всеки един ден 
		    			$headerInfo[$i]['mainHeader'] = "<span class = '{$color}'>" . date("d.m. ", dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0]))) . "</span>";
	    			} else {
	    				$headerInfo[$i]['mainHeader'] = date("d.m. ", dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0])));
	    			}
		    		for ($j = 0; $j <=23; $j++) {
		    			// започваме да чертаем от 00ч на намерения за начало ден, до 23ч на намерения за край ден
		    			$headerInfo[$i]['subHeader'][$j] = date("H", mktime($j, $j, 0, $startExplode[1], $i, $endExplode[0])) . ":00";
		    		}
	    		}
    		
    		break;
   		
    		// ако периода на таблицата е в рамките на седмица - месец
    		case 'WeekDay' :
    		    
    		    date_default_timezone_set('UTC');
    		    
	    		// делението е седмица/ден
	    		$otherParams['mainHeaderCaption'] = tr('седмица');
	    		$otherParams['subHeaderCaption'] = tr('ден');
	    		
	    		// от началото на намерения стартов ден
	    		$otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]);
	    		// до края на намерения за край ден
	    		$otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);
	    		
	    		// урл-тата на стрелките
	    		$otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
	    		$otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
	    		
	    		// кое време е сега?
	    		$otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
	    		
	    		$curDate = $startTasksTime[0]. " 00:00:00"; 
	    		$toDate = $endTasksTime[0]. " 23:59:59"; 
	
	    		// генерираме номерата на седмиците между началото и края
	    		while ($curDate < $toDate){
	    		    $color = cal_Calendar::getColorOfDay($curDate);
	    			$w = date("W", dt::mysql2timestamp($curDate));
	    		 	$res[$w]['mainHeader'] = $w;
	    		 	
	    		 	if(isset($color)){
	    		 		$res[$w]['subHeader'][] = "<span class = '{$color}'>" . date("d.m. ", dt::mysql2timestamp($curDate)) . "</span>";
	    		 	} else {
	    		 		$res[$w]['subHeader'][] = date("d.m. ", dt::mysql2timestamp($curDate));	
	    		 	}
	    		 	
	    		 	$curDate = dt::addDays(1, $curDate); 
	    		}
	    		
	    		foreach ($res as $headerArr) {
	    			$headerInfo[] = $headerArr;
	    		}
    		
            break;
            
    	   // ако периода на таблицата е в рамките на месец - ден
    		case 'Months' :
    		    
    		    date_default_timezone_set('UTC');
    		    
	    		// делението е месец/ден
	    		$otherParams['mainHeaderCaption'] = tr('месец');
	    		$otherParams['subHeaderCaption'] = tr('ден');
	    		
	    		// таблицата започва от 1 ден на намерения за начало месец
	    		$otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]);
	    		// до последния ден на намерения за край месец
	    		$otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2] + 3, $endExplode[0]);
	    		
	    		// урл-тата на стрелките
	    		$otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
	    		$otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
	    		
	    		// кое време е сега?
	    		$otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
	    		
	    		$curDate = $startTasksTime[0]. " 00:00:00"; 
	    		$toDate = dt::addDays(3, $endTasksTime[0]). " 23:59:59"; 
	            
	    		// генерираме номерата на седмиците между началото и края
	    		while ($curDate <= $toDate){
	    		    $color = cal_Calendar::getColorOfDay($curDate);
	    			$curDateExplode =  explode("-", $curDate);
	    			$w = dt::getMonth($curDateExplode[1], 'F') . " " . $curDateExplode[0];
	    		 	$res[$w]['mainHeader'] = $w;
	    		 	
	    		 	if(isset($color)){
	    		 		$res[$w]['subHeader'][] =	"<span class='{$color}'>" . date("d.m ", dt::mysql2timestamp($curDate)) . "</span>";
	    		 	} else {
	    		 		$res[$w]['subHeader'][] = date("d.m ", dt::mysql2timestamp($curDate));
	    		 	}
	    		 	$curDate = dt::addDays(1, $curDate); 
	    		}
	    		
	    		foreach ($res as $headerArr) {
	    			$headerInfo[] = $headerArr;
	    		}

    		break; 
    	  
    	   // ако периода на таблицата е в рамките на година - седмици
    		case 'YearWeek' :
    		    
    		    date_default_timezone_set('UTC');
    		    
	    		// делението е месец/седмица
	    		$otherParams['mainHeaderCaption'] = tr('година');
	    		$otherParams['subHeaderCaption'] = tr('седмица');
	    		
	    		if(date("N", mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0])) != 1 ) {
		    		// таблицата започва от понеделника преди намерената стартова дата
		    		$otherParams['startTime'] = dt::mysql2timestamp(date('Y-m-d H:i:s', strtotime('last Monday',mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]))));
		    		
	    		} else {
	    			$otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]); 
	    		}
	    		
	    		if(date("N", mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0])) != 7 ) {
	    			// до неделята след намеренета за край дата
		    		$otherParams['endTime'] = dt::mysql2timestamp(date('Y-m-d H:i:s', strtotime('Sunday',mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]))));
	    		} else {
	    			$otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);
	    		}
	    		// урл-тата на стрелките
	    		$otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
	    		$otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
	    		
	    		// кое време е сега?
	    		$otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
	    		
	    		$curDate = date('Y-m-d H:i:s', $otherParams['startTime']);
	    		$toDate = dt::addSecs(86399, date('Y-m-d H:i:s', strtotime('Sunday', mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]))));
	          
	    		// генерираме номерата на седмиците между началото и края
	    		while ($curDate < $toDate){
	    		    
	    			$curDateExplode =  explode("-", $curDate);
	    			$w = $curDateExplode[0];
	    			
	    			// ако 31.12 е ден до сряда, то 01 седмица ще се отбелязва в следващата година
	    			if(date("W", dt::mysql2timestamp($curDate)) == 01 && date("N", mktime(23, 59, 59, 12, 31, $startExplode[0])) <= 3) {
	    				$w = $w + 1;
	    			} 
	    			
	    			$res[$w]['mainHeader'] = $w;
	    			// номера на седмицата
	    		 	$res[$w]['subHeader'][date("W", dt::mysql2timestamp($curDate))] = "&nbsp;" . date("W", dt::mysql2timestamp($curDate)) . "&nbsp;";
	    		 	
	    		 	// обикаляме по седмиците
	    		 	$curDate = dt::addDays(7, $curDate);
	    		}
	    		
	    		// тези действия са за номериране на вътрешния масив от 0,1, ...
	    		foreach ($res as $key => $headerArr) {
	                foreach($headerArr['subHeader'] as $val){
	                	$subInfo[$key]['mainHeader'] = $key;
	    				$subInfo[$key]['subHeader'][] = $val;
	                }
	    		}
	    		
	    		// тези действия са за номериране на външния масив от 0,1, ...
	    		foreach($subInfo as $infoArr){
	    			$headerInfo[] = $infoArr;
	    		}
    		
    		break; 
    	}
    	
    	return (object) array('otherParams' => $otherParams, 'headerInfo' => $headerInfo);
    }
    
    
    /**
     * Изчислява мин начало и макс край на всички задачи
     * @param stdClass $data
     */
    public static function calcTasksMinStartMaxEndTime ($data)
    {  
        if($data->recs){ 
        	$data = $data->recs;
        } 
        
        if(is_array($data)){
    	// за всеки едиин запис от базата данни
    	foreach($data as $rec){ 
    	
    		if ($rec->timeStart) {
    			$timeStart = $rec->timeStart;
    		} else { 
    			$timeStart = $rec->expectationTimeStart;
    		}
    		
    		if ($rec->timeEnd) {
    			$timeEnd = $rec->timeEnd;
    		} else {
    			$timeEnd = $rec->expectationTimeEnd;
    		}
    		
    		if($timeStart){
	    		// ако няма продължителност на задачата
	    		if(!$rec->timeDuration) {
	    			// продължителността е края - началото
	    			$timeDuration = 1800;
	    		} else {
	    			$timeDuration = $rec->timeDuration;
	    		}
	    		
	    		// ако нямаме край на задачата
	    		/*if(!$rec->timeEnd){
	    			// изчисляваме края, като начало + продължителност
	    			$timeEnd = dt::timestamp2Mysql(dt::mysql2timestamp($rec->timeStart) + $timeDuration);
	    		} else {
	    			$timeEnd = $rec->timeEnd;
	    		}*/
	    		
	    		// правим 2 масива с начални и крайни часове
	    		if($timeStart){
	    			$start[] = dt::mysql2timestamp($timeStart);
	    			$end[] = dt::mysql2timestamp($timeEnd);
	    		}
    		}
    	}
        }
    	
    	if (count($start) >= 2 && count($end) >=2) {
	    	$startTime = min($start);
	    	$endTime = max($end);  
    	} else {
    		$startTime = dt::mysql2timestamp($timeStart);
	    	$endTime = dt::mysql2timestamp($timeEnd); 
    	}
      
    	return (object) array('minStartTaskTime' => $startTime, 'maxEndTaskTime' => $endTime);
    
    }

    
    /**
     * Може ли една задача да стане в състояние 'active'?
     * 
     * @param stdClass $rec
     * @return date|NULL
     */
    static public function canActivateTask($rec)
    {
        // Без отговорник да не може да се активират
        $sharedUsersArr = keylist::toArray($rec->sharedUsers);
        if ($rec->assign) {
            $sharedUsersArr[$rec->assign] = $rec->assign;
        }
        if (empty($sharedUsersArr)) {
            
            return ;
        }
        
    	// сега
    	$now = dt::verbal2mysql(); 
    	$nowTimeStamp = dt::mysql2timestamp($now);
    	// вчера
        $yesterday = $nowTimeStamp - (24 * 60 * 60); 
    	$yesterdayDate =  dt::timestamp2Mysql($yesterday);
    	
    	$calcTime = FALSE;
    	
        // Ако сме активирали през singleToolbar-а
	    if ($rec->id) { 
	    	$query = cal_TaskConditions::getQuery();
	    	$query->where("#baseId = '{$rec->id}'");

	    	while($recCond = $query->fetch()) {
				$arrCond[] = $recCond;
				
	    	} 
	    	
	    	// ако задачата е зависима
	    	if (is_array($arrCond)) {
    			foreach ($arrCond as $cond) { 
    				// зависиама по прогрес
		    		if ($cond->activationCond == "onProgress") {  
		    			// процентите на завършване на бащината задача
		    			$progress = self::fetchField($cond->dependId, "progress");
		    			
		    			// ако е равен или по голям на искания от потребутеля процент
		    			if ($progress >= $cond->progress) { 
		    				// времето за стартирване на текущата задача е сега
			    			$calcTime = $now;
			    		} else {
			    		   $calcTime = NULL;
			    		}
			    		
			    		return $calcTime;
			    	// ако ще правим изчисления по времена
		    		} else { 
                        // правим масив с всички изчислени времена
		    			$calcTimeS[] = self::calculateTimeToStart($rec, $cond);
		    			
		    		} 		 
		     	}
		     	
		     	// взимаме и началното време на текущата задача,
		     	// ако има такова
		     	$timeStart = self::fetchField($rec->id, "timeStart");
		     	
		     	if ($timeStart != NULL) { 
			     	// прибавяме го към масива
			     	array_push($calcTimeS, $timeStart);
			     	
			     	// най-малкото време е времето за стартирване на текущата задача
			     	$calcTime = min($calcTimeS);
                } else {
		     	    if (is_array($calcTimeS)) {
		     	        $calcTime = min ($calcTimeS);
		     	        
		     	    } else {
		     	        $calcTime = NULL;
		     	    }
		     	}
		     	
		     	return $calcTime;
		     	
		    // задачата не е зависима от други задачи
    		} else { 
    			$timeStart = self::fetchField($rec->id, "timeStart");
    			$timeEnd = self::fetchField($rec->id, "timeEnd");
    			$timeDuration = self::fetchField($rec->id, "timeDuration");
    			
    			if ($timeStart != NULL) {
    				// времето за стартиране е времето оказано от потребителя
    				$calcTime = $timeStart;
    			} elseif (!$timeStart && ($timeEnd && $timeDuration)) {
    				
    				$calcTime =  dt::timestamp2Mysql(dt::mysql2timestamp($timeEnd) - $timeDuration);
    			} else { 
    				// ако не е оказано време от потребителя - е сега
    				$calcTime = $now;
    			}
    			
    			return $calcTime;
    		}
	    } elseif (!$rec->id && $rec->timeStart) { 
    		if (is_array($arrCond)) { 
    			foreach ($arrCond as $cond) { 
		    		if ($cond->activationCond == "onProgress") { 
		    			// proverka za systoqnieto ?!? 
		    			$progress = self::fetchField($cond->dependId, "progress");
		    			
		    			if ($progress >= $cond->progress) {
			    			$calcTime = $now; 
			    		}
		    		} else { 
		    			$calcTimeS[] = self::calculateTimeToStart($rec, $cond);
		    		}
		    		
		    		return $calcTime;
		     	}
		     	$timeStart = self::fetchField($rec->id, "timeStart");
		     	
    			if ($timeStart != NULL) { 
			     	// прибавяме го към масива
			     	array_push($calcTimeS, $timeStart);
			     	
			     	// най-малкото време е времето за стартирване на текущата задача
			     	$calcTime = min($calcTimeS);
                } else {
		     	    $calcTime = min ($calcTimeS);
		     	}
    			
    		} else {
    			$calcTime = $rec->timeStart;
    		}
    	} elseif (!$rec->timeStart && !$rec->id) { 
    		$calcTime = $now;
        }
    	
    	// връщаме времето за активиране
    	return $calcTime;
    }
   
    
    /**
     * Правим нотификация на всички шернати потребители,
     * че е стартирана задачата
     */
    static public function doNotificationForActiveTasks($activatedTasks)
    {
    	foreach($activatedTasks as $rec) {

	    	$subscribedArr = keylist::toArray($rec->sharedUsers); 
			
	    	if(is_array($subscribedArr)) {  
				$message = "Стартирана е задачата \"" . self::getVerbal($rec, 'title') . "\"";
				$url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
				$customUrl = array('cal_Tasks', 'single',  $rec->id);
				$priority = 'normal';
				
				foreach($subscribedArr as $userId) {   
					if($userId > 0  &&  
					   doc_Threads::haveRightFor('single', $rec->threadId, $userId)) { 
						bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
					}
				}
			}
  			
  			$rec->notifySent = 'yes';

        	self::save($rec, 'notifySent');
        }
    }
    
    
    /**
     * Изчисляваме новото начало за стратиране на задачата
     * ако тя е зависима по време от някоя друга
     * 
     * @param stdClass $rec
     * @param stdClass $recCond
     */
    static public function calculateExpectationTime (&$rec)
    {
    	// сега
    	$now = dt::verbal2mysql(); 
    	
        // ако задачата има id следователно може да е зависима от други
    	if($rec->id) {
    	    $query = cal_TaskConditions::getQuery();
	    		
	    	$query->where("#baseId = '{$rec->id}'");
	    		
	    	while ($recCond = $query->fetch()) {
	    		$arrCond[] = $recCond;
	    	}
	    	
	    	if (is_array($arrCond)) { 
	    	    foreach($arrCond as $cond) {
	    	    	 // правим масив с всички изчислени времена
	    			$calcTimeS[] = self::calculateTimeToStart($rec, $cond);
    	    		//$timeEnd = self::fetchField($cond->dependId, "expectationTimeEnd");
	    	    }
	    	    
		     	// взимаме и началното време на текущата задача,
		     	// ако има такова
		     	$timeStartRec = self::fetchField($rec->id, "timeStart");
		     	
		     	if (!$timeStartRec) { 
		     		// в противен случай го слагаме 0
		     		//$timeStartRec = $now;
		     		//$timeStartRec = 0;

		     	} else {
			     	// прибавяме го към масива
			     	array_push($calcTimeS, $timeStartRec);
		     	}
		     	// най-малкото време е времето за стартирване на текущата задача
		     	$timeStart = min($calcTimeS);

		     // ако не е зависима от други взимаме нейните начало и край
	    	} else {
	    		$timeStart = self::fetchField($rec->id, "timeStart");
    	    	$timeEnd = self::fetchField($rec->id, "timeEnd");
    	    	$timeDuration = self::fetchField($rec->id, "timeDuration");
    	    	
    	    	if($timeDuration && !$timeEnd){
    	    		$timeEnd = dt::timestamp2Mysql(dt::mysql2timestamp($timeStart) + $timeDuration);
    	    		
    	    	} elseif (($timeDuration && $timeEnd) && !$timeStart) {
    	    		$timeStart = dt::timestamp2Mysql(dt::mysql2timestamp($timeEnd) - $timeDuration);
    	    	}
	    	}
	    // ако няма id, то имаме директно началото и края й	
    	} else { 
    		$timeStart = $rec->timeStart;
    		$timeEnd = $rec->timeEnd;
    		
    	}
	    
    	// ако задачата няма начало и край
	    if ($timeStart == NULL && $timeEnd == NULL && $rec->timeDuration == NULL) { 

			$expStart = $now;
			$expEnd = $now;
		    	
		// ако задачата има начало
		// може да определим и края й
	    } elseif ($timeStart && !$timeEnd) { 
	    	$expStart = $timeStart;
	   		$expEnd = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) + $rec->timeDuration);
	    		
	    // ако задачата има край
	    // можем да кажем кога е началото й
	    } elseif ($timeEnd && !$timeStart && !$rec->timeDuration) {
	    	$expEnd = $timeEnd;
	    	if ($rec->id) {
	    		$expStart = self::fetchField($rec->id, "modifiedOn");
	    	}	
	    // ако има и начало и край
	    // то очакваните начало и край са тези
	    } elseif ($timeStart && $timeEnd) {
	   		$expStart = $timeStart;
			$expEnd = $timeEnd;
	    } elseif (($rec->timeDuration && $timeStart) && !$timeEnd) {
	    	$expStart = $timeStart;
	    	$expEnd = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) + $rec->timeDuration);
	    } elseif ($rec->timeDuration && (!$timeStart && !$timeEnd)){
	    	$expStart = $now;
	    	$expEnd = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) + $rec->timeDuration);
	    } elseif (($rec->timeDuration && $timeEnd) && !$timeStart){
	    	$expStart = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) - $rec->timeDuration);
	    	$expEnd = $timeEnd;
	    }

    	$rec->expectationTimeStart = $expStart;
    	$rec->expectationTimeEnd = $expEnd;
    }
    
    
    /**
     * Изчисляваме новото начало за стратиране на задачата
     * ако тя е зависима по време от някоя друга
     * 
     * @param stdClass $rec
     * @param stdClass $recCond
     */
    static public function calculateTimeToStart($rec, $recCond)
    {
    	// времето от което зависи новата задача е началото на зависимата задача
    	// "timeCalc"
    	$dependTimeStart = self::fetchField($recCond->dependId, "expectationTimeStart");
    	$dependTimeEnd = self::fetchField($recCond->dependId, "expectationTimeEnd");
    	$closedTime = self::fetchField($recCond->dependId, "timeClosed");
    	
    	$now = dt::verbal2mysql(); 

    	if (!$dependTimeStart) { 
    		$dependTimeStart = self::fetchField($recCond->dependId, "timeActivated");
    	}
    	
    	if (!$dependTimeEnd) {
    		if (!$closedTime) {
    			$dependTimeEnd = dt::timestamp2Mysql(dt::mysql2timestamp($dependTimeStart) + $recCond->timeDuration);
    		} else {
    			$dependTimeEnd = $closedTime;
    		}
    	}

    	// ако имаме условие след началото на задачата
    	if ($recCond->activationCond == 'afterTime') {
    		// прибавяме отместването след началото
    		$calcTime = dt::mysql2timestamp($dependTimeStart) + $recCond->distTime;
    		$calcTimeStart = dt::timestamp2Mysql($calcTime);
    	} elseif ($recCond->activationCond == 'beforeTime') {
    		// в противен случай го вадим
    		$calcTime = dt::mysql2timestamp($dependTimeStart) - $recCond->distTime;
    		$calcTimeStart = dt::timestamp2Mysql($calcTime);
    	} elseif ($recCond->activationCond == 'afterTimeEnd'){
    		// прибавяме отместването в кря
    		$calcTime = dt::mysql2timestamp($dependTimeEnd) + $recCond->distTime;
    		$calcTimeStart = dt::timestamp2Mysql($calcTime);
    	} else {
    		// в противен случай го вадим
    		$calcTime = dt::mysql2timestamp($dependTimeEnd) - $recCond->distTime;
    		$calcTimeStart = dt::timestamp2Mysql($calcTime);
    	}
    	
    	// ако задачата е безкрайна
    	if (!$rec->timeStart) { 
    		$rec->timeCalc = $calcTimeStart;
    		self::save($rec, 'timeCalc');
    			    	
    		// връщаме изчисленото време
    		return $calcTimeStart;
    		// в противен случай гледаме коя е най-голямата дата и нея взимаме
    	} else {
    		
    		if ($rec->timeStart > $calcTimeStart) {
    			$rec->timeCalc = $rec->timeStart;
    			self::save($rec, 'timeCalc');
    			
    			return $rec->timeStart;
    		} else {
    			$rec->timeCalc = $calcTimeStart;
    			self::save($rec, 'timeCalc');
    			
    			return $calcTimeStart;
    		}
    	}
    }
    
    
    public static function roundTime($time)
    {
    	if(!isset($time) || !is_numeric($time)) return NULL;
    	
    	$t = abs($time);
    	
    	$weeks    = floor($t / (7 * 24 * 60 * 60));
    	$days     = floor(($t - $weeks * (7 * 24 * 60 * 60)) / (24 * 60 * 60));
    	$hours    = floor(($t - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60)) / (60 * 60));
    	$minutes  = floor(($t - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60) - $hours * 60 * 60) / 60);
    	$secundes = floor(($t - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60) - $hours * 60 * 60 - $minutes * 60));
    	
    	if ($weeks > 0) { 
    		$res = round($time / 86400) * 86400;
    		
    		return $res;
    	}
    	
    	if ($days > 0) { 
    		$res = round($time / 3600) * 3600;
    		
    		return $res;
    	}
    	
    	if ($hours > 0 || $minutes > 0) {
    		$res = round($time / 60) * 60;
    		
    		return $res;
    	}
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        if ($row->progressBar || $row->progress) {
            $resArr['progressBar'] =  array('name' => tr('Прогрес'), 'val' =>"[#progressBar#] [#progress#]");
        }
        
        $resArr['priority'] =  array('name' => tr('Приоритет'), 'val' =>"[#priority#]");
        
        if ($row->timeStart){
            $resArr['timeStart'] =  array('name' => tr('Начало'), 'val' =>"[#timeStart#]");
        }
        
        if ($row->timeDuration) {
            $resArr['timeDuration'] =  array('name' => tr('Продължителност'), 'val' =>"[#timeDuration#]");
        }
        
        if ($row->timeEnd) {
            $resArr['timeEnd'] =  array('name' => tr('Краен срок'), 'val' =>"[#timeEnd#] [#remainingTime#]");
        }
        
        if ($row->workingTime) {
            $resArr['workingTime'] =  array('name' => tr('Отработено време'), 'val' =>"[#workingTime#]");
        }
        
        if ($row->afterTask) {
            $resArr['afterTask'] =  array('name' => tr('Започване след задача'), 'val' =>"[#afterTask#]");
        }
        
        if ($row->afterTaskProgress){
            $resArr['afterTaskProgress'] =  array('name' => tr('Прогрес на задачата'), 'val' =>"[#afterTaskProgress#]");
        }
        
        
        if ($row->expectationTimeStart) {
            $resArr['expectationTimeStart'] =  array('name' => tr('Очаквано начало'), 'val' =>"[#expectationTimeStart#]");
        }
        
        if ($rec->timeStart) {
        	unset($resArr['expectationTimeStart']);
        }
        
        if ($row->expectationTimeEnd){ 
            $resArr['expectationTimeEnd'] =  array('name' => tr('Очакван край'), 'val' =>"[#expectationTimeEnd#]");
        }
        
        if ($rec->timeEnd) {
        	unset($resArr['expectationTimeEnd']);
        }
        
        if (!$rec->timeStart && !$rec->timeEnd) {
        	unset($resArr['expectationTimeStart']);
        	unset($resArr['expectationTimeEnd']);
        }

        if ($row->assign) {
            $resArr['assign'] =  array('name' => tr('Възложено'), 'val' => tr('на') . " [#assign#] " . tr('от') . " [#assignedBy#] " . tr('в') . " [#assignedOn#]");
        }
    }
    
    
    /**
     * Преди записване на клонирания запис
     * 
     * @param core_Mvc $mvc
     * @param object $rec
     * @param object $nRec
     * 
     * @see plg_Clone
     */
    function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        unset($nRec->progress);
        unset($nRec->timeActivated);
        unset($nRec->workingTime);
        unset($nRec->timeCalc);
        $nRec->notifySent = 'no';
    }
    
    
    /**
     * Създаване на задача от документ
     */
    function act_AddDocument()
    {
        $this->requireRightFor('add');
        
        $originId = Request::get('foreignId');
        
        expect($originId);
        
        $document = doc_Containers::getDocument($originId);
        
        expect($document);
        
        $dRec = $document->fetch();
        
        $document->instance->requireRightFor('single', $dRec);
        
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($document, 'single', $originId));
        
        // Вземаме формата към този модел
        $form = cls::get('core_Form');
        
        $cu = core_Users::getCurrent();
        
        $form->FNC('taskType', 'enum', 'caption=Тип задача, input=input, removeAndRefreshForm=date,folderId,silent, class=w100');
        
        $taskArr = array();
        
        // Групите за видовете задачи
        $toNewTask = new stdClass();
        $toNewTask->title = 'Към нова задача';
        $toNewTask->group = TRUE;
        
        $toWaitingTask = new stdClass();
        $toWaitingTask->title = 'Към чакаща задача';
        $toWaitingTask->group = TRUE;
		
        $toActiveTask = new stdClass();
        $toActiveTask->title = 'Към активна задача';
        $toActiveTask->group = TRUE;
        
        $prefixDelim = '_';
        
        $newPrefix = 'n' . $prefixDelim;
        $waitingPrefix = 'w' . $prefixDelim;
        $activePrefix = 'a' . $prefixDelim;
        
        $prefixTaskTypeArr = array($newPrefix => $toNewTask, $waitingPrefix => $toWaitingTask, $activePrefix => $toActiveTask);
        
        $form->setDefault('taskType', $newPrefix . 'postPonned');
        
        // Видовете "Нови задачи"
        $taskArr[$newPrefix] = $prefixTaskTypeArr[$newPrefix];
        $taskArr[$newPrefix . 'postPonned'] = 'Отложена задача';
        $taskArr[$newPrefix . 'project'] = 'В папка на проект';
        $taskArr[$newPrefix . 'compnany'] = 'В папка на фирма';
        $taskArr[$newPrefix . 'person'] = 'В папка на лице';
        
        // Шаблонните задачи
        $prototypeArr = doc_Prototypes::getPrototypes($this);
        foreach ($prototypeArr as $pId => $title) {
            $taskArr[$newPrefix . $pId] = $title;
        }
        
        // Активните и чакащите задачи
        $query = $this->getQuery();
        $query->where("#state = 'waiting'");
        $query->orWhere("#state = 'active'");
        
        $query->orderBy('modifiedOn', 'DESC');
        
        doc_Threads::restrictAccess($query);
        
        $tArr = array();
        
        $isTask = (boolean) ($document->instance instanceof cal_Tasks);
        
        while ($rec = $query->fetch()) {
            
            // Да не може да се прикача към себе си
            if ($isTask && $rec->id == $document->that) continue; 
            
            $tArr[$rec->state][$rec->id] = $rec->title;
        }
        
        // Чакащите задачи
        if (is_array($tArr['waiting']) && !empty($tArr['waiting'])) {
            $taskArr[$waitingPrefix] = $prefixTaskTypeArr[$waitingPrefix];
            foreach ($tArr['waiting'] as $id => $title) {
                $taskArr[$waitingPrefix . $id] = $title;
            }
        }
        
        // Активните задачи
        if (is_array($tArr['active']) && !empty($tArr['active'])) {
            $taskArr[$activePrefix] = $prefixTaskTypeArr[$activePrefix];
            foreach ($tArr['active'] as $id => $title) {
                $taskArr[$activePrefix . $id] = $title;
            }
        }
        
        $form->setOptions('taskType', $taskArr);
        
        $form->input(NULL, TRUE);
        $form->input();
        
        $sTypePrefix = '';
        $sSel = '';

        $mvcName = '';
        $fncName = 'contragentId';
        $allowEmpty = '';
        
        $redirectUrl = array($this, 'add', 'foreignId' => $originId, 'ret_url' => TRUE);

        try {
            $dRow = $document->getDocumentRow();
            $title = $dRow->recTitle ? $dRow->recTitle : $dRow->title;
        
            $redirectUrl['title'] = tr("За") . ': ' . $title;
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
        
        $rec = $form->rec;
        
        // Добавяне допълнителните полета
        if ($rec->taskType) {
            list($sType, $sSel) = explode($prefixDelim, $rec->taskType, 2);
            
            $sTypePrefix = $sType . $prefixDelim;
            
            expect($prefixTaskTypeArr[$sTypePrefix]);
            
            // Ако ще е нова задача
            if ($sTypePrefix == $newPrefix) {
                
                if ($sSel == 'postPonned') {
                    $form->FNC('date', 'date', 'caption=Дата,class=w100, input=input, silent');
                    $form->setDefault('date', dt::addDays(1));
                    $mvcName = 'doc_Folders';
                    
                    $form->setDefault('folderId', doc_Folders::getDefaultFolder($cu));
                    $allowEmpty = ' ,allowEmpty';
                    $fncName = 'folderId';
                } elseif ($sSel == 'compnany') {
                    $mvcName = 'crm_Companies';
                } elseif ($sSel == 'person') {
                    $mvcName = 'crm_Persons';
                } elseif ($sSel == 'project') {
                    $mvcName = 'doc_UnsortedFolders';
                } else {
                    
                    // Трябва да е id на шаблонна задача
                    expect(is_numeric($sSel));
                    
                    $mvcName = 'doc_Folders';
                    $fncName = 'folderId';
                    
                    // Ако е зададена папка за шаблонните задачи по-подразбиране да е там - ако не в папката на документа
                    $defFolderId = doc_Prototypes::getProtoRec(get_called_class(), $sSel, 'sharedFolders');
                    wp($defFolderId);
                    if (!$defFolderId || !doc_Folders::haveRightFor('single', $defFolderId)) {
                        $defFolderId = cal_Tasks::fetchField($sSel, 'folderId');
                    }
                    
                    $form->setDefault($fncName, $defFolderId);
                    
                    $redirectUrl[$this->protoFieldName] = $sSel;
                }
                
                if ($mvcName) {
                    $form->FNC($fncName, "key2(mvc={$mvcName}, restrictViewAccess=yes{$allowEmpty})", 'caption=Папка,class=w100, input=input, silent');
                }
                
                // За да не гърми при избор на различен тип задачи и когато няма такава стойност в folderId
                if ($form->cmd == 'refresh') {
                    Request::push(array($fncName => ''));
                }
                
                $form->input(NULL, TRUE);
                $form->input();
            }
        }
        
        $taskId = 0;
        if($form->isSubmitted() && $sTypePrefix) {
            
            // Ако е избрана задача, проверяваме дали документа е бил добавен вече
            if (($sTypePrefix == $waitingPrefix) || ($sTypePrefix == $activePrefix)) {
                $taskId = $sSel;
                
                if ($taskId) {
                    if (cal_TaskDocuments::fetch(array("#taskId = '[#1#]' AND #containerId = '[#2#]'", $taskId, $originId))) {
                        $form->setError('taskType', 'Документът вече е бил добавен в задачата');
                    }
                } else {
                    $form->setError('taskType', 'Не е избрана задача');
                }
            }
            
            // За новите задачи - подогтвяме `folderId`
            if ($sTypePrefix == $newPrefix && $sSel != 'postPonned') {
                if ($rec->contragentId && !$rec->folderId) {
                
                    $mvcInst = cls::get($mvcName);
                    $rec->folderId = $mvcInst->forceCoverAndFolder($rec->contragentId);
                }
                
                expect($rec->folderId, $rec);
                
                $redirectUrl['folderId'] = $rec->folderId;
            }
        }
        
        // Ако е избрана съществуваща задача - прикачаме документа към нея
        if($form->isSubmitted() && $taskId) {
            $this->requireRightFor('single', $taskId);
            
            if (cal_TaskDocuments::add($taskId, $originId)) {
                
                return new Redirect($retUrl, '|Успешно добавихте документа към|* ' . cal_Tasks::getLinkToSingle($taskId));
            } else {
                $form->setError('taskType', 'Грешка при добавяне на документа към задачата');
            }
        }
        
        // Ако ще се създава нова задача - при избор на отложена
        if($form->isSubmitted() && $sTypePrefix = $newPrefix && $sSel == 'postPonned') {
            
            $haveFolder = FALSE;
            
            if ($rec->folderId) {
                $redirectUrl['folderId'] = $rec->folderId;
                $haveFolder = TRUE;
            }
            
            // Ако има дата
            if ($rec->date) {
                
                Mode::push('text', 'plain');
                $date = dt::mysql2verbal($rec->date, 'd.m.Y');
                $wDay = dt::mysql2verbal($rec->date, 'N');
                $wDayStr = tr(core_DateTime::$weekDays[$wDay-1]);
                $nick = core_Users::getCurrent('nick');
                Mode::pop('text');
                
                $redirectUrl['title'] = tr("Задачи за") . ' ' . $date . '/' . $wDayStr . '/' . $nick;
                $redirectUrl['timeStart'] = dt::verbal2mysql($date . ' 08:00:00');
                
                // Проверяваме дали същата задача не е създадена
                $query = self::getQuery();
                $query->where("#state != 'rejected'");
                $query->where(array("#createdBy = '[#1#]'", $cu));
                $query->where(array("#title = '[#1#]'", $redirectUrl['title']));
                $query->where(array("#timeStart = '[#1#]'", $redirectUrl['timeStart']));
                if ($haveFolder) {
                    $query->where(array("#folderId = '[#1#]'", $redirectUrl['folderId']));
                }
                $query->limit(1);
                
                // Ако задачата съществува, добавяме документа към нея
                if ($rec = $query->fetch()) {
                    
                    // Ако ще се добавя към същата задача
                    if ($rec->containerId == $originId) {
                        
                        return new Redirect($retUrl, '|Задачата не може да бъде добавена към себе си|* ' . cal_Tasks::getLinkToSingle($rec->id), 'warning');
                    }
                    
                    if (cal_TaskDocuments::fetch(array("#taskId = '[#1#]' AND #containerId = '[#2#]'", $rec->id, $originId))) {
                        
                        return new Redirect($retUrl, '|Документът вече е бил добавен към|* ' . cal_Tasks::getLinkToSingle($rec->id), 'warning');
                    } elseif (cal_TaskDocuments::add($rec->id, $originId)) {
                        
                        return new Redirect($retUrl, '|Успешно добавихте документа към|* ' . cal_Tasks::getLinkToSingle($rec->id));
                    }
                }
            } else {
                
                // Ако е нова задача без попълнени данни - ще е в нишката на оригиналния документ
                if (!$rec->folderId) {
                    $redirectUrl['threadId'] = $dRec->threadId;
                    $haveFolder = TRUE;
                }
            }
            
            if (!$haveFolder) {
                $redirectUrl['folderId'] = doc_Folders::getDefaultFolder($cu);
            }
            
            $redirectUrl['DefUsers'] = '|' . $cu . '|';
        }
        
        // Ако се стигне до тук и няма грешки във формата
        if ($form->isSubmitted()) {
            
            return new Redirect($redirectUrl);
        }
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Продължи', 'save', NULL, 'ef_icon = img/16/next-img.png, title=Запис на документа');
        $form->toolbar->addBtn('Отказ', $retUrl, NULL, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Добавяме титлата на формата
        $form->title = "Създаване на задача от|* ";
        $form->title .= cls::get($document)->getFormTitleLink($document->that);
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        return self::renderWrapping($tpl);
    }
}
