<?php

class store_model_Receipt extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'store_Receipts';
    
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
    public $amountDeliveredVat;
        
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

    /**
     * @var string(3)
     */
    public $currencyId;
    
    /**
     * ДДС 
     */
    public $chargeVat;
    
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
    
    
}