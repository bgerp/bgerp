<?php
/**
 * Instances of this class are used as parameters in web service calls for picking calculation and registration
 */
class FixedDiscountCardId {

    /**
     * Agreement (contract) ID
     * MANDATORY: NO
     * @var integer Signed 32-bit
     */
    private $_agreementId;

    /**
     * Card ID
     * @var integer Signed 32-bit
     */
    private $_cardId;

    /**
     * Set agreement (contract) ID
     * @param integer $agreementId Signed 32-bit
     */
    public function setAgreementId($agreementId) {
        $this->_agreementId = $agreementId;
    }

    /**
     * Get agreement (contract) ID
     * @return integer Signed 32-bit
     */
    public function getAgreementId() {
        return $this->_agreementId;
    }

    /**
     * Set card ID
     * @param integer $cardId Signed 32-bit
     */
    public function setCardId($cardId) {
        $this->_cardId = $cardId;
    }

    /**
     * Get card ID
     * @return integer Signed 32-bit
     */
    public function getCardId() {
        return $this->_cardId;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->agreementId  = $this->_agreementId;
        $stdClass->cardId       = $this->_cardId;
        return $stdClass;
    }
}
?>