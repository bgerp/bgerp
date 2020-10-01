<?php


/**
 * Клас 'log_System' - Мениджър за запис на действията на потребителите
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class log_System extends core_Manager
{
    /**
     * Максимален брой редове, които ще се извличат от error_log
     */
    public static $phpErrMaxLinesLimit = 200;
    
    
    /**
     * Максимален брой записи, които ще се записват при всяк извикване
     */
    public static $phpErrMaxLimit = 20;
    
    
    /**
     * Кои PHP грешки да се каствам logErr
     * Останалите грешки ще са logNotice
     */
    public static $phpErrReportTypeArr = array('error', 'warning');
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Системен лог';
    
    
    /**
     * Колко реда да се листват в една страница?
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Кои полета ще бъдат показани?
     */
    public $listFields = 'id, createdOn=Дата, createdBy=Потребител, what=Действие';
    
    
    public $oldClassName = 'log_Debug';
    
    
    /**
     * Кой може да листва и разглежда?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    public $loadList = 'plg_SystemWrapper, plg_Created';
    
    
    protected static $notifySysId = 'notifyForSysErr';
    
    
    protected static $notifyErrArr = array('alert', 'err', 'logErr');
    
    
    /**
     * Дали за този модел ще се прави репликация на SQL заявките
     */
    public $doReplication = false;
    
    
    /**
     * Описание на полетата на модела
     */
    public function description()
    {
        $this->FLD('className', 'varchar(64)', 'placeholder=Клас, autoFilter, allowEmpty, silent, recently');
        $this->FLD('objectId', 'int');
        $this->FLD('detail', 'text');
        $this->FLD('lifeDays', 'int', 'value=120, oldFieldName=lifeTime');
        $this->FLD('type', 'enum(info=Инфо,alert=Тревога,err=Грешка,warning=Предупреждение,notice=Известие,debug=Дебъг,logErr=Грешка в лога, logNotice=Известие в лога)', 'caption=Тип');
        $this->FLD('lastSaved', 'datetime(smartTime)', 'caption=Последно');
        
        $this->setDbIndex('createdOn');
        $this->setDbIndex('lastSaved');
        $this->setDbIndex('className');
        $this->setDbIndex('objectId');
        $this->setDbIndex('type');
        
        $this->setDbIndex('type, createdOn, className');
        
        $this->dbEngine = 'InnoDB';
        
        if (defined('LOG_DB_NAME') && defined('LOG_DB_USER') && defined('LOG_DB_PASS') && defined('LOG_DB_HOST')) {
            $this->db = cls::get(
                'core_Db',
                array('dbName' => LOG_DB_NAME,
                    'dbUser' => LOG_DB_USER,
                    'dbPass' => LOG_DB_PASS,
                    'dbHost' => LOG_DB_HOST,
                )
            );
        }
    }
    
    
    /**
     * Добавяне на събитие в лога
     *
     * @param string            $className
     * @param int|NULL|stdClass $objectId
     * @param string            $action
     * @param string            $type
     * @param int               $lifeDays
     * @param null|int          $notDublicateTime
     * @param null|int          $forceDublicateTime
     */
    public static function add($className, $action, $objectId = null, $type = 'info', $lifeDays = 7, $notDublicateTime = null, $forceDublicateTime = null)
    {
        if (in_array($type, self::$notifyErrArr)) {
            if (!isset($notDublicateTime)) {
                $notDublicateTime = 300;
            }
            
            if (!isset($forceDublicateTime)) {
                $forceDublicateTime = 86400;
            }
        }
        
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        
        if (is_object($objectId)) {
            $objectId = $objectId->id;
        }
        
        $logStr = $className;
        $logStr .= $objectId ? ' - ' . $objectId : '';
        $logStr .= ': ' . $action;
        Debug::log($logStr);
        
        expect(is_string($className));
        
        // Ако е зададено да се предпазва от дублирани записи
        if ($notDublicateTime) {
            $query = self::getQuery();
            $query->where(array("#className = '[#1#]'", $className));
            if (isset($objectId)) {
                $query->where(array("#objectId = '[#1#]'", $objectId));
            }
            $query->where(array("#detail = '[#1#]'", $action));
            $query->where(array("#type = '[#1#]'", $type));
            $query->where(array("#lastSaved >= '[#1#]'", dt::subtractSecs($notDublicateTime)));
            $query->orderBy('lastSaved', 'DESC');
            
            $oRec = $query->fetch();
            if ($oRec) {
                $mustUpdate = true;
                if ($forceDublicateTime) {
                    $forceDublicateTime = dt::subtractSecs($forceDublicateTime);
                    if (($oRec->lastSaved > $forceDublicateTime) && ($oRec->createdOn > $forceDublicateTime)) {
                        $mustUpdate = true;
                    } else {
                        $mustUpdate = false;
                    }
                }
                
                if ($mustUpdate) {
                    $oRec->lastSaved = dt::now();
                    
                    try {
                        return self::save($oRec, 'lastSaved');
                    } catch (Throwable $e) {
                        reportException($e);
						
						return ;
                    }
                }
            }
        }
        
        $rec = new stdClass();
        $rec->className = $className;
        $rec->objectId = $objectId;
        $rec->detail = $action;
        $rec->lifeDays = $lifeDays;
        $rec->lastSaved = dt::now();
        $rec->type = $type;
        
        try {
            return self::save($rec);
        } catch (Throwable $e) {
            reportException($e);
        }
    }
    
    
    /**
     * Почистване на старите записи
     */
    public function cron_DeleteOldRecords()
    {
        $deletedRecs = $this->delete(" ADDDATE( #createdOn, #lifeDays ) < '" . dt::verbal2mysql() . "'");
        
        return "Log: <B>{$deletedRecs}</B> old records was deleted";
    }
    
    
    /**
     * Форма за търсене по дадена ключова дума
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FNC('date', 'date', 'placeholder=Дата');
        $data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене, autoFilter, allowEmpty, silent');
        
        $data->listFilter->fields['type']->caption = 'Тип';
        $data->listFilter->fields['type']->type->options = array('' => '') + $data->listFilter->fields['type']->type->options;
        $data->listFilter->fields['type']->autoFilter = 'autoFilter';
        
        $data->listFilter->showFields = 'date, search, type';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        $query = $data->query;
        
        // Заявка за филтриране
        $fRec = $data->listFilter->rec;
        
        if ($fRec->date) {
            if ($fRec->date == dt::now(false)) {
                $query->where("#createdOn >= '{$fRec->date}'");
            } else {
                $query->where("#createdOn >= '{$fRec->date}' AND #createdOn <= '{$fRec->date} 23:59:59'");
            }
        }
        
        $objectId = Request::get('objectId', 'int');
        if ($objectId) {
            if ($objectId == 'NULL') {
                $query->where('#objectId IS NULL');
            } else {
                $query->where("#objectId = {$objectId}");
            }
        }
        
        $search = trim($fRec->search);
        if ($search) {
            $search = mb_strtolower($fRec->search);
            $query->where(array("LOWER (#className) LIKE '%[#1#]%'", $search));
            $query->orWhere(array("LOWER (#detail) LIKE '%[#1#]%'", $search));
        }
        
        // Филтрираме по тип
        if (trim($fRec->type)) {
            $query->where(array("#type = '[#1#]'", $fRec->type));
        }
        
        $query->orderBy('#createdOn,#id', 'DESC');
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ROW_ATTR['class'] = "logs-type-{$rec->type}";
        
        $detail = core_Type::escape($rec->detail);
        
        $row->what = log_Data::prepareText($detail, $rec->className, $rec->objectId);
    }
    
    
    /**
     * Добавя div със стил за състоянието на треда
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $type = $data->listFilter->rec->type;
        
        if ($type) {
            // Изчистване на нотификации за възникнали грешки
            $url = array($mvc, 'list', 'type' => $type);
            bgerp_Notifications::clear($url);
        }
    }
    
    
    /**
     * Нотифициране на администраторите по крон за възникнали грешк
     */
    public function cron_NotifyForSysErr()
    {
        $period = core_Cron::getPeriod(self::$notifySysId);
        $period += 59;
        
        // Преобразуваме повтарящите се `warning` в `err`
        $wQuery = $this->getQuery();
        $time = dt::subtractSecs(log_Setup::get('WARNING_TO_ERR_PERIOD') + $period);
        $wQuery->where("#createdOn >= '{$time}'");
        $wQuery->where("#type = 'warning'");
        $wQuery->orWhere("#type = 'err'");
        $wQuery->orderBy('type', 'ASC');
        $wQuery->orderBy('createdOn', 'DESC');
        
        $errArr = array();
        $wArr = array();
        while ($wRec = $wQuery->fetch()) {
            $dHash = md5($wRec->detail);
            
            // Ако вече warning е променен на err - да не се променят другите подобни
            if (isset($errArr[$dHash])) {
                continue;
            }
            
            if ($wRec->type == 'err') {
                $errArr[$dHash] = true;
                
                continue;
            }
            
            if (!isset($wArr[$dHash])) {
                $wArr[$dHash] = array();
                $wArr[$dHash]['cnt'] = 1;
                $wArr[$dHash]['rec'] = $wRec;
            } else {
                $wArr[$dHash]['cnt']++;
                
                // Ако сме достигнали лимита за предупреждения, тогава трябва да стане грешка
                if ($wArr[$dHash]['cnt'] > log_Setup::get('WARNING_TO_ERR_CNT')) {
                    $errArr[$dHash] = true;
                    $nRec = $wArr[$dHash]['rec'];
                    $nRec->type = 'err';
                    $this->save($nRec, 'type');
                }
            }
        }
        
        $from = dt::subtractSecs($period);
        
        $query = $this->getQuery();
        $query->where("#createdOn >= '{$from}'");
        $query->orWhereArr('type', self::$notifyErrArr);
        $query->orderBy('createdOn', 'ASC');
        
        $roleId = core_Roles::fetchByName('admin');
        $adminsArr = core_Users::getByRole($roleId);
        while ($rec = $query->fetch()) {
            $more = false;
            $errType = '';
            switch ($rec->type) {
                case 'alert':
                    $msgType = 'спешни';
                    $errType = 'alert';
                break;
                
                case 'logErr':
                    $errType = 'logErr';
                    $msgType = 'PHP';
                break;
                
                default:
                    $msgType = '';
                break;
            }
            
            // Опитваме се да определим най-важната част на стринга
            list($detStr) = explode(':', $rec->detail, 2);
            $detStr = mb_substr($detStr, 0, 30);
            if ($detStr != $rec->detail) {
                $detStr .= '...';
            }
            
            if ($errTypeArr[$errType]) {
                if ($errTypeArr[$errType] != $detStr) {
                    $more = true;
                }
            }
            $errTypeArr[$errType] = $detStr;
            
            $msgType .= $msgType ? ' ' : '';
            
            foreach ($adminsArr as $userId) {
                $moreUsr = false;
                $msg = "|Нови {$msgType}грешки в системния лог|*";
                
                $urlArr = array($this, 'list', 'type' => $rec->type);
                
                if ($errTypeArr[$errType]) {
                    $msg .= ' - "' . $errTypeArr[$errType] . '"';
                }
                
                if (!$more) {
                    $lastActiveMsg = bgerp_Notifications::getActiveMsgFor($urlArr, $userId);
                    if ($lastActiveMsg) {
                        if (($msg != $lastActiveMsg) && mb_strrpos($lastActiveMsg, ' |и др.|*') === false) {
                            $moreUsr = true;
                        }
                    }
                }
                
                if ($more || $moreUsr) {
                    $msg .= ' |и др.|*';
                }
                
                if (!$this->haveRightFor('list', null, $userId)) {
                    continue;
                }
                
                bgerp_Notifications::add($msg, $urlArr, $userId, 'warning');
            }
        }
    }
    
    
    /**
     * Извлича грешките от "error_log" по cron
     */
    public function cron_getErr()
    {
        // Пътя до файла с грешки
        $errLogPath = get_cfg_var('error_log');
        
        if (!$errLogPath) {
            
            return "Не е дефиниран 'error_log'";
        }
        
        $resStr = 'Няма записи';
        
        $linesArr = core_Os::getLastLinesFromFile($errLogPath, self::$phpErrMaxLinesLimit, true, $resStr);
        
        if (empty($linesArr)) {
            
            return $resStr;
        }
        
        $i = 0;
        
        $arrSave = array();
        $hashArr = array();
        
        foreach ($linesArr as $resStr) {
            $resStr = trim($resStr);
            if (!strlen($resStr)) {
                continue;
            }
            
            // Максимумалния лимит, който ще се записва при извикване
            // Да не претовари сървъра, когато се пуска за първи път или след дълго време
            if ($i >= self::$phpErrMaxLimit) {
                break;
            }
            
            // Парсираме и записваме грешката
            $errArr = self::parsePhpErr($resStr);
            
            $nErrStr = $errArr['type'] . ': ' . $errArr['err'];
            
            $errType = 'logNotice';
            $lifeDays = 7;
            
            foreach (self::$phpErrReportTypeArr as $reportType) {
                if (stripos($errArr['type'], $reportType) !== false) {
                    $errType = 'logErr';
                    $lifeDays = 30;
                    break;
                }
            }
            
            $hash = md5($nErrStr);
            
            if (isset($hashArr[$hash])) {
                continue;
            }
            
            $hashArr[$hash] = true;
            
            $rec = new stdClass();
            $rec->className = get_called_class();
            $rec->detail = $nErrStr;
            if (isset($errArr['time'])) {
                $rec->createdOn = $errArr['time'];
            }
            $rec->type = $errType;
            
            if ($rec->createdOn) {
                $oRec = self::fetch(array("#className = '[#1#]' AND #detail = '[#2#]' AND #type = '[#3#]' AND #createdOn = '[#4#]'", $rec->className, $rec->detail, $rec->type, $rec->createdOn));
            } else {
                $oRec = self::fetch(array("#className = '[#1#]' AND #detail = '[#2#]' AND #type = '[#3#]'", $rec->className, $rec->detail, $rec->type));
            }
            
            // Ако сме достигнали до съществуващ запис спираме процеса
            if ($oRec) {
                break;
            }
            
            // Да не се добавят стари записи, които ще се изтрият веднага по крон
            $before = dt::subtractSecs($lifeDays * 86400);
            if ($errArr['time'] && $before > $errArr['time']) {
                continue;
            }
            
            $i++;
            
            $arrSave[] = $rec;
        }
        
        // Първо да се записват най-старите записи, както са във файла
        $arrSave = array_reverse($arrSave);
        
        $cnt = 0;
        foreach ($arrSave as $rSave) {
            if (self::save($rSave)) {
                $cnt++;
            }
        }
        
        if ($cnt > 0) {
            
            return 'Записани грешки - ' . $cnt;
        }
        
        return 'Няма нови грешки';
    }
    
    
    /**
     * Отдалечено репортване на грешките
     */
    public function cron_reportSysErr()
    {
        $period = core_Cron::getPeriod('reportSysErr');
        
        $before = dt::subtractSecs($period + 59);
        
        $query = self::getQuery();
        $query->where("#createdOn >= '{$before}'");
        
        $query->orWhereArr('type', self::$notifyErrArr);
        
        $query->orderBy('createdOn', 'DESC');
        
        $resArr = array();
        
        $hashArr = array();
        
        $i = 0;
        
        while ($rec = $query->fetch()) {
            $rec->Cnt = 1;
            
            $hash = md5($rec->detail);
            
            if (isset($hashArr[$hash])) {
                $hashId = $hashArr[$hash];
                ++$resArr[$hashId]->Cnt;
                
                continue;
            }
            
            $hashArr[$hash] = $i;
            
            $resArr[$i++] = $rec;
        }
        
        uasort($resArr, array($this, 'orderReportArr'));
        
        if (!empty($resArr)) {
            wp($resArr);
        }
    }
    
    
    /**
     * Подрежда подадените данни - използва се от uasort
     *
     * @param stdClass $a
     * @param stdClass $b
     *
     * @return int
     */
    public function orderReportArr($a, $b)
    {
        if ($a->Cnt == $b->Cnt) {
            
            return 0;
        }
        
        return ($a->Cnt > $b->Cnt) ? -1 : 1;
    }
    
    
    /**
     * Парсира стринга и взма времето, типа и съобщението за грешка
     *
     * @param string $errStr
     *
     * @return array
     *               'time'
     *               'type'
     *               'err'
     */
    protected static function parsePhpErr($errStr)
    {
        $resArr = array();
        $timeEdnPos = 0;
        if (strpos($errStr, '[') === 0) {
            $timeEdnPos = strpos($errStr, '] ');
            $resArr['time'] = substr($errStr, 1, $timeEdnPos - 1);
            $resArr['time'] = strtotime($resArr['time']);
            if ($resArr['time']) {
                $resArr['time'] = dt::timestamp2Mysql($resArr['time']);
            }
        }
        
        $errEndPos = strpos($errStr, ': ');
        
        $resArr['type'] = substr($errStr, $timeEdnPos + 2, $errEndPos - $timeEdnPos - 2);
        $resArr['err'] = substr($errStr, $errEndPos + 2);
        
        return $resArr;
    }
    
    
    /**
     * Начално установяване на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        // Нагласяване на Крон  за извлича грешките от "error_log"
        $rec = new stdClass();
        $rec->systemId = 'getErr';
        $rec->description = 'Извлича грешките от "error_log"';
        $rec->controller = $mvc->className;
        $rec->action = 'getErr';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
        
        // Нагласяване на Крон за изтриване на старите логове в системата
        $rec = new stdClass();
        $rec->systemId = 'DeleteExpiredLogs';
        $rec->description = 'Изтриване на старите логове в системата';
        $rec->controller = $mvc->className;
        $rec->action = 'DeleteOldRecords';
        $rec->period = 24 * 60;
        $rec->offset = rand(1320, 1439); // от 22h до 24h
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
        
        // Нагласяване на Крон за нотификация на администраторите
        $rec = new stdClass();
        $rec->systemId = self::$notifySysId;
        $rec->description = 'Нотифициране на администраторите за грешки';
        $rec->controller = $mvc->className;
        $rec->action = 'notifyForSysErr';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
        
        // Нагласяване на Крон за репортване на грешки
        $rec = new stdClass();
        $rec->systemId = 'reportSysErr';
        $rec->description = 'Репортване на грешки';
        $rec->controller = $mvc->className;
        $rec->action = 'reportSysErr';
        $rec->period = 24 * 60;
        $rec->offset = rand(60, 180); // от 1h до 3h
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
    }
}
