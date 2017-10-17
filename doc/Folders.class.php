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
class doc_Folders extends core_Master
{
    
    /**
     * Максимална дължина на показваните заглавия 
     */
    const maxLenTitle = 48;
    
    
    /**
     * Интерфейси
     */
    var $interfaces = 'core_SettingsIntf';
    
    
    /**
     * 10 секунди време за опресняване на нишката
     * 
     * @see plg_RefreshRows
     */
    public $refreshRowsTime = 10000;
    

    /**
     * Кое поле да се гледа за промяна и да се пуска обновяването
     * 
     * @see plg_RefreshRows
     */
    public $refreshRowsCheckField = 'last';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg,plg_Search, doc_ContragentDataIntf, plg_Sorting, plg_RefreshRows';
    
    
    /**
     * Заглавие
     */
    var $title = "Папки с нишки от документи";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,title,type=Тип,inCharge=Отговорник,threads=Нишки,last=Последно';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой може да вижда единичния изглед
     */
    var $canSingle = 'powerUser';

    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';

    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'powerUser';
    
    
    /**
     * 
     */
    var $canNewdoc = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'title';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Папка';
    

    /**
     * Масив в id-та на папки, които трябва да се обновят на Shutdown
     */
    var $updateByContentOnShutdown = array();
    
    
    /**
     * Масив с id-та, за които не трябва да се праща нотификация
     */
    var $preventNotification = array();


    /**
     * Флаг, че заявките, които са към този модел лимитирани до 1 запис, ще са HIGH_PRIORITY
     */
    public $highPriority = TRUE;


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Определящ обект за папката
        $this->FLD('coverClass' , 'class(interface=doc_FolderIntf)', 'caption=Корица->Клас');
        $this->FLD('coverId' , 'int', 'caption=Корица->Обект');
        
        // Информация за папката
        $this->FLD('title' , 'varchar(255,ci)', 'caption=Заглавие, tdClass=folderListTitle');
        $this->FLD('status' , 'varchar(128)', 'caption=Статус');
        $this->FLD('state' , 'enum(active=Активно,opened=Отворено,rejected=Оттеглено,closed=Затворено)', 'caption=Състояние');
        $this->FLD('allThreadsCnt', 'int', 'caption=Нишки->Всички');
        $this->FLD('openThreadsCnt', 'int', 'caption=Нишки->Отворени');
        $this->FLD('last' , 'datetime(format=smartTime)', 'caption=Последно');
        $this->FLD('statistic', 'blob(serialize,compress)', 'caption=Статистика, input=none');
        
