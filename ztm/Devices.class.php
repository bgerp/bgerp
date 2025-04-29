<?php

/**
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_Devices extends core_Master
{
    /**
     * Заглавие на модела
     */
    public $title = 'Устройства';
    public $singleTitle = 'Устройство';


    /**
     * Детайла, на модела
     */
    public $details = 'ztm_Notes';


    /**
     * Кой има право да чете?
     */
    public $canRead = 'ztm, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    public $canSingle = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    public $canReject = 'ztm, ceo';
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * Стойността на затвореното състояние
     *
     * @see plg_State2
     */
    public $closedState = 'draft';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting';
    
    
    /**
     *
     * @var string
     */
    public $listFields = 'name, profileId, ident, model, ip, lastSync, state, configTime, modifiedOn, modifiedBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     *
     * @var string
     */
    protected $deviceDelimiter = '_';
        
    
    /**
     *
     * @var string
     */
//    public $interfaces = 'acs_ZoneIntf';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('ident', 'varchar(64)', 'caption=Идентификатор');
        $this->FLD('model', 'varchar(32)', 'caption=Модел');
        $this->FLD('name', 'varchar(32, ci)', 'caption=Име, mandatory');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Локация->Обект, mandatory');
        $this->FLD('zone', 'varchar(12)', 'caption=Локация->Зона');

        $this->FLD('state', 'enum(draft=Чакащо,active=Активно,rejected=Оттеглено )', 'caption=Състояние,input=none');
        $this->FLD('profileId', 'key(mvc=ztm_Profiles,select=name)', 'caption=Профил, mandatory');
        $this->FLD('accessGroupId', 'key(mvc=ztm_Groups,select=name, allowEmpty)', 'caption=Група->Достъп');
        $this->FLD('fireGroupId', 'key(mvc=ztm_Groups,select=name, allowEmpty)', 'caption=Група->Пожар');
        $this->FLD('ip', 'ip', 'caption=IP, input=none');
        $this->FLD('token', 'password(16)', 'caption=Сесия, input=none');
        $this->FLD('lastSync', 'datetime(format=smartTime)', 'caption=Синхронизиране,input=none');
        $this->FLD('configTime', 'int', 'caption=Време,input=none');

        // @todo - remove
        $this->FNC('showToken', 'varchar', 'caption=Сесия');

        $this->setDbUnique('token');
        $this->setDbIndex('name, state');
    }


    /**
     * @param $mvc
     * @param $rec
     *
     * @todo - Да се изтрие showToken
     */
    function on_CalcShowToken($mvc, $rec)
    {
        $rec->showToken = $rec->token;
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
        if ($requiredRoles != 'no_one') {
            if (($action == 'changestate') && $rec->state != 'active') {
                if ($mvc->fetch(array("#name = '[#1#]' AND #state = 'active'", $rec->name))) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * След подготовка на полетата
     *
     * @todo - Да се изтрие showToken
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if (haveRole('admin')) {
            $data->listFields['showToken'] = "Сесия";
        }
    }


    /**
     * Връща записа за този токен
     *
     * @param string $token
     * @param null|string  $state
     *
     * @return false|stdClass
     */
    public static function getRecForToken($token, $state = 'active')
    {
        $token = trim($token);

        $query = self::getQuery();
        $query->where(array("#token = '[#1#]'", $token));
        if ($state) {
            $query->where(array("#state = '[#1#]'", $state));
        }
        $query->orderBy('lastSync', 'DESC');
        $query->orderBy('modifiedOn', 'DESC');
        $query->orderBy('id', 'DESC');
        $query->limit(1);

        $rec = $query->fetch();

        if ($rec && $rec->state == 'draft') {
            self::logWarning('Неактивирано устройство', $rec->id);
        }
        
        return $rec;
    }
    
    
    /**
     * Обновява времето на синхронизиране
     *
     * @param string $token
     */
    public static function updateSyncTime($token)
    {
        $rec = self::getRecForToken($token, null);
        
        expect($rec);
        
        $rec->lastSync = dt::now();
        
        self::save($rec, 'lastSync');
    }
    
    
    /**
     *
     *
     * @param string $ident
     * @param bool   $active
     *
     * @return string|null
     */
    public static function getToken($ident, $active = true)
    {
        if ($active) {
            $state = 'active';
        } else {
            $state = 'draft';
        }
        
        $token = self::fetchField(array("#ident = '[#1#]' and #state = '[#2#]'", $ident, $state), 'token');
        
        return $token;
    }
    
    
    /**
     * Проверява дали има други неоттеглено устройство за този идентификатор
     *
     * @param string $ident
     *
     * @return bool
     */
    public static function checkIsUniq($ident)
    {
        $token = self::fetch(array("#ident = '[#1#]' and #state != 'rejected'", $ident), 'token');
        
        return ($token === false) ? true : false;
    }


    /**
     * @throws core_exception_Expect
     */
    public function act_Register()
    {
        $ident = Request::get('serial_number');
        $bgerpId = Request::get('bgerp_id');

        $ident = trim($ident);

        expect($ident);
        
        $uniqId = getBGERPUniqId();
        
        // Ако има запис, но все още не е активиран
        if ($bgerpId && ($bgerpId == $uniqId) && $this->getToken($ident, false)) {
            header('HTTP/1.1 403');
            header('Status: 403');
            
            shutdown();
        }
        
        // Ако има активен запис
        if ($bgerpId && ($bgerpId == $uniqId) && $this->getToken($ident)) {
            header('HTTP/1.1 423');
            header('Status: 423');
            
            shutdown();
        }
        
        $rec = new stdClass();
        $rec->ident = $ident;
        $rec->model = Request::get('model');
        $rec->state = 'draft';
        $rec->ip = core_Users::getRealIpAddr();
        $rec->token = str::getRand(str_repeat('*', 16));
        $rec->configTime = Request::get('config_time', 'int');
        
        expect($this->save($rec));
        
        $res = new stdClass();
        $res->token = $rec->token;
        $res->bgerp_id = $uniqId;
        
        core_App::outputJson($res);
    }
    
    
    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if (!$mvc->checkIsUniq($rec->ident)) {
            core_Statuses::newStatus('|Не може да се възстанови, поради дублиране на устройство');
            
            return false;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->configTime = dt::timestamp2Mysql($rec->configTime);
        $row->configTime = dt::mysql2verbal($row->configTime, 'smartTime');
        
        if (isset($rec->profileId)) {
            $row->profileId = ztm_Profiles::getHyperlink($rec->profileId, true);
        }
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$res, $rec, $part)
    {
        if ($part == 'name') {
            $rec = $mvc->fetchRec($rec);
            if (!$rec->name) {
                $res = $mvc->getRecTitle($rec);
            }
        }
    }
    
    
    /**
     * След подготовка на формата за добавяне/редакция
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setReadOnly('ident');
        $data->form->setReadOnly('model');
        
        $data->form->setOptions('accessGroupId', ztm_Groups::getOptionsByType('access'));
        $data->form->setOptions('fireGroupId', ztm_Groups::getOptionsByType('fire'));

        $data->form->setOptions('locationId', crm_Locations::getOwnLocations());
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (ztm_RegisterValues::haveRightFor('list')) {
            $data->toolbar->addBtn('Стойности', array('ztm_RegisterValues', 'list', 'deviceId' => $data->rec->id, 'ret_url' => true),
                'id=btnRegisterValues', 'ef_icon = img/16/specification.png,title=Разглеждане на регистрите на това устройство');
        }

        if (ztm_RegisterValues::haveRightFor('import')) {
            $data->toolbar->addBtn('Импорт', array('ztm_RegisterValues', 'import', 'device' => $data->rec->id, 'ret_url' => true),
                'id=btnRegisterValuesImport', 'ef_icon = img/16/import.png,title=Импортиране на стойности в това устройство');
        }

        if (ztm_RegisterValues::haveRightFor('export')) {
            $data->toolbar->addBtn('Експорт', array('ztm_RegisterValues', 'export', 'device' => $data->rec->id, 'ret_url' => true),
                'id=btnRegisterValuesExport', 'ef_icon = img/16/export.png,title=Експортиране на стойности за това устройство');

            $zQuery = ztm_RegisterValues::getQuery();
            $zQuery->where(array("#deviceId = '[#1#]'", $data->rec->id));
            $userId = core_Users::getCurrent();
            core_Cache::remove('ztm_RegisterValues', "exportRecs{$userId}");
            core_Cache::set('ztm_RegisterValues', "exportRecs{$userId}", $zQuery->fetchAll(), 20);
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * 
     * @param ztm_Devices $mvc
     * @param stdClass $rec
     * @param string $newState
     */
    protected static function on_AfterChangeState(core_Mvc $mvc, &$rec, &$newState)
    {
        if ($newState == 'active') {
            $saveArr = array();
            if (!$rec->name) {
                unset($rec->name);
                $rec->name = $mvc->getRecTitle($rec);
                $saveArr['name'] = 'name';
            }
            
            if (!$rec->profileId) {
                $pQuery = ztm_Profiles::getQuery();
                $pQuery->where("#state = 'active'");
                $pQuery->limit(1);
                $pQuery->orderBy('createdOn', 'DESC');
                $pQuery->show('id');
                $pRec = $pQuery->fetch();
                    
                $rec->profileId = $pRec->id;
                
                $saveArr['profileId'] = 'profileId';
            }
            
            if (!$rec->locationId) {
                $ownLocations = crm_Locations::getOwnLocations();
                if (!empty($ownLocations)) {
                    $rec->locationId = key($ownLocations);
                    $saveArr['locationId'] = 'locationId';
                }
            }
            
            if (!empty($saveArr)) {
                $mvc->save($rec, implode(', ', $saveArr));
            }

            ztm_SensMonitoring::addSens($rec->name);
        }
    }
    
    
    /**
     * Връща списък с наименованията на всички четци за които отговаря
     *
     * @return array
     *
     * @see acs_ZoneIntf
     */
    public function getCheckpoints_()
    {
        $query = $this->getQuery();
        $query->where("#state = 'active'");
        
        $resArr = array();
        
        while ($rec = $query->fetch()) {

            $gVal = array('name' => $this->prepareName($rec), 'locationId' => $rec->locationId, 'zone' => $rec->zone, '_id' => $rec->id);
            
            $resArr[$gVal['name']] = $gVal;
        }
        
        return $resArr;
    }
    
    
    /**
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public function prepareName($rec)
    {
        $rec = $this->fetchRec($rec);

        if ($rec->accessGroupId) {
            $gRec = ztm_Groups::fetch($rec->accessGroupId);
            
            return 'g' . $this->deviceDelimiter . $gRec->name;
        }
        
        return 'r' . $this->deviceDelimiter . $rec->name;
    }
    
    
    /**
     *
     *
     * @param string $name
     *
     * @return array
     */
    protected function getRecFromName($name)
    {
        list($dType, $name) = explode($this->deviceDelimiter, $name);
        
        $resArr = array();
        
        if ($dType == 'r') {
            $rec = self::fetch(array("#name = '[#1#]'", $name));
            $resArr[$rec->id] = $rec;
            
        } elseif ($dType == 'g') {
            $gId = ztm_Groups::fetchField(array("#name = '[#1#]'", $name));
            
            $query = self::getQuery();
            $query->where(array("#accessGroupId = '[#1#]'", $gId));
            
            $resArr = $query->fetchAll();
        }
        
        return $resArr;
    }
    
    
    /**
     * Задава картите, които могат да отварят зоната и времето в което могат да го правят
     *
     * @param string $chp - име на четеца
     * @param array $perm - масив с номер на карта и таймстамп на валидност
     *
     * @see acs_ZoneIntf
     */
    public function setPermissions($chp, $perm)
    {
        cls::get(get_called_class());
        
        $recsArr = $this->getRecFromName($chp);
        
        if (empty($recsArr)) {
            
            return false;
        }
        
        $registerId = ztm_Registers::fetchField("#name = 'ac.allowed_attendees'");
        
        expect($registerId);
        
        $valRes = array();
        if (isset($perm)) {
            foreach ($perm as $cardId => $validUntil) {
                $res = new stdClass();
                $res->card_id = $cardId;
                $res->valid_until = $validUntil;
                $valRes[$cardId] = $res;
            }
        }
        
        foreach ($recsArr as $recId => $rec) {
            ztm_RegisterValues::set($recId, $registerId, $valRes, null, true);
        }
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
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if ($rec->id) {
            $oRec = $mvc->fetch($rec->id);
            $nName = $mvc->prepareName($rec);
            $oName = $mvc->prepareName($oRec);
            
            $rec->__newName = $nName;
            $rec->__oldName = $oName;
            
            $rec->__update = null;
            
            // Ако е променена групата, клонираме записа
            if ($oRec->accessGroupId != $rec->accessGroupId) {
                $rec->__update = false;
            } else {
                
                // Ако е рекдатиран записа, променяме името
                if ($nName != $oName) {
                    $rec->__update = true;
                }
            }
        }
    }
}
