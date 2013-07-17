<?php

class sales_model_Sale extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'sales_Sales';
        
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
     * 
     * @param array $saleDocuments масив от референции към документи
     * @return bgerp_iface_DealResponse
     */
    public function getAggregatedDealInfo($saleDocuments)
    {
        $aggregateInfo = new bgerp_iface_DealResponse();
        $dealInfo      = array();
        
        /* @var $d core_ObjectReference */
        foreach ($saleDocuments as $d) {
            $dState = $d->rec('state');
            if ($dState == 'draft' || $dState == 'rejected') {
                // Игнорираме черновите и оттеглените документи
                continue;
            }
        
            if ($d->haveInterface('bgerp_DealIntf')) {
                /* @var $dealInfo bgerp_iface_DealResponse */
                $dealInfo = $d->getDealInfo();
                
                $aggregateInfo->agreed->push($dealInfo->agreed);
                $aggregateInfo->shipped->push($dealInfo->shipped);
                $aggregateInfo->paid->push($dealInfo->paid);
                $aggregateInfo->invoiced->push($dealInfo->invoiced);
            }
        }
        
        return $aggregateInfo;
    }
}
