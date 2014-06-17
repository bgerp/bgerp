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
    var $title = "Логин лог на потребителите";
    
    
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
    var $loadList = 'plg_SystemWrapper, plg_LoginWrapper, plg_Created, plg_GroupByDate, plg_AutoFilter';
    
    
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
        $this->FLD('userId', 'user(select=nick, allowEmpty)', 'caption=Потребител, silent, autoFilter');
        $this->FLD('ip', 'ip', 'caption=IP');
        $this->FLD('brid', 'varchar(8)', 'caption=BRID');
        $this->FLD('status', 'enum( all=,
        							success=Успешно логване,
									first_login=Първо логване,
									wrong_password=Грешна парола,
									missing_password=Липсва парола,
									pass_reset=Ресетване на парола,
									pass_change=Промяна на парола,
									change_nick=Промяна на ник,
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
        $rec->brid = core_Browser::getBrid();
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
        					#createdOn > '[#1#]' AND
        					#userId = '[#2#]' AND
        					#timestamp = '[#3#]' AND
        					(#status='success' OR #status='first_login')", $maxCreatedOn, $userId, $timestamp));
        
        if ($rec) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Връща id на потребителя, който се е логва от този браузър
     * 
     * @return mixed
     */
    static function getUserIdForAutocomplete()
    {
        // id на браузъра
        $brid = core_Browser::getBrid(FALSE);
        
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
        $query->where(array("#createdOn > '[#1#]'", $maxCreatedOn));
        $query->where("#status = 'success'");
        $query->orWhere("#status = 'first_login'");
        $query->where("#brid = '{$brid}'");
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
        $brid = core_Browser::getBrid();
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FETCH_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Вземаме всички успешни логвания (включтелно първите)
        // За съответния потреибтел
        // От това IP или този браузър
        // Като лимитираме търсенето до константа
        $rec = static::fetch(array("#createdOn > '[#1#]' AND
        							(#ip = '[#2#]' OR #brid = '[#3#]') AND
        							#userId = '[#4#]' AND
        							(#status = 'success' OR #status = 'first_login')", $maxCreatedOn, $ip, $brid, $userId));
        
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
        $brid = core_Browser::getBrid();
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Дали има първо логване в зададения период
        $rec = static::fetch(array("#createdOn > '[#1#]' AND
        							(#ip = '[#2#]' OR #brid = '[#3#]') AND
        							#userId = '[#4#]' AND
        							#status = 'first_login' 
        							", $maxCreatedOn, $ip, $brid, $userId));
        if ($rec) return FALSE;
        
        // Дали има успешно логване в зададения период
        $rec = static::fetch(array("#createdOn > '[#1#]' AND
        							(#ip = '[#2#]' OR #brid = '[#3#]') AND
        							#userId = '[#4#]' AND
        							#status = 'success' 
        							", $maxCreatedOn, $ip, $brid, $userId));
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
        $brid = core_Browser::getBrid();
        
        $conf = core_Packs::getConfig('core');
        
        // Ограничение на броя на дните
        $daysLimit = (int)$conf->CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT;
        
        // Ограничаваме времето на търсене
        $maxCreatedOn = dt::subtractSecs($daysLimit);
        
        // Последното логване с това IP/браузър от този потребител
        $query = static::getQuery();
        $query->where(array("#createdOn > '[#1#]'", $maxCreatedOn));
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
        $sQuery->where(array("#createdOn > '[#1#]'", $lastCreatedOn));
        $sQuery->where(array("#ip != '[#1#]'", $ip));
        $sQuery->where(array("#brid != '[#1#]'", $brid));
        $sQuery->where(array("#userId = '[#1#]'", $userId));
        $sQuery->where("#status = 'success'");
        $sQuery->orWhere("#status = 'first_login'");
        
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
        $query->where(array("#createdOn > '[#1#]'", $maxCreatedOn));
        $query->where("#userId = '{$userId}'");
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
        if ($rec->ip) {
    	    $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
    	}
    	
    	// В зависимост от статуса, добавяме клас на реда
    	if ($rec->status == 'success') {
    	    $row->ROW_ATTR['class'] = 'loginLog-success';
    	} elseif ($rec->status == 'first_login') {
    	    $row->ROW_ATTR['class'] = 'loginLog-first_login';
    	} else {
    	    $row->ROW_ATTR['class'] = 'loginLog-other';
    	}
    	
    	// Оцветяваме BRID
    	$bridTextColor = static::getTextColor($rec->brid);
    	$brinFontColor = static::getFontColor($rec->brid);
    	$row->brid = "<span style='color: {$bridTextColor}; background-color: {$brinFontColor};'>" . $row->brid . "</span>";
    	
    	// Оцветяваме IP-то
    	$ipTextColor = static::getTextColor($rec->ip);
    	$ipFontColor = static::getFontColor($rec->ip);
    	$row->ip = "<span style='color: {$ipTextColor}; background-color: {$ipFontColor};'>" . $row->ip . "</span>";
    	
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
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Избираме го по подразбиране
        $data->listFilter->setDefault('status', 'all');
        
        // Кои полета да се показват
        $data->listFilter->showFields = 'userId, status';
        
        // Инпутваме заявката
        $data->listFilter->input('userId, status', 'silent');
        
        // Сортиране на записите по създаване
        $data->query->orderBy('createdOn', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако се търси по потребител
            if ($filter->userId) {
                $data->query->where(array("#userId = '[#1#]'", $filter->userId));
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
    
    
    /**
     * Връща цвете на текста от подадения стринг
     * 
     * @param string $text
     * 
     * @return string
     */
    static function getTextColor($text)
    {
        $color = static::getColorFromText($text, "&", "050505");
        
        return $color;
    }
    
    
    /**
     * Връща фона за стринга
     * 
     * @param string $text
     * 
     * @return string
     */
    static function getFontColor($text)
    {
        $color = static::getColorFromText($text, "|", "C0C0C0");
        
        return $color;
    }
    
    
    /**
     * От подадения стринг и маска генерира съдържание
     * 
     * @param string $text
     * @param string $operation
     * @param string $mask
     * @param string $prefix
     * 
     * @return string
     */
    static function getColorFromText($text, $operation, $mask, $prefix='#')
    {
        // Масив с всички генерирание цветове
        static $colorArray = array();
        
        $textStr = $text . $operation . $mask;
        
        // Ако не сме генерирали цвят за този текст с тази маска и операция
        if (!$colorArray[$textStr]) {
            
            // Хеша на текста
            $hash = md5($text);
            
            // RGB на текста
            $r = hexdec(substr($hash, 0, 2));
            $g = hexdec(substr($hash, 2, 2));
            $b = hexdec(substr($hash, 4, 2));
            
            // RGB от маската
            $rM = hexdec(substr($mask, 0, 2));
            $gM = hexdec(substr($mask, 2, 2));
            $bM = hexdec(substr($mask, 4, 2));
            
            // В зависимост от операцията
            if ($operation == '|') {
                $rC = $r | $rM;
                $gC = $g | $gM;
                $bC = $b | $bM;
            } elseif ($operation == '&') {
                $rC = $r & $rM;
                $gC = $g & $gM;
                $bC = $b & $bM;
            }
            
            // Добавяме получения цвят в масива
            $colorArray[$textStr] = str_pad(dechex($rC), 2, 0, STR_PAD_LEFT) . 
                    str_pad(dechex($gC), 2, 0, STR_PAD_LEFT) . 
                    str_pad(dechex($bC), 2, 0, STR_PAD_LEFT);
        }
        
        return $prefix . $colorArray[$textStr];
    }
}
