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
     * Максимална дължина на показваните заглавия
     */
    const maxLenTitle = 120;
    
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
        $this->FLD('lastTime', 'datetime', 'caption=Предишното време, input=none');
        
        $this->setDbUnique('url, userId');
        $this->setDbIndex('userId');
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
        
        // Ако само се запознава със съдържанието - не се изчиства
        if (Request::get('ОnlyMeet')) return ;
        
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
            bgerp_Notifications::save($rec, 'state,modifiedOn,closedOn,modifiedBy');
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
     * Скрива записите, които са към съответния сингъл на документа
     * 
     * @param string $className
     * @param integer $clsId
     */
    public static function hideNotificationsForSingle($className, $clsId, $hidden = 'no')
    {
        $query = self::getQuery();
        $query->where("#hidden = '{$hidden}'");
        $className = strtolower($className);
        
        $query->where(array("LOWER(#url) LIKE '%/[#1#]/single/[#2#]' OR LOWER(#url) LIKE '%/[#1#]/single/[#2#]/%' OR LOWER(#customUrl) LIKE '%/[#1#]/single/[#2#]' OR LOWER(#customUrl) LIKE '%/[#1#]/single/[#2#]/%'", $className, $clsId));
        
        while ($rec = $query->fetch()) {
            $rec->hidden = ($hidden == 'no') ? 'yes' : 'no';
            
            if ($rec->hidden == 'no') {
                try {
                    $urlArr = self::getUrl($rec);
                    $act = strtolower($urlArr['Act']);
                    
                    $ctr = $urlArr['Ctr'];
                    if (!$ctr::haveRightFor($act, $urlArr['id'], $rec->userId)) continue;
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
     * @param integer $clsId
     */
    public static function showNotificationsForSingle($className, $clsId)
    {
        self::hideNotificationsForSingle($className, $clsId, 'yes');
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
        $url = self::getUrl($rec);
        
        if($rec->cnt > 1) {
            //  $row->msg .= " ({$rec->cnt})";
        }
        
        $attr = array();
        if($rec->state == 'active') {
            $attr['style'] = 'font-weight:bold;';
            $attr['onclick'] = 'render_forceReloadAfterBack()';
        } else {
            $attr['style'] = 'color:#666;';
        }
        
        if (!Mode::isReadOnly() && ($rec->userId == core_Users::getCurrent())) {
            $attr['class'] .= " ajaxContext";
            $attr['name'] = 'context-holder';
            ht::setUniqId($attr);
            $replaceId = $attr['id'];
            unset($attr['name'], $attr['id']);
            
            $dataUrl =  toUrl(array(get_called_class(), 'getContextMenu', $rec->id, 'replaceId' => $replaceId), 'local');
            $attr['data-id'] = $replaceId;
            $attr['data-url'] = $dataUrl;
        }
        
        // Превеждаме съобщението
        // Спираме преовада и въте, ако има за превеждане, тогава се превежда
        $row->msg = tr("|*{$row->msg}");
        $row->msg = str::limitLen($row->msg, self::maxLenTitle, 20, " ... ", TRUE);
        
        $row->msg = ht::createLink($row->msg, $url, NULL, $attr);
    }
    
    
    /**
     * Екшън връщащ бутоните за контектстното меню
     */
    function act_getContextMenu()
    {
        $id = Request::get('id', 'int');
        expect($id);
        
        expect($rec = $this->fetch($id));
        
        expect($rec->userId == core_Users::getCurrent());
        
    	expect($replaceId = Request::get('replaceId', 'varchar'));
    	
    	$tpl = new core_ET();
        
    	if ($rec->userId != core_Users::getCurrent()) return array();
    	
    	$url = self::getUrl($rec);

    	// Отваряне в нов таб
    	$newTabBtn = ht::createLink(tr('Отвори в нов таб'), $url, NULL, array('ef_icon' => "img/16/tab-new.png", 'title' => 'Отваряне в нов таб', "class" => "button", 'target' => '_blank'));
    	$tpl->append($newTabBtn);
        
    	if ($rec->state == 'active') {
    	    // Запознаване със съдържанието, но без отмаркиране
    	    $meetUrl = $url;
    	    $meetUrl['ОnlyMeet'] = TRUE;
    	    $introBtn = ht::createLink(tr('Запознаване'), $meetUrl, NULL, array('ef_icon' => "img/16/see.png", 'title' => 'Запознаване със съдържанието без отмаркиране', "class" => "button"));
    	    $tpl->append($introBtn);
    	}
        
    	// Маркиране/отмаркиране на текст
    	$markUrl = array(get_called_class(), 'mark', $rec->id);
    	$markText = 'Маркиране';
        $iconMark = "img/16/mark.png";
    	if ($rec->state == 'active') {
    	    $markText = 'Отмаркиране';
            $iconMark = "img/16/unmark.png";
    	}
    	$attr = array('ef_icon' => $iconMark, 'title' => $markText . ' на нотификацията', "class" => "button", 'data-url' => toUrl($markUrl, 'local'));
    	$attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
    	
    	$markBtn = ht::createLink(tr($markText), $markUrl, NULL, $attr);

    	$tpl->append($markBtn);
    	
    	// Ако има записи за отабониране от нотификациите
    	self::getAutoNotifyArr($rec, NULL, $haveStopped);
    	if ($haveStopped) {
    	    $unsubscribeUrl = array(get_called_class(), 'unsubscribe', $rec->id);
    	    $attr = array('ef_icon' => "img/16/no-bell.png", 'title' => 'Автоматично отписване от нотификациите', "class" => "button", 'data-url' => toUrl($unsubscribeUrl, 'local'));
    	    $attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
    	    $unsubscribeBtn = ht::createLink('Отписване', $unsubscribeUrl, NULL, $attr);
    	    $tpl->append($unsubscribeBtn);
    	}
    	
    	// Бутон за настройки
    	$ctr = $url['Ctr'];
    	if ($ctr) {
    	    if (cls::load($ctr, TRUE)) {
        	    $ctrInst = cls::get($ctr);
        	    $settingsUrl = array(get_called_class(), 'settings', $rec->id, 'ret_url' => TRUE);
        	    if (($ctrInst instanceof doc_Folders) || ($ctrInst instanceof doc_Threads) || ($ctrInst instanceof doc_Containers) || (cls::haveInterface('doc_DocumentIntf', $ctrInst))) {
        	        $settingsBtn = ht::createLink('Настройки', $settingsUrl, NULL, array('ef_icon' => "img/16/cog.png", 'title' => 'Настойки за получаване на нотификация', "class" => "button"));
        	        $tpl->append($settingsBtn);
        	    }
    	    }
    	}

    	// Ако сме в AJAX режим
    	if(Request::get('ajax_mode')) {
    		$resObj = new stdClass();
    		$resObj->func = "html";
    		$resObj->arg = array('id' => $replaceId, 'html' => $tpl->getContent(), 'replace' => TRUE);
    		
    		$res = array_merge(array($resObj));
    		
    		return $res;
    	} else {
    	    
    		return $tpl;
    	}
    }
    
    
    /**
     * Помощна функция за спиране и пускане на нотификациите
     * 
     * @param stdClass|integer $rec
     * @param NULL|string $update
     * @param NULL|array $haveStopped
     * 
     * @return array
     */
    protected static function getAutoNotifyArr($rec, $update = NULL, &$haveStopped = NULL)
    {
        $resValsArr = array();
        
        $rec = self::fetchRec($rec);
        
        if (!$rec) return $resValsArr;
        
        // Вземаме необходимите параметри от URL-то
        $url = self::getUrl($rec);
        
        $ctr = $url['Ctr'];
        $act = $url['Act'];
        $dId = $url['id'];
        
        if (cls::load($ctr, TRUE)) {
            
            $clsInst = cls::get($ctr);
            
            if (($clsInst instanceof core_Manager) && ($ctr::haveRightFor($act, $dId))) {
                $folderId = $url['folderId'];
                $threadId = $url['threadId'];
                $containerId = $url['containerId'];
                
                if ($dId) {
                    if ($dRec = $ctr::fetch($dId)) {
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
        if (strpos($msg, '|отворени теми в|') !== FALSE) {
            $stopNotifyArr['folOpenings'] = 'doc_Folders';
        } elseif ((strpos($msg, '|добави|') !== FALSE) || (strpos($msg, '|хареса') !== FALSE) || (strpos($msg, '|промени|') !== FALSE)) {
            if (strpos($msg, '|входящ имейл|') !== FALSE) {
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
        }
        
        $stoppedArr = $valsArr = array();
        
        $fKey = $tKey = NULL;
        
        // Определяме стойностите, които трябва да се изключат
        foreach ($stopNotifyArr as $kVal => $kClass) {
            
            // За папките
            if ($kClass == 'doc_Folders') {
                if (!$folderId) continue;
                
                if (!doc_Folders::haveRightFor('single', $folderId)) continue;
                
                if (!$fKey) {
                    
                    $fKey = doc_Folders::getSettingsKey($folderId);
                    
                    $valsArr[$fKey] = core_Settings::fetchKeyNoMerge($fKey);
                }
                
                $key = $fKey;
            }
            
            // За нишките
            if ($kClass == 'doc_Threads') {
                if (!$threadId) continue;
                
                if (!doc_Threads::haveRightFor('single', $threadId)) continue;
                
                if (!$tKey) {
                    
                    $tKey = doc_Threads::getSettingsKey($threadId);
                    
                    $valsArr[$tKey] = core_Settings::fetchKeyNoMerge($tKey);
                }
                
                $key = $tKey;
            }
            
            // Ако преди това не е била забранане стойност
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
            $notifyVerbMap = array('notify' => 'Нов документ', 'personalEmailIncoming' => 'Личен имейл', 'folOpenings' => 'Отворени теми', 'newThread' => 'Нова тема', 'newDoc' => 'Нов документ');
            
            $notifyMsg = '';
            
            foreach($stoppedArr as $cls => $v) {
                
                if ($cls == 'doc_Folders') {
                    $title = doc_Folders::getLinkForObject($folderId);
                    $key = $fKey;
                } elseif ($cls == 'doc_Threads') {
                    $title = doc_Threads::getLinkForObject($threadId);
                    $key = $tKey;
                }
                
                $notifyMsg .= ($notifyMsg) ? '<br>' : '';
                
                if ($update == 'revert') {
                    $txt = "|Върнати настройки за нотифициране в|*";
                } else {
                    $txt = "|Спряно нотифициране в|*";
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
                
                $notifyMsg .= "<span  style='color: #00ff00;'>" . $notifyTxt . '</span>';;
                
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
            $haveStopped = TRUE;
        }
        
        return $resValsArr;
    }
    
    
    /**
     * Екшън за автоматично отписване от нотификации
     * 
     * @return Redirect|array
     */
    function act_Unsubscribe()
    {
        $id = Request::get('id', 'int');
        expect($id);
        
        expect($rec = $this->fetch($id));
        
        expect($rec->userId == core_Users::getCurrent());
        
        $valsArr = self::getAutoNotifyArr($rec, 'unsubscribe');
        
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
            status_Messages::newStatus($valsArr['notifyMsg'], 'notice', NULL, 60, $hitId);
            $res = status_Messages::getStatusesData(Request::get('hitTime', 'int'), 0, $hitId);
        }
        
        $res[] = (object)array('func' => 'closeContextMenu');
        
        // Ако се отписваме от активна нотификация - да не е болд
        if ($rec->state == 'active') {
            $res = array_merge(Request::forward(array(get_called_class(), 'mark', $rec->id)), $res);
        }
        
        return $res;
    }
    
    
    /**
     * Екшън за маркиране/отмаркиране на нотификация
     *
     * @return Redirect
     */
    function act_Mark()
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
        
        self::save($rec, 'state, modifiedOn, modifiedBy');
        
        // Ако сме активирали нотификацията, връщаме автоматично нилираните настройки за нотифициране
        if ($rec->state == 'active') {
            $valsArr = self::getAutoNotifyArr($rec, 'revert');
        }
        
        $this->logWrite($act . ' на нотификация', $rec->id);
        
        $notifyMsg = $valsArr['notifyMsg'];
        
        if (!Request::get('ajax_mode')) {
            
            $notifyMsg = $notifyMsg ? "<br>" . $notifyMsg : '';
            
            return new Redirect(array('Portal', 'show'), "|Успепшно {$msg} нотификацията|*{$notifyMsg}");
        }
        
        $res = $this->action('render');

        // Добавяме резултата и броя на нотифиакциите
        if (is_array($res)) {
            
            $notifCnt = static::getOpenCnt();
            
            $obj = new stdClass();
            $obj->func = 'notificationsCnt';
            $obj->arg = array('id'=>'nCntLink', 'cnt' => $notifCnt, 'notifyTime' => 1000 * dt::mysql2timestamp(self::getLastNotificationTime(core_Users::getCurrent())));

            
            if ($notifyMsg) {
                $hitId = rand();
                status_Messages::newStatus($notifyMsg, 'notice', NULL, 60, $hitId);
                $res = array_merge($res, status_Messages::getStatusesData(Request::get('hitTime', 'int'), 0, $hitId));
            }
            
            $res[] = $obj;
        }
        
        return $res;
    }
    
    
    /**
     * Промяна на настройките за нотификации към папки/нишки
     */
    function act_Settings()
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
        
        if (!cls::load($ctr, TRUE) || !$ctr::haveRightFor($act, $dId)) {
            
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
        
        $enumChoise = 'enum(default=Автоматично, yes=Винаги, no=Никога)';
        $enumTypeArr = array('input' => 'input', 'maxRadio' => 3, 'columns' => 3);
        
        $notifyDefArr = array();
        
        $valsArr = array();
        
        // Настройки за папк
        if ($folderId) {
            $fKey = doc_Folders::getSettingsKey($folderId);
            
            $folderTitle = doc_Folders::getLinkForObject($folderId);
            
            $fCaption = "Известяване в|* {$folderTitle} |при";
            
            $enumTypeArr['caption'] = $fCaption . '->Нов документ';
            $form->FNC('newDoc', $enumChoise, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Нова тема';
            $form->FNC('newThread', $enumChoise, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Отворени теми';
            $form->FNC('folOpenings', $enumChoise, $enumTypeArr);
            
            $enumTypeArr['caption'] = $fCaption . '->Личен имейл';
            $form->FNC('personalEmailIncoming', $enumChoise, $enumTypeArr);
            
            $sArr[$fKey] = array('newDoc', 'newThread', 'folOpenings', 'personalEmailIncoming');
            
            // Добавяме стойностите по подразбиране
            $valsArr[$fKey] = core_Settings::fetchKeyNoMerge($fKey);
            setIfNot($valsArr[$fKey]['newDoc'], 'default');
            setIfNot($valsArr[$fKey]['newThread'], 'default');
            setIfNot($valsArr[$fKey]['folOpenings'], 'default');
        }
        
        // Настройки за нишка
        if ($containerId && $threadId) {
            $tKey = doc_Threads::getSettingsKey($threadId);
            
            $threadTitle = doc_Threads::getLinkForObject($threadId);
            $tCaption = "Известяване в|* {$threadTitle} |при";
            $enumTypeArr['caption'] = $tCaption . '->Нов документ';
            
            $form->FNC('notify', $enumChoise, $enumTypeArr);
            
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
                $pKey = core_Settings::prepareKey($key);
                $sRec = core_Settings::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]'", $pKey, core_Users::getCurrent()));
                core_Settings::logWrite('Промяна на настройките', $sRec);
            }
            
            $this->logWrite('Промяна на настройки за нотифициране', $rec->id);
            
            return new Redirect($retUrl);
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', NULL, 'ef_icon = img/16/disk.png, title=Запис на настройките');
        $form->toolbar->addBtn('Отказ', $retUrl, NULL, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Добавяме титлата на формата
        $form->title = "Настройка за нотифициране";
        
        $tpl = $form->renderHtml();
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Помощна фунцкия за парсирана не URL-то
     * 
     * @param mixed $rec
     * @return array
     */
    public static function getUrl($rec)
    {
        $rec = self::fetchRec($rec);
        
        return parseLocalUrl($rec->customUrl ? $rec->customUrl : $rec->url, FALSE);
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
        
        $cQuery = clone $query;
                
        $query->orderBy("#modifiedOn", 'DESC');
        $lastRec = $query->fetch();
        
        $lastModifiedOnKey = $lastRec->modifiedOn;
        $lastModifiedOnKey .= '|' . $lastRec->id;
        
        $modifiedBefore = dt::subtractSecs(180);
        
        // Инвалиидиране на кеша след запазване на подредбата -  да не стои запазено до следващото инвалидиране
        $cQuery->where(array("#modifiedOn >= '[#1#]'", $modifiedBefore));
        if ($cLastRec = $cQuery->fetch()) {
            $lastModifiedOnKey .= '|' . $lastRec->lastTime;
            $lastModifiedOnKey .= '|' . $cLastRec->id;
        }
        
        $key = md5($userId . '_' . Request::get('ajax_mode') . '_' . Mode::get('screenMode') . '_' . Request::get('P_bgerp_Notifications') . '_' . Request::get('noticeSearch') . '_' . core_Lg::getCurrent());
        
        list($tpl, $modifiedOnKey) = core_Cache::get('Notifications', $key);
        
        if(!$tpl || $modifiedOnKey != $lastModifiedOnKey) {

            // Създаваме обекта $data
            $data = new stdClass();
            
            // Създаваме заявката
            $data->query = $Notifications->getQuery();
            
            $data->query->show("msg,state,userId,priority,cnt,url,customUrl,modifiedOn,modifiedBy,searchKeywords");
            
            // Подготвяме полетата за показване
            $data->listFields = 'modifiedOn=Време,msg=Съобщение';
            
            $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
            
            $data->query->XPR('modifiedOnTop', 'datetime', "IF((((#modifiedOn > '{$modifiedBefore}') || (#state = 'active'))), IF((#state = 'active'), #modifiedOn, #lastTime), NULL)");
            $data->query->orderBy("modifiedOnTop", "DESC");
            
            $data->query->orderBy("modifiedOn=DESC");
            
            if(Mode::is('screenMode', 'narrow') && !Request::get('noticeSearch')) {
                $data->query->where("#state = 'active'");
                
                // Нотификациите, модифицирани в скоро време да се показват
                $data->query->orWhere("#modifiedOn > '{$modifiedBefore}'");
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

            core_Cache::set('Notifications', $key, array($tpl, $lastModifiedOnKey), doc_Setup::get('CACHE_LIFETIME'));
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
    public static function getLastNotificationTime($userId = NULL, $state = NULL, $useHidden = FALSE, $order = 'DESC', $field = 'modifiedOn')
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
        
        if (!$resRec) return ;
        
        return $resRec->{$field};
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
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->view = 'horizontal';
        
        if(strtolower(Request::get('Act')) == 'show'){
            
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
            if(!$data->listFilter->rec->usersSearch) {
                
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
            $obj->arg = array('id'=>'nCntLink', 'cnt' => $notifCnt, 'notifyTime' => 1000 * dt::mysql2timestamp(self::getLastNotificationTime(core_Users::getCurrent())));
            
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
        // и това би накарало всеки път да се обновяват нотификациите.
        
        $status = strip_tags($status, '<a>');
        
        $status = preg_replace('/context-holder[0-9]{1,4}_[0-9]{1,2}/i', '', $status);
        
        $hash = md5(trim($status));
        
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
    
    
    /**
     * Изтриваме недостъпните нотификации, към съответните потребители
     */
    function cron_HideInaccesable()
    {
        $query = self::getQuery();
        $query->where("#hidden = 'no'");
        
        $query->orderBy('modifiedOn', 'DESC');
        
        while ($rec = $query->fetch()) {
            $urlArr = self::getUrl($rec);
            
            $act = strtolower($urlArr['Act']);
            
            if ($act == 'default') {
                $act = 'list';
            }
            
            if (($act != 'single') && ($act != 'list')) continue;
            
            try {
                $ctr = $urlArr['Ctr'];
                
                if (!$ctr) continue;
                
                if ((!cls::load($ctr, TRUE)) || ($urlArr['id'] && !$ctr::fetch($urlArr['id']))) {
                    self::delete($rec->id);
                    self::logDebug("Изтрита нотифакаци за премахнат ресурс", $rec->id);
                } elseif (!$ctr::haveRightFor($act, $urlArr['id'], $rec->userId)) {
                    $rec->hidden = 'yes';
                    self::save($rec, 'hidden,modifiedOn,modifiedBy');
                    
                    self::logDebug("Скрит недостъпен ресурс", $rec->id);
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
    public static function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
        if ($rec->id) {
            $modifiedOn = self::fetchField($rec->id, 'modifiedOn', FALSE);
            $rec->lastTime = $modifiedOn;
            
            if ($fields !== NULL) {
                $fields = arr::make($fields, TRUE);
                $fields['lastTime'] = 'lastTime';
            }
        }
    }
}
