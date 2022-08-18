<?php

require_once 'ParamOptionsBeforePayment.class.php';

/**
 * This class represents ParamCalculation type
 */
class ParamCalculation {

    /**
     * Payer type: sender
     * @var integer Signed 32-bit
     */
    const PAYER_TYPE_SENDER = 0;

    /**
     * Payer type: receiver
     * @var integer Signed 32-bit
     */
    const PAYER_TYPE_RECEIVER = 1;

    /**
     * Payer type: third party
     * @var integer Signed 32-bit
     */
    const PAYER_TYPE_THIRD_PARTY = 2;

    /**
     * Service type ID to set when this structire is used for multiple service calculations
     * @var integer Signed 64-bit
     */
    const CALCULATE_MULTUPLE_SERVICES_SERVICE_TYPE_ID = 0;

    /**
     * The date for shipment pick-up (the "time" component is ignored). Default value is "today".
     * MANDATORY: NO
     * @access private
     * @var date
     */
    private $_takingDate;

    /**
     * If set to true, the "takingDate" field is not just to be validated, but the first allowed (following)
     * date will be used instead (in compliance with the pick-up schedule etc.).
     * MANDATORY: NO
     * @access private
     * @var boolean
     */
    private $_autoAdjustTakingDate;
     
    /**
     * Courier service type ID
     * MANDATORY: YES
     * @access private
     * @var integer Signed 64-bit
     */
    private $_serviceTypeId;

	/**
     * Specifies the specific Speedy office, where the sender intends to deliver the shipment by him/herself. 
     * If willBringToOfficeId is provided, willBringToOffice flag is considered "true" and the picking "from office", regardless the value provided. 
     * If willBringToOfficeId is not provied (null) and willBringToOffice flag is "true", willBringToOfficeId is automatically set with default value configured for caller user profile. 
     * The default willBringToOfficeId value could be managed using profile configuration page in client's Speedy web site. 
     * If willBringToOfficeId is set to 0, broughtToOffice flag is considered "false". 
     * MANDATORY: NO
     * @access private
     * @var integer signed 64-bit
     * @since 2.8.0
     */
	private $_willBringToOfficeId;

    /**
     * Specifies if the sender intends to deliver the shipment to a Speedy office by him/herself instead of ordering a visit by courier
     * MANDATORY: YES
     * @access private
     * @var boolean
     */
    private $_broughtToOffice;

	/**
     * ID of an office "to be called". 
     * Non-null and non-zero value indicates this picking as "to office". Otherwise "to address" is considered. 
     * If officeToBeCalledId is provided (non-null and non-zero), toBeCalled flag is considered "true". 
     * If officeToBeCalledId is set to 0, toBeCalled flag is considered "false". 
     * MANDATORY: NO
     * @access private
     * @var integer signed 64-bit
     * @since 2.8.0
     */
	private $_officeToBeCalledId;

    /**
     * Specifies if the shipment is "to be called"
     * MANDATORY: YES
     * @access private
     * @var boolean
     */
    private $_toBeCalled;

    /**
     * Fixed time for delivery ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.).
     * Depending on the courier service, this property could be required, allowed or banned
     * MANDATORY: NO
     * @access private
     * @var integer Signed 16-bit
     */
    private $_fixedTimeDelivery;

    /**
     * In some rare cases users might prefer the delivery to be deferred by a day or two.
     * This parameter allows users to specify by how many (working) days they would like to postpone the shipment delivery.
     * Max value is 2.
     * MANDATORY: NO
     * @access private
     * @var integer Signed 32-bit
     */
    private $_deferredDeliveryWorkDays;

    /**
     * Shipment insurance value (if the shipment is insured).
     * The limit of this value depends on user's permissions and Speedy's current policy.
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_amountInsuranceBase;

    /**
     * Cash-on-Delivery (COD) amount
     * The limit of this value depends on user's permissions and Speedy's current policy.
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_amountCodBase;

    /**
     * Specifies if the COD value is to be paid to a third party. Allowed only if the shipment has payerType = 2 (third party).
     * MANDATORY: NO
     * @access private
     * @var boolean
     */
    private $_payCodToThirdParty;

