<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Последна рецепта"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Последна рецепта"
 *
 */
class price_interface_LastActiveBomCostPolicy extends price_interface_BaseCostPolicy
{
    
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'price_CostPolicyIntf';
    
    
    /**
     * Как се казва политиката
     *
     * @param bool $verbal - вербалното име или системното
     *
     * @return string
     */
    public function getName($verbal = false)
    {
        $res = ($verbal) ? tr('Последна рецепта') : 'lastBomPolicy';
        
        return $res;
    }
    
    
    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts
     *
     * @return $res
     *         ['classId']       - клас ид на политиката
     *         ['productId']     - ид на артикул
     *         ['quantity']      - количество
     *         ['price']         - ед. цена
     *         ['valior']        - вальор
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts)
    {
        $res = array();
        
        if(!countR($affectedTargetedProducts)){
            
            return $res;
        }
        
        $cache = array();
        $now = dt::now();
        $classId = cat_Boms::getClassId();
        
        // За всеки артикул
        foreach ($affectedTargetedProducts as $productId) {
            
            // Търсим му рецептата
            if ($bomRec = cat_Products::getLastActiveBom($productId)) {
                if (!isset($cache[$bomRec->id])) {
                    
                    // Ако има, намираме и цената
                    $t = ($bomRec->quantityForPrice) ? $bomRec->quantityForPrice : $bomRec->quantity;
                    $cache[$bomRec->id] = cat_Boms::getBomPrice($bomRec, $t, 0, 0, $now, price_ListRules::PRICE_LIST_COST);
                }
                
                $primeCost = $cache[$bomRec->id];
                if ($primeCost) {
                    $res[$productId] = (object) array('sourceClassId' => $classId,
                                                      'sourceId'      => $bomRec->id,
                                                      'productId'     => $productId,
                                                      'quantity'      => $t,
                                                      'valior'        => null,
                                                      'classId'       => $this->getClassId(),
                                                      'price'         => $primeCost);
                }
            }
        }
        
        // Връщаме намрените цени
        return $res;
    }
    
    
    /**
     * Дали има самостоятелен крон процес за изчисление
     *
     * @return boolean
     */
    public function hasSeparateCalcProcess()
    {
        return true;
    }
    
    
    /**
     * Изчисляване на всички последни рецепти на артикулите
     */
    public function cron_updateCachedBoms()
    {
        $updateProductIds = $updateCategoryFolderIds = array();
        
        // Кои категории и артикули имат правила за обновяване по последна рецепта
        $classId = $this->getClassId();
        $uQuery = price_Updates::getQuery();
        $uQuery->where("#sourceClass1 = {$classId} OR #sourceClass2 = {$classId} OR #sourceClass3 = {$classId}");
        
        while($uRec = $uQuery->fetch()){
            if($uRec->type == 'product'){
                $updateProductIds[$uRec->objectId] = $uRec->objectId;
            } else {
                $folderId = cat_Categories::fetchField($uRec->objectId, 'folderId');
                $updateCategoryFolderIds[$folderId] = $folderId;
            }
        }
        
        if(!countR($updateProductIds) && !countR($updateCategoryFolderIds)){
            
            return;
        }
        
        // И имат активна рецепта
        $bQuery = cat_Boms::getQuery();
        $bQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $bQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $bQuery->EXT('pFolderId', 'cat_Products', 'externalName=folderId,externalKey=productId');
        $bQuery->where("#state = 'active' AND #isPublic = 'yes' AND #canStore = 'yes'");
        
        if(countR($updateProductIds)){
            $bQuery->in('productId', $updateProductIds);
        }
        
        if(countR($updateCategoryFolderIds)){
            $bQuery->in('pFolderId', $updateCategoryFolderIds, false, true);
        }
        
        $bQuery->show('productId');
        
        $affectedProducts = arr::extractValuesFromArray($bQuery->fetchAll(), 'productId');
        $count = countR($affectedProducts);
        if(!$count){
            
            return;
        }
        
        // Изчисляване на цените по последни рецепти
        core_App::setTimeLimit($count * 0.8, 600);
        $Interface = cls::getInterface('price_CostPolicyIntf', $this);
        $calced = $Interface->calcCosts($affectedProducts);
        
        $now = dt::now();
        array_walk($calced, function (&$a) use ($now) {
            $a->updatedOn = $now;
        });
        
        // Синхронизиране на новите записи със старите записи на засегнатите пера
        $ProductCache = cls::get('price_ProductCosts');
        
        // Синхронизират се само с вече наличните цени по последни рецепти
        $exQuery = $ProductCache->getQuery();
        $exQuery->in('productId', $affectedProducts);
        $exQuery->where("#classId = {$classId}");
        $exRecs = $exQuery->fetchAll();
        $res = arr::syncArrays($calced, $exRecs, 'productId,classId', 'price,quantity,sourceClassId,sourceId,valior');
        if (countR($res['insert'])) {
            $ProductCache->saveArray($res['insert']);
        }
        
        if (countR($res['update'])) {
            $ProductCache->saveArray($res['update'], 'id,price,quantity,sourceClassId,sourceId,updatedOn,valior');
        }
        
        // Изтриване на несрещнатите себестойностти
        if (countR($res['delete'])) {
            foreach ($res['delete'] as $id) {
                $ProductCache->delete($id);
            }
        }
    }
}