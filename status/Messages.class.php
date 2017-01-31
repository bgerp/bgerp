<?php 


/**
 * Клас 'status_Messages'
 *
 * @category  vendors
 * @package   status
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class status_Messages extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Статус съобщения';
    
    
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
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'status_Wrapper, plg_Created';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('text', 'html', 'caption=Текст');
        $this->FLD('type', 'enum(success=Успех, notice=Известие, warning=Предупреждение, error=Грешка)', 'caption=Тип');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('sid', 'varchar(32)', 'caption=Идентификатор');
        $this->FLD('lifeTime', 'time', 'caption=Живот');
        $this->FLD('hitId', 'varchar(16)', 'caption=ID на хита');
        
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Добавя статус съобщение към избрания потребител
     * 
     * @param string $text - Съобщение, което ще добавим
     * @param enum $type - Типа на съобщението - success, notice, warning, error
     * @param integer $userId - Потребителя, към когото ще се добавя. Ако не е подаден потребител, тогава взема текущия потребител.
     * @param integer $lifeTime - След колко време да е неактивно
     * @param string $hitId - Уникално ID на хита
     * 
     * @return integer - При успешен запис връща id' то на записа
     */
    static function newStatus($text, $type='notice', $userId=NULL, $lifeTime=60, $hitId=NULL)
    {
        // Ако не е бил сетнат преди
        if (!Mode::get('hitTime')) {
            
            // Задаваме текущото време
            Mode::set('hitTime', dt::mysql2timestamp());
        }
        
        $currUserId = core_Users::getCurrent();
        
        // Ако не подаден потребител, тогава използваме текущия
        $userId = ($userId) ? ($userId) : ($currUserId);
        
        // Стойности за записа
        $rec = new stdClass();
        
        // Ако текущия потребител добавя за себи си, тогава да се добавя sid
        if ($userId == $currUserId) {
            $rec->sid = self::getSid();
        }
        
        $rec->text = $text;
        $rec->type = $type;
        $rec->userId = $userId;
        $rec->lifeTime = $lifeTime;
        $rec->hitId = $hitId;
        
        $id = self::save($rec);
        
        return $id;
    }
    
    
    /**
     * Генерира sid на текущия потребител
     * 
     * @return string - md5 стойността на sid
     */
    static function getSid()
    {
        //Перманентния ключ на текущия потребител
        $permanentKey = Mode::getPermanentKey();
        
        // Стойността на солта на константата
        $conf = core_Packs::getConfig('status');
        $salt = $conf->STATUS_SALT;
        
        //Вземаме md5'а на sid
        $sid = md5($salt . $permanentKey);
        
        return $sid;
    }
    
    
    /**
     * Връща всички статуси на текущия потребител, на които не им е изтекъл lifeTime' а
     * 
     * @param integer $hitTime - timestamp на изискване на страницата
     * @param integer $idleTime - Време на бездействие на съответния таб
     * @param integer $maxLimit - Максимален брой на статусите, които да се връщат при едно извикване
     * @param boolean $once - Еднакви (стринг и тип) статус съобщения да се показват само веднъж
     * @param string $hitId - Уникално ID на хита
     * 
     * @return array $resArr - Масив със съобщението и типа на статуса
     */
    static function getStatuses($hitTime, $idleTime, $maxLimit=4, $once=TRUE, $hitId=NULL)
    {
        $resArr = array();
        
        // id на текущия потребител
        $userId = core_Users::getCurrent();
        
        // Конфигурационния пакет
        $conf = core_Packs::getConfig('status');
        
        // Намяляме времето
        $hitTimeB = $hitTime - $conf->STATUS_TIME_BEFORE;
        
        // Време на извикване на страницата
        $hitTime = dt::timestamp2Mysql($hitTime);
        
        // Време на извикване на страницата с премахнат коригиращ офсет
        $hitTimeB = dt::timestamp2Mysql($hitTimeB);
        
        // Вземаме всички записи за текущия потребител
        // Създадени преди съответното време
        $query = self::getQuery();
        
        // Ако потребителя е логнат
        if ($userId > 0) {
            
            // Статусите за него
            $query->where(array("#userId = '[#1#]'", $userId));
        }
        
        // Статусите за съответния SID
        $sid = self::getSid();
        $query->where(array("#sid = '[#1#]'", $sid));
        
        // Само логнатите потребители могат да видят статусите без sid
        if ($userId > 0) {
            $query->orWhere("#sid IS NULL");
        }
        
        $query->orderBy('createdOn', 'ASC');
        
        // Записите със зададено hitId да се връщат, се връщат само за съответното hitId
        $query->where(array("#hitId IS NULL AND #createdOn >= '[#1#]'", $hitTimeB));
        if (!empty($hitId)) {
            $query->orWhere(array("#hitId = '[#1#]'", $hitId));
        }
        
        $checkedArr = array();
        
        $limit = 0;
        
        while ($rec = $query->fetch()) {
            
            $skip = FALSE;
            
            // Проверяваме дали е извличан преди
            $isRetrived = status_Retrieving::isRetrived($rec->id, $hitTime, $idleTime, $sid, $userId, $hitId);
            
            // Ако е извличан преди в съответния таб, да не се показва пак
            if ($isRetrived) continue;
            
            // Ако ще се показват само веднъж
            if ($once) {
                
                // Хеша на стринга
                $strHash = md5($rec->text . $rec->type);
                
                if ($checkedArr[$strHash]) {
                    $skip = TRUE;
                }
                
                // Добавяме в масива
                $checkedArr[$strHash] = $strHash;
            }
            
            // Ако няма да се прескача
            if (!$skip) {
                
                // Ако сме достигнали лимита
                if ($limit >= $maxLimit) continue;
                
                // Двумерен масив с типа и текста
                $resArr[$rec->id]['text'] = tr("|*" . $rec->text);
                $resArr[$rec->id]['type'] = $rec->type;
                $limit++;
            }
            
            // Добавяме в извличанията
            status_Retrieving::addRetrieving($rec->id, $hitTime, $idleTime, $sid, $userId, $hitId);
        }
        
        return $resArr;
    }
    
    
    /**
     * Абонира за извличане на статус съобщения
     * 
     * @return core_ET
     */
    static function subscribe_()
    {
        $res = new ET();
        
        // Ако е регистриран потребител
        if (haveRole('user')) {
            
            // Абонираме статус съобщенията
            core_Ajax::subscribe($res, array('status_Messages', 'getStatuses'), 'status', 5000);
        }
        
        // Извлича статусите веднага след обновяване на страницата
        core_Ajax::subscribe($res, array('status_Messages', 'getStatuses'), 'statusOnce', 0);
        
        return $res;
    }
    
    
    /**
     * Връща статус съобщенията
     */
    static function act_getStatuses()
    {
        // Ако се вика по AJAX
        if (Request::get('ajax_mode')) {
            
            // Ако се принтира
            if (Request::get('Printing')) return array();
            
            // Времето на отваряне на таба
            $hitTime = Request::get('hitTime', 'int');
            
            // Време на бездействие
            $idleTime = Request::get('idleTime', 'int');
            
            $hitId = Request::get('hitId');
            
            // Вземаме непоказаните статус съобщения
            $statusesArr = self::getStatusesData($hitTime, $idleTime, $hitId);
            
            // Ако няма нищо за показване
            if (!$statusesArr) return array();
            
            // При възникване на статус от тип грешка, да се нотифицира в таба 
            foreach ((array)$statusesArr as $statusDesc) {
                if ($statusDesc->arg['type'] == 'error') {
                    $errIcon = sbf('img/16/dialog_error.png', '');
                    
                    $resObj = new stdClass();
                    $resObj->func = 'Notify';
                    $resObj->arg =  array('title' => 'Грешка', 'blinkTimes' => 3,  'favicon' => $errIcon);
                    
                    $statusesArr[] = $resObj;
                    
                    break;
                }
            }
            
            return $statusesArr;
        }
    }
    
    
    /**
     * Връща 'div' със статус съобщенията
     * 
     * @param integer $hitTime - Timestamp на показване на страницата
     * @param integer $idleTime - Време на бездействие на съответния таб
     * @param string $hitId - Уникално ID на хита
     * 
     * @return array
     */
    static function getStatusesData_($hitTime, $idleTime, $hitId=NULL)
    {
        // Всички статуси за текущия потребител преди времето на извикване на страницата
        $statusArr = self::getStatuses($hitTime, $idleTime, 4, TRUE, $hitId);
        
        $resStatus = array();
        
        foreach ($statusArr as $value) {
            
            $res = '';
            
            // Записваме всеки статус в отделен div и класа се взема от типа на статуса
            $res = "<div class='statuses-message statuses-{$value['type']}'> {$value['text']} </div>";
            
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id'=>'statuses', 'html' => $res, 'replace' => FALSE);
            
            $resStatus[] = $resObj;
            
            if ($soundNotifObj = self::getSoundNotifications($value['type'])) {
                $resStatus[] = $soundNotifObj;
            }
        }
        
        return $resStatus;
    }
    
    
    /**
     * Връща обект за звукава нотификация
     * 
     * @param string $type
     * 
     * @return stdClass|NULL
     */
    public static function getSoundNotifications($type)
    {
        if ($type == 'error') {
            
            $obj = new stdClass();
            
            $notifyArr = array('title' => tr('Грешка'), 'blinkTimes' => 2);
            
            $notifyArr['soundOgg'] = sbf("sounds/error.ogg", '');
            $notifyArr['soundMp3'] = sbf("sounds/error.mp3", '');
            
            $obj->func = 'Notify';
            $obj->arg = $notifyArr;
            
            return $obj;
        }
    }
    
    
    /**
     * Извиква се от крона. Премахва старите статус съобщения
     */
    function cron_removeOldStatuses()
    {
        // Текущото време
        $now = dt::verbal2mysql();
        
        // Вземаме всички статус съобщения, които са изтекли
        $query = self::getQuery();
        $query->where("DATE_ADD(#createdOn, INTERVAL #lifeTime SECOND) < '{$now}'");
        
        $cnt = 0;
        
        while ($rec = $query->fetch()) {
            
            // Изтриваме информцията за изтегляния
            status_Retrieving::removeRetrieving($rec->id);
            
            $cnt++;
            
            // Изтриваме записа
            self::delete($rec->id);
        }
        
        return $cnt;
    }
    
    
	/**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'removeOldStatuses';
        $rec->description = 'Премахване на старите статус съобщения';
        $rec->controller = $mvc->className;
        $rec->action = 'removeOldStatuses';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 40;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Връща масив със чакащите статуси в момента
     * @return array
     */
    public static function returnStatusesArray()
    {
    	$hitTime = Request::get('hitTime', 'int');
    	$idleTime = Request::get('idleTime', 'int');
    	$statusData = status_Messages::getStatusesData($hitTime, $idleTime);
    
    	// Връщаме статусите ако има
    	return (array)$statusData;
    }
}
