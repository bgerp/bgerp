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
       
        // Намира последната цена на която продукта е бил 
        // продаден на този контрагент
        $detailQuery = sales_SalesDetails::getQuery();
        $detailQuery->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');
        $detailQuery->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');
        $detailQuery->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
        $detailQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
        $detailQuery->where("#contragentClassId = {$customerClass}");
        $detailQuery->where("#contragentId = {$customerId}");
        $detailQuery->where("#valior <= '{$date}'");
        $detailQuery->where("#state = 'active'");
        $detailQuery->where("#productId = '{$productId}'");
        $detailQuery->orderBy('#valior', 'DESC');
        
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
    
    
	/**
     * Връща мениджъра на продуктите (@see cat_Products)
     * @return core_Classes $class - инстанция на мениджъра
     */
    public function getProductMan()
    {
        return cls::get('cat_Products');
    }
}