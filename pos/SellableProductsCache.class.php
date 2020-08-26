<?php


/**
 * Кеш на продаваемите артикули
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_SellableProductsCache extends core_Master
{
    
    /**
     * Заглавие
     */
    public $title = 'Кеш на продаваеми артикули в POS-а';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'pos_Wrapper,plg_Search';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да синхронизира?
     */
    public $canSync = 'debug';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $this->FLD('string', 'varchar', 'caption=Код');
        $this->FLD('searchKeywords', 'text', 'caption=Ключови думи');
        $this->FLD('priceListId', 'key(mvc=cat_Products,select=name)', 'caption=Ценова политика');
        
        $this->setDbIndex('priceListId');
        $this->setDbIndex('productId');
        $this->setDbIndex('string');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->priceListId = price_Lists::getHyperLink($rec->priceListId, true);
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('sync')) {
            $data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', 'ret_url' => true), null, 'warning=Наистина ли искате да ресинхронизирате свойствата,ef_icon = img/16/arrow_refresh.png,title=Ресинхронизиране на свойствата на перата');
        }
    }
    
    
    /**
     * Екшън за ръчно обновяване на кешираните артикули
     */
    function act_sync()
    {
        $this->requireRightFor('sync');
        $this->cron_CacheSellablePosProducts();
        
        followRetUrl();
    }
    
    
    /**
     * Крон процес обновяващ продаваемите в ПОС-а артикули
     */
    public function cron_CacheSellablePosProducts()
    {
        if(!pos_Points::count()) return;
        
        $priceLists = array();
        $pointQuery = pos_Points::getQuery();
        while($pointRec = $pointQuery->fetch()){
            $policyId = pos_Points::getSettings($pointRec, 'policyId');
            $priceLists[$policyId] = $policyId;
        }
        
        $toSave = array();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#state = 'active' AND #isPublic = 'yes'");
        $pQuery->show('name,nameEn,code,measureId,searchKeywords');
        
        $count = $pQuery->count();
        core_App::setTimeLimit($count * 0.5, false, 100);
        
        while($pRec = $pQuery->fetch()){
            $newRec = (object)array('productId' => $pRec->id, 'searchKeywords' => $pRec->searchKeywords);
            $newRec->string = plg_Search::normalizeText($pRec->name) . " " . plg_Search::normalizeText($pRec->code);
            
            foreach ($priceLists as $listId){
                if(price_ListRules::getPrice($listId, $pRec->id)){
                    $newRecClone = clone $newRec;
                    $newRecClone->priceListId = $listId;
                    $toSave[] = $newRecClone;
                }
            }
        }
        
        // Синхронизиране на таблицата
        $exRecs = self::getQuery()->fetchAll();
        $res = arr::syncArrays($toSave, $exRecs, 'productId,priceListId', 'productId,string,searchKeywords,priceListId');
        
        if(countR($res['insert'])){
            $this->saveArray($res['insert']);
        }
        
        if(countR($res['update'])){
            $this->saveArray($res['update'], 'id,string,searchKeywords');
        }
        
        if(countR($res['delete'])){
            foreach ($res['delete'] as $id){
                $this->delete($id);
            }
        }
    }
    
    
    /**
     * Връща статистическа информация за пос продажбите
     *
     * @return array $res
     */
    public static function getPosStatisticData()
    {
        $time = sales_Setup::get('STATISTIC_DATA_FOR_THE_LAST');
        $valiorFrom = dt::verbal2mysql(dt::addSecs(-1 * $time), false);
        
        // За всяка бележка, намират се най-продаваните 100 артикула
        $receiptQuery = pos_ReceiptDetails::getQuery();
        $receiptQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
        $receiptQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $receiptQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $receiptQuery->EXT('pointId', 'pos_Receipts', 'externalName=pointId,externalKey=receiptId');
        $receiptQuery->EXT('valior', 'pos_Receipts', 'externalName=valior,externalKey=receiptId');
        $receiptQuery->where("#state != 'draft' && #state != 'rejected' AND #isPublic = 'yes' AND #valior >= '{$valiorFrom}'");
        $receiptQuery->show('productId,quantity,amount,canStore,pointId');
        
        $count = $receiptQuery->count();
        core_App::setTimeLimit($count * 0.4, false, 200);
        
        $res = array();
        while ($receiptRec = $receiptQuery->fetch()){
            $storeId = pos_Points::fetchField($receiptRec->pointId, 'storeId');
            $key = "{$storeId}|{$receiptRec->productId}";
            if(!array_key_exists($key, $res)){
                $res[$key] = (object)array('productId' => $receiptRec->productId, 'storeId' => $storeId, 'sumQuantity' => 0, 'sumAmount' => 0, 'count' => 0);
                
            }
            $res[$key]->count++;
            $res[$key]->sumQuantity += $receiptRec->quantity;
            $res[$key]->sumAmount += $receiptRec->amount;
        }
        
        arr::sortObjects($res, 'count', 'desc');
       
        return $res;
    }
}