<?php


/**
 * Мениджър за известявания
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Известявания
 */
class bgerp_Notifications extends core_Manager
{
    /**
     * Максимална дължина на показваните заглавия
     */
    const maxLenTitle = 120;
    
    
    /**
     * Необходими мениджъри
     */
    public $loadList = 'plg_Modified, bgerp_Wrapper, plg_RowTools, plg_GroupByDate, plg_Sorting, plg_Search, bgerp_RefreshRowsPlg';
    
    
    /**
     * @see bgerp_RefreshRowsPlg
     */
    public $bgerpRefreshRowsTime = 5000;
    
    
    /**
     * Името на полето, по което плъгина GroupByDate ще групира редовете
     */
    public $groupByDateField = 'modifiedOn';
    
    
    /**
     * Заглавие
     */
    public $title = 'Известия';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Известие';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'admin';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 15;
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Полета по които ще се търси
     */
    public $searchFields = 'msg';
    
    
    /**
     * Как се казва полето за пълнотекстово търсене
     */
    public $searchInputField = 'noticeSearch';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Офсет преди текущото време при липса на 'Затворено на' в нотификациите
     */
    const NOTIFICATIONS_LAST_CLOSED_BEFORE = 60;

    
/**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 100000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'modifiedOn,lastTime';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('msg', 'varchar(255)', 'caption=Съобщение, mandatory');
        $this->FLD('state', 'enum(active=Активно, closed=Затворено)', 'caption=Състояние');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Отговорник');
        $this->FLD('priority', 'enum(normal, warning, alert)', 'caption=Приоритет');
        $this->FLD('cnt', 'int', 'caption=Брой');
        $this->FLD('url', 'varchar(ci)', 'caption=URL->Ключ');
        $this->FLD('customUrl', 'varchar(ci)', 'caption=URL->Обект');
        $this->FLD('hidden', 'enum(no,yes)', 'caption=Скрито,notNull');
        $this->FLD('closedOn', 'datetime', 'caption=Затворено на');
        $this->FLD('lastTime', 'datetime', 'caption=Предишното време, input=none');
        $this->FLD('activatedOn', 'datetime', 'caption=Последно активиране, input=none');
        $this->FLD('urlId', 'bigint', 'caption=URL номера от URL, input=none,column=none,single=none');
        $this->FLD('customUrlId', 'bigint', 'caption=URL номера от обект, input=none,column=none,single=none');
        
        $this->setDbUnique('url, userId');
        $this->setDbIndex('userId');
        
        $this->setDbIndex('urlId');
        $this->setDbIndex('customUrlId');
        
        $this->setDbIndex('modifiedOn');
        $this->setDbIndex('lastTime');
    }
    
    
    /**
     * Връща максималното id от подаденото url
     *
     * @param string $urlStr
     *
     * @return NULL|string
     */
    public static function prepareUrlId($urlStr)
    {
        $urlStr = trim($urlStr);
        
        if (!$urlStr) {
            
            return;
        }
        
        $res = '';
        
        $urlStr = preg_replace('/[^0-9]+/', ' ', $urlStr);
        
        $urlStr = trim($urlStr);
        
        if (!$urlStr) {
            
            return $res;
        }
        
        $urlStrArr = explode(' ', $urlStr);
        
        $strWord = '';
        $maxStrLen = 0;
        foreach ($urlStrArr as $strVal) {
            $strVal = trim($strVal);
            $strLen = strlen($strVal);
            if ($maxStrLen < $strLen) {
                $maxStrLen = $strLen;
                $strWord = $strVal;
            }
        }
        
        $res = $strWord;
        
        return $res;
    }
    
    
    /**
     * Добавя известие за настъпило събитие
     *
     * @param string      $msg
     * @param array       $url
     * @param int         $userId
     * @param null|string $priority
     *
     * @return null
     */
    public static function add($msg, $urlArr, $userId, $priority = null, $customUrl = null, $addOnce = false)
    {
        if (!isset($userId)) {
            
            return ;
        }
        
        $priorityMap = array(
            'high' => 'warning',
            'critical' => 'alert',
            'warning' => 'warning',
            'alert' => 'alert');
        
        $priority = $priorityMap[$priority];
        
        if (!$priority) {
            $priority = 'normal';
        }
        
        // Потребителят не може да си прави нотификации сам на себе си
        // Режима 'preventNotifications' спира задаването на всякакви нотификации
        if (($userId == core_Users::getCurrent()) || Mode::is('preventNotifications')) {
            
            return ;
        }
        
        // Да не се нотифицира контракторът
        if (core_Users::haveRole('partner', $userId)) {
            
            return ;
        }
        
        $rec = new stdClass();
        $rec->msg = $msg;
        $rec->url = toUrl($urlArr, 'local', false);
        $rec->userId = $userId;
        $rec->priority = $priority;
        $rec->activatedOn = dt::now();
        
        // Ако има такова съобщение - само му вдигаме флага, че е активно
        $r = bgerp_Notifications::fetch(array("#userId = {$rec->userId} AND #url = '[#1#]'", $rec->url));
        
        if (is_object($r)) {
            if ($addOnce && ($r->state == 'active') && ($r->hidden == 'no') && ($r->msg == $rec->msg) && ($r->priority == $rec->priority)) {
                
                // Вече имаме тази нотификация
                return;
            }
            
            $rec->id = $r->id;
            
            // Увеличаваме брояча
            $rec->cnt = $r->cnt + 1;
            
            if ($r->state == 'active' &&
                isset($r->activatedOn) &&
                $r->activatedOn > bgerp_LastTouch::get('portal', $userId)) {
                $rec->activatedOn = $r->activatedOn;
            }
        } else {
            $rec->cnt = 1;
        }
        
        $rec->state = 'active';
        $rec->hidden = 'no';
        
        if ($customUrl) {
            $rec->customUrl = toUrl($customUrl, 'local', false);
        } else {
            $rec->customUrl = null;
        }
        
        bgerp_Notifications::save($rec);
        
        // Инвалидиране на кеша
        bgerp_Portal::invalidateCache($userId, 'bgerp_drivers_Notifications');
    }
    
    
    /**
     * Отбелязва съобщение за прочетено
     */
    public static function clear($urlArr, $userId = null)
    {
        // Не изчистваме от опресняващи ajax заявки
        if (Request::get('ajax_mode')) {
            
            return;
        }
        
        // Ако само се запознава със съдържанието - не се изчиства
        if (Request::get('OnlyMeet')) {
            
            return ;
        }
        
        if (empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if (empty($userId)) {
            
            return;
        }
        
        // Да не се нотифицира контрактора
        if ($userId != '*' && core_Users::haveRole('partner', $userId)) {
            
            return ;
        }
        
        $url = toUrl($urlArr, 'local', false);
        
        $query = bgerp_Notifications::getQuery();
        
        $urlId = self::prepareUrlId($url);
        if ($urlId) {
            $query->where(array("#urlId = '[#1#]'", $urlId));
        }
        
        if ($userId == '*') {
            $query->where(array("#url = '[#1#]' AND #state = 'active'", $url));
        } else {
            $query->where(array("#userId = {$userId} AND #url = '[#1#]' AND #state = 'active'", $url));
        }
        $query->show('id, state, userId, url');
        
        while ($rec = $query->fetch()) {
            $rec->state = 'closed';
            $rec->closedOn = dt::now();
            bgerp_Notifications::save($rec, 'state,modifiedOn,closedOn,modifiedBy');
        }
    }
    
    
    /**
     * Връща кога за последен път е затваряна нотификацията с дадено URL от даден потребител
     */
    public static function getLastClosedTime($urlArr, $userId = null)
    {
        $url = toUrl($urlArr, 'local', false);
        
        $query = self::getQuery();
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $urlId = self::prepareUrlId($url);
        if ($urlId) {
            $query->where(array("#urlId = '[#1#]'", $urlId));
        }
        
        $query->where(array("#url = '[#1#]' AND #userId = '[#2#]'", $url, $userId));
        
        $query->limit(1);
        
        if ($rec = $query->fetch()) {
            
            return $rec->closedOn;
        }
    }
    
    
    /**
     * Връща текста на активното съобщение, ако има такова
     */
    public static function getActiveMsgFor($urlArr, $userId)
    {
        $url = toUrl($urlArr, 'local', false);
        
        $query = self::getQuery();
        
        $urlId = self::prepareUrlId($url);
        if ($urlId) {
            $query->where(array("#urlId = '[#1#]'", $urlId));
        }
        
        $query->where("#state = 'active'");
        $query->where("#hidden = 'no'");
        
        $query->where(array("#url = '[#1#]' AND #userId = '[#2#]'", $url, $userId));
        
        $query->limit(1);
        
        if ($rec = $query->fetch()) {
            
            return $rec->msg;
        }
    }
    
    
    /**
     * Връща нотифицираните потребители към съответното URL
     *
     * @param array $urlArr
     *
     * @return array
     */
    public static function getNotifiedUserArr($urlArr)
    {
        $url = toUrl($urlArr, 'local', false);
        
        $query = self::getQuery();
        
        $urlId = self::prepareUrlId($url);
        if ($urlId) {
            $query->where(array("#urlId = '[#1#]'", $urlId));
        }
        
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
    public static function setHidden($urlArr, $hidden = 'yes', $userId = null)
    {
        $url = toUrl($urlArr, 'local', false);
        
        $query = self::getQuery();
        
        $urlId = self::prepareUrlId($url);
        if ($urlId) {
            $query->where(array("#urlId = '[#1#]'", $urlId));
        }
        
        $query->where("#url = '{$url}'");
        
        if ($userId) {
            $query->where("#userId = '{$userId}'");
        }
        
        while ($rec = $query->fetch()) {
            
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
     * Скрива записите, които са към съответния сингъл на документа
     *
     * @param string $className
     * @param int    $clsId
     */
    public static function hideNotificationsForSingle($className, $clsId, $hidden = 'no')
    {
        $query = self::getQuery();
        $query->where("#hidden = '{$hidden}'");
        $className = strtolower($className);
        
        $query->setUnion(array("(LOWER(#url) LIKE '%/[#1#]/single/[#2#]') AND #urlId = '[#2#]'", $className, $clsId));
        $query->setUnion(array("(LOWER(#url) LIKE '%/[#1#]/single/[#2#]/%') AND #urlId = '[#2#]'", $className, $clsId));
        $query->setUnion(array("(LOWER(#customUrl) LIKE '%/[#1#]/single/[#2#]') AND #customUrlId = '[#2#]'", $className, $clsId));
        $query->setUnion(array("(LOWER(#customUrl) LIKE '%/[#1#]/single/[#2#]/%') AND #customUrlId = '[#2#]'", $className, $clsId));
        
        while ($rec = $query->fetch()) {
            $rec->hidden = ($hidden == 'no') ? 'yes' : 'no';
            
            if ($rec->hidden == 'no') {
                try {
                    $urlArr = self::getUrl($rec);
                    $act = strtolower($urlArr['Act']);
                    
                    $ctr = $urlArr['Ctr'];
                    if (!$ctr::haveRightFor($act, $urlArr['id'], $rec->userId)) {
                        continue;
                    }
                } catch (Exception $e) {
                    reportException($e);
                }
            }
            
            self::save($rec, 'hidden,modifiedOn,modifiedBy');
            
            $msg = 'Скрита нотификация';
            if ($rec->hidden == 'no') {
                $msg = 'Показана нотификация';
            }
            
            self::logDebug($msg, $rec->id);
        }
    }
    
    
    /**
     * Показва записите, които са към съответния сингъл на документа
     *
     * @param string $className
     * @param int    $clsId
     */
    public static function showNotificationsForSingle($className, $clsId)
    {
        self::hideNotificationsForSingle($className, $clsId, 'yes');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записът в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $url = self::getUrl($rec);
        
        if ($rec->cnt > 1) {
            //  $row->msg .= " ({$rec->cnt})";
        }
        
        $attr = array();
        if ($rec->state == 'active') {
            $attr['style'] = 'font-weight:bold;';
            $attr['onclick'] = 'render_forceReloadAfterBack()';
            if ($rec->priority == 'alert') {
                $attr['style'] .= 'color:#cc0033 !important;';
            } elseif ($rec->priority == 'warning') {
                $attr['style'] .= 'color:#882200 !important;';
            }
        } else {
            $attr['style'] = 'color:#666;';
        }
        
        
        if (!Mode::isReadOnly() && ($rec->userId == core_Users::getCurrent())) {
            $attr['class'] .= ' ajaxContext';
            $attr['name'] = 'context-holder';
            ht::setUniqId($attr);
            $replaceId = $attr['id'];
            unset($attr['name'], $attr['id']);
            
            $dataUrl = toUrl(array(get_called_class(), 'getContextMenu', $rec->id, 'replaceId' => $replaceId), 'local');
            $attr['data-id'] = $replaceId;
            $attr['data-url'] = $dataUrl;
        }
        
        // Превеждаме съобщението
        // Спираме превода и вътре, ако има за превеждане, тогава се превежда
        $row->msg = tr("|*{$row->msg}");
        $row->msg = str::limitLen($row->msg, self::maxLenTitle, 20, ' ... ', true);
        
        $row->msg = ht::createLink($row->msg, $url, null, $attr);
    }
    
    
    /**
     * Екшън връщащ бутоните за контекстното меню
     */
    public function act_getContextMenu()
    {
        $id = Request::get('id', 'int');
        expect($id);
        
        expect($rec = $this->fetch($id));
        
        expect($rec->userId == core_Users::getCurrent());
        
        expect($replaceId = Request::get('replaceId', 'varchar'));
        
        $tpl = new core_ET();
        
        if ($rec->userId != core_Users::getCurrent()) {
            
            return array();
        }
        
        $url = self::getUrl($rec);
        
        // Отваряне в нов таб
        $newTabBtn = ht::createLink(tr('Отвори в нов таб'), $url, null, array('ef_icon' => 'img/16/tab-new.png', 'title' => 'Отваряне в нов таб', 'class' => 'button', 'target' => '_blank'));
        $tpl->append($newTabBtn);
        
        if ($rec->state == 'active') {
            // Запознаване със съдържанието, но без отмаркиране
            $meetUrl = $url;
            $meetUrl['OnlyMeet'] = true;
            $introBtn = ht::createLink(tr('Запознаване'), $meetUrl, null, array('ef_icon' => 'img/16/see.png', 'title' => 'Запознаване със съдържанието без отмаркиране', 'class' => 'button'));
            $tpl->append($introBtn);
        }
        
        // Маркиране/отмаркиране на текст
        $markUrl = array(get_called_class(), 'mark', $rec->id);
        $markText = 'Маркиране';
        $iconMark = 'img/16/mark.png';
        if ($rec->state == 'active') {
            $markText = 'Отмаркиране';
            $iconMark = 'img/16/unmark.png';
        }
        $attr = array('ef_icon' => $iconMark, 'title' => $markText . ' на нотификацията', 'class' => 'button', 'data-url' => toUrl($markUrl, 'local'));
        $attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
        
        $markBtn = ht::createLink(tr($markText), $markUrl, null, $attr);
        
        $tpl->append($markBtn);
        
        // Ако има записи за отабониране от нотификациите
        $this->getAutoNotifyArr($rec, null, $haveStopped);
        if ($haveStopped) {
            $unsubscribeUrl = array(get_called_class(), 'unsubscribe', $rec->id);
            $attr = array('ef_icon' => 'img/16/no-bell.png', 'title' => 'Автоматично отписване от нотификациите', 'class' => 'button', 'data-url' => toUrl($unsubscribeUrl, 'local'));
            $attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
            $unsubscribeBtn = ht::createLink('Отписване', $unsubscribeUrl, null, $attr);
            $tpl->append($unsubscribeBtn);
        }
        
        // Бутон за настройки
        $ctr = $url['Ctr'];
        if ($ctr) {
            if (cls::load($ctr, true)) {
                $ctrInst = cls::get($ctr);
                $settingsUrl = array(get_called_class(), 'settings', $rec->id, 'ret_url' => true);
                if (($ctrInst instanceof doc_Folders) || ($ctrInst instanceof doc_Threads) || ($ctrInst instanceof doc_Containers) || (cls::haveInterface('doc_DocumentIntf', $ctrInst))) {
                    $settingsBtn = ht::createLink('Настройки', $settingsUrl, null, array('ef_icon' => 'img/16/cog.png', 'title' => 'Настройки за получаване на нотификация', 'class' => 'button'));
                    $tpl->append($settingsBtn);
                }
            }
        }
        
        // Ако сме в AJAX режим
        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => $replaceId, 'html' => $tpl->getContent(), 'replace' => true);
            
            $res = array_merge(array($resObj));
            
            return $res;
        }
        
        return $tpl;
    }
    
    
    /**
     * Помощна функция за спиране и пускане на нотификациите
     *
     * @param stdClass|int $rec
     * @param NULL|string  $update
     * @param NULL|array   $haveStopped
     *
     * @return array
     */
    protected static function getAutoNotifyArr($rec, $update = null, &$haveStopped = null)
    {
        $resValsArr = array();
        
        $rec = self::fetchRec($rec);
        
        if (!$rec) {
            
            return $resValsArr;
        }
        
        // Вземаме необходимите параметри от URL-то
        $url = self::getUrl($rec);
        
        $ctr = $url['Ctr'];
        $act = $url['Act'];
        $dId = $url['id'];
        
        if (cls::load($ctr, true)) {
            $clsInst = cls::get($ctr);
            
            if (($clsInst instanceof core_Manager) && ($ctr::haveRightFor($act, $dId))) {
                $folderId = $url['folderId'];
                $threadId = $url['threadId'];
                $containerId = $url['containerId'];
                
                if ($dId) {
                    if (is_numeric($dId) && $dRec = $ctr::fetch($dId)) {
                        $folderId = $dRec->folderId;
                        $threadId = $dRec->threadId;
                        $containerId = $dRec->containerId;
                    }
                }
                
                $resValsArr['folderId'] = $folderId;
                $resValsArr['threadId'] = $threadId;
                $resValsArr['containerId'] = $containerId;
            }
        }
        
        $stopNotifyArr = array();
        
        // В зависимост от текста определяме нотификациите, които да се изключат
        $msg = mb_strtolower($rec->msg);
        if (strpos($msg, '|отворени теми в|') !== false) {
            $stopNotifyArr['folOpenings'] = 'doc_Folders';
        } elseif ((strpos($msg, '|добави|') !== false) || (strpos($msg, '|хареса') !== false) || (strpos($msg, '|промени|') !== false)) {
            if (strpos($msg, '|входящ имейл|') !== false) {
                $stopNotifyArr['personalEmailIncoming'] = 'doc_Folders';
            }
            $stopNotifyArr['notify'] = 'doc_Threads';
            
            // Ако е начало на нишка
            if ($threadId && $containerId) {
                $fCid = doc_Threads::getFirstContainerId($threadId);
                if ($fCid == $containerId) {
                    $stopNotifyArr['newThread'] = 'doc_Folders';
                }
            }
        } elseif (strpos($msg, '|създаде заявка за|') !== false) {
            $stopNotifyArr['newPending'] = 'doc_Folders';
        } else {
            // Ако нотификацията е за смяна на състоянието
            if ($containerId) {
                $doc = doc_Containers::getDocument($containerId);
                
                $plugins = arr::make($doc->instance->loadList, true);
                
                if ($plugins['planning_plg_StateManager']) {
                    if ($nActArr = $doc->instance->notifyActionNamesArr) {
                        foreach ($nActArr as $actName) {
                            $actName = mb_strtolower($actName);
                            
                            if (strpos($msg, "|{$actName} на|* ") !== false) {
                                $stopNotifyArr['stateChange'] = 'doc_Folders';
                                
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        $stoppedArr = $valsArr = array();
        
        $fKey = $tKey = null;
        
        // Определяме стойностите, които трябва да се изключат
        foreach ($stopNotifyArr as $kVal => $kClass) {
            
            // За папките
            if ($kClass == 'doc_Folders') {
                if (!$folderId) {
                    continue;
                }
                
                if (!doc_Folders::haveRightFor('single', $folderId)) {
                    continue;
                }
                
                if (!$fKey) {
                    $fKey = doc_Folders::getSettingsKey($folderId);
                    
                    $valsArr[$fKey] = core_Settings::fetchKeyNoMerge($fKey);
                }
                
                $key = $fKey;
            }
            
            // За нишките
            if ($kClass == 'doc_Threads') {
                if (!$threadId) {
                    continue;
                }
                
                if (!doc_Threads::haveRightFor('single', $threadId)) {
                    continue;
                }
                
                if (!$tKey) {
                    $tKey = doc_Threads::getSettingsKey($threadId);
                    
                    $valsArr[$tKey] = core_Settings::fetchKeyNoMerge($tKey);
                }
                
                $key = $tKey;
            }
            
            // Ако преди това не е била забранена стойност
            if (!$valsArr[$key][$kVal] || ($valsArr[$key][$kVal] != 'no')) {
                $stoppedArr[$kClass][$kVal] = $valsArr[$key][$kVal];
                $valsArr[$key][$kVal] = 'no';
            }
        }
        
        $modeKey = 'NotifySettings::' . $rec->id;
        
        if ($update == 'revert') {
            $stoppedArr = Mode::get($modeKey);
        }
        
        // В зависимост от състоянието връщаме/спираме настройките за бъдещите нотификации
        if (is_array($stoppedArr) && $update) {
            $notifyVerbMap = array('notify' => 'Нов документ', 'personalEmailIncoming' => 'Личен имейл', 'folOpenings' => 'Отворени теми', 'newPending' => 'Създадена заявка', 'stateChange' => 'Променено състояние на документ', 'newThread' => 'Нова тема', 'newDoc' => 'Нов документ');
            
            $notifyMsg = '';
            
            foreach ($stoppedArr as $cls => $v) {
                if ($cls == 'doc_Folders') {
                    $title = doc_Folders::getLinkForObject($folderId);
                    $key = $fKey;
                } elseif ($cls == 'doc_Threads') {
                    $title = doc_Threads::getLinkForObject($threadId);
                    $key = $tKey;
                }
                
                $notifyMsg .= ($notifyMsg) ? '<br>' : '';
                
                if ($update == 'revert') {
                    $txt = '|Върнати настройки за нотифициране в|*';
                } else {
                    $txt = '|Спряно нотифициране в|*';
                }
                
                $notifyMsg .= "{$txt} \"{$title}\" |за|*:";
                
                $notifyTxt = '';
                foreach ($v as $kVal => $oldVal) {
                    $notifyTxt .= ($notifyTxt) ? ', ' : ' ';
                    $notifyTxt .= $notifyVerbMap[$kVal];
                    
                    if ($update == 'revert') {
                        $valsArr[$key][$kVal] = $oldVal;
                    }
                }
                
                $notifyMsg .= "<span  style='color: #00ff00;'>" . $notifyTxt . '</span>';
                
                $resValsArr['notifyMsg'] = $notifyMsg;
                
                core_Settings::setValues($key, $valsArr[$key]);
                
                if ($update == 'revert') {
                    Mode::setPermanent($modeKey, array());
                } else {
                    Mode::setPermanent($modeKey, $stoppedArr);
                }
            }
        }
        
        if ($stoppedArr) {
            $haveStopped = true;
        }
        
        return $resValsArr;
    }
    
    
    /**
     * Екшън за автоматично отписване от нотификации
     *
     * @return Redirect|array
     */
    public function act_Unsubscribe()
    {
        $id = Request::get('id', 'int');
        expect($id);
        
        expect($rec = $this->fetch($id));
        
        expect($rec->userId == core_Users::getCurrent());
        
        $valsArr = $this->getAutoNotifyArr($rec, 'unsubscribe');
        
        if (!Request::get('ajax_mode')) {
            if ($rec->state == 'active') {
                $retUrl = array(get_called_class(), 'mark', $rec->id);
            } else {
                $retUrl = array('Portal', 'show');
            }
            
            return new Redirect($retUrl, $valsArr['notifyMsg']);
        }
        
        $res = array();
        
        if ($valsArr['notifyMsg']) {
            $hitId = rand();
            status_Messages::newStatus($valsArr['notifyMsg'], 'notice', null, 60, $hitId);
            $res = status_Messages::getStatusesData(Request::get('hitTime', 'int'), 0, $hitId);
        }
        
        $res[] = (object) array('func' => 'closeContextMenu');
        
        // Ако се отписваме от активна нотификация - да не е болд
        if ($rec->state == 'active') {
            $res = array_merge(Request::forward(array(get_called_class(), 'mark', $rec->id)), $res);
        }
        
        bgerp_LastTouch::set('portal');
        
        return $res;
    }
    
    
    /**
     * Екшън за маркиране/отмаркиране на нотификация
     *
     * @return Redirect
     */
    public function act_Mark()
    {
        $id = Request::get('id', 'int');
        expect($id);
        
        expect($rec = $this->fetch($id));
        
        expect($rec->userId == core_Users::getCurrent());
        
        if ($rec->state == 'active') {
            $rec->state = 'closed';
            $msg = 'отмаркирахте';
            $act = 'Отмаркиране';
        } else {
            $rec->state = 'active';
            $msg = 'маркирахте';
            $act = 'Маркиране';
        }
        
        $rec->lastTime = dt::now();
        
        self::save($rec, 'state, lastTime');
        
        // Ако сме активирали нотификацията, връщаме автоматично нулираните настройки за нотифициране
        if ($rec->state == 'active') {
            $valsArr = $this->getAutoNotifyArr($rec, 'revert');
        }
        
        $this->logWrite($act . ' на нотификация', $rec->id);
        
        $notifyMsg = $valsArr['notifyMsg'];
        
        if (!Request::get('ajax_mode')) {
            $notifyMsg = $notifyMsg ? '<br>' . $notifyMsg : '';
            
            return new Redirect(array('Portal', 'show'), "|Успешно {$msg} нотификацията|*{$notifyMsg}");
        }
        
        if (bgerp_Setup::get('PORTAL_VIEW') == 'customized') {
            
            // Да не прескача страницата - при маркиране/отмаркиране или отписване
            if ($parentUrlStr = Request::get('parentUrl')) {
                $parentUrlArr = parseLocalUrl($parentUrlStr);
                $rArr = array();
                foreach ($parentUrlArr as $fName => $fVal) {
                    $r = Request::get($fName);
                    if (!isset($r)) {
                        $rArr[$fName] = $fVal;
                    }
                }
                
                if (!empty($rArr)) {
                    Request::push($rArr);
                }
            }
            
            $res = cls::get('bgerp_Portal')->getPortalBlockForAJAX();
        } else {
            $res = $this->action('render');
        }
        
        // Добавяме резултата и броя на нотификациите
        if (is_array($res)) {
            $notifCnt = static::getOpenCnt();
            
            $obj = new stdClass();
            $obj->func = 'notificationsCnt';
            $obj->arg = array('id' => 'nCntLink', 'cnt' => $notifCnt, 'notifyTime' => 1000 * dt::mysql2timestamp(self::getLastNotificationTime(core_Users::getCurrent())));
            
            if ($notifyMsg) {
                $hitId = rand();
                status_Messages::newStatus($notifyMsg, 'notice', null, 60, $hitId);
                $res = array_merge($res, status_Messages::getStatusesData(Request::get('hitTime', 'int'), 0, $hitId));
            }
            
            $res[] = $obj;
            
            $res[] = (object) array('func' => 'closeContextMenu');
        }
        
        bgerp_LastTouch::set('portal');
        
        return $res;
    }
    
    
    /**
     * Промяна на настройките за нотификации към папки/нишки
     */
    public function act_Settings()
    {
        $id = Request::get('id', 'int');
        expect($id);
        
        expect($rec = $this->fetch($id));
        
        expect($rec->userId == core_Users::getCurrent());
        
        $url = self::getUrl($rec);
        
        $ctr = $url['Ctr'];
        $act = $url['Act'];
        $dId = $url['id'];
        
        $retUrl = getRetUrl();
        
        if (!cls::load($ctr, true) || !$ctr::haveRightFor($act, $dId)) {
            
            return new Redirect($retUrl, 'Не може да се настройва', 'warning');
        }
        
        $folderId = $url['folderId'];
        $threadId = $url['threadId'];
        $containerId = $url['containerId'];
        
        if ($dId) {
            expect($dRec = $ctr::fetch($dId));
            $folderId = $dRec->folderId;
            $threadId = $dRec->threadId;
            $containerId = $dRec->containerId;
        } elseif ($folderId) {
            expect(doc_Folders::haveRightFor('single', $folderId));
        }
        
        expect($folderId || $threadId || $containerId, $rec, $url);
        
        $ctrInst = cls::get($ctr);
        
        $form = cls::get('core_Form');
        
        $sArr = array();
        
        $enumChoice = 'enum(default=Автоматично, yes=Винаги, no=Никога)';
        $enumTypeArr = array('input' => 'input', 'maxRadio' => 3, 'columns' => 3);
        
        $notifyDefArr = array();
        
        $valsArr = array();
        
        // Настройки за папка
        if ($folderId) {
            $fKey = doc_Folders::getSettingsKey($folderId);
            
            $folderTitle = doc_Folders::getLinkForObject($folderId);
            
            $fCaption = "Известяване в|* {$folderTitle} |при";
            
            $enumTypeArr['caption'] = $fCaption . '->Нов документ';
            $form->FNC('newDoc', $enumChoice, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Нова тема';
            $form->FNC('newThread', $enumChoice, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Отворени теми';
            $form->FNC('folOpenings', $enumChoice, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Създадени заявки';
            $form->FNC('newPending', $enumChoice, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Промяна на състоянието на документ';
            $form->FNC('stateChange', $enumChoice, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Личен имейл';
            $form->FNC('personalEmailIncoming', $enumChoice, $enumTypeArr);
            
            $sArr[$fKey] = array('newDoc', 'newThread', 'newPending', 'stateChange', 'folOpenings', 'personalEmailIncoming');
            
            // Добавяме стойностите по подразбиране
            $valsArr[$fKey] = core_Settings::fetchKeyNoMerge($fKey);
            setIfNot($valsArr[$fKey]['newDoc'], 'default');
            setIfNot($valsArr[$fKey]['newThread'], 'default');
            setIfNot($valsArr[$fKey]['folOpenings'], 'default');
            setIfNot($valsArr[$fKey]['newPending'], 'default');
            setIfNot($valsArr[$fKey]['stateChange'], 'default');
        }
        
        // Настройки за нишка
        if ($containerId && $threadId) {
            $tKey = doc_Threads::getSettingsKey($threadId);
            
            $threadTitle = doc_Threads::getLinkForObject($threadId);
            $tCaption = "Известяване в|* {$threadTitle} |при";
            $enumTypeArr['caption'] = $tCaption . '->Нов документ';
            
            $form->FNC('notify', $enumChoice, $enumTypeArr);
            
            $sArr[$tKey] = array('notify');
            
            // Добавяме стойностите по подразбиране
            $valsArr[$tKey] = core_Settings::fetchKeyNoMerge($tKey);
            setIfNot($valsArr[$fKey]['notify'], 'default');
        }
        
        // Сетваме необходимите стойности
        foreach ($sArr as $fKey => $fArr) {
            foreach ($fArr as $valKey) {
                $val = $valsArr[$fKey][$valKey];
                
                setIfNot($val, 'default');
                
                $form->setDefault($valKey, $val);
            }
        }
        
        $form->input();
        
        if ($form->isSubmitted()) {
            foreach ($sArr as $key => $sArr) {
                $vRecArr = $valsArr[$key];
                foreach ($sArr as $rVal) {
                    $vRecArr[$rVal] = $form->rec->{$rVal};
                }
                core_Settings::setValues($key, $vRecArr);
                
                // Записваме в лога
                list(, $objectId) = explode('::', $key);
                $pKey = core_Settings::prepareKey($key);
                if (isset($objectId)) {
                    $sRec = core_Settings::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]' AND #objectId = '[#3#]'", $pKey, core_Users::getCurrent(), $objectId));
                } else {
                    $sRec = core_Settings::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]'", $pKey, core_Users::getCurrent()));
                }
                
                core_Settings::logWrite('Промяна на настройките', $sRec);
            }
            
            $this->logWrite('Промяна на настройки за нотифициране', $rec->id);
            
            return new Redirect($retUrl);
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', null, 'ef_icon = img/16/disk.png, title=Запис на настройките');
        $form->toolbar->addBtn('Отказ', $retUrl, null, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Добавяме титлата на формата
        $form->title = 'Настройка за нотифициране';
        
        $tpl = $form->renderHtml();
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Помощна фунцкия за парсиране на URL-то
     *
     * @param mixed $rec
     *
     * @return array
     */
    public static function getUrl($rec)
    {
        $rec = self::fetchRec($rec);
        
        return parseLocalUrl($rec->customUrl ? $rec->customUrl : $rec->url, false);
    }
    
    
    /**
     * Екшън за рендиране блок с нотификации за текущия
     *
     * @deprecated
     */
    public function act_Render()
    {
        requireRole('powerUser');
        
        $userId = core_Users::getCurrent();
        
        return static::render($userId);
    }
    
    
    /**
     * Рендира блок с нотификации за текущия или посочения потребител
     *
     * @deprecated
     */
    public static function render_($userId = null)
    {
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $now = dt::now();
        
        $Notifications = cls::get('bgerp_Notifications');
        
        // Намираме времето на последния запис
        $query = $Notifications->getQuery();
        $query->where("#userId = ${userId}");
        $query->limit(1);
        
        $cQuery = clone $query;
        
        $query->XPR('modifiedOnTop', 'datetime', 'IF((#modifiedOn > #lastTime), #modifiedOn, #lastTime)');
        $query->orderBy('#modifiedOnTop', 'DESC');
        
        $lastRec = $query->fetch();
        $lRecModifiedOnTop = $lastRec->modifiedOnTop;
        
        // Ако времето на промяна съвпада с текущото
        if ($lRecModifiedOnTop >= $now) {
            $lRecModifiedOnTop = dt::subtractSecs(5, $now);
        }
        
        $lastModifiedOnKey = $lRecModifiedOnTop;
        $lastModifiedOnKey .= '|' . $lastRec->id;
        
        // Инвалидиране на кеша след 5 минути
        $lastModifiedOnKey .= '|' . (int) (dt::mysql2timestamp($lRecModifiedOnTop) / 300);
        
        $modifiedBefore = dt::subtractSecs(180);
        
        // Инвалидиране на кеша след запазване на подредбата - да не стои запазено до следващото инвалидиране
        $cQuery->where(array("#modifiedOn > '[#1#]'", $modifiedBefore));
        $cQuery->orWhere(array("#lastTime > '[#1#]'", $modifiedBefore));
        $cQuery->limit(1);
        $cQuery->orderBy('modifiedOn', 'DESC');
        $cQuery->orderBy('lastTime', 'DESC');
        if ($cLastRec = $cQuery->fetch()) {
            $lRecLastTime = $lastRec->lastTime;
            
            // Ако времето на промяна съвпада с текущото
            if ($lRecLastTime >= $now) {
                $lRecLastTime = dt::subtractSecs(5, $now);
            }
            
            $lastModifiedOnKey .= '|' . $lRecLastTime;
            $lastModifiedOnKey .= '|' . $cLastRec->id;
        }
        
        $cntQuery = $Notifications->getQuery();
        $cntQuery->where("#userId = {$userId} AND #hidden != 'yes'");
        $cntQuery->show('id');
        $nCnt = $cntQuery->count();
        
        $key = md5($userId . '_' . Request::get('ajax_mode') . '_' . Mode::get('screenMode') . '_' . Request::get('P_bgerp_Notifications') . '_' . Request::get('noticeSearch') . '_' . core_Lg::getCurrent() . '_' . $nCnt);
        
        list($tpl, $modifiedOnKey) = core_Cache::get('Notifications', $key);
        
        if (!$tpl || $modifiedOnKey != $lastModifiedOnKey) {
            
            // Създаваме обекта $data
            $data = new stdClass();
            
            // Създаваме заявката
            $data->query = $Notifications->getQuery();
            
            $data->query->show('msg,state,userId,priority,cnt,url,customUrl,modifiedOn,modifiedBy,searchKeywords');
            
            // Подготвяме полетата за показване
            $data->listFields = 'modifiedOn=Време,msg=Съобщение';
            
            $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
            
            $data->query->XPR('modifiedOnTop', 'datetime', "IF((((#modifiedOn >= '{$modifiedBefore}') || (#state = 'active') || (#lastTime >= '{$modifiedBefore}'))), IF(((#state = 'active') || (#lastTime > #modifiedOn)), #modifiedOn, #lastTime), NULL)");
            $data->query->orderBy('modifiedOnTop', 'DESC');
            
            $data->query->orderBy('modifiedOn=DESC');
            
            if (Mode::is('screenMode', 'narrow') && !Request::get('noticeSearch')) {
                $data->query->where("#state = 'active'");
                
                // Нотификациите, модифицирани в скоро време да се показват
                $data->query->orWhere("#modifiedOn >= '{$modifiedBefore}'");
                $data->query->orWhere("#lastTime >= '{$modifiedBefore}'");
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
            $data->title = tr('Известия');
            
            // Подготвяме лентата с инструменти
            $Notifications->prepareListToolbar($data);
            
            // Рендираме изгледа
            $tpl = $Notifications->renderPortal($data);
            
            core_Cache::set('Notifications', $key, array($tpl, $lastModifiedOnKey), doc_Setup::get('CACHE_LIFETIME'));
        }
        
        //Задаваме текущото време, за последно преглеждане на нотификациите
        Mode::setPermanent('lastNotificationTime', time());
        
        return $tpl;
    }
    
    
    /**
     * Преброява отворените нотификации за всеки потребител
     */
    public static function getOpenCnt($userId = null)
    {
        if (empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId > 0) {
            $query = self::getQuery();
            $cnt = $query->count("#userId = ${userId} AND #state = 'active' AND #hidden = 'no'");
        } else {
            $cnt = 0;
        }
        
        return $cnt;
    }
    
    
    /**
     * Връща времето на последната нотификация
     *
     * @userId NULL|integer $userId
     * @$state NULL|string $state
     * @$state boolean $useHidden
     * @$state string $order
     * @$state string $field
     *
     * @return NULL|datetime
     */
    public static function getLastNotificationTime($userId = null, $state = null, $useHidden = false, $order = 'DESC', $field = 'modifiedOn')
    {
        $query = self::getQuery();
        
        if ($userId) {
            $query->where(array("#userId = '[#1#]'", $userId));
        }
        
        if ($state) {
            $query->where(array("#state = '[#1#]'", $state));
        }
        
        if (!$useHidden) {
            $query->where("#hidden = 'no'");
        }
        
        $query->limit(1);
        
        $query->orderBy($field, $order);
        
        $query->show($field);
        
        $resRec = $query->fetch();
        
        if (!$resRec) {
            
            return ;
        }
        
        return $resRec->{$field};
    }
    
    
    /**
     * Рендира портала
     *
     * @deprecated
     */
    public function renderPortal($data)
    {
        $Notifications = cls::get('bgerp_Notifications');
        
        // Ако се вика по AJAX
        if (!Request::get('ajax_mode')) {
            $divId = $Notifications->getDivId();
            
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
            if (!Mode::is('screenMode', 'narrow')) {
                $tpl->append($data->title, 'PortalTitle');
            }
            
            if ($data->listFilter) {
                $formTpl = $data->listFilter->renderHtml();
                $formTpl->removeBlocks();
                $formTpl->removePlaces();
                $tpl->append($formTpl, 'ListFilter');
            }
            
            // Попълваме долния страньор
            $tpl->append($Notifications->renderListPager($data), 'PortalPagerBottom');
        } else {
            $tpl = new ET('[#PortalTable#]');
        }
        
        // Попълваме таблицата с редовете
        $tpl->append($Notifications->renderListTable($data), 'PortalTable');
        jquery_Jquery::runAfterAjax($tpl, 'getContextMenuFromAjax');
        
        return $tpl;
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
            $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo|admin, rolesForTeams=ceo|manager|admin)', 'caption=Потребител,input,silent,autoFilter');
            
            // Кои полета да се показват
            $data->listFilter->showFields = "{$mvc->searchInputField}, usersSearch";
            
            // Инпутваме полетата
            $data->listFilter->input();
            
            // Бутон за филтриране
            $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
            
            // Ако не е избран потребител по подразбиране
            if (!$data->listFilter->rec->usersSearch) {
                if ($data->listFilter->rec->id) {
                    $f = 'all_users';
                } else {
                    $uArr = $data->listFilter->getField('usersSearch')->type->getUserFromTeams();
                    reset($uArr);
                    $f = key($uArr);
                }
                
                $default = $data->listFilter->getField('usersSearch')->type->fitInDomain($f);
                $data->listFilter->setDefault('usersSearch', $default);
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
        
        $data->query->orderBy('#modifiedOn', 'DESC');
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
                // Обновяваме ключовите думи на нотификациите, ако нямат
                if ($rec->searchKeywords) {
                    continue;
                }
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
    public static function subscribeCounter($tpl = null)
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
    public function act_NotificationsCnt()
    {
        // Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
            
            // Броя на нотификациите
            $notifCnt = static::getOpenCnt();
            
            $res = array();
            
            // Добавяме резултата
            $obj = new stdClass();
            $obj->func = 'notificationsCnt';
            $obj->arg = array('id' => 'nCntLink', 'cnt' => $notifCnt, 'notifyTime' => 1000 * dt::mysql2timestamp(self::getLastNotificationTime(core_Users::getCurrent())));
            
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
            if ($notifCnt != $lastCnt) {
                if (core_Users::getCurrent()) {
                    Mode::setPermanent('NotificationsCnt', $notifCnt);
                } else {
                    Mode::setPermanent('NotificationsCnt', null);
                }
            }
            
            return $res;
        }
    }
    
    
    /**
     * Колко нови за потребителя нотификации има, след последното разглеждане на портала?
     */
    public static function getNewCntFromLastOpen($userId = null, $arg = null)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $lastTime = bgerp_LastTouch::get('portal', $userId);
        
        if (!$lastTime) {
            $lastTime = '2000-01-01';
        }
        
        $res = self::count("#state = 'active' AND #hidden = 'no' AND #userId = {$userId} AND #modifiedOn >= '{$lastTime}'");
        
        
        if (is_array($arg) && $arg['priority']) {
            if ($msgRec = self::fetch("#state = 'active' AND #hidden = 'no' AND #userId = {$userId} AND #modifiedOn >= '{$lastTime}' AND #priority = 'alert'")) {
                $priority = 'alert';
            } elseif ($msgRec = self::fetch("#state = 'active' AND #hidden = 'no' AND #userId = {$userId} AND #modifiedOn >= '{$lastTime}' AND #priority = 'warning'")) {
                $priority = 'warning';
            } else {
                $priority = 'normal';
            }
            
            $res = array('cnt' => $res, 'priority' => $priority);
            
            if (isset($msgRec)) {
                $res['msg'] = $msgRec->msg;
            }
        }
        
        return $res;
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
        // Премахваме всички тагове без 'a'
        // Това е необходимо за да определим когато има промяна в състоянието на някоя нотификация
        // Трябва да се премахват другите тагове, защото цвета се променя през няколко секунди
        // и това би накарало всеки път да се обновяват нотификациите.
        
        $status = strip_tags($status, '<a>');
        
        $status = preg_replace('/context-holder[0-9]{1,4}_[0-9]{1,2}/i', '', $status);
        
        $hash = md5(trim($status));
        
        return $hash;
    }
    
    
    /**
     * Изтрива стари записи в bgerp_Notifications
     */
    public function cron_DeleteOldNotifications()
    {
        $closedBefore = dt::addDays(-1 * (bgerp_Setup::get('NOTIFICATION_KEEP_DAYS') / (24 * 3600)));
        $modifiedBefore = dt::addDays(-1 * ((bgerp_Setup::get('NOTIFICATION_KEEP_DAYS') * 2) / (24 * 3600)));
        
        $res = self::delete("((#closedOn IS NOT NULL) AND (#closedOn < '{$closedBefore}')) OR (#modifiedOn < '{$modifiedBefore}')");
        
        if ($res) {
            $this->logNotice("Бяха изтрити {$res} записа");
            
            return "Бяха изтрити {$res} записа от " . $this->className;
        }
    }
    
    
    /**
     * Изтриваме недостъпните нотификации към съответните потребители
     */
    public function cron_HideInaccesable()
    {
        $query = self::getQuery();
        
        $before = dt::subtractSecs(180000); // преди 50 часа
        $query->setUnion(array("#modifiedOn >= '[#1#]'", $before));
        $query->setUnion(array("#closedOn >= '[#1#]'", $before));
        $query->setUnion(array("#lastTime >= '[#1#]'", $before));
        $query->setUnion(array("#activatedOn >= '[#1#]'", $before));
        
        $query->orderBy('modifiedOn', 'DESC');
        
        while ($rec = $query->fetch()) {
            $urlArr = self::getUrl($rec);
            
            $act = strtolower($urlArr['Act']);
            
            if ($act == 'default') {
                $act = 'list';
            }
            
            if (($act != 'single') && ($act != 'list')) {
                continue;
            }
            
            try {
                $ctr = $urlArr['Ctr'];
                
                if (!$ctr) {
                    continue;
                }
                
                if ((!cls::load($ctr, true)) || ($urlArr['id'] && !($cRec = $ctr::fetch($urlArr['id'])))) {
                    self::delete($rec->id);
                    self::logInfo('Изтрита нотификация за премахнат ресурс', $rec->id);
                } else {
                    $haveRight = $ctr::haveRightFor($act, $urlArr['id'], $rec->userId);
                    if (!$haveRight && ($rec->hidden == 'no')) {
                        $rec->hidden = 'yes';
                        self::save($rec, 'hidden,modifiedOn,modifiedBy');
                        
                        self::logInfo('Скрит недостъпен ресурс', $rec->id);
                    } elseif ($haveRight && ($rec->hidden == 'yes')) {
                        if (!$rec->cnt) {
                            continue;
                        }
                        
                        if (!$cRec || ($cRec->state == 'rejected')) {
                            continue;
                        }
                        
                        $rec->hidden = 'no';
                        self::save($rec, 'hidden');
                        
                        self::logInfo('Показан достъпен ресурс', $rec->id);
                    }
                }
            } catch (core_exception_Expect $e) {
                reportException($e);
                continue;
            }
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = null)
    {
        if ($rec->id) {
            if ($fields !== null) {
                $fields = arr::make($fields, true);
            }
            
            // Ако няма да се записва само 'lastTime', сетваме стойността от modifiedOn
            if (!isset($fields) || (!$fields['lastTime'] && $fields['modifiedOn'])) {
                $modifiedOn = self::fetchField($rec->id, 'modifiedOn', false);
                $rec->lastTime = $modifiedOn;
                
                if ($fields !== null) {
                    $fields['lastTime'] = 'lastTime';
                }
            }
        }
        
        if (isset($rec->url)) {
            $rec->urlId = self::prepareUrlId($rec->url);
        }
        
        if (isset($rec->customUrl)) {
            $rec->customUrlId = self::prepareUrlId($rec->customUrl);
        }
    }
}
