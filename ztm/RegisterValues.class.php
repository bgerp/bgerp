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
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'ztm_Registers';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, ztm_Wrapper, plg_Modified';
    
    
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
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой има право да пише?
     */
    public $canWrite = 'ztm, ceo';
    
    
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
        $this->FLD('updatedOn', 'datetime(format=smartTime)','caption=Обновено на');
        
        $this->setDbUnique('deviceId,registerId');
    }
    
    
    /**
     * Подредба на записите
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->setFieldTypeParams('deviceId', array('allowEmpty' => 'allowEmpty'));
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'deviceId,registerId';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        
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
       
        expect(ztm_Devices::fetch($deviceId), "Няма такова устройство");
        expect(ztm_Registers::fetch($registerId), "Няма такъв регистър");
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
        
        $val = (array)ztm_Devices::fetch(3);
        //$val = array('name' => 'ivelin', 'sex' => 'male', 'familyName' => 'dimov','zorrrrry' => 'morrrrrroi');
        bp(self::set(3, 14, $val));
        
        
        $r = '{
    "ac1.next_attendance": null,
    "ac2.next_attendance": null,
    "at.state": null,
    "cwf.value": null,
    "dc.state": null,
    "fire_detect.state": null,
    "general.cwf.leak": 0,
    "general.hwf.leak": 0,
    "general.is_empty": 1,
    "general.is_empty_timeout": null,
    "hwf.value": null,
    "monitoring.clear_errors": 0,
    "monitoring.error_message": "",
    "monitoring.info_message": "",
    "monitoring.warning_message": "",
    "pd.state": null,
    "sc.sub_dev.current.value": 0,
    "sc.sub_dev.current_power.value": 0,
    "sc.sub_dev.total_energy.value": 0,
    "self.ram.current": 0,
    "self.ram.peak": 0,
    "self.time.usage": 0,
    "wc.state": null,
    "wt.state": null
}';
       
        
        $lastSync = '2020-01-01 10:00:00';
        
        $regArr = json_decode($r);
        
        
        
        
        $deviceId = 3;
        
        
        self::sync($regArr, $deviceId, $lastSync);
        
        
        
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
     * ->synch($regArr, $lastSync)
        // $regArr е масив с обекти, идващ от устройството, които имат name->(value, lastUpdate)
        Какво прави тази функция:
        1. Взема лок, да не се дублира
        2. $lastSync= min($lastSync, $deviceRec->lastSync) - взема по-старото време от полученото (от контролера) и пазаното в bgERP
        3. Взема всички регистри от модела, които са променяни след $lastSync и премахва от тях тези за които priority==device
        4. Нанася $regArr върху вътрешното състояние, като взема само регистрите с priority==device и този с priority=time и имащи по-голям таймстамп
        5. Връща получения в 3 масив
     * 
     * 
     * @param array $regArr
     * @param stdClass $deviceId
     * @param datetime $lastSync
     */
    public function sync($regArr, $deviceId, $lastSync)
    {
        expect($deviceRec = ztm_Devices::fetchRec($deviceId));
        //core_Locks::get("ZTM_SYNC_DEVICE_{$deviceRec->id}");
        
        $lastSyncMin = min($lastSync, $deviceRec->lastSync);
        
        $expandRegister = self::expandArr($regArr, $deviceRec->id);
        foreach ($expandRegister as $k => $ourReg){
            if($ourReg->priority == 'device'){
                unset($ourRegisters[$k]);
            }
        }
        
        
        $ourRegisters = self::grab($deviceRec, $lastSyncMin);
        foreach ($ourRegisters as $k => $ourReg){
            if($ourReg->priority == 'device'){
                unset($ourRegisters[$k]);
            }
        }
        
       // bp($ourRegisters);
        
        //Взема всички регистри от модела, които са променяни след $lastSync и премахва от тях тези за които priority==device
        //4. Нанася $regArr върху вътрешното състояние, като взема само регистрите с priority==device и този с priority=time и имащи
        
        
        
        
        
        
        
        bp($expandRegister, $ourRegisters);
    }
    
    public static function expandArr($arr)
    {
        $res = array();
        foreach ($arr as $name => $value){
            $registerRec = ztm_Registers::fetch(array("#name = '[#1#]'", trim($name)), 'priority,id');
            $res[] = (object)array('name' => $name, 'value' => $value, 'registerId' => $registerRec->id, 'priority' => $registerRec->priority);
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
       
        log_System::logAlert(serialize(Request::$vars));
        expect($deviceRec = ztm_Devices::getRecForToken($token), $token);
        ztm_Devices::updateSyncTime($token);
        
        //if(empty($lastSync)){
            
        // samo global system bez device za purvonachalna
            $defaultResponse = ztm_Profiles::getDefaultResponse($deviceRec->profileId);
           // $defaultResponse = array('') + $defaultResponse;
            
            //$test = (object)array("monitoring.enabled" => 1, 'pir_detector.enabled' => 1, 'window_closed.enabled' => 1, 'access_control_1' => 1, 'self' => 1, 'access_control_1.card_reader.enabled' => 1, 'general' => 1);
            //bp($defaultResponse);
            //log_System::logWarning($response);
            //wp($response);
            core_App::outputJson($defaultResponse);
        //}
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
        
        $row->value = $Type->toVerbal($value);
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
    public static function on_AfterPrepareListToolbar($mvc, &$data)
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