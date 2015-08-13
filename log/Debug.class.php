<?php



/**
 * Клас 'log_Debug' - Мениджър за запис на действията на потребителите
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
class log_Debug extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Логове';
    
    
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
    public $oldClassName = 'core_Logs';
    
    
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
    var $loadList = 'plg_SystemWrapper, plg_AutoFilter, plg_Created';
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('className', 'varchar(16)');
        $this->FLD('objectId', 'int');
        $this->FLD('detail', 'text');
        $this->FLD('lifeTime', 'int', 'value=120');
    }
    
    
    /**
     * Добавяне на събитие в лога
     * @deprecated
     */
    static function add($className, $objectId, $detail, $lifeTime = 180)
    {
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        core_Debug::log("$className, $objectId, $detail");
        expect(is_string($className));
        
        $rec = new stdClass();
        $rec->className = $className;
        $rec->objectId = $objectId;
        $rec->detail = $detail;
        $rec->lifeTime = $lifeTime;
        
        return self::save($rec);
    }
    
    
    /**
     * Почистване на старите записи
     */
    function cron_DeleteOldRecords()
    {
        $deletedRecs = $this->delete(" ADDDATE( #createdOn, #lifeTime ) < '" . dt::verbal2mysql() . "'");
        
        return "Log: <B>{$deletedRecs}</B> old records was deleted";
    }
    
    

    /**
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {   
        $data->listFilter->FNC('date', 'date', 'placeholder=Дата');
        $data->listFilter->FNC('class', 'varchar', 'placeholder=Клас,refreshForm, allowEmpty, silent');

        $data->listFilter->setSuggestions('class', core_Classes::makeArray4Select('name'));
        $data->listFilter->showFields = 'date,class';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('date,class', 'silent'); 

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
        
        if ($data->listFilter->rec->class) {
            $class = mb_strtolower($data->listFilter->rec->class);
            $query->where(array("LOWER (#className) = '[#1#]'", $class));
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if (FALSE && cls::load($rec->className, TRUE)) {
            $Class = & cls::get($rec->className);
            
            if(is_object($Class)) {
                if (method_exists($Class, 'logToVerbal')) {
                    $row->what = $Class->logToVerbal($rec->objectId, $rec->detail);
                } else {
                    $row->what = $rec->detail;
                }
            }
        } else {
            $row->what = $rec->className . " * " . $rec->objectId . " * " . $rec->detail;
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
        $rec->controller = "{$mvc->className}";
        $rec->action = 'DeleteOldRecords';
        $rec->period = 24 * 60;
        $rec->offset = rand(1320, 1439); // ot 22h до 24h
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
}