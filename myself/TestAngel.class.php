<?php

/**
 * Клас  'myself_TestAngel' - Разни тестове на PHP-to
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     ТестовеАнгел » Тест
 */
class myself_TestAngel extends core_Manager
{


    public function act_Test()
    {
       // bp(self::addGenericQuantities());
       
      //bp(store_Products::sync($all));
      
        //$query = sales_Sales::getQuery();
       // $query = purchase_Purchases::getQuery();
        $query = acc_CostAllocations::getQuery();
        while ($pRec = $query->fetch()){
            $arr[]=$pRec;
            
            $bbb = acc_Items::getQuery();
            
            bp(acc_Items::getItemRec(5196),$bbb->fetch(5196),core_Classes::fetch(67));
            
          //  bp(sales_Sales::fetch(1307),purchase_Purchases::fetch(322),purchase_PurchasesDetails::fetch(542),
           //     acc_CostAllocations::getAllocatedInDocument($pRec->detailClassId, $pRec->detailRecId));
           
           
            
            $aaa = core_Classes::fetch($pRec->detailClassId)->name;
            
            
        //  bp($pRec,core_Classes::fetch($pRec->detailClassId),cat_Products::fetch($pRec->productId),$aaa::fetch($pRec->detailRecId),doc_Containers::fetch($pRec->containerId));
        }
      
        //bp($arr);
    }


    public function addGenericQuantities()
    {
        $query = store_Products::getQuery();
        
        $query->where("#state != 'rejected'");
        
        $queryS = planning_ObjectResources::getQuery();
        
        while ($prRec = $query->fetch()) {
            
//             if (!is_null($prRec->reservedQuantity)) {
//                 $availableQuantity = $prRec->quantity - $prRec->reservedQuantity;
//             } else {
//                 $availableQuantity = $prRec->quantity;
//             }
            
            $key = keylist::fromArray(array(
                $prRec->storeId => $prRec->storeId,
                $prRec->productId => $prRec->productId
            ));
            
            $all[$key] = $prRec->quantity;
        }
        
        while ($generics = $queryS->fetch()) {
            
            $genericProducts[$generics->objectId] = $generics->likeProductId;
        }
        
        foreach ($all as $key => $val) {
            
            $quantity = $available = 0;
            
            list ($storeId, $productId) = explode('|', trim($key, '|'));
            
            $genericProductId = $genericProducts[$productId];
            
            if (in_array($productId, array_keys($genericProducts))) {
                
                $quantity = store_Products::getQuantity($genericProductId, $storeId, true);
                
                $all[$key] += $quantity;
            }
        }
        
        return $all;
    }


    public function addGenericAvailableQuantities()
    {
        $query = store_Products::getQuery();
        
        $query->where("#state != 'rejected'");
        
        $queryS = planning_ObjectResources::getQuery();
        
        while ($prRec = $query->fetch()) {
            
            if (!is_null($prRec->reservedQuantity)) {
                $availableQuantity = $prRec->quantity - $prRec->reservedQuantity;
            } else {
                $availableQuantity = $prRec->quantity;
            }
            
            $key = keylist::fromArray(array(
                $prRec->storeId => $prRec->storeId,
                $prRec->productId => $prRec->productId
            ));
            
            $all[$key] = $availableQuantity;
        }
        
        while ($generics = $queryS->fetch()) {
            
            $genericProducts[$generics->objectId] = $generics->likeProductId;
        }
        
        foreach ($all as $key => $val) {
            
            $quantity = $available = 0;
            
            list ($storeId, $productId) = explode('|', trim($key, '|'));
            
            $genericProductId = $genericProducts[$productId];
            
            if (in_array($productId, array_keys($genericProducts))) {
                $available = store_Products::getQuantity($genericProductId, $storeId, false);
                
                $all[$key] += $available;
            }
        }
        
        return $all;
    }
}
