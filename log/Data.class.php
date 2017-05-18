<?php


/**
 * 
 *
 * @category  bgerp
 * @package   logs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Data extends core_Manager
{
    
    
    /**
     * Знак, който ще се замества с линк към обекта, ако съществува
     */
    protected static $objReplaceInAct = '#';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'logs_Data';
    
    
    /**
     * Заглавие
     */
    public $title = "Логове";
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, log_Wrapper';
    
    
    /**
     * 
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * 
     */
    public $listFields = 'id, actTime, userId=Потребител,  text, type=Тип, ipId=IP адрес, brId=Браузър';
    
    
    /**
     * 
     */
    protected static $toAdd = array();
    
    
    
    /**
     * Полета на модела
     */
    public function description()
    {    
         $this->FLD('ipId', 'key(mvc=log_Ips, select=ip)', 'caption=Идентификация->IP адрес');
         $this->FLD('brId', 'key(mvc=log_Browsers, select=brid)', 'caption=Идентификация->Браузър');
         $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Идентификация->Потребител, notNull');
         $this->FLD('time', 'int', 'caption=Време на записа');
         $this->FLD('type', 'enum(read=Четене, write=Запис, login=Вход)', 'caption=Данни->Тип на събитието');
         $this->FLD('actionCrc', 'bigint', 'caption=Данни->Действие');
         $this->FLD('classCrc', 'bigint', 'caption=Данни->Клас');
         $this->FLD('objectId', 'int', 'caption=Данни->Обект');
         $this->FLD('lifeTime', 'int', 'caption=Време живот, notNull');
         
         $this->FNC('text', 'varchar', 'caption=Съобщение');
         $this->FNC('actTime', 'datetime', 'caption=Време');
         
         $this->setDbIndex('ipId');
         $this->setDbIndex('brId');
         $this->setDbIndex('userId');
         $this->setDbIndex('time');
         $this->setDbIndex('type');
         $this->setDbIndex('actionCrc');
         $this->setDbIndex('classCrc,objectId');

         $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * 
     * 
     * @param string $type
     * @param string $message
     * @param string|object|NULL $className
     * @param integer|NULL|stdObject $objectId
     * @param integer $lifeDays
     */
    public static function add($type, $message, $className = NULL, $objectId = NULL, $lifeDays = 180)
    {
        // Инстанцираме класа, за да може да се изпълни on_Shutdown
        cls::get(get_called_class());
        
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        
        if (isset($objectId)) {
            
            if (is_object($objectId)) {
                $objectId = $objectId->id;
            }
            
            if (!is_numeric($objectId)) {
                $objectId = NULL;
            }
        }
        
        $toAdd = array();
        $toAdd['type'] = $type;
        $toAdd['message'] = $message;
        $toAdd['className'] = $className;
        $toAdd['objectId'] = $objectId;
        $toAdd['time'] = dt::mysql2timestamp();
        $toAdd['lifeTime'] = $lifeDays * 86400;
                
        self::$toAdd[] = $toAdd;
        
        $logStr = $className;
        $logStr .= $objectId ? " - " . $objectId : '';
        $logStr .=  ": " . $message;
        Debug::log($logStr);
    }
    
    
    /**
     * Дали потребиеля може да вижда лога на съотвения потребител
     * 
     * @param integer $userId
     * @param NULL|integer $currUserId
     * 
     * @return boolean
     */
    public static function canViewUserLog($userId, $currUserId = NULL)
    {
        if (!isset($currUserId)) {
            $currUserId = core_Users::getCurrent();
        }
        
        // Текущия потребител може да вижда лога за себе си
        if ($userId == $currUserId) return TRUE;
        
        // admin и ceo на всички
        if (haveRole('admin, ceo', $currUserId)) return TRUE;
        
        // Мениджър - на всички от неговия екип без лога на ceo и други manager-и
        if (haveRole('manager')) {
            if (!haveRole('ceo, manager', $userId)) {
                $teamMatest = core_Users::getTeammates($currUserId);
                if (type_Keylist::isIn($userId, $teamMatest)) return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща масив с логовете за потребителя
     * 
     * @param integer $userId
     * @param integer $perPage
     * 
     * @return array
     * array rows
     * object pager
     */
    public static function getLogsForUser($userId, $perPage = 10)
    {
        $query = self::getQuery();
        $query->where("#userId = {$userId}");
        $query->orderBy("time", "DESC");
        $query->orderBy("id", "DESC");
        $me = cls::get(get_called_class());
        $data = new stdClass();
        $data->query = $query;
        $me->listItemsPerPage = $perPage;
        
        $data->listFields = array('text', 'actTime', 'type');
        
        $me->prepareListPager_($data);
        $me->prepareListRecs_($data);
        $me->prepareListRows_($data);
        
        $resArr = array('rows' => $data->rows, 'pager' => $data->pager);
        
        return $resArr;
    }
    
    
    /**
     * Връща броя на записите за съответния обект
     * 
     * @param object|string $className
     * @param integer $objectId
     * @param NULL|string $type
     * @param NULL|string $act
     * 
     * @return NULL|integer
     */
    public static function getObjectCnt($className, $objectId, $type = NULL, $act = NULL)
    {
        $query = self::getObjetQuery($className, $objectId, $type, $act);
        
        $query->show('id');
        
        return $query->count();
    }
    
    
    /**
     * Връща записите за съответния обект
     * 
     * @param object|string $className
     * @param integer $objectId
     * @param NULL|string $type
     * @param NULL|string $act
     * 
     * @return array
     */
    public static function getObjectRecs($className, $objectId, $type = NULL, $act = NULL, $limit = NULL, $order = 'DESC')
    {
        $query = self::getObjetQuery($className, $objectId, $type, $act);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        if ($order) {
            $query->orderBy('time', $order);
        }
        
        $resArr = array();
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec;
        }
	
        return $resArr;
    }
    
    
    /**
     * Връща заявка за съответния обект
     * 
     * @param object|string $className
     * @param integer $objectId
     * @param NULL|string $type
     * @param NULL|string $act
     *
     * @return core_Query
     */
    protected static function getObjetQuery($className, $objectId, $type = NULL, $act = NULL)
    {
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        
        if (!$className || !$objectId || !(is_numeric($objectId))) return ;
        
        $classCrc = log_Classes::getClassCrc($className);
        
        $query = self::getQuery();
        $query->where("#classCrc = {$classCrc}");
        $query->where("#objectId = {$objectId}");
        
        if (isset($type)) {
            $query->where(array("#type = '[#1#]'", $type));
        }
        
        if (isset($act)) {
            $actCrc = log_Actions::getActionCrc($act);
            $query->where(array("#actionCrc = '[#1#]'", $actCrc));
        }
        
        return $query;
    }
    
    
    /**
     * Връща всички записи за съответния обект
     * 
     * @param object|string $className
     * @param integer $objectId
     * @param core_Pager $pager
     * @param NULL|string $type
     * 
     * @return array|NULL
     */
    public static function getRecs($className, $objectId, $pager, $type = NULL)
    {
        $resArr = array();
        
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        
        if (!$className || !$objectId || !(is_numeric($objectId))) return ;
        
        $classCrc = log_Classes::getClassCrc($className);
        
        $query = self::getQuery();
        $query->where("#classCrc = {$classCrc}");
        $query->where("#objectId = {$objectId}");
        
        if (isset($type)) {
            $query->where(array("#type = '[#1#]'", $type));
        }
        
        $query->orderBy("time", "DESC");
        $query->orderBy("id", "DESC");
        
        $pager->setLimit($query);
        
        $resArr = array();
        
        while($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща вербалната стойност на масива
     * 
     * @param array $recsArr
     * @param array $fieldsArr
     * 
     * @return array
     */
    public static function getRows($recsArr, $fieldsArr = array())
    {
        $rowsArr = array();
        
        foreach ((array)$recsArr as $key=>$rec) {
            $rowsArr[$key] = self::recToVerbal($rec, $fieldsArr);
        }
        
        return $rowsArr;
    }
    
    
    /**
     * При приключване на изпълнените на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Записва в БД всички действия от стека
        self::flush();
    }
    
    
    /**
     * Записва в БД всички действия от стека
     */
    public static function flush()
    {
        // Ако няма данни за добавяне, няма нужда да се изпълнява
        if (empty(self::$toAdd)) return ;
        
        $ipId = log_Ips::getIpId();
        $bridId = log_Browsers::getBridId();
        
        foreach (self::$toAdd as $toAdd) {
            
            $rec = new stdClass();
            $rec->ipId = $ipId;
            $rec->brId = $bridId;
            $rec->userId = core_Users::getCurrent();
            $rec->actionCrc = log_Actions::getActionCrc($toAdd['message']);
            $rec->classCrc = log_Classes::getClassCrc($toAdd['className']);
            $rec->objectId = $toAdd['objectId'];
            $rec->time = $toAdd['time'];
            $rec->type = $toAdd['type'];
            $rec->lifeTime = $toAdd['lifeTime'];
            
            self::save($rec);
            
            log_Referer::addReferer($ipId, $bridId, $toAdd['time']);
        }
        
        // Записваме crc32 стойностите на стринговете
        log_Actions::saveActions();
        log_Classes::saveActions();
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fieldsArr = array())
    {
        if (empty($fieldsArr) || $fieldsArr['brId']) {
            $row->brId = log_Browsers::getLinkFromId($rec->brId);
        }
        
        if ($rec->time && (empty($fieldsArr) || $fieldsArr['actTime'])) {
            $time = dt::timestamp2Mysql($rec->time);
            $row->actTime = dt::mysql2verbal($time, 'smartTime');
        }
        
        $action = log_Actions::getActionFromCrc($rec->actionCrc);
        
        if (strpos($action, self::$objReplaceInAct) !== FALSE) {
            $escapedRep = preg_quote(self::$objReplaceInAct, '/');
            
            $action = preg_replace("/(\s)*({$escapedRep})(\s)*/i", '\\1|\\2|*\\3', $action);
        }
        
        $action = tr($action);
        
        if (empty($fieldsArr) || $fieldsArr['actionCrc']) {
            $typeVarchar = cls::get('type_Varchar');
            $row->actionCrc = str_replace(self::$objReplaceInAct, '', $action);
            $row->actionCrc = $typeVarchar->toVerbal($row->actionCrc);
        }
        
        $className = log_Classes::getClassFromCrc($rec->classCrc);
        if (empty($fieldsArr) || $fieldsArr['classCrc']) {
            $typeClass = cls::get('type_Class');
            $row->classCrc = $typeClass->toVerbal($className);
        }
        
        if (empty($fieldsArr) || $fieldsArr['text']) {
            $row->text = self::prepareText($action, $className, $rec->objectId);
            
            // Добавяме линк към реферера
            $refRec = log_Referer::getRefRec($rec->ipId, $rec->brId, $rec->time);
            if ($refRec && log_Referer::haveRightFor('single', $refRec)) {
                $row->text .= ht::createLinkRef("", array('log_Referer', 'single', $refRec->id), NULL, array('title' => 'Реферер|*: ' . $refRec->ref));
            }
        }
        
        if ($fieldsArr['userId']) {
            if ($rec->userId && $rec->userId > 0) {
                $row->userId = crm_Profiles::createLink($rec->userId);
            }
        }
        
        $row->ROW_ATTR['class'] = "logs-type-{$rec->type}";
    }
    
    
    /**
     * 
     * 
     * @param string $action
     * @param string $className
     * @param NULL|integer $objectId
     * 
     * @return string
     */
    public static function prepareText($action, $className, $objectId = NULL)
    {
        $link = NULL;
        
        if ($className) {
            if (cls::load($className, TRUE)) {
                try {
                    $clsInst = @cls::get($className);
                } catch (Exception $e) {
                }
                
                if (method_exists($clsInst, 'getLinkForObject')) {
                    try {
                        $link = $clsInst->getLinkForObject($objectId);
                    } catch (ErrorException $e) {
                        reportException($e);
                    }
                }
                
                if ($clsInst instanceof core_Detail) {
                    $singleTitle = '';
                    if (is_object($clsInst->Master)) {
                        $singleTitle = $clsInst->Master->singleTitle;
                        $singleTitle = mb_strtolower($singleTitle);
                    }
                    
                    if (!$singleTitle) {
                        $singleTitle = 'детайл';
                    }
                    
                    $action .= ' ' . tr('на') . ' ' . tr($singleTitle);
                }
            }
            
            if (!$link) {
                $link = $className;
            }
        }
        
        if (isset($link)) {
            if (strpos($action, self::$objReplaceInAct) !== FALSE) {
                $action = str_replace(self::$objReplaceInAct, $link, $action);
            } else {
                $action .= ': ' . $link;
            }
        }
        
        return $action;
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
        $data->query->orderBy("time", "DESC");
        $data->query->orderBy("id", "DESC");
        
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('log/tpl/DataFilterForm.shtml')));
        
        $data->listFilter->FNC('users', 'users(rolesForAll=ceo|admin, rolesForTeams=ceo|admin, roles=user)', 'caption=Потребител, silent, autoFilter');
        
        $data->listFilter->FNC('message', 'varchar', 'caption=Текст');
        $data->listFilter->FNC('ip', 'varchar(32)', 'caption=IP адрес');
        $data->listFilter->FNC('from', 'datetime', 'caption=От');
		$data->listFilter->FNC('to', 'datetime', 'caption=До');
		$data->listFilter->FNC('class', 'varchar', 'caption=Клас,removeAndRefreshForm=object, allowEmpty, silent');
		$data->listFilter->FNC('object', 'varchar', 'caption=Обект,autoFilter, allowEmpty, silent');
        
		$def = setIfNot($def, Request::get('users'), 'all_users');
        $default = $data->listFilter->getField('users')->type->fitInDomain($def);
        $data->listFilter->setDefault('users', $default);
        
        if (is_null(Request::get('class'))) {
            // По - подразбиране да се търси месец назад
            $data->listFilter->setDefault('from', dt::addDays(-1, NULL, FALSE));
        }
        
        $data->listFilter->showFields = 'users, message, class, object, ip, from, to';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->view = 'vertical';
        
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        $rec = $data->listFilter->rec;
        $query = $data->query;
        
        // Филтрираме по потребители
        if (isset($rec->users)) {
            
            if (!type_Keylist::isIn('-1', $rec->users)) {
                $usersArr = type_Users::toArray($rec->users);
                $query->in('userId', $usersArr);
            }
        }
        
        // Филтрираме по екшъна/съобщението
        if (trim($rec->message)) {
            
            $actQuery = log_Actions::getQuery();
            plg_Search::applySearch($rec->message, $actQuery);
            
            $actArr = array();
            
            while ($actRec = $actQuery->fetch()) {
                $actArr[$actRec->id] = $actRec->crc;
            }
            
            if (!empty($actArr)) {
                $query->in('actionCrc', $actArr);
            } else {
                
                // Ако няма намерен текст, да не се показва никакъв резултат
                $query->where("1 = 2");
            }
        }
        
        // Филтрираме по IP
        if($ip = $data->listFilter->rec->ip) {
            $ip = str_replace('*', '%', $ip);
            
            $ipArr = array();
            
            $ipQuery = log_Ips::getQuery();
            $ipQuery->where(array("#ip LIKE '[#1#]'", $ip));
            while ($ipRec = $ipQuery->fetch()) {
                $ipArr[$ipRec->id] = $ipRec->id;
            }
            
            if (!empty($ipArr)) {
                $query->in('ipId', $ipArr);
            } else {
                // Ако няма намерен текст, да не се показва никакъв резултат
                $query->where("1 = 2");
            }
        }
        
        // Филтрираме по време
        if ($rec->from || $rec->to) {
        
            $dateRange = array();
	        
	        if ($rec->from) {
	            $dateRange[0] = $rec->from; 
	        }
	        
	        if ($rec->to) {
	            $dateRange[1] = $rec->to; 
	        }
	        
	        if (count($dateRange) == 2) {
	            sort($dateRange);
	        }
	        
            if($dateRange[0]) {
                if (!strpos($dateRange[0], ' ')) {
                    $dateRange[0] .= ' 00:00:00';
                }
                $dateRange[0] = dt::mysql2timestamp($dateRange[0]);
    			$query->where(array("#time >= '[#1#]'", $dateRange[0]));
    		}
            
			if($dateRange[1]) {
			    if (!strpos($dateRange[1], ' ')) {
                    $dateRange[1] .= ' 23:59:59';
                }
                $dateRange[1] = dt::mysql2timestamp($dateRange[1]);
    			$query->where(array("#time <= '[#1#]'", $dateRange[1]));
    		}
        }
        
        // Добавяме класовете, за които има запис в търсения резултат
        $classSuggArr = array();
        $cQuery = clone $query;
        
        // Ако не е въведена дата, ограничаваме времето - това е само за показване на класовете
        if (!$rec->from && !$rec->to) {
            $beforeT = dt::mysql2timestamp(dt::addDays(-2, NULL, FALSE));
            $cQuery->where(array("#time <= '[#1#]'", $beforeT));
        }
        
        $cQuery->groupBy('classCrc');
        $cQuery->show('classCrc');
        while ($cRec = $cQuery->fetch()) {
            $className = log_Classes::getClassFromCrc($cRec->classCrc);
            if ($className) {
                $classSuggArr[$className] = $className;
            }
        }
            
        if (trim($rec->class)) {
            $classSuggArr[$rec->class] = $rec->class;
        }
        
        if (!empty($classSuggArr)) {
            asort($classSuggArr);
            $classSuggArr = array('' => '') + $classSuggArr;
            $data->listFilter->setOptions('class', $classSuggArr);
        }
        
        // Филтрираме по клас
        if (trim($rec->class)) {
            $crc = log_Classes::getClassCrc($rec->class, FALSE);
            if (isset($crc)) {
                $query->where("#classCrc = '{$crc}'");
            } else {
                $query->where("1=2");
            }
        }
        
        $objSuggArr = array();
        
        // Подготваме данните и филтрираме по обект
        if (!trim($rec->class)) {
            $rec->object = '';
            $data->listFilter->setReadOnly('object');
        } else {
            $cQuery = clone $query;
            
            $cQuery->groupBy('classCrc');
            $cQuery->groupBy('objectId');
            
            $cQuery->where("#objectId IS NOT NULL");
            
            // Избрания обект да е на първо място
            if ($rec->object) {
                $oldOrderArr = $cQuery->orderBy;
                $cQuery->orderBy = array();
                
                $cQuery->orWhere(array("#objectId = '[#1#]'", $rec->object));
                
                $cQuery->XPR('orderObjectId', 'int', "(CASE #objectId WHEN '{$rec->object}' THEN 1 ELSE 2 END)");
                $cQuery->orderBy('orderObjectId');
                
                $cQuery->orderBy = array_merge($cQuery->orderBy, $oldOrderArr);
            }
            
            $cQuery->limit(100);
            
            $cQuery->show('classCrc, objectId');
            
            while ($cRec = $cQuery->fetch()) {
                $className = log_Classes::getClassFromCrc($cRec->classCrc);
                
                if ($className) {
                    
                    if (cls::load($className, TRUE)) {
                        $clsInst = cls::get($className);
                        
                        if (method_exists($clsInst, 'getTitleForId_')) {
                            $objSuggArr[$cRec->objectId] = $clsInst->getTitleForId($cRec->objectId);
                        } else {
                            $objSuggArr[$cRec->objectId] = $cRec->objectId;
                        }
                    }
                }
            }
        }
        
        // Добавяме обектите, за които има запис
        if (!empty($objSuggArr)) {
            $objSuggArr = array('' => '') + $objSuggArr;
            $data->listFilter->setOptions('object', $objSuggArr);
        }
        
        if ($rec->object) {
            $query->where(array("#objectId = '[#1#]'", $rec->object));
        }
    }
    
    
    /**
     * Почистване на старите записи
     */
    function cron_DeleteOldRecords()
    {
        $query = $this->getQuery();
        $query->where("(#time + #lifeTime) < '" . dt::mysql2timestamp() . "'");
        
        $deletedRecs = 0;
        $delRefCnt = 0;
        
        $delArr = array();
        
        while ($rec = $query->fetch()) {
            
            if ($this->delete($rec->id)) {
                $deletedRecs++;
                
                $delArr[$rec->id]['ipId'] = $rec->ipId;
                $delArr[$rec->id]['brId'] = $rec->brId;
                $delArr[$rec->id]['time'] = $rec->time;
            }
        }
        
        $res = '';
        
        if ($deletedRecs) {
            $res .= "Изтрити <b>{$deletedRecs}</b> записа от логовете";
            
            foreach ($delArr as $dArr) {
                
                // Изтриваме реферерите за данните, само ако няма друга връзка
                $delRefCnt += log_Referer::delRefRec($dArr['ipId'], $dArr['brId'], $dArr['time'], TRUE);
            }
        }
        
        if ($delRefCnt) {
            if ($res) {
                $res .= "\n";
            }
            $res .= "Изтрити <b>{$delRefCnt}</b> записа от реферери";
        }
        
        return $res;
    }
    
    
    /**
     * Начално установяване на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Нагласяване на Крон        
        $rec = new stdClass();
        $rec->systemId = 'DelExpLogsDataAndRef';
        $rec->description = 'Изтриване на старите логове и реферери в системата';
        $rec->controller = $mvc->className;
        $rec->action = 'DeleteOldRecords';
        $rec->period = 24 * 60;
        $rec->offset = rand(1320, 1439); // ot 22h до 24h
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
}