    /**
     * Parcels count.
     * Max 999.
     * MANDATORY: YES
     * @access private
     * @var integer Signed 32-bit
     */
    private $_parcelsCount;
    
   /**
     * Data for parcels with explicit/fixed IDs (from the second one onward)
     * The list has maximum lenght 998
     * MANDATORY: NO
     * @var array List of ParamParcelInfo
     */
    private $_parcels;

    /**
     * Declared weight (the greater of "volume" and "real" weight values).
     * Max 100.00
     * MANDATORY: YES
     * @access private
     * @var double Signed 64-bit
     */
    private $_weightDeclared;

    /**
     * Specifies whether the shipment consists of documents
     * MANDATORY: YES
     * @access private
     * @var boolean
     */
    private $_documents;

    /**
     * Specifies whether the shipment is fragile - necessary when the price of insurance is being calculated
     * MANDATORY: YES
     * @access private
     * @var boolean
     */
    private $_fragile;

    /**
     * Specifies whether the shipment is palletized
     * MANDATORY: YES
     * @access private
     * @var boolean
     */
    private $_palletized;

    /**
     * Sender's ID.
     * Either senderId or senderSiteId must be set
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_senderId;

    /**
     * Sender's site ID.
     * Either senderId or senderSiteId must be set
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_senderSiteId;

    /**
     * Receiver's ID.
     * Either receiverId or receiverSiteId must be set
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_receiverId;

    /**
     * Receiver's site ID
     * Either receiverId or receiverSiteId must be set
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_receiverSiteId;

    /**
     * Payer type (0=sender, 1=receiver or 2=third party)
     * MANDATORY: YES
     * @access private
     * @var integer Signed 32-bit
     */
    private $_payerType;

    /**
     * Payer ID. Must be set <=> payer is "third party".
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_payerRefId;

    /**
     * Insurance payer type (0=sender, 1=reciever or 2=third party).
     * Must be set <=> shipment is insured (i.e. amountInsuranceBase > 0).
     * MANDATORY: NO
     * @access private
     * @var integer Signed 32-bit
     */
    private $_payerTypeInsurance;
    
    /**
     * Packings payer type (0=sender, 1=reciever or 2=third party)
     * MANDATORY: NO. If not set, the payer of the packings' surcharge will be the same as the one indicated by payerType.
     * @var integer Signed 32-bit
     * @since 2.3.0
     */
    private $_payerTypePackings;
    
    /**
     * Insurance payer ID. Must be set <=> shipment has insurance (i.e. amountInsuranceBase > 0) and it is payed by a "third party".
     * MANDATORY: NO
     * @access private
     * @var integer Signed 64-bit
     */
    private $_payerRefInsuranceId;
    
    /**
     * Packings payer id
     * MANDATORY: Must be set <=> payerTypePackings is "third party".
     * @var integer Signed 64-bit
     * @since 2.3.0
     */
    protected $_payerRefPackingsId;
    
    /**
     * Special delivery id
     * MANDATORY: NO
     * @var signed 32-bit integer
     * @since 2.3.0
     */
    protected $_specialDeliveryId;

    /**
     * Receiver's country ID
     * MANDATORY: NO. Defaults to Bulgaria when not specified
     * @var integer Signed 64-bit
     * @since 2.5.0
     */
    private $_receiverCountryId;

    /**
     * Receiver's post code
     * MANDATORY: According to internal nomenclature support
     * @var string
     * @since 2.5.0
     */
    private $_receiverPostCode;
    
    /**
     * Sender's country ID
     * MANDATORY: NO. Defaults to Bulgaria when not specified
     * @var integer Signed 64-bit
     * @since 2.5.0
     */
    private $_senderCountryId;
    
    /**
     * Sender's post code
     * MANDATORY: According to internal nomenclature support
     * @var string
     * @since 2.5.0
     */
    private $_senderPostCode;

    /**
     * Flag indicating whether the shipping price should be included into the cash on delivery price.
     * MANDATORY: NO
     * @var boolean
     * @since 2.9.0
     */
    private $_includeShippingPriceInCod;

    /**
     * Check if specified office to be called is working. Default value - true.
     * MANDATORY: NO
     * @var boolean
     * @since 2.9.4
     */
    private $_checkTBCOfficeWorkDay;

