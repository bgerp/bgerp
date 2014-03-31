<?php

/**
 * 
 * @author developer
 */
class purchase_model_Invoice extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'purchase_Invoices';
    
    
    /**
     * @var string
     */
    public $date;
    
    
    /**
     * @var string
     */
    public $place;
    
    
    /**
     * @var int
     */
    public $number;
    
    
    /**
     * @var int class(interface=crm_ContragentAccRegIntf)
     */
    public $contragentClassId;
    
    
    /**
     * @var int
     */
    public $contragentId;
    
    
    /**
     * @var string
     */
    public $contragentName;
    
    
    /**
     * @var string
     */
    public $responsible;
    
    
    /**
     * @var int key(mvc=drdata_Countries)
     */
    public $contragentCountryId;
    
    
    /**
     * @var string drdata_VatType
     */
    public $contragentVatNo;
    
    
    /**
     * @var string
     */
    public $contragentPCode;
    
    
    /**
     * @var string
     */
    public $contragentPlace;
    
    
    /**
     * @var string
     */
    public $contragentAddress;
    
    
    /**
     * @var double
     */
    public $changeAmount;
    
    
    /**
     * @var int key(mvc=cond_PaymentMethods)
     */
    public $paymentMethodId;
    
    
    /**
     * @var int key(mvc=bank_OwnAccounts)
     */
    public $accountId;
    
    
    /**
     * @var int key(mvc=cash_Cases)
     */
    public $caseId;
    
    
    /**
     * @var string(3) customKey(mvc=currency_Currencies,key=code)
     */
    public $currencyId;

    
    /**
     * @var double
     */
    public $rate;

    
    /**
     * @var int key(mvc=cond_DeliveryTerms)
     */
    public $deliveryId;
    
    
    /**
     * @var int key(mvc=crm_Locations)
     */
    public $deliveryPlaceId;
    
    
    /**
     * @var double
     */
    public $discountAmount;
    
    
    /**
     * @var string
     */
    public $vatDate;
    
    
    /**
     * @var string
     */
    public $vatAmount;
    
    
    /**
     * @var string enum(yes,freed,export)
     */
    public $vatRate;
    
    /**
     * @var string
     */
    public $vatReason;
    
    
    /**
     * @var string
     */
    public $reason;
    
    
    /**
     * @var string richtext(bucket=Notes)
     */
    public $additionalInfo;
    
    
    /**
     * @var double
     */
    public $dealValue;
    
    
    /**
     * @var string enum(draft, active, rejected)
     */
    public $state;
    
    
    /**
     * @var string enum(invoice, credit_note, debit_note)
     */
    public $type;
    
    
    /**
     * Авансова сума
     */
    public $dpAmount;
    
    
    /**
     * Авансова операция
     */
    public $dpOperation;
    
    
    /**
     * @var int class(interface=store_ShipmentIntf)
     */
    public $docType;
    
    
    /**
     * @var int
     */
    public $docId;
}