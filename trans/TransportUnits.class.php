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
    public $canEdit = 'transMaster,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'transMaster,ceo';
    
    
    /**
     * Никой не може да добавя директно през модела нови фирми
     */
    public $canAdd = 'transMaster,ceo';
    
    
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
        return cls::get(get_called_class())->makeArray4Select('name');
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
     * @param $productId   - ид на артикул
     * @param $quantity    - к-во в основна мярка
     * @param $packagingId - ид на използвана опаковка
     * @return array|null
     *      ['unitId']   - ид на лог. единица
     *      ['quantity'] - к-во от логистичната единица
     */
    public static function getBestUnit($productId, $quantity, $packagingId)
    {
        if(empty($quantity)) return null;

        // От опаковките на артикула, кои са свързани към ЛЕ
        $packs = cat_Products::getPacks($productId);
        $uQuery = trans_TransportUnits::getQuery();
        $uQuery->in('packagingId', array_keys($packs));
        $uQuery->show('id,packagingId');
        $isPublic = cat_Products::fetchField($productId, 'isPublic');

        // За всяка ле, която е опаковка се събира по к-то в нея
        $calcQuantity = array();
        while ($uRec = $uQuery->fetch()){
            if($packRec = cat_products_Packagings::getPack($productId, $uRec->packagingId)){

                // Ако артикула е стандартен - ще се сортират по остатъка им иначе по-кто в опакока
                $index = ($isPublic == 'yes') ? (($quantity * 1000) % ($packRec->quantity * 1000)) : $packRec->quantity;
                $calcQuantity[$index][$uRec->packagingId] = (object)array('unitId' => $uRec->id, 'quantity' => $packRec->quantity);
            }
        }

        // Ако има намерени
        if(countR($calcQuantity)){

            // Ако е стандартен артикула
            if($isPublic == 'yes'){
                $res = null;

                // и има опаковки на които количеството е кратно
                if(is_array($calcQuantity[0])){
                    if(array_key_exists($packagingId, $calcQuantity[0])){

                        // С приоритет е опаковката от документа
                        $res  = array('unitId' => $calcQuantity[0][$packagingId]->unitId, 'quantity' => $quantity / $calcQuantity[0][$packagingId]->quantity);
                    } else {

                        // Иначе е първата опаковка от документа
                        arr::sortObjects($calcQuantity[0], 'quantity');
                        $res  = array('unitId' => $calcQuantity[0][key($calcQuantity[0])]->unitId, 'quantity' => $quantity / $calcQuantity[0][key($calcQuantity[0])]->quantity);
                    }
                }

                // Връща се така намерената опаковка
                return $res;
            }

            // Сортират се във възходящ ред, така че ЛЕ с най-малко к-во в опаковка да са първи в масива
            ksort($calcQuantity);
            $minQuantityArr = $calcQuantity[key($calcQuantity)];

            // Ако има намерени ЛЕ с най-малко к-во
            if(countR($minQuantityArr)){
                if(array_key_exists($packagingId, $minQuantityArr)){

                    // Ако сред тях е и използваната опаковка - връща се тя
                    $transPackagingId = $packagingId;
                    $transUnitId = $minQuantityArr[$packagingId]->unitId;
                    $transQuantityInPack = $minQuantityArr[$packagingId]->quantity;
                } else {

                    // Ако не е се връща първата с най-малко к-во
                    $transPackagingId = key($minQuantityArr);
                    $transUnitId = $minQuantityArr[$transPackagingId]->unitId;
                    $transQuantityInPack = $minQuantityArr[$transPackagingId]->quantity;
                }

                // В колко бройки от ЛЕ ще се разпредели к-то
                $transUnitCalcedQuantity = $quantity / $transQuantityInPack;

                // Търси се ПО където е произведен артикула в опаковката на ЛЕ с нестандартно к-во
                $pQuery = planning_ProductionTaskDetails::getQuery();
                $pQuery->EXT('measureId', 'planning_Tasks', 'externalName=measureId,externalKey=taskId');
                $pQuery->EXT('tState', 'planning_Tasks', 'externalName=state,externalKey=taskId');
                $pQuery->EXT('packagingId', 'planning_Tasks', 'externalName=labelPackagingId,externalKey=taskId');

                $pQuery->where("#productId = {$productId} AND #type = 'production' AND (#tState IN ('closed', 'active', 'wakeup', 'stopped')) AND #packagingId = {$transPackagingId} AND #quantity != {$transQuantityInPack}");
                $pQuery->orderBy('createdOn', "DESC");
                $pQuery->limit(1);
                $pQuery->show('quantity');

                // Намира се последното произведено нестандартно к-во
                $lastProducedPackQuantity = $pQuery->fetch()->quantity;
                if(!empty($lastProducedPackQuantity)){

                    // Ако има такова и то е над стандартното се закръгля нагоре иначе надоло
                    if($lastProducedPackQuantity > $transQuantityInPack){
                        $transUnitCalcedQuantity = floor($transUnitCalcedQuantity);
                    } else {
                        $transUnitCalcedQuantity = ceil($transUnitCalcedQuantity);
                    }
                } else {

                    // Ако няма последно произведена нестандартна опаковка
                    $minModuleToRound = $transQuantityInPack * 0.15;

                    // Какъв ще е остатъка от к-то ако се побере в опаковката
                    $module = fmod($quantity, $transQuantityInPack);

                    // Ако е над зададените 15% ще се закръгля нагоре иначе надоло
                    if($module >= $minModuleToRound){
                        $transUnitCalcedQuantity = ceil($transUnitCalcedQuantity);
                    } else {
                        $transUnitCalcedQuantity = round($transUnitCalcedQuantity);
                    }
                }

                // Ако е получено естествено число
                if(!empty($transUnitCalcedQuantity)){

                    // Връща се тази ЛЕ с изчисленото к-во
                    return array('unitId' => $transUnitId, 'quantity' => $transUnitCalcedQuantity);
                }
            }
        }

        // Ако се стигне до тук, значи не може да се изчисли ЛЕ
        return null;
    }
}
