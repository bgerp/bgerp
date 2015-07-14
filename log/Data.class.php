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
    public $listFields = 'id, actTime, userId=Потребител, text, ipId=IP адрес, brId=Браузър';
    
    
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
         $this->FLD('type', 'enum(emerg=Спешно,alert=Тревога,crit=Критично,err=Грешка,warning=Предупреждение,notice=Известие,info=Инфо,debug=Дебъг)', 'caption=Данни->Тип на събитието');
         $this->FLD('actionCrc', 'int', 'caption=Данни->Действие');
         $this->FLD('classCrc', 'int', 'caption=Данни->Клас');
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
    }
    
    
    /**
     * 
     * 
     * @param string $type
     * @param string $message
     * @param string|object|NULL $className
     * @param integer|NULL $objectId
     * @param integer $lifeDays
     */
    public static function add($type, $message, $className = NULL, $objectId = NULL, $lifeDays = 180)
    {
        // Инстанцираме класа, за да може да се изпълни on_Shutdown
        cls::get(get_called_class());
        
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        
        $toAdd = array();
        $toAdd['type'] = $type;
        $toAdd['message'] = $message;
        $toAdd['className'] = $className;
        $toAdd['objectId'] = $objectId;
        $toAdd['time'] = dt::mysql2timestamp();
        $toAdd['lifeTime'] = $lifeDays * 86400;
                
        self::$toAdd[] = $toAdd;
    }
    
    
    /**
     * При приключване на изпълнените на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Форсираме стартирането на сесията
        core_Session::forcedStart();
        
        // Записва в БД всички действия от стека
        self::flush();
    }
    
    
    /**
     * Записва в БД всички действия от стека
     */
    public static function flush()
    {
        // Ако няма данни за добавяне, няма нужда да се изпълнява
        if (!self::$toAdd) return ;
        
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->brId = log_Browsers::getLinkFromId($rec->brId);
        
        if ($rec->time) {
            $time = dt::timestamp2Mysql($rec->time);
            $row->actTime = dt::mysql2verbal($time, 'smartTime');
            
            $row->actTime .= "<span class='logs-icon-{$rec->type}'></span>";
        }
        
        $action = log_Actions::getActionFromCrc($rec->actionCrc);
        $className = log_Classes::getClassFromCrc($rec->classCrc);
        
        $row->text = self::prepareText($action, $className, $rec->objectId);
        
        // Добавяме линк към реферера
        $refRec = log_Referer::getRefRec($rec->ipId, $rec->brId, $rec->time);
        if ($refRec && log_Referer::haveRightFor('single', $refRec)) {
            $row->text .= ht::createLinkRef("", array('log_Referer', 'single', $refRec->id), NULL, array('title' => tr('Реферер|*: ') . $refRec->ref));
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
    protected static function prepareText($action, $className, $objectId = NULL)
    {
        $clsInst = NULL;
        
        if ($className) {
            if (cls::load($className, TRUE)) {
                $clsInst = cls::get($className);
                
                if (method_exists($clsInst, 'getLinkForObject')) {
                    $link = $clsInst->getLinkForObject($objectId);
                } else {
                    $link = $className;
                }
            }
        }
        
        if ($link) {
            if (strpos($action, '#') !== FALSE) {
                $action = str_replace('#', $link, $action);
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
        
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('log/tpl/DataFilterForm.shtml')));
        
        $data->listFilter->fields['type']->caption = 'Тип';
        $data->listFilter->fields['type']->type->options = array('' => '') + $data->listFilter->fields['type']->type->options;
        $data->listFilter->fields['type']->refreshForm = 'refreshForm';
        
        $data->listFilter->FNC('users', 'users(rolesForAll=ceo|admin, rolesForTeams=ceo|admin)', 'caption=Потребител,refreshForm');
        
        $data->listFilter->FNC('message', 'varchar', 'caption=Текст');
        $data->listFilter->FNC('ip', 'varchar(32)', 'caption=IP адрес');
        $data->listFilter->FNC('from', 'datetime', 'caption=От');
		$data->listFilter->FNC('to', 'datetime', 'caption=До');
		$data->listFilter->FNC('class', 'varchar', 'caption=Клас,refreshForm, allowEmpty, silent');
		$data->listFilter->FNC('object', 'varchar', 'caption=Обект,refreshForm, allowEmpty, silent');
        
        $default = $data->listFilter->getField('users')->type->fitInDomain('all_users');
        $data->listFilter->setDefault('users', $default);
        
        $data->listFilter->showFields = 'users, message, class, object, type, ip, from, to';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->view = 'vertical';
        
        $data->listFilter->input($data->listFilter->showFields);
        
        $rec = $data->listFilter->rec;
        $query = $data->query;
        
        // Филтрираме по потребители
        if (isset($rec->users)) {
            $usersArr = type_Users::toArray($rec->users);
            $query->in('userId', $usersArr);
        }
        
        // Филтрираме по екшъна/съобщението
        if (trim($rec->message)) {
            
            $actQuery = log_Actions::getQuery();
            plg_Search::applySearch($rec->message, $actQuery);
            
            $actArr = array();
            
            while ($actRec = $actQuery->fetch()) {
                $actArr[$actRec->id] = $actRec->crc;
            }
            
            if ($actArr) {
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
            
            if ($ipArr) {
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
        $cQuery->groupBy('classCrc');
        while ($cRec = $cQuery->fetch()) {
            $className = log_Classes::getClassFromCrc($cRec->classCrc);
            if ($className) {
                $classSuggArr[$className] = $className;
            }
        }
        
        if ($classSuggArr) {
            $classSuggArr = array('' => '') + $classSuggArr;
            $data->listFilter->setOptions('class', $classSuggArr);
        }
        
        // Филтрираме по клас
        if (trim($rec->class)) {
            $crc = log_Classes::getClassCrc($rec->class, FALSE);
            if ($crc) {
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
            
            $cQuery->limit(100);
            
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
        if ($objSuggArr) {
            $objSuggArr = array('' => '') + $objSuggArr;
            $data->listFilter->setOptions('object', $objSuggArr);
        }
        
        if ($rec->object) {
            $query->where(array("#objectId = '[#1#]'", $rec->object));
        }
        
        // Филтрираме по тип
        if (trim($rec->type)) {
            $query->where(array("#type = '[#1#]'", $rec->type));
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
