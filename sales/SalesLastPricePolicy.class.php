<?php



/**
* Имплементация на ценова политика "По последна продажна цена"
* Връща последната цена на която е продаден даден артикул
* на този клиент (от последната контирана продажба в папката на
* клиента)
*
* @category  bgerp
* @package   sales
* @author    Ivelin Dimov <ivelin_pdimov@abv.com>
* @copyright 2006 - 2013 Experta OOD
* @license   GPL 3
* @since     v 0.1
* @title     Политика "По последна продажна цена"
*/
class sales_SalesLastPricePolicy extends core_Manager
{
    /**
     * Заглавие
     */
    var $title = 'Последна цена';


    /**
     * Интерфейс за ценова политика
     */
    var $interfaces = 'price_PolicyIntf';
    
    
    /**
     * Връща последната цена за посочения продукт направена в
     * продажба към контрагента
     * 
     * @return object $rec->price  - цена
     * 				  $rec->discount - отстъпка
     * 				  $rec->priority - приоритет на цената
     */
    function getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId = NULL, $quantity = NULL, $date = NULL, $rate = 1, $chargeVat = 'no')
    {
       if(!$date){
       	   $date = dt::now();
        }
        
        // Намира последната цена на която продукта е бил 
        // продаден на този контрагент
        $detailQuery = sales_SalesDetails::getQuery();
        $detailQuery->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');
        $detailQuery->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');
        $detailQuery->EXT('currencyId', 'sales_Sales', 'externalName=currencyId,externalKey=saleId');
        $detailQuery->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
        $detailQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
        $detailQuery->where("#contragentClassId = {$customerClass}");
        $detailQuery->where("#contragentId = {$customerId}");
        $detailQuery->where("#valior <= '{$date}'");
        $detailQuery->where("#productId = '{$productId}'");
        $detailQuery->where("#classId = {$productManId}");
        $detailQuery->where("#state = 'active' || #state = 'closed'");
        $detailQuery->orderBy('#valior,#id', 'DESC');
        $lastRec = $detailQuery->fetch();
        
        if(!$lastRec){
        	
        	return NULL;
        }
        
        $vat = cls::get($lastRec->classId)->getVat($lastRec->productId);
        $lastRec->packPrice = deals_Helper::getDisplayPrice($lastRec->packPrice, $vat, $rate, $chargeVat);
        
        return (object)array('price' => deals_Helper::roundPrice($lastRec->packPrice), 'discount' => $lastRec->discount);
    }
}