<?php


/**
 * Последни документи и папки, посетени от даден потребител
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Последни документи и папки
 */
class bgerp_Recently extends core_Manager
{
    /**
     * Максимална дължина на показваните заглавия
     */
    const maxLenTitle = 120;
    
    
    /**
     * @see bgerp_RefreshRowsPlg
     */
    public $bgerpRefreshRowsTime = 15000;
    
    
    /**
     * Необходими мениджъри
     */
    public $loadList = 'bgerp_Wrapper, plg_RowTools, plg_GroupByDate, plg_Search, bgerp_RefreshRowsPlg';
    
    
    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    public $groupByDateField = 'last';
    
    
    /**
     * Заглавие
     */
    public $title = 'Последни';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Как се казва полето за пълнотекстово търсене
     */
    public $searchInputField = 'recentlySearch';
    
    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 100000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'last';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('type', 'enum(folder,document)', 'caption=Тип, mandatory');
        $this->FLD('objectId', 'int', 'caption=Id');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('last', 'datetime(format=smartTime)', 'caption=Последно');
        $this->FLD('hidden', 'enum(no,yes)', 'caption=Скрито,notNull');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка, input=none');
        
        $this->setDbUnique('type, objectId, userId');
        $this->setDbIndex('userId');
        $this->setDbIndex('last');
    }
    
    
    /**
     * Добавя известие за настъпило събитие
     *
     * @param string   $type     - folder,document
     * @param int      $objectId
     * @param int|NULL $userId
     * @param string   $hidden   - yes/no
     * @param int|NULL $threadId
     */
    public static function add($type, $objectId, $userId = null, $hidden = 'no', $threadId = null)
    {
        // Не добавяме от опресняващи ajax заявки
        if (Request::get('ajax_mode')) {
            
            return;
        }
        
        $rec = new stdClass();
        
        $rec->type = $type;
        $rec->objectId = $objectId;
        $rec->userId = $userId ? $userId : core_Users::getCurrent();
        $rec->last = dt::verbal2mysql();
        $rec->hidden = $hidden;
        
        if (!$threadId && $objectId && ($type == 'document')) {
            $rec->threadId = doc_Containers::fetchField($objectId, 'threadId');
        }
        
        $rec->id = bgerp_Recently::fetchField("#type = '{$type}'  AND #objectId = {$objectId} AND #userId = {$rec->userId}");
        
        bgerp_Recently::save($rec);
    }
    
    
    /**
     * Преди запис на документ, преизчисляваме стойността на threadId
     *
     * @param bgerp_Recently $mvc
     * @param stdClass       $res
     * @param stdClass       $rec
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        if (!$rec->threadId && $rec->objectId && ($rec->type == 'document')) {
            $rec->threadId = doc_Containers::fetchField($rec->objectId, 'threadId');
        }
    }
    
    
    /**
     * Скрива посочените записи
     * Обикновено след Reject
     */
    public static function setHidden($type, $objectId, $hidden = 'yes', $userId = null)
    {
        $query = self::getQuery();
        
        $query->where("#type = '{$type}'  AND #objectId = ${objectId}");
        
        if ($userId) {
            $query->where("#userId = '{$userId}'");
        }
        
        while ($rec = $query->fetch()) {
            $rec->hidden = $hidden;
            $rec->last = dt::verbal2mysql();
            self::save($rec);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($rec->type == 'folder') {
            try {
                $folderRec = doc_Folders::fetch($rec->objectId);
                $folderRow = doc_Folders::recToVerbal($folderRec);
                
                $attr = array();
                if ($folderRec->last > $mvc->getLastFolderSee($folderRec->id, null, false)) {
                    $attr['class'] .= " tUnsighted";
                }
                
                $row->title = doc_Folders::getFolderTitle($folderRec, null, $attr);
                $state = $folderRec->state;
            } catch (core_exception_Expect $ex) {
                $row->title = tr("Проблемна папка|* № {$rec->objectId}");
            }
        } elseif ($rec->type == 'document') {
            try {
                $threadId = $rec->threadId;
                $threadRec = null;
                if ($threadId) {
                    $threadRec = doc_Threads::fetch($threadId);
                }
                $docProxy = doc_Containers::getDocument($rec->objectId);
                $docRow = $docProxy->getDocumentRow();
                $docRec = $docProxy->fetch();
                if (!$threadRec) {
                    $threadRec = doc_Threads::fetch($docRec->threadId);
                }
                $state = $threadRec->state;
                
                $attr = array();
                $attr['class'] .= "state-{$state}";
                
                $modOn = $docRec->modifiedOn;
                if ($docRec->threadId) {
                    $tRec = doc_Threads::fetch($docRec->threadId);
                    $modOn = max(array($modOn, $tRec->last));
                }
                
                if ($modOn > $mvc->getLastDocumentSee($docRec->containerId, null, false)) {
                    $attr['class'] .= " tUnsighted";
                }
                
                $attr = ht::addBackgroundIcon($attr, $docProxy->getIcon($docRec->id));
                
                if (mb_strlen($docRow->title) > self::maxLenTitle) {
                    $attr['title'] = '|*' . $docRow->title;
                }
                
                // Ако имамем права, тогава генерирам линк
                if ($docProxy->haveRightFor('single') || doc_Threads::haveRightFor('single', $docRec->threadId)) {
                    $linkUrl = array($docProxy->getInstance(), 'single',
                        'id' => $docRec->id);
                }
                
                $row->title = ht::createLink(
                    str::limitLen($docRow->title, self::maxLenTitle, 20, ' ... ', true),
                    $linkUrl,
                    null,
                    $attr
                );
            } catch (core_exception_Expect $ex) {
                $row->title = tr("Проблемен контейнер|* № {$rec->objectId}");
            }
        }
        
        if ($state == 'opened') {
            $row->title = new ET("<span class='state-opened-link'>[#1#]</span>", $row->title);
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене, това са името на
     * документа или папката
     */
    public function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $objectTitle = $mvc->getObjectTitle($rec);
        
        $res = plg_Search::normalizeText($objectTitle);
        $res = ' ' . $res;
    }
    
    
    /**
     * Взима заглавието на обекта
     */
    public function getObjectTitle($rec)
    {
        try {
            if ($rec->type == 'folder') {
                $folderRec = doc_Folders::fetch($rec->objectId);
                $objectTitle = $folderRec->title;
            } else {
                $docProxy = doc_Containers::getDocument($rec->objectId);
                $docRow = $docProxy->getDocumentRow();
                $objectTitle = $docRow->title;
            }
        } catch (core_exception_Expect $ex) {
            $objectTitle = '';
        }
        
        return $objectTitle;
    }
    
    
    /**
     * Връща кога за последен път потребителя е виждал този документ
     *
     * @param bool $isFirstContainerId дали първият аргумент е ид на първи контейнер в нишка
     */
    public static function getLastDocumentSee($doc, $userId = null, $isFirstContainerId = false)
    {
        if (!$isFirstContainerId) {
            if (is_object($doc)) {
                $cRec = $doc;
            } else {
                expect(is_numeric($doc));
                $cRec = doc_Containers::fetch($doc);
            }
            
            if (!$cRec->threadId) {
                
                return;
            }
            
            $fid = doc_Threads::getFirstContainerId($cRec->threadId);
        } else {
            $fid = $doc;
        }
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($fid && $userId) {
            $lastTime = bgerp_Recently::fetchField("#type = 'document' AND #objectId = {$fid} AND #userId = {$userId}", 'last');
        }
        
        return $lastTime;
    }
    
	
    /**
     * Връща кога за последен път потребителя е виждал този документ
     * 
     * @param integer $folderId
     * @param null|integer $userId
     * 
     * @return string
     */
    public static function getLastFolderSee($folderId, $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $lastTime = bgerp_Recently::fetchField(array("#type = 'folder' AND #objectId = '[#1#]' AND #userId = '[#2#]'", $folderId, $userId), 'last');
        
        return $lastTime;
    }
    
    
    /**
     * Екшън за рендиране блок с последни за текущия
     */
    public function act_Render()
    {
        requireRole('powerUser');
        
        $userId = core_Users::getCurrent();
        
        return static::render($userId);
    }
    
    
    /**
     * Рендира блок с последните документи и папки, посетени от даден потребител
     *
     * @deprecated
     */
    public static function render_($userId = null)
    {
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $Recently = cls::get('bgerp_Recently');
        
        // Намираме времето на последния запис
        $query = $Recently->getQuery();
        $query->where("#userId = ${userId}");
        $query->limit(1);
        $query->orderBy('#last', 'DESC');
        $lastRec = $query->fetch();
        $key = md5($userId . '_' . Request::get('ajax_mode') . '_' . Mode::get('screenMode') . '_' . Request::get('P_bgerp_Recently') . '_' . Request::get('recentlySearch') . '_' . core_Lg::getCurrent());
        list($tpl, $createdOn) = core_Cache::get('RecentDoc', $key);
        
        if (!$tpl || $createdOn != $lastRec->last) {
            
            // Създаваме обекта $data
            $data = new stdClass();
            
            // Създаваме заявката
            $data->query = $Recently->getQuery();
            
            // Подготвяме полетата за показване
            $data->listFields = 'last,title';
            
            $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
            $data->query->orderBy('last=DESC');
            
            // Подготвяме филтрирането
            $Recently->prepareListFilter($data);
            
            // Подготвяме навигацията по страници
            $Recently->prepareListPager($data);
            
            // Подготвяме записите за таблицата
            $Recently->prepareListRecs($data);
            
            // Подготвяме редовете на таблицата
            $Recently->prepareListRows($data);
            
            if (!Mode::is('screenMode', 'narrow')) {
                // Подготвяме заглавието на таблицата
                $data->title = tr('Последно||Recently');
            }
            
            // Подготвяме лентата с инструменти
            $Recently->prepareListToolbar($data);
            
            // Рендираме изгледа
            $tpl = $Recently->renderPortal($data);
            
            core_Cache::set('RecentDoc', $key, array($tpl, $lastRec->last), doc_Setup::get('CACHE_LIFETIME'));
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендира блок в портала с последните документи и папки, посетени от даден потребител
     *
     * @deprecated
     */
    public function renderPortal($data)
    {
        $Recently = cls::get('bgerp_Recently');
        
        // Ако се вика по AJAX
        if (!Request::get('ajax_mode')) {
            $divId = $Recently->getDivId();
            
            $tpl = new ET("
                <div class='clearfix21 portal'>
                <div class='legend'><div style='float:left'>[#PortalTitle#]</div>
                [#ListFilter#]<div class='clearfix21'></div></div>
                
                <div id='{$divId}'>
                    <!--ET_BEGIN PortalTable-->
                        [#PortalTable#]
                    <!--ET_END PortalTable-->
                </div>
                
                [#PortalPagerBottom#]
                </div>
            ");
            
            // Попълваме титлата
            $tpl->append($data->title, 'PortalTitle');
            
            if ($data->listFilter) {
                $tpl->append($data->listFilter->renderHtml(), 'ListFilter');
            }
            
            // Попълваме долния страньор
            $tpl->append($Recently->renderListPager($data), 'PortalPagerBottom');
        } else {
            $tpl = new ET('[#PortalTable#]');
        }
        
        // Попълваме таблицата с редовете
        $tpl->append($Recently->renderListTable($data), 'PortalTable');
        
        return $tpl;
    }
    
    
    /**
     * Игнорираме pager-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListPager($mvc, &$res, $data)
    {
        if ($data->usePortalArrange !== false) {
            // Задаваме броя на елементите в страница
            $portalArrange = core_Setup::get('PORTAL_ARRANGE');
            
            if ($portalArrange == 'recentlyNotifyTaskCal') {
                $mvc->listItemsPerPage = 20;
            } else {
                $mvc->listItemsPerPage = 10;
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
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->view = 'horizontal';
        
        if (strtolower(Request::get('Act')) == 'show') {
            $data->listFilter->showFields = $mvc->searchInputField;
            
            bgerp_Portal::prepareSearchForm($mvc, $data->listFilter);
        } else {
            
            // Добавяме поле във формата за търсене
            $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager|admin)', 'caption=Потребител,input,silent,refreshForm');
            
            // Кои полета да се показват
            $data->listFilter->showFields = "{$mvc->searchInputField}, usersSearch";
            
            // Инпутваме полетата
            $data->listFilter->input();
            
            // Добавяме бутон за филтриране
            $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
            
            // Ако не е избран потребител по подразбиране
            if (!$data->listFilter->rec->usersSearch) {
                
                // Да е текущия
                $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
            }
            
            // Ако има филтър
            if ($filter = $data->listFilter->rec) {
                
                // Ако се търси по всички и има права ceo
                if ((strpos($filter->usersSearch, '|-1|') !== false) && (haveRole('ceo'))) {
                    // Търсим всичко
                } else {
                    
                    // Масив с потребителите
                    $usersArr = type_Keylist::toArray($filter->usersSearch);
                    
                    // Ако има избрани потребители
                    if (countR((array) $usersArr)) {
                        
                        // Показваме всички потребители
                        $data->query->orWhereArr('userId', $usersArr);
                    } else {
                        
                        // Не показваме нищо
                        $data->query->where('1=2');
                    }
                }
            }
        }
        
        $data->query->orderBy('#last', 'DESC');
    }
    
    
    /**
     * Какво правим след сетъпа на модела?
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        if (!$mvc->fetch("#searchKeywords != '' AND #searchKeywords IS NOT NULL")) {
            $count = 0;
            $query = static::getQuery();
            $query->orderBy('#id', 'DESC');
            
            while ($rec = $query->fetch()) {
                if ($rec->searchKeywords) {
                    continue;
                }
                $rec->searchKeywords = $mvc->getSearchKeywords($rec);
                $mvc->save_($rec, 'searchKeywords');
                $count++;
            }
            
            $res .= "Обновени ключови думи на  {$count} записа в Последно";
        }
    }
    
    
    /**
     * Връща id-тата на последно използваните нишки
     *
     * @param int|null $count       - броя нишки
     * @param int|null $userId      - за потребителя
     * @param int|null $lastSeconds - в последните колко секунди
     *
     * @return array
     */
    public static function getLastThreadsId($count = 5, $userId = null, $lastSeconds = null)
    {
        // Броя трябва да е положителен
        expect((isset($count) && $count > 0) || isset($lastSeconds));
        
        // Масив с нишките
        $threadsArr = array();
        
        // Ако не е подадено id на потребителя
        if (!$userId) {
            
            // id на текищия потребител
            $userId = core_Users::getCurrent();
        }
        
        // Вземаме последните документи
        $query = static::getQuery();
        $query->where("#userId = '{$userId}'");
        $query->where("#type = 'document'");
        $query->orderBy('last', 'DESC');
        
        if (isset($lastSeconds)) {
            $fromDate = dt::addSecs(-1 * $lastSeconds);
            $query->where("#last >= '{$fromDate}'");
        }
        
        // Брояч
        $cnt = 0;
        
        while ($rec = $query->fetch()) {
            
            // id на нишката
            $threadId = doc_Containers::fetchField($rec->objectId, 'threadId');
            
            // Ако няма id на нишка или нямам права за сингъла на нишката, прескачаме
            if (!$threadId || !doc_Threads::haveRightFor('single', $threadId)) {
                continue;
            }
            
            // Добавяме в масива
            $threadsArr[$threadId] = $threadId;
            
            // Увеличаваме брояча
            $cnt++;
            
            // Ако сме достигнали лимита, прекъсваме
            if (isset($count) && $cnt == $count) {
                break;
            }
        }
        
        return $threadsArr;
    }
    
    
    /**
     * Връща id-тата на последно използваните папки
     *
     * @param int $count  - Броя папки
     * @param int $userId - За потребителя
     *
     * @return array
     */
    public static function getLastFolderIds($count = 5, $userId = null)
    {
        // Броя трябва да е положителен
        expect($count > 0);
        
        // Масив с нишките
        $foldersArr = array();
        
        // Ако не е подадено id на потребителя
        if (!$userId) {
            
            // id на текищия потребител
            $userId = core_Users::getCurrent();
        }
        
        // Вземаме последните документи
        $query = static::getQuery();
        $query->where("#userId = '{$userId}'");
        $query->where("#type = 'folder'");
        $query->orderBy('last', 'DESC');
        
        // Брояч
        $cnt = 0;
        
        while ($rec = $query->fetch()) {
            $folderId = $rec->objectId;
            
            // Ако няма id на нишка или нямам права за сингъла на нишката, прескачаме
            if (!$folderId || !doc_Folders::haveRightFor('single', $folderId)) {
                continue;
            }
            
            // Добавяме в масива
            $foldersArr[$folderId] = $folderId;
            
            // Увеличаваме брояча
            $cnt++;
            
            // Ако сме достигнали лимита, прекъсваме
            if ($cnt == $count) {
                break;
            }
        }
        
        return $foldersArr;
    }
    
    
    /**
     * Връща id, което ще се използва за обграждащия div на таблицата, който ще се замества по AJAX
     *
     * @return string
     *
     * @deprecated
     */
    public function getDivId()
    {
        return $this->className . '_PortalTable';
    }
    
    
    /**
     * Връща хеша за листовия изглед. Вика се от bgerp_RefreshRowsPlg
     *
     * @param string $status
     *
     * @return string
     *
     * @see bgerp_RefreshRowsPlg
     */
    public function getContentHash($status)
    {
        // Премахваме всички тагове
        $hash = md5(trim(strip_tags($status)));
        
        return $hash;
    }
    
    
    /**
     * Изтрива стари записи в bgerp_Recently
     */
    public function cron_DeleteOldRecently()
    {
        $lastRecently = dt::addDays(-bgerp_Setup::get('RECENTLY_KEEP_DAYS') / (24 * 3600));
        
        $res = $this->delete(array("#last < '[#1#]'", $lastRecently));
        
        if ($res) {
            $this->logNotice("Бяха изтрити {$res} записа");
            
            return "Бяха изтрити {$res} записа от " . $this->className;
        }
    }
}