    /**
     * Flag indicating whether the shipment should be delivered on a half working day, if such a delivery date happens to be calculated.
     * MANDATORY: NO
     * @var boolean
     * @since 3.2.6
     */
    private $_halfWorkDayDelivery;

    /**
     * If the flag is true and picking doesn't allow insuranceBase calculate picking successfully and ignore insuranceBase. Default value - true.
     * MANDATORY: NO
     * @var boolean
     * @since 3.3.2
     */
    private $_ignoreAmountInsuranceBaseIfNotApplicable;

    /**
     * Return money-transfer request amount
     * MANDATORY: NO
     * @var signed 64-bit real (nullable)
     * @since 3.4.9
     */
    protected $_retMoneyTransferReqAmount;

    /**
     * Options before payment
     * MANDATORY: NO
     * @var ParamOptionsBeforePayment
     * @since 3.4.9
     */
    protected $_optionsBeforePayment;






    /**
     * Set the date for shipment pick-up (the "time" component is ignored).
     * Server defaults this value to "today" if it is not set
     * @param date $takingDate
     */
    public function setTakingDate($takingDate) {
        $this->_takingDate = $takingDate;
    }

    /**
     * Get date for shipment pick-up (the "time" component is ignored).
     * @return date Taking date
     */
    public function getTakingDate() {
        return $this->_takingDate;
    }

    /**
     * Set flag to auto-adjist or not taking date.
     * If set to true, the "takingDate" field is not just to be validated, but the first allowed (following)
     * date will be used instead (in compliance with the pick-up schedule etc.).
     * @param boolean $autoAdjustTakingDate
     */
    public function setAutoAdjustTakingDate($autoAdjustTakingDate) {
        $this->_autoAdjustTakingDate = $autoAdjustTakingDate;
    }

    /**
     * Get flag for taking date auto-adjustment
     * @return boolean Auto-adjust taking date flag
     */
    public function isAutoAdjustTakingDate() {
        return $this->_autoAdjustTakingDate;
    }

    /**
     * Set courier service type ID from.
     * @param integer $serviceTypeId Signed 64-bit value from Speedy service nomenclature
     */
    public function setServiceTypeId($serviceTypeId) {
        $this->_serviceTypeId = $serviceTypeId;
    }

    /**
     * Get courier service type ID from nomenclature.
     * @return integer Courier service type ID - signed 64-bit value from Speedy service nomenclature
     */
    public function getServiceTypeId() {
        return $this->_serviceTypeId;
    }
    
    /**
     * Set the specific Speedy office, where the sender intends to deliver the shipment by him/herself. 
     * If willBringToOfficeId is provided, willBringToOffice flag is considered "true" and the picking "from office", regardless the value provided. 
     * If willBringToOfficeId is not provied (null) and willBringToOffice flag is "true", willBringToOfficeId is automatically set with default value configured for caller user profile. 
     * The default willBringToOfficeId value could be managed using profile configuration page in client's Speedy web site. 
     * If willBringToOfficeId is set to 0, broughtToOffice flag is considered "false". 
     * @param integer signed 64-bit $willBringToOfficeId Bring-to-office id
     * @since 2.8.0
     */
    public function setWillBringToOfficeId($willBringToOfficeId) {
        $this->_willBringToOfficeId = $willBringToOfficeId;
    }

    /**
     * Get the specific Speedy office, where the sender intends to deliver the shipment by him/herself. 
     * If willBringToOfficeId is provided, willBringToOffice flag is considered "true" and the picking "from office", regardless the value provided. 
     * If willBringToOfficeId is not provied (null) and willBringToOffice flag is "true", willBringToOfficeId is automatically set with default value configured for caller user profile. 
     * The default willBringToOfficeId value could be managed using profile configuration page in client's Speedy web site. 
     * If willBringToOfficeId is set to 0, broughtToOffice flag is considered "false". 
     * @return integer signed 64-bit Bring-to-office id
     * @since 2.8.0
     */
    public function getWillBringToOfficeId() {
        return $this->_willBringToOfficeId;
    }

