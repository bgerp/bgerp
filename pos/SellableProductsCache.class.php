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
        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Ценова политика');
        
        $this->setDbIndex('productId,priceListId');
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
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'search,priceListId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        if($filter = $data->listFilter->rec){
            if(isset($filter->priceListId)){
                $data->query->where("#priceListId = {$filter->priceListId}");
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('sync')) {
            $data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', 'ret_url' => true), null, 'warning=Наистина ли искате да синхронизирате продаваемите артикули в POS-а,ef_icon = img/16/arrow_refresh.png,title=Ресинхронизиране на свойствата на перата');
        }
    }


    /**
     * Синхронизиране на продаваемите пос артикули
     *
     * @param bool $force - дали да се форсира преизчисляването
     * @return string
     */
    public function sync($force = false)
    {
        if(!pos_Points::count()) return "Няма налични точки на продажба";

        // Ако има промяна в ценовите политики - ще се синхронизира (освен ако не се форсира синхронизирането)
        $datetime = dt::now();
        if(!$force){
            if(!price_Lists::areListUpdated($datetime)) return "Няма промяна в ценовите политики";
        }

        // Кои ценови политики участват в ПОС-а
        $priceLists = array();
        $pointQuery = pos_Points::getQuery();
        while($pointRec = $pointQuery->fetch()){
            $policyId = pos_Points::getSettings($pointRec, 'policyId');
            $priceLists[$policyId] = $policyId;
        }

        // Извличане на всички активни стандартни артикули
        $toSave = array();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#state = 'active' AND #isPublic = 'yes'");
        $pQuery->show('name,nameEn,code,measureId,searchKeywords');

        $count = $pQuery->count();
        core_App::setTimeLimit($count * 0.5, false, 100);

        // Ако имат цена ще се извлекат данните им за търсене
        while($pRec = $pQuery->fetch()){
            foreach ($priceLists as $listId){
                if(price_ListRules::getPrice($listId, $pRec->id, null, $datetime)){
                    $newRec = (object)array('productId' => $pRec->id, 'searchKeywords' => $pRec->searchKeywords);
                    $newRec->string = " " . plg_Search::normalizeText($pRec->name) . " " . plg_Search::normalizeText($pRec->code);
                    $newRec->priceListId = $listId;
                    $toSave[] = $newRec;
                }
            }
        }

        // Синхронизиране на таблицата
        $exRecs = self::getQuery()->fetchAll();

        $res = arr::syncArrays($toSave, $exRecs, 'productId,priceListId', 'productId,string,searchKeywords,priceListId');

        $iCount = countR($res['insert']);
        $uCount = countR($res['update']);
        $dCount = countR($res['delete']);

        if($iCount){
            $this->saveArray($res['insert']);
        }

        if($uCount){
            $this->saveArray($res['update'], 'id,string,searchKeywords');
        }

        if($dCount){
            foreach ($res['delete'] as $id){
                $this->delete($id);
            }
        }

        return "Резултат|*: Добавени:{$iCount}, Обновени:{$uCount}, Изтрити:{$dCount}";
    }


    /**
     * Крон процес обновяващ продаваемите в ПОС-а артикули
     */
    public function cron_CacheSellablePosProducts()
    {
        $res = $this->sync();
        $this->logDebug($res);
    }


    /**
     * Екшън за ръчно обновяване на кешираните артикули
     */
    function act_sync()
    {
        $this->requireRightFor('sync');
        $res = $this->sync(true);

        followRetUrl(null, $res);
    }
}