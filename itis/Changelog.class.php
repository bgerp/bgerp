<?php


/**
 * Променени стойности от състоянието на ИТ устройства
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 */
class itis_Changelog extends core_Detail
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, itis_Wrapper, plg_Sorting, plg_Created';
    
    
    /**
     * Заглавие
     */
    public $title = 'Променени стойности от наблюдението на IT устройства';
    

    /**
     * Ключ към мастъра
     */
    public $masterKey = 'deviceId';


    /**
     * Права за запис
     */
    public $canWrite = 'ceo,itis,admin';
    
    
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
    public $listItemsPerPage = '40';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('deviceId', 'key(mvc=itis_Devices,select=alias)', 'caption=Устройство');
        $this->FLD('param', 'varchar(32)', 'caption=Параметър');
        $this->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване');
        $this->FLD('oldValue', 'int', 'caption=Стойност->Стара');
        $this->FLD('newValue', 'int', 'caption=Стойност->Нова');
        $this->FLD('status', 'enum(ok,warning,alert)', 'caption=Статус,value=ok,notNull');

        $this->setDbIndex('deviceId');
        $this->setDbIndex('param');
        $this->setDbIndex('createdOn');
    }


    /**
     * Обновява данните за устройството, като записва промените в този модел (Changelog)
     */
    public static function updateDeviceData($deviceRec, $newData)
    { 
        $Devices = cls::get('itis_Devices');
        $fields =  $Devices->selectFields("#report == 'report'");
        $newData = (array) $newData;

        foreach($fields as $field => $fieldRec) {
            $oldValue  = isset($deviceRec->{$field}) ? trim($deviceRec->{$field}) : null;
            $newValue = $v = isset($newData[$field]) ? trim($newData[$field]) : null;
  
            $status = 'ok';
            $method = 'prepare' . $field;
            if(method_Exists($Devices, $method)) {
                $status = $Devices->{$method}($newValue, $oldValue);
            }
            
            if($oldValue == $newValue) continue;

            // Ако полето е различно от int, то то се замества с индекса му в Values
            $type = $Devices->getFieldType($field);
            if(!is_a($type, 'type_Int') && !is_a($type, 'type_Time')) {
                $oldValue = itis_Values::getId($oldValue);
                $newValue = itis_Values::getId($newValue);
            }
       
            $rec = (object) array('deviceId' => $deviceRec->id, 'param' => $field, 'createdOn' => dt::now(), 
                'oldValue' => $oldValue, 'newValue' => $newValue, 'status' => $status);
            
            // TODO: Check for dangers

            self::save($rec);

            $deviceRec->{$field} = $v;
            $deviceRec->lastUpdate = dt::now();
            if($status == 'warning') {
                $deviceRec->warningCnt++;
            } elseif($status == 'alert') {
                $deviceRec->alertCnt++;
            }
        }

        return $deviceRec;
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
        static $fields, $Devices;
        if(!isset($sields)) {
            $Devices = cls::get('itis_Devices');
            $fields =  $Devices->selectFields("#report == 'report'");
        }
 
        if(isset($fields[$rec->param])) {
            $type = $Devices->getFieldType($rec->param);
            if(!is_a($type, 'type_Int') && !is_a($type, 'type_Time')) {
                $row->oldValue = $type->toVerbal(itis_Values::getValue($rec->oldValue));
                $row->newValue = $type->toVerbal(itis_Values::getValue($rec->newValue));
            } else {
                $row->oldValue = $type->toVerbal($rec->oldValue);
                $row->newValue = $type->toVerbal($rec->newValue);
            }
            if((is_a($type, 'type_Varchar') || is_a($type, 'type_Text')) && !is_a($type, 'type_Time')) {
                $row->oldValue = $row->oldValue = str_replace('|', ', ', $row->oldValue);
                $row->newValue = $row->newValue = str_replace('|', ', ', $row->newValue);
                $row->ROW_ATTR = array('class' => 'string-value'); 
            }
        }
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'DESC');
        if($field = Request::get('field')) {  
            $Devices = cls::get('itis_Devices');
            $fields =  $Devices->selectFields("#report == 'report'");
            if(isset($fields[$field])) {
                $data->query->where(array("#param = '[#1#]'", $field));
            }
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


    /**
     * Извиква се от крона. Премахва изтеклите връзки
     */
    public function cron_RemoveExpiredRecords()
    {
        // Датата към момента от която пазим логовете
        $dateToKeepLogs = dt::addSecs(-itis_Setup::get('TIME_TO_KEEP_LOGS'));

        // Изтриваме всички изтекли записи на индикаторите
        $delLog = self::delete("#createdOn < '{$dateToKeepLogs}'");

        
        return "Бяха изтрити {$delLog} записа в логовете на itis";
    }
}
