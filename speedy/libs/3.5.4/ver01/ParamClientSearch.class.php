<?php
/**
 * Instances of this class are used as a parameter to search for Speedy clients
 * @since 1.6
 */
class ParamClientSearch {
    
    /**
     * Client id
     * MANDATORY: NO
     * @var integer signed 64-bit
     */
    private $_clientId;
    
    /**
     * Client name
     * MANDATORY: NO
     * @var string
     */
    private $_clientName;

    /**
     * Common object name
     * MANDATORY: NO
     * @var string
     */
    private $_objectName;

    /**
     * Client phone
     * MANDATORY: NO
     * @var string
     */
    private $_phone;

    /**
     * Site id
     * MANDATORY: NO
     * @var integer signed 64-bit
     */
    private $_siteId;

    /**
     * User def tag
     * MANDATORY: NO
     * @var string
     */
    private $_userDefTag;
    
    /**
     * Set client id
     * @param integer $clientId Signed 64 bit
     */
    public function setClientId($clientId) {
        $this->_clientId = $clientId;
    }
    
    /**
     * Get client id
     * @return integer Signed 64-bit
     */
    public function getClientId() {
        return $this->_clientId;
    }
    
    /**
     * Set client name
     * @param string $clientName
     */
    public function setClientName($clientName) {
        $this->_clientName = $clientName;
    }
    
    /**
     * Get client name
     * @return string client name
     */
    public function getClientName() {
        return $this->_clientName;
    }
    
    /**
     * Set object name
     * @param string $objectName
     */
    public function setObjectName($objectName) {
        $this->_objectName = $objectName;
    }
    
    /**
     * Get object name
     * @return string object name
     */
    public function getObjectName() {
        return $this->_objectName;
    }
    
    /**
     * Set phone
     * @param string $phone
     */
    public function setPhone($phone) {
        $this->_phone = $phone;
    }
    
    /**
     * Get phone
     * @return string phone
     */
    public function getPhone() {
        return $this->_phone;
    }
    
    /**
     * Set site id
     * @param integer $siteId Signed 64 bit
     */
    public function setSiteId($siteId) {
        $this->_siteId = $siteId;
    }
    
    /**
     * Get site id
     * @return integer Signed 64-bit
     */
    public function getSiteId() {
        return $this->_siteId;
    }
    
    /**
     * Set userDefTag
     * @param string $userDefTag
     */
    public function setUserDefTag($userDefTag) {
        $this->_userDefTag = $userDefTag;
    }
    
    /**
     * Get userDefTag
     * @return string userDefTag
     */
    public function getUserDefTag() {
        return $this->_userDefTag;
    }
    
    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->clientId   = $this->_clientId;
        $stdClass->clientName = $this->_clientName;
        $stdClass->objectName = $this->_objectName;
        $stdClass->phone      = $this->_phone;
        $stdClass->siteId     = $this->_siteId;
        $stdClass->userDefTag = $this->_userDefTag;
        return $stdClass;
    }
}
?>