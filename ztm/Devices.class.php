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
    public $singleTitle = 'Устройсво';
    
    
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
     * 
     * @var string
     */
    public $details = 'ztm_DeviceCardReadersDetail';
    
    
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
    
    
    public function description()
    {
        $this->FLD('ident', 'varchar(32)', 'caption=Идентификатор');
        $this->FLD('model', 'varchar(32)', 'caption=Модел');
        $this->FLD('name', 'varchar(32)', 'caption=Име, mandatory');
        $this->FLD('state', 'enum(draft=Чакащо,active=Активно,rejected=Оттеглено )', 'caption=Състояние,input=none');
        $this->FLD('profileId', 'key(mvc=ztm_Profiles,select=name)', 'caption=Профил, mandatory');
        $this->FLD('ip', 'ip', 'caption=IP, input=none');
        $this->FLD('token', 'password(16)', 'caption=Сесия, input=none');
        $this->FLD('lastSync', 'datetime(format=smartTime)', 'caption=Синхронизиране,input=none');
        $this->FLD('configTime', 'int', 'caption=Време,input=none');
        
        $this->setDbUnique('token');
    }
    
    
    /**
     * Връща записа за този токен
     *
     * @param string $token
     * @param bool   $onlyActive
     *
     * @return false|stdClass
     */
    public static function getRecForToken($token, $onlyActive = true)
    {
        $rec = self::fetch(array("#token = '[#1#]'", $token));
        
        if ($rec->state == 'draft') {
            self::logWarning('Неактивирано устройство', $rec->id);
        }
        
        if ($onlyActive) {
            if ($rec->state == 'active') {
                
                return $rec;
            }
            
            return false;
        }
        
        return $rec->state == 'rejected' ? false : $rec;
    }
    
    
    /**
     * Обновява времето на синхронизиране
     *
     * @param string $token
     */
    public static function updateSyncTime($token)
    {
        $rec = self::getRecForToken($token);
        
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
    
    
    public function act_Register()
    {
        $ident = Request::get('serial_number');
        $bgerpId = Request::get('bgerp_id');
        
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
            
            if (!empty($saveArr)) {
                $mvc->save($rec, implode(', ', $saveArr));
            }
        }
    }
}
