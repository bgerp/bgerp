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
    public $loadList = 'plg_RowTools, price_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id=Пулт, productId, classId, price, accPrice, quantity, sourceId=Документ, updatedOn';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,input=none');
        $this->FLD('classId', 'class(interface=price_CostPolicyIntf,select=title)', 'caption=Алгоритъм,input=none');
        $this->FLD('price', 'double', 'caption=Ед. цена,mandatory');
        $this->FLD('accPrice', 'double', 'caption=Ед. сч. цена,input=none');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none');
        $this->FLD('sourceClassId', 'class', 'caption=Документ->Клас,input=none');
        $this->FLD('sourceId', 'varchar', 'caption=Документ->Ид,input=none');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Обновено на,input=none');
        
        $this->setDbUnique('productId,classId');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
        
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'edit' && isset($rec)){
            $Source = cls::get($rec->classId);
            if(!$Source->canEditPrice($rec->productId, $userId)){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->price = price_Lists::roundPrice(price_ListRules::PRICE_LIST_COST, $rec->price, true);
        $row->ROW_ATTR = array('class' => 'state-active');
        
        if(!empty($rec->sourceId)){
            $Source = cls::get($rec->sourceClassId);
            if(cls::haveInterface('doc_DocumentIntf', $Source)){
                $row->sourceId = cls::get($rec->sourceClassId)->getLink($rec->sourceId, 0);
            } elseif($Source instanceof core_Master){
                $row->sourceId = cls::get($rec->sourceClassId)->getHyperlink($rec->sourceId, true);
            } else {
                $row->sourceId = cls::get($rec->sourceClassId)->getRecTitle($rec->sourceId);
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
        $datetime = dt::addSecs(-1 * 60 * 60);
        self::saveCalcedCosts($datetime);
        
        return followRetUrl(null, "Преизчислени са данните за последния час");
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
     * Кои стандартни артикули са засегнати  след посочената дата
     * 
     * @param datetime $beforeDate
     * 
     * @return array $res
     */
    public static function getAffectedProducts($beforeDate)
    {
        // Кои пера са участвали в дебитирането на склада в последните 3 месеца
        $res = array();
        
        $pQuery = purchase_PurchasesDetails::getQuery();
        $pQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $pQuery->EXT('activatedOn', 'purchase_Purchases', 'externalName=activatedOn,externalKey=requestId');
        $pQuery->EXT('documentModifiedOn', 'purchase_Purchases', 'externalName=modifiedOn,externalKey=requestId');
        $pQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
        $pQuery->where("((#state = 'active' || #state = 'closed') AND #activatedOn >= '{$beforeDate}') OR (#state = 'rejected' AND #activatedOn IS NOT NULL AND #documentModifiedOn >= '{$beforeDate}')");
        $pQuery->where("#canStore = 'yes' AND #isPublic = 'yes'");
        $pQuery->show('productId');
        $res += arr::extractValuesFromArray($pQuery->fetchAll(), 'productId');
        
        $bQuery = cat_BomDetails::getQuery();
        $bQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=resourceId');
        $bQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=resourceId');
        $bQuery->EXT('activatedOn', 'purchase_Purchases', 'externalName=activatedOn,externalKey=bomId');
        $bQuery->EXT('documentModifiedOn', 'purchase_Purchases', 'externalName=modifiedOn,externalKey=bomId');
        $bQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=bomId');
        $bQuery->where("((#state = 'active' || #state = 'closed') AND #activatedOn >= '{$beforeDate}') OR (#state = 'rejected' AND #activatedOn IS NOT NULL AND #documentModifiedOn >= '{$beforeDate}')");
        $bQuery->show('resourceId');
        $bQuery->where("#canStore = 'yes' AND #isPublic = 'yes'");
        $res += arr::extractValuesFromArray($bQuery->fetchAll(), 'resourceId');
        
        $jQuery = acc_JournalDetails::getQuery();
        $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $jQuery->EXT('journalCreatedOn', 'acc_Journal', 'externalName=createdOn,externalKey=journalId');
        $storeAccId = acc_Accounts::getRecBySystemId('321')->id;
        $jQuery->where("#debitAccId = {$storeAccId} AND #journalCreatedOn >= '{$beforeDate}'");
        $jQuery->show('debitItem2');
        $jQuery->groupBy('debitItem2');
        $itemsWithMovement = arr::extractValuesFromArray($jQuery->fetchAll(), 'debitItem2');
        
        // Кои ид-та на артикули, съответстват на тези пера
        if(countR($itemsWithMovement)){
            
            $iQuery = acc_Items::getQuery();
            $iQuery->EXT('productState', 'cat_Products', 'externalName=state,externalKey=objectId');
            $iQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=objectId');
            $iQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=objectId');
            $iQuery->EXT('canSell', 'cat_Products', 'externalName=canStore,externalKey=objectId');
            $iQuery->EXT('canBuy', 'cat_Products', 'externalName=canBuy,externalKey=objectId');
            $iQuery->EXT('canManifacture', 'cat_Products', 'externalName=canManifacture,externalKey=objectId');
            $iQuery->where("#state = 'active' AND #classId= " . cat_Products::getClassId());
            $iQuery->where("#isPublic = 'yes' AND #canStore = 'yes' AND #productState = 'active' AND (#canBuy = 'yes' OR #canManifacture = 'yes' OR #canSell = 'yes')");
            $iQuery->in("id", $itemsWithMovement);
            $iQuery->show('id,objectId');
            $iQuery->notIn('objectId', $res);
            $res += arr::extractValuesFromArray($iQuery->fetchAll(), 'objectId');
        }
        
        return $res;
    }
    
    
    /**
     * Кои са засегнатите политики
     * 
     * @param array $affectedProducts
     * 
     * @return array $res
     */
    private static function getAffectedPolicies($affectedProducts)
    {
        $res = array();
      
        if(countR($affectedProducts)){
            $categoryClassId = cat_Categories::getClassId();
            $pQuery = cat_Products::getQuery();
            $pQuery->EXT('folderClassId', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
            $pQuery->EXT('folderCoverId', 'doc_Folders', 'externalName=coverId,externalKey=folderId');
            $pQuery->where("#folderClassId = {$categoryClassId}");
            $pQuery->show('folderCoverId');
            $pQuery->in('id', $affectedProducts);
            $categoryIds = arr::extractValuesFromArray($pQuery->fetchAll(), 'folderCoverId');
            
            $uQuery = price_Updates::getQuery();
            $affectedProductImploded = implode(',', $affectedProducts);
            $uQuery->where("#type = 'product' AND #objectId IN ({$affectedProductImploded})");
            
            $categoryImploded = implode(',', $categoryIds);
            $uQuery->orWhere("#type = 'category' AND #objectId IN ({$categoryImploded})");
            $uQuery->show('sourceClass1,sourceClass2,sourceClass3');
            
            $uRecs = $uQuery->fetchAll();
           
            array_walk($uRecs, function ($a) use (&$res) {
                foreach (array('sourceClass1', 'sourceClass2', 'sourceClass3') as $fld){
                    if(!empty($a->{$fld})){
                        $res[$a->{$fld}] = $a->{$fld};
                    }
                }
            });
        }
        
        return $res;
    }
    
    
    /**
     * Обновяване на себестойностите по разписание
     */
    public static function saveCalcedCosts($datetime)
    {   
        $self = cls::get(get_called_class());
       
        // Кои са засегнатите артикули
        core_Debug::startTimer('calcAffected');
        $affectedProducts = self::getAffectedProducts($datetime);
        core_Debug::stopTimer('calcAffected');
       
        $timer = round(core_Debug::$timers['calcAffected']->workingTime, 2);
        $count = countR($affectedProducts);
        log_System::logDebug("CALC AFFECTED[{$count}] = {$timer} FOR '{$datetime}'");
        
        // Кои са засегнатите политики
        $policiesArr = static::getAffectedPolicies($affectedProducts);
        if(!countR($affectedProducts) || !countR($policiesArr)){
            
            return;
        }
        
        core_App::setTimeLimit($count * 0.6, 60);
        core_Debug::startTimer('calcCosts');
       
        // Изчисляване на всяка от засегнатите политики, себестойностите на засегнатите пера
        $update = array();
        foreach ($policiesArr as $policyId){
            if(cls::load($policyId, true)){
                $Policy = cls::get($policyId);
                $calced = $Policy->calcCosts($affectedProducts);
                $update = array_merge($update, $calced);
            }
        }
       
        core_Debug::stopTimer('calcCosts');
        $timer = round(core_Debug::$timers['calcCosts']->workingTime, 2);
        log_System::logDebug("CALC COSTS COUNT[{$count}] - calcTime = {$timer}");
        
        $now = dt::now();
        array_walk($update, function (&$a) use($now){$a->updatedOn = $now;});
        
        // Синхронизиране на новите записи със старите записи на засегнатите пера
        $exQuery = self::getQuery();
        $exQuery->in('productId', $affectedProducts);
        $exRecs = $exQuery->fetchAll();
        $res = arr::syncArrays($update, $exRecs, 'productId,classId', 'price,quantity,sourceClassId,sourceId,accPrice');
        if(countR($res['insert'])){
            $self->saveArray($res['insert']);
        }
        
        if(countR($res['update'])){
            $self->saveArray($res['update'], 'id,price,quantity,sourceClassId,sourceId,updatedOn,accPrice');
        }
        
        // Изтриване на несрещнатите себестойностти
        if (countR($res['delete'])) {
            $averageStoreClassId = price_interface_AverageCostPricePolicyImpl::getClassId();
            
            $query = self::getQuery();
            $query->in('id', $res['delete']);
            $query->show('classId');
            $arr = $query->fetchAll();
            
            foreach ($res['delete'] as $id) {
                if($arr[$id]->classId == $averageStoreClassId) continue;
                $self->delete($id);
            }
        }
    }
    
    
    /**
     * Намира себестойността на артикула по вида
     *
     * @param int    $productId - ид на артикула
     * @param mixed $source     - източник
     *
     * @return float $price     - намерената себестойност
     */
    public static function getPrice($productId, $source)
    {
        expect($productId);
        $Source = cls::get($source);
        $price = static::fetchField("#productId = {$productId} AND #classId = '{$Source->getClassId()}'", 'price');
        
        return $price;
    }
}
