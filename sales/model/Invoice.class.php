<?php

/**
 * 
 * @author developer
 */
class sales_model_Invoice
{
    /**
     * @var int
     */
    public $id;
    
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
     * @var int key(mvc=acc_Items)
     */
    public $contragentAccItemId;
    
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
     * @var int key(mvc=salecond_PaymentMethods)
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
     * @var int key(mvc=salecond_DeliveryTerms)
     */
    public $deliveryId;
    
    /**
     * @var int key(mvc=crm_Locations)
     */
    public $deliveryPlaceId;
    
    /**
     * @var string
     */
    public $vatDate;
    
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
     * @var int class(interface=store_ShipmentIntf)
     */
    public $docType;
    
    /**
     * @var int
     */
    public $docId;
    

    /**
     * 
     * @var core_Mvc
     */
    protected $_mvc;
    
    /**
     * @var array
     */
    protected $_details = array(); 
    
    public function __construct($id = NULL, $mvc = 'sales_Invoices')
    {
        $this->_mvc = cls::get($mvc);
        
        if (isset($id)) {
            $rec = $this->fetch($id);
            $this->init($rec);
        }
    }
    
    public function fetch($id)
    {
        return $this->_mvc->fetchRec($id);
    }
            
    
    public function init(stdClass $rec)
    {
        foreach (get_class_vars($this) as $prop) {
            if (isset($rec->{$prop})) {
                $this->{$prop} = $rec->{$prop};
            }
        }
    }
    
    
    /**
     * 
     * @param core_Mvc $detailMvc
     * @return array
     */
    public function getDetails($detailMvc) {
        if (is_scalar($detailMvc)) {
            $detailMvc = cls::get($detailMvc);
        }
        
        $detailName = cls::getClassName($detailMvc);
        
        if (!isset($this->_details[$detailName])) {
            $this->_details[$detailName] = array();
            
            if (!empty($this->id)) {
                /* @var $query core_Query */
                $query = $detailMvc->getQuery();
                
                $this->_details[$detailName] = $query->fetchAll("#{$detailMvc->masterKey} = {$this->id}");
            }
        }
        
        return $this->_details[$detailName];
    }
}
