<?php



/**
* Имплементация на ценова политика "По последна покупна цена"
* Връща последната цена на която е купен даден артикул
* от този клиент (от последната контирана покупка в папката на
* клиента)
*
* @category  bgerp
* @package   purchase
* @author    Ivelin Dimov <ivelin_pdimov@abv.com>
* @copyright 2006 - 2013 Experta OOD
* @license   GPL 3
* @since     v 0.1
* @title     Политика "По последна покупна цена"
*/
class purchase_PurchaseLastPricePolicy extends core_Mvc
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Последна покупна цена';


    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_PolicyIntf';
    
    
    /**
     * Връща последната цена за посочения продукт направена в покупка от контрагента
     * @return object $rec->price  - цена
     * 				  $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $date = NULL, $rate = 1, $chargeVat = 'no')
    {
       if(!$date){
       	   $date = dt::today();
        }
        
        // Намира последната цена на която продукта е бил продаден на този контрагент
        $detailQuery = purchase_PurchasesDetails::getQuery();
        $detailQuery->EXT('contragentClassId', 'purchase_Purchases', 'externalName=contragentClassId,externalKey=requestId');
        $detailQuery->EXT('contragentId', 'purchase_Purchases', 'externalName=contragentId,externalKey=requestId');
        $detailQuery->EXT('valior', 'purchase_Purchases', 'externalName=valior,externalKey=requestId');
        $detailQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
        $detailQuery->where("#contragentClassId = {$customerClass}");
        $detailQuery->where("#contragentId = {$customerId}");
        $detailQuery->where("#valior <= '{$date}'");
        $detailQuery->where("#productId = '{$productId}'");
        $detailQuery->where("#state = 'active' OR #state = 'closed'");
        $detailQuery->orderBy('#valior,#id', 'DESC');
        $lastRec = $detailQuery->fetch();
        
        if(!$lastRec) return NULL;
        
        $vat = cat_Products::getVat($lastRec->productId);
        $lastRec->price = deals_Helper::getDisplayPrice($lastRec->price, $vat, $rate, $chargeVat);
        
        return (object)array('price' => $lastRec->price, 'discount' => $lastRec->discount);
    }
}