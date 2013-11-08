<?php

/**
 * 
 * @author developer
 *
 * @property int $classId key(mvc=core_Classes) първичен ключ на мениджъра на продукта
 * @property core_Manager $productClass инстанция на мениджъра на продукта
 */
class purchase_model_RequestProduct extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'purchase_RequestDetails';
    
    
    /**
     * @var int key(mvc=purchase_Request)
     */
    public $requestId;

    
    /**
     * Мениджър на продукт
     *
     * @var int key(mvc=core_Classes)
     */
    public $classId;
    
    
    /**
     * ИД на продукт
     * 
     * @var int
     */
    public $productId;
    
    
    /**
     * Мярка
     * 
     * @var int key(mvc=cat_UoM)
     */
    public $uomId;
    
    
    /**
     * Опаковка (ако има)
     * 
     * @var int key(mvc=cat_Packagings)
     */
    public $packagingId;
    
    
    /**
     * Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
     * няма стойност, приема се за единица.
     * 
     * @var double
     */
    public $quantityInPack;
        
    
    /**
     * Количество (в основна мярка)
     * 
     * @var double
     */
    public $quantity;
        
    
    /**
     * Експедирано количество (в основна мярка)
     * 
     * @var double
     */
    public $quantityDelivered;
        
    
    /**
     * Фактурирано количество (в основна мярка)
     * 
     * @var double
     */
    public $quantityInvoiced;
        
    
    /**
     * Цена за единица продукт в основна мярка
     * 
     * @var double
     */
    public $price;
        
    
    /**
     * Процент отстъпка (0..1 => 0% .. 100%)
     * 
     * @var double
     */
    public $discount;
    
    
    /**
     * Връща к-то в опаковка
     */
    public function getQuantityInPack()
    {
        $q = 1;
        
        if (!empty($this->packagingId)) {
            $productInfo = $this->getProductInfo();
            
            if (!$packInfo = $productInfo->packagings[$this->packagingId]) {
                $q = NULL;
            } else {
                $q = $packInfo->quantity;
            }
        }
        
        return $q;
    }
}