    /**
     * Set flag for brought-to-office
     * This flag specifies if the sender intends to deliver the shipment to a Speedy office by him/herself instead of ordering a visit by courier
     * @param boolean $broughtToOffice Brought-to-office flag
     */
    public function setBroughtToOffice($broughtToOffice) {
        $this->_broughtToOffice = $broughtToOffice;
    }

    /**
     * Get flag for brought-to-office
     * @return boolean Brought-to-office flag
     */
    public function isBroughtToOffice() {
        return $this->_broughtToOffice;
    }

	/**
     * Set ID of the office "to be called". 
     * Non-null and non-zero value indicates this picking as "to office". Otherwise "to address" is considered. 
     * If officeToBeCalledId is provided (non-null and non-zero), toBeCalled flag is considered "true". 
     * If officeToBeCalledId is set to 0, toBeCalled flag is considered "false". 
     * @param integer signed 64-bit $officeToBeCalledId Office-to-be-called id
     * @since 2.8.0
     */
    public function setOfficeToBeCalledId($officeToBeCalledId) {
        $this->_officeToBeCalledId = $officeToBeCalledId;
    }

    /**
     * Get ID of the office "to be called". 
     * Non-null and non-zero value indicates this picking as "to office". Otherwise "to address" is considered. 
     * If officeToBeCalledId is provided (non-null and non-zero), toBeCalled flag is considered "true". 
     * If officeToBeCalledId is set to 0, toBeCalled flag is considered "false". 
     * @return integer signed 64-bit Office-to-be-called id
     * @since 2.8.0
     */
    public function getOfficeToBeCalledId() {
        return $this->_officeToBeCalledId;
    }

    /**
     * Set flag for to-be-called. Specifies if the shipment is "to be called"
     * @param boolean $toBeCalled Brought-to-office flag
     */
    public function setToBeCalled($toBeCalled) {
        $this->_toBeCalled = $toBeCalled;
    }

    /**
     * Get flag for to-be-called
     * @return boolean To-be-called flag
     */
    public function isToBeCalled() {
        return $this->_toBeCalled;
    }

    /**
     * Set fixed time for delivery ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.).
     * Depending on the courier service, this property could be required, allowed or banned
     * @param integer $fixedTimeDelivery Signed 16-bit
     */
    public function setFixedTimeDelivery($fixedTimeDelivery) {
        $this->_fixedTimeDelivery = $fixedTimeDelivery;
    }

    /**
     * Get fixed time for delivery ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.).
     * @return integer Fixed time for delivery - signed 16-bit
     */
    public function getFixedTimeDelivery() {
        return $this->_fixedTimeDelivery;
    }

    /**
     * Set deferred delivery work days.
     * This parameter allows users to specify by how many (working) days they would like to postpone the shipment delivery.
     * Max value is 2.
     * @param integer $deferredDeliveryWorkDays Signed 32-bit
     */
    public function setDeferredDeliveryWorkDays($deferredDeliveryWorkDays) {
        $this->_deferredDeliveryWorkDays = $deferredDeliveryWorkDays;
    }

    /**
     * Get deferred delivery work days.
     * @return integer Deferred delivery work days - signed 32-bit
     */
    public function getDeferredDeliveryWorkDays() {
        return $this->_deferredDeliveryWorkDays;
    }
     
    /**
     * Set shipment insurance value (if the shipment is insured).
     * The limit of this value depends on user's permissions and Speedy's current policy.
     * @param integer $amountInsuranceBase Signed 64-bit
     */
    public function setAmountInsuranceBase($amountInsuranceBase) {
        $this->_amountInsuranceBase = $amountInsuranceBase;
    }

    /**
     * Get shipment insurance value
     * @return integer Shipment insurance value - signed 64-bit
     */
    public function getAmountInsuranceBase() {
        return $this->_amountInsuranceBase;
    }

    /**
     * Set cash-on-Delivery (COD) amount.
     * The limit of this value depends on user's permissions and Speedy's current policy.
     * @param integer $amountCodBase Signed 64-bit
     */
    public function setAmountCodBase($amountCodBase) {
        $this->_amountCodBase = $amountCodBase;
    }

    /**
     * Get cash-on-Delivery (COD) amount.
     * @return integer Cash-on-Delivery (COD) amount - signed 64-bit
     */
    public function getAmountCodBase() {
        return $this->_amountCodBase;
    }

