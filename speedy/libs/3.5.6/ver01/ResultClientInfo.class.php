<?php

require_once 'ResultAddress.class.php';
require_once 'ResultPhoneNumber.class.php';

/**
 * Instances of this class are returned as a result of Speedy web service method calls for client data info (from ResultPickingExtendedInfo)
 */
class ResultClientInfo {

    /**
     * Client ID
     * @var integer Signed 64-bit (nullable)
     */
    private $_clientId;

    /**
     * Name of the client (company or private person)
     * @var string
     */
    private $_partnerName;

    /**
     * Company department/office
     * @var string
     */
    private $_objectName;

    /**
     * Address
     * @var ResultAddress
     */
    private $_address;

    /**
     * Contact name
     * @var string
     */
    private $_contactName;

    /**
     * Phone numbers
     * @var array ResultPhoneNumber
     */
    private $_phones;

    /**
     * Constructs new instance of ResultClientInfo
     * @param stdClass $stdClassResultClientInfo
     */
    function __construct($stdClassResultClientInfo) {
        $this->_clientId    = isset($stdClassResultClientInfo->clientId)    ? $stdClassResultClientInfo->clientId                   : null;
        $this->_partnerName = isset($stdClassResultClientInfo->partnerName) ? $stdClassResultClientInfo->partnerName                : null;
        $this->_objectName  = isset($stdClassResultClientInfo->objectName)  ? $stdClassResultClientInfo->objectName                 : null;
        $this->_address     = isset($stdClassResultClientInfo->address)     ? new ResultAddress($stdClassResultClientInfo->address) : null;
        $this->_contactName = isset($stdClassResultClientInfo->contactName) ? $stdClassResultClientInfo->contactName                : null;
        $arrResultPhoneNumbers = array();
        if (isset($stdClassResultClientInfo->phones)) {
            if (is_array($stdClassResultClientInfo->phones)) {
                for($i = 0; $i < count($stdClassResultClientInfo->phones); $i++) {
                    $arrResultPhoneNumbers[$i] = new ResultPhoneNumber($stdClassResultClientInfo->phones[$i]);
                }
            } else {
                $arrResultPhoneNumbers[0] = new ResultPhoneNumber($stdClassResultClientInfo->phones);
            }
        }
        $this->_phones = $arrResultPhoneNumbers;
    }

    /**
     * Get client ID
     * @return integer Signed 64-bit (nullable)
     */
    public function getClientId() {
        return $this->_clientId;
    }

    /**
     * Get name of the client (company or private person)
     * @return string
     */
    public function getPartnerName() {
        return $this->_partnerName;
    }

    /**
     * Get company department/office
     * @return string
     */
    public function getObjectName() {
        return $this->_objectName;
    }

    /**
     * Get address
     * @return ResultAddress
     */
    public function getAddress() {
        return $this->_address;
    }

    /**
     * Get contact name
     * @return string
     */
    public function getContactName() {
        return $this->_contactName;
    }

    /**
     * Get phone numbers
     * @return array ResultPhoneNumber
     */
    public function getPhones() {
        return $this->_phones;
    }
}
?>