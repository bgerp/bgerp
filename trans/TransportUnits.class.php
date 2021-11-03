<?php


/**
 * Клас 'trans_TransportUnits'
 *
 * Документ за Логистични единици
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_TransportUnits extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'transsrv_TransportUnits';
    
    
    /**
     * Заглавие
     */
    public $title = 'Логистични единици';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Логистична единица';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'trans_Wrapper,plg_RowTools2,plg_Created,plg_Modified';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'trans,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'trans,ceo';
    
    
    /**
     * Никой не може да добавя директно през модела нови фирми
     */
    public $canAdd = 'trans,ceo';
    
    
    /**
     * Кой може да разглежда
     */
    public $canList = 'trans,ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(24)', 'caption=Наименование->Единично,mandatory');
        $this->FLD('pluralName', 'varchar(24)', 'caption=Наименование->Множествено,mandatory');
        $this->FLD('abbr', 'varchar(10)', 'caption=Наименование->Съкращение,mandatory');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0,allowEmpty)', 'caption=Възможности->Продуктова опаковка', 'tdClass=small-field nowrap,silent,optionsFunc=cat_UoM::getPackagingOptions');
        $this->FLD('maxWeight', 'cat_type_Uom(unit=t,Min=0)', 'caption=Възможности->Макс. тегло');
        $this->FLD('maxVolume', 'cat_type_Uom(unit=cub.m,Min=0)', 'caption=Възможности->Макс. обем');
        $this->FLD('systemId', 'varchar(10)', 'caption=Систем ид,input=none');

        // Видове транспорт
        $this->FLD('transModes', 'keylist(mvc=trans_TransportModes,select=name)', 'caption=Използване в транспорт->Вид');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Динамично изчисляване на необходимите роли за дадения потребител, за извършване на определено действие към даден запис
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if (isset($rec) && is_int($rec)) {
            $rec = $mvc->fetch($rec);
        }
        
        if (($action == 'delete' || $action == 'edit')) {
            if (isset($rec->createdBy)) {
                if ($rec->createdBy != core_Users::getCurrent()) {
                    $roles = 'ceo';
                }
            }
            
            if (isset($rec->systemId)) {
                $roles = 'no_one';
            }
        }
    }
    
    
    /**
     * Връща всички ЛЕ
     */
    public static function getAll()
    {
        return cls::get(get_called_class())->makeArray4Select('pluralName');
    }
    
    
    /**
     * След началното установяване на този мениджър
     */
    public function loadSetupData()
    {
        $file = 'trans/data/Units.csv';
        
        $fields = array(0 => 'name',
            1 => 'pluralName',
            2 => 'abbr',
            3 => 'systemId',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща записа отговарящ на посочения стринг
     *
     * @param string $sysId
     * @param int|NULL
     */
    public static function fetchIdByName($sysId)
    {
        return self::fetchField(array("#systemId = '[#1#]' OR #name = '[#1#]' OR #pluralName = '[#1#]'", $sysId));
    }
    
    
    /**
     * Връща к-то и името на мярката спрямо числото
     *
     * @param int   $unitId   - ид
     * @param float $quantity - к-во
     *
     * @return string $str     - к-то и мярката
     */
    public static function display($unitId, $quantity)
    {
        $unitId = ($unitId) ? $unitId : self::fetchIdByName('load');
        $quantity = isset($quantity) ? $quantity : 1;
        
        $unitName = ($quantity == 1) ? trans_TransportUnits::fetchField($unitId, 'name') : trans_TransportUnits::fetchField($unitId, 'pluralName');
        $unitName = tr(mb_strtolower($unitName));
        $quantity = core_Type::getByName('int')->toVerbal($quantity);
        $str = "{$quantity} {$unitName}";
        
        return $str;
    }


    /**
     * Коя е най-добрата логистична единица за подаденото к-во от артикула
     *
     * @param $productId
     * @param $quantity
     * @return array|null
     *      ['unitId']   - ид на лог. единица
     *      ['quantity'] - к-во от логистичната единица
     */
    public static function getBestUnit($productId, $quantity)
    {
        $calcQuantity = array();

        // От опаковките на артикула, кои са свързани към ЛЕ
        $packs = cat_Products::getPacks($productId);
        $uQuery = trans_TransportUnits::getQuery();
        $uQuery->in('packagingId', array_keys($packs));
        $uQuery->show('id,packagingId');

        // За всяка от тези ЛЕ се смята в какво к-во ще е подаденото ако се конвертира към нея
        while ($uRec = $uQuery->fetch()){
            if($packRec = cat_products_Packagings::getPack($productId, $uRec->packagingId)){
                $roundedQuantity = round($quantity / $packRec->quantity);
                if($roundedQuantity){
                    $calcQuantity[$uRec->id] = $roundedQuantity;
                }
            }
        }

        // Сортират се във възходящ ред и се връщата лог.ед с най-голямо изчислена опаковка
        arsort($calcQuantity);
        $bestTransUnitId = key($calcQuantity);
        $bestQuantity = $calcQuantity[key($calcQuantity)];
        if(isset($bestTransUnitId) && isset($bestQuantity)){

            return array('unitId' => $bestTransUnitId, 'quantity' => $bestQuantity);
        }

        return null;
    }
}
