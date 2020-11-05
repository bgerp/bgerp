<?php


/**
 * Кеширани последни цени за артикулите
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class price_ProductCosts extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Кеширани последни цени на артикулите';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Кеширани последни цени на артикулите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, classId, price, valior, quantity, sourceId=Документ, updatedOn';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,onlyPublic)', 'caption=Артикул,mandatory');
        $this->FLD('classId', 'class(interface=price_CostPolicyIntf,select=title,allowEmpty)', 'caption=Алгоритъм,mandatory');
        $this->FLD('price', 'double', 'caption=Ед. цена,mandatory,mandatory');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none,mandatory');
        $this->FLD('sourceClassId', 'class(allowEmpty)', 'caption=Документ->Клас');
        $this->FLD('sourceId', 'varchar', 'caption=Документ->Ид');
        $this->FLD('valior', 'date', 'caption=Вальор');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Обновено на');
        
        $this->setDbUnique('productId,classId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->price = price_Lists::roundPrice(price_ListRules::PRICE_LIST_COST, $rec->price, true);
        $row->ROW_ATTR = array('class' => 'state-active');
        
        if (!empty($rec->sourceId)) {
            $Source = cls::get($rec->sourceClassId);
            if (cls::haveInterface('doc_DocumentIntf', $Source)) {
                $row->sourceId = $Source->getLink($rec->sourceId, 0);
            } elseif ($Source instanceof core_Master) {
                $row->sourceId = $Source->getHyperlink($rec->sourceId, true);
            } else {
                $row->sourceId = $Source->getRecTitle($rec->sourceId);
            }
            
            if($Source->getField('state', false)){
                $sState = $Source->fetchField($rec->sourceId, 'state');
                if($sState == 'rejected'){
                    $row->sourceId = "<span class= 'state-{$sState} document-handler'>{$row->sourceId}</span>";
                }
            }
        }
        
        $row->classId = cls::get($rec->classId)->getName(true);
        $row->classId = trim($row->classId, ' "');
    }
    
    
    /**
     * Рекалкулира себестойностите
     */
    public function act_CachePrices()
    {
        expect(haveRole('debug'));
        $datetime = dt::addSecs(-1 * 60);
      
        self::saveCalcedCosts($datetime);
        
        return followRetUrl(null, 'Преизчислени са данните за последния час');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Преизчисли', array($mvc, 'CachePrices', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на себестойностите,target=_blank');
        }
    }
    
    
    /**
     * Обновяване на себестойностите по разписание
     */
    public static function saveCalcedCosts($datetime)
    {
        $self = cls::get(get_called_class());
        $PolicyOptions = core_Classes::getOptionsByInterface('price_CostPolicyIntf');
        
        // Изчисляване на всяка от засегнатите политики, себестойностите на засегнатите пера
        $update = array();
        foreach ($PolicyOptions as $policyId) {
            if (cls::load($policyId, true)) {
                
                // Ако няма отделен крон процес
                $Interface = cls::getInterface('price_CostPolicyIntf', $policyId);
                if($Interface->hasSeparateCalcProcess()) continue;
                
                // Кои са засегнатите артикули, касаещи политиката
                $affectedProducts = $Interface->getAffectedProducts($datetime);
                
                // Ако има такива, ще се прави опит за изчисляване на себестойносттите
                $count = countR($affectedProducts);
                if($count){
                    
                    core_App::setTimeLimit($count * 0.5, 60);
                    $calced = $Interface->calcCosts($affectedProducts);
                    $update = array_merge($update, $calced);
                }
            }
        }
        
        if(!countR($update)){
           
            return;
        }
        
        $now = dt::now();
        array_walk($update, function (&$a) use ($now) {
            $a->updatedOn = $now;
        });
        
        // Синхронизиране на новите записи със старите записи на засегнатите пера
        $exQuery = self::getQuery();
        $exQuery->in('productId', $affectedProducts);
        $exRecs = $exQuery->fetchAll();
        $res = arr::syncArrays($update, $exRecs, 'productId,classId', 'price,quantity,sourceClassId,sourceId,valior');
        
        if (countR($res['insert'])) {
            $self->saveArray($res['insert']);
        }
        
        if (countR($res['update'])) {
            $self->saveArray($res['update'], 'id,price,quantity,sourceClassId,sourceId,updatedOn,valior');
        }
    }
    
    
    /**
     * Намира себестойността на артикула по вида
     *
     * @param int   $productId - ид на артикула
     * @param mixed $source    - източник
     *
     * @return float $price     - намерената себестойност
     */
    public static function getPrice($productId, $source)
    {
        expect($productId);
        
        $price = null;
        if(cls::load($source, true)){
            $Source = cls::get($source);
            $price = static::fetchField("#productId = {$productId} AND #classId = '{$Source->getClassId()}'", 'price');
        }
        
        return $price;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->setOptions('classId', price_Updates::getCostPoliciesOptions());
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'productId,classId';
        $data->listFilter->input();
        
        if ($filterRec = $data->listFilter->rec) {
            if (isset($filterRec->productId)) {
                $data->query->where("#productId = {$filterRec->productId}");
            }
            if (isset($filterRec->classId)) {
                $data->query->where("#classId = {$filterRec->classId}");
            }
        }
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
}
