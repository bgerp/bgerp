<?php

class store_model_ShipmentOrder extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'store_ShipmentOrders';
    
    /**
     * @var string
     */
    public $valior;
    
    /*
     * Стойности
     */
    
    /**
     * @var double
     */
    public $amountDelivered;
    
    /*
     * Контрагент
     */ 
    
    /**
     * @var int
     */
    public $contragentClassId;
        
    /**
     * @var int
     */
    public $contragentId;
    
        
    /*
     * Доставка
     */
        
    /**
     * @var int
     */
    public $termId;
        
    /**
     * @var int
     */
    public $locationId;
        
    /**
     * @var string
     */
    public $deliveryTime;
        
    /**
     * @var int
     */
    public $storeId;
        

    /*
     * Допълнително
     */
    
    /**
     * @var string
     */
    public $note;
    
    /**
     * @var enum(draft, active, rejected)
     */
    public $state;
    
    
    /**
     * @vat string(3)
     */
    public $currencyId;
    
    /**
     * @var double
     */
    public $currencyRate;
    
    /**
     * ДДС 
     */
    public $chargeVat;
}