    /**
     * Set flag, if the COD value is to be paid to a third party. Allowed only if the shipment has payerType = 2 (third party).
     * @param boolean $payCodToThirdParty
     */
    public function setPayCodToThirdParty($payCodToThirdParty) {
        $this->_payCodToThirdParty = $payCodToThirdParty;
    }

    /**
     * Get flag for COD value to be paid to a third party
     * @return boolean Flag for cash-on-Delivery (COD) to third party
     */
    public function isPayCodToThirdParty() {
        return $this->_payCodToThirdParty;
    }

    /**
     * Set parcels count.
     * Max 999.
     * @param integer $parcelsCount Parcels count - signed 32-bit
     */
    public function setParcelsCount($parcelsCount) {
        $this->_parcelsCount = $parcelsCount;
    }

    /**
     * Get parcels count.
     * @return integer Parcels count - signed 32-bit
     */
    public function getParcelsCount() {
        return $this->_parcelsCount;
    }

    /**
     * Set declared weight (the greater of "volume" and "real" weight values).
     * Max 100.00
     * @param double $weightDeclared Declared weight - signed 64-bit
     */
    public function setWeightDeclared($weightDeclared) {
        $this->_weightDeclared = $weightDeclared;
    }

    /**
     * Get declared weight
     * @return double Declared weight - signed 64-bit
     */
    public function getWeightDeclared() {
        return $this->_weightDeclared;
    }

    /**
     * Set flag whether the shipment consists of documents
     * @param boolean $documents Documents flag
     */
    public function setDocuments($documents) {
        $this->_documents = $documents;
    }

    /**
     * Get flag whether the shipment consists of documents
     * @return boolean Documents flag
     */
    public function isDocuments() {
        return $this->_documents;
    }

    /**
     * Set flag whether the shipment is fragile - necessary when the price of insurance is being calculated
     * @param boolean $fragile Fragile flag
     */
    public function setFragile($fragile) {
        $this->_fragile = $fragile;
    }

    /**
     * Get flag whether the shipment is fragile
     * @return boolean Fragile flag
     */
    public function isFragile() {
        return $this->_fragile;
    }

    /**
     * Set flag whether the shipment is for pallets
     * @param boolean $palletized Palletized flag
     */
    public function setPalletized($palletized) {
        $this->_palletized = $palletized;
    }

    /**
     * Get flag whether the shipment is for pallets
     * @return boolean Palletized flag
     */
    public function isPalletized() {
        return $this->_palletized;
    }

    /**
     * Set sender's ID. From Speedy client nomenclature.
     * Either senderId or senderSiteId must be set
     * @param integer $senderId Signed 64-bit
     */
    public function setSenderId($senderId) {
        $this->_senderId = $senderId;
    }

    /**
     * Get sender's ID. From Speedy client nomenclature.
     * @return integer Sender's ID - signed 64-bit
     */
    public function getSenderId() {
        return $this->_senderId;
    }

    /**
     * Set sender's site ID. From Speedy site nomenclature.
     * Either senderId or senderSiteId must be set
     * @param integer $senderSiteId Signed 64-bit
     */
    public function setSenderSiteId($senderSiteId) {
        $this->_senderSiteId = $senderSiteId;
    }

    /**
     * Get sender's site ID. From Speedy site nomenclature.
     * @return integer Sender's site ID - signed 64-bit
     */
    public function getSenderSiteId() {
        return $this->_senderSiteId;
    }

    /**
     * Receiver's ID. From Speedy client nomenclature.
     * Either receiverId or receiverSiteId must be set
     * @param integer $receiverId Signed 64-bit
     */
    public function setReceiverId($receiverId) {
        $this->_receiverId = $receiverId;
    }

    /**
     * Get receiver's ID. From Speedy client nomenclature.
     * @return integer Receiver's ID - signed 64-bit
     */
    public function getReceiverId() {
        return $this->_receiverId;
    }

    /**
     * Set receiver's site ID. From Speedy site nomenclature.
     * Either receiverId or receiverSiteId must be set
     * @param integer $receiverSiteId Signed 64-bit
     */
    public function setReceiverSiteId($receiverSiteId) {
        $this->_receiverSiteId = $receiverSiteId;
    }

