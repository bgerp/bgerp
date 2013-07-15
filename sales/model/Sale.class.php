<?php

class sales_model_Sale
{
    /**
     * @var int
     */
    public $id;
    
    /**
     * @var string
     */
    public $valior;
    
    /**
     * @var enum(yes,no,monthend)
     */
    public $makeInvice;
    
    /**
     * @var enum(yes,no)
     */
    public $chargeVat;
        
    /*
     * Стойности
     */
    
    /**
     * @var double
     */
    public $amountDeal;
    
    /**
     * @var double
     */
    public $amountDelivered;
        
    /**
     * @var double
     */
    public $amountPaid;
        
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
    public $deliveryTermId;
        
    /**
     * @var int
     */
    public $deliveryLocationId;
        
    /**
     * @var string
     */
    public $deliveryTime;
        
    /**
     * @var int
     */
    public $shipmentStoreId;
        
    /**
     * @var enum(no, yes)
     */
    public $isInstantShipment;
        
    /*
     * Плащане
     */
    
    /**
     * @var int
     */
    public $paymentMethodId;
    
    /**
     * 3-буквен ISO код на валута (BGN, USD, EUR и пр)
     * 
     * @var string(3)
     */
    public $currencyId;
    
    /**
     * @var double
     */
    public $currencyRate;
    
    /**
     * @var int
     */
    public $bankAccountId;
    
    /**
     * @var int
     */
    public $caseId;
    
    /**
     * @var enum(no,yes)
     */
    public $isInstantPayment;
        
    /*
     * Наш персонал
     */
    
    /**
     * @var int
     */
    public $initiatorId;
    
    /**
     * @var int
     */
    public $dealerId;

    /*
     * Допълнително
     */
    
    /**
     * @var string
     */
    public $pricesAtDate;
    
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
    public $sales_SaleDetails = array(); 
    
    public function __construct(stdClass $rec)
    {
        foreach (get_class_vars($this) as $prop) {
            if (isset($rec->{$prop})) {
                $this->{$prop} = $rec->{$prop};
            }
        }
    }
}
