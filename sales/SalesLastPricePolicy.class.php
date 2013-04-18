<?php



/**
* Имплементация на ценова политика "По последна цена"
*
*
* @category  bgerp
* @package   sales
* @author    Ivelin Dimov <ivelin_pdimov@abv.com>
* @copyright 2006 - 2013 Experta OOD
* @license   GPL 3
* @since     v 0.1
* @title     Политика "По последна цена"
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
     * sysId-та на  групи, от които можем да задаваме продукти
     */
    public static $productGroups = array('goods', 
    									 'productsStandard', 
    									 'productsNonStand', 
    									 'services');
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        return FALSE;
    }
    
    
    /**
     * 
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    function getProducts($customerClass, $customerId, $date = NULL)
    {
        $products = cat_Products::getByGroup(static::$productGroups);
        
        return $products;
    }
    
    
    /**
     * Връща последната цена за посочения продукт направена в
     * продажба към контрагента
     * @return object $rec->price  - цена
     * 				  $rec->discount - отстъпка
     */
    function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
        if(!$date){
       	  $date = dt::now();
        }
       
        $salesQuery = sales_Sales::getQuery();
        $salesQuery->where("#contragentClassId = {$customerClass}");
        $salesQuery->where("#contragentId = {$customerId}");
        $salesQuery->where("#valior <= '{$date}'");
        $salesQuery->where("#state = 'active'");
        $salesQuery->orderBy('#valior', 'DESC');
        $allSales = $salesQuery->fetchAll();
        
        $detailQuery = sales_SalesDetails::getQuery();
    	foreach ($allSales as $sale){
       		 $detailQuery->orWhere("#saleId = {$sale->id}");
        }
        
        $detailQuery->where("#productId = {$productId}");
        
        if($packagingId){
        	$detailQuery->where("#packagingId = {$packagingId}");
        } else {
        	$detailQuery->where("#packagingId IS NULL");
        }
       
        $detailQuery->orderBy('#saleId', 'DESC');
        
        $lastRec = $detailQuery->fetch();
        if(!$lastRec){
        	return NULL;
        }
        
        return (object)array('price' => $lastRec->price, 'discount' => $lastRec->discount);
    }
    
    
    /**
     * Заглавие на ценоразписа за конкретен клиент 
     * 
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    {
        return $this->title;
    }
}