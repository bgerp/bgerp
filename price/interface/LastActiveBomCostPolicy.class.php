<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Последна рецепта (без режийни)"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Последна рецепта (без режийни)"
 *
 */
class price_interface_LastActiveBomCostPolicy extends price_interface_BaseCostPolicy
{
    
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'price_CostPolicyIntf';


    /**
     * Калкулиран кеш
     */
    private static $calcCache = array();


    /**
     * Как се казва политиката
     *
     * @param bool $verbal - вербалното име или системното
     *
     * @return string
     */
    public function getName($verbal = false)
    {
        $res = ($verbal) ? tr('Последна рецепта (без режийни)') : 'lastBomPolicy';
        
        return $res;
    }


    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts - засегнати артикули
     * @param array $params - параметри
     *
     * @return array
     *         ['classId']       - клас ид на политиката
     *         ['productId']     - ид на артикул
     *         ['quantity']      - количество
     *         ['price']         - ед. цена
     *         ['valior']        - вальор
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts, $params = array())
    {
        $res = array();
        
        if(!countR($affectedTargetedProducts)) return $res;
        
        $now = dt::now();
        $classId = cat_Boms::getClassId();

        // За всеки артикул
        foreach ($affectedTargetedProducts as $productId) {
            
            // Търсим му рецептата
            if ($bomRec = cat_Products::getLastActiveBom($productId)) {

                // Ако има, намираме и цената
                $t = ($bomRec->quantityForPrice) ? $bomRec->quantityForPrice : $bomRec->quantity;

                if (!isset(self::$calcCache[$bomRec->id])) {
                    try{
                        self::$calcCache[$bomRec->id] = cat_Boms::getBomPrice($bomRec, $t, 0, 0, $now, price_ListRules::PRICE_LIST_COST);
                    } catch(core_exception_Expect $e){
                        reportException($e);
                        continue;
                    }
                }
                
                $primeCost = self::$calcCache[$bomRec->id];
                if ($primeCost) {

                    // Добавяне и на режийните разходи ако се искат
                    if(isset($params['addExpenses'])){
                        $expenses = $bomRec->expenses;
                        if(!isset($expenses)){
                            if($defaultOverheadCostArr = cat_Products::getDefaultOverheadCost($productId)){
                                $expenses = $defaultOverheadCostArr['overheadCost'];
                            }
                        }

                        if(isset($expenses)){
                            $primeCost *= 1 + $expenses;
                            $primeCost = core_Math::roundNumber($primeCost);
                        }
                    }

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

        // Връщане на намрените цени
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
        $now = dt::now();
        foreach (array('price_interface_LastActiveBomCostPolicy', 'price_interface_LastActiveBomCostWithExpenses') as $className){
            $Class = cls::get($className);
            if(!cls::load($Class, true)) continue;

            $classId = $Class->getClassId();
            $updateProductIds = $updateCategoryFolderIds = $updateGroupIds = array();

            // Кои категории/артикули/артикули групи имат правила за обновяване по Последна рецепта (без режийни) / или Последна рецепта (+разходи)
            $uQuery = price_Updates::getQuery();
            $uQuery->where("#sourceClass1 = {$classId} OR #sourceClass2 = {$classId} OR #sourceClass3 = {$classId}");

            while($uRec = $uQuery->fetch()){
                if($uRec->type == 'product'){
                    $updateProductIds[$uRec->objectId] = $uRec->objectId;
                } elseif($uRec->type == 'group'){
                    $updateGroupIds[$uRec->objectId] = $uRec->objectId;
                } else {
                    $folderId = cat_Categories::fetchField($uRec->objectId, 'folderId');
                    $updateCategoryFolderIds[$folderId] = $folderId;
                }
            }

            // Ако няма нито едно правило за обновяване - нищо не се прави
            if(!countR($updateProductIds) && !countR($updateCategoryFolderIds) && !countR($updateGroupIds)) continue;

            // И имат активна рецепта
            $bQuery = cat_Boms::getQuery();
            $bQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
            $bQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
            $bQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $bQuery->EXT('pFolderId', 'cat_Products', 'externalName=folderId,externalKey=productId');
            $bQuery->where("#state = 'active' AND #isPublic = 'yes' AND #canStore = 'yes'");
            $bQuery->show('productId');

            // Рецептите към артикулите с конкретни правила
            if(countR($updateProductIds)){
                $bQuery->in('productId', $updateProductIds);
            }

            // Рецептите, чиито артикули са в категория с правило
            if(countR($updateCategoryFolderIds)){
                $bQuery->in('pFolderId', $updateCategoryFolderIds, false, true);
            }

            // Рецептите, чиито артикулни групи са в категория с правило
            if(countR($updateGroupIds)){
                plg_ExpandInput::applyExtendedInputSearch('cat_Products', $bQuery, $updateGroupIds, 'productId');
            }

            $affectedProducts = arr::extractValuesFromArray($bQuery->fetchAll(), 'productId');
            $count = countR($affectedProducts);
            if(!$count) continue;

            // Изчисляване на цените по последни рецепти
            core_App::setTimeLimit($count * 0.8,false, 600);

            // Извличане на засегнатите артикули
            $Interface = cls::getInterface('price_CostPolicyIntf', $Class);
            $calced = $Interface->calcCosts($affectedProducts);

            // Промяна на датата на последно изчисление
            array_walk($calced, function (&$a) use ($now) {
                $a->updatedOn = $now;
            });

            // Синхронизиране на новите записи със старите записи на засегнатите пера
            $ProductCache = cls::get('price_ProductCosts');

            // Синхронизират се само с вече наличните цени по последни рецепти
            $exQuery = $ProductCache->getQuery();
            $exQuery->in('productId', $affectedProducts);
            $exQuery->where("#classId = {$Class->getClassId()}");
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
}