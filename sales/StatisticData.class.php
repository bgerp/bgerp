<?php


/**
 * Модел за статистически данни на продажбите
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_StatisticData extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Статистически данни на продажбите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_Sorting';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които се виждат
     */
    public $listFields = 'productId,storeId,storeId,count,quantity,amount,type';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('type', 'enum(sales=Продажби,pos=ПОС,eshop=Е-шоп)', 'mandatory,caption=Източник');
        $this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'input=none,caption=Артикул');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'mandatory,caption=Склад');
        $this->FLD('count', 'int', 'mandatory,caption=Брой');
        $this->FLD('quantity', 'double', 'mandatory,caption=Количество');
        $this->FLD('amount', 'double', 'mandatory,caption=Сума (без ДДС)');
        
        $this->setDbIndex('type');
        $this->setDbUnique('type,productId,storeId');
    }
    
    
    
    public function cron_GatherSalesData()
    {
        $exPosQuery = self::getQuery();
        $exRecs = $exPosQuery->fetchAll();
        $newRecs = self::getSaleStatistic();
        
        if(core_Packs::isInstalled('pos')){
            $posRecs = array();
            $posDataRecs = pos_SellableProductsCache::getPosStatisticData();
            foreach($posDataRecs as $posRec){
                $posRecs["pos|{$posRec->productId}|{$posRec->storeId}"] = (object)array('type' => 'pos', 'productId' => $posRec->productId, 'storeId' => $posRec->storeId, 'count' => $posRec->count, 'quantity' => $posRec->sumQuantity, 'amount' => $posRec->sumAmount);
            }
            
            $newRecs += $posRecs;
        }
        
        if(core_Packs::isInstalled('eshop')){
            /*$eshopRecs = array();
            $eshopDataRecs = eshop_Carts::getStatisticData();
            foreach($eshopDataRecs as $posRec){
                $eshopRecs["eshop|{$posRec->productId}|{$posRec->storeId}"] = (object)array('type' => 'eshop', 'productId' => $posRec->productId, 'storeId' => $posRec->storeId, 'count' => $posRec->count, 'quantity' => $posRec->sumQuantity, 'amount' => $posRec->sumAmount);
            }
            
            $newRecs += $eshopRecs;*/
        }
        
        $res = arr::syncArrays($newRecs, $exRecs, 'type,productId,storeId', 'storeId,count,quantity,amount');
        $this->saveArray($res['insert']);
        $this->saveArray($res['update'], 'id,storeId,count,quantity,amount');
        
        if(countR($res['delete'])){
            foreach ($res['delete'] as $deleteId){
                self::delete($deleteId);
            }
        }
    }
    
    
    public function act_Test()
    {
        requireRole('debug');
        
        $this->cron_GatherSalesData();
    }
    
    
    /**
     * Връща статистическа информация за продажбите
     * 
     * @return array $res 
     */
    public function getSaleStatistic()
    {
        $res = array();
        
        $deltaQuery = sales_PrimeCostByDocument::getQuery();
        $deltaQuery->XPR('count', 'int', 'count(#id)');
        $deltaQuery->XPR('sumQuantity', 'int', 'SUM(#quantity)');
        $deltaQuery->XPR('sumAmount', 'int', 'SUM(#quantity * #sellCost)');
        $deltaQuery->where("#sellCost IS NOT NULL AND (#state = 'active' OR #state = 'closed')");
        $deltaQuery->groupBy('productId,storeId');
        $deltaQuery->orderBy("count", 'DESC');
        $deltaQuery->show('productId,storeId,sumQuantity,sumAmount,count');
        while($saleRec = $deltaQuery->fetch()){
            $res["sales|{$saleRec->productId}|{$saleRec->storeId}"] = (object)array('type' => 'sales', 'productId' => $saleRec->productId, 'storeId' => $saleRec->storeId, 'count' => $saleRec->count, 'quantity' => $saleRec->sumQuantity, 'amount' => $saleRec->sumAmount);
        }
        
        return $res;
    }
}