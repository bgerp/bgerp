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
    var $listFields = 'id,createdOn=Кога?,createdBy=Кой?,what=Какво?';
    
    
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
    protected static $notifyErrArr = array('emerg', 'alert', 'crit', 'err', 'warning');
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('className', 'varchar(16)');
        $this->FLD('objectId', 'int');
        $this->FLD('detail', 'text');
        $this->FLD('lifeDays', 'int', 'value=120, oldFieldName=lifeTime');
        $this->FLD('type', 'enum(info=Инфо,emerg=Спешно,alert=Тревога,crit=Критично,err=Грешка,warning=Предупреждение,notice=Известие,debug=Дебъг)', 'caption=Тип');
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
        
        if ($type && in_array($type, self::$notifyErrArr)) {
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
            
            $errType = $this->getVerbal($rec, 'type');
            $msg = '|Грешка в системата от тип|*: |' . $errType;
            
            foreach ($adminsArr as $userId) {
                if (!$this->haveRightFor('list', NULL, $userId)) continue;
                $urlArr = array($this, 'list', 'type' => $rec->type);
                bgerp_Notifications::add($msg, $urlArr, $userId, 'warning');
            }
        }
    }
    
    
    /**
     * Начално установяване на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Нагласяване на Крон        
        $rec = new stdClass();
        $rec->systemId = 'DeleteExpiredLogs';
        $rec->description = 'Изтриване на старите логове в системата';
        $rec->controller = $mvc->className;
        $rec->action = 'DeleteOldRecords';
        $rec->period = 24 * 60;
        $rec->offset = rand(1320, 1439); // ot 22h до 24h
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
    }
}
