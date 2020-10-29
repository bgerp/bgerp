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
class ztm_DeviceCardReadersDetail extends core_Detail
{
    /**
     * Заглавие на модела
     */
    public $title = 'Карти за достъп';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * 
     * @var string
     */
    public $canReject = 'ztm, ceo';
    
    
    /**
     * 
     */
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'deviceId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, ztm_Wrapper, plg_Rejected, plg_State2';
    
    
    /**
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * 
     * @var string
     */
    public $interfaces = 'acs_ZoneIntf';
    
    
    /**
     * 
     * @var string
     */
    protected $deviceDelimiter = '_';
    
    
    /**
     * 
     * @var array
     */
    protected $saveRecArr = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('deviceId', 'key(mvc=ztm_Devices, select=name)', 'caption=Устройство,mandatory');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Локация, mandatory');
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        
        $this->setDbUnique('deviceId, name, locationId');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $fRec = &$data->form->rec;
        
        $data->form->setOptions('locationId', crm_Locations::getOwnLocations());
    }
    
    
    /**
     * Връща списък с наименованията на всички четци за които отговаря
     *
     * @return array
     * 
     * @see acs_ZoneIntf
     */
    public function getCheckpoints()
    {
        $query = $this->getQuery();
        $query->where("#state = 'active'");
        
        $resArr = array();
        
        while ($rec = $query->fetch()) {
            $resArr[] = array('name' => $this->prepareName($rec), 'locationId' => $rec->locationId);
        }
        
        return $resArr;
    }
    
    
    /**
     * 
     * @param stdClass $rec
     * 
     * @return string
     */
    protected function prepareName($rec)
    {
        
        return $rec->deviceId . $this->deviceDelimiter . $rec->name;
    }
    
    
    /**
     * 
     * 
     * @param string $name
     * 
     * @return false|stdObject
     */
    protected function getRecFromName($name)
    {
        list($deviceId, $name) = explode($this->deviceDelimiter, $name);
        
        return self::fetch(array("#deviceId = '[#1#]' AND #name = '[#2#]'", $deviceId, $name));
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
        
        $rec = $this->getRecFromName($chp);
        
        if (!$rec) {
            
            return false;
        }
        
        $registerId = ztm_Registers::fetchField("#name = 'ac.allowed_attendees'");
        
        expect($registerId);
        
        $valRes = array();
        foreach ($perm as $cardId => $validUntil) {
            $res = new stdClass();
            $res->card_id = $cardId;
            $res->valid_until = $validUntil;
            $valRes[$cardId] = $res;
        }
        
        setIfNot($this->saveRecArr[$rec->deviceId][$registerId], array());
        
        $this->saveRecArr[$rec->deviceId][$registerId] = array_merge($this->saveRecArr[$rec->deviceId][$registerId], $valRes);
    }
    
    
    /**
     * 
     * 
     * @params ztm_DeviceCardReadersDetail $mvc 
     * 
     * {@inheritDoc}
     * @see core_Manager::on_Shutdown()
     */
    public static function on_Shutdown($mvc)
    {
        if ($mvc->saveRecArr) {
            foreach ($mvc->saveRecArr as $dId => $regArr) {
                $vRes = array();
                
                foreach ($regArr as $regId => $valRes) {
                    foreach ($valRes as $vResRec) {
                        $vRes[] = $vResRec;
                    }
                }
                
                ztm_RegisterValues::set($dId, $regId, $vRes, null, true);
            }
        }
    }
}
