<?php



/**
 * Импортиран артикул от друга Bgerp система
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Импортиран артикул от друга Bgerp система
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
    public $icon = 'img/16/ep_old_product.png';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public function addFields(core_Fieldset &$form)
    {
        $form->FLD('html', 'html', 'caption=Изглед,before=measureId,input=none');
        $form->FLD('htmlEn', 'html', 'caption=Изглед EN,before=measureId,input=none');
        $form->FLD('quotations', 'blob', 'caption=Данни на оферта,input=none');
        $form->FLD('params', 'blob', 'caption=Параметри,input=none');
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
        
        $info = (core_Lg::getCurrent() == 'bg') ? $row->html : $row->htmlEn;
        if(!empty($row->info)){
            $row->info = $info . "<br>{$row->info}";
        } else {
            $row->info = $info;
        }
       
        unset($row->html);
        unset($row->htmlEn);
        unset($row->params);
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
        $tpl = parent::renderProductDescription($data);
        $Embedder = cls::get($data->Embedder);
        
        $params = $Embedder->getParams($data->rec);
        $paramsTpl = $this->renderParams($Embedder, $data->rec, $params);
        $tpl->append($paramsTpl, 'PARAMETERS');
        
        return $tpl;
    }
    
    
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
            
            foreach ($params as $k => $v){
                if($k == $nameId){
                    
                    return ($verbal === true) ? cat_Params::toVerbal($k, $classId, $id, $v) : $v;
                }
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
        return 5;
    }
}