    /**
     * Get receiver's site ID. From Speedy site nomenclature.
     * @return integer Receiver's site ID - signed 64-bit
     */
    public function getReceiverSiteId() {
        return $this->_receiverSiteId;
    }

    /**
     * Set payer type (0=sender, 1=receiver or 2=third party)
     * @param integer $payerType Signed 32-bit
     */
    public function setPayerType($payerType) {
        $this->_payerType = $payerType;
    }

    /**
     * Get payer type (0=sender, 1=receiver or 2=third party)
     * @return integer Payer type - signed 32-bit
     */
    public function getPayerType() {
        return $this->_payerType;
    }

    /**
     * Set payer ID from Speedy client nomenclature.
     * Must be set <=> payer is "third party".
     * @param integer $payerRefId Signed 64-bit
     */
    public function setPayerRefId($payerRefId) {
        $this->_payerRefId = $payerRefId;
    }

    /**
     * Get payer ID from Speedy client nomenclature.
     * @return integer Payer ID - signed 64-bit
     */
    public function getPayerRefId() {
        return $this->_payerRefId;
    }

    /**
     * Set insurance payer type (0=sender, 1=reciever or 2=third party).
     * Must be set <=> shipment is insured (i.e. amountInsuranceBase > 0).
     * @param integer $payerTypeInsurance Signed 32-bit
     */
    public function setPayerTypeInsurance($payerTypeInsurance) {
        $this->_payerTypeInsurance = $payerTypeInsurance;
    }

    /**
     * Get insurance payer type (0=sender, 1=reciever or 2=third party).
     * @return integer Insurance payer type  - signed 32-bit
     */
    public function getPayerTypeInsurance() {
        return $this->_payerTypeInsurance;
    }

    
    /**
     * Set packings payer type (0=sender, 1=reciever or 2=third party).
     * Must be set <=> shipment is insured (i.e. amountInsuranceBase > 0).
     * @param integer $payerTypePackings Signed 32-bit
     */
    public function setPayerTypePackings($payerTypePackings) {
    	$this->_payerTypePackings = $payerTypePackings;
    }
    
    /**
     * Get packings payer type (0=sender, 1=reciever or 2=third party).
     * @return integer Insurance payer type  - signed 32-bit
     */
    public function getPayerTypePackings() {
    	return $this->_payerTypePackings;
    }
    
    /**
     * Set insurance payer ID from Speedy client nomenclature.
     * Must be set <=> shipment has insurance (i.e. amountInsuranceBase > 0) and it is payed by a "third party".
     * @param integer $payerRefInsuranceId Signed 64-bit
     */
    public function setPayerRefInsuranceId($payerRefInsuranceId) {
        $this->_payerRefInsuranceId = $payerRefInsuranceId;
    }

    /**
     * Get insurance payer ID from Speedy client nomenclature.
     * @return integer Insurance payer ID - signed 64-bit
     */
    public function getPayerRefInsuranceId() {
        return $this->_payerRefInsuranceId;
    }
    
    /**
     * Set packings payer ID
     * @param integer $payerRefPackingsId Signed 64-bit
     */
    public function setPayerRefPackingsId($payerRefPackingsId) {
    	$this->_payerRefPackingsId = $payerRefPackingsId;
    }
    
    /**
     * Get packings payer ID
     * @return integer Signed 64-bit
     */
    public function getPayerRefPackingsId() {
    	return $this->_payerRefPackingsId;
    }
    
    /**
     * Gets the special delivery id
     * @return signed 32-bit integer special delivery id
     */
    public function getSpecialDeliveryId() {
    	return $this->_specialDeliveryId;
    }
    
    /**
     * Sets the special delivery id
     * @param signed 32-bit integer $specialDeliveryId Special delivery id
     */
    public function setSpecialDeliveryId($specialDeliveryId) {
    	$this->_specialDeliveryId = $specialDeliveryId;
    }
    
    /**
     * Set the receiver country id
     * @param integer signed 64-bit $receiverCountryId
     */
    public function setReceiverCountryId($receiverCountryId) {
        $this->_receiverCountryId = $receiverCountryId;
    }

