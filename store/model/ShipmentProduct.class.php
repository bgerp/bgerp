<?php

class store_model_ShipmentProduct extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'store_ShipmentOrderDetails';
    
    /**
     * @var int key(mvc=store_ShipmentOrders)
     */
    public $shipmentId;
    
    /**
     * Ценова политика
     * 
     * @var int class(interface=price_PolicyIntf)
     */
    public $policyId;
    
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
     * Цена 
     * 
     * @var double
     */
    public $price;
    
    /**
     * Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
     * няма стойност, приема се за единица.
     * 
     * @var double
     */
    public $quantityInPack;

    
    /**
     * Отстъпка
     * 
     * @var double
     */
    public $discount;
    
    
    /**
     * Количество (в основна мярка)
     * 
     * @var double
     */
    public $quantity;
    
    
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
    
    
    public function getProductInfo()
    {
        $ref = new core_ObjectReference($this->classId, $this->productId);
        
        return $ref->getProductInfo();
    }
}
