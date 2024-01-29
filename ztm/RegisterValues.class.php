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
        $this->FLD('deviceId', 'key(mvc=ztm_Devices, select=name)', 'caption=Устройство,mandatory, refreshForm');
        $this->FLD('registerId', 'key(mvc=ztm_Registers, select=name,allowEmpty)', 'caption=Регистър,mandatory,removeAndRefreshForm=value|extValue,silent, refreshForm');
        $this->FLD('value', 'varchar(32)', 'caption=Стойност,input=none');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Обновено на,input=none');
        
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
        while ($dRec = $dQuery->fetch()) {
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
        $data->query->orderBy('updatedOn,id', 'DESC');
        
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
     * @param int $deviceId   - ид на устройство
     * @param int $registerId - ид на вид регистър
     *
     * @return stdClass|null $rec - записа на регистъра, null ако няма
     */
    public static function get($deviceId, $registerId)
    {
        if ($rec = self::fetch("#deviceId = '{$deviceId}' AND #registerId = '{$registerId}'")) {
            
            // Разпъва стойността и добавя името и приоритета
            $registerRec = ztm_Registers::fetch($registerId, 'scope,name,type');
            $rec->scope = $registerRec->scope;
            $rec->name = $registerRec->name;
            
            $rec->value = ztm_LongValues::getValueByHash($rec->value);
            
            if ($registerRec->type == 'bool') {
                if (is_string($rec->value)) {
                    if ($rec->value == 'false') {
                        $rec->value = false;
                    } elseif ($rec->value == 'true') {
                        $rec->value = true;
                    }
                }
            }
            
            if ($registerRec->type == 'int') {
                $rec->value = intval($rec->value);
            }
            
            if ($registerRec->type == 'float') {
                $rec->value = floatval($rec->value);
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Задава стойност на регистъра
     *
     * @param int           $deviceId    - ид на устройство
     * @param int           $registerId  - ид на регистър
     * @param mixed         $value       - стойност
     * @param datetime|null $time        - време
     * @param bool          $forceUpdate - форсирано обновяване
     * @param bool          $checkState  - обновява само активните регистри
     *
     * @return null|stdClass $rec  - сетнатия запис или null, ако не е обновен
     */
    public static function set($deviceId, $registerId, $value, $time = null, $forceUpdate = false, $checkState = true)
    {
        $now = dt::now();
        $time = isset($time) ? $time : $now;
        
        expect(ztm_Devices::fetchRec($deviceId), 'Няма такова устройство');
        $rRec = ztm_Registers::fetchRec($registerId);
        expect($rRec, 'Няма такъв регистър');
        if ($checkState) {
            expect($rRec->state == 'active', 'Няма такъв активен регистър');
        }
        if ($time > $now) {
            if (dt::secsBetween($time, dt::now()) <= 3600) {
                ztm_Devices::logWarning("Устройството изпраща време в бъдещето '{$time}' ({$now}). Задаваме текущото за {$rRec->name}", $deviceId);
                $time = $now;
            } else {
                expect($time <= $now, "Не може да се зададе бъдеще време '{$time}' ({$now})");
            }
        }
        $rec = (object) array('deviceId' => $deviceId, 'registerId' => $registerId, 'updatedOn' => $time, 'value' => $value);
        $exRec = self::fetch("#deviceId = '{$deviceId}' AND #registerId = '{$registerId}'");
        if (is_object($exRec)) {
            if ($forceUpdate === false && $exRec->updatedOn > $time) {
                
                return;
            }
            
            $rec->id = $exRec->id;
        }
        
        $rec->value = ztm_Registers::recordValue($registerId, $rec->value);
        
        $rec->_skip = true;
        self::save($rec);
        
        return $rec;
    }
    
    
    /**
     * Синхронизация на вътрешните ни данни за регистрите с тези от устройството
     *
     * 1. Заключва синхронизацията
     * 2. $lastSync= min($lastSync, $deviceRec->lastSync) - взема по-старото време от полученото (от контролера) и пазаното в bgERP
     * 3. Взема всички регистри от модела, които са променяни след $lastSync и премахва от тях тези за които scope==device
     * 4. Нанася $regArr върху вътрешното състояние, като взема само регистрите с scope==device и този с scope=both и имащи по-голям таймстамп
     * 5. Връща получения в 3 масив
     *
     * @param array    $regArr   - масив върнат от устройството
     * @param stdClass $deviceId - ид на устройство
     * @param datetime $lastSync - обновени след, коя дата
     * @param null|stdClass $grabDeviceRec - старо устройство, което ще се използва като източник
     *
     * @return stdClass $syncedArray
     */
    public function sync($regArr, $deviceId, $lastSync, $grabDeviceRec = null)
    {
        expect($deviceRec = ztm_Devices::fetchRec($deviceId));
        if (isset($grabDeviceRec)) {
            expect($grabDeviceRec = ztm_Devices::fetchRec($grabDeviceRec));
        }
        
        // Заключване на синхронизацията
        //if(!core_Locks::get("ZTM_SYNC_DEVICE_{$deviceRec->id}")){
        // $this->logNotice('Синхронизирането на устройството е вече заключено');
        //}
        
        // След кое, време ще обновяваме записите
        $lastSyncMin = min($lastSync, $deviceRec->lastSync);

        // Добавяме регистрите от старото устройсво към новото
        if (isset($grabDeviceRec)) {
            // Извлича нашите регистри обновени от предишното устройство
            $ourRegisters = self::grab($grabDeviceRec);
            foreach ($ourRegisters as $regObj) {
                if (array_key_exists($regObj->name, $regArr)) {

                    continue;
                }

                if ($regObj->score == 'system') {

                    continue;
                }

                $regArr[$regObj->name] = $regObj->value;
            }
        } else {
            // Извлича нашите регистри обновени след $lastSyncMin, махайки тези, които са приоритетно от устройството
            $ourRegisters = self::grab($deviceRec, $lastSyncMin);
        }

        // Обработка на входящия масив
        $expandedRegArr = $unknownRegisters = array();
        self::processRegArr($regArr, $deviceRec->id, $expandedRegArr, $unknownRegisters);

        $resultArr = array();
        foreach ($ourRegisters as $ourReg) {
            if ($ourReg->scope != 'device') {
                $resultArr[$ourReg->name] = $ourReg->value;
            }
        }
        
        // Записване на новите стойностти, върнати от устройството с приоритет 'device' или 'both'
        foreach ($expandedRegArr as $obj) {
            try {
                ztm_RegisterValues::set($deviceId, $obj->registerId, $obj->value, $lastSync);
            } catch (core_exception_Expect $e) {
                $dump = $e->getDump();
                ztm_Devices::logErr("'{$obj->name}': {$dump[0]}", $deviceId);
                wp('Грешка при записване на регистър', $e, $obj);
            }
        }

        foreach ($unknownRegisters as $unknownRegisterObj) {
            $value = $unknownRegisterObj->value;
            if (!is_string($value)) {
                $value = core_Type::mixedToString($value);
            }
            ztm_Devices::logErr("Неразпознат ZTM регистър: '{$unknownRegisterObj->name}' : '{$value}'", $deviceId);
        }
        
        // Отключване на синхронизацията
        //core_Locks::release("ZTM_SYNC_DEVICE_{$deviceRec->id}");
        
        // Връщане на синхронизирания масив
        return (object) $resultArr;
    }
    
    
    /**
     * Обработва подадения входящ масив
     *
     * @param array $arr              - подадения масив
     * @param int   $deviceId         - ид на устройство
     * @param array $expandedRegArr   - масив с намерените регистри при нас
     * @param array $unknownRegisters - масив с регистрите, които не са намерени
     *
     * @return void
     */
    private static function processRegArr($arr, $deviceId, &$expandedRegArr, &$unknownRegisters)
    {
        if (is_array($arr)) {
            foreach ($arr as $name => $value) {
                $registerRec = ztm_Registers::fetch(array("#name = '[#1#]'", $name), 'scope,id,state');
                if (($registerRec) && ($registerRec->state == 'active')) {
                    if ($registerRec->scope != 'system') {
                        $expandedRegArr[$registerRec->id] = (object) array('name' => $name, 'value' => $value, 'deviceId' => $deviceId, 'registerId' => $registerRec->id, 'scope' => $registerRec->scope);
                    } else {
                        ztm_Devices::logNotice("Получен регистър {$name} с приоритет {$registerRec->scope}", $deviceId);
                    }
                } else {
                    $unknownRegisters[] = (object) array('name' => $name, 'value' => $value);
                }
            }
        }
    }
    
    
    /**
     * Извлича регистрите за устройството, обновени след определена дата
     *
     * @param int           $deviceId     - ид на устройство
     * @param datetime|null $updatedAfter - обновени след дата
     *
     * @return array $res                 - масив от намерените регистри
     */
    public static function grab($deviceId, $updatedAfter = null)
    {
        $deviceRec = ztm_Devices::fetchRec($deviceId);
        $query = self::getQuery();
        $query->where("#deviceId = '{$deviceRec->id}'");
        if (isset($updatedAfter)) {
            $query->where("#updatedOn >= '{$updatedAfter}'");
        }
        
        $res = array();
        while ($rec = $query->fetch()) {
            $extRec = self::get($deviceRec->id, $rec->registerId);
            $res[$rec->registerId] = (object) array('deviceId' => $deviceRec->id, 'name' => $extRec->name, 'registerId' => $rec->registerId, 'value' => $extRec->value, 'scope' => $extRec->scope);
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
        
        // Кое е устройството
        $deviceRec = ztm_Devices::getRecForToken($token);

        $oDeviceRec = null;
        if (!$deviceRec) {
            $deviceRec = ztm_Devices::getRecForToken($token, 'draft');

            expect($deviceRec, $token);

            // Намира старото устройство със същото име
            if ($deviceRec->name) {
                $dQuery = ztm_Devices::getQuery();
                $dQuery->where(array("#name = '[#1#]'", $deviceRec->name));
                $dQuery->where(array("#token != '[#1#]'", $deviceRec->token));

                $dQuery->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END)");
                $dQuery->orderBy('#orderByState=ASC');

                $dQuery->orderBy('lastSync', 'DESC');
                $dQuery->orderBy('modifiedOn', 'DESC');
                $dQuery->orderBy('id', 'DESC');

                $dQuery->limit(1);

                $oDeviceRec = $dQuery->fetch();

                expect($oDeviceRec, $token, $deviceRec->name);
            }
        }

        $registers = Request::get('registers');

//        ztm_Devices::logDebug('Registers: ' . $registers, $deviceRec);
        
        ztm_Devices::updateSyncTime($token);
        
        // Добавяне на дефолтните стойностти към таблицата с регистрите, ако няма за тях
        $now = dt::now();
        if (isset($oDeviceRec)) {
            $ourRegisters = self::grab($oDeviceRec);
        } else {
            $ourRegisters = self::grab($deviceRec);
        }

        if ($deviceRec->profileId) {
            $defaultArr = ztm_Profiles::getDefaultRegisterValues($deviceRec->profileId);
            foreach ($defaultArr as $dRegKey => $dRegValue) {
                if (!array_key_exists($dRegKey, $ourRegisters)) {
                    try {
                        ztm_RegisterValues::set($deviceRec->id, $dRegKey, $dRegValue, $now, false, false);
                    } catch (core_exception_Expect $e) {
                        $dump = $e->getDump();
                        ztm_Devices::logErr("register: {$dRegKey} - {$dump[0]}", $deviceRec->id);
                    }
                }
            }
        }
        
        try {
            $regArr = array();

            if (!empty($registers)) {
                if (is_scalar($registers)) {
                    if (str::isJson($registers)) {
                        $regArr = (array) json_decode($registers);
                    } else {
                        wp("Невалидни стойности на 'registers'", $registers, $deviceRec);
                        $registers = str::limitLen($registers, 200);
                        ztm_Devices::logErr("Невалидни стойности на 'registers': '{$registers}'", $deviceRec->id);
                    }
                }
            }
            
            // Синхронизране на данните от устройството с тези от системата
            $lastSync = (empty($lastSync)) ? null : dt::timestamp2Mysql($lastSync);
            $result = $this->sync($regArr, $deviceRec->id, $lastSync, $oDeviceRec);
        } catch (core_exception_Expect $e) {
            $result = $registers;
            reportException($e);
        }
        
        if ((array) $result) {
//            ztm_Devices::logDebug('Results: ' . serialize($result), $deviceRec);
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
        
        $form->setReadOnly('deviceId');
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec    Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if ($rec->_skip !== true) {
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
        $Type = ztm_Registers::getOurType($rec->registerId);
        if (($Type instanceof type_Double || $Type instanceof type_Int) && !is_numeric($value)) {
            $row->value = ht::createHint($row->value, 'Стойността е с променен тип', 'error');
        } else {
            $row->value = $Type->toVerbal($value);
        }
        
        $profileValue = null;
        if ($profileId = ztm_Devices::fetchField($rec->deviceId, 'profileId')) {
            $profileValue = ztm_ProfileDetails::fetchField("#profileId = {$profileId}", 'value');
            $profileValue = ztm_LongValues::getValueByHash($profileValue);
        }
        $defaultValue = ztm_Registers::fetchField($rec->registerId, 'default');
        
        if (isset($profileValue) && $profileValue == $value) {
            $row->ROW_ATTR['class'] = 'state-pending';
            $row->value = ht::createHint($row->value, 'Стойността идва от профила', 'notice', true);
        } elseif ($defaultValue == $value) {
            $row->ROW_ATTR['class'] = 'state-draft';
            $row->value = ht::createHint($row->value, 'Стойността е дефолтна за устройството', 'notice', true);
        } else {
            $row->ROW_ATTR['class'] = 'state-template';
        }
        
        $row->deviceId = ztm_Devices::getHyperlink($rec->deviceId, true);
        
        if ($description = ztm_Registers::fetchField($rec->registerId, 'description')) {
            $row->registerId = ht::createHint($row->registerId, $description);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'edit' && $rec) {
            $rRec = ztm_Registers::fetch($rec->registerId);
            if ($rRec->scope == 'device') {
                $requiredRoles = 'no_one';
            }
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
        if (haveRole('ztm, admin')) {
            $warning = '';
            $urlArr = array($mvc, 'truncate');
            $btnName = 'Изчистване';

            if ($deviceId = Request::get('deviceId', 'int')) {
                $urlArr['deviceId'] = $deviceId;
                $warning .= 'Изтриване на запив в регистър|* "' . ztm_Devices::fetchField($deviceId, 'name') . '". |';
                $btnName = 'Изтриване';
            }

            if ($registerId = Request::get('registerId', 'int')) {
                $urlArr['registerId'] = $registerId;
                $warning .= 'Изтриване на регистър|* "' . ztm_Registers::fetchField($registerId, 'name') . '". |';
                $btnName = 'Изтриване';
            }

            if (!trim($warning)) {
                $warning = 'Искате ли да изчистите таблицата';
            }
            $showButton = true;
            if (!$deviceId && !$registerId) {
                if (!haveRole('admin')) {
                    $showButton = false;
                }
            }

            $urlArr['ret_url'] = true;

            if ($showButton) {
                $data->toolbar->addBtn($btnName, $urlArr, "warning={$warning}, ef_icon=img/16/sport_shuttlecock.png");
            }
        }
    }
    
    
    /**
     * Изчиства записите в балансите
     */
    public function act_Truncate()
    {
        requireRole('ztm, admin');
        $deviceId = Request::get('deviceId', 'int');
        $registerId = Request::get('registerId', 'int');

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array($this, 'list');
        }

        if ($deviceId || $registerId) {
            $query = $this->getQuery();
            if ($deviceId) {
                $query->where(array("#deviceId = '[#1#]'", $deviceId));
            }
            if ($registerId) {
                $query->where(array("#registerId = '[#1#]'", $registerId));
            }

            while ($rec = $query->fetch()) {
                $this->delete($rec->id);
            }
        } else {
            requireRole('admin');

            // Изчистваме записите от моделите
            self::truncate();
            ztm_LongValues::truncate();
        }

        return new Redirect($retUrl, '|Избраните записи са изчистени успешно');
    }
}
