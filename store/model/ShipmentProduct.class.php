<?php

class store_model_ShipmentProduct
{
    /**
     * @var int
     */
    public $id;
    
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
        
    public function __construct(stdClass $rec)
    {
        foreach (get_class_vars($this) as $prop) {
            if (isset($rec->{$prop})) {
                $this->{$prop} = $rec->{$prop};
            }
        }
    }
}
