<?php


/**
 * Клас 'cond_Parameters' - Търговски условия
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_Parameters extends bgerp_ProtoParam
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, cond_Wrapper, plg_State2, plg_Search';
    
    
    /**
     * Заглавие
     */
    public $title = 'Видове търговски условия';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Търговско условие';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setFields($this);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'cond/csv/Parameters.csv';
        $fields = array(
            0 => 'name',
            1 => 'driverClass',
            2 => 'sysId',
            3 => 'group',
            4 => 'suffix',
            5 => 'csv_roles',
            6 => 'csv_options',
            7 => 'csv_params',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща стойността на дадено търговско условие за клиента
     * според следните приоритети
     * 	  1. Директен запис в cond_ConditionsToCustomers
     * 	  2. Дефолт метод "get{$conditionSysId}" дефиниран в модела
     *    3. От условието за конкретната държава на контрагента
     *    4. От условието за всички държави за контрагенти
     *    5. NULL ако нищо не е намерено
     *
     * @param int    $cClass         - клас на контрагента
     * @param int    $cId            - ид на контрагента
     * @param string $conditionSysId - sysId на параметър (@see cond_Parameters)
     *
     * @return string $value         - стойността на параметъра
     */
    public static function getParameter($cClass, $cId, $conditionSysId)
    {
        // Ако няма клас и ид на документ да не връща нищо
        if (!isset($cClass) && !isset($cId)) return;
        
        expect($Class = cls::get($cClass));
        expect($cRec = $Class::fetch($cId), $Class->className, $cId);
        expect($condId = self::fetchIdBySysId($conditionSysId));
        
        // Връщаме стойността ако има директен запис за условието
        $value = cond_ConditionsToCustomers::fetchByCustomer($Class, $cId, $condId);
        if ($value) return $value;
        
        // Търси се метод дефиниран за връщане на стойността на условието
        $method = "get{$conditionSysId}";
        if (method_exists($Class, $method)) return $Class::$method($cId);

        // Всички условия групирани по държава (потребителските заместват системните)
        $allConditions = array();
        $condQuery = cond_Countries::getQuery();
        $condQuery->where("#conditionId = {$condId}");
        $condQuery->orderBy('createdBy', 'ASC');
        while($condRec = $condQuery->fetch()){
            $allConditions[$condRec->country] = $condRec;
        }

        // Ако има поле за държава
        $countryFieldName = $Class->countryFieldName;
        if (isset($countryFieldName)) {

            // Търси се имали дефинирано търговско условие за държавата на контрагента
            $countryId = $cRec->{$countryFieldName};
            if ($countryId) {
                if(array_key_exists($countryId, $allConditions)) return $allConditions[$countryId]->value;
            }
        }

        // Търси се има ли глобален дефолт за всички държави
        if(array_key_exists('', $allConditions)) return $allConditions['']->value;

        return null;
    }
    
    
    /**
     * Форсира параметър
     *
     * @param string      $sysId   - систем ид на параметър
     * @param string      $name    - име на параметъра
     * @param string      $type    - тип на параметъра
     * @param NULL|string   $options - опции на параметъра само за типовете enum и set
     * @param NULL|string $suffix  - наставка
     *
     * @return float - ид на параметъра
     */
    public static function force($sysId, $name, $type, $options = array(), $suffix = null)
    {
        // Ако има параметър с това систем ид,връща се
        $id = self::fetchIdBySysId($sysId);
        if (!empty($id)) {
            
            return $id;
        }
        
        // Създаване на параметъра
        $rec = self::makeNewRec($sysId, $name, $type, $options, $suffix);

        return self::save($rec);
    }
}
