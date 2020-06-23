<?php


/**
 * Мениджър за справки
 *
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class frame2_Reports extends embed_Manager
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf,email_DocumentIntf';
    
    
    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface = 'frame2_ReportIntf';
    
    
    /**
     * @see plg_SelectPeriod
     */
    public $useFilterDateOnEdit = true;
    
    
    /**
     * @see plg_SelectPeriod
     */
    public $useFilterDateOnFilter = false;
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, doc_Wrapper, doc_plg_Prototype, doc_DocumentPlg, doc_plg_SelectFolder, plg_Search, plg_Printing, bgerp_plg_Blank, doc_SharablePlg, plg_Clone, doc_plg_Close, doc_EmailCreatePlg, plg_Sorting, plg_SelectPeriod';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Справка';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Справки';
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = true;
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'powerUser';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canRefresh = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, report, admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'frame2_ReportVersions';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Rpt';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '1.5|Общи';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'frame2/tpl/SingleLayoutReport.shtml';
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,title=Наименование,lastRefreshed=Обновяване->Последно,nextUpdate=Обновяване->Следващо,updateDays=Обновяване->Дни,updateTime=Обновяване->Час,folderId,modifiedOn,modifiedBy';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders,crm_ContragentAccRegIntf';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Кеш на обновените справки
     */
    protected $refreshReports = array();
    
    
    /**
     * Кеш на обновените справки
     */
    protected $setNewUpdateTimes = array();
    
    
    /**
     * Дефолтен текст за нотификация
     */
    protected static $defaultNotificationText = "|*[#handle#] |има актуална версия от|* '[#lastRefreshed#]'";
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'lastRefreshed,data';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title,driverClass';
    
    
    /**
     * Бутона за затваряне на кой ред да е
     */
    public $closeBtnRow = 1;
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar', 'caption=Заглавие');
        $this->FLD('updateDays', 'set(monday=Понеделник,tuesday=Вторник,wednesday=Сряда,thursday=Четвъртък,friday=Петък,saturday=Събота,sunday=Неделя)', 'caption=Обновяване и известяване->Дни,autohide');
        $this->FLD('updateTime', 'set(08:00,09:00,10:00,11:00,12:00,13:00,14:00,15:00,16:00,17:00,18:00,19:00,20:00)', 'caption=Обновяване и известяване->Час,autohide');
        $this->FLD('notificationText', 'varchar', 'caption=Обновяване и известяване->Текст,autohide');
        $this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Обновяване и известяване->Потребители,autohide');
        $this->FLD('changeFields', 'set', 'caption=Други настройки->Промяна,autohide,input=none');
        $this->FLD('maxKeepHistory', 'int(Min=0,max=40)', 'caption=Други настройки->Предишни състояния,autohide,placeholder=Неограничено');
        $this->FLD('data', 'blob(serialize, compress,size=20000000)', 'input=none');
        $this->FLD('lastRefreshed', 'datetime(format=smartTime)', 'caption=Последно актуализиране,input=none');
        $this->FLD('visibleForPartners', 'enum(no=Не,yes=Да)', 'caption=Други настройки->Видими от партньори,input=none,after=maxKeepHistory');
    }
    
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        $mvc->setField('priority', 'caption=Обновяване и известяване->Приоритет,after=sharedUsers');
    }
    
    
    /**
     * 
     * @param frame2_Reports $mvc
     * @param null|stdClass $res
     * @param core_ET $tpl
     * @param null|stdClass $data
     */
    public static function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data = null)
    {
        Mode::set('pageMenu', 'Документи');
        Mode::set('pageSubMenu', 'Всички');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     * 
     * @see embed_Manager::prepareEditForm_()
     */
    public function prepareEditForm_($data)
    {
        $data = parent::prepareEditForm_($data);
        
        $rec = $data->form->rec;
        if ($rec->id && $rec->changeFields) {
            $cu = core_Users::getCurrent();
            // И потребителя не е създател на документа
            if ($rec->createdBy != $cu && core_Users::compareRangs($rec->createdBy, $cu) >= 0) {
                $changeable = type_Set::toArray($rec->changeFields);
                $fF = $this->filterDateFrom ? $this->filterDateFrom : 'from';
                $fT = $this->filterDateTo ? $this->filterDateTo : 'to';
                
                if (!$changeable[$fF] || !$changeable[$fT]) {
                    $this->useFilterDateOnEdit = false;
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        $form->setField('notificationText', array('placeholder' => self::$defaultNotificationText));
        $form->setField('maxKeepHistory', array('placeholder' => frame2_Setup::get('MAX_VERSION_HISTORT_COUNT')));
        
        if ($Driver = self::getDriver($rec)) {
            $dates = $Driver->getNextRefreshDates($rec);
            if ((is_array($dates) && countR($dates)) || $dates === false) {
                $form->setField('updateDays', 'input=none');
                $form->setField('updateTime', 'input=none');
            }
            
            // Има ли полета, които може да се променят
            $changeAbleFields = $Driver->getChangeableFields($rec);
            if (countR($changeAbleFields)) {
                $set = array();
                foreach ($changeAbleFields as $fldName) {
                    if ($Fld = $form->getField($fldName, false)) {
                        $set[$fldName] = $Fld->caption;
                    }
                }
                
                // Задаване като опции на артикулите, които може да се променят
                if (countR($set)) {
                    $form->setField('changeFields', 'input');
                    $form->setSuggestions('changeFields', $set);
                }
            }
            
            // При редакция, ако има полета за промяна
            if (isset($rec->id)) {
                $rec->changeFields = empty($rec->changeFields) ? static::fetchField($rec->id, 'changeFields') : $rec->changeFields;
                $rec->createdBy = empty($rec->createdBy) ? static::fetchField($rec->id, 'createdBy') : $rec->createdBy;
                
                if($rec->changeFields) {
                    $changeable = type_Set::toArray($rec->changeFields);
                    $cu = core_Users::getCurrent();
                    
                    // И потребителя не е създател на документа
                    if ($rec->createdBy != $cu && core_Users::compareRangs($rec->createdBy, $cu) >= 0) {
                        
                        // Скриват се всички полета, които не са упоменати като променяеми
                        $fields = $form->selectFields("#input != 'none' AND #input != 'hidden'");
                        $diff = array_diff_key($fields, $changeable);
                        
                        $mustExist = $form->selectFields("#mustExist");
                        $diff = array_diff_key($diff, $mustExist);
                        unset($diff[$mvc->driverClassField]);
                        
                        if ($data->action == 'clone') {
                            unset($diff['sharedUsers'], $diff['notificationText'], $diff['updateDays'], $diff['updateTime'], $diff['maxKeepHistory']);
                        }
                        $diff = array_keys($diff);
                        foreach ($diff as $name) {
                            $form->setField($name, 'input=none');
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            // Ако има драйвер
            $Driver = $mvc->getDriver($rec);
            if ($Driver) {
                
                // и няма заглавие на справката, прави се опит да се вземе от драйвера
                if (empty($rec->title)) {
                    $rec->title = $Driver->getTitle($rec);
                }
                
                $refresh = true;
                if (isset($rec->id) && $form->_cloneForm !== true) {
                    $refresh = false;
                    $oldRec = self::fetch($rec->id);
                    
                    // Ако записа бива редактиран и няма променени полета от драйвера не се преизчислява
                    $fields = $mvc->getDriverFields($Driver);
                    foreach ($fields as $name => $caption) {
                        if ($oldRec->{$name} !== $rec->{$name}) {
                            $refresh = true;
                            break;
                        }
                    }
                    
                    // Ако е променен броя на версиите ъпдейт
                    if ($rec->maxKeepHistory != $oldRec->maxKeepHistory) {
                        $rec->updateVersionHistory = true;
                    }
                    
                    // Ако преди е имало обновяване, но сега няма ще се премахнат зададените обновявания
                    $oldUpdateTime = (!empty($oldRec->updateDays) || !empty($oldRec->updateTime));
                    if ($oldUpdateTime && (empty($rec->updateDays) && empty($rec->updateTime))) {
                        $rec->removeSetUpdateTimes = true;
                    }
                    
                    // Ако са променени данните за обновяване ъпдейтват се
                    if ($rec->removeSetUpdateTimes !== true) {
                        if ($oldRec->updateDays != $rec->updateDays || $oldRec->updateTime != $rec->updateTime) {
                            $rec->updateRefreshTimes = true;
                        }
                    }
                }
                
                // Флаг че датата трябва да се рефрешне
                if ($refresh === true) {
                    $rec->refreshData = true;
                }
            }
            
            // Трябва да има заглавие
            if (empty($rec->title)) {
                $form->setError('title', 'Задайте име на справката');
            }
            
            if ((isset($rec->updateDays) || isset($rec->updateTime)) && empty($rec->sharedUsers)) {
                if($Driver->requireUserForNotification($rec)){
                    $form->setError('sharedUsers', 'Не са посочени потребители за известяване при обновяване');
                }
            }
            
            frame2_ReportVersions::unSelectVersion($rec->id);
        }
    }
    
    
    /**
     * Изпращане на нотификации на споделените потребители
     *
     * @param stdClass $rec
     *
     * @return void
     */
    public static function sendNotification($rec)
    {
        // Ако няма избрани потребители за нотифициране, не се прави нищо
        $userArr = keylist::toArray($rec->sharedUsers);
        if (!countR($userArr)) {
            
            return;
        }
        
        $text = (!empty($rec->notificationText)) ? $rec->notificationText : self::$defaultNotificationText;
        $msg = new core_ET($text);
        
        // Заместване на параметрите в текста на нотификацията
        if ($Driver = self::getDriver($rec)) {
            $params = $Driver->getNotificationParams($rec);
            if (is_array($params)) {
                $msg->placeArray($params);
            }
        }
        
        $url = array('frame2_Reports', 'single', $rec->id);
        $msg = $msg->getContent();
        
        // На всеки от абонираните потребители се изпраща нотификацията за промяна на документа
        foreach ($userArr as $userId) {
            bgerp_Notifications::add($msg, $url, $userId, $rec->priority);
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $title = '???';
        if($Driver = static::getDriver($rec)){
            $title = $Driver->getTitle($rec);
        }
        
        return "{$title} №{$rec->id}";
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = $this->getRecTitle($rec);
        
        $Driver = $this->getDriver($rec);
        if (is_object($Driver)) {
            $driverTitle = $Driver->getTitle($rec);
            
            if(trim($driverTitle) != trim($rec->title)){
                $row->title = $rec->title;
                $row->subTitle = $driverTitle . " №{$rec->id}";
            }
        }
        
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $driverTitle;
        
        return $row;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        // Добавен бутон за ръчно обновяване
        if ($mvc->haveRightFor('refresh', $rec)) {
            $data->toolbar->addBtn('Обнови', array($mvc, 'refresh', $rec->id, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Обновяване на справката');
        }
        
        $url = array($mvc, 'single', $rec->id);
        $icon = 'img/16/checked.png';
        if (!Request::get('vId', 'int')) {
            $url['vId'] = $rec->id;
            $icon = 'img/16/checkbox_no.png';
        }
        
        $vCount = frame2_ReportVersions::count("#reportId = {$rec->id}");
        if ($vCount > 1) {
            $data->toolbar->addBtn("Версии|* ({$vCount})", $url, null, "ef_icon={$icon}, title=Показване на предишни версии,row=1");
        }
    }
    
    
    /**
     * Рефрешване на справката
     */
    public function act_Refresh()
    {
        $this->requireRightFor('refresh');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('refresh', $rec);
        
        self::refresh($rec);
        frame2_ReportVersions::unSelectVersion($rec->id);
        $this->logWrite('Ръчно обновяване на справката', $rec->id);
        
        return followRetUrl();
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $rec = $data->rec;
        
        // Рендиране на данните
        if ($Driver = $mvc->getDriver($rec)) {
            
            try{
                $lang = $Driver->getRenderLang($rec);
                if(isset($lang)){
                    core_Lg::push($lang);
                }
                
                $tplData = $Driver->renderData($rec);
                if(isset($lang)){
                    core_Lg::pop();
                }
                
                if (Mode::is('saveJS')) {
                    $tpl->replace($tplData, 'DRIVER_DATA');
                } else{
                    $tpl->replace($tplData->getContent(), 'DRIVER_DATA');
                }
                
            } catch(core_exception_Expect $e){
                reportException($e);
                $tpl->replace("<span class='red'><b>" . tr('Проблем при показването на справката') . '</b></span>', 'DRIVER_DATA');
            }
        } else {
             $tpl->replace("<span class='red'><b>" . tr('Проблем при зареждането на справката') . '</b></span>', 'DRIVER_DATA');
        }
        
        // Връщане на оригиналния рек ако е пушнат
        if (isset($data->originalRec)) {
            $rec = $data->originalRec;
        }
    }
    
    
    /**
     * Метод опресняващ справката по разписание
     *
     * @param stdClass $data - дата
     */
    public static function callback_refreshOnTime($data)
    {
        try {
            expect($rec = self::fetch($data->id));
            if ($rec->state == 'rejected') {
                
                return;
            }
            self::refresh($rec);
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
    }
    
    
    /**
     * Метод опресняващ справката
     *
     * @param stdClass $rec - ид на справка
     * 
     * @return void
     */
    public static function refresh(&$rec)
    {
        $rec = self::fetchRec($rec);
        
        // Ако има драйвер
        if ($Driver = self::getDriver($rec)) {
            try {
                $me = cls::get(get_called_class());
                
                // Опресняват се данните му
                $rec->data = $Driver->prepareData($rec);
                $rec->lastRefreshed = dt::now();
                
                // Запис на променените полета
                $me->save_($rec, 'data,lastRefreshed');
                
                // Записване в опашката че справката е била опреснена
                if (frame2_ReportVersions::log($rec->id, $rec)) {
                    $me->refreshReports[$rec->id] = $rec;
                    if (core_Users::getCurrent() != core_Users::SYSTEM_USER) {
                        core_Statuses::newStatus('Справката е актуализирана|*!');
                    }
                }
            } catch (core_exception_Expect $e) {
                reportException($e);
                
                if (core_Users::getCurrent() != core_Users::SYSTEM_USER) {
                    core_Statuses::newStatus('Грешка при обновяване на справката|*!', 'error');
                }
                
                self::logErr('Грешка при обновяване на справката', $rec->id);
            }
            
            $me->setNewUpdateTimes[$rec->id] = $rec;
            
            // Ако справката сега е създадена да не се обновява
            if ($rec->__isCreated === true) {
                
                return;
            }
            
            // Кога последно е видяна от потребител справката
            $lastSeen = self::getLastSeenByUser(__CLASS__, $rec);
            $months = frame2_Setup::get('CLOSE_LAST_SEEN_BEFORE_MONTHS');
            $seenBefore = dt::addMonths(-1 * $months);
            if ($lastSeen <= $seenBefore) {
                
                // Ако е последно видяна преди зададеното време да се затваря и да не се обновява повече
                $rec->brState = $rec->state;
                $rec->state = 'closed';
                $rec->refreshData = false;
                $me->invoke('BeforeChangeState', array(&$rec, &$rec->state));
                $me->save($rec, 'state,brState');
                $me->logWrite('Затваряне на остаряла справка', $rec->id);
                unset($me->setNewUpdateTimes[$rec->id]);
            }
        }
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
        // Ако е имало опреснени справки
        if (is_array($mvc->refreshReports)) {
            foreach ($mvc->refreshReports as $rec) {
                if ($Driver = $mvc->getDriver($rec)) {
                    
                    // Проверява се трябва ли да бъде изпратена нова нотификация до споделените
                    if ($Driver->canSendNotificationOnRefresh($rec)) {
                        
                        // Ако да то се нотифицират всички споделени потребители
                        self::sendNotification($rec);
                    }
                }
            }
        }
        
        // Задаване на нови времена за обновяване
        if (is_array($mvc->setNewUpdateTimes)) {
            foreach ($mvc->setNewUpdateTimes as $rec) {
                self::setAutoRefresh($rec->id);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if ($rec->refreshData === true) {
            self::refresh($rec);
        }
        
        // Ако е променен броя на поддържаните версии, ъпдейтват се
        if ($rec->updateVersionHistory === true) {
            frame2_ReportVersions::keepInCheck($rec->id);
        }
        
        // Ако ще се махнат зададените времена за обновяване, махат се
        if ($rec->removeSetUpdateTimes === true) {
            self::removeAllSetUpdateTimes($rec->id);
        }
        
        // Ако ще се ъпдейтват времената за обновяване
        if ($rec->updateRefreshTimes === true) {
            $mvc->setNewUpdateTimes[$rec->id] = $rec;
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if ($rec->state == 'draft') {
            $rec->state = 'active';
            
            if (empty($rec->activatedOn)) {
                $rec->activatedOn = dt::now();
                $rec->activatedBy = core_Users::getCurrent();
            }
        } elseif ($rec->state == 'rejected' || $rec->state == 'closed') {
            $rec->removeSetUpdateTimes = true;
        } elseif ($rec->state == 'active' && in_array($rec->brState, array('rejected', 'closed'))) {
            $rec->updateRefreshTimes = true;
        }
        
        if (empty($rec->id)) {
            $rec->__isCreated = true;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'refresh' && isset($rec)) {
            if ($Driver = $mvc->getDriver($rec)) {
                $dates = $Driver->getNextRefreshDates($rec);
                if ($dates === false) {
                    $requiredRoles = 'no_one';
                }
            }
            
            if (in_array($rec->state, array('rejected', 'closed'))) {
                $requiredRoles = 'no_one';
            }
            
            if(!$mvc->haveRightFor('edit', $rec)){
                $requiredRoles = 'no_one';
            }
        }
        
        // Документа може да бъде създаван ако потребителя може да избере поне един драйвер
        if ($action == 'add') {
            $options = self::getAvailableDriverOptions($userId);
            if (!countR($options)) {
                $requiredRoles = 'no_one';
            }
        }
        
        // За модификация, потребителя трябва да има права и за драйвера
        if (in_array($action, array('write')) && isset($rec->driverClass)) {
            if ($Driver = $mvc->getDriver($rec)) {
                if (!$Driver->canSelectDriver($userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if (in_array($action, array('edit', 'clonerec', 'close')) && isset($rec->driverClass, $rec->id)) {
            if ($Driver = $mvc->getDriver($rec)) {
                $fRec = $mvc->fetch($rec->id, 'createdBy,sharedUsers,changeFields');
                $changeFields = $sharedUsers =  $createdBy = null;
                
                // Взимат се стойностите от записа в БД, защото може да е подменен ако се разглежда по стара версия
                foreach (array('createdBy', 'sharedUsers', 'changeFields') as $exFld) {
                    ${$exFld} = $fRec->{$exFld};
                }
                
                // Кои са избраните полета за промяна (ако има)
                $changeAbleFields = type_Set::toArray($changeFields);
                
                // Може да се клонира/редактира ако може да се избере драйвера и има посочени полета за промяна
                if (isset($userId) && !haveRole('ceo', $userId)) {
                    if (!($userId == $createdBy || (keylist::isIn($userId, $sharedUsers) && countR($changeAbleFields)) || (core_Users::compareRangs($userId, $createdBy) > 0 && $mvc->haveRightFor('single', $rec)))) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        if($action == 'sendemail' && isset($rec)){
            if ($Driver = $mvc->getDriver($rec)) {
                if(!$Driver->canBeSendAsEmail($rec)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param core_Master $mvc
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        $titleObj = new core_ET("{$row->title}<!--ET_BEGIN driverTitle--><br>[#driverTitle#]<!--ET_END driverTitle-->");
        if($Driver = $mvc->getDriver($rec)){
            $driverTitle = $Driver->getTitle($rec);
            if(trim($driverTitle) != trim($row->title)){
                $titleObj->replace($driverTitle, 'driverTitle');
            }
        }
        
        $resArr['title'] = array('name' => tr('Заглавие'), 'val' => $titleObj);
        $updateHeaderName = tr('Актуализиране');
        
        if ($rec->state == 'closed') {
            $nextUpdates = self::getNextRefreshDates($rec);
            if (countR($nextUpdates)) {
                $updateHeaderName = ht::createHint($updateHeaderName, 'Справката няма да се актуализира докато е затворена', 'warning', true, 'height=12px;width=12px');
            }
        }
        
        if (!empty($rec->updateDays) || !empty($rec->updateTime) || !empty($row->nextUpdate)) {
            $resArr['update'] = array('name' => $updateHeaderName, 'val' => tr("|*<!--ET_BEGIN updateDays--><div><span style='font-weight:normal'>|Дни|*</span>: [#updateDays#]<br><!--ET_END updateDays-->
        																		 <!--ET_BEGIN updateTime--><span style='font-weight:normal'>|Часове|*</span>: [#updateTime#]<!--ET_END updateTime--><!--ET_BEGIN nextUpdate--><div><span style='font-weight:normal'>|Следващо|*</span> [#nextUpdate#]</div><!--ET_END nextUpdate-->"));
        }
        
        if (isset($rec->lastRefreshed)) {
            $resArr['lastRefreshed'] = array('name' => tr('Актуален към'), 'val' => $row->lastRefreshed);
        }
        
        if (isset($rec->sharedUsers)) {
            $resArr['notify'] = array('name' => tr('Известия'), 'row' => 2, 'val' => tr('|*[#sharedUsers#]'));
        }
    }
    
    
    /**
     * Коя е последната избрана версия от потребителя
     *
     * @param int $id - ид
     *
     * @return int - ид на последната версия
     */
    public static function getSelectedVersionId($id)
    {
        $versionArr = Mode::get(frame2_ReportVersions::PERMANENT_SAVE_NAME);
        
        return $versionArr[$id];
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    protected static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
        // Ако има избрана версия записа се подменя преди да се е подготвил
        if ($versionId = self::getSelectedVersionId($data->rec->id)) {
            if ($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')) {
                $data->originalRec = clone $data->rec;
                $versionRec->state = $data->originalRec->state;
                $data->rec = $versionRec;
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-single'])) {
            
            // Ако има избрана версия
            $selectedVersionid = self::getSelectedVersionId($rec->id);
            if (isset($selectedVersionid) && !Mode::isReadOnly()) {
                
                // И тя е по-стара от последната
                $latestVersionId = frame2_ReportVersions::getLatestVersionId($rec->id);
                if ($selectedVersionid < $latestVersionId) {
                    
                    // Показва се информация
                    if (frame2_ReportVersions::haveRightFor('checkout', $latestVersionId)) {
                        $retUrl = $mvc->getSingleUrlArray($rec->id);
                        if(countR($retUrl)){
                            $retUrl['vId'] = $rec->id;
                        }
                        
                        $checkoutUrl = array('frame2_ReportVersions', 'checkout', $latestVersionId, 'ret_url' => $retUrl);
                        $row->checkoutBtn = ht::createLink('Избор', $checkoutUrl, false, array('ef_icon' => 'img/16/tick-circle-frame.png', 'title' => 'Към последната версия'));
                        $row->checkoutDate = frame2_ReportVersions::getVerbal($latestVersionId, 'createdOn');
                    }
                }
            }
        }
        
        $callOn = $mvc->getNextRefreshTime($rec);
        if (!empty($callOn)) {
            $row->nextUpdate = core_Type::getByName('datetime(format=smartTime)')->toVerbal($callOn);
        }
    }
    
    
    /**
     * Премахване на зададените времена за обновяване
     *
     * @param int $id
     *
     * @return void
     */
    private static function removeAllSetUpdateTimes($id)
    {
        foreach (range(0, 2) as $i) {
            $data = new stdClass();
            $data->id = (string) $id;
            $data->index = (string) $i;
            core_CallOnTime::remove(get_called_class(), 'refreshOnTime', $data);
        }
    }
    
    
    /**
     * Задаване на автоматично време за изпълнение
     *
     * @param int $id
     *
     * @return void
     */
    private static function setAutoRefresh($id)
    {
        $rec = self::fetchRec($id);
        $dates = null;
        
        if ($Driver = self::getDriver($rec)) {
            $dates = $Driver->getNextRefreshDates($rec);
        }
        
        // Намира следващите три времена за обновяване
        if (empty($dates)) {
            $dates = self::getNextRefreshDates($rec);
        }
        
        // Обхождане от 0 до 2
        foreach (range(0, 2) as $i) {
            $data = new stdClass();
            $data->id = (string) $id;
            $data->index = (string) $i;
            if (!isset($dates[$i])) {
                continue;
            }
            
            core_CallOnTime::setOnce(get_called_class(), 'refreshOnTime', $data, $dates[$i]);
        }
        
        if (haveRole('debug') && countR($dates)) {
            status_Messages::newStatus('Зададени времена за обновяване');
        }
    }
    
    
    /**
     * Следващото обновяване на справката
     *
     * @param stdClass $rec
     *
     * @return datetime|NULL
     */
    private function getNextRefreshTime($rec)
    {
        foreach (range(0, 2) as $i) {
            $callOn = core_CallOnTime::getNextCallTime(get_called_class(), 'refreshOnTime', (object) array('id' => (string) $rec->id, 'index' => (string) $i));
            if (!empty($callOn)) {
                
                return $callOn;
            }
        }
    }
    
    
    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec - запис
     *
     * @return array - масив с три дати
     */
    private static function getNextRefreshDates($rec)
    {
        // Ако няма зададени времена, няма да има дати за обновяване
        if (empty($rec->updateDays) && empty($rec->updateTime)) {
            
            return array();
        }
        
        $fromDate = $rec->lastRefreshed;
        $dayKeys = array(1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday');
        $date = new DateTime($fromDate);
        
        // Кой ден от седмицата е (1 за Понеделник до 7 за Неделя)
        $todayKey = $date->format('N');
        $days = type_Set::toArray($rec->updateDays);
        $daysArr = array();
        
        // Ако има зададени дати
        if (countR($days)) {
            $orderArr = $after = $before = array();
            
            // Подреждат се дните, които са след текущия ден
            foreach ($days as $d) {
                $k = array_search($d, $dayKeys);
                if ($k > $todayKey && $k <= 7) {
                    $after[$k] = $d;
                } elseif ($k <= $todayKey && $k >= 1) {
                    $before[$k] = $d;
                }
            }
            
            ksort($after);
            ksort($before);
            
            // Връща се масив с подредените относително дни
            $orderArr = array_merge($after, $before);
            $count = countR($orderArr);
            
            // Подсигуряване, че масива има три дена (ако е зададен само един, се повтарят)
            if ($count == 1) {
                $orderArr = array_merge($orderArr, $orderArr, $orderArr);
            } elseif ($count == 2) {
                $orderArr = array_merge($orderArr, array($orderArr[key($orderArr)]));
            }
            
            // Генериране на следващите три дена за изпълняване
            foreach ($orderArr as $d1) {
                $date->modify("next {$d1}");
                $nextDate = $date->format('Y-m-d');
                $daysArr[] = $nextDate;
            }
        } else {
            
            // Ако няма зададени дни, взимат се най-близките три дена
            $daysArr[] = $date->format('Y-m-d');
            $date->modify('next day');
            $daysArr[] = $date->format('Y-m-d');
            $date->modify('next day');
            $daysArr[] = $date->format('Y-m-d');
            $date->modify('next day');
            $daysArr[] = $date->format('Y-m-d');
        }
        
        // Намират се зададените времена, ако няма това е началото на работния ден
        $timesArr = type_Set::toArray($rec->updateTime);
        if (!countR($timesArr)) {
            $startTime = bgerp_Setup::get('START_OF_WORKING_DAY');
            $timesArr[$startTime] = $startTime;
        }
        
        // Времената се добавят към датите
        $now = dt::now();
        $res = array();
        foreach ($daysArr as $d) {
            foreach ($timesArr as $time) {
                $dt = "{$d} {$time}";
                if ($dt < $now) {
                    continue;
                }
                $res[] = $dt;
            }
        }
        
        // Фикс за и на часовете от текущия ден
        $td = strtolower(date('l'));
        if ($days[$td]) {
            $n = dt::now(false);
            $nF = dt::now();
            foreach ($timesArr as $time) {
                $dt = "{$n} {$time}";
                
                if ($nF >= $dt . ':00') continue;
                
                $res[] = $dt;
            }
        }
        
        // Сортират се
        sort($res);
        
        // Връщат се най близките 3 дати
        return array($res[0], $res[1], $res[2]);
    }
    
    
    /**
     * След клониране на модела
     */
    public static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
        if ($Driver = $mvc->getDriver($nRec)) {
            $cu = core_Users::getCurrent();
            
            // Ако потребителя няма права за драйвера, но го е клонирал се споделя автоматично
            if (!$Driver->canSelectDriver($cu)) {
                doc_ThreadUsers::addShared($nRec->threadId, $nRec->containerId, $cu);
            }
        }
    }
    
    
    /**
     * Помощна ф-я кога дадения обект е последно видян от потребител
     *
     * @param mixed $classId  - клас
     * @param mixed $objectId - ид на запис или обект
     *
     * @return datetime|NULL - на коя дата
     */
    private static function getLastSeenByUser($classId, $objectId)
    {
        $Class = cls::get($classId);
        $objectRec = $Class->fetchRec($objectId, 'id,threadId');
        
        // Нишката посещавана ли е
        $oRecs = log_Data::getObjectRecs('doc_Threads', $objectRec->threadId, 'read', null, 1, 'DESC');
        $lastDate1 = $oRecs[key($oRecs)]->time;
        
        // Сингъла посещаван ли е
        $oRecs1 = log_Data::getObjectRecs($Class->className, $objectRec->id, 'read', null, 1, 'DESC');
        $lastDate2 = $oRecs[key($oRecs1)]->time;
        
        // По-голямата дата от двете
        $maxDate = max($lastDate1, $lastDate2);
        $lastUsedDate = !empty($maxDate) ? dt::timestamp2Mysql($maxDate) : null;
        
        return $lastUsedDate;
    }
    
    
    /**
     * Кои са достъпните шаблони за печат на етикети
     * 
     * @param int $id     - ид на обекта
     * @return array $res - списък със шаблоните
     */
    public function getLabelTemplates($id)
    {
        $res = array();
        
        // Проверка има ли шаблон за драйвера
        if($Driver = static::getDriver($id)){
            $res = $Driver->getLabelTemplates($id);
        }
        
        return $res;
    }
    
    
    /**
     * Какви ще са параметрите на източника на етикета
     * 
     * @param stdClass $rec
     * @return array $res -
     *               ['class'] - клас
     *               ['id] - ид
     */
    public function getLabelSource($rec)
    {
        // Източника на етикета ще е драйвера
        if($Driver = static::getDriver($rec)){
            return array('class' => $Driver, 'id' => $rec->id);
        }
        
        return array('class' => $this, 'id' => $rec->id);
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля запознайте се с нашата справка:') . '#[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FLD('user', 'user(rolesForAll=ceo, rolesForTeams=manager|officer, roles=executive, allowEmpty)', 'caption=Потребител');
        $data->listFilter->showFields = 'search, driverClass, user';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        $rec = $data->listFilter->rec;
        if ($rec->driverClass) {
            $data->query->where(array("#driverClass = '[#1#]'", $rec->driverClass));
        }
        
        if ($rec->user) {
            $data->query->like('sharedUsers', '|' . $rec->user . '|');
            $data->query->orWhere(array("#createdBy = '[#1#]'", $rec->user));
        }
        
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('modifiedOn', 'DESC');
    }
    
    
    /**
     * Връща иконката на документа
     *
     * @param mixed $id - ид или запис
     *
     * @return string   - пътя на иконката
     */
    public function getIcon($id)
    {
        if ($Driver = $this->getDriver($id)) {
            
            return $Driver->getIcon($id);
        }
        
        return 'img/16/error-red.png';
    }
}
