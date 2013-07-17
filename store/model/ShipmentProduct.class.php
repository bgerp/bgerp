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
     * @var int class(interface=price_PolicyIntf, select=title)
     */
    public $policyId;
    
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
}
