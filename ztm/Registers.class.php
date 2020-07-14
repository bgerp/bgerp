<?php


/**
 * Клас 'ztm_Registers' - Документ за Транспортни линии
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
class ztm_Registers extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Регистри в Zontromat';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, ztm_Wrapper';
    
    
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
    public $listFields = 'id,deviceId,registerDefId,value,updatedOn';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('deviceId', 'key(mvc=ztm_Devices, select=name)','caption=Устройство,mandatory');
        $this->FLD('registerDefId', 'key(mvc=ztm_RegistersDef, select=name)','caption=Регистър');
        $this->FLD('value', 'varchar(32)','caption=Стойност');
        $this->FLD('updatedOn', 'datetime(format=smartTime)','caption=Обновено на');
        
        $this->setDbUnique('deviceId,registerDefId');
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
        $rec = self::fetch("#deviceId = '{$deviceId}' AND #registerDefId = '{$registerId}'");
        
        if(is_object($rec)){
            if($longValue = ztm_RegisterLongValues::fetchField("#registerId = {$rec->id}", 'value')){
                $rec->value = $longValue;
            }
        }
        
        return is_object($rec) ? $rec : null;
    }
    
    
    /**
     * Задава стойност на регистъра
     * 
     * @param int $deviceId        - ид на устройство
     * @param int $registerId      - ид на вид регистър
     * @param mixed $value         - стойност
     * @param datetime|null $time  - време 
     * 
     * @return null|stdClass $rec  - сетнатия запис или null, ако не е обновен
     */
    public static function set($deviceId, $registerId, $value, $time = null)
    {
        $now = dt::now();
        $time = isset($time) ? $time : $now;
       
        expect(ztm_Devices::fetch($deviceId), "Няма такова устройство");
        expect($registerDefRec = ztm_RegistersDef::fetch($registerId), "Няма такъв регистър");
        expect($time <= $now, 'Не може да се зададе бъдеще време');
        
        $rec = (object)array('deviceId' => $deviceId, 'registerDefId' => $registerId, 'updatedOn' => $time, 'value' => $value);
        $exRec = self::fetch("#deviceId = '{$deviceId}' AND #registerDefId = '{$registerId}'");
        if(is_object($exRec)){
            if($exRec->updatedOn > $time) {
                
                return null;
            }
            
            $rec->id = $exRec->id;
        }
        
        $hash = null;
        if(in_array($registerDefRec->type, array('array', 'object', 'text'))){
            $hash = md5(serialize($value));
            $rec->value = $hash;
        }
        
        $id = self::save($rec);
        if(isset($hash)){
            $longRec = (object)array('registerId' => $id, 'value' => $value, 'hash' => $hash);
            if($exId = ztm_RegisterLongValues::fetchField("#registerId = {$id}")){
                $longRec->id = $exId;
            }
            
            ztm_RegisterLongValues::save($longRec);
        }
        
        return $rec;
    }
    
    
    function act_Test()
    {
        requireRole('debug');
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
        $query = self::getQuery();
        $query->where("#deviceId = '{$deviceId}'");
        if(isset($updatedAfter)){
            $query->where("#updatedOn >= '{$updatedAfter}'");
        }
        
        $res = array();
        while($rec = $query->fetch()){
            $res[] = self::get($deviceId, $rec->registerDefId);
        }
        
        return $res;
    }
}