    /**
     * Get receiver country id.
     * @return integer signed 64-bit Receiver country id
     */
    public function getReceiverCountryId() {
        return $this->_receiverCountryId;
    }
    
    /**
     * Set the receiver post code
     * @param string $receiverPostCode
     */
    public function setReceiverPostCode($receiverPostCode) {
        $this->_receiverPostCode = $receiverPostCode;
    }

    /**
     * Get receiver post code
     * @return string Receiver post code
     */
    public function getReceiverPostCode() {
        return $this->_receiverPostCode;
    }
    
    /**
     * Set the sender country id
     * @param integer signed 64-bit $senderCountryId
     */
    public function setSenderCountryId($senderCountryId) {
        $this->_senderCountryId = $senderCountryId;
    }

    /**
     * Get sender country id.
     * @return integer signed 64-bit Sender country id
     */
    public function getSenderCountryId() {
        return $this->_senderCountryId;
    }
    
    /**
     * Set the sender post code
     * @param string $senderPostCode
     */
    public function setSenderPostCode($senderPostCode) {
        $this->_senderPostCode = $senderPostCode;
    }

    /**
     * Get sender post code
     * @return string Sender post code
     */
    public function getSenderPostCode() {
        return $this->_senderPostCode;
    }
    
    /**
     * Set data for parcels with explicit/fixed IDs (from the second one onward)
     * @param array $parcels List of ParamParcelInfo
     */
    public function setParcels($parcels) {
        $this->_parcels = $parcels;
    }

    /**
     * Get data for parcels with explicit/fixed IDs (from the second one onward)
     * @return array List of ParamParcelInfo
     */
    public function getParcels() {
        return $this->_parcels;
    }

    /**
     * Set Flag indicating whether the shipping price should be included into the cash on delivery price.
     * @param boolean
     */
    public function setIncludeShippingPriceInCod($includeShippingPriceInCod) {
        $this->_includeShippingPriceInCod = $includeShippingPriceInCod;
    }

    /**
     * Get Flag indicating whether the shipping price should be included into the cash on delivery price.
     * @return boolean
     */
    public function getIncludeShippingPriceInCod() {
        return $this->_includeShippingPriceInCod;
    }

    /**
     * Set Flag indicating if specified office to be called is working. Default value - true.
     * @param boolean
     */
    public function setCheckTBCOfficeWorkDay($checkTBCOfficeWorkDay) {
        $this->_checkTBCOfficeWorkDay = $checkTBCOfficeWorkDay;
    }

    /**
     * Get Flag indicating if specified office to be called is working. Default value - true.
     * @return boolean
     */
    public function getCheckTBCOfficeWorkDay() {
        return $this->_checkTBCOfficeWorkDay;
    }


    /**
     * Set Flag indicating whether the shipment should be delivered on a half working day, if such a delivery date happens to be calculated.
     * @param boolean
     */
    public function setHalfWorkDayDelivery($halfWorkDayDelivery) {
        $this->_halfWorkDayDelivery = $halfWorkDayDelivery;
    }

    /**
     * Get Flag indicating whether the shipment should be delivered on a half working day, if such a delivery date happens to be calculated.
     * @return boolean
     */
    public function getHalfWorkDayDelivery() {
        return $this->_halfWorkDayDelivery;
    }

    /**
     * Set a flag indicating to ignore insuranceBase. Default value - true.
     * @param boolean
     */
    public function setIgnoreAmountInsuranceBaseIfNotApplicable($ignoreAmountInsuranceBaseIfNotApplicable) {
        $this->_ignoreAmountInsuranceBaseIfNotApplicable = $ignoreAmountInsuranceBaseIfNotApplicable;
    }

    /**
     * Get a flag to ignore insuranceBase.
     * @return boolean
     */
    public function getIgnoreAmountInsuranceBaseIfNotApplicable() {
        return $this->_ignoreAmountInsuranceBaseIfNotApplicable;
    }

    /**
     * Set Return money-transfer request amount
     * @param signed 64-bit real (nullable)
     */
    public function setRetMoneyTransferReqAmount($retMoneyTransferReqAmount) {
    	$this->_retMoneyTransferReqAmount = $retMoneyTransferReqAmount;
    }
    
