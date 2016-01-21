<?php



/**
 * Клас 'log_System' - Мениджър за запис на действията на потребителите
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class log_System extends core_Manager
{
    
    
    /**
     * Максимален брой записи, които ще се извличат
     */
    static $phpErrMaxLimit = 20;
    
    
    /**
     * Кои PHP грешки да се каствам logErr
     * Останалите грешки ще са logNotice
     */
    static $phpErrReportTypeArr = array('error', 'warning');
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Системен лог';
    
    
    /**
     * Колко реда да се листват в една страница?
     */
    var $listItemsPerPage = 50;
    
    
    /**
     * Кои полета ще бъдат показани?
     */
    var $listFields = 'id, createdOn=Дата, createdBy=Потребител, what=Действие';
    
    
    /**
     * 
     */
    public $oldClassName = 'log_Debug';
    
    
    /**
     * Кой може да листва и разглежда?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    var $canWrite = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_Created';
    
    
    /**
     * 
     */
    protected static $notifySysId = 'notifyForSysErr';
    
    
    /**
     * 
     */
    protected static $notifyErrArr = array('emerg', 'alert', 'crit', 'err', 'logErr');
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('className', 'varchar(64)');
        $this->FLD('objectId', 'int');
        $this->FLD('detail', 'text');
        $this->FLD('lifeDays', 'int', 'value=120, oldFieldName=lifeTime');
        $this->FLD('type', 'enum(info=Инфо,emerg=Спешно,alert=Тревога,crit=Критично,err=Грешка,warning=Предупреждение,notice=Известие,debug=Дебъг,logErr=Грешка в лога, logNotice=Известие в лога)', 'caption=Тип');
    }
    
    
    /**
     * Добавяне на събитие в лога
     * 
     * @param string $className
     * @param integer|NULL $objectId
     * @param string $action
     * @param string $type
     * @param integer $lifeDays
     */
    public static function add($className, $action, $objectId = NULL, $type = 'info', $lifeDays = 7)
    {
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        
        $logStr = $className;
        $logStr .= $objectId ? " - " . $objectId : '';
        $logStr .=  ": " . $action;
        Debug::log($logStr);
        
        expect(is_string($className));
        
        $rec = new stdClass();
        $rec->className = $className;
        $rec->objectId = $objectId;
        $rec->detail = $action;
        $rec->lifeDays = $lifeDays;
        $rec->type = $type;
        
        return self::save($rec);
    }
    
    
    /**
     * Почистване на старите записи
     */
    function cron_DeleteOldRecords()
    {
        $deletedRecs = $this->delete(" ADDDATE( #createdOn, #lifeDays ) < '" . dt::verbal2mysql() . "'");
        
        return "Log: <B>{$deletedRecs}</B> old records was deleted";
    }
    
    

    /**
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FNC('date', 'date', 'placeholder=Дата');
        $data->listFilter->FNC('class', 'varchar', 'placeholder=Клас,refreshForm, allowEmpty, silent');
        
        $data->listFilter->fields['type']->caption = 'Тип';
        $data->listFilter->fields['type']->type->options = array('' => '') + $data->listFilter->fields['type']->type->options;
        $data->listFilter->fields['type']->refreshForm = 'refreshForm';
        
        $data->listFilter->setSuggestions('class', core_Classes::makeArray4Select('name'));
        $data->listFilter->showFields = 'date, class, type';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input($data->listFilter->showFields, 'silent'); 

    	$query = $data->query;
        $query->orderBy('#id=DESC');
        
        // Заявка за филтриране
        $fRec = $data->listFilter->rec;

        if($fRec->date) {
            $query->where("#createdOn >= '{$fRec->date}' AND #createdOn <= '{$fRec->date} 23:59:59'");
        }
        
        if($fRec->class) {
            $query->where("#className = '$fRec->class'");
        }
        
        $objectId = Request::get('objectId', 'int');
        if ($objectId) {
            if ($objectId == 'NULL') {
                $query->where("#objectId IS NULL");
            } else {
                $query->where("#objectId = {$objectId}");
            }
        }
    
        // Добавяме класовете, за които има запис в търсения резултат
        $classSuggArr = array();
        $cQuery = clone $query;
        $cQuery->groupBy('className');
        while ($cRec = $cQuery->fetch()) {
            
            $className = trim($cRec->className);
            
            if ($className) {
                $classSuggArr[$className] = $className;
            }
        }
        
        if ($classSuggArr) {
            $classSuggArr = array('' => '') + $classSuggArr;
            $data->listFilter->setOptions('class', $classSuggArr);
        }
        
        if ($fRec->class) {
            $class = mb_strtolower($fRec->class);
            $query->where(array("LOWER (#className) = '[#1#]'", $class));
        }
        
        // Филтрираме по тип
        if (trim($fRec->type)) {
            $query->where(array("#type = '[#1#]'", $fRec->type));
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ROW_ATTR['class'] = "logs-type-{$rec->type}";
        
        $row->what = log_Data::prepareText($rec->detail, $rec->className, $rec->objectId);
    }
    
    
    /**
     * Добавя div със стил за състоянието на треда
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
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
    function cron_NotifyForSysErr()
    {
        $period = core_Cron::getPeriod(self::$notifySysId);
        $period += 59;
        
        $from = dt::subtractSecs($period);
        
        $query = $this->getQuery();
        $query->where("#createdOn >= '{$from}'");
        $query->orWhereArr('type', self::$notifyErrArr);
        $query->groupBy('type');
        
        $roleId = core_Roles::fetchByName('admin');
        $adminsArr = core_Users::getByRole($roleId);
        while($rec = $query->fetch()) {
            
            switch ($rec->type) {
                case 'emerg':
                case 'alert':
                    $msgType = 'спешни';
                break;
                
                case 'crit':
                    $msgType = 'критични';
                break;
                
                case 'logErr':
                    $msgType = 'PHP';
                break;
                
                default:
                    $msgType = '';
                break;
            }
            
            $msgType .= $msgType ? ' ' : '';
            
            $msg = "|Нови {$msgType}грешки в системния лог";
            
            foreach ($adminsArr as $userId) {
                if (!$this->haveRightFor('list', NULL, $userId)) continue;
                $urlArr = array($this, 'list', 'type' => $rec->type);
                bgerp_Notifications::add($msg, $urlArr, $userId, 'warning');
            }
        }
    }
    
    
    /**
     * Извлича грешките от "error_log" по cron
     */
    function cron_getErr()
    {
        // Пътя до файла с грешки
        $errLogPath = get_cfg_var("error_log");
        
        if (!$errLogPath) return;
        
        if (!is_file($errLogPath)) return;
        
        $content = file_get_contents($errLogPath);
        
        if (!$content) return;
        
        $contentArr = explode("\n", $content);
        $contentArr = array_reverse($contentArr);
        
        $i = 0;
        
        $arrSave = array();
        $hashArr = array();
        
        foreach ($contentArr as $errStr) {
            $errStr = trim($errStr);
            if (!strlen($errStr)) continue;
            
            // Максимумалния лимит, който може да се извлече
            // Да не претовари сървъра, когато се пуска за първи път или след дълго време
            if ($i >= self::$phpErrMaxLimit) break;
            
            // Парсираме и записваме грешката
            $errArr = self::parsePhpErr($errStr);
            
            $nErrStr = $errArr['type'] . ': ' . $errArr['err'];
            
            $errType = 'logNotice';
            $lifeDays = 7;
            
            foreach (self::$phpErrReportTypeArr as $reportType) {
                if (stripos($errArr['type'], $reportType) !== FALSE) {
                    $errType = 'logErr';
                    $lifeDays = 30;
                    break;
                }
            }
            
            $hash = md5($nErrStr);
            
            if (isset($hashArr[$hash])) continue;
            
            $hashArr[$hash] = TRUE;
            
            $rec = new stdClass();
            $rec->className = get_called_class();
            $rec->detail = $nErrStr;
            if ($errArr['time']) {
                $rec->createdOn = $errArr['time'];
            }
            $rec->type = $errType;
            
            // Ако сме достигнали до съществуващ запис спираме процеса
            if (self::fetch(array("#className = '[#1#]' AND #detail = '[#2#]' AND #createdOn = '[#3#]' AND #type = '[#4#]'", $rec->className, $rec->detail, $rec->createdOn, $rec->type))) break;
            
            // Да не се добавят стари записи, които ще се изтрият веднага по крон
            $before = dt::subtractSecs($lifeDays * 86400);
            if ($errArr['time'] && $before > $errArr['time']) continue;
            
            $i++;
            
            $arrSave[] = $rec;
        }
        
        // Първо да се записват най-старите записи, както са във файла
        $arrSave = array_reverse($arrSave);
        foreach ($arrSave as $rSave) {
            self::save($rSave);
        }
    }
    
    
    /**
     * Отдалечено репортване на грешките
     */
    function cron_reportSysErr()
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
                $resArr[$hashId]->Cnt += 1;
                
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
     * @param stdObject $a
     * @param stdObject $b
     * 
     * @return integer
     */
    function orderReportArr($a, $b)
    {
        if ($a->Cnt == $b->Cnt) return 0;
        
        return ($a->Cnt > $b->Cnt) ? -1 : 1;
    }
    
    
    /**
     * Парсира стринга и взма времето, типа и съобщението за грешка
     * 
     * @param string $errStr
     * 
     * @return array
     * 'time'
     * 'type'
     * 'err'
     */
    protected static function parsePhpErr($errStr)
    {
        $resArr = array();
        $timeEdnPos = 0;
        if (strpos($errStr, '[') === 0) {
            $timeEdnPos = strpos($errStr, '] ');
            $resArr['time'] = substr($errStr, 1, $timeEdnPos-1);
            $resArr['time'] = dt::verbal2mysql($resArr['time']);
        }
        
        $errEndPos = strpos($errStr, ': ');
        
        $resArr['type'] = substr($errStr, $timeEdnPos+2, $errEndPos-$timeEdnPos-2);
        $resArr['err'] = substr($errStr, $errEndPos+2);
        
        return $resArr;
    }
    
    
    /**
     * Начално установяване на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
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
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
    }
}
