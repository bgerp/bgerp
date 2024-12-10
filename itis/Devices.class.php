<?php


/**
 * Регистър на ITC ustrojstwata
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class itis_Devices extends core_Master
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, itis_Wrapper, plg_Sorting, plg_RefreshRows';
    
    
    /**
     * Заглавие
     */
    public $title = 'Логванена IT устройства';
    
    
    /**
     * Права за запис
     */
    public $canWrite = 'debug';
    
    
    /**
     * Права за четене
     */
    public $canRead = 'ceo,itis,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,itis';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,itis';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 100;
    

    /**
     * Детайли на модела
     */
    public $details = 'itis_Changelog';
    

    /**
     * Полета за еденичен изглед
     */
    // public $listFields = 'id,indicatorId, value, time';
    

    /**
     * Без броене на редовете, по време на страницирането
     */
    // public $simplePaging = true;

    
    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 500000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'time';
    
    public $rowToolsSingleField = 'alias';

    /**
     * Описание на модела
     */
    public function description()
    {        
        // Дата на създаването
        $this->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване,input=none,column=none');
        
        // Работно наименование на устройството
        $this->FLD('alias', 'varchar(255)', 'caption=Наименование');
        
        // Група на устройството
        $this->FLD('groupId', 'key(mvc=itis_Groups,select=name)', 'caption=Група');

        // Местоположение на устройството

        // Последна връзка и обновяване
        $this->FLD('lastConn', 'datetime(format=smartTime)', 'caption=Последно->Свързване,input=none');
        $this->FLD('lastUpdate', 'datetime(format=smartTime)', 'caption=Последно->Обновяване,input=none');
        
        // Брой сигнали
        $this->FLD('warningCnt', 'int', 'caption=Брой сигнали->Предупреждения,input=none');
        $this->FLD('alertCnt', 'int', 'caption=Брой сигнали->Тревоги,input=none');

        // Репортвани параметри
        $this->FLD('uniqueID', 'varchar(255)', 'caption=Операционна система->Идентификатор,input=none,column=none');
        $this->FLD('name', 'varchar(255)', 'caption=Операционна система->Име на компютъра,report,input=none,column=none');
        $this->FLD('osVer', 'varchar(255)', 'caption=Операционна система->Версия,report,input=none,column=none');
        $this->FLD('lastBootTime', 'datetime', 'caption=Операционна система->Рестартиране,report,input=none,column=none');
        $this->FLD('freeMem', 'int', 'caption=Свободна памет->RAM,report,input=none,column=none');
        $this->FLD('freeDiskC', 'int', 'caption=Свободна памет->Диск C (GB),report,input=none,column=none');
        $this->FLD('freeDiskD', 'int', 'caption=Свободна памет->Диск D (GB),report,input=none,column=none');
        $this->FLD('processesCnt', 'int', 'caption=Процеси->Брой,report,input=none,column=none');
        $this->FLD('topProcess', 'varchar(1024)', 'caption=Процеси->Извадка,report,input=none,column=none');
        $this->FLD('hostsHash', 'varchar(32)', 'caption=Индикатори->MD5 на hosts,report,input=none,column=none');
        $this->FLD('readyTasksCount', 'int', 'caption=Индикатори->Задачи по разписание,report,input=none,column=none');
        
        $this->FLD('macAddr', 'varchar(17)', 'caption=Мрежа->MAC адрес,report,input=none,column=none');
        $this->FLD('openPorts', 'text', 'caption=Мрежа->Отворени портове,report,input=none,column=none');
        $this->FLD('incomingData', 'int', 'caption=Мрежа->Входящ трафик,report,input=none,column=none');
        $this->FLD('outgoingData', 'int', 'caption=Мрежа->Изходящ трафик,report,input=none,column=none');
        $this->FLD('ownIp', 'varchar(15)', 'caption=IP->Собствено,report,input=none,column=none');
        $this->FLD('agentIp', 'varchar(15)', 'caption=IP->Изпращач,report,input=none,column=none');
        $this->FLD('upIp', 'varchar(15)', 'caption=IP->На доставчика,report,input=none,column=none');

        // Индекси
        $this->setDbIndex('createdOn');
        $this->setDbIndex('uniqueID');
    }

    /**
     * Екшън за добавяне на запис от наблюдавано устройство
     */
    public function act_Log()
    {
        $uid = Request::get('uniqueID');
 
        $rec = self::fetch(array("#uniqueID = '[#1#]'", $uid));

        if(!$rec) {
            $rec = new stdClass();
            $rec->uniqueID = $uid;
            $rec->id = $this->save_($rec);
        }

        $newData = $this->getDataFromRequest();
        $newData['agentIp'] = core_Users::getRealIpAddr();

        $rec = itis_Changelog::updateDeviceData($rec, $newData);
        
        // Ако няма име - задаваме му името на компютъра
        if(!$rec->alias) {
            $rec->alias = $rec->name;
        }
        
        // Текущо време за създаването на записа, ако не е зададено
        if(!isset($rec->createdOn)) {
            $rec->createdOn = dt::now();
        }

        // Времето за последна връзка
        $rec->lastConn = dt::now();
 
        // Добавяне на записа в базата
        if ($this->save($rec)) {
            echo 'Data is saved succesful.';
        } else {
            echo 'Error is saving data';
        }

        die;
    }
    

    /**
     * Връща масив с очакваните параметри от агента
     */
    private function getDataFromRequest()
    {
        $fields = $this->selectFields("#report == 'report'");
        $res = array();

        foreach($fields as $name => $fRec) {
            $type = $this->getFieldType($name);
            $res[$name] = Request::get($name, $type);
        }

        return $res;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        static $fields;

        $row->openPorts = str_replace('|', ', ', $row->openPorts);
        $row->topProcess = str_replace('|', ', ', $row->topProcess);

        $row->freeMem = round($rec->freeMem / 102.4, 2) . ' GB';
        $row->freeDiskC = round($rec->freeDiskC / 102.4, 2) . ' GB';
        $row->freeDiskD = round($rec->freeDiskD / 102.4, 2) . ' GB';
        
        if(!$fields) {
            $fields = $mvc->selectFields("#report == 'report'");
        }

        $nameReq = Request::get('field');
        
        foreach($fields as $name => &$caption) {
            $url = toUrl(array($mvc, 'Single', $rec->id, 'field' => $name));
            $sign = '⛉';
            $title = 'Филтрирай по този параметър';
            if($nameReq == $name) {
                $sign = '⛊';
                $url = toUrl(array($mvc, 'Single', $rec->id));
                $title = 'Премахни филтъра';
            }
            $row->{$name} .= " <a href='{$url}' title='{$title}' style='font-size:0.9em;'>{$sign}</a>";
        }
    }

    

    /**
     * Изпълнява се след опаковане на съдаржанието от мениджъра
     *
     * @param core_Mvc       $mvc
     * @param string|core_ET $res
     * @param string|core_ET $tpl
     * @param stdClass       $data
     *
     * @return bool
     */
    public static function on_AfterRenderWrapping(core_Manager $mvc, &$res, &$tpl = null, $data = null)
    {
        $res->append('.string-value .rightCol { white-space:wrap; text-align:left !important}', 'STYLES');
    }
    
}
