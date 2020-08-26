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
    public $listFields = 'id,productId,key,count,quantity,amount';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Артикул,silent,class=ajaxSelect');
        $this->FLD('key', 'varchar', 'mandatory,caption=Ключ,silent');
        $this->FLD('count', 'int', 'mandatory,caption=Брой');
        $this->FLD('quantity', 'double', 'mandatory,caption=Количество');
        $this->FLD('amount', 'double', 'mandatory,caption=Сума (без ДДС)');
        
        $this->setDbIndex('key');
        $this->setDbUnique('key,productId');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $time = sales_Setup::get('STATISTIC_DATA_FOR_THE_LAST');
        $data->title = 'Статистически данни на продажбите за последните|* <b class="green">' . core_Type::getByName('time')->toVerbal($time) . "</b>";
        
        $data->listFilter->showFields = 'productId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('productId');
        
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
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Обновяване', array($mvc, 'GatherSalesData', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Обновяване на статистическите данни');
        }
    }
    
    
    /**
     * Екшън за обновяване на статистическите данн
     */
    function act_GatherSalesData()
    {
        requireRole('debug');
        $this->cron_GatherSalesData();
        core_Statuses::newStatus('Данните са опреснени');
        
        followRetUrl();
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
            $newRecs["sales|{$saleRec->productId}|{$saleRec->storeId}"] = (object)array("key" => "sales|{$saleRec->storeId}", 'productId' => $saleRec->productId, 'count' => $saleRec->count, 'quantity' => $saleRec->sumQuantity, 'amount' => $saleRec->sumAmount);
        }
        
        if(core_Packs::isInstalled('pos')){
            $posRecs = array();
            $posDataRecs = pos_SellableProductsCache::getPosStatisticData();
            foreach($posDataRecs as $posRec){
                $posRecs["pos|{$posRec->productId}|{$posRec->storeId}"] = (object)array("key" => "pos|{$posRec->storeId}", 'productId' => $posRec->productId, 'count' => $posRec->count, 'quantity' => $posRec->sumQuantity, 'amount' => $posRec->sumAmount);
            }
            
            $newRecs += $posRecs;
        }
        
        if(core_Packs::isInstalled('eshop')){
            $saleEshopRecs = self::getSaleStatistic(true);
            $eshopRecs = array();
            foreach ($saleEshopRecs as $saleEshopRec){
                $eshopRecs["eshop|{$saleEshopRec->productId}|{$saleEshopRec->storeId}"] = (object)array("key" => "eshop|{$saleEshopRec->storeId}", 'productId' => $saleEshopRec->productId, 'count' => $saleEshopRec->count, 'quantity' => $saleEshopRec->sumQuantity, 'amount' => $saleEshopRec->sumAmount);
            }
            $newRecs += $eshopRecs;
        }
        
        $res = arr::syncArrays($newRecs, $exRecs, 'key,productId', 'count,quantity,amount');
        $this->saveArray($res['insert']);
        $this->saveArray($res['update'], 'id,count,quantity,amount');
        
        if(countR($res['delete'])){
            foreach ($res['delete'] as $deleteId){
                self::delete($deleteId);
            }
        }
    }
    
    
    /**
     * Връща статистическа информация за продажбите
     * 
     * @return array
     */
    public function getSaleStatistic($onlyOnlineSales = false)
    {
        $time = sales_Setup::get('STATISTIC_DATA_FOR_THE_LAST');
        $valiorFrom = dt::verbal2mysql(dt::addSecs(-1 * $time), false);
        
        $deltaQuery = sales_PrimeCostByDocument::getQuery();
        $deltaQuery->XPR('count', 'int', 'count(#id)');
        $deltaQuery->XPR('sumQuantity', 'int', 'SUM(#quantity)');
        $deltaQuery->XPR('sumAmount', 'int', 'SUM(#quantity * #sellCost)');
        $deltaQuery->where("#sellCost IS NOT NULL AND (#state = 'active' OR #state = 'closed') AND #isPublic = 'yes'");
        $deltaQuery->groupBy('productId,storeId');
        $deltaQuery->orderBy("count", 'DESC');
        $deltaQuery->where("#valior >= '{$valiorFrom}'");
        $deltaQuery->show('productId,storeId,sumQuantity,sumAmount,count');
        
        $count = $deltaQuery->count();
        core_App::setTimeLimit($count * 0.4, false, 200);
        
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