        $this->setDbUnique('coverId,coverClass');
    }


    /**
     * Редиректва към съдържанието на папката
     */
    public function act_Single()
    {
        expect($id = Request::get('id', 'int'));

        return new Redirect(array('doc_Threads', 'list', 'folderId' => $id));
    }
    
    
    /**
     * Връща линк към документа
     */
    static function getLink($id, $maxLength = FALSE, $attr = array())
    {
        $rec = static::fetch($id);
        $haveRight = static::haveRightFor('single', $rec);
        
        $iconStyle = 'background-image:url(' . static::getIconImg($rec, $haveRight) . ');';
        $url = array('doc_Folders', 'single', $id);
        
        $title = static::getVerbal($rec, 'title');
        
        if(!static::haveRightFor('single', $id)) {
            $url =  array();
        }
        
        $attr['class'] .= ' linkWithIcon';
        $attr['style'] .= $iconStyle;
        
        if ($rec->state == 'rejected') {
            $attr['class'] .= ' state-rejected';
        }

        $link = ht::createLink($title, $url, NULL, $attr);
        
        return $link;
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
        if (!self::logToFolder('read', $action, $objectId, $lifeDays)) {
            
            return parent::logRead($action, $objectId, $lifeDays);
        }
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
        if (!self::logToFolder('write', $action, $objectId, $lifeDays)) {
            
            return parent::logWrite($action, $objectId, $lifeDays);
        }
    }
    
    
    /**
     * 
     * 
     * @param string $type
     * @param string $action
     * @param integer|NULL $objectId
     * @param integer|NULL $lifeDays
     */
    protected static function logToFolder($type, $action, $objectId, $lifeDays)
    {
        if (!$objectId) return ;
        
        $allowedType = array('read', 'write');
        
        if (!in_array($type, $allowedType)) {
            
            return ;
        }
        
        try {
            $rec = self::fetch($objectId);
            
            $type = strtolower($type);
            $type = ucfirst($type);
            $fncName = 'log' . $type;
            
            if (!cls::load($rec->coverClass, TRUE)) return ;
            
            $inst = cls::get($rec->coverClass);
            
            $inst->$fncName($action, $rec->coverId, $lifeDays);
            
            return TRUE;
        } catch (core_exception_Expect $e) {
            
            reportException($e);
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
     	// Добавяме поле във формата за търсене
		$data->listFilter->FNC('users', 'users(rolesForAll = |officer|manager|ceo|)', 'caption=Потребител,input,silent,autoFilter');
		$data->listFilter->FNC('order', 'enum(pending=Първо чакащите,last=Сортиране по "последно")', 'caption=Подредба,input,silent,autoFilter');
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		// Показваме само това поле. Иначе и другите полета
		// на модела ще се появят
		$data->listFilter->showFields = 'search,users,order';
		$data->listFilter->input('search,users,order', 'silent');
		
		if(!$data->listFilter->rec->users) {
			$data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
		}
		
		if(!$data->listFilter->rec->search) {
			$data->query->where("'{$data->listFilter->rec->users}' LIKE CONCAT('%|', #inCharge, '|%')");
			$data->query->orLikeKeylist('shared', $data->listFilter->rec->users);
			$data->title = 'Папките на |*<span class="green">' .
			$data->listFilter->getFieldType('users')->toVerbal($data->listFilter->rec->users) . '</span>';
		} else {
			$data->title = 'Търсене на папки отговарящи на |*<span class="green">"' .
			$data->listFilter->getFieldType('search')->toVerbal($data->listFilter->rec->search) . '"</span>';
		}
		
		switch($data->listFilter->rec->order) {
			case 'last' :
				$data->query->orderBy('#last', 'DESC');
			case 'pending' :
		default :
		        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'opened' THEN 1 WHEN 'active' THEN 2 ELSE 3 END)");
				$data->query->orderBy('#orderByState=ASC,#last=DESC');
		}
    }
    
    
    /**
     * Връща информация дали потребителя има достъп до посочената папка
     */
    static function haveRightToFolder($folderId, $userId = NULL)
    {
        if(!($folderId > 0)) return FALSE;

        $rec = doc_Folders::fetch($folderId);
        
        return doc_Folders::haveRightToObject($rec, $userId);
    }
    

    /**
     * Дали посоченият (или текущият ако не е посочен) потребител има право на достъп до този обект
     * Обекта трябва да има полета inCharge, access и shared
     */
    static function haveRightToObject($rec, $userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // Всеки има право на достъп до папката за която отговаря
        if ($rec->inCharge && ($rec->inCharge == $userId)) return TRUE;
        
        // Всеки има право на достъп до папките, които са му споделени
        if(strpos($rec->shared, '|' . $userId . '|') !== FALSE) return TRUE;
        
        // Всеки има право на достъп до общите папки
        if (($rec->access == 'public') && !core_Users::isContractor()) return TRUE;
        
        // 'ceo' има достъп до всяка папка
        if (core_Users::haveRole('ceo', $userId)) {
            
            // с изключение на личните и секретните на други CEO
            if (core_Users::haveRole('ceo', $rec->inCharge) && (($rec->access == 'private') || ($rec->access == 'secret'))) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
        
        // Вземаме членовете на екипа на потребителя
        $teamMembers = core_Users::getTeammates($userId);
        
        // Дали обекта има отговорник - съекипник
        $fromTeam = strpos($teamMembers, '|' . $rec->inCharge . '|') !== FALSE;
        
        // Ако папката е екипна, и е на член от екипа на потребителя, и потребителя е manager или officer или executive - има достъп
        if($rec->access == 'team' && $fromTeam && core_Users::haveRole('manager,officer,executive', $userId)) return TRUE;
        
        // Ако собственика на папката има права 'manager' или 'ceo' отказваме достъпа
        if(core_Users::haveRole('manager,ceo', $rec->inCharge)) return FALSE;
        
        // Ако папката е лична на член от екипа, и потребителя има права 'manager' - има достъп
        if($rec->access == 'private' && $fromTeam && core_Users::haveRole('manager', $userId)) return TRUE;
        
        // Ако никое от горните не е изпълнено - отказваме достъпа
        return FALSE;
    }
    
    
    /**
     * След преобразуване към вербални данни на записа
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        
        $openThreads = $mvc->getVerbal($rec, 'openThreadsCnt');
        
        if($rec->openThreadsCnt) {
            $row->threads = "<span style='float-right; color:#5a6;'>$openThreads</span>";
        }
        
        $row->threads .= "<span style='float:right;'>&nbsp;&nbsp;&nbsp;" . $mvc->getVerbal($rec, 'allThreadsCnt') . "</span>";
        
        $attr = array();
        $attr['class'] = 'linkWithIcon';
        
        if(mb_strlen($row->title) > self::maxLenTitle) {
            $attr['title'] = $row->title;
        }

        $row->title = str::limitLen($row->title, self::maxLenTitle);
        
        $haveRight = $mvc->haveRightFor('single', $rec);
        if(core_Packs::isInstalled('colab') && core_Users::haveRole('partner')){
        	$haveRight = colab_Folders::haveRightFor('single', $rec);
        }
        
        // Иконката на папката според достъпа и
        $img = static::getIconImg($rec, $haveRight);
        
        // Ако състоянието е оттеглено
        if ($rec->state == 'rejected') {
            
           // Добавяме към класа да е оттеглено
            $attr['class'] .= ' state-rejected';
        }
        
        if($haveRight) {
            $attr['style'] = 'background-image:url(' . $img . ');';
            if(!(core_Packs::isInstalled('colab') && core_Users::haveRole('partner'))){
            	$link = array('doc_Threads', 'list', 'folderId' => $rec->id);
            } else {
            	$link = array('colab_Threads', 'list', 'folderId' => $rec->id);
            }
            
            // Ако е оттеглен
            if ($rec->state == 'rejected') {
                
                // Да сочи към коша
                $link['Rejected'] = 1;
            }
            
            if(Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')){
            	$link = array();
            }
            
            $row->title = ht::createLink($row->title, $link, NULL, $attr);
        } else {
            $attr['style'] = 'color:#777;background-image:url(' . $img . ');';
            $row->title = ht::createElement('span', $attr, $row->title);
        }
        
		if(cls::load($rec->coverClass, TRUE)){
			$typeMvc = cls::get($rec->coverClass);
			
			$attr['style'] = 'background-image:url(' . sbf($typeMvc->singleIcon) . ');';
			
			if($typeMvc->haveRightFor('single', $rec->coverId)) {
				$row->type = ht::createLink(tr($typeMvc->singleTitle), array($typeMvc, 'single', $rec->coverId), NULL, $attr);
			} else {
				$attr['style'] .= 'color:#777;';
				$row->type = ht::createElement('span', $attr, tr($typeMvc->singleTitle));
			}
		} else {
			$row->type = "<span class='red'>" . tr('Проблем при показването') . "</span>";
		}
    }
    

    /**
     * Добавя бутони за нова фирма, лице и проект
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if(crm_Companies::haveRightFor('add')){
    		$data->toolbar->addBtn('Нова фирма', array('crm_Companies', 'add', 'ret_url' => TRUE), 'ef_icon=img/16/office-building-add.png', 'title=Създаване на нова визитка на фирма');
    	}
       
    	if(crm_Persons::haveRightFor('add')){
    		$data->toolbar->addBtn('Ново лице', array('crm_Persons', 'add', 'ret_url' => TRUE), 'ef_icon=img/16/vcard-add.png', 'title=Създаване на нова визитка на лице');
    	}
       
    	if(doc_UnsortedFolders::haveRightFor('add')){
    		$data->toolbar->addBtn('Нов проект', array('doc_UnsortedFolders', 'add', 'ret_url' => TRUE), 'ef_icon=img/16/project-archive-add.png', 'title=Създаване на нов проект');
    	}
    }
    
    
    static function updateFolderByContent($id)
    {
        $mvc = cls::get('doc_Folders');
        $mvc->updateByContentOnShutdown[$id] = $id;
    }

    
    /**
     * Обновява информацията за съдържанието на дадена папка
     */
    static function on_Shutdown($mvc)
    {
        // Първо изпълняваме shutdown процедурата на doc_Threads, тъй-като кода по-долу зависи
        // от нейното действие, а не е гарантирано, че doc_Threads::on_Shutdown() е вече
        // изпълнен.
        doc_Threads::doUpdateThread();
        
        if(count($mvc->updateByContentOnShutdown)) {
            foreach($mvc->updateByContentOnShutdown as $id) {
                // Извличаме записа на папката
                $rec = doc_Folders::fetch($id);

                if (!$rec) {
                    wp($id);
                    
                    continue;
                }
                
                // Запомняме броя на отворените теми до сега
                $exOpenThreadsCnt = $rec->openThreadsCnt;
                
                $allThreadsCnt = $openThreadCnt = 0;
                
                $newStatisticArr = $mvc->updateStatistic($rec->id);
                
                foreach ((array)$newStatisticArr['_all'] as $key => $cntArr) {
                    if (($key != '_notRejected') && ($key != 'opened')) continue;
                    foreach ($cntArr as $cnt) {
                        if ($key == 'opened') {
                            $openThreadCnt += $cnt;
                        } else {
                            $allThreadsCnt += $cnt;
                        }
                    }
                }
                
                $rec->allThreadsCnt = $allThreadsCnt;
                $rec->openThreadsCnt = $openThreadCnt;
                
                // Възстановяване на корицата, ако е оттеглена.
                self::getCover($rec)->restore();
                
                if($rec->openThreadsCnt) {
                    $rec->state = 'opened';
                } else {
                	if($rec->state != 'closed'){
                		$rec->state = 'active';
                	}
                }
                
                $thQuery = doc_Threads::getQuery();
                $thQuery->orderBy("#last", 'DESC');
                $thQuery->limit(1);
                $lastThRec = $thQuery->fetch("#folderId = {$id} AND #state != 'rejected'");
                
                $rec->last = $lastThRec->last;
                
                doc_Folders::save($rec, 'last,allThreadsCnt,openThreadsCnt,state');
                
                if (!$mvc->preventNotification[$id]) {
                    // Генерираме нотификация за потребителите, споделили папката
                    // ако имаме повече отворени теми от преди
                    if($exOpenThreadsCnt < $rec->openThreadsCnt) {
                        
                        $userId = $rec->inCharge;
                        
                        $msg = '|Отворени теми в|*' . " \"$rec->title\"";
                        
                        $url = array('doc_Threads', 'list', 'folderId' => $id);
                            
                        $priority = 'normal';
                        
                        $notifyArr = $mvc->getUsersArrForNotify($rec);
                        
                        // Ако всички потребители, които ще се нотифицират са оттеглени, вземаме всички администратори в системата
                        $isRejected = core_Users::checkUsersIsRejected($notifyArr);
                        if ($isRejected) {
                            
                            $otherNotifyArr = array();
                            if ($defNotify = doc_Setup::get('NOTIFY_FOR_OPEN_IN_REJECTED_USERS')) {
                                $otherNotifyArr = type_Keylist::toArray($defNotify);
                            }
                            
                            // Ако има избрани потребители в настройките, проверяваме да не са оттеглени
                            if (!empty($otherNotifyArr)) {
                                if (core_Users::checkUsersIsRejected($otherNotifyArr)) {
                                    $otherNotifyArr = array();
                                }
                            }
                            
                            if (empty($otherNotifyArr)) {
                                $otherNotifyArr = core_Users::getByRole('admin');
                            }
                            
                            $notifyArr += $otherNotifyArr;
                        }
                        
                        // Нотифицираме всички потребители в масива, които имат достъп до сингъла на папката
                        foreach((array)$notifyArr as $nUserId) {
                            
                            if (!doc_Folders::haveRightFor('single', $id, $nUserId)) continue;
                            
                            bgerp_Notifications::add($msg, $url, $nUserId, $priority);
                        }
                    } elseif($exOpenThreadsCnt > 0 && $rec->openThreadsCnt == 0) {
                        // Изчистване на нотификации за отворени теми в тази папка
                        $url = array('doc_Threads', 'list', 'folderId' => $rec->id);
                        bgerp_Notifications::clear($url, '*');
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща статистиката за документите в папката
     * 
     * @param int $folderId
     * 
     * @return array
     */
    public static function getStatistic($folderId)
    {
        
        return self::updateStatistic($folderId, FALSE);
    }
    
    
    /**
     * Обновява и връща статистиката за документите в папката
     * 
     * @param int $folderId
     * @param boolean $forced
     * 
     * @return array
     */
    public static function updateStatistic($folderId, $forced = TRUE)
    {
        $fRec = self::fetch($folderId);
        
        // Ако не е форсирано, при наличие на запис да не се обновява
        if (!$forced) {
            if (isset($fRec->statistic)) return $fRec->statistic;
        }
        
        $tQuery = doc_Threads::getQuery();
        $tQuery->where(array("#folderId = '[#1#]'", $folderId));
        $tQuery->groupBy('visibleForPartners,state,firstDocClass');
        
        $tQuery->XPR('cnt', 'int', 'COUNT(#id)');
        
        $tQuery->show('visibleForPartners,state,firstDocClass,cnt');
        
        $statisticArr = array();
        
        while ($tRec = $tQuery->fetch()) {
            $statisticArr[$tRec->visibleForPartners][$tRec->state][$tRec->firstDocClass] = $tRec->cnt;
            
            if ($tRec->state != 'rejected') {
                $statisticArr[$tRec->visibleForPartners]['_notRejected'][$tRec->firstDocClass] += $tRec->cnt;
                $statisticArr['_all']['_notRejected'][$tRec->firstDocClass] += $tRec->cnt;
            }
            
            $statisticArr['_all'][$tRec->state][$tRec->firstDocClass] += $tRec->cnt;
            $statisticArr['_all']['_all'][$tRec->firstDocClass] += $tRec->cnt;
            $statisticArr['_all']['_all']['_all'] += $tRec->cnt;
        }
        
        $fRec->statistic = $statisticArr;
        
        self::save($fRec, 'statistic');
        
        return $fRec->statistic;
    }
    
    
    /**
     * Връща масив с потребители, които ще се нотифицират за действия в папката
     * 
     * @param stdObject $rec
     * 
     * @return array
     */
    public static function getUsersArrForNotify($rec)
    {
        static $resArr = array();
        
        if ($resArr[$rec->id]) $resArr[$rec->id];
        
        $notifyArr = array();
        $notifyArr[$rec->inCharge] = $rec->inCharge;
        
        $oSharedArr = keylist::toArray($rec->shared);
        
        // Настройките на пакета
        $notifySharedConf = doc_Setup::get('NOTIFY_FOLDERS_SHARED_USERS');
        if ($notifySharedConf == 'no') {
            $sharedArr = array();
        } else {
            $sharedArr = $oSharedArr;
        }
        
        // Персоналните настройки на потребителите
        $pKey = crm_Profiles::getSettingsKey();
        $pName = 'DOC_NOTIFY_FOLDERS_SHARED_USERS';
        
        $settingsNotifyArr = core_Settings::fetchUsers($pKey, $pName);
        
        if ($settingsNotifyArr) {
            foreach ($settingsNotifyArr as $userId => $uConfArr) {
                if ($uConfArr[$pName] == 'no') {
                    unset($sharedArr[$userId]);
                } elseif ($uConfArr[$pName] == 'yes') {
                    if ($oSharedArr[$userId]) {
                        $sharedArr[$userId] = $userId;
                    }
                }
            }
        }
        
        $notifyArr += $sharedArr;
        
        $key = doc_Folders::getSettingsKey($rec->id);
        $folOpeningNotifications = core_Settings::fetchUsers($key, 'folOpenings');
        
        // В зависимост от избраната персонална настройка добавяме/премахваме от масива
        foreach ((array)$folOpeningNotifications as $userId => $folOpening) {
            
            if ($folOpening['folOpenings'] == 'no') {
                unset($notifyArr[$userId]);
            } else if ($folOpening['folOpenings'] == 'yes') {
                
                // Може да е абониран, но да няма права
                if (doc_Folders::haveRightFor('single', $rec->folderId, $userId)) {
                    $notifyArr[$userId] = $userId;
                }
            }
        }
        
        $currUserId = core_Users::getCurrent();
        // Премахваме анонимния, системния и текущия потребител
        unset($notifyArr[0]);
        unset($notifyArr[-1]);
        unset($notifyArr[$currUserId]);
        
        $rNotifyArr = array();
        foreach ($notifyArr as $kUId => $uId) {
            if (doc_Folders::haveRightFor('single', $rec->folderId, $uId)) {
                $rNotifyArr[$kUId] = $uId;
            }
        }
        
        $resArr[$rec->id] = $rNotifyArr;
        
        return $resArr[$rec->id];
    }
    
    
    /**
     * Обновява информацията за корицата на посочената папка
     */
    static function updateByCover($id)
    {
        $rec = doc_Folders::fetch($id);
        
        if(!$rec) return;
        
        $coverMvc = cls::get($rec->coverClass);
        
        if(!$rec->coverId) {
            expect($coverRec = $coverMvc->fetch("#folderId = {$id}"));
            $rec->coverId = $coverRec->id;
            $mustSave = TRUE;
        } else {
            expect($coverRec = $coverMvc->fetch($rec->coverId));
        }
        
        $coverRec->title = $coverMvc->getFolderTitle($coverRec->id, FALSE);

        $isRevert = ($rec->state == 'rejected' && $coverRec->state != 'rejected');
        $isReject = ($rec->state != 'rejected' && $coverRec->state == 'rejected');
        $isClosed = ($rec->state != 'closed' && $coverRec->state == 'closed');
        $isActivated = ($rec->state == 'closed' && $coverRec->state == 'active');
        
        $fields = 'title,inCharge,access,shared';
        
        foreach(arr::make($fields) as $field) {
            if($rec->{$field} != $coverRec->{$field}) {
                $rec->{$field} = $coverRec->{$field};
                $mustSave = TRUE;
            }
        }

    	if($isReject) {
			$rec->state = 'rejected';
			$mustSave = TRUE;
		}

		if($isRevert) {
			$rec->state = $coverRec->state;
			$mustSave = TRUE;
		}

		if($isClosed){
			$rec->state = 'closed';
			$mustSave = TRUE;
		}
		
		if($isActivated){
			$rec->state = 'active';
			$mustSave = TRUE;
		}
		
        if($mustSave) {
            if($isRevert || !$rec->state) {
            	if($rec->state != 'closed'){
            		$rec->state = 'open';
            	}
            }

            static::save($rec);
           
            // Ако сега сме направили операцията възстановяване
            if($isRevert || !$rec->state) {
                self::updateFolderByContent($rec->id);
            }
            
            // URL за нотификациите
            $keyUrl = array('doc_Threads', 'list', 'folderId' => $id);
            
            // Ако оттегляме
            if ($isReject) {
                
                // Скриваме нотификациите
                bgerp_Notifications::setHidden($keyUrl, 'yes');
                
                // Скриваме последно
                bgerp_Recently::setHidden('folder', $id, 'yes');
            } elseif ($isRevert) {
                
                // Скриваме нотификациите
                bgerp_Notifications::setHidden($keyUrl, 'no');
                
                // Скриваме последно
                bgerp_Recently::setHidden('folder', $id, 'no');
            }
        }
    }
    
    
    /**
     * Създава празна папка за посочения тип корица
     * и връща нейното $rec->id
     */
    static function createNew($coverMvc)
    {
        $rec = new stdClass();
        $rec->coverClass = core_Classes::getId($coverMvc);
        
        expect($rec->coverClass);
        
        // Задаваме няколко параметъра по подразбиране за 
        $rec->status = '';
        $rec->allThreadsCnt = 0;
        $rec->openThreadsCnt = 0;
        $rec->last = dt::verbal2mysql();
        
        static::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Изпълнява се след начално установяване(настройка) на doc_Folders
     * @todo Да се махне
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $query = $mvc->getQuery();
        
        while($rec = $query->fetch()) {
            if(($rec->state != 'active') && ($rec->state != 'rejected') && ($rec->state != 'opened') && ($rec->state != 'closed')) {
                $rec->state = 'active';
                $mvc->save($rec, 'state');
                $res .= "<li style='color:red'> $rec->title - active";
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     */
    static function getContragentData($id)
    {
        //Вземаме данните за ковъра от папката
        $folder = doc_Folders::fetch($id, 'coverClass, coverId');
        
        //id' то на класа, който е ковър на папката
        $coverClass = $folder->coverClass;
        
        //Ако класа поддържа интерфейса doc_ContragentDataIntf 
        if (cls::haveInterface('doc_ContragentDataIntf', $coverClass)) {
            //Името на класа
            $className = Cls::get($coverClass);
            
            //Контрагентните данни, взети от класа
            $contragentData = $className::getContragentData($folder->coverId);
        }
        
        return $contragentData;
    }
    
    
    /**
     * Добавя към заявка необходимите условия, така че тя да връща само папките, достъпни за
     * даден потребител.
     *
     * @param core_Query $query
     * @param int $userId key(mvc=core_Users)
     * @param boolean $viewAccess - Възможно най - много права за папката
     */
    static function restrictAccess_(&$query, $userId = NULL, $viewAccess = TRUE)
    {
        if ($query->mvc->className != 'doc_Folders') {
            // Добавя необходимите полета от модела doc_Folders
            if (!$query->fields['folderAccess']) {
                $query->EXT('folderAccess', 'doc_Folders', 'externalName=access,externalKey=folderId');
            }
            
            if (!$query->fields['folderInCharge']) {
                $query->EXT('folderInCharge', 'doc_Folders', 'externalName=inCharge,externalKey=folderId');
            }
            
            if (!$query->fields['folderShared']) {
                $query->EXT('folderShared', 'doc_Folders', 'externalName=shared,externalKey=folderId');
            }
        }
    }
    
    
    /**
     * Връща езика на папката от държавата на визитката
     * 
     * Първо проверява в обръщенията, после в папката
     *
     * @param int $id - id' то на папката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        // Ако няма стойност, връщаме
        if (!$id) return ;
        
        // id' то на класа, който е корица
        $coverClassId = doc_Folders::fetchField($id, 'coverClass');
        
        // Името на корицата на класа
        $coverClass = cls::getClassName($coverClassId);
        
        // Ако корицата не е Лице или Фирма
        if (($coverClass != 'crm_Persons') && ($coverClass != 'crm_Companies')) return ;
            
        // Вземаме държавата
        $classRec = $coverClass::fetch("#folderId = '{$id}'", 'country');
        
        // Ако няма въведена държава
        if (!$classRec->country) return ;
            
        // Вземаме стринга с официалните езици
        $lgStr = drdata_Countries::fetchField($classRec->country, 'languages');
        
        // Ако няма нищо
        if (!$lgStr) return ;
        
        // Превръщаме в масив
        $lgArr = explode(',', $lgStr);
        
        // Обхождаме масива
        foreach ((array)$lgArr as $lg) {
            
            // Ако няам език прескачаме
            if (!$lg) continue;
            
            // Първия добър език
            return strtolower($lg);
        }
    }


    /**
     * Връща папката по подразбиране за текущия потребител
     * Ако има дефинирана 'корпоративна' сметка за имейли, то папката е корпоративната имейл-кутия на потребителя
     * В противен случай, се връща куп със заглавие 'Документите на {Names}'
     */
    static function getDefaultFolder($userId = NULL)
    {   
        if(!$userId) {
            $names = core_Users::getCurrent('names');
            $nick  = core_Users::getCurrent('nick');
        } else {
            $names = core_Users::fetchField($userId, 'names');
            $nick  = core_Users::fetchField($userId, 'nick');
        }
        
        $rec = new stdClass();
        $rec->inCharge = $userId;
        $rec->access = 'private';
        
        $conf = core_Packs::getConfig('email');
        $defaultSentBox = $conf->EMAIL_DEFAULT_SENT_INBOX;
        
        if ($defaultSentBox && ($inboxId = email_Outgoings::getDefaultInboxId(NULL, $userId))) {
            $inboxRec = email_Inboxes::fetch($inboxId);
            
            $rec->email = $inboxRec->email;
            $rec->accountId = $inboxRec->accountId;
            $folderId = email_Inboxes::forceCoverAndFolder($rec);
        } else {
            
            // Контракторите да нямат корпоративна кутия
            if (core_Users::isContractor($userId)) {
                $corpAccRec = '';
            } else {
                $corpAccRec = email_Accounts::getCorporateAcc();
            }
            
            if($corpAccRec) {
                $rec->email = "{$nick}@{$corpAccRec->domain}";
                $rec->accountId = $corpAccRec->id;
                $folderId = email_Inboxes::forceCoverAndFolder($rec);
            } else {
                $rec->name = "Документите на {$nick}";
                $folderId = doc_UnsortedFolders::forceCoverAndFolder($rec);
            }
        } 

        return $folderId;
    }
    
    
    /**
     * Връща линка на папката във вербален вид
     * 
     * @param array $params - Масив с частите на линка
     * @param $params['Ctr'] - Контролера
     * @param $params['Act'] - Действието
     * @param $params['folderId'] - id' то на папката
     * 
     * @return core_ET|FALSE - Линк
     */
    static function getVerbalLink($params)
    {
        // Проверяваме дали е число
        if (!is_numeric($params['folderId'])) return FALSE;
        
        // Записите за папката
        $rec = static::fetch($params['folderId']);
            
        $haveRight = static::haveRightFor('single', $rec);
        
        if (!$haveRight && strtolower($params['Ctr']) == 'colab_threads') {
            if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab')) {
                $haveRight = colab_Folders::haveRightFor('single', $rec);
            }
        }
        
        // Проверяваме дали има права
        if (!$rec || (!($haveRight) && $rec->access != 'private')) return FALSE;

        // Заглавието на файла във вербален вид
        $title = static::getVerbal($rec, 'title');
        
        // Иконата на папката
        $sbfIcon = static::getIconImg($rec, $haveRight);
        
        if (Mode::is('text', 'plain')) {

            // Ескейпваме плейсхолдърите и връщаме титлата
            $res = core_ET::escape($title);
        } elseif (Mode::is('text', 'xhtml') || !$haveRight) {
            
            $res = new ET("<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> [#1#] </span>", $title);
        } else {

            // Дали линка да е абсолютен
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            // Линка
            $params['Ctr'] = 'doc_Threads';
            $link = toUrl($params, $isAbsolute);

            // Атрибути на линка
            $attr = array();
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = "background-image:url({$sbfIcon});";    
            $attr['target'] = '_blank'; 

            // Създаваме линк
            $res = ht::createLink($title, $link, NULL, $attr); 
        }
        
        return $res;
    }
    
    
    public static function fetchCoverClassId($id)
    {
        return static::fetchField($id, 'coverClass');
    }
    
    
    /**
     * Името на класа на корицата на папка
     * 
     * @param int $id key(mvc=doc_Folders)
     * @return string име на PHP клас-наследник на core_Mvc
     */
    public static function fetchCoverClassName($id)
    {
        $folderClass = static::fetchCoverClassId($id);
        $folderClassName = cls::getClassName($folderClass);
        
        return $folderClassName;
    }
    
    
    public static function fetchCoverId($id)
    {
        return static::fetchField($id, 'coverId');
    }
    
    
    /**
     * Инстанция на корицата.
     * 
     * Резултата има всички методи, налични в мениджъра на корицата
     * 
     * @param int|stdClass $id идентификатор или запис на папка
     * @return core_ObjectReference
     */
    public static function getCover($id)
    {
        expect($rec = static::fetchRec($id));

        $cover = new core_ObjectReference($rec->coverClass, $rec->coverId);
        
        return $cover;
    }
    
    
    /**
     * Добавя ограничение за дати на създаване/модифициране в заявката
     * 
     * @param core_Query $query
     * @param datetime|NULL $from
     * @param datetime|NULL $to
     * @param integer $delay
     */
    public static function prepareRepairDateQuery(&$query, $from, $to, $delay, $dateField='modifiedOn')
    {
        if (isset($from)) {
            
            if (isset($delay)) {
                $from = dt::subtractSecs($delay, $from);
            }
            
            $query->where(array("#{$dateField} >= '[#1#]'", $from));
        }
        
        if (!isset($to)) {
            $to = dt::now();
        }
        
        if (isset($delay)) {
            $to = dt::subtractSecs($delay, $to);
        }
        
        $query->where(array("#{$dateField} <= '[#1#]'", $to));
    }
    
    
    /**
     * Поправка на структурата на папките
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
        
        $unsortedFolders = 'doc_UnsortedFolders';
        $unsortedFolderId = core_Classes::getId($unsortedFolders);
        
        $currUser = core_Users::getCurrent();
        if ($currUser <= 0) {
            $currUser = core_Users::getFirstAdmin();
        }
        $currUser = ($currUser) ? $currUser : 1;
        
        $query = self::getQuery();
        
        // Подготвяме данните за търсене
        self::prepareRepairDateQuery($query, $from, $to, $delay, 'createdOn');
        
        // Търсим документи с развалени стойности
        $query->where("#inCharge IS NULL");
        $query->orWhere("#inCharge <= 0");
        $query->orWhere("#coverClass IS NULL");
        $query->orWhere("#coverId IS NULL");
        $query->orWhere("#title IS NULL");
        
        $query->limit(500);
        
        while($rec = $query->fetch()) {
            try {
                // Ако има папка без собственик
                if(!isset($rec->inCharge) || ($rec->inCharge <= 0)) {
                    $resArr['inCharge']++;
                    $rec->inCharge = $currUser;
                    self::save($rec, 'inCharge');
                    self::logNotice("Добавен е собственик на папката", $rec->id);
                }
                
                // Ако липсва coverClass, да е на несортираните
                if (!isset($rec->coverClass)) {
                    $resArr += self::moveToUnsorted($rec, $currUser);
                }
                
                // Ако няма coverId
                if (!isset($rec->coverId)) {
                    $resArr += self::moveToUnsorted($rec, $currUser);
                }
                
                // Ако няма заглвиет, използваме заглавието от документа
                if (!isset($rec->title)) {
                    $resArr['title']++;
                    $coverMvc = cls::get($rec->coverClass);
                    $rec->title = $coverMvc->getFolderTitle($rec->coverId, FALSE);
                    self::save($rec, 'title');
                    self::logNotice("Добавено заглавие на корица {$rec->coverId}");
                }
                
                self::logNotice("Папката е обновена, защото има развалени данни", $rec->id);
                
                // Обновяваме папката
                self::updateFolderByContent($rec->id);
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
     * Проверка и поправка на всички записи
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
    
        self::prepareRepairDateQuery($query, $from, $to, $delay, 'createdOn');
        
        while ($rec = $query->fetch()) {
            
            $mustRepair = FALSE;
            
            try {
                // Поправяме документите, които няма инстанция или липсва запис за тях
                if (cls::load($rec->coverClass, TRUE)) {
                    $inst = cls::get($rec->coverClass);
                    
                    if (!$inst->fetch($rec->coverId, '*', FALSE)) {
                        $mustRepair = TRUE;
                    }
                } else {
                    $mustRepair = TRUE;
                }
            } catch (ErrorException $e) {
                reportException($e);
                
                continue;
            }
            
            if ($mustRepair) {
                try {
                    $resArr += self::moveToUnsorted($rec);
                } catch (ErrorException $e) {
                    reportException($e);
                
                    continue;
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Поправка на структурата на кориците
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repairCover($from = NULL, $to = NULL, $delay = 10)
    {
        $resArr = array();
        
        // Изкючваме логването
        $isLoging = core_Debug::$isLogging;
        core_Debug::$isLogging = FALSE;
        
        // Всички документи, които имат интерфейса
        $clsArr = core_Classes::getOptionsByInterface('doc_FolderIntf', 'name');
        
        $fArr = array();
        
        foreach ($clsArr as $clsId => $clsName) {
            
            if ($clsName == 'doc_Folders') continue;
            
            if (!cls::load($clsName, TRUE)) continue ;
            
            $coverInst = cls::get($clsName);
            $coverId = core_Classes::getId($coverInst);
            
            $cQuery = $coverInst->getQuery();
            
            // Подготвяме данните за търсене
            self::prepareRepairDateQuery($cQuery, $from, $to, $delay);
            
            $cQuery->where("#folderId IS NOT NULL AND #folderId != ''");
            
            while ($cRec = $cQuery->fetch()) {
                
                if (!$cRec->folderId) continue;
                
                // Премахва повтарящите се folderId
                if ($fArr[$cRec->folderId]) {
                    
                    // Избираме най-добрият запис
                    $cPoints = self::getPointsForRec($cRec);
                    $aPoints = self::getPointsForRec($fArr[$cRec->folderId]['rec']);
                    
                    if ($cPoints == $aPoints) {
                        if ($cRec->lastUsedOn > $fArr[$cRec->folderId]['rec']->lastUsedOn) {
                            $cPoints++;
                        }
                    }
                    
                    try {
                        // Нулираме folderId на записа с по-малко точки
                        if ($aPoints > $cPoints) {
                            $cRec->folderId = NULL;
                            $coverInst->save($cRec, 'folderId');
                            $coverInst->logNotice('Нулирано повтарящо се "folderId"', $cRec->id);
                        } else {
                            $sInst = cls::get($fArr[$cRec->folderId]['cls']);
                            $fArr[$cRec->folderId]['rec']->folderId = NULL;
                            $sInst->logNotice('Нулирано повтарящо се "folderId"', $fArr[$cRec->folderId]['rec']->id);
                            $sInst->save($fArr[$cRec->folderId]['rec'], 'folderId');
                        
                            // Добавяме новите стойности в масива
                            $fArr[$cRec->folderId]['cls'] = $clsName;
                            $fArr[$cRec->folderId]['rec'] = $cRec;
                        }
                        $resArr['folderId']++;
                    } catch (ErrorException $e) {
                        reportException($e);
                    }
                } else {
                    $fArr[$cRec->folderId]['cls'] = $clsName;
                    $fArr[$cRec->folderId]['rec'] = $cRec;
                }
                
                // Ако ще се проверяват всички записи
                if (doc_Setup::get('REPAIR_ALL') == 'yes') {
                    
                    $fRec = FALSE;
                    if ($cRec->folderId) {
                        $fRec = doc_Folders::fetch($cRec->folderId, '*', FALSE);
                    }
                    
                    // Ако няма такава папка
                    if (!$fRec) {
                        try {
                            $cRec->folderId = NULL;
                            $coverInst->save($cRec, 'folderId');
                            $coverInst->logNotice('Нулирано грешно "folderId"', $cRec->id);
                            $resArr['folderId']++;
                        } catch (ErrorException $e) {
                            reportException($e);
                        }
                    } else {
                        
                        // Ако корицата не отговаря с данните на папката
                        if ($fRec->coverClass != $coverId || $fRec->coverId != $cRec->id) {
                            
                            $newFolderId = doc_Folders::fetchField(array("#coverClass = '[#1#]' AND #coverId = '[#2#]'", $coverId, $cRec->id), 'id', FALSE);
                            
                            try {
                                if ($newFolderId) {
                                    $cRec->folderId = $newFolderId;
                                    $coverInst->save($cRec, 'folderId');
                                    $coverInst->logNotice('Променено грешно "folderId"', $cRec->id);
                                    $resArr['folderId']++;
                                } else {
                                    $fRec->coverClass = $coverId;
                                    $fRec->coverId = $cRec->id;
                                    self::save($fRec, 'coverClass, coverId', 'IGNORE');
                                    self::logNotice('Промене coverClass и coverId', $fRec->id);
                                    $resArr['coverId']++;
                                }
                            } catch (ErrorException $e) {
                                reportException($e);
                            }
                        }
                    }
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Опитва се да извлече точки за записа
     * 
     * @param stdObject $rec
     * 
     * @return number
     */
    protected static function getPointsForRec($rec)
    {
        $points = 0;
        
        $points = strlen($rec->searchKeywords);
        
        if ($rec->state == 'rejected') {
            $points -= 1000;
        }
        
        if (!$rec->lastUsedOn) {
            $points -= 100;
        }
        
        return $points;
    }
    
    
    /**
     * Прави миграция на папките към несортирани. Използва се при поправка на документите.
     * 
     * @param stdObject $rec
     * @param NULL|integer $currUser
     * 
     * @return array
     */
    protected static function moveToUnsorted($rec, $currUser = NULL)
    {
        $resArr = array();
        $unsortedFolders = 'doc_UnsortedFolders';
        $unsortedFolderId = core_Classes::getId($unsortedFolders);
        
        if (!$currUser) {
            $currUser = core_Users::getCurrent();
            if ($currUser <= 0) {
                $currUser = core_Users::getFirstAdmin();
            }
            $currUser = ($currUser) ? $currUser : 1;
        }
        
        if (isset($rec->coverClass) || ($rec->coverClass != $unsortedFolderId)) {
            
            self::logNotice("Променен клас на корица от {$rec->coverClass} на {$unsortedFolderId}");
            
            $resArr['coverClass']++;
            $rec->coverClass = $unsortedFolderId;
            $rec->coverId = NULL;
            self::save($rec, 'coverClass, coverId');
        }
        
        $resArr['coverId']++;
        
        $oldCoverId = $rec->coverId;
        
        // Създаваме документ и използваме id-то за coverId
        $unRec = new stdClass();
        $unRec->name = "LaF " . $rec->title . ' ' . $unsortedFolders::count();
        $unRec->inCharge = $currUser;
        $unRec->folderId = $rec->id;
        $rec->coverId = $unsortedFolders::save($unRec);
        
        self::save($rec, 'coverId');
        
        self::logNotice("Променено id на папка от {$oldCoverId} на {$rec->coverId}");
        
        return $resArr;
    }
    
    
    /**
     * Екшън за поправка на структурите в документната система
     */
    function act_Repair()
    {
        requireRole('admin');

        core_Debug::$isLogging = FALSE;

        $Folders = cls::get('doc_Folders');
        set_time_limit($Folders->count());
        $html .= $Folders->repair();
        
        $Containers = cls::get('doc_Containers');
        set_time_limit($Containers->count());
        $html .= $Containers->repair();

        $Router = cls::get('email_Router');
        set_time_limit($Router->count());
        $html .= $Router->repair();

        return new Redirect(array('core_Packs'), $html);
    }
    

    /**
     * Връща иконата на папката според достъпа
     * 
     * @params object $rec - Данните за записа
     * @param boolean $haveRight - Дали има права за single
     * 
     * @return string $sbfImg - Иконата
     */
    static function getIconImg($rec, $haveRight = FALSE)
    {
        switch($rec->access) {
            case 'secret' :
                $img = 'folder_key.png';
            break;
            
            case 'private' :
                if ($haveRight) {
                    $img = 'folder_user.png';    
                } else {
                    $img = 'lock.png';
                }
                
            break;
            
            case 'team' :
            case 'public' :
            default :
                $img = 'folder-icon.png';
            break;
        }
        
        // Дали линка да е абсолютен
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');


        // Връщаме sbf линка до иконата
        $imgSrc = 'img/16/' . $img;

        if(log_Browsers::isRetina()) {
            $tempIcon = 'img/32/' . $img;
            if(getFullPath($tempIcon)) {
                $imgSrc = $tempIcon;
            }
        }

        $sbfImg = sbf($imgSrc, '"', $isAbsolute);

        return $sbfImg;
    }

    /**
     * Връща масив с всички активни потребители, които имат достъп до дадена папка
     * 
     * @param doc_Folders $folderId - id на папката
     * @param boolean $removeCurrent - Дали да се премахне текущия потребител от резултатите
     * 
     * @return array $sharedUsersArr - Масив с всички споделени потребители
     */
    static function getSharedUsersArr($folderId, $removeCurrent=FALSE)
    {
        // Масив с потребителите, които имат права за папката
        $sharedUsersArr = array();
        
        // Вземаме всички активни потребители
        $userQuery = core_Users::getQuery();
        $userQuery->where("#state='active'");
        while ($rec = $userQuery->fetch()) {
            
            // Ако потребителя има права за single в папката
            if (doc_Folders::haveRightFor('single', $folderId, $rec->id)) {
                
                // Добавяме в масива
                $sharedUsersArr[$rec->id] = core_Users::getVerbal($rec, 'nick');
            }
        }
        
        // Ако е зададен да се премахне текущия потребител от масива и има такъв потребител
        if ($removeCurrent && ($currUser = core_Users::getCurrent())) {
            
            // Премахваме от масива текущия потребител
            unset($sharedUsersArr[$currUser]);
        }
        
        return $sharedUsersArr;
    }
    
    
    /**
     * Добавя типа на папката към полетата за търсене
     * 
     * @param doc_Folders $mvc
     * @param string $searchKeywords
     * @param object $rec
     */
    static function on_AfterGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
        if (!$rec->coverClass) return ;
        $class = cls::get($rec->coverClass);
        $title = $class->getTitle();
        
        if ($class instanceof core_Master) {
            $singleTitle = $class->singleTitle;
            if ($singleTitle && ($title != $singleTitle)) {
                $title .= ' ' . $singleTitle;
            }
        }
        
        $searchKeywords .= " " . plg_Search::normalizeText($title);
        
        // Добавя ключовии думи за държавата и на bg и на en
        if(($class->className == 'crm_Companies' || $class->className == 'crm_Persons') && $rec->coverId) {
            $countryId = $class->fetchField($rec->coverId, 'country');
            if($countryId) {
                $searchKeywords = drdata_Countries::addCountryInBothLg($countryId, $searchKeywords);
            }
        } 
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
        $key = 'doc_Folders::' . $id;
        
        return $key;
    }
    
    
    /**
     * Може ли текущия потребител да пороменя сетингите на посочения потребител/роля?
     * 
     * @param string $key
     * @param integer|NULL $userOrRole
     * 
     * @see core_SettingsIntf
     * 
     * @return boolean
     */
    static function canModifySettings($key, $userOrRole=NULL)
    {
        // За да може да промени трябва да има достъп до сингъла на папката
        // Да променя собствените си настройки или да е admin|ceo
        
        list(, $id) = explode('::', $key);
        
        $currUser = core_Users::getCurrent();
        
        if (!doc_Folders::haveRightFor('single', $id, $currUser)) return FALSE;
        
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
        list(, $folderId) = explode('::', $form->rec->_key);
        
        // Определяме заглавито
        $rec = $this->fetch($folderId);
        $row = $this->recToVerbal($rec, 'title');
        $form->title = 'Настройка на|* ' . $row->title;
        
        // Добавяме функционални полета
        $form->FNC('newDoc', 'enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване при->Нов документ, input=input');
        $form->FNC('newThread', 'enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване при->Нова тема, input=input');
        $form->FNC('folOpenings', 'enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване при->Отворени теми, input=input');
        $form->FNC('personalEmailIncoming', 'enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване при->Личен имейл, input=input');
        $form->FNC('perPage', 'enum(default=Автоматично, 10=10, 20=20, 40=40, 100=100, 200=200)', 'caption=Теми на една страница->Брой, input=input');

        $form->FNC('ordering', 'enum(default=Автоматично, ' . doc_Threads::filterList . ')', 'caption=Подредба на темите->Правило, input=input');

        $form->FNC('defaultEmail', 'key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Адрес|* `From` за изходящите писма от тази папка->Имейл, input=input');
        
        // Изходящ имейл по-подразбиране за съответната папка
        try {
            $userId = NULL;
            if ($form->rec->_userOrRole > 0) {
                $userId = $form->rec->_userOrRole;
            }
            
            // Личните имейли на текущия потребител
            $fromEmailOptions = email_Inboxes::getFromEmailOptions($folderId, $userId, FALSE);
        } catch (core_exception_Expect $e) {
            $fromEmailOptions = array('');
        }
        $form->setOptions(defaultEmail, $fromEmailOptions);
        
        $form->setDefault('folOpenings', 'default');
        $form->setDefault('perPage', 'default');
        $form->setDefault('ordering', 'default');
        $form->setDefault('personalEmailIncoming', 'default');
        $form->setDefault('newThread', 'default');
        $form->setDefault('newDoc', 'default');
        
        // Сетваме стринг за подразбиране
        $defaultStr = 'По подразбиране|*: ';
        
        // Ако сме в мобилен режим, да не е хинт
        $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
        
        // Сетваме стойност по подразбиране
        $form->setParams('folOpenings', array($paramType => $defaultStr . '|Винаги'));
        $form->setParams('perPage', array($paramType => $defaultStr . '20'));
        $form->setParams('ordering', array($paramType => $defaultStr . '|Първо отворените'));
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
     * Връща опции за избор на папки, чиито корици имат определен интерфейс
     *
     * @param string $interface - име на интерфейс
     * @param array $ignoreFolders - масив с ид-та на папки за игнориране
     * @return array $options - масив с опции
     */
    public static function getOptionsByCoverInterface($interface, $ignoreFolders = array())
    {
    	$options = array();
    
    	$query = doc_Folders::getQuery();
    	$contragents = core_Classes::getOptionsByInterface($interface, 'title');
    	$contragents = array_keys($contragents);
    	$query->in('coverClass', $contragents);
    	$query->where("#state != 'rejected'");
    	doc_Folders::restrictAccess($query);
    	
    	if($ignoreFolders){
    		$query->notIn('id', $ignoreFolders);
    	}
    	
    	$query->show('title');
    	while($rec = $query->fetch()){
    		$options[$rec->id] = doc_Folders::getVerbal($rec, 'title');
    	}
    
    	return $options;
    }


    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
        $query = self::getQuery();
	    $query->orderBy("last=DESC");

	    // Ако има зададен интерфейс за кориците, взимат се само тези папки, чиито корици имат интерфейса
	    if(isset($params['coverInterface'])){
	    	$coverClasses = core_Classes::getOptionsByInterface($params['coverInterface'], 'title');
	    	$coverClasses = array_keys($coverClasses);
	    	$query->in('coverClass', $coverClasses);
	    }
	    
        $viewAccess = TRUE;
	    if ($params['restrictViewAccess'] == 'yes') {
	        $viewAccess = FALSE;
	    }

        $me = cls::get(get_called_class());
	       
	    $me->restrictAccess($query, NULL, $viewAccess);
	    
        if(!$includeHiddens) {
            $query->where("#state != 'rejected' AND #state != 'closed'");
        }

        if($params['where']) {
            $query->where($params['where']);
        }
	       
        if(is_array($onlyIds)) {
            if(!count($onlyIds)) {
                return array();
            }

            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);

            $query->where("#id IN ($ids)");
        } elseif(ctype_digit("{$onlyIds}")) {
            $query->where("#id = $onlyIds");
        }

        if($threadId = $params['moveThread']) {
            $tRec = doc_Threads::fetch($threadId);
            expect($doc = doc_Containers::getDocument($tRec->firstContainerId));
            $doc->getInstance()->restrictQueryOnlyFolderForDocuments($query, $viewAccess);
        }
        
        $titleFld = $params['titleFld'];
        $query->EXT('class', 'core_Classes', 'externalKey=coverClass,externalName=title');
        $query->XPR('searchFieldXpr', 'text', "LOWER(CONCAT(' ', #{$titleFld}))");
       
        if($q) {
            if($q{0} == '"') $strict = TRUE;

            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            
            $q = mb_strtolower($q);
            
            if($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            foreach($qArr as $w) {
                $query->where(array("#searchFieldXpr REGEXP '\ {1}[^a-z0-9\p{L}]?[#1#]'", $w));
            }
        }
 
        if($limit) {
            $query->limit($limit);
        }

        $query->show("id,searchFieldXpr,class, {$titleFld}");
        
        $res = array();
        
        while($rec = $query->fetch()) {
            $res[$rec->id] = trim($rec->{$titleFld}) . ' (' . $rec->class . ')';
        }
 
        return $res;
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
        // Премахваме color стилове
        $status = preg_replace('/style\s*=\s*(\'|")color:\#[a-z0-9]{3,6}(\'|")/i', '', $status);
    }
}
