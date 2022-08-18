<?php

require_once 'ParamAddress.class.php';
require_once 'ParamPhoneNumber.class.php';

/**
 * Instances of this class are used as a parameter for speedy web service method calls for picking calculation and registration
 */
class ParamClientData {

    /**
     * Client/Partner ID
     * MANDATORY: NO
     * @var integer Signed 64-bit
     */
    private $_clientId;

    /**
     * Name of the client (company or private person).
     * Maximum size is 60 symbols.
     * MANDATORY: Must be set <=> clientId is null.
     * @var string
     */
    private $_partnerName;

    /**
     * Company department/office.
     * Maximum size is 60 symbols.
     * MANDATORY: Allowed <=> clientId is null.
     * @var string
     */
    private $_objectName;

    /**
     * Address details
     * MANDATORY: Required when clientId is null
     * @var ParamAddress
     */
    private $_address;

    /**
     * Contact name.
     * Maximum size is 60 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_contactName;

    /**
     * Phone numbers.
     * This list contains maximum 3 phone numbers.
     * MANDATORY: Sender's phone number is always required.
     *   Receiver's phone number is required if the shipment is to be delivered on a half-working day or
     *   the shipment needs to be delivered the day it has been picked up.
     *   ("Required" means at least one valid phone number must be set.)
     * @var array List of ParamPhoneNumber
     */
    private $_phones;
    
    /**
     * Email
     * Maximum size is 256 symbols.
     * MANDATORY: NO
     * @var string
     * 
     * @since 2.1.0
     */
    private $_email;

    /**
     * Private Person Type
     * MANDATORY: NO
     * @var signed 32-bit integer (nullable)
     * 
     * @since 3.2.1
	   */
    private $_privatePersonType;


    /**
     * Set client/partner ID
     * @param integer $clientId Signed 64-bit
     */
    public function setClientId($clientId) {
        $this->_clientId = $clientId;
    }

    /**
     * Get client/partner ID
     * @return integer Signed 64-bit
     */
    public function getClientId() {
        return $this->_clientId;
    }

    /**
     * Set name of the client (company or private person).
     * Maximum size is 60 symbols.
     * @param string $partnerName
     */
    public function setPartnerName($partnerName) {
        $this->_partnerName = $partnerName;
    }

    /**
     * Get name of the client (company or private person).
     * @return string
     */
    public function getPartnerName() {
        return $this->_partnerName;
    }

    /**
     * Set company department/office.
     * Maximum size is 60 symbols.
     * @param string $objectName
     */
    public function setObjectName($objectName) {
        $this->_objectName = $objectName;
    }

    /**
     * Get company department/office.
     * @return string
     */
    public function getObjectName() {
        return $this->_objectName;
    }

    /**
     * Set address details
     * @param ParamAddress $address
     */
    public function setAddress($address) {
        $this->_address = $address;
    }

    /**
     * Get address details
     * @return ParamAddress
     */
    public function getAddress() {
        return $this->_address;
    }

    /**
     * Set contact name. Maximum size is 60 symbols.
     * @param string $contactName
     */
    public function setContactName($contactName) {
        $this->_contactName = $contactName;
    }

    /**
     * Get contact name.
     * @return string
     */
    public function getContactName() {
        return $this->_contactName;
    }

    /**
     * Set phone numbers. This list contains maximum 3 phone numbers.
     * Sender's phone number is always required. Receiver's phone number is required if the shipment is to be delivered on a half-working day or
     * the shipment needs to be delivered the day it has been picked up.
     * ("Required" means at least one valid phone number must be set.)
     * @param array $phones List of ParamPhoneNumber
     */
    public function setPhones($phones) {
        $this->_phones = $phones;
    }

    /**
     * Get phone numbers.
     * @return array List of ParamPhoneNumber
     */
    public function getPhones() {
        return $this->_phones;
    }
    
    /**
     * Set email
     * @param string $email Email address to set
     * @since 2.1.0
     */
    public function setEmail($email) {
    	$this->_email = $email;
    }
    
    /**
     * Get email
     * @return string Email address
     * @since 2.1.0
     */
    public function getEmail() {
    	return $this->_email;
    }

    /**
     * Set Private Person Type
     * @param string $privatePersonType Private Person Type to set
     * @since 3.2.1
     */
    public function setPrivatePersonType($privatePersonType) {
    	$this->_privatePersonType = $privatePersonType;
    }
    
    /**
     * Get Private Person Type
     * @return signed 32-bit integer (nullable)
     * @since 3.2.1
     */
    public function getPrivatePersonType() {
    	return $this->_privatePersonType;
    }


    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->clientId    = $this->_clientId;
        $stdClass->partnerName = $this->_partnerName;
        $stdClass->objectName  = $this->_objectName;
        if (isset($this->_address)) {
            $stdClass->address = $this->_address->toStdClass();
        }
        $stdClass->contactName = $this->_contactName;
        $arrStdClassParamPhoneNumber = array();
        if (isset($this->_phones)) {
            if (is_array($this->_phones)) {
                for($i = 0; $i < count($this->_phones); $i++) {
                    $arrStdClassParamPhoneNumber[$i] = $this->_phones[$i]->toStdClass();
                }
            } else {
                $arrStdClassParamPhoneNumber[0] = $this->_phones->toStdClass();
            }
        }
        $stdClass->phones = $arrStdClassParamPhoneNumber;
        $stdClass->email = $this->_email;
        $stdClass->privatePersonType = $this->_privatePersonType;
        return $stdClass;
    }
}
?>