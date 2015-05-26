<?php


/**
 * Лог за всички логвания и действия с акаунтите
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_LoginLog extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Логвания на потребителите";
    
    
    /**
     * 
     */
    var $canSingle = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'createdOn';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_Created, plg_GroupByDate, plg_AutoFilter';
    
    
    /**
     * Кой може да види IP-то от последното логване
     */
    var $canViewlog = 'powerUser';
    
    
    /**
     * 
     */
    var $listFields = 'userId, status, ip, brid, createdOn, createdBy';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('userId', 'user(select=nick, allowEmpty)', 'caption=Потребител, silent');
        $this->FLD('ip', 'ip', 'caption=IP');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър');
        $this->FLD('status', 'enum( all=,
        							success=Успешно логване,
									first_login=Първо логване,
									wrong_password=Грешна парола,
									missing_password=Липсва парола,
									pass_reset=Ресетване на парола,
									pass_change=Промяна на парола,
									change_nick=Промяна на ник,
									new_user=Нов потребител,
									time_deviation=Отклонение във времето,
									used_timestamp=Използван timestamp,
									error=Грешка,
									block=Блокиран,
									reject=Оттеглен,
									draft=Чернова,
									user_reg=Регистриране,
									user_activate=Активиране
								  )', 'caption=Статус, silent, autoFilter');
        $this->FLD('timestamp', 'int', 'caption=Време, input=none');
        
        $this->setDbIndex('createdOn');
        $this->setDbIndex('ip');
        $this->setDbIndex('brid');
    }
    
    
    /**
     * Записва в лога опитите за логване
     * 
     * @param string $status
     * @param integer $userId
     * @param timestamp $time
     */
    static function add($status, $userId=NULL, $time=NULL)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $rec = new stdClass();
        $rec->userId = $userId;
        $rec->ip = core_Users::getRealIpAddr();
        $rec->status = $status;
        $rec->brid = logs_Browsers::getBrid();
        $rec->timestamp = $time;
        
        static::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Проверява дали отклонението на подадения таймстамп е в границите на допустимото
     * 
     * @param integer $timestamp
     * 
     * @return boolean
     */
    static function isTimestampDeviationInNorm($timestamp)
    {
        $conf = core_Packs::getConfig('core');
        $maxDeviation = $conf->CORE_LOGIN_TIMESTAMP_DEVIATION;
        
        // Текущото време в таймстампа
        $nowTimestamp = dt::mysql2timestamp();
        
        // Разликата между текущото време и зададенот
        $diff = abs($nowTimestamp - $timestamp);
        
        // Ако е в границите
        if ($maxDeviation > $diff) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Проверява дали timestamp-а е използван от съответния потребител за успешен вход
     * 
     * @param integer $timestamp
     * @param integer $userId
     * 
     * @return boolean
     */
    static function isTimestampUsed($timestamp, $userId=NULL)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $conf = core_Packs::getConfig('core');
        $daysLimit = (int)$conf->CORE_LOGIN_TIMESTAMP_DEVIATION;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        $rec = static::fetch(array("
        					#createdOn > '{$maxCreatedOn}' AND
        					#userId = '[#1#]' AND
        					#timestamp = '[#2#]' AND
        					(#status='success' OR #status='first_login')", $userId, $timestamp));
        
        if ($rec) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Проверява дали от този `brid` е осъществено логване
     * 
     * @param IP $ip
     * 
     * @return boolean
     */
    public static function isLoggedBefore($ip = NULL)
    {
        $brid = logs_Browsers::getBrid();
        
        $query = self::getQuery();
        $query->where(array("#brid = '[#1#]'", $brid));
        
        if ($ip) {
            $query->where(array("#ip = '[#1#]'", $ip));
        }
        
        $query->where("#status = 'success'");
        $query->orWhere("#status = 'first_login'");
        
        $query->limit(1);
        
        return (boolean)$query->count();
    }
    
    
    /**
     * Връща id на потребителя, който се е логва от този браузър
     * 
     * @return mixed
     */
    static function getUserIdForAutocomplete()
    {
        // id на браузъра
        $brid = logs_Browsers::getBrid(FALSE);
        
        // Ако няма записано
        if (!$brid) return FALSE;
        
        $userId = FALSE;
        
        $cnt = 0;
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FETCH_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Последния n на брой успешни логвания от този браузър
        $query = static::getQuery();
        $query->where(array("#brid = '[#1#]'", $brid));
        $query->where("#createdOn > '{$maxCreatedOn}'");
        $query->where("#status = 'success'");
        $query->orWhere("#status = 'first_login'");
        
        $query->limit((int)$conf->CORE_SUCCESS_LOGIN_AUTOCOMPLETE);
        
        $query->orderBy('createdOn', 'DESC');
        
        // Ако е логнат само от един потребител
        while ($rec = $query->fetch()) {
            $cnt++;
            if ($userId === FALSE) {
                $userId = $rec->userId;
            } else {
                if ($userId != $rec->userId) {
                    
                    return FALSE;
                }
            }
        }
        
        // Ако има по - малко записи от лимита
        if ($cnt < (int)$conf->CORE_SUCCESS_LOGIN_AUTOCOMPLETE) return FALSE;
        
        return $userId;
    }
    
        
    /**
     * Проверява дали дадения потребители се логва за първи път от съответното IP и браузър
     * 
     * @param IP $ip
     * @param integer $userId
     * 
     * @return boolean
     */
    static function isFirstLogin($ip, $userId=NULL)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // Идентификатор на браузъра
        $brid = logs_Browsers::getBrid();
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FETCH_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Вземаме всички успешни логвания (включтелно първите)
        // За съответния потреибтел
        // От това IP или този браузър
        // Като лимитираме търсенето до константа
        $rec = static::fetch(array("#createdOn > '{$maxCreatedOn}' AND
        							(#ip = '[#1#]' OR #brid = '[#2#]') AND
        							#userId = '[#3#]' AND
        							(#status = 'success' OR #status = 'first_login')", $ip, $brid, $userId));
        
        // Ако има някакъв запис, следователно не е първо логване
        if ($rec) {
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Проверява дали потребителя се логва от достоверно IP/browser
     * Ако няма първо логване в определен период и има успешно логване, тогава е достоверно
     * 
     * @param IP $ip
     * @param integer $userId
     * 
     * @return boolean
     */
    static function isTrustedUserLogin($ip, $userId=NULL)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // Идентификатор на браузъра
        $brid = logs_Browsers::getBrid();
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Дали има първо логване в зададения период
        $rec = static::fetch(array("#createdOn > '{$maxCreatedOn}' AND
        							(#ip = '[#1#]' OR #brid = '[#2#]') AND
        							#userId = '[#3#]' AND
        							#status = 'first_login' 
        							", $ip, $brid, $userId));
        if ($rec) return FALSE;
        
        // Дали има успешно логване в зададения период
        $rec = static::fetch(array("#createdOn > '{$maxCreatedOn}' AND
        							(#ip = '[#1#]' OR #brid = '[#2#]') AND
        							#userId = '[#3#]' AND
        							#status = 'success' 
        							", $ip, $brid, $userId));
        if ($rec) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Връща масив с логваниято от съответния потребител, след последното му логване
     * от съответното IP/brid
     * 
     * @param IP $ip
     * @param integer $userId
     * 
     * @return array
     * ['success']
     * ['first_login']
     */
    static  function getLastLoginFromOtherIp($ip, $userId=NULL)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $resArr = array();
        
        // Идентификатор на браузъра
        $brid = logs_Browsers::getBrid();
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Последното логване с това IP/браузър от този потребител
        $query = static::getQuery();
        $query->where("#createdOn > '{$maxCreatedOn}'");
        $query->where(array("#ip = '[#1#]'", $ip));
        $query->orWhere(array("#brid = '[#1#]'", $brid));
        $query->where(array("#userId = '[#1#]'", $userId));
        $query->where("#status = 'success'");
        $query->orderBy('createdOn', 'DESC');
        $query->limit(1);
        
        $rec = $query->fetch();
        
        if (!$rec) return ;
        
        $lastCreatedOn = $rec->createdOn;
        
        // Всички логвания от други IP'та/браузъри с този потребител
        // След съответното време
        $sQuery = static::getQuery();
        $sQuery->where("#createdOn > '{$lastCreatedOn}'");
        $sQuery->where(array("#ip != '[#1#]'", $ip));
        $sQuery->where(array("#brid != '[#1#]'", $brid));
        $sQuery->where(array("#userId = '[#1#]'", $userId));
        $sQuery->where("#status = 'success'");
        $sQuery->orWhere("#status = 'first_login'");
        
        $sQuery->orderBy("createdOn", 'DESC');
        
        while ($sRec = $sQuery->fetch()) {
            
            if (!$sRec->ip) continue;
            
            if ($ip == $sRec->ip) continue;
            
            // Ако е отбелязано в първо логване, да не се добавя в масива с успешни логвания
            if ($sRec->status == 'success' && $resArr['first_login'][$sRec->ip]) continue;
            
            $resArr[$sRec->status][$sRec->ip] = $sRec;
            
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща последните записи в лога за съответния потребител
     * 
     * @param integer $userId
     * @param integer $limit
     * @param array $statusArr
     * 
     * @return array
     */
    static function getLastAttempts($userId=NULL, $limit=5, $statusArr=array()) 
    {
        // Ако не е подаден потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $recsArr = array();
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FETCH_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Всички записи за съответния потребител, подредени по дата
        $query = static::getQuery();
        $query->where("#createdOn > '{$maxCreatedOn}'");
        $query->where(array("#userId = '[#1#]'", $userId));
        $query->orderBy('createdOn', 'DESC');
        
        // Ако е зададен лимит
        if ($limit) {
            $query->limit($limit);
        }
        
        // Ако е зададен масив със статуси
        if ($statusArr) {
            $query->orWhereArr('status', $statusArr);
        }
        
        while ($rec = $query->fetch()) {
            $recsArr[$rec->id] = $rec;
        }
        
        return $recsArr;
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	// В зависимост от статуса, добавяме клас на реда
    	if ($rec->status == 'success') {
    	    $row->ROW_ATTR['class'] = 'loginLog-success';
    	} elseif ($rec->status == 'first_login') {
    	    $row->ROW_ATTR['class'] = 'loginLog-first_login';
    	} else {
    	    $row->ROW_ATTR['class'] = 'loginLog-other';
    	}
    	
    	// Оцветяваме BRID
    	$row->brid = logs_Browsers::getLink($rec->brid);
    	
        if ($rec->ip) {
        	// Декорираме IP-то
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE);
    	}
    }
    
    
    /**
     * 
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Поле за избор на потребител
        $data->listFilter->FNC('users', 'users(rolesForAll = admin, rolesForTeams = admin)', 'caption=Потребител,input,silent,refreshForm');
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Избираме го по подразбиране
        $data->listFilter->setDefault('status', 'all');
        
        // Кои полета да се показват
        $data->listFilter->showFields = 'users, status';
        
        // Инпутваме заявката
        $data->listFilter->input('users, status', 'silent');
        
        // Ако не избран потребител
        if(!$data->listFilter->rec->users) {
            
        	// По подразбиране да е избран текущия
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }
        
        // Сортиране на записите по създаване
        $data->query->orderBy('createdOn', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако се търси по потребител
            if ($filter->users && $filter->users != 'all') {
                
                // Масив с избраните потребители
                $usersArr = type_Keylist::toArray($filter->users);
                
                // Филтрираме всички избрани потребители
                $data->query->orWhereArr('userId', $usersArr);
            }
            
            // Ако се търси по статус
            if ($filter->status && $filter->status != 'all') {
                $data->query->where(array("#status = '[#1#]'", $filter->status));
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param object $rec
     * @param id $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Текущия потребител може да си види записите от лога, admin и ceo могат на всичките
        if ($action == 'viewlog') {
            if ($rec && ($rec->userId != $userId)) {
                if (!haveRole('ceo, admin')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        $data->listFields = arr::make($data->listFields);
        
        if (haveRole('debug')) {
            $data->listFields['timestamp'] = 'Време';
        }
    }
    
}