<?php

class purchase_model_Request extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'purchase_Requests';
        
    
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

    
    /**
     * @var double
     */
    public $amountInvoiced;

    
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
    public $storeId;

    
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
     * Обновява БД с агрегирана бизнес информация за покупка 
     * 
     * @param bgerp_iface_DealResponse $aggregateDealInfo
     */
    public function updateAggregateDealInfo(bgerp_iface_DealResponse $aggregateDealInfo)
    {
        // Преизчисляваме общо платената и общо експедираната сума
        $this->amountPaid      = $aggregateDealInfo->paid->amount;
        $this->amountDelivered = $aggregateDealInfo->shipped->amount;
        $this->amountInvoiced  = $aggregateDealInfo->invoiced->amount;
        
        $requestProducts = $this->getDetails('purchase_RequestDetails', 'purchase_model_RequestProduct');
        
        $this->save();
        
        /* @var $p purchase_model_RequestProduct */
        foreach ($requestProducts as $p) {
            $aggrProduct = $aggregateDealInfo->shipped->findProduct($p->productId, $p->classId, $p->packagingId);
            if ($aggrProduct) {
                $p->quantityDelivered = $aggrProduct->quantity;
            } else {
                $p->quantityDelivered = 0;
            }
            $aggrProduct = $aggregateDealInfo->invoiced->findProduct($p->productId, $p->classId, $p->packagingId);
            if ($aggrProduct) {
                $p->quantityInvoiced = $aggrProduct->quantity;
            } else {
                $p->quantityInvoiced = 0;
            }
        
            $p->save();
        }
    }
}