    /**
     * Get Return money-transfer request amount
     * @return integer Signed 64-bit
     */
    public function getRetMoneyTransferReqAmount() {
    	return $this->_retMoneyTransferReqAmount;
    }

    /**
     * Set options before payment
     * @param ParamOptionsBeforePayment
     */
    public function setOptionsBeforePayment($optionsBeforePayment) {
    	$this->_optionsBeforePayment = $optionsBeforePayment;
    }
    
    /**
     * Get options before payment
     * @return 
     */
    public function getOptionsBeforePayment() {
    	return $this->_optionsBeforePayment;
    }





    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->takingDate               = $this->_takingDate;
        $stdClass->autoAdjustTakingDate     = $this->_autoAdjustTakingDate;
        $stdClass->serviceTypeId            = $this->_serviceTypeId;
        $stdClass->willBringToOfficeId      = $this->_willBringToOfficeId;
        $stdClass->broughtToOffice          = $this->_broughtToOffice;
        $stdClass->officeToBeCalledId       = $this->_officeToBeCalledId;
        $stdClass->toBeCalled               = $this->_toBeCalled;
        $stdClass->fixedTimeDelivery        = $this->_fixedTimeDelivery;
        $stdClass->deferredDeliveryWorkDays = $this->_deferredDeliveryWorkDays;
        $stdClass->amountInsuranceBase      = $this->_amountInsuranceBase;
        $stdClass->amountCodBase            = $this->_amountCodBase;
        $stdClass->payCodToThirdParty       = $this->_payCodToThirdParty;
        $stdClass->parcelsCount             = $this->_parcelsCount;
        $stdClass->weightDeclared           = $this->_weightDeclared;
        $stdClass->documents                = $this->_documents;
        $stdClass->fragile                  = $this->_fragile;
        $stdClass->palletized               = $this->_palletized;
        $stdClass->senderId                 = $this->_senderId;
        $stdClass->senderSiteId             = $this->_senderSiteId;
        $stdClass->receiverId               = $this->_receiverId;
        $stdClass->receiverSiteId           = $this->_receiverSiteId;
        $stdClass->payerType                = $this->_payerType;
        $stdClass->payerRefId               = $this->_payerRefId;
        $stdClass->payerTypeInsurance       = $this->_payerTypeInsurance;
        $stdClass->payerTypePackings        = $this->_payerTypePackings;
        $stdClass->payerRefInsuranceId      = $this->_payerRefInsuranceId;
        $stdClass->payerRefPackingsId       = $this->_payerRefPackingsId;
        $stdClass->specialDeliveryId        = $this->_specialDeliveryId;
        $stdClass->receiverCountryId        = $this->_receiverCountryId;
        $stdClass->receiverPostCode         = $this->_receiverPostCode;
        $stdClass->senderCountryId          = $this->_senderCountryId;
        $stdClass->senderPostCode           = $this->_senderPostCode;
        
        $arrStdClassParamParcelInfo = array();
        if (isset($this->_parcels)) {
            if (is_array($this->_parcels)) {
                for($i = 0; $i < count($this->_parcels); $i++) {
                    $arrStdClassParamParcelInfo[$i] = $this->_parcels[$i]->toStdClass();
                }
            } else {
                $arrStdClassParamParcelInfo[0] = $this->_parcels->toStdClass();
            }
        }
        $stdClass->parcels                  = $arrStdClassParamParcelInfo;
        $stdClass->includeShippingPriceInCod = $this->_includeShippingPriceInCod;
        $stdClass->checkTBCOfficeWorkDay = $this->_checkTBCOfficeWorkDay;
        
        if (isset($this->_halfWorkDayDelivery)) {
            $stdClass->halfWorkDayDelivery = $this->_halfWorkDayDelivery;
        }
        $stdClass->ignoreAmountInsuranceBaseIfNotApplicable = $this->_ignoreAmountInsuranceBaseIfNotApplicable;
        $stdClass->retMoneyTransferReqAmount = $this->_retMoneyTransferReqAmount;

        if (isset($this->_optionsBeforePayment)) {
        	   $stdClass->optionsBeforePayment = $this->_optionsBeforePayment->toStdClass();
        }

        return $stdClass;
    }
}
?>