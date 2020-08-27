<?php


/**
 * Клас 'ztm_RegisterValues' - Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_RegisterValues extends core_Manager
{
    
    /**
     * Заглавие
     */
    public $title = 'Стойности на регистрите в Zontromat';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, ztm_Wrapper, plg_Modified, plg_Sorting';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,deviceId,registerId,value,updatedOn,modifiedOn,modifiedBy';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('deviceId', 'key(mvc=ztm_Devices, select=name)','caption=Устройство,mandatory');
        $this->FLD('registerId', 'key(mvc=ztm_Registers, select=name,allowEmpty)','caption=Регистър,mandatory,removeAndRefreshForm=value|extValue,silent');
        $this->FLD('value', 'varchar(32)','caption=Стойност,input=none');
        $this->FLD('updatedOn', 'datetime(format=smartTime)','caption=Обновено на,input=none');
        
        $this->setDbUnique('deviceId,registerId');
    }
    
    
    /**
     * Подредба на записите
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $deviceOptions = array();
        $dQuery = ztm_Devices::getQuery();
        $dQuery->where("#state = 'active'");
        while($dRec = $dQuery->fetch()){
            $deviceOptions[$dRec->id] = ztm_Devices::getRecTitle($dRec);
        }
        
        $data->listFilter->setOptions('deviceId', $deviceOptions);
        $data->listFilter->setFieldTypeParams('deviceId', array('allowEmpty' => 'allowEmpty'));
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'deviceId,registerId';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->query->EXT('deviceState', 'ztm_Devices', 'externalName=state,externalKey=deviceId');
        $data->query->where("#deviceState = 'active'");
        $data->query->orderBy("updatedOn,id", "DESC");
        
        if ($data->listFilter->isSubmitted()) {
            if ($deviceId = $data->listFilter->rec->deviceId) {
                $data->query->where("#deviceId = {$deviceId}");
            }
            
            if ($registerId = $data->listFilter->rec->registerId) {
                $data->query->where("#registerId = {$registerId}");
            }
        }
    }
    
    
    /**
     * Извлича стойността на дадения регистър
     * 
     * @param int $deviceId       - ид на устройство
     * @param int $registerId     - ид на вид регистър
     * 
     * @return stdClass|null $rec - записа на регистъра, null ако няма
     */
    public static function get($deviceId, $registerId)
    {
        if($rec = self::fetch("#deviceId = '{$deviceId}' AND #registerId = '{$registerId}'")){
            
            // Разпъва стойността и добавя името и приоритета
            $registerRec = ztm_Registers::fetch($registerId, 'priority,name,type');
            $rec->priority = $registerRec->priority;
            $rec->name = $registerRec->name;
            $rec->value = ztm_LongValues::getValueByHash($rec->value);
        }
        
        return $rec;
    }
    
    
    /**
     * Задава стойност на регистъра
     * 
     * @param int $deviceId         - ид на устройство
     * @param int $registerId       - ид на регистър
     * @param mixed $value          - стойност
     * @param datetime|null $time   - време 
     * @param boolean $forceUpdate  - форсирано обновяване 
     * 
     * @return null|stdClass $rec  - сетнатия запис или null, ако не е обновен
     */
    public static function set($deviceId, $registerId, $value, $time = null, $forceUpdate = false)
    {
        $now = dt::now();
        $time = isset($time) ? $time : $now;
       
        expect(ztm_Devices::fetchRec($deviceId), "Няма такова устройство");
        expect(ztm_Registers::fetchRec($registerId), "Няма такъв регистър");
        expect($time <= $now, 'Не може да се зададе бъдеще време');
        
        $rec = (object)array('deviceId' => $deviceId, 'registerId' => $registerId, 'updatedOn' => $time, 'value' => $value);
        $exRec = self::fetch("#deviceId = '{$deviceId}' AND #registerId = '{$registerId}'");
        if(is_object($exRec)){
            if($forceUpdate === false && $exRec->updatedOn > $time) {
                
                return null;
            }
            
            $rec->id = $exRec->id;
        }
       
        $rec->value = ztm_Registers::recordValue($registerId, $rec->value);
        
        $rec->_skip = true;
        self::save($rec);
        
        return $rec;
    }
    
    
    function act_Test()
    {
        requireRole('debug');
        
       
        $r = '{
    "ac.door_closed.input": "DI2",
    "ac.door_closed2.input": "off",
    "ac.enabled": "yes",
    "ac.entry_reader.enabled": "yes",
    "ac.entry_reader.model": "act230",
    "ac.entry_reader.port.baudrate": 9600,
    "ac.entry_reader.port.name": "COM5",
    "ac.entry_reader.serial_number": "2897",
    "ac.entry_reader.vendor": "TERACOM",
    "ac.entry_reader2.enabled": "no",
    "ac.entry_reader2.model": "act230",
    "ac.entry_reader2.port.baudrate": 9600,
    "ac.entry_reader2.port.name": "COM5",
    "ac.entry_reader2.serial_number": "2897",
    "ac.entry_reader2.vendor": "TERACOM",
    "ac.exit_button.input": "DI8",
    "ac.exit_button2.input": "off",
    "ac.exit_reader.enabled": "yes",
    "ac.exit_reader.model": "act230",
    "ac.exit_reader.port.baudrate": 9600,
    "ac.exit_reader.port.name": "COM11",
    "ac.exit_reader.serial_number": "2911",
    "ac.exit_reader.vendor": "TERACOM",
    "ac.exit_reader2.enabled": "no",
    "ac.exit_reader2.model": "act230",
    "ac.exit_reader2.port.baudrate": 9600,
    "ac.exit_reader2.port.name": "COM11",
    "ac.exit_reader2.serial_number": "2911",
    "ac.exit_reader2.vendor": "TERACOM",
    "ac.lock_mechanism.output": "DO1",
    "ac.lock_mechanism2.output": "off",
    "ac.nearby_attendees": [],
    "ac.pir.input": "DI0",
    "ac.pir2.input": "off",
    "ac.time_to_open": 2,
    "ac.time_to_open2": 2,
    "ac.window_closed.input": "DI3",
    "ac.window_closed2.input": "off",
    "at.enabled": "no",
    "at.input": "DI1",
    "blinds.enabled": "no",
    "blinds.input_fb": "AI0",
    "blinds.output_ccw": "DO0",
    "blinds.output_cw": "DO1",
    "blinds.position": 0,
    "blinds.sun.azimuth.mou": "deg",
    "blinds.sun.azimuth.value": 0,
    "blinds.sun.elevation.mou": "deg",
    "blinds.sun.elevation.value": 0,
    "cc.enabled": "no",
    "cc.goal_temp": 8,
    "cc.tank_temp.circuit": "28FFFCD0001703AE",
    "cc.tank_temp.dev": "temp",
    "cc.tank_temp.enabled": 1,
    "cc.tank_temp.type": "DS18B20",
    "env.emergency": 0,
    "env.enabled": "yes",
    "env.energy": 0,
    "env.is_empty_timeout": 3600,
    "env.light": 1000,
    "env.rh": 60,
    "env.sunpos.enabled": "no",
    "env.temp.a6": 30,
    "env.temp.actual": 29,
    "env.temp.max24": 36,
    "env.temp.min24": 27,
    "env.wind.actual": 3,
    "env.wind.max12": 6,
    "hc.enabled": "no",
    "hc.goal_temp": 20,
    "hc.tank_temp.circuit": "28FF2B70C11604B7",
    "hc.tank_temp.dev": "temp",
    "hc.tank_temp.enabled": 1,
    "hc.tank_temp.type": "DS18B20",
    "hvac.adjust_temp": 0,
    "hvac.air_temp_cent.circuit": "28FFFCD0001703AE",
    "hvac.air_temp_cent.dev": "temp",
    "hvac.air_temp_cent.enabled": 1,
    "hvac.air_temp_cent.type": "DS18B20",
    "hvac.air_temp_lower.circuit": "28FFC4EE00170349",
    "hvac.air_temp_lower.dev": "temp",
    "hvac.air_temp_lower.enabled": 1,
    "hvac.air_temp_lower.type": "DS18B20",
    "hvac.air_temp_upper.circuit": "28FF2B70C11604B7",
    "hvac.air_temp_upper.dev": "temp",
    "hvac.air_temp_upper.enabled": 1,
    "hvac.air_temp_upper.type": "DS18B20",
    "hvac.convector.enabled": 1,
    "hvac.convector.stage_1.output": "RO0",
    "hvac.convector.stage_2.output": "RO1",
    "hvac.convector.stage_3.output": "RO2",
    "hvac.convector.vendor": "silpa",
    "hvac.delta_time": 5,
    "hvac.enabled": "no",
    "hvac.goal_building_temp": 20,
    "hvac.loop1.cnt.enabled": 1,
    "hvac.loop1.cnt.input": "DI4",
    "hvac.loop1.cnt.tpl": 1,
    "hvac.loop1.fan.enabled": 1,
    "hvac.loop1.fan.max_speed": 30,
    "hvac.loop1.fan.min_speed": 0,
    "hvac.loop1.fan.output": "AO3",
    "hvac.loop1.fan.vendor": "HangzhouAirflowElectricApplications",
    "hvac.loop1.temp.circuit": "28FF2B70C11604B7",
    "hvac.loop1.temp.dev": "temp",
    "hvac.loop1.temp.enabled": 1,
    "hvac.loop1.temp.type": "DS18B20",
    "hvac.loop1.valve.enabled": 1,
    "hvac.loop1.valve.feedback": "AI1",
    "hvac.loop1.valve.max_pos": 100,
    "hvac.loop1.valve.min_pos": 0,
    "hvac.loop1.valve.output": "RO4",
    "hvac.loop1.valve.vendor": "TONHE",
    "hvac.loop2.cnt.enabled": 1,
    "hvac.loop2.cnt.input": "DI5",
    "hvac.loop2.cnt.tpl": 1,
    "hvac.loop2.fan.enabled": 1,
    "hvac.loop2.fan.max_speed": 30,
    "hvac.loop2.fan.min_speed": 0,
    "hvac.loop2.fan.output": "AO4",
    "hvac.loop2.fan.vendor": "HangzhouAirflowElectricApplications",
    "hvac.loop2.temp.circuit": "28FFC4EE00170349",
    "hvac.loop2.temp.dev": "temp",
    "hvac.loop2.temp.enabled": 1,
    "hvac.loop2.temp.type": "DS18B20",
    "hvac.loop2.valve.enabled": 1,
    "hvac.loop2.valve.feedback": "AI2",
    "hvac.loop2.valve.max_pos": 100,
    "hvac.loop2.valve.min_pos": 0,
    "hvac.loop2.valve.output": "RO3",
    "hvac.loop2.valve.vendor": "TONHE",
    "hvac.temp.actual": null,
    "hvac.temp.max": 30,
    "hvac.temp.min": 20,
    "hvac.thermal_force_limit": 100,
    "hvac.thermal_mode": 2,
    "hvac.update_rate": 3,
    "light.enabled": "no",
    "light.max": 10000,
    "light.min": 800,
    "light.sensor.circuit": "26607314020000F8",
    "light.sensor.dev": "1wdevice",
    "light.sensor.enabled": 1,
    "light.v1.output": "AO1",
    "light.v2.output": "AO2",
    "monitoring.cw.input": "DI6",
    "monitoring.cw.tpl": 1,
    "monitoring.enabled": "yes",
    "monitoring.hw.input": "DI7",
    "monitoring.hw.tpl": 1,
    "monitoring.pa.dev_id": 3,
    "monitoring.pa.model": "SDM630",
    "monitoring.pa.uart": 2,
    "monitoring.pa.vendor": "Eastron",
    "sys.enabled": "yes",
    "sys.sl.blink_time": 1,
    "sys.sl.output": "LED0",
    "wt.enabled": "no",
    "wt.output": "DO3",
    "wt.pulse_time": 10,
    "wt.reset": 0
}';
       
        
        
        
        
        $lastSync = '2018-01-01 10:00:00';
        
        $deviceRec = ztm_Devices::fetch(5);
        
        
        
        
        
        
        
        
        
        
        
        
        $regArr = (array)json_decode($r);
        
      
        
        
        
        $synced = $this->sync($regArr, $deviceRec->id, $lastSync);
        
        
        //$result = ztm_Profiles::getDefaultResponse($deviceRec->profileId);
        
       
        
        $a = self::get(1, 1);
        //bp($a);
        //$time = '2020-07-10 18:35:34';
        $deviceId = 1;
        $registerId = 128;
        $value = (object)array('test' => 'daaaa', 'test' => 'neeeeee');
        
        
        $t = self::set($deviceId, $registerId, $value, $time);
        
        bp($t);
    }
    
    
    /**
     * Синхронизация на вътрешните ни данни за регистрите с тези от устройството
     * 
     * 1. Заключва синхронизацията
     * 2. $lastSync= min($lastSync, $deviceRec->lastSync) - взема по-старото време от полученото (от контролера) и пазаното в bgERP
     * 3. Взема всички регистри от модела, които са променяни след $lastSync и премахва от тях тези за които priority==device
     * 4. Нанася $regArr върху вътрешното състояние, като взема само регистрите с priority==device и този с priority=time и имащи по-голям таймстамп
     * 5. Връща получения в 3 масив
     * 
     * @param array $regArr      - масив върнат от устройството
     * @param stdClass $deviceId - ид на устройство
     * @param datetime $lastSync - обновени след, коя дата
     * 
     * @return stdClass $syncedArray
     */
    public function sync($regArr, $deviceId, $lastSync)
    {
        expect($deviceRec = ztm_Devices::fetchRec($deviceId));
        
        // Заключване на синхронизацията
        //if(!core_Locks::get("ZTM_SYNC_DEVICE_{$deviceRec->id}")){
           // $this->logNotice('Синхронизирането на устройството е вече заключено');
        //}
        
        // След кое, време ще обновяваме записите
        $lastSyncMin = min($lastSync, $deviceRec->lastSync);
        
        // Обработка на входящия масив
        $expandedRegArr = $unknownRegisters = array();
        self::processRegArr($regArr, $deviceRec->id, $expandedRegArr, $unknownRegisters);
        
        // Извлича нашите регистри обновени след $lastSyncMin, махайки тези, които са приоритетно от устройството
        $ourRegisters = self::grab($deviceRec, $lastSyncMin);
        foreach ($ourRegisters as $k => $ourReg){
            if($ourReg->priority == 'device'){
                unset($ourRegisters[$k]);
            }
        }
        
        // Записване на новите стойностти, върнати от устройството с приоритет 'device' или 'time'
        foreach ($expandedRegArr as $obj){
            try{
                ztm_RegisterValues::set($deviceId, $obj->registerId, $obj->value, $lastSync);
            } catch(core_exception_Expect $e){
                log_System::logErr("'{$obj->name}': {$e->getMessage()}");
            }
        }
        
        foreach ($unknownRegisters as $unknownRegisterObj){
            log_System::logErr("Неразпознат ZTM регистър: '{$unknownRegisterObj->name}' : '{$unknownRegisterObj->value}'");
        }
        
        // Отключване на синхронизацията
        //core_Locks::release("ZTM_SYNC_DEVICE_{$deviceRec->id}");
        
        // Връщане на синхронизирания масив
        return (object)$ourRegisters;
    }
    
    
    /**
     * Обработва подадения входящ масив
     * 
     * @param array $arr                - подадения масив
     * @param int $deviceId             - ид на устройство
     * @param array $expandedRegArr     - масив с намерените регистри при нас
     * @param array $unknownRegisters  - масив с регистрите, които не са намерени
     * 
     * @return void
     */
    private static function processRegArr($arr, $deviceId, &$expandedRegArr, &$unknownRegisters)
    {
        if(is_array($arr)){
            foreach ($arr as $name => $value){
                if($registerRec = ztm_Registers::fetch(array("#name = '[#1#]'", $name), 'priority,id')){
                    if(in_array($registerRec->priority, array('device', 'time'))){
                        $expandedRegArr[$registerRec->id] = (object)array('name' => $name, 'value' => $value, 'deviceId' => $deviceId, 'registerId' => $registerRec->id, 'priority' => $registerRec->priority);
                    }
                } else {
                    $unknownRegisters[] = (object)array('name' => $name, 'value' => $value);
                }
            }
        }
    }
    
    
    /**
     * Извлича регистрите за устройството, обновени след определена дата
     *
     * @param int $deviceId               - ид на устройство
     * @param datetime|null $updatedAfter - обновени след дата
     *
     * @return array $res                 - масив от намерените регистри
     */
    public static function grab($deviceId, $updatedAfter = null)
    {
        $deviceRec = ztm_Devices::fetchRec($deviceId);
        $query = self::getQuery();
        $query->where("#deviceId = '{$deviceRec->id}'");
        if(isset($updatedAfter)){
            $query->where("#updatedOn >= '{$updatedAfter}'");
        }
        
        $res = array();
        while($rec = $query->fetch()){
            $extRec = self::get($deviceRec->id, $rec->registerId);
            $res[$rec->registerId] = (object)array('deviceId' => $deviceRec->id, "name" => $extRec->name, 'registerId' => $rec->registerId, 'value' => $extRec->value, 'priority' => $extRec->priority);
        }
        
        return $res;
    }
    
    
    /**
     * Създава пряк път до публичните статии
     */
    public function act_Sync()
    {
        $token = Request::get('token');
        $lastSync = Request::get('last_sync');
       
        log_System::logDebug(serialize(Request::$vars));
        
        // Кое е устройството
        expect($deviceRec = ztm_Devices::getRecForToken($token), $token);
        ztm_Devices::updateSyncTime($token);
        
        // Добавяне на дефолтните стойностти към таблицата с регистрите, ако няма за тях
        $now = dt::now();
        $ourRegisters = self::grab($deviceRec);
        $defaultArr = ztm_Profiles::getDefaultRegisterValues($deviceRec);
        foreach ($defaultArr as $dRegKey => $dRegValue){
            if(!array_key_exists($dRegKey, $ourRegisters)){
                try{
                    ztm_RegisterValues::set($deviceRec->id, $dRegKey, $dRegValue, $now);
                } catch(core_exception_Expect $e){
                    $errMsh = $e->getMessage();
                    log_System::logErr("register:{$dRegKey} - {$errMsh}");
                }
            }
        }
        
        try{
            $regArr = array();
            $registers = Request::get('registers');
            if(!empty($registers)){
                if(is_scalar($registers)){
                    if(str::isJson($registers)){
                        $regArr = (array)json_decode($registers);
                    }
                }
            }
            
            if(!countR($regArr)){
                log_System::logErr("Невалидни стойности на 'registers': '{$registers}'");
            }
            
            // Синхронизране на данните от устройството с тези от системата
            $lastSync = (empty($lastSync)) ? null : dt::timestamp2Mysql($lastSync);
            $result = $this->sync($regArr, $deviceRec->id, $lastSync);
            
        } catch(core_exception_Expect $e){
            $result = Request::get('registers');
            reportException($e);
        }
        
        // Връщане на резултатния обект
        core_App::outputJson($result);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $form->setDefault('updatedOn', dt::now());
        ztm_Registers::extendAddForm($form);
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if($rec->_skip !== true){
            $rec->value = ztm_Registers::recordValue($rec->registerId, $rec->extValue);
            $rec->updatedOn = dt::now();
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $value = ztm_LongValues::getValueByHash($rec->value);
        $Type = ztm_Registers::getOurType($rec->registerId, false);
        if(($Type instanceof type_Double || $Type instanceof type_Int) && !is_numeric($value)){
            $row->value = ht::createHint($row->value, 'Стойността е с променен тип', 'error');
        } else {
            $row->value = $Type->toVerbal($value);
        }
        
        $profileValue = null;
        if($profileId = ztm_Devices::fetchField($rec->deviceId, 'profileId')){
            $profileValue = ztm_ProfileDetails::fetchField("#profileId = {$profileId}", 'value');
            $profileValue = ztm_LongValues::getValueByHash($profileValue);
        }
        $defaultValue = ztm_Registers::fetchField($rec->registerId, 'default');
        
        if(isset($profileValue) && $profileValue == $value){
            $row->ROW_ATTR['class'] = 'state-pending';
            $row->value = ht::createHint($row->value, 'Стойността идва от профила', 'notice', true);
        } elseif($defaultValue == $value){
            $row->ROW_ATTR['class'] = 'state-draft';
            $row->value = ht::createHint($row->value, 'Стойността е дефолтна за устройството', 'notice', true);
        } else {
            $row->ROW_ATTR['class'] = 'state-template';
        }
        
        $row->deviceId = ztm_Devices::getHyperlink($rec->deviceId, true);
        
        if($description = ztm_Registers::fetchField($rec->registerId, 'description')){
            $row->registerId = ht::createHint($row->registerId, $description);
        }
    }
    
    
    /**
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Бутон за изчистване на всички
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
        }
    }
    
    
    /**
     * Изчиства записите в балансите
     */
    public function act_Truncate()
    {
        requireRole('debug');
        
        // Изчистваме записите от моделите
        self::truncate();
        ztm_LongValues::truncate();
        
        return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
    }
}