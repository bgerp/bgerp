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
     * Генерира агрегираната бизнес информация за тази продажба
     * 
     * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
     * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до 
     * момента.
     * 
     * Списъка с въпросните документи, имащи отношение към бизнес информацията за пробдажбата е
     * сечението на следните множества:
     * 
     *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
     *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
     *  * Документите, в състояние различно от `draft` и `rejected`
     * 
     * 
     * @return bgerp_iface_DealResponse
     */
    public function getAggregatedDealInfo()
    {
        $saleDocuments = $this->_mvc->getDescendants($this->id);
        $aggregateInfo = new bgerp_iface_DealResponse();
        
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
    
    
    /**
     * Обновява БД с агрегирана бизнес информация за продажба 
     * 
     * @param bgerp_iface_DealResponse $aggregateDealInfo
     */
    public function updateAggregateDealInfo(bgerp_iface_DealResponse $aggregateDealInfo)
    {
        // Преизчисляваме общо платената и общо експедираната сума
        $this->amountPaid      = $aggregateDealInfo->paid->amount;
        $this->amountDelivered = $aggregateDealInfo->shipped->amount;
        $this->amountInvoiced  = $aggregateDealInfo->invoiced->amount;
        
        $saleProducts = $this->getDetails('sales_SalesDetails', 'sales_model_SaleProduct');
        
        $this->save();
        
        /* @var $p sales_model_SaleProduct */
        foreach ($saleProducts as $p) {
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
