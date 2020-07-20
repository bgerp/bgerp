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
    public $loadList = 'sales_Wrapper, plg_Sorting, plg_AlignDecimals2';
    
    
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
        $this->FLD('type', 'enum(,sales=Продажби,pos=ПОС,eshop=Е-шоп)', 'mandatory,caption=Източник');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Артикул,silent,class=ajaxSelect');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'mandatory,caption=Склад,silent');
        $this->FLD('count', 'int', 'mandatory,caption=Брой');
        $this->FLD('quantity', 'double', 'mandatory,caption=Количество');
        $this->FLD('amount', 'double', 'mandatory,caption=Сума (без ДДС)');
        
        $this->setDbIndex('type');
        $this->setDbUnique('type,productId,storeId');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'productId,storeId,type';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('productId,storeId,type');
        
        if($rec = $data->listFilter->rec){
            if(!empty($rec->productId)){
                $data->query->where("#productId = {$rec->productId}");
            }
            
            if(!empty($rec->storeId)){
                $data->query->where("#storeId = {$rec->storeId}");
            }
            
            if(!empty($rec->type)){
                $data->query->where("#type = '{$rec->type}'");
            }
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->storeId)){
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }
        
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
    }
    
    
    /**
     * Събиране на статистическата информация по разписание
     */
    public function cron_GatherSalesData()
    {
        $exPosQuery = self::getQuery();
        $exRecs = $exPosQuery->fetchAll();
        
        $newRecs = array();
        $saleRecs = self::getSaleStatistic();
        foreach ($saleRecs as $saleRec){
            $newRecs["sales|{$saleRec->productId}|{$saleRec->storeId}"] = (object)array('type' => 'sales', 'productId' => $saleRec->productId, 'storeId' => $saleRec->storeId, 'count' => $saleRec->count, 'quantity' => $saleRec->sumQuantity, 'amount' => $saleRec->sumAmount);
        }
        
        if(core_Packs::isInstalled('pos')){
            $posRecs = array();
            $posDataRecs = pos_SellableProductsCache::getPosStatisticData();
            foreach($posDataRecs as $posRec){
                $posRecs["pos|{$posRec->productId}|{$posRec->storeId}"] = (object)array('type' => 'pos', 'productId' => $posRec->productId, 'storeId' => $posRec->storeId, 'count' => $posRec->count, 'quantity' => $posRec->sumQuantity, 'amount' => $posRec->sumAmount);
            }
            
            $newRecs += $posRecs;
        }
        
        if(core_Packs::isInstalled('eshop')){
            $saleEshopRecs = self::getSaleStatistic(true);
            $eshopRecs = array();
            foreach ($saleEshopRecs as $saleEshopRec){
              $eshopRecs["eshop|{$saleEshopRec->productId}|{$saleEshopRec->storeId}"] = (object)array('type' => 'eshop', 'productId' => $saleEshopRec->productId, 'storeId' => $saleEshopRec->storeId, 'count' => $saleEshopRec->count, 'quantity' => $saleEshopRec->sumQuantity, 'amount' => $saleEshopRec->sumAmount);
            }
            $newRecs += $eshopRecs;
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
     * @return array
     */
    public function getSaleStatistic($onlyOnlineSales = false)
    {
        $deltaQuery = sales_PrimeCostByDocument::getQuery();
        $deltaQuery->XPR('count', 'int', 'count(#id)');
        $deltaQuery->XPR('sumQuantity', 'int', 'SUM(#quantity)');
        $deltaQuery->XPR('sumAmount', 'int', 'SUM(#quantity * #sellCost)');
        $deltaQuery->where("#sellCost IS NOT NULL AND (#state = 'active' OR #state = 'closed') AND #isPublic = 'yes'");
        $deltaQuery->groupBy('productId,storeId');
        $deltaQuery->orderBy("count", 'DESC');
        $deltaQuery->show('productId,storeId,sumQuantity,sumAmount,count');
        
        if($onlyOnlineSales){
            $cartQuery = eshop_Carts::getQuery();
            $cartQuery->EXT('threadId', 'sales_Sales', 'externalName=threadId,externalKey=saleId');
            $cartQuery->where("#saleId IS NOT NULL");
            $cartQuery->show('threadId');
            $threadsArr = arr::extractValuesFromArray($cartQuery->fetchAll(), 'threadId');
            if(countR($threadsArr)){
                $deltaQuery->in('threadId', $threadsArr);
            } else {
                $deltaQuery->where("1=2");
            }
        }
        
        return $deltaQuery->fetchAll();
    }
}