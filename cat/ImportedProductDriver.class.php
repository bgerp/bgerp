<?php



/**
 * Импортиран артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Импортиран артикул
 */
class cat_ImportedProductDriver extends cat_ProductDriver
{
    
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'cat_ProductDriverIntf';
    
    
    /**
     * Дефолт мета данни за всички продукти
     */
    protected $defaultMetaData = 'canSell,canBuy,canStore,canManifacture';
    
    
    /**
     * Стандартна мярка за ЕП продуктите
     */
    public $uom = 'pcs';
    
    
    /**
     * Иконка за артикула
     */
    public $icon = 'img/16/import.png';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public function addFields(core_Fieldset &$form)
    {
        $form->FLD('importedFromDomain', 'url', 'caption=Импортиран от,input=none');
        $form->FLD('html', 'html', 'caption=Изглед,before=measureId,input=none');
        $form->FLD('htmlEn', 'html', 'caption=Изглед EN,before=measureId,input=none');
        $form->FLD('quotations', 'blob', 'caption=Данни на оферта,input=none');
        $form->FLD('params', 'blob', 'caption=Параметри,input=none');
        $form->FLD('moq', 'double(smartRound)', 'caption=МКП,input=none');
        $form->FLD('conditions', 'blob', 'caption=Допълнителни условия,input=none');
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = null)
    {
        $driverInUrl = Request::get('innerClass', 'int');
        if ($driverInUrl == $this->getClassId()) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal(cat_ProductDriver $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $shortUom = cat_UoM::getShortName($rec->measureId);
        
        foreach (range(1, 3) as $i) {
            if (isset($rec->{"quantity{$i}"})) {
                $row->{"quantity{$i}"} .= " {$shortUom}";
            }
        }
        
        $info .= (core_Lg::getCurrent() == 'bg') ? $row->html : $row->htmlEn;
        if(!empty($row->info)){
            $row->info = $info . "<br>{$row->info}";
        } else {
            $row->info = $info;
        }
       
        unset($row->html);
        unset($row->htmlEn);
        unset($row->params);
        unset($row->quotations);
        unset($row->conditions);
    }
    
    
    /**
     * Рендира данните за показване на артикула
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function renderProductDescription($data)
    {
        if($data->documentType == 'public' ){
            unset($data->row->importedFromDomain);
        }
       
        $tpl = parent::renderProductDescription($data);
        $Embedder = cls::get($data->Embedder);
        
        $params = $Embedder->getParams($data->rec);
        $paramsTpl = $this->renderParams($Embedder, $data->rec, $params);
        $tpl->append($paramsTpl, 'PARAMETERS');
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на параметрите
     */
    private function renderParams($Embedder, $rec, $params)
    {
        $arr = array();
        foreach ($params as $paramId => $value) {
            if (!isset($value)) {
                continue;
            }
            
            $row = new stdClass();
            $pRec = cat_Params::fetch($paramId);
            $pRec->name = tr($pRec->name);
            $row->paramId = cat_Params::getVerbal($pRec, 'name');
            $row->paramValue = cat_Params::toVerbal($paramId, $Embedder, $rec->id, $value);
            
            if (!empty($pRec->suffix) && isset($value)) {
                $row->paramValue .= ' ' . cls::get('type_Varchar')->toVerbal(tr($pRec->suffix));
            }
            
            if (!empty($pRec->group)) {
                $pRec->group = tr($pRec->group);
                $row->group = cat_Params::getVerbal($pRec, 'group');
            }
            
            $arr[] = $row;
        }
        
        $tpl = cat_Params::renderParamBlock($arr);
        $tpl->removeBlock('legendBlock');
        
        return $tpl;
    }
    
    
    /**
     * Връща стойността на параметъра с това име, или
     * всички параметри с техните стойностти
     *
     * @param  int     $classId - ид на клас
     * @param  string  $id      - ид на записа
     * @param  string  $name    - име на параметъра, или NULL ако искаме всички
     * @param  boolean $verbal  - дали да са вербални стойностите
     * @return mixed   $params - стойност или FALSE ако няма
     */
    public function getParams($classId, $id, $name = null, $verbal = false)
    {
        $Embedder = cls::get($classId);
        $rec = $Embedder->fetchRec($id);
        $params = (array)$rec->params;
       
        if (isset($name)) {
            if (!is_numeric($name)) {
                $nameId = cat_Params::fetchField(array("#sysId = '[#1#]'", $name));
            } else {
               $nameId = $name;
            }
            
            if(array_key_exists($nameId, $params)){
                
                return ($verbal === true) ? cat_Params::toVerbal($nameId, $classId, $id, $params[$nameId]) : $params[$nameId];
            }
            
            return false;
        }
        
        // Ако се искат вербални стойности - вербализират се
        if ($verbal === true) {
            $verbalParams = array();
            if (is_array($params)) {
                foreach ($params as $pId => $v) {
                    $verbalParams[$pId] = cat_Params::toVerbal($pId, $classId, $id, $v);
                }
                $params = $verbalParams;
            }
        }
        
        return $params;
    }
    
    
    /**
     * Връща транспортното тегло за подаденото количество
     *
     * @param mixed $rec      - ид или запис на продукт
     * @param int   $quantity - общо количество
     *
     * @return float|NULL - транспортното тегло на общото количество
     */
    public function getTransportWeight($rec, $quantity)
    {
        $weight = $this->getParams(cat_Products::getClassId(), $rec->id, 'transportWeight');
       
        if ($weight) {
            $weight *= $quantity;
            
            return round($weight, 2);
        }
    }
    
    
    /**
     * Връща транспортния обем за подаденото количество
     *
     * @param mixed $rec      - ид или запис на артикул
     * @param int   $quantity - общо количество
     *
     * @return float - транспортния обем на общото количество
     */
    public function getTransportVolume($rec, $quantity)
    {
        $volume = $this->getParams(cat_Products::getClassId(), $rec->id, 'transportVolume');
        if ($volume) {
            $volume *= $quantity;
            
            return round($volume/1000, 3);
        }
    }
    
    
    /**
     * Колко е толеранса
     *
     * @param int   $id       - ид на артикул
     * @param float $quantity - к-во
     *
     * @return float|NULL - толеранс или NULL, ако няма
     */
    public function getTolerance($id, $quantity)
    {
        // Ако за това к-во има запис в данните от оферта на артикула, връща се този толеранс
        $foundRec = $this->getQuoteRecByQuantity($id, $quantity);
        if(is_object($foundRec)){
            
            return $foundRec->tolerance;
        }
        
        return $this->getParams(cat_Products::getClassId(), $id, 'tolerance');
    }
    
    
    /**
     * Колко е срока на доставка
     *
     * @param int   $id       - ид на артикул
     * @param float $quantity - к-во
     *
     * @return float|NULL - срока на доставка в секунди или NULL, ако няма
     */
    public function getDeliveryTime($id, $quantity)
    {
        // Ако за това к-во има запис в данните от оферта на артикула, връща се този срок на доставка
        $foundRec = $this->getQuoteRecByQuantity($id, $quantity);
        if(is_object($foundRec)){
            
            return $foundRec->term;
        }
        
        return $this->getParams(cat_Products::getClassId(), $id, 'term');
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     *
     * @param mixed                                                                              $productId - ид на артикул
     * @param int                                                                                $quantity  - к-во
     * @param float                                                                              $minDelta  - минималната отстъпка
     * @param float                                                                              $maxDelta  - максималната надценка
     * @param datetime                                                                           $datetime  - дата
     * @param float                                                                              $rate      - валутен курс
     * @param string $chargeVat - начин на начисляване на ддс
     *
     * @return stdClass|float|NULL $price  - обект с цена и отстъпка, или само цена, или NULL ако няма
     */
    public function getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime = null, $rate = 1, $chargeVat = 'no')
    {
        // Кой запис от офертите на артикула, отговаря на това количество
        $foundRec = $this->getQuoteRecByQuantity($productId, $quantity);
        if(!is_object($foundRec)) {
            
            return null;
        }
        
        // Ако се търсе себестойност, приспада се отстъпката от продажната цена
        $price = $foundRec->price;
        if ($minDelta === 0 && $maxDelta === 0) {
            if(isset($foundRec->discount)){
                $price = $foundRec->price * (1 - $foundRec->discount);
            }
            
            $primeCostDiscount = sync_Setup::get('IMPORTED_PRODUCT_PRIMECOST_DISCOUNT');
            $price = $price * (1 - $primeCostDiscount);
        } else {
            $discount = $foundRec->discount;
        }
        
        $price = (object)array('price' => $price, 'discount' => $discount);
        
        return $price;
    }
    
    
    /**
     * Може ли драйвера автоматично да си изчисли себестойността
     *
     * @param mixed $productId - запис или ид
     *
     * @return bool
     */
    public function canAutoCalcPrimeCost($productId)
    {
        return true;
    }
    
    
    /**
     * Допълнителните условия за дадения продукт,
     * които автоматично се добавят към условията на договора
     *
     * @param stdClass    $rec     - ид/запис на артикул
     * @param string      $docType - тип на документа sale/purchase/quotation
     * @param string|NULL $lg      - език
     */
    public function getConditions($rec, $docType, $lg = null)
    {
        $lg = isset($lg) ? $lg : core_Lg::getCurrent();
        $conditions = (array)$rec->conditions;
        
        $foundArr = $conditions[$docType][$lg];
        if(is_array($foundArr)){
            
            return $foundArr;
        }
        
        return null;
    }
    
    
    /**
     * Връща минималното количество за поръчка
     *
     * @param int|NULL $id - ид на артикул
     *
     * @return float|NULL - минималното количество в основна мярка, или NULL ако няма
     */
    public function getMoq($id = null)
    {
        return $this->driverRec->moq;
    }
    
    
    /**
     * Кой запис от офертата съотвества на артикула
     * 
     * @param int $productId
     * @param double $quantity
     * @return null|stdClass
     */
    private function getQuoteRecByQuantity($productId, $quantity)
    {
        // Всички данни от оферта на артикула
        $quotations = (array)$this->driverRec->quotations;
        if(!countR($quotations)) {
            
            return null;
        }
       
        // Намира се записа отговарящ на най-близкото количество
        $oldDiff = $index =  null;
        foreach ($quotations as $key => $quotationRec){
            $diff = abs($quantity - $quotationRec->quantity);
            if ($oldDiff > $diff || is_null($oldDiff)) {
                $oldDiff = $diff;
                $index = $key;
            }
        }
        
        // Връщане на намерения запис
        return $quotations[$index];
    }
}