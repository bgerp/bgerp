<?php

class store_model_ShipmentOrder
{
    /**
     * @var int
     */
    public $id;
    
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
     * @var array
     */
    public $products = array(); 
    
    public function __construct(stdClass $rec)
    {
        foreach (get_class_vars($this) as $prop) {
            if (isset($rec->{$prop})) {
                $this->{$prop} = $rec->{$prop};
            }
        }
    }
}
