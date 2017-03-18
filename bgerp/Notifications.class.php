<?php



/**
 * Мениджър за известявания
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Известявания
 */
class bgerp_Notifications extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_Modified, bgerp_Wrapper, plg_RowTools, plg_GroupByDate, plg_Sorting, plg_Search, bgerp_RefreshRowsPlg';
    
    
    /**
     * @see bgerp_RefreshRowsPlg
     */
    var $bgerpRefreshRowsTime = 5000;
    
    
    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'modifiedOn';
    
    
    /**
     * Заглавие
     */
    var $title = 'Известия';


    /**
     * Заглавие
     */
    public $singleTitle = 'Известие';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'admin';


    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 15;


    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Полета по които ще се търси
     */
    var $searchFields = 'msg';
    
    
    /**
     * Как се казва полето за пълнотекстово търсене
     */
    var $searchInputField = 'noticeSearch';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Офсет преди текущото време при липса на 'Затворено на' в нотификциите
     */
    const NOTIFICATIONS_LAST_CLOSED_BEFORE = 60;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('msg', 'varchar(255)', 'caption=Съобщение, mandatory');
        $this->FLD('state', 'enum(active=Активно, closed=Затворено)', 'caption=Състояние');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Отговорник');
        $this->FLD('priority', 'enum(normal, warning, alert)', 'caption=Приоритет');
        $this->FLD('cnt', 'int', 'caption=Брой');
        $this->FLD('url', 'varchar', 'caption=URL->Ключ');
        $this->FLD('customUrl', 'varchar', 'caption=URL->Обект');
        $this->FLD('hidden', 'enum(no,yes)', 'caption=Скрито,notNull');
        $this->FLD('closedOn', 'datetime', 'caption=Затворено на');
        
        $this->setDbUnique('url, userId');
    }
    
    
    /**
     * Добавя известие за настъпило събитие
     * @param varchar $msg
     * @param array $url
     * @param integer $userId
     * @param enum $priority
     */
    static function add($msg, $urlArr, $userId, $priority = NULL, $customUrl = NULL, $addOnce = FALSE)
    {
        if (!isset($userId)) return ;
        
        // Потребителя не може да си прави нотификации сам на себе си
        // Режима 'preventNotifications' спира задаването на всякакви нотификации
        if (($userId == core_Users::getCurrent()) || Mode::is('preventNotifications')) return ;
        
        // Да не се нотифицира контрактора
        if (core_Users::haveRole('partner', $userId)) return ;
        
        if(!$priority) {
            $priority = 'normal';
        }

        $rec = new stdClass();
        $rec->msg = $msg;
        $rec->url = toUrl($urlArr, 'local', FALSE);
        $rec->userId = $userId;
        $rec->priority = $priority;
        
        // Ако има такова съобщение - само му вдигаме флага, че е активно
        $r = bgerp_Notifications::fetch(array("#userId = {$rec->userId} AND #url = '[#1#]'", $rec->url));
        
        if(is_object($r)) {
            if($addOnce && ($r->state == 'active') && ($r->hidden == 'no') && ($r->msg == $rec->msg) && ($r->priority == $rec->priority)) {
                
                // Вече имаме тази нотификация
                return;
            }

            $rec->id = $r->id;
            // Увеличаваме брояча
            $rec->cnt = $r->cnt + 1;
        }

        $rec->state = 'active';
        $rec->hidden = 'no';
        
        if($customUrl) {
            $rec->customUrl = toUrl($customUrl, 'local', FALSE);
        }
        
        bgerp_Notifications::save($rec);
    }
    
    
    /**
     * Отбелязва съобщение за прочетено
     */
    static function clear($urlArr, $userId = NULL)
    {
        // Не изчистваме от опресняващи ajax заявки
        if(Request::get('ajax_mode')) return;
        
        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if(empty($userId)) {
            return;
        }

        // Да не се нотифицира контрактора
        if ($userId != '*' && core_Users::haveRole('partner', $userId)) return ;
        
        $url = toUrl($urlArr, 'local', FALSE);
        $query = bgerp_Notifications::getQuery();
        
        if($userId == '*') {
            $query->where(array("#url = '[#1#]' AND #state = 'active'", $url));
        } else {
            $query->where(array("#userId = {$userId} AND #url = '[#1#]' AND #state = 'active'", $url));
        }
        $query->show('id, state, userId, url');
        
        while($rec = $query->fetch()) {
            $rec->state = 'closed';
            $rec->closedOn = dt::now();
            bgerp_Notifications::save($rec, 'state,modifiedOn,closedOn');
        }
    }
    
    
    /**
     * Връща кога за последен път е затваряна нотификацията с дадено URL от даден потребител
     */
    static function getLastClosedTime($urlArr, $userId = NULL)
    {
        $url = toUrl($urlArr, 'local', FALSE);
        
        $query = self::getQuery();
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $query->where("#url = '{$url}' AND #userId = '{$userId}'");
        
        if($rec = $query->fetch()) {
            
            return $rec->closedOn;
        }
    }
    
    
    /**
     * Връща нотифицираните потребители към съответното URL
     *
     * @param array $urlArr
     *
     * @return array
     */
    static function getNotifiedUserArr($urlArr)
    {
        $url = toUrl($urlArr, 'local', FALSE);
        
        $query = self::getQuery();
        $query->where("#url = '{$url}'");
        
        $usersArr = array();
        while ($rec = $query->fetch()) {
            $usersArr[$rec->userId] = $rec->hidden;
        }
        
        return $usersArr;
    }
    
    
    /**
     * Скрива посочените записи
     * Обикновено след Reject
     */
    static function setHidden($urlArr, $hidden = 'yes', $userId = NULL)
    {
        $url = toUrl($urlArr, 'local', FALSE);
        
        $query = self::getQuery();
        
        $query->where("#url = '{$url}'");
        
        if ($userId) {
            $query->where("#userId = '{$userId}'");
        }
        
        while($rec = $query->fetch()) {
            
            // Ако ще се скрива
            if ($hidden == 'yes') {
                
                // Ако имаме 
                if ($rec->cnt > 0) {
                    
                    // Вадим един брой
                    $rec->cnt--;
                }
                
                // Ако няма нито един брой
                if ($rec->cnt == 0) {
                    
                    // Скриваме
                    $rec->hidden = $hidden;
                }
            } elseif ($hidden == 'no') {
                
                // Ако ще се визуализира
                
                // Увеличаваме с единица
                $rec->cnt++;
                
                // Показваме
                $rec->hidden = $hidden;
            }
            
            self::save($rec);
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
        $url = parseLocalUrl($rec->customUrl ? $rec->customUrl : $rec->url, FALSE);
        
        if($rec->cnt > 1) {
            //  $row->msg .= " ({$rec->cnt})";
        }
        
        $attr = array();
        if($rec->state == 'active') {
            $attr['style'] = 'font-weight:bold;';
        } else {
            $attr['style'] = 'color:#666;';
        }
        
        // Превеждаме съобщението
        // Спираме преовада и въте, ако има за превеждане, тогава се превежда
        $row->msg = tr("|*{$row->msg}");
        
        $row->msg = ht::createLink($row->msg, $url, NULL, $attr);
    }
    
    
    /**
     * Екшън за рендиране блок с нотификации за текущия
     */
    function act_Render()
    {
        requireRole('powerUser');
        
        $userId = core_Users::getCurrent();
        
        return static::render($userId);
    }
    
    
    /**
     * Рендира блок с нотификации за текущия или посочения потребител
     */
    static function render_($userId = NULL)
    {
        if(empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $Notifications = cls::get('bgerp_Notifications');


        // Намираме времето на последния запис
        $query = $Notifications->getQuery();
        $query->where("#userId = $userId");
        $query->limit(1);
        $query->orderBy("#modifiedOn", 'DESC');
        $lastRec = $query->fetch();
        $key = md5($userId . '_' . Request::get('ajax_mode') . '_' . Mode::get('screenMode') . '_' . Request::get('P_bgerp_Notifications') . '_' . Request::get('noticeSearch') . '_' . core_Lg::getCurrent());

        list($tpl, $modifiedOn) = core_Cache::get('Notifications', $key);
 
        if(!$tpl || $modifiedOn != $lastRec->modifiedOn) {

            // Създаваме обекта $data
            $data = new stdClass();
            
            // Създаваме заявката
            $data->query = $Notifications->getQuery();
            
            $data->query->show("msg,state,userId,priority,cnt,url,customUrl,modifiedOn,modifiedBy,searchKeywords");
            
            // Подготвяме полетата за показване
            $data->listFields = 'modifiedOn=Време,msg=Съобщение';
            
            $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
            $data->query->orderBy("state,modifiedOn=DESC");
            
            if(Mode::is('screenMode', 'narrow') && !Request::get('noticeSearch')) {
                $data->query->where("#state = 'active'");
                
                // Нотификациите, модифицирани в скоро време да се показват
                $before = dt::subtractSecs(200);
                $data->query->orWhere("#modifiedOn >= '{$before}'");
            }
            
            // Подготвяме филтрирането
            $Notifications->prepareListFilter($data);
            
            // Подготвяме навигацията по страници
            $Notifications->prepareListPager($data);
            
            // Подготвяме записите за таблицата
            $Notifications->prepareListRecs($data);
            
            // Подготвяме редовете на таблицата
            $Notifications->prepareListRows($data);
            
            // Подготвяме заглавието на таблицата
            $data->title = tr("Известия");
            
            // Подготвяме лентата с инструменти
            $Notifications->prepareListToolbar($data);
            
            // Рендираме изгледа
            $tpl = $Notifications->renderPortal($data);

            core_Cache::set('Notifications', $key, array($tpl, $lastRec->modifiedOn), doc_Setup::get('CACHE_LIFETIME'));
        }
        
        //Задаваме текущото време, за последно преглеждане на нотификациите
        Mode::setPermanent('lastNotificationTime', time());
        
        return $tpl;
    }
    
    
    /**
     * Преброява отворените нотификации за всеки потребител
     */
    static function getOpenCnt($userId = NULL)
    {
        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if($userId > 0) {
            $query = self::getQuery();
            $cnt = $query->count("#userId = $userId AND #state = 'active' AND #hidden = 'no'");
        } else {
            $cnt = 0;
        }

        
        return $cnt;
    }
    
    
    /**
     * Рендира портала
     */
    function renderPortal($data)
    {
        $Notifications = cls::get('bgerp_Notifications');
    
        // Ако се вика по AJAX
        if (!Request::get('ajax_mode')) {
            
            $divId = $Notifications->getDivId();
          
            $tpl = new ET("
                <div class='clearfix21 portal' style='background-color:#fff8f8'>
                <div style='background-color:#fee' class='legend'><div style='float:left'>[#PortalTitle#]</div>
                [#ListFilter#]<div class='clearfix21'></div></div>
                [#PortalPagerTop#]
                
                <div id='{$divId}'>
                    <!--ET_BEGIN PortalTable-->
                        [#PortalTable#]
                    <!--ET_END PortalTable-->
                </div>
                
                [#PortalPagerBottom#]
                </div>
            ");
            
            // Попълваме титлата
            if(!Mode::is('screenMode', 'narrow')) {  
                $tpl->append($data->title, 'PortalTitle');
            }
            
            // Попълваме горния страньор
            $tpl->append($Notifications->renderListPager($data), 'PortalPagerTop');
            
            if($data->listFilter){
                $formTpl = $data->listFilter->renderHtml();
                $formTpl->removeBlocks();
                $formTpl->removePlaces();
                $tpl->append($formTpl, 'ListFilter');
            }
            
            // Попълваме долния страньор
            $tpl->append($Notifications->renderListPager($data), 'PortalPagerBottom');
        } else {
            $tpl = new ET("[#PortalTable#]");
        }
        
        // Попълваме таблицата с редовете
        $tpl->append($Notifications->renderListTable($data), 'PortalTable');
        
        return $tpl;
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
        $data->listFilter->view = 'horizontal';
        
        if(strtolower(Request::get('Act')) == 'show'){
            
            $data->listFilter->showFields = $mvc->searchInputField;
            
            bgerp_Portal::prepareSearchForm($mvc, $data->listFilter);
        } else {
            
            // Добавяме поле във формата за търсене
            $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager|admin)', 'caption=Потребител,input,silent,autoFilter');
            
            // Кои полета да се показват
            $data->listFilter->showFields = "{$mvc->searchInputField}, usersSearch";
            
            // Инпутваме полетата
            $data->listFilter->input();
            
            // Бутон за филтриране
            $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
            
            // Ако не е избран потребител по подразбиране
            if(!$data->listFilter->rec->usersSearch) {
                
                // Да е текущия
                $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
            }
            
            // Ако има филтър
            if($filter = $data->listFilter->rec) {
                
                // Ако се търси по всички и има права ceo
                if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo'))) {
                    // Търсим всичко
                } else {
                    
                    // Масив с потребителите
                    $usersArr = type_Keylist::toArray($filter->usersSearch);
                    
                    // Ако има избрани потребители
                    if (count((array)$usersArr)) {
                        
                        // Показваме всички потребители
                        $data->query->orWhereArr('userId', $usersArr);
                    } else {
                        
                        // Не показваме нищо
                        $data->query->where("1=2");
                    }
                }
            }
        }
        
        $data->query->orderBy("#modifiedOn", 'DESC');
    }
    
    
    /**
     * Какво правим след сетъпа на модела?
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!$mvc->fetch("#searchKeywords != '' AND #searchKeywords IS NOT NULL")) {
            $count = 0;
            $query = static::getQuery();
            $query->orderBy("#id", "DESC");
            
            while($rec = $query->fetch()){
                // Обновяваме ключовите думи на нотификациите, ако нямат
                if($rec->searchKeywords) continue;
                $rec->searchKeywords = $mvc->getSearchKeywords($rec);
                $mvc->save_($rec, 'searchKeywords');
                $count++;
            }
            
            $res .= "Обновени ключови думи на  {$count} записа в Нотификациите";
        }
    }
    
    
    /**
     * Абонира функцията за промяна на броя на нотификациите по AJAX
     * 
     * $param core_ET|NULL $tpl
     * 
     * @return core_ET $tpl
     */
    static function subscribeCounter($tpl=NULL)
    {
        if (!$tpl) {
            $tpl = new ET();
        }
        
        core_Ajax::subscribe($tpl, array('bgerp_Notifications', 'notificationsCnt'), 'notificationsCnt', 5000);
        
        return $tpl;
    }
    
    
    /**
     * Екшън, който връща броя на нотификациите в efae
     */
    function act_NotificationsCnt()
    {

        
        // Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
            
            // Броя на нотифиакциите
            $notifCnt = static::getOpenCnt();
            
            $res = array();

            // Добавяме резултата
            $obj = new stdClass();
            $obj->func = 'notificationsCnt';
            $obj->arg = array('id'=>'nCntLink', 'cnt' => $notifCnt);
            
            $res[] = $obj;

            // Ако има увеличаване - пускаме звук
            $lastCnt = Mode::get('NotificationsCnt');

            if (isset($lastCnt) && ($notifCnt > $lastCnt)) {
                
                $newNotifCnt = $notifCnt - $lastCnt;
                    
                if ($newNotifCnt == 1) {
                    $notifStr = $newNotifCnt . ' ' . tr('ново известие');
                } else {
                    $notifStr = $newNotifCnt . ' ' . tr('нови известия');
                }
                
                $notifyArr = array('title' => $notifStr, 'blinkTimes' => 2);
                
                // Добавяме и звук, ако е зададено
                $notifSound = bgerp_Setup::get('SOUND_ON_NOTIFICATION');
                if ($notifSound != 'none') {
                    $notifyArr['soundOgg'] = sbf("sounds/{$notifSound}.ogg", '');
                    $notifyArr['soundMp3'] = sbf("sounds/{$notifSound}.mp3", '');
                }
                
                $obj = new stdClass();
                $obj->func = 'Notify';
                $obj->arg = $notifyArr;
                $res[] = $obj;
            }
            
            // Записваме в сесията последно изпратените нотификации, ако има промяна
            if($notifCnt != $lastCnt) {
                if(core_Users::getCurrent()) {
                    Mode::setPermanent('NotificationsCnt', $notifCnt);
                } else {
                    Mode::setPermanent('NotificationsCnt', NULL);
                }
            }

            return $res;
        }
    }
    
    
    /**
     * Колко нови за потребителя нотификации има, след позледното разглеждане на портала?
     */
    public static function getNewCntFromLastOpen($userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }

        $lastTime = bgerp_LastTouch::get('portal', $userId);
        
        if(!$lastTime) {
            $lastTime = '2000-01-01';
        }

        $cnt = self::count("#state = 'active' AND #hidden = 'no' AND #userId = {$userId} AND #modifiedOn >= '{$lastTime}'");

        return $cnt;
    }
    
    
    /**
     * Връща id, което ще се използва за обграждащия div на таблицата, който ще се замества по AJAX
     *
     * @return string
     */
    function getDivId()
    {
        return $this->className . '_PortalTable';
    }
    
    
    /**
     * Връща хеша за листовия изглед. Вика се от bgerp_RefreshRowsPlg
     *
     * @param string $status
     *
     * @return string
     * @see bgerp_RefreshRowsPlg
     */
    function getContentHash($status)
    {
        // Премахваме всички тагове без 'a'
        // Това е необходимо за да определим когато има промяна в състоянието на някоя нотификация
        // Трябва да се премахват другите тагове, защото цвета се промяне през няколко секунди
        // и това би накарало всеки път да се обновяват нотификациите
        $hash = md5(trim(strip_tags($status, '<a>')));
        
        return $hash;
    }


    /**
     * Изтрива стари записи в bgerp_Notifications
     */
    function cron_DeleteOldNotifications()
    {
        $lastRecently = dt::addDays(-bgerp_Setup::get('RECENTLY_KEEP_DAYS')/(24*3600));

        // $res = self::delete("(#closedOn IS NOT NULL) AND (#closedOn < '{$lastRecently}')");

        if($res) {

            return "Бяха изтрити {$res} записа от " . $this->className;
        }
    }

}
