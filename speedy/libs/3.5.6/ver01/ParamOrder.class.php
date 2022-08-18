<?php

require_once 'ParamPhoneNumber.class.php';

/**
 * Instances of this class are used as parameters for orders web service calls
 */
class ParamOrder {

    /**
     * Include in order list all explicitly provided numbers with setBillOfLadingsList method
     * @var integer Signed 32-bit
     */
    const ORDER_BOL_INCLUDE_TYPE_EXPLICIT = 10;

    /**
     * Include in order list all not-ordered-yet BOLs created by the logged client
     * @var integer Signed 32-bit
     */
    const ORDER_BOL_INCLUDE_TYPE_OWN_PENDING = 20;

    /**
     * Include in order list all not-ordered-yet BOLs created by the logged client or members of his/her contract.
     * (taking into account user's permissions)
     * @var integer Signed 32-bit
     */
    const ORDER_BOL_INCLUDE_TYPE_CONTARCT_PENDIND = 30;

    /**
     * Specifies the set of shipments/BOLs to be ordered:
     * •[10] Explicit numbers (in billOfLadingsList)
     * •[20] All not-ordered-yet BOLs created by the logged client
     * •[30] All not-ordered-yet BOLs created by the logged client or members of his/her contract (taking into account user's permissions)
     * MANDATORY: YES
     * @var integer Signed 32-bit
     */
    private $_billOfLadingsToIncludeType;

    /**
     * List of BOL numbers.
     * MANDATORY: Must be set <=> billOfLadingsToIncludeType = 10.
     * @var array List of signed 64-bit integer
     */
    private $_billOfLadingsList;

    /**
     * The date for shipments pick-up (the "time" component is ignored). The default value is "today".
     * MANDATORY: NO
     * @var date
     */
    private $_pickupDate;

    /**
     * Specifies when all the shipments/parcels will be ready for pickup. The default value is "now".
     * MANDATORY: Only if pickupDate > today
     * @var integer Signed 16-bit
     */
    private $_readinessTime;

    /**
     * The sender's working time end
     * MANDATORY: YES
     * @var integer Signed 16-bit
     */
    private $_workingEndTime;

    /**
     * Contact name.
     * Limited to 60 symbols.
     * MANDATORY: YES
     * @var string
     */
    private $_contactName;

    /**
     * Phone number
     * MANDATORY: YES
     * @var ParamPhoneNumber
     */
    private $_phoneNumber;

    /**
     * Set the set of shipments/BOLs to be ordered:
     * •[10] Explicit numbers (in billOfLadingsList)
     * •[20] All not-ordered-yet BOLs created by the logged client
     * •[30] All not-ordered-yet BOLs created by the logged client or members of his/her contract (taking into account user's permissions)
     * @param integer $billOfLadingsToIncludeType Signed 32-bit
     */
    public function setBillOfLadingsToIncludeType($billOfLadingsToIncludeType) {
        $this->_billOfLadingsToIncludeType = $billOfLadingsToIncludeType;
    }

    /**
     * Get the set of shipments/BOLs to be ordered:
     * •[10] Explicit numbers (in billOfLadingsList)
     * •[20] All not-ordered-yet BOLs created by the logged client
     * •[30] All not-ordered-yet BOLs created by the logged client or members of his/her contract (taking into account user's permissions)
     * @return integer Signed 32-bit
     */
    public function getBillOfLadingsToIncludeType() {
        return $this->_billOfLadingsToIncludeType;
    }

    /**
     * Set list of BOL numbers.
     * Must be set <=> billOfLadingsToIncludeType = 10.
     * @param array $billOfLadingsList List of signed 64-bit
     */
    public function setBillOfLadingsList($billOfLadingsList) {
        $this->_billOfLadingsList = $billOfLadingsList;
    }

    /**
     * Get list of BOL numbers.
     * @return array List of signed 64-bit
     */
    public function getBillOfLadingsList() {
        return $this->_billOfLadingsList;
    }

    /**
     * Set date for shipments pick-up (the "time" component is ignored)
     * @param date $pickupDate
     */
    public function setPickupDate($pickupDate) {
        $this->_pickupDate = $pickupDate;
    }

    /**
     * Get date for shipments pick-up
     * @return date
     */
    public function getPickupDate() {
        return $this->_pickupDate;
    }

    /**
     * Set the time when all the shipments/parcels will be ready for pickup.
     * @param iteger $readinessTime Signed 16-bit
     */
    public function setReadinessTime($readinessTime) {
        $this->_readinessTime = $readinessTime;
    }

    /**
     * Get the time when all the shipments/parcels will be ready for pickup.
     * @return iteger Signed 16-bit
     */
    public function getReadinessTime() {
        return $this->_readinessTime;
    }

    /**
     * Set sender's working time end
     * @param iteger $workingEndTime Signed 16-bit
     */
    public function setWorkingEndTime($workingEndTime) {
        $this->_workingEndTime = $workingEndTime;
    }

    /**
     * Get sender's working time end
     * @return iteger Signed 16-bit
     */
    public function getWorkingEndTime() {
        return $this->_workingEndTime;
    }

    /**
     * Set contact name - limited to 60 symbols.
     * @param string $contactName
     */
    public function setContactName($contactName) {
        $this->_contactName = $contactName;
    }

    /**
     * Get contact name
     * @return string
     */
    public function getContactName() {
        return $this->_contactName;
    }

    /**
     * Set phone number.
     * @param ParamPhoneNumber $phoneNumber
     */
    public function setPhoneNumber($phoneNumber) {
        $this->_phoneNumber = $phoneNumber;
    }

    /**
     * Get phone number.
     * @return ParamPhoneNumber
     */
    public function getPhoneNumber() {
        return $this->_phoneNumber;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->billOfLadingsToIncludeType = $this->_billOfLadingsToIncludeType;
        $stdClass->billOfLadingsList          = $this->_billOfLadingsList;
        $stdClass->pickupDate                 = $this->_pickupDate;
        $stdClass->readinessTime              = $this->_readinessTime;
        $stdClass->workingEndTime             = $this->_workingEndTime;
        $stdClass->contactName                = $this->_contactName;
        if (isset($this->_phoneNumber)) {
            $stdClass->phoneNumber = $this->_phoneNumber->toStdClass();
        }
        return $stdClass;
    }
}
?>