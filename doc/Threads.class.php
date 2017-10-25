<?php



/**
 * Клас 'doc_Folders' - Папки с нишки от документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class doc_Threads extends core_Manager
{   


    /**
     *
     */
    const DELETE_SYSTEM_ID = 'DeleteThread';
    
	
    /**
     * Максимална дължина на показваните заглавия 
     */
    const maxLenTitle = 70;
    

    /**
     * Възможности за филтриране на нишките в една папка
     */
    const filterList = 'open=Първо отворените, recent=По последно, create=По създаване, numdocs=По брой документи, mine=Само моите';
    
    
    /**
     * Колко пъти да излиза съобщение за ръчно обновяване в листовия изглед
     * @see plg_RefreshRows
     */
    public $manualRefreshCnt = 1;
    
    
    /**
     * Кое поле да се гледа за промяна и да се пуска обновяването
     * 
     * @see plg_RefreshRows
     */
    public $refreshRowsCheckField = 'modifiedOn';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Modified,plg_State,doc_Wrapper, plg_Select, expert_Plugin,plg_Sorting, plg_RefreshRows';
    
    
    /**
     * Интерфейси
     */
    var $interfaces = 'core_SettingsIntf';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
     * 
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Заглавие
     */
    var $title = "Нишки от документи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Нишка от документи";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'title=Заглавие,author=Автор,last=Последно,hnd=Номер,allDocCnt=Документи,createdOn=Създаване';
    
    
    /**
     * 
     */
    var $canNewdoc = 'powerUser';
    
    
    /**
     * Какви действия са допустими с избраните редове?
     */
    var $doWithSelected = 'open=Отваряне,close=Затваряне,restore=Възстановяване,reject=Оттегляне,move=Преместване';
    

    /**
     * Кешираме достъпа до даден контейнер
     */
    var $haveRightForSingle = array();
    

    /**
     * Данните на адресата, с най - много попълнени полета
     */
    static $contragentData = NULL;
    
    
    /**
     * Флаг, че заявките, които са към този модел лимитирани до 1 запис, ще са HIGH_PRIORITY
     */
    public $highPriority = TRUE;


    /**
     * Опашка от id на нишки, които трябва да обновят статистиките си
     *  
     * @var array
     * @see doc_Threads::updateThread()
     */
    protected static $updateQueue = array();
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId', 'key(mvc=doc_Folders,select=title,silent)', 'caption=Папки');
        $this->FNC('title', 'varchar', 'caption=Заглавие,tdClass=threadListTitle');
        $this->FLD('state', 'enum(opened,pending,closed,rejected)', 'caption=Състояние,notNull');
        $this->FLD('allDocCnt', 'int', 'caption=Брой документи->Всички,smartCenter');
        $this->FLD('partnerDocCnt', 'int', 'caption=Брой документи->Публични, oldFieldName=pubDocCnt');
        $this->FLD('last', 'datetime(format=smartTime)', 'caption=Последно');
        
        // Ключ към първия контейнер за документ от нишката
        $this->FLD('firstContainerId' , 'key(mvc=doc_Containers)', 'caption=Начало,input=none,column=none,oldFieldName=firstThreadDocId');
        
        // Достъп
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
                
        // Състоянието на последния документ в нишката
        $this->FLD('lastState', 'enum(draft=Чернова,
                  pending=Чакащо,
                  active=Активирано,
                  opened=Отворено,
                  closed=Приключено,
                  hidden=Скрито,
                  rejected=Оттеглено,
                  stopped=Спряно,
                  wakeup=Събудено,
                  free=Освободено)','caption=Последно->състояние, input=none');
        
        // Създателя на последния документ в нишката
        $this->FLD('lastAuthor', 'key(mvc=core_Users)', 'caption=Последно->От, input=none');
        
        // Ид-та на контейнерите оттеглени при цялостното оттегляне на треда, при възстановяване на треда се занулява
        $this->FLD('rejectedContainersInThread', 'blob(serialize,compress)', 'caption=Заглавие, input=none');
		
        $this->FLD('firstDocClass' , 'class', 'caption=Документ->Клас, input=none');
        $this->FLD('firstDocId' , 'int', 'caption=Документ->Обект, input=none');
        
        // Дали нишката е видима за партньори
        $this->FLD('visibleForPartners', 'enum(no=Не, yes=Да)', 'caption=Видим за партньори, input=none');
        
        // Индекс за по-бързо избиране по папка
        $this->setDbIndex('folderId');
        $this->setDbIndex('last');
        $this->setDbIndex('state');

        $this->setDbIndex('firstContainerId');
    }
    
    
    /**
     * Добавя info запис в log_Data
     * 
     * @param string $action
     * @param integer $objectId
     * @param integer $lifeDays
     * 
     * @see core_Mvc::logRead($action, $objectId, $lifeDays)
     */
    public static function logRead($action, $objectId = NULL, $lifeDays = 180)
    {
        self::logToDocument('read', $action, $objectId, $lifeDays);
        
        return parent::logRead($action, $objectId, $lifeDays);
    }
    
    
    /**
     * Добавя info запис в log_Data
     * 
     * @param string $action
     * @param integer $objectId
     * @param integer $lifeDays
     * 
     * @see core_Mvc::logWrite($action, $objectId, $lifeDays)
     */
    public static function logWrite($action, $objectId = NULL, $lifeDays = 360)
    {
        self::logToDocument('write', $action, $objectId, $lifeDays);
        
        return parent::logWrite($action, $objectId, $lifeDays);
    }
    
    
    /**
     * 
     * @param string $type
     * @param string $action
     * @param integer|NULL $objectId
     * @param integer|NULL $lifeDays
     */
    protected static function logToDocument($type, $action, $objectId, $lifeDays)
    {
        if (!$objectId) return ;
        
        $allowedType = array('read', 'write');
        
        if (!in_array($type, $allowedType)) {
            
            return ;
        }
        
        try {
            
            $firstCid = self::getFirstContainerId($objectId);
            
            if (!$firstCid) return ;
            
            $type = strtolower($type);
            $type = ucfirst($type);
            $fncName = 'log' . $type;
            
            doc_Containers::$fncName($action, $firstCid, $lifeDays);
            
            return TRUE;
        } catch (core_exception_Expect $e) {
            
            reportException($e);
        }
    }
    
    
    /**
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        if (doc_Threads::haveRightFor('single', $objId)) {
            
            $fistContainerId = self::fetchField($objId, 'firstContainerId');
            
            return doc_Containers::getLinkForObject($fistContainerId);
        }
    }
    
    
    /**
     * 
     * 
     * @param integer $id
     * @param boolean $escaped
     */
    public static function getTitleForId_($id, $escaped = TRUE)
    {
        $fistContainerId = self::fetchField($id, 'firstContainerId');
        
        return doc_Containers::getTitleForId_($fistContainerId);
    }
    
    
    /**
     * Логва действието
     * 
     * @param string $msg
     * @param NULL|stdClass|integer $rec
     * @param string $type
     */
    function logInAct($msg, $rec = NULL, $type = 'write')
    {
        if (is_numeric($rec)) {
            $rec = $this->fetch($rec);
        }
        
        if (($type == 'read') && ($folderId = Request::get('folderId', 'int')) && ($msg == 'Листване')) {
            doc_Folders::logRead('Разглеждане на папка', $folderId);
        } else {
            parent::logInAct($msg, $rec, $type);
        }
    }
    
    
    /**
     * Поправка на структурата на нишките
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repair($from = NULL, $to = NULL, $delay = 10)
    {
        // Изкючваме логването
        $isLoging = core_Debug::$isLogging;
        core_Debug::$isLogging = FALSE;
        
        $resArr = array();
        
        // id на папката за несортирани
        $unsortedCoverClassId = core_Classes::getId('doc_UnsortedFolders');
        
        // id на папката за несортирани
        $currUser = core_Users::getCurrent();
        $defaultFolderId = NULL;
        if ($currUser > 0) {
            $defaultFolderId = doc_Folders::fetchField("#coverClass = '{$unsortedCoverClassId}' AND #inCharge = '{$currUser}'", 'id', FALSE);
        }
        if (!isset($defaultFolderId)) {
            $defaultFolderId = doc_Folders::fetchField("#coverClass = '{$unsortedCoverClassId}'", 'id', FALSE);
        }
        
        $query = self::getQuery();
        
        // Подготвяме данните за търсене
        doc_Folders::prepareRepairDateQuery($query, $from, $to, $delay);
        
        $query->where("#firstContainerId IS NULL");
        $query->orWhere("#folderId IS NULL");
        
        // Не им се правят обработвки
        // За да предизвикат стартиране за съответния запис в on_Shutdown
        $query->orWhere("#allDocCnt IS NULL");
        $query->orWhere("#partnerDocCnt IS NULL");
        $query->orWhere("#lastAuthor IS NULL");
        $query->orWhere("#lastState IS NULL");
        $query->orWhere("#firstDocClass IS NULL");
        $query->orWhere("#firstDocId IS NULL");
        
        $query->limit(500);
        
        while ($rec = $query->fetch()) {
            try {
                // Ако има нишка без firstContainerId
                if (!isset($rec->firstContainerId)) {
                
                    // Първия документ от нишката
                    $firstCid = doc_Containers::fetchField("#threadId = '{$rec->id}'", 'id', FALSE);
                    
                    // Ако не може да се определи първия документ в нишката, изтриваме нишката
                    if (!$firstCid) {
                        if ($rec->id) {
                            self::delete($rec->id);
                            $resArr['del_cnt']++;
                            
                            self::logNotice("Изтрита нишка, в която няма документи", $rec->id);
                            
                            continue;
                        }
                    }
                    
                    $rec->firstContainerId = $firstCid;
                    
                    if (self::save($rec, 'firstContainerId')) {
                        $resArr['firstContainerId']++;
                        self::logNotice("Контейнерът {$firstCid} е направен първи документ в нишката", $rec->id);
                        
                        self::updateThread($rec->id);
                    }
                }
                
                // Ако няма папка използваме папката за несортирани
                if (!isset($rec->folderId) && isset($defaultFolderId)) {
                    $rec->folderId = $defaultFolderId;
                    
                    if (self::save($rec, 'folderId')) {
                        $resArr['folderId']++;
                        self::logNotice("Нишката е преместена в папка {$defaultFolderId}", $rec->id);
                    }
                }
                
                // Ако няма firstDocClass използваме от контейнера
                if (!isset($rec->firstDocClass)) {
                    $rec->firstDocClass = doc_Containers::fetchField("#id = '{$rec->firstContainerId}'", 'docClass', FALSE);
                    
                    if (self::save($rec, 'firstDocClass')) {
                        $resArr['firstDocClass']++;
                        self::logNotice("Добавен липсващ firstDocClass", $rec->id);
                    }
                }
                
                // Ако няма firstDocId използваме от контейнера
                if (!isset($rec->firstDocId)) {
                    $rec->firstDocId = doc_Containers::fetchField("#id = '{$rec->firstContainerId}'", 'docId', FALSE);
                    
                    if (self::save($rec, 'firstDocId')) {
                        $resArr['firstDocId']++;
                        self::logNotice("Добавен липсващ firstDocId", $rec->id);
                    }
                }
                
                // Ако ще се поправя само partnerDocCnt и allDocCnt
                if (!self::$updateQueue[$rec->id] && (!isset($rec->partnerDocCnt) || !isset($rec->allDocCnt))) {
                    $preparedDocCnt = FALSE;
                    $allDocCnt = $rec->allDocCnt;
                    if (!isset($rec->partnerDocCnt)) {
                        self::prepareDocCnt($rec, $firstDcRec, $lastDcRec);
                        self::save($rec, 'partnerDocCnt');
                        $resArr['partnerDocCnt']++;
                        self::logNotice("Поправен partnerDocCnt", $rec->id);
                        $preparedDocCnt = TRUE;
                    }
                    
                    if (!isset($allDocCnt)) {
                        if (!$preparedDocCnt) {
                            self::prepareDocCnt($rec, $firstDcRec, $lastDcRec);
                        }
                        self::save($rec, 'allDocCnt');
                        $resArr['allDocCnt']++;
                        self::logNotice("Поправен allDocCnt", $rec->id);
                    }
                    
                    continue;
                }
                
                self::logNotice("Нишката е обновена, защото има развалени данни", $rec->id);
                $resArr['updateThread']++;
                
                // Обновяваме нишката
                self::updateThread($rec->id);
            } catch (ErrorException $e) {
                reportException($e);
            }
        }
                
        // Ако е зададено да се поправят всички стойности
        if (doc_Setup::get('REPAIR_ALL') == 'yes') {
            $resArr += self::repairAll($from, $to, $delay);
        }
        
        // Връщаме старото състояние за ловговането в дебъг
        core_Debug::$isLogging = $isLoging;
        
        return $resArr;
    }
    
    
    
    /**
     * Поправка на развалените полета за състоянието на нишките
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repairAll($from = NULL, $to = NULL, $delay = 10)
    {
        $resArr = array();
        $query = self::getQuery();
        
        doc_Folders::prepareRepairDateQuery($query, $from, $to, $delay);
        
        // id на папката за несортирани
        $unsortedCoverClassId = core_Classes::getId('doc_UnsortedFolders');
        
        // id на папката за несортирани
        $currUser = core_Users::getCurrent();
        $defaultFolderId = NULL;
        if ($currUser > 0) {
            $defaultFolderId = doc_Folders::fetchField("#coverClass = '{$unsortedCoverClassId}' AND #inCharge = '{$currUser}'", 'id', FALSE);
        }
        if (!isset($defaultFolderId)) {
            $defaultFolderId = doc_Folders::fetchField("#coverClass = '{$unsortedCoverClassId}'", 'id', FALSE);
        }
        
        while ($rec = $query->fetch()) {
            try {
                
                // Ако папката е грешна (няма такава папка)
                if ($rec->folderId) {
                    if (!doc_Folders::fetch($rec->folderId, '*', FALSE)) {
                        self::logNotice("Променена папка от {$rec->folderId} на {$defaultFolderId}", $rec->id);
                        $rec->folderId = $defaultFolderId;
                        
                        if (self::save($rec, 'folderId')) {
                            $resArr['folderId']++;
                        }
                    }
                }
                
                $prepareDocCnt = FALSE;
                
                // Поправка за броя на документите
                if (!self::$updateQueue[$rec->id]) {
                    $cQuery = doc_Containers::getQuery();
                    $cQuery->where(array("#threadId = '[#1#]'", $rec->id));
                    $cQuery->where("#state != 'rejected'");
                    
                    $pCQuery = clone $cQuery;
                    
                    // Ако се различава броя на документите
                    $cCnt = $cQuery->count();
                    
                    $partnerCnt = (int)$rec->partnerDocCnt;
                    $allDocCnt = (int)$rec->allDocCnt;
                    
                    if ($cCnt != $allDocCnt) {
                        self::logNotice("Променен брой на документите от {$allDocCnt} на {$cCnt}", $rec->id);
                        self::prepareDocCnt($rec, $firstDcRec, $lastDcRec);
                        if ($allDocCnt) {
                            self::updateThread($rec->id);
                        } else {
                            self::save($rec, 'allDocCnt');
                        }
                        $resArr['allDocCnt']++;
                        $prepareDocCnt = TRUE;
                    }
                }
                
                // Поправяме състоянието, ако се е счупило
                if (!$rec->firstContainerId) continue;
                
                try {
                    $cRec = doc_Containers::fetch($rec->firstContainerId, '*', FALSE);
                } catch (ErrorException $e) {
                    continue;
                }
                
                // Само, ако първият контейнер е видим за партньори, тогава проверяваме за броят на видимите контейнери
                if($cRec->visibleForPartners == 'yes' && !self::$updateQueue[$rec->id]) {
                    // Ако се различава броя на документите, видими за партньори
                    $pCQuery->where("#visibleForPartners = 'yes'");
                    $pCCnt = $pCQuery->count();
                    if ($pCCnt != $partnerCnt) {
                        $pCCnt = (int)$pCCnt;
                        
                        if (!$prepareDocCnt) {
                            self::prepareDocCnt($rec, $firstDcRec, $lastDcRec);
                        }
                        
                        self::logNotice("Променен брой на документите видими за партньори от {$partnerCnt} на {$pCCnt}", $rec->id);
                        
                        if ($partnerCnt) {
                            self::updateThread($rec->id);
                        } else {
                            self::save($rec, 'partnerDocCnt');
                        }
                        
                        $resArr['partnerDocCnt']++;
                    }
                }
                
                if (!$prepareDocCnt) {
                    self::prepareDocCnt($rec, $firstDcRec, $lastDcRec);
                }
                
                $fCid = NULL;
                if ($firstDcRec && $firstDcRec->id && ($rec->firstContainerId != $firstDcRec->id)) {
                    $fCid = $rec->firstContainerId;
                    $rec->firstContainerId = $firstDcRec->id;
                    
                    self::save($rec, 'firstContainerId');
                    $resArr['firstContainerId']++;
                    self::logNotice("Променено firstContainerId от {$fCid} на {$firstDcRec->id} в нишка", $rec->id);
                }
                
                if ($fCid) {
                    try {
                        $cRec = doc_Containers::fetch($fCid, '*', FALSE);
                    } catch (ErrorException $e) {
                        continue;
                    }
                }
                
                if (!$cRec || !$cRec->docClass || !$cRec->docId) continue;
                
                // Ако firstDocClass не съвпада с този в контейнера, го променяме
                if ($rec->firstDocClass != $cRec->docClass) {
                    $fDocClass = $rec->firstDocClass;
                    $rec->firstDocClass = $cRec->docClass;
                    if (self::save($rec, 'firstDocClass')) {
                        $resArr['firstDocClass']++;
                        self::logNotice("Променен firstDocClass от {$fDocClass} на {$rec->firstDocClass}", $rec->id);
                    }
                }
                
                // Ако firstDocId не съвпада с този в контейнера, го променяме
                if ($rec->firstDocId != $cRec->docId) {
                    $fDocId = $rec->firstDocId;
                    $rec->firstDocId = $cRec->docId;
                    if (self::save($rec, 'firstDocId')) {
                        $resArr['firstDocId']++;
                        self::logNotice("Променен firstDocId от {$fDocId} на {$rec->firstDocId}", $rec->id);
                    }
                }
                
                try {
                    
                    if (!cls::load($cRec->docClass, TRUE)) continue;
                    
                    $clsInst = cls::get($cRec->docClass);
                    $iRec = $clsInst->fetch($cRec->docId, 'state', FALSE);
                    
                    if (!isset($iRec->state)) continue;
                    
                    // Ако състоянието на документа е оттеглен и на нишката трябва да е оттеглен
                    if ($iRec->state != 'rejected') continue;
                    if ($iRec->state == $rec->state) continue;
                    $rec->state = $iRec->state;
                    
                    if (self::save($rec, 'state')) {
                        $resArr['firstContainerIdState']++;
                        self::logNotice("Променено състояние на първия документ в нишката", $rec->id);
                    }
                } catch (core_exception_Expect $e) {
                    
                    continue;
                }
            } catch (ErrorException $e) {
                reportException($e);
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Екшън за оттегляне на тредове
     */
    function act_Reject()
    {
        return $this->doRejectOrRestore('Reject');
    }
    

    /**
     * Екшън за възстановяване на тредове
     */
    function act_Restore()
    {
        return $this->doRejectOrRestore('Restore');
    }

    
    /**
     * Изпълнява процедура по оттегляне/възстановяване на нишка
     */
    function doRejectOrRestore($act)
    {
        if($selected = Request::get('Selected')) {
            Debug::log('Selected = ' . $selected);
            $selArr = arr::make($selected);
            
            foreach($selArr as $id) {
                if($this->haveRightFor('single', $id)) {
                    $this->haveRightForSingle[$id] = TRUE;
                    Request::push(array('id' => $id, 'Selected' => FALSE), $act . '/' . $id);
                    $res = Request::forward();
                    Request::pop($act . '/' . $id);
                }
            } 
        } else {
            expect($id = Request::get('id', 'int'));
            expect($rec = $this->fetch($id));
            if(!$this->haveRightForSingle[$id]) {
                $this->requireRightFor('single', $rec);
            }
            $fDoc = doc_Containers::getDocument($rec->firstContainerId);
            
            Request::push(array('id' => $fDoc->that, 'Ctr' => $fDoc->className, 'Act' => $act));
            $res = Request::forward();
            Request::pop();
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя титлата на папката с теми
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        expect($data->folderId = Request::get('folderId', 'int'));
        
        $title = new ET("<div class='path-title'>[#user#] » [#folder#] ([#folderCover#])</div>");
        
        // Папка и корица
        $folderRec = doc_Folders::fetch($data->folderId);
        $folderRow = doc_Folders::recToVerbal($folderRec);
        if ($folderRec->state == 'closed') {
        	$folderRow->title = ht::createHint($folderRow->title, 'Папката е затворена', 'warning');
        }
        
        $title->append($folderRow->title, 'folder');
        $title->replace($folderRow->type, 'folderCover');
        
        // Потребител
        if($folderRec->inCharge > 0) {
            $user = crm_Profiles::createLink($folderRec->inCharge);
        } else {
            $user = core_Setup::get('SYSTEM_NICK');
        }
        $title->replace($user, 'user');
      
        if(Request::get('Rejected')) {
            $title->append("&nbsp;<span class='state-rejected stateIndicator'>&nbsp;" . tr('оттеглени') . "&nbsp;</span>", 'folder');
        }
        
        $title->replace($user, 'user');
        
        $data->title = $title;

        $mvc->title = '|*' . doc_Folders::getTitleById($folderRec->id) . '|' ;
    }
    
    
    /**
     * 
     * 
     * @param doc_Threads $mvc
     * @param object $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('search', 'varchar', 'caption=Ключови думи,input,silent,recently');
        $data->listFilter->FNC('order', 'enum(' . self::filterList . ')', 
            'allowEmpty,caption=Подредба,input,silent,autoFilter');
        $data->listFilter->setField('folderId', 'input=hidden,silent');
        $data->listFilter->FNC('documentClassId', "class(interface=doc_DocumentIntf,select=title,allowEmpty)", 'caption=Вид документ,input,recently,autoFilter');
        
        if(!isset($data->listFilter->fields['Rejected'])) {
        	$data->listFilter->FNC('Rejected', 'varchar', 'input=hidden,silent');
        }
        
        // Ако е зададено
        if ($rejectedId = Request::get('Rejected', 'int')) {
        
        	// Задаваме стойността от заявката
        	$data->listFilter->setDefault('Rejected', $rejectedId);
        }
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'folderId,search,order,documentClassId';
        
        $data->listFilter->input(NULL, 'silent');
        
        // id на папката
        $folderId = $data->listFilter->rec->folderId;
        
        $rejected = Request::get('Rejected');
        
        $documentsInThreadOptions = self::getDocumentTypesOptionsByFolder($folderId, FALSE, $rejected);
        if(count($documentsInThreadOptions)) {
            $documentsInThreadOptions = array_map('tr', $documentsInThreadOptions);
            $data->listFilter->setOptions('documentClassId', $documentsInThreadOptions);
        } else {
        	$data->listFilter->setReadOnly('documentClassId');
        }
        
        // Вземаме данните
        $key = doc_Folders::getSettingsKey($folderId);
        $vals = core_Settings::fetchKey($key);
        
        // Ако е зададено подреждане в персонализацията
        if ($vals['ordering']) {
            
            // Подреждаме по зададената стойност
            $data->listFilter->setDefault('order', $vals['ordering']);
        }
        
        expect($folderId = $data->listFilter->rec->folderId);
        
        doc_Folders::requireRightFor('single');
        
        expect($folderRec = doc_Folders::fetch($folderId));
        
        doc_Folders::requireRightFor('single', $folderRec);

        $mvc::applyFilter($data->listFilter->rec, $data->query, $data->rejQuery);
        $data->rejQuery = clone($data->query);

        // Изчистване на нотификации, свързани с промени в тази папка
        $url = array('doc_Threads', 'list', 'folderId' => $folderId);
        bgerp_Notifications::clear($url);
        bgerp_Recently::add('folder', $folderId, NULL, ($folderRec->state == 'rejected') ? 'yes' : 'no');
        
        if (Request::get('share')) {
            $url['share'] = TRUE;
            bgerp_Notifications::clear($url);
        }
        
        // Позволяваме на корицата да модифицира филтъра
        $Cover = doc_Folders::getCover($folderId);
        $Cover->invoke('AfterPrepareThreadFilter', array(&$data->listFilter, &$data->query));

        $data->query->useCacheForPager = TRUE;
    }
    
    
    /**
     * Връща типовете документи в папката, за бързодействие кешира резултатите
     * 
     * @param int $folderId - ид на папка
     * @param boolean $onlyVisibleForPartners - дали да са само видимите за партнъори документи
     * @param boolean $rejected - оттеглените или не оттеглените документи
     * @return array $options - типовете документи
     */
    public static function getDocumentTypesOptionsByFolder($folderId, $onlyVisibleForPartners = FALSE, $rejected = FALSE)
    {
        if (!$folderId) return array();
        
        $fStatArr = doc_Folders::getStatistic($folderId);
        
        $visKey = ($onlyVisibleForPartners === TRUE) ? "yes" : "_all";
        
        $rejKey = ($rejected) ? 'rejected' : '_notRejected';
        
        $resArr = array();
        
        foreach ((array)$fStatArr[$visKey][$rejKey] as $clsId => $cnt) {
            $resArr[$clsId] = core_Classes::getTitleById($clsId);
        }
        
        return $resArr;
    }
    
    
    /**
     * Налага данните на филтъра като WHERE /GROUP BY / ORDER BY клаузи на заявка
     *
     * @param stdClass $filter
     * @param core_Query $query
     */
    static function applyFilter($filter, $query)
    {
        if (!empty($filter->folderId)) {
            if (empty($filter->search)) {
                $query->where("#folderId = {$filter->folderId}");
            } else {
                $query->EXT('containerFolderId', 'doc_Containers', 'externalName=folderId');
                $query->where("#containerFolderId = {$filter->folderId}");
            }
        }
        
        // Налагане на условията за търсене
        if (!empty($filter->search)) {
            $query->EXT('containerSearchKeywords', 'doc_Containers', 'externalName=searchKeywords');
            $query->where(
            	  '`' . doc_Containers::getDbTableName() . '`.`thread_id`' . ' = ' 
                . '`' . static::getDbTableName() . '`.`id`');
            
            plg_Search::applySearch($filter->search, $query, 'containerSearchKeywords');
            
            $query->groupBy('`doc_threads`.`id`');
        }
        
        if($filter->documentClassId){
        	$query->where("#firstDocClass = {$filter->documentClassId}");
        }
        

        // Подредба - @TODO
        switch ($filter->order) {
        	default:
            case 'open':
            case 'mine':
                $query->XPR('isOpened', 'int', "IF(#state = 'opened', 0, 1)");
                $query->orderBy('#isOpened,#state=ASC,#last=DESC,#id=DESC');
                if($filter->order == 'mine') {
                    if($cu = core_Users::getCurrent()) {
                        
                        $tList = array();
                        
                        // Извличаме тредовете, където има добавени от потребителя документи;
                        $cQuery = doc_Containers::getQuery();
                        $cQuery->show('threadId');
                        $cQuery->groupBy('threadId');
                        $cQuery->where(array("#createdBy = '[#1#]'", $cu));
                        if ($filter->folderId) {
                            $cQuery->where(array("#folderId = '[#1#]'", $filter->folderId));
                        }
                        
                        while($cRec = $cQuery->fetch()) {
                            $tList[$cRec->threadId] = $cRec->threadId;
                        }

                        // Извличаме тредовете, където потребителя е лайквал документи
                        $lQuery = doc_Likes::getQuery();

                        $lQuery->EXT('folderId', 'doc_Containers', 'externalKey=containerId');
                        $lQuery->show('threadId');
                        $lQuery->groupBy('threadId');
                        $lQuery->where(array("#createdBy = '[#1#]'", $cu));
                        if (!empty($tList)) {
                            $lQuery->in("threadId", $tList, TRUE); // Това е за бързодействие
                        }
                        if ($filter->folderId) {
                            $lQuery->where(array("#folderId = '[#1#]'", $filter->folderId));
                        }
                        while($lRec = $lQuery->fetch()) {
                            $tList[$lRec->threadId] = $lRec->threadId;
                        }
                        
                        // Извличаме тредовете, където потребителя е споделен
                        $tQuery = doc_Threads::getQuery();
                        $tQuery->like("shared", "|{$cu}|");
                        if (!empty($tList)) {
                            $tQuery->in("id", $tList, TRUE); // Това е за бързодействие
                        }
                        if ($filter->folderId) {
                            $tQuery->where(array("#folderId = '[#1#]'", $filter->folderId));
                        }
                        
                        while($tRec = $tQuery->fetch()) {
                            $tList[$tRec->id] = $tRec->id;
                        }
                        
                        // Добавяме нишките, в които има входящи имейли към съответния потребител
                        $currUsersInboxesIdsArr = email_Inboxes::getUserInboxesIds($cu);
                        if (!empty($currUsersInboxesIdsArr)) {
                            $userInboxesKeylist = type_Keylist::fromArray($currUsersInboxesIdsArr);
                            $iQuery = email_Incomings::getQuery();
                            $iQuery->show('threadId');
                            $iQuery->groupBy('threadId');
                            if ($filter->folderId) {
                                $iQuery->where("#folderId = '{$filter->folderId}'");
                            }
                            
                            $iQuery->likeKeylist('userInboxes', $userInboxesKeylist);
                            
                            while ($iRec = $iQuery->fetch()) {
                                $tList[$iRec->threadId] = $iRec->threadId;
                            }
                        }
                        
                        if (!empty($tList)) {
                            $tList = implode(',', $tList);
                            $query->where("#id IN ({$tList})"); // OR #createdBy = {$cu} OR #modifiedBy = {$cu}
                        } else {
                            $query->where("1 = 2");
                        }
                    }
                }
                break;
            case 'recent':
                $query->orderBy('#last=DESC,#id=DESC');
                break;
            case 'create':
                $query->orderBy('#createdOn=DESC,#state=ASC,#last=DESC,#id=DESC');
                break;
            case 'numdocs':
                $query->orderBy('#allDocCnt=DESC,#state=ASC,#last=DESC,#id=DESC');
                break;
        }
       
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if(empty($rec->firstContainerId)) return;
        
        static $lastRecently, $cu;

        if(!$lastRecently) {
            $lastRecently = dt::addDays(-bgerp_Setup::get('RECENTLY_KEEP_DAYS')/(24*3600));
        }
 
        if(!$cu) {
            $cu = core_Users::getCurrent();
        }

        try {
            $docProxy = doc_Containers::getDocument($rec->firstContainerId);
            $docRow = $docProxy->getDocumentRow();
            $attr = array();
            
            $attr = ht::addBackgroundIcon($attr, $docProxy->getIcon());
            
            if(mb_strlen($docRow->title) > self::maxLenTitle) {
                $attr['title'] = $docRow->title;
            }

            if($rec->last < $lastRecently) {
                $attr['class'] .= ' tOld ';
            } else {
                if($rec->last > bgerp_Recently::getLastDocumentSee($rec->firstContainerId, NULL, TRUE)) {
                    $attr['class'] .= ' tUnsighted ';
                } else {
                    $attr['class'] .= ' tSighted ';
                }
            }

            
            $row->onlyTitle = $row->title = ht::createLink(str::limitLenAndHyphen($docRow->title, self::maxLenTitle),
                array('doc_Containers', 'list',
                    'threadId' => $rec->id,
                    'folderId' => $rec->folderId,
                    'Q' => Request::get('search') ? Request::get('search') : NULL),
                NULL, $attr);

            if($docRow->subTitle) {
                $row->title .= "\n<div class='threadSubTitle'>{$docRow->subTitle}</div>";
            }

            if($docRow->authorId > 0) {
                $row->author = crm_Profiles::createLink($docRow->authorId);
            } else {
                $row->author = $docRow->author;
            }

            $row->hnd .= "<div onmouseup='selectInnerText(this);' class=\"state-{$docRow->state} document-handler\">#" . ($rec->handle ? substr($rec->handle, 0, strlen($rec->handle)-3) : $docProxy->getHandle()) . "</div>";


        } catch (core_Exception_Expect $expect) {
            $row->hnd .= $rec->handle ? substr($rec->handle, 0, strlen($rec->handle)-3) : '???';
            $row->title = '?????????????';
            if($rec->firstContainerId) {
                $cRec = doc_Containers::fetch($rec->firstContainerId);
            }
            $row->author = crm_Profiles::createLink($rec->createdBy);
        
            if($cRec->docClass ) {
                if($classRec =  core_Classes::fetch($cRec->docClass )) {
                    $row->title = $classRec->title;
                }
            }
            
        }
    }
    
    
    /**
     * Създава нов тред
     */
    static function create($folderId, $createdOn, $createdBy)
    {
        $rec = new stdClass();
        $rec->folderId = $folderId;
        $rec->createdOn = $createdOn;
        $rec->createdBy = $createdBy;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Екшън за преместване на тред
     */
    function exp_Move($exp)
    {
        if($selected = Request::get('Selected')) {
            $selArr = arr::make($selected);
            Request::push(array('threadId' => $selArr[0]));
        }
        
        $threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $this->requireRightFor('single', $threadId);

            $tRec = $this->fetch($threadId);
        }
        
        // TODO RequireRightFor
        $exp->DEF('#threadId=Нишка', 'key(mvc=doc_Threads)', 'fromRequest');
        $exp->DEF('#Selected=Избрани', 'varchar', 'fromRequest');
       
        $exp->functions['canmovetofolder'] = 'doc_Threads::canmovetofolder';
        $exp->functions['doc_threads_fetchfield'] = 'doc_Threads::fetchField';
        $exp->functions['getcompanyfolder'] = 'crm_Companies::getCompanyFolder';
        $exp->functions['getpersonfolder'] = 'crm_Persons::getPersonFolder';
        $exp->functions['getcontragentdata'] = 'doc_Threads::getContragentData';
        $exp->functions['getquestionformoverest'] = 'doc_Threads::getQuestionForMoveRest';
        $exp->functions['checksimilarcompany'] = 'doc_Threads::checkSimilarCompany';
        $exp->functions['checksimilarperson'] = 'doc_Threads::checkSimilarPerson';
        $exp->functions['haveaccess'] = 'doc_Folders::haveRightToFolder';
        $exp->functions['checkmovetime'] = 'doc_Threads::checkExpectationMoveTime';
        $exp->functions['getfolderopt'] = 'doc_Threads::getFolderOpt';

        $exp->DEF('dest=Преместване към', 'enum(exFolder=Съществуваща папка, 
                                                newCompany=Нова папка на фирма,
                                                newPerson=Нова папка на лице)', 'maxRadio=4,columns=1', '');
        
        $exp->ASSUME('#dest', "'exFolder'");

        if(count($selArr) > 1) {
            $exp->question("#dest", tr("Моля, посочете къде да бъдат преместени нишките") . ":", TRUE, 'title=' . tr('Преместване на нишки от документи'));
        } else {
            if($tRec->allDocCnt > 1) {
                $exp->question("#dest", tr("Моля, посочете къде да бъде преместена нишката") . ":", TRUE, 'title=' . tr('Преместване на нишка от документи'));
            } else {
                $exp->question("#dest", tr("Моля, посочете къде да бъде преместен документа") . ":", TRUE, 'title=' . tr('Преместване на документ в нова папка'));
            }
        }
        
        $exp->DEF('#folderId=Папка', 'key2(mvc=doc_Folders, moveThread=' . $threadId . ', where=#state !\\= \\\'rejected\\\', allowEmpty)', 'class=w100,mandatory');

        //$exp->OPTIONS("#folderId", "getFolderOpt(#threadId,#dest)", '#dest == "exFolder"');

        // Информация за фирма и представител
        $exp->DEF('#company', 'varchar(255)', 'caption=Фирма,width=100%,mandatory,remember=info');
        $exp->DEF('#salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение,notNull');
        $exp->DEF('#name', 'varchar(255)', 'caption=Имена,width=100%,mandatory,remember=info');
        
        // Адресни данни
        $exp->DEF('#country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,notNull,mandatory');
        $exp->DEF('#pCode', 'varchar(16)', 'caption=П. код,recently');
        $exp->DEF('#place', 'varchar(64)', 'caption=Град,width=100%');
        $exp->DEF('#address', 'varchar(255)', 'caption=Адрес,width=100%');
        
        // Комуникации
        $exp->DEF('#email', 'emails', 'caption=Имейл,width=100%,notNull');
        $exp->DEF('#tel', 'drdata_PhoneType', 'caption=Телефони,width=100%,notNull');
        $exp->DEF('#fax', 'drdata_PhoneType', 'caption=Факс,width=100%,notNull');
        $exp->DEF('#website', 'url', 'caption=Web сайт,width=100%,notNull');
        
        // Стойности по подразбиране при нова папка на фирма или лице
        $exp->ASSUME('#email', "getContragentData(#threadId, 'email')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#country', "getContragentData(#threadId, 'countryId')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#company', "getContragentData(#threadId, 'company')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#tel', "getContragentData(#threadId, 'tel')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#fax', "getContragentData(#threadId, 'fax')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#pCode', "getContragentData(#threadId, 'pCode')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#place', "getContragentData(#threadId, 'place')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#address', "getContragentData(#threadId, 'address')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#website', "getContragentData(#threadId, 'web')", "#dest == 'newCompany' || #dest == 'newPerson'");
        
        $exp->SUGGESTIONS('#company', "getContragentData(#threadId, 'companyArr')", "#dest == 'newCompany' || #dest == 'newPerson'");
        
        // Данъчен номер на фирмата
        $exp->DEF('#vatId', 'drdata_VatType', 'caption=Данъчен №,remember=info,width=100%');
        
        // Проверка за съвпадащи лица или фирми
        $exp->rule("#similarText", "checksimilarcompany(#dest, #company, #vatId, #country, #email)");
        $exp->rule("#similarText", "checksimilarperson(#dest, #name, #country, #email)");
        $exp->WARNING("=#similarText", '#similarText !== ""');
        
        $exp->question("#company, #country, #pCode, #place, #address, #email, #tel, #fax, #website, #vatId", tr("Моля, въведете контактните данни на фирмата") . ":", "#dest == 'newCompany'", 'title=' . tr('Преместване в папка на нова фирма'));
        
        $exp->question("#salutation, #name, #country, #pCode, #place, #address, #email, #tel, #website", tr("Моля, въведете контактните данни на лицето") . ":", "#dest == 'newPerson'", 'title=' . tr('Преместване в папка на ново лице'));
        
        $exp->rule('#folderId', "getPersonFolder(#salutation, #name, #country, #pCode, #place, #address, #email, #tel, #website)", TRUE);

        $exp->rule('#folderId', "getCompanyFolder(#company, #country, #pCode, #place, #address, #email, #tel, #fax, #website, #vatId)", TRUE);
        
        //$exp->ASSUME('#folderId', "doc_Threads_fetchField(#threadId, 'folderId')", TRUE);
        
        $exp->question("#folderId", tr("Моля, изберете папка") . ":", "#dest == 'exFolder'", 'title=' . tr('Избор на папка за нишката'));
        
        // От какъв клас е корицата на папката където е изходния тред?
        $exp->DEF('#moveRest=Преместване на всички', 'enum(no=Не,yes=Да)');
        $exp->rule('#askMoveRest', "getQuestionForMoveRest(#threadId)", TRUE);
        $exp->question("#moveRest", "=#askMoveRest", '#askMoveRest && #folderId', 'title=' . tr('Групово преместване'));
        $exp->rule("#moveRest", "'no'", '!(#askMoveRest)');
        $exp->rule("#moveRest", "'no'", '#Selected');
        $exp->rule("#haveAccess", "haveaccess(#folderId)");
        $exp->WARNING(tr("Нямате достъп до избраната папка! Сигурни ли сте, че искате да преместите нишката?"), '#haveAccess === FALSE');
        
        $exp->rule("#checkMoveTime", "checkmovetime(#threadId, #moveRest)");
        $exp->WARNING(tr("Операцията може да отнеме време!"), '#checkMoveTime === FALSE');
        $exp->ERROR(tr('Нишката не може да бъде преместена в избраната папка'), 'canMoveToFolder(#threadId, #folderId) === FALSE');
        
        $result = $exp->solve('#folderId,#moveRest,#checkMoveTime,#haveAccess');
        
        if($result == 'SUCCESS') {
            $threadId = $exp->getValue('threadId');
            $this->requireRightFor('single', $threadId);
            $folderId = $exp->getValue('folderId');
            $haveAccess = $exp->getValue('haveAccess');
            $selected = $exp->getValue('Selected');
            $moveRest = $exp->getValue('moveRest');
            $threadRec = doc_Threads::fetch($threadId);
            $time = doc_Threads::getExpectationMoveTime($threadId, $moveRest);
            
            $time = ceil($time);
            $time += 10;
            
            if ($time > ini_get('max_execution_time')) {
                core_App::setTimeLimit($time);
            }
            
            if($moveRest == 'yes') {
                $doc = doc_Containers::getDocument($threadRec->firstContainerId);
                $msgRec = $doc->fetch();
                $msgQuery = email_Incomings::getSameFirstDocumentsQuery($threadRec->folderId, array('fromEml' => $msgRec->fromEml));
                
                while($mRec = $msgQuery->fetch()) {
                    $selArr[] = $mRec->threadId;
                }
            } else {
                $selArr = arr::make($selected);
            }
            
            if(!count($selArr)) {
                $selArr[] = $threadId;
            }
            
            // Брояч на успешните премествания
            $successCnt = 0;

            // Брояч на грешките при преместване
            $errCnt = 0;
            
            $loggedToFolders = FALSE;
            $moveW = count($selArr) > 1 ? 'Преместени нишки' : 'Преместена нишка';
            
            foreach($selArr as $threadId) {
                try {
                    $this->move($threadId, $folderId);
					
                    if (!$loggedToFolders) {
                        doc_Folders::logWrite("{$moveW} от", $threadRec->folderId);
                        doc_Folders::logWrite("{$moveW} в", $folderId);
                        $loggedToFolders = TRUE;
                    }
                    
                    doc_Threads::logWrite('Преместена нишка', $threadId);
                    
                    $successCnt++;
                } catch (core_Exception_Expect $expect) { 
                    reportException($expect);
                    $errCnt++; 
                }
            }
            
            // Изходяща папка
            $folderFromRec = doc_Folders::fetch($threadRec->folderId);
            $folderFromRow = doc_Folders::recToVerbal($folderFromRec);
            
            // Входяща папка
            $folderToRec = doc_Folders::fetch($folderId);
            $folderToRow = doc_Folders::recToVerbal($folderToRec);
            
            $Recently = cls::get('recently_Values');

            $Recently->add('MoveFolders', $folderId);
            
            $message = '';
            
            if ($successCnt) {
                if ($successCnt == 1) {
                    $message = "|*{$successCnt} |нишка от|* {$folderFromRow->title} |е преместена в|* {$folderToRow->title}";
                } else {
                    $message = "|*{$successCnt} |нишки от|* {$folderFromRow->title} |са преместени в|* {$folderToRow->title}";
                }
            }
            
            if ($errCnt) {
                $message .= $message ? "<br> " : '';
                if ($errCnt == 1) {
                    $message .= "|Възникна|* {$errCnt} |грешка";
                } else {
                    $message .= "|Възникнаха|* {$errCnt} |грешки";
                }
                
                $exp->redirectMsgType = 'error';
            }
            
            $exp->message = tr($message);
            
            // Ако преместваме само една нишка
            if (count($selArr) == 1) {
            
                // Ако имаме права за нишката, в преместената папка
                if ($this->haveRightFor('single', $threadId)) {
                    
                    // Вземаме първия документ в нишката
                    $firstContainerId = $threadRec->firstContainerId;
                    
                    // Ако има такъв
                    if ($firstContainerId) {
                        
                        // Вземаме документа
                        $doc = doc_Containers::getDocument($firstContainerId);
                        
                        // Редиректваме към сингъла на документа
                        $exp->setValue('ret_url', toUrl(array($doc, 'single', $doc->that)));
                        
                        // Сетваме флага
                        $haveRightForSingle = TRUE;
                    }
                }
            }
            
            // Ако не е вдигнат флага - когато преместваме повече от една нишка или нямаме достъп до преместената нишка (когато е една)
            if (!$haveRightForSingle) {
                
                // Ако имаме достъп 
                if ($haveAccess){
                    
                    // да отидем в изходящата папка
                	$exp->setValue('ret_url', toUrl(array('doc_Threads', 'list', 'folderId' => $folderToRec->id)));
                } else {
                    
                    // Ако няма ret_url, да редиректне в папката, от която се мести    
                    $exp->setValue('ret_url', toUrl(array('doc_Threads', 'list', 'folderId' => $folderFromRec->id)));
                }
            }
        }
        
        // Поставя  под формата, първия постинг в треда
        // TODO: да се замени с интерфейсен метод
        if($threadId = $exp->getValue('threadId')) {
            $threadRec = self::fetch($threadId);
            $originTpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Първи документ в нишката") . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div>");
            $document = doc_Containers::getDocument($threadRec->firstContainerId);
            $docHtml = $document->getInlineDocumentBody();
            $originTpl->append($docHtml, 'DOCUMENT');
            
            if(!$exp->midRes) {
                $exp->midRes = new stdClass();
            }
            $exp->midRes->afterForm = $originTpl;
        }
        
        return $result;
    }
    
    
    /**
     * В кои папки може да бъде преместена нишката?
     */
    public static function getFolderOpt($threadId)
    {  
        $res = array();
        // $res = doc_Folders::makeArray4Select();

        $rec = doc_Threads::fetch($threadId);
        $doc = doc_Containers::getDocument($rec->firstContainerId);
        
        $query = doc_Folders::getQuery();
        $doc->getInstance()->restrictQueryOnlyFolderForDocuments($query);
        $query->orderBy('#last=DESC,#title=ASC');
        
        $contragentData = self::getContragentData($threadId);
 
        while($rec = $query->fetch()) {
            $res[$rec->id] = $rec->title;
        }
        
        $Recently = cls::get('recently_Values');
        $lastArr = $Recently->getSuggestions('MoveFolders');  
        $res1 = array();
        foreach($lastArr as $id) {
            $res1[$id] = $res[$id];
            unset($res[$id]);
        }
        $res1 += $res;
        
        return $res1;
    }




    /**
     * Връща стринг с подобните фирми
     * 
     * @param string $dest
     * @param string $name
     * @param string $vatId
     * @param string $country
     * @param string $email
     * 
     * @return string
     */
    public static function checkSimilarCompany($dest, $name, $vatId, $country, $email)
    {
        $resStr = crm_Companies::getSimilarWarningStr((object) array('name' => $name, 'vatId' => $vatId, 'country' => $country, 'email' => $email));
		
        if ($resStr) {
            $resStr = tr($resStr);
        }
        
        return $resStr;
    }
    
    
    /**
     * Връща стринг с подобните лица
     * 
     * @param string $dest
     * @param string $name
     * @param string $country
     * @param string $email
     * 
     * @return string
     */
    public static function checkSimilarPerson($dest, $name, $country, $email)
    {
        $resStr = crm_Persons::getSimilarWarningStr((object) array('name' => $name, 'country' => $country, 'email' => $email));
        
        if ($resStr) {
            $resStr = tr($resStr);
        }
        
        return $resStr;
    }
    
    
    /**
     * Можели нишката да бъде преместена в папката
     * 
     * @param int $threadId - ид на нишка
     * @param int $folderId - ид на папка
     * 
     * @return boolean
     */
    public static function canMoveToFolder($threadId, $folderId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	// Ако е зададено да не се може да се мести документа
    	if ($firstDoc->moveDocToFolder === FALSE) return FALSE;
    	
    	return (boolean)$firstDoc->getInstance()->canAddToFolder($folderId);
    }
    
    
    /**
     * Проверява времето за преместване дали ще е в границата
     * 
     * @param integer $threadId
     * @param string $moveRest
     * 
     * @return boolean
     */
    public static function checkExpectationMoveTime($threadId, $moveRest = 'no')
    {
        $maxTimeForMove = 5;
        
        if (self::getExpectationMoveTime($threadId, $moveRest) >= $maxTimeForMove) return FALSE;
        
        return TRUE;
    }
    
    
    /**
     * Връща предполагаемото време, което ще отнеме за преместване на нишките
     * 
     * @param integer $threadId
     * @param string $moveRest
     * 
     * @return double
     */
    public static function getExpectationMoveTime($threadId, $moveRest = 'no')
    {
        $timeFormMoveContainer = 0.006;
        $timeFormMoveThread = 0.02;
        
        $moveTime = 0;
        
        $threadRec = doc_Threads::fetch($threadId);
        $doc = doc_Containers::getDocument($threadRec->firstContainerId);
        
        if ($moveRest == 'yes') {
            $msgRec = $doc->fetch();
            $msgQuery = email_Incomings::getSameFirstDocumentsQuery($threadRec->folderId, array('fromEml' => $msgRec->fromEml));
        } else {
            $msgQuery = $doc->getQuery();
            $msgQuery->where(array("#threadId = '[#1#]'", $threadId));
        }
        
        $msgQuery->show('threadId');
        $msgQuery->groupBy('threadId');
        
        while ($mRec = $msgQuery->fetch()) {
            if (!$mRec->threadId) continue;
            
            $cQuery = doc_Containers::getQuery();
            $cQuery->where(array("#threadId = '[#1#]'", $mRec->threadId));
            $cQuery->show('id');
            
            if ($cCnt = $cQuery->count()) {
                $moveTime += $timeFormMoveContainer * $cCnt;
            }
            
            $moveTime += $timeFormMoveThread;
        }
        
        return $moveTime;
    }
    
    
    /**
     * Преместване на нишка от в друга папка.
     *
     * @param int $id key(mvc=doc_Threads)
     * @param int $destFolderId key(mvc=doc_Folders)
     * @return boolean
     */
    public static function move($id, $destFolderId)
    {
        // Подсигуряваме, че нишката, която ще преместваме, както и папката, където ще я 
        // преместваме съществуват.
        expect($currentFolderId = static::fetchField($id, 'folderId'));
        expect(doc_Folders::fetchField($destFolderId, 'id') == $destFolderId);
        
        // Извличаме doc_Cointaners на този тред
        /* @var $query core_Query */
        $query = doc_Containers::getQuery();
        $query->where("#threadId = {$id}");
        $query->show('id, docId, docClass');
        
        while ($rec = $query->fetch()) {

            $doc = doc_Containers::getDocument($rec->id);

            /*
             *  Преместваме оригиналния документ. Плъгина @link doc_DocumentPlg ще се погрижи да
             *  премести съответстващия му контейнер.
             */
            expect($rec->docId, $rec);
            $nRec = (object)array('id' => $rec->docId, 'folderId' => $destFolderId,);
            $doc->getInstance()->save($nRec,'id,folderId');
        }
        
        // Преместваме самата нишка
        if (doc_Threads::save(
                (object)array(
                    'id' => $id,
                    'folderId' => $destFolderId
                )
            )) {
                
                // Изчистваме нотификацията до потребители, които нямат достъп до нишката
                $urlArr = array('doc_Containers', 'list', 'threadId' => $id);
                $usersArr = bgerp_Notifications::getNotifiedUserArr($urlArr);
                $nRec = doc_Threads::fetch($id, '*', FALSE);
                
                if (!empty($usersArr)) {
                    foreach ((array)$usersArr as $userId => $hidden) {
                        
                        // Ако има права до сингъла
                        if (doc_Threads::haveRightFor('single', $nRec, $userId)) {
                            
                            // Ако е скрит, го показваме
                            if ($hidden == 'yes') {
                                
                                // Показваме
                                bgerp_Notifications::setHidden($urlArr, 'no', $userId);
                            }
                        } else {
                            
                            // Ако нямаме права и се показва 
                            if ($hidden == 'no') {
                                bgerp_Notifications::setHidden($urlArr, 'yes', $userId);
                            }
                        }
                    }
                }
                
                // Нотифицираме новата и старата папка за настъпилото преместване
                
                // $currentFolderId сега има една нишка по-малко
                doc_Folders::updateFolderByContent($currentFolderId);
                
                // $destFolderId сега има една нишка повече
                doc_Folders::updateFolderByContent($destFolderId);
                
                //
                // Добавяме нови правила за рутиране на базата на току-що направеното преместване.
                //
                // expect($firstContainerId = static::fetchField($id, 'firstContainerId'));
                // email_Router::updateRoutingRules($firstContainerId, $destFolderId);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getQuestionForMoveRest($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        $folderRec = doc_Folders::fetch($threadRec->folderId);
        $folderFromRow = doc_Folders::recToVerbal($folderRec);
        
        $doc = doc_Containers::getDocument($threadRec->firstContainerId);
        
        if($doc->className == 'email_Incomings') {
            
            $msgRec = $doc->fetch();
            
            $msgQuery = email_Incomings::getSameFirstDocumentsQuery($folderRec->id, array('fromEml' => $msgRec->fromEml));
            
            $msgQuery->show('id');
            
            $sameEmailMsgCnt = $msgQuery->count() - 1;
            
            $msgRow = $doc->recToVerbal($msgRec);
            
            if($sameEmailMsgCnt > 0) {
                if ($sameEmailMsgCnt == 1) {
                    $res = tr("|Желаете ли и останалата|* {$sameEmailMsgCnt} |нишка, започваща с входящ имейл от|* {$msgRow->fromEml}, |намираща се в|* {$folderFromRow->title} |също да бъде преместена|*?");
                } else {
                    $res = tr("|Желаете ли и останалите|* {$sameEmailMsgCnt} |нишки, започващи с входящ имейл от|* {$msgRow->fromEml}, |намиращи се в|* {$folderFromRow->title} |също да бъдат преместени|*?");
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Извлича първичния ключ на първия контейнер в нишка
     * 
     * @param int $id key(mvc=doc_Threads)
     * @return int key(mvc=doc_Containers)
     */
    public static function getFirstContainerId($id)
    {
        $rec = self::fetch($id);

        if($rec->firstContainerId) {

            return $rec->firstContainerId;
        }

        /* @var $query core_Query */
        $query = doc_Containers::getQuery();
        $query->where("#threadId = {$id}");
        $query->orderBy('createdOn', 'ASC');
        $query->limit(1);
        $query->show('id');
        $r = $query->fetch();
        
        return $r->id;
    }
    
    
    /**
     * Референция към първия документ в зададена нишка
     * 
     * @param int $id key(mvc=doc_Threads)
     * @return core_ObjectReference референция към документ
     */
    public static function getFirstDocument($id)
    {
        try{
        	$containerId = static::getFirstContainerId($id);
        	$firstDoc = doc_Containers::getDocument($containerId);
        } catch(core_exception_Expect $e){
        	
        	// Ако няма първи документ, връща NULL
        	return NULL;
        }
        
        return $firstDoc;
    }
    
    
    /**
     * Добавя нишка в опашката за опресняване на стат. информация.
     * 
     * Същинското опресняване ще случи при shutdown на текущото изпълнение, при това еднократно
     * за всяка нишка, независимо колко пъти е заявена за опресняване тя.
     *  
     * @param int $id key(mvc=doc_Threads)
     */
    public static function updateThread($id)
    {
        // Изкуствено създаваме инстанция на doc_Folders. Това гарантира, че ще бъде извикан
        // doc_Folders::on_Shutdown()
        cls::get('doc_Folders');
        
        self::$updateQueue[$id] = TRUE;
    }
    
    
    /**
     * Обновява информацията за дадена тема. Обикновено се извиква след промяна на doc_Containers
     * 
     * @param array|int $ids масив с ключ id на нишка или 
     */
    public static function doUpdateThread($ids = NULL)
    {
        if (!isset($ids)) {
            $ids = self::$updateQueue;
        }
        
        if (is_array($ids)) {
            foreach (array_keys($ids) as $id) {
                if (!is_int($id)) { 
                    continue; 
                }
                self::doUpdateThread($id);
            }
            return;
        }
        
        if (!$id = $ids) {
            return;
        }

        // Махаме id-то от бъдещо обовяване
        unset(self::$updateQueue[$id]);

        // Вземаме записа на треда
        $rec = self::fetch($id, NULL, FALSE);
        
        if (!$rec) {
        	wp($id);
        	
	        return;
    	}

        // Запазваме общия брой документи
        $exAllDocCnt = $rec->allDocCnt;
        
        self::prepareDocCnt($rec, $firstDcRec, $lastDcRec);
        
        // Попълваме полето за споделените потребители
        $rec->shared = keylist::fromArray(doc_ThreadUsers::getShared($rec->id));

        if($firstDcRec) {
            // Първи документ в треда
            $rec->firstContainerId = $firstDcRec->id;
            $rec->firstDocClass = $firstDcRec->docClass;
            $rec->firstDocId = $firstDcRec->docId;
            $rec->visibleForPartners = $firstDcRec->visibleForPartners;
            
            if (($firstDcRec->state == 'draft') || $firstDcRec->state == 'rejected') {
                
                // Ако не е партньор документа не е видим за партньори
                if (!core_Users::haveRole('partner', $firstDcRec->createdBy)) {
                    $rec->visibleForPartners = 'no';
                }
            }
            
            // Последния документ в треда
            if($lastDcRec->state != 'draft') {
                $rec->last = max($lastDcRec->createdOn, $lastDcRec->modifiedOn);
            } else {
                $rec->last = $lastDcRec->createdOn;
            }
            
            // Ако имаме добавяне/махане на документ от треда или промяна на състоянието към активно
            // тогава състоянието му се определя от последния документ в него
            if(($rec->allDocCnt != $exAllDocCnt) || ($rec->lastState && ($lastDcRec->state != $rec->lastState))) {
                // Ако състоянието не е draft или не е rejected
                if($lastDcRec && $lastDcRec->state != 'draft') {
                    $doc = doc_Containers::getDocument($lastDcRec->id);
                    $newState = $doc->getThreadState();
                    
                    if($newState) {
                        $rec->state = $newState;
                    }
                }
            }
            
            if ($lastDcRec) {
                
                // Състоянието на последния документ
                $rec->lastState = $lastDcRec->state;
                
                if (isset($lastDcRec->createdBy)) {
                    
                    // Създателя на последния документ
                    $rec->lastAuthor = $lastDcRec->createdBy;    
                }
            }
            
            // Когато има само един документ и той е оттеглен
            if (!isset($rec->lastAuthor) && $firstDcRec) {
                if (isset($firstDcRec->createdBy)) {
                    $rec->lastAuthor = $firstDcRec->createdBy;
                }
            }
            
            // Състоянието по подразбиране за последния документ е затворено
            if(!$rec->lastState) {
                $rec->lastState = 'closed';
            }
            
            // Състоянието по подразбиране за треда е затворено
            if(!$rec->state) {
                $rec->state = 'closed';
            }
            
            doc_Threads::save($rec, 'last, allDocCnt, partnerDocCnt, firstContainerId, state, shared, modifiedOn, modifiedBy, lastState, lastAuthor, firstDocClass, firstDocId, visibleForPartners');
         } else {
            // Ако липсват каквито и да е документи в нишката - изтриваме я
            self::delete($id);
        }
        
        doc_Folders::updateFolderByContent($rec->folderId);
    }
    
    
    /**
     * Помощна функция за изчисляване на броя на документите
     * 
     * @param stdObject $rec
     * @param NULL|stdObject $firstDcRec
     * @param NULL|stdObject $lastDcRec
     */
    protected static function prepareDocCnt(&$rec, &$firstDcRec, &$lastDcRec)
    {
        // Публични документи в треда
        $rec->partnerDocCnt = $rec->allDocCnt = 0;
        
        $firstDcRec = NULL;
        
        $dcQuery = doc_Containers::getQuery();
        $dcQuery->orderBy('#createdOn');
        $dcQuery->orderBy('#id'); // Ако датите съвпадат, гледаме по id
        
        while($dcRec = $dcQuery->fetch("#threadId = {$rec->id}")) {
        
            if(!$firstDcRec) {
                $firstDcRec = $dcRec;
            }
        
            // Не броим оттеглените документи
            if($dcRec->state != 'rejected') {
        
                // Преброяваме всичките документи и задържаме последния
                $lastDcRec = $dcRec;
                $rec->allDocCnt++;
        
                if($dcRec->visibleForPartners == 'yes') {
                    // Преброяваме партньорските документи и задържаме последния
                    $lastDcPartnerRec = $dcRec;
                    $rec->partnerDocCnt++;
                }
            }
        }
        
        // Ако първия документ не е видим за партньори, то нищо в тази нишка не е видимо за тях
        if($firstDcRec->visibleForPartners != 'yes') {
            $rec->partnerDocCnt = 0;
        }
    }

    /**
     * Отчита последно модифицаране на нишката към момента
     */
    public static function setModification($id)
    {
        $rec = self::fetch($id);
        $rec->modifiedOn = dt::now();
        $rec->modifiedBy = core_Users::getCurrent();
        self::save($rec, 'modifiedOn,modifiedBy');
    }
    

    /**
     * Оттегля цяла нишка, заедно с всички документи в нея
     * 
     * @param int $id
     */
    public static function rejectThread($id)
    {
        // Оттегляме записа в doc_Threads
        expect($rec = static::fetch($id));
            
        if ($rec->state == 'rejected') {
            
            return;
        }
        
        $rec->state = 'rejected';
        $rec->modifiedOn = dt::now();
        static::save($rec);

        // Оттегляме всички контейнери в нишката
        $rejectedIds = doc_Containers::rejectByThread($rec->id);
        
        // Добавяме и контейнера на първия документ в треда
        $rejectedIds[] = $rec->firstContainerId;
        
        // Обръщаме последователността на обратно
        $rejectedIds = array_reverse($rejectedIds);
        	
        // Ако има оттеглени контейнери с треда, запомняме ги, за да може при възстановяване да възстановим само тях
        $rec->rejectedContainersInThread = $rejectedIds;
        	
        static::save($rec, 'rejectedContainersInThread');
        
        self::invalidateDocumentCache($rec->id);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param doc_Threads $mvc
     * @param int $id - първичния ключ на направения запис
     * @param stdClass $rec - всички полета, които току-що са били записани
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
        if ($rec->folderId) {
            $Folders = cls::get('doc_Folders');
            if (Mode::is('isMigrate')) {
                $Folders->preventNotification[$rec->folderId] = $rec->folderId;
            }
        }
    }
    
    
    /**
     * Метод за спиране на контиращите документи в нишката
     * 
     * @param int $id
     * @return void
     */
    public static function stopDocuments($id)
    {
    	expect($rec = static::fetch($id));
    	if($rec->state == 'rejected') return;
    	
    	self::groupDocumentsInThread($rec->id, $contable, $notContable);
    	if(!count($contable)) return;
    	
    	foreach ($contable as $cRec){
    		if($cRec->state != 'active') continue;
    		if(!cls::load($cRec->docClass, TRUE)) continue;
    		$Class = cls::get($cRec->docClass);
    		$docRec = $Class->fetch($cRec->docId);
    		if($docRec->state == 'active'){
    			acc_Journal::deleteTransaction($cRec->docClass, $cRec->docId);
    			$docRec->state = 'stopped';
    		} else {
    			$docRec->brState = 'stopped';
    		}
    		
    		$Class->save($docRec, 'state,brState');
    	}
    }
    
    
    /**
     * Стартиране на контиращите документи в нишката
     * 
     * @param int $id
     * @return void
     */
    public static function startDocuments($id)
    {
    	expect($rec = static::fetch($id));
    	
    	// Намиране на всички спрени контиращи документи в нишката
    	self::groupDocumentsInThread($rec->id, $contable, $notContable, 'stopped');
    	if(!count($contable)) return;
    	
    	// За всеки
    	foreach ($contable as $cRec){
    		if(!cls::load($cRec->docClass, TRUE)) continue;
    		$Class = cls::get($cRec->docClass);
    		$docRec = $Class->fetch($cRec->docId);
    		
    		// Ако е спрян се активира, и се реконтира
    		if($docRec->state == 'stopped'){
    			$docRec->state = 'active';
    			$Class->save($docRec, 'state');
    			acc_Journal::saveTransaction($cRec->docClass, $cRec->docId);
    		} 
    	}
    }
    
    
    /**
     * Екшън за стартиране на бизнес документите в нишката
     */
    public function act_Startthread()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = self::fetch($id));
    	$firstDocument = doc_Threads::getFirstDocument($rec->id);
    	$this->requireRightFor('startthread', $rec);
    	
    	$returnUrl = array($firstDocument->getInstance(), 'single', $firstDocument->that);
    	if(!self::haveRightForAllDocs('restore', $id)){
    		followRetUrl($returnUrl, 'Нямате права да контирате част от документите в нишката', 'error');
    	}
    	
    	self::startDocuments($rec->id);
    	
    	return new redirect($returnUrl, 'Бизнес документите в нишката са успешно пуснати');
    }
    
    
    /**
     * Помощен метод намиращ контиращите и контиращите документи в нипката
     * 
     * @param mixed $id
     * @param array $contable
     * @param array $notContable
     * @param string $state
     * @param int|NULL $limit
     */
    public static function groupDocumentsInThread($id, &$contable = array(), &$notContable = array(), $state = 'active', $limit = NULL)
    {
    	$rec = static::fetchRec($id);
    	$classes = core_Classes::getOptionsByInterface('acc_TransactionSourceIntf');
    	
    	$cQuery = doc_Containers::getQuery();
    	$cQuery->where("#threadId = {$rec->id}");
    	
    	if(isset($limit)){
    		$cQuery->limit($limit);
    	}
    	
    	$cloneQuery = clone $cQuery;
    	$cloneQuery->where("#state = 'active' OR #state = 'pending' OR #state = 'waiting' OR #state = 'closed'");
    	$cloneQuery->notIn('docClass', array_keys($classes));
    	
    	$cQuery->in('docClass', array_keys($classes));
    	$cQuery->where("#state = '{$state}'");
    	
    	$contable = $cQuery->fetchAll();
    	$notContable = $cloneQuery->fetchAll();
    }
    
    
    /**
     * Възстановява цяла нишка, заедно с всички документи в нея 
     * 
     * @param int $id
     */
    public static function restoreThread($id)
    {
        // Възстановяваме записа в doc_Threads
        expect($rec = static::fetch($id));
        
        if ($rec->state != 'rejected') {
            
            return;
        }
        
        $rec->state = 'closed';
        static::save($rec);

        // Възстановяваме всички контейнери в нишката
        doc_Containers::restoreByThread($rec->id);
        
        if($rec->rejectedContainersInThread){
        	
        	// Зануляваме при нужда списъка с оттеглените ид-та
        	unset($rec->rejectedContainersInThread);
        	static::save($rec, 'rejectedContainersInThread');
        }
        
        self::invalidateDocumentCache($rec->id);
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery->where("#state = 'rejected'");
                // Показваме или само оттеглените или всички останали нишки
         	    $data->query->where("#state != 'rejected' OR #state IS NULL");
            }
        }
    }

    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {  
        
        // Бутони за разгледане на всички оттеглени тредове
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*', 'with_selected');
            $data->toolbar->addBtn('Всички', array($mvc, 'folderId' => $data->folderId), 'id=listBtn', 'ef_icon = img/16/application_view_list.png');
        } else {
        	$folderState = doc_Folders::fetchField($data->folderId, 'state');
        	if($folderState == 'closed'){
        		$data->toolbar->removeBtn('*');
        		if($mvc->hasPlugin('plg_Select')){
        			unset($data->listFields['_checkboxes']);
        		}
        	} else {
        		// Може да се добавя нов документ, само ако папката не е затворена
        		if(doc_Folders::haveRightFor('newdoc', $data->folderId)){
        			$data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'folderId' => $data->folderId), 'id=btnAdd', array('ef_icon'=>'img/16/star_2.png', 'title'=>'Създаване на нова тема в папката'));
        		}
        		self::addBinBtnToToolbar($data);
        		
        		// Ако има мениджъри, на които да се слагат бързи бутони, добавяме ги
            	$Cover = doc_Folders::getCover($data->folderId);
            	$managersIds = self::getFastButtons($Cover->getInstance(), $Cover->that);
        	    
            	$fState = doc_Folders::fetchField($data->folderId, 'state');
        	    if(count($managersIds) && ($fState != 'closed' && $fState != 'rejected')){
        	    	
        	    	// Всеки намерен мениджър го добавяме като бутон, ако потребителя има права
        			foreach ($managersIds as $classId){
        				$Cls = cls::get($classId);
        				if($Cls->haveRightFor('add', (object)array('folderId' => $data->folderId))){
        					$btnTitle = ($Cls->buttonInFolderTitle) ? $Cls->buttonInFolderTitle : $Cls->singleTitle; 
        					$data->toolbar->addBtn($btnTitle, array($Cls, 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE), "ef_icon = {$Cls->singleIcon},title=Създаване на " . mb_strtolower($Cls->singleTitle));
        				}
        			}
        		}
        	}
        }
        
        // Ако има права за настройка на папката, добавяме бутона
        $key = doc_Folders::getSettingsKey($data->folderId);
        $userOrRole = core_Users::getCurrent();
        if (doc_Folders::canModifySettings($key, $userOrRole)) {
            core_Settings::addBtn($data->toolbar, $key, 'doc_Folders', $userOrRole, 'Настройки', array('class' => 'fright', 'row' => 2, 'title'=>'Персонални настройки на папката'));
        }
    }
    
    
    /**
     * Добавя бутон за кошче към тулбара
     * 
     * @param stdClass $data
     * @return void
     */
    public static function addBinBtnToToolbar(&$data)
    {
    	$data->rejQuery->where("#folderId = {$data->folderId}");
    	
    	// Ако не се търси текст или документ, правим опит за по-бързо намиране на документите
    	if (!$data->listFilter->rec->search && !$data->listFilter->rec->documentClassId) {
    	    $fStatistic = doc_Folders::getStatistic($data->folderId);
    	     
    	    $visType = '_all';
    	    if (haveRole('partner')) {
    	        $visType = 'yes';
    	    }
    	     
    	    $rejCnt = 0;
    	     
    	    foreach ((array)$fStatistic[$visType]['rejected'] as $cnt) {
    	        $rejCnt += $cnt;
    	    }
    	    $data->rejectedCnt = $rejCnt;
    	} else {
    	    $data->rejectedCnt = $data->rejQuery->count();
    	}
    	
    	if($data->rejectedCnt) {
    		$curUrl = getCurrentUrl();
    		$curUrl['Rejected'] = 1;
    		if(isset($data->pager->pageVar)) {
    			unset($curUrl[$data->pager->pageVar]);
    		}
    	
    		$data->rejQuery->orderBy('modifiedOn', 'DESC');
    		$data->rejQuery->limit(1);
    		$lastRec = $data->rejQuery->fetch();
    		$color = dt::getColorByTime($lastRec->modifiedOn);
    	
    		$data->toolbar->addBtn("Кош|* ({$data->rejectedCnt})",
    		$curUrl, 'id=binBtn,class=fright,order=50' . (Mode::is('screenMode', 'narrow') ? ',row=2' : ''), "ef_icon = img/16/bin_closed.png,style=color:#{$color};");
    	}
    }
    
    
    /**
     * Връща масив с ид-та на мениджърите които ще бъдат бързи бутони в папката
     * 
     * @param int $folderId - ид на папката
     * @return array $res - намерените мениджъри
     */
    public static function getFastButtons($coverClass, $coverId)
    {
    	expect($Cover = cls::get($coverClass));
    	$managers = $Cover->getDocButtonsInFolder($coverId);
    
    	$res = array();
    	if(is_array($managers) && count($managers)){
    		foreach ($managers as $manager){
    			
    			// Проверяваме дали може да се зареди класа
    			if(cls::load($manager, TRUE)){
    				$Cls = cls::get($manager);
    				
    				if (!cls::haveInterface('doc_DocumentIntf', $Cls)) continue;
    				
    				$res[$Cls->getClassId()] = $Cls->getClassId();
    			}
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Извиква се след изчисляване на ролите необходими за дадено действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'open') {
            if($rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'close') {
            if($rec->state == 'opened') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'reject') {
            if($rec->state == 'opened' || $rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        

        if($action == 'restore') {
            if($rec->state == 'rejected') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }

        if($action == 'move') {
            $res = $mvc->getRequiredRoles('single', $rec, $userId);
        }

        if($action == 'single') {
            if(doc_Folders::haveRightToFolder($rec->folderId, $userId)) {
                $res = 'powerUser';
            } elseif(keylist::isIn($userId, $rec->shared)) {
                $res = 'powerUser';
            } else {
                $res = 'no_one';
            }
        }

        if($action == 'newdoc') {
            if($rec->state == 'opened' || $rec->state == 'closed') {
            	if(doc_Folders::fetchField($rec->folderId, 'state') != 'closed'){
            		$res = $mvc->getRequiredRoles('single', $rec, $userId);
            	} else {
            		$res = 'no_one';
            	}
            } else {
                $res = 'no_one';
            }
        }
        
        // Можели нишката да се стартира
        if($action == 'startthread' && isset($rec)){
        	$res = $mvc->getRequiredRoles('reject', $rec, $userId);
        	
        	// Трябва да не е оттеглена
        	if($rec->state == 'rejected'){
        		$res = 'no_one';
        	} else {
        		
        		// Имали контиращи спрени документи
        		self::groupDocumentsInThread($rec, $contable, $notContable, 'stopped', 1);
        		if(!count($contable)){
        			$res = 'no_one';
        		} else{
        			
        		}
        	}
        }
    }
    
    
    
    /**
     * Отваря треда
     */
    function act_Open()
    {
        if($selected = Request::get('Selected')) {
            
            foreach(arr::make($selected) as $id) {
                $R = cls::get('core_Request');
                Request::push(array('threadId' => $id, 'Selected' => FALSE));
                Request::forward();
                Request::pop();
            }
            
            followRetUrl();
        }
        
        expect($id = Request::get('threadId', 'int'));
        
        expect($rec = $this->fetch($id));
        $this->requireRightFor('single', $rec);
        expect(doc_Folders::fetchField($rec->folderId, 'state') != 'closed');
        
        $rec->state = 'opened';
        
        $this->save($rec, 'state');
        
        $this->updateThread($rec->id);
        
        $this->logWrite('Отвори нишка', $id);
        
        return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
    }
    
    
    /**
     * Затваря треда
     */
    function act_Close()
    {
        if($selected = Request::get('Selected')) {
            
            foreach(arr::make($selected) as $id) {
                $R = cls::get('core_Request');
                Request::push(array('threadId' => $id, 'Selected' => FALSE));
                Request::forward();
                Request::pop();
            }
            
            followRetUrl();
        }
        
        expect($id = Request::get('threadId', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('single', $rec);
        expect(doc_Folders::fetchField($rec->folderId, 'state') != 'closed');
        
        $rec->state = 'closed';
        
        $this->save($rec);
        
        $this->updateThread($rec->id);
        
        $this->logWrite('Затвори нишка', $id);
        
        return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
    }
    
    
    /**
     * Намира контрагента с който се комуникира по тази нишка
     * Връща данните, които са най - нови и с най - много записи
     */
    static function getContragentData($threadId, $field = NULL)
    {
        static $cache;
        
        if(!$bestContragentData = $cache[$threadId]) {
            $query = doc_Containers::getQuery();
            $query->where("#state != 'rejected'");
            $query->where("#threadId = '{$threadId}'");
            $query->orderBy('createdOn', 'DESC');
            
            // Текущо най-добрата оценка за данни на контрагент
            $bestRate = 0;
            
            while ($rec = $query->fetch()) {
                
                $className = Cls::getClassName($rec->docClass);
                
                if (cls::haveInterface('doc_ContragentDataIntf', $className)) {
                    
                    $contragentData = $className::getContragentData($rec->docId);
                    
                    $rate = self::calcPoints($contragentData);
                    
                    // Даваме предпочитания на документите, създадени от потребители на системата
                    if($rec->createdBy >= 0) {
                        $rate = $rate * 10;
                    }
                    
                    if($rate > $bestRate) {
                        $bestContragentData = clone($contragentData);
                        $bestRate = $rate;
                    }
                }
            }
            
            // Вземаме данните на потребителя от папката
            // След като приключим обхождането на треда
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
            
            $contragentData = doc_Folders::getContragentData($folderId);
            
            if($contragentData) {
                $rate = self::calcPoints($contragentData) + 4;
            } else {
                $rate = 0;
            }
            
            if($rate > $bestRate) {
                if($bestContragentData->company == $contragentData->company) {
                    foreach(array('tel', 'fax', 'email', 'web', 'address', 'person') as $part) {
                        if($bestContragentData->{$part}) {
                            setIfNot($contragentData->{$part}, $bestContragentData->{$part});
                        }
                    }
                }
                
                $bestContragentData = $contragentData;
                $bestRate = $rate;
            }
            
            // Попълваме вербалното или индексното представяне на държавата, ако е налично другото
            if($bestContragentData->countryId && !$bestContragentData->country) {
                
                // Ако езика е на български
                if (core_Lg::getCurrent() == 'bg') {
                    $bestContragentData->country = drdata_Countries::fetchField($bestContragentData->countryId, 'commonNameBg');
                } else {
                    $bestContragentData->country = drdata_Countries::fetchField($bestContragentData->countryId, 'commonName');
                }
            }
            
            // Попълваме вербалното или индексното представяне на фирмата, ако е налично другото
            if($bestContragentData->companyId && !$bestContragentData->company) {
                $bestContragentData->company = crm_Companies::fetchField($bestContragentData->companyId, 'name');
            }
            
            // Попълваме вербалното или индексното представяне на държавата, ако е налично другото
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("LOWER(#commonName) LIKE '%[#1#]%'", mb_strtolower($bestContragentData->country)), 'id');
            }
            
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("LOWER(#formalName) LIKE '%[#1#]%'", mb_strtolower($bestContragentData->country)), 'id');
            }
            
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("LOWER(#commonNameBg) LIKE '%[#1#]%'", mb_strtolower($bestContragentData->country)), 'id');
            }
            
            $cache[$threadId] = $bestContragentData;
        }
        
        if($field) {
            return $bestContragentData->{$field};
        } else {
            return $bestContragentData;
        }
    }
    
    
    /**
     * Изчислява точките (рейтинга) на подадения масив
     */
    static function calcPoints($data)
    {
        $dataArr = (array) $data;
        $points = 0;
        
        foreach($dataArr as $key => $value) {
            if(!is_scalar($value) || empty($value)) continue;
            $len = max(0.5, min(mb_strlen($value) / 20, 1));
            $points += $len;
        }
        
        if($dataArr['company']) $points += 3;
        
        return $points;
    }
    
    
    /**
     * Показва меню от възможности за добавяне на нови документи към посочената нишка
     * Очаква folderId
     */
    function act_ShowDocMenu()
    {
        expect($folderId = Request::get('folderId', 'int'));
        
        doc_Folders::requireRightFor('newdoc', $folderId);
        
        $rec = (object) array('folderId' => $folderId);
        
        $tpl = doc_Containers::getNewDocMenu($rec);
       	
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Добавя към заявка необходимите условия, така че тя да връща само достъпните нишки.
     *
     * В резултат заявката ще селектира само достъпните за зададения потребител нишки които са
     * в достъпни за него папки (@see doc_Folders::restrictAccess())
     *
     * @param core_Query $query
     * @param int $userId key(mvc=core_Users) текущия по подразбиране
     * @param boolean $viewAccess
     */
    static function restrictAccess($query, $userId = NULL, $viewAccess = FALSE)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        doc_Folders::restrictAccess($query, $userId, $viewAccess);
        
        if ($query->mvc->className != 'doc_Threads') {
            // Добавя необходимите полета от модела doc_Threads
            $query->EXT('threadShared', 'doc_Threads', 'externalName=shared,externalKey=threadId');
        } else {
            $query->XPR('threadShared', 'varchar', '#shared');
        }
        
        $query->orWhere("LOCATE('|{$userId}|', #threadShared)");
    }
    
    
    /**
     * Връща езика на нишката
     * 
     * Първо проверява в обръщенията, после в контейнера
     *
     * @param int $id - id' то на нишката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        // Ако няма стойност, връщаме
        if (!$id) return ;
        
        // Записа на нишката
        $threadRec = doc_Threads::fetch($id);
        
        // id' то на контейнера на първия документ в треда
        $firstContId = $threadRec->firstContainerId;
        
        // Ако няма id на първия документ
        if (!$firstContId) return ;
        
        // Връщаме езика на контейнера
        return doc_Containers::getLanguage($firstContId);
    }

    
    /**
     * Връща титлата на нишката, която е заглавието на първия документ в нишката
     * 
     * @param integer $id
     * @param boolean $verbal - Дали да се върне вербалната стойност
     */
    static function getThreadTitle($id, $verbal=TRUE)
    {
        $rec = self::fetch($id);
        
        // Ако няма първи контейнер
        // При директно активиране на първия документ
        if (!($cid = $rec->firstContainerId)) {
            
            // Вземаме id' то на записа
            $cid = doc_Containers::fetchField("#threadId = '{$rec->id}'");
        }
        
        $document = doc_Containers::getDocument($cid);
        $docRow = $document->getDocumentRow();  
        
        if ($verbal) {
            $title = $docRow->title;
        } else {
            $title = $docRow->recTitle;
        }
        
        return $title;
    }
    
    /**
     * Връща линка на папката във вербален вид
     * 
     * @param array $params - Масив с частите на линка
     * @param $params['Ctr'] - Контролера
     * @param $params['Act'] - Действието
     * @param $params['threadId'] - id' то на нишката
     * 
     * @return core_ET|FALSE - Линк
     */
    static function getVerbalLink($params)
    {
        // Проверяваме дали е число
        if (!is_numeric($params['threadId'])) return FALSE;
        
        // Записите за нишката
        $rec = static::fetch($params['threadId']);
        
        $haveRight = static::haveRightFor('single', $rec);

        if (!$haveRight && strtolower($params['Ctr']) == 'colab_threads') {
            if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab')) {
                $haveRight = colab_Threads::haveRightFor('single', $rec);
            }
        }
        
        // Проверяваме дали има права
        if (!$rec || !$haveRight) return FALSE;
            
        // Инстанция на първия документ
        $docProxy = doc_Containers::getDocument($rec->firstContainerId);
        
        // Вземаме колоните на документа
        $docRow = $docProxy->getDocumentRow();
        
        // Ескейпваме заглавието
        $title = $docRow->title;

        // Ако мода е xhtml
        if (Mode::is('text', 'xhtml')) {
            
            $res = new ET("<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> [#1#] </span>", $title);
        } elseif (Mode::is('text', 'plain')) {
            
            // Ескейпваме плейсхолдърите и връщаме титлата
            $res = core_ET::escape($title);
        } else {
            
            // Атрибути на линка
            $attr = array();
            $attr['ef_icon'] = $docProxy->getIcon();    
            $attr['target'] = '_blank'; 
            
            // Създаваме линк
            $res = ht::createLink($title, $params, NULL, $attr);  
        }
        
        return $res;
    }
    
    
    /**
     * Прави широчината на колонката със заглавието на треда да не се свива под 240px
     */
    static function on_AfterPrepareListFields($mvc, $res, $data)
    {
        $data->listFields['title'] = "|*<div style='min-width:240px'>|" . $data->listFields['title'] . '|*</div>';
    }
    
    
    /**
     * Връща ключа за персонална настройка
     * 
     * @param integer $id
     * 
     * @return string
     */
    static function getSettingsKey($id)
    {
        $key = 'doc_Threads::' . $id;
        
        return $key;
    }
    
    
    /**
     * Може ли текущия потребител да пороменя сетингите на посочения потребител/роля?
     * 
     * @param string $key
     * @param integer|NULL $userOrRole
     * @see core_SettingsIntf
     */
    static function canModifySettings($key, $userOrRole=NULL)
    {
        // За да може да промени трябва да има достъп до сингъла на нишката
        // Да променя собствените си настройки или да е admin|ceo
        
        list(, $id) = explode('::', $key);
        
        $currUser = core_Users::getCurrent();
        
        if (!doc_Threads::haveRightFor('single', $id, $currUser)) return FALSE;
        
        if (!$userOrRole) return TRUE;
        
        if ($currUser == $userOrRole) {
            
            return TRUE;
        }
        
        if (haveRole('admin, ceo', $currUser)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    
    /**
     * Подготвя формата за настройки
     * 
     * @param core_Form $form
     * @see core_SettingsIntf
     */
    function prepareSettingsForm(&$form)
    {
        // Задаваме таба на менюто да сочи към документите
        Mode::set('pageMenu', 'Документи');
        Mode::set('pageSubMenu', 'Всички');
        $this->currentTab = 'Теми';
        
        // Вземаме id на папката от ключа
        list(, $threadId) = explode('::', $form->rec->_key);
        
        // Определяме заглавито
        $rec = $this->fetch($threadId);
        $row = $this->recToVerbal($rec, 'title');
        $form->title = 'Настройка на|* ' . $row->title;
        
        // Добавяме функционални полета
        $form->FNC('notify', 'enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване при->Нов документ, input=input');
        
        $form->setDefault('notify', 'default');
        
        // Сетваме стринг за подразбиране
        $defaultStr = 'По подразбиране|*: ';
        
        // Ако сме в мобилен режим, да не е хинт
        $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
        
        // Сетваме стойност по подразбиране
        $form->setParams('notify', array($paramType => $defaultStr . '|Винаги'));
    }
    
    
    /**
     * Проверява формата за настройки
     * 
     * @param core_Form $form
     * @see core_SettingsIntf
     */
    function checkSettingsForm(&$form)
    {
        
        return ;
    }
    
    
    /**
     * Преди подготвяне на пейджъра, ако има персонализация да се използва
     * 
     * @param doc_Threads $mvc
     * @param object $res
     * @param object $data
     */
    function on_BeforePrepareListPager($mvc, &$res, &$data)
    {
        // id на папката
        $folderId = Request::get('folderId', 'int');
        
        $key = doc_Folders::getSettingsKey($folderId);
        $vals = core_Settings::fetchKey($key);
        
        // Ако е зададено да се страницира
        if ($vals['perPage']) {
            
            // Променяме броя на страниците
            $mvc->listItemsPerPage = $vals['perPage'];
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	self::invalidateDocumentCache($rec->id);
    }
    
    
    /**
     * Инвалидиране на кеша за видовете документи в папката
     */
    private static function invalidateDocumentCache($id)
    {
        if (Mode::is('isMigrate')) {
            
            return;
        }

    	// Изтриваме от кеша видовете документи в папката и в коша и
    	$folderId = self::fetchField($id, 'folderId');
    	core_Cache::remove("doc_Folders", "folder{$folderId}");
    	core_Cache::remove("doc_Folders", "visibleDocumentsInFolder{$folderId}");
    }
    
    
    /**
     * Вика се по крон и изтрива оттеглените нишки и одкументи
     * Ако са оттеглени преди константа, не са използвани в някой документ и има само един документ в нишката
     */
    public function cron_DeleteThread()
    {
        $query = self::getQuery();
        
        $from = NULL;
        $to = dt::now();
        doc_Folders::prepareRepairDateQuery($query, $from, $to, doc_Setup::get('REPAIR_DELAY'), 'modifiedOn');
        
        $delFrom = dt::subtractSecs(doc_Setup::get('DELETE_REJECTED_THREADS_PERIOD'));
        $query->where(array("#modifiedOn <= '[#1#]'", $delFrom));
        
        $query->where("#state = 'rejected'");
        
        $query->orderBy('modifiedOn', 'DESC');
        
        $delCnt = 0;
        while ($rec = $query->fetch()) {
            $cQuery = doc_Containers::getQuery();
            $cQuery->where(array("#threadId = '[#1#]'", $rec->id));
            
            $cQuery->limit(2);
            
            if ($cQuery->count() != 1) continue ;
            
            $cQuery->orderBy('createdOn', 'DESC');
            
            $cRec = $cQuery->fetch();
            
            // Ако записа не е първия документ в нишката
            if ($cRec->id != $rec->firstContainerId) continue;
            
            // Ако документа е използван някъде
            if (doclog_Used::getUsedCount($cRec->id)) continue;
            
            $delFlag = FALSE;
            
            // Изтриваме записа в модела на документа
            if ($cRec->docId && $cRec->docClass && cls::load($cRec->docClass, TRUE)) {
                try {
                    $doc = doc_Containers::getDocument($cRec->id);
                    
                    // Да не може да се изтриват всички документи
                    if ($doc && $doc->deleteThreadAndDoc) {
                        $delFlag = TRUE;
                    }
                } catch (ErrorException $e) {
                    reportException($e);
                }
                
                if ($delFlag) {
                    try {
                        $delMsg = 'Изтрит оттеглен документ';
                    	
                        if ($doc->instance instanceof core_Master) {
                    	
                            $dArr = arr::make($doc->details, TRUE);
                    		
                            // Изтирваме детайлите за документа
                            if (!empty($dArr)) {
                    		
                                $delDetCnt = 0;
                    			
                                foreach ($dArr as $detail) {
                                    if (!cls::load($detail, TRUE)) continue;
                    				
                                    $detailInst = cls::get($detail);
                                    if (!($detailInst->Master instanceof $doc->instance)) continue;
                    				
                                    if ($detailInst->masterKey) {
                                        $delDetCnt += $detailInst->delete(array("#{$detailInst->masterKey} = '[#1#]'", $doc->that));
                                    }
                                }
                    			
                                $delMsg = "Изтрит оттеглен документ и детайлите към него ({$delDetCnt})";
                            }
                        }
                    	
                        $doc->instance->logInfo($delMsg, $doc->that);
                    
                        $doc->delete();
                    } catch (ErrorException $e) {
                        reportException($e);
                    } 
                }
            }
            
            if ($delFlag) {
                // Изтриваме нишката
                self::logInfo('Изтрита оттеглена нишка', $rec->id);
                self::delete($rec->id);
                
                // Изтриваме записа за използвани файлове в папката
                doc_Files::delete(array("#containerId = '[#1#]'", $cRec->id));
                
                // Изтриваме документа
                doc_Containers::logInfo('Изтрит оттеглен документ', $cRec->id);
                doc_Containers::delete($cRec->id);
                
                $delCnt++;
            }
        }
        
        return "Изтрити записи: " . $delCnt; 
    }
    
    
    /**
     * Връща хеша за листовия изглед. Вика се от bgerp_RefreshRowsPlg
     *
     * @param string $status
     *
     * @return string
     * @see plg_RefreshRows
     */
    public static function getContentHash_(&$status)
    {
        doc_Folders::getContentHash_($status);
        
        // Премахваме ненужните класове, при промяната на които да не се обновява
        $status = preg_replace('/(class\s*=\s*)(\'|")(.*?)\s*(tSighted|tUnsighted|active|inactive)\s*(.*?)(\'|")/i', '$1$2$3$5$6', $status);
    }
    
    
    /**
     * Дали потребителя има права за екшъна за всички документи в нишката
     * 
     * @param string $action      - екшън
     * @param int $threadId       - ид на тред
     * @param string|NULL $userId - ид на потребител, или ако няма текущия
     * @return boolean            - резултат
     */
    public static function haveRightForAllDocs($action, $threadId, $userId = NULL)
    {
    	expect(in_array($action, array('reject', 'restore')));
    	
    	if(!$userId){
    		$userId = core_Users::getCurrent();
    	}
    	
    	// Намиране на всички документи в нишката
    	$res = TRUE;
    	$cQuery = doc_Containers::getQuery();
    	$cQuery->where("#threadId = {$threadId}");
    	if($action == 'reject'){
    		$cQuery->where("#state != 'rejected'");
    	} else {
    		$cQuery->where("#state = 'rejected'");
    	}
    	
    	// Проверка за всички документи в нишката дали могат да се $action
    	$cQuery->show('docClass,docId');
    	while($cRec = $cQuery->fetch()){
    		if(!cls::get($cRec->docClass)->haveRightFor($action, $cRec->docId, $userId)){
    			$res = FALSE;
    		}
    	}
    	
    	return $res;
    }
}
