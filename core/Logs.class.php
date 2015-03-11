<?php



/**
 * Клас 'core_Logs' - Мениджър за запис на действията на потребителите
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
class core_Logs extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Логове';
    
    
    /**
     * Колко реда да се листват в една страница?
     */
    var $listItemsPerPage = 200;
    
    
    /**
     * Кои полета ще бъдат показани?
     */
    var $listFields = 'id,createdOn=Кога?,createdBy=Кой?,what=Какво?';
    
    
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
        $this->FLD('detail', 'text(1000000)');
        $this->FLD('lifeTime', 'int', 'value=120');
    }
    
    
    /**
     * Добавяне на събитие в лога
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
        
        return core_Logs::save($rec );
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
        $data->listFilter->FNC('user', 'key(mvc=core_Users,select=nick,allowEmpty,where=#state !\\= \\\'rejected\\\')', 'placeholder=Потребител,silent,refreshForm');
        $data->listFilter->FNC('date', 'date', 'placeholder=Дата');
        $data->listFilter->FNC('class', 'varchar', 'placeholder=Клас');

        $data->listFilter->setSuggestions('class', core_Classes::makeArray4Select('name'));
        $data->listFilter->showFields = 'user,date,class';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('user,date,class', 'silent'); 

    	$query = $data->query;
        $query->orderBy('#createdOn=DESC');
        $query->orderBy('#id=DESC');
        
        // Заявка за филтриране
        $fRec = $data->listFilter->rec;

        if($fRec->user) {
            $query->where("#createdBy = {$fRec->user}");
        }
        
        if($fRec->date) {
            $query->where("#createdOn >= '{$fRec->date}' AND #createdOn <= '{$fRec->date} 23:59:59'");
        }
        
        if($fRec->class) {
            $query->where("#className = '$fRec->class'");
        }
        
        $className = Request::get('className', 'varchar');
        $objectId = Request::get('objectId', 'int');
        
        if ($className) {
            $ctr = & cls::get($className);
            
            if (method_exists($ctr, 'canViewLog')) {
                $canView = $ctr->canViewLog($objectId);
            }
        }
        
        /**
         * @todo: Да се добави възможност за сортиране по потребител
         */
        if (Users::haveRole('admin')) {
            $userId = Request::get('userId', 'int');
        } else {
            $userId = Users::getCurrent();
        }
        
        if ($userId > 0) {
            $query->where("#createdBy = {$userId}");
        } elseif ($userId == -1) {
            $query->where("#createdBy IS NULL");
        }
        
        if ($objectId) {
            if ($objectId == 'NULL') {
                $query->where("#objectId IS NULL");
            } else {
                $query->where("#objectId = {$objectId}");
            }
        }
        
        if ($className) {
            $mvc->info = new ET(tr('Филтър') . ': ');
            $classes = explode('|', $className);
            
            foreach ($classes as $c) {
                $mvc->info->append(' ');
                $mvc->info->append(ht::createLink($c, array($c)));
                $c = strtolower($c);
                $cond .= ($cond ? " OR " : "") . "(lower(#className) LIKE '%{$c}%')";
            }
            
            $query->where($cond);
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