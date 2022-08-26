<?php
/**
 * Instances of this class are returned as a result of services for special delivery requirements
 * 
 * @since 2.1.0
 */
class ResultSpecialDeliveryRequirement {

    /**
     * A special delivery ID
     * @var integer 64-bit
     */
    private $_specialDeliveryId;

    /**
     * A special delivery description
     * @var string
     */
    private $_specialDeliveryText;
    
    /**
     * A special delivery price
     * @var double (signed 64-bit)
     */
    private $_specialDeliveryPrice;

    /**
     * Constructs new instance of ResultSpecialDeliveryRequirement
     * @param stdClass $stdClassResultSpecialDeliveryRequirement
     */
    function __construct($stdClassResultSpecialDeliveryRequirement) {
        $this->_specialDeliveryId = isset($stdClassResultSpecialDeliveryRequirement->specialDeliveryId)       ? $stdClassResultSpecialDeliveryRequirement->specialDeliveryId    : null;
        $this->_specialDeliveryText = isset($stdClassResultSpecialDeliveryRequirement->specialDeliveryText)   ? $stdClassResultSpecialDeliveryRequirement->specialDeliveryText  : null;
        $this->_specialDeliveryPrice = isset($stdClassResultSpecialDeliveryRequirement->specialDeliveryPrice) ? $stdClassResultSpecialDeliveryRequirement->specialDeliveryPrice : null;
    }

    /**
     * Get special delivery id
     * @return integer (64-bit)
     */
    public function getSpecialDeliveryId() {
        return $this->_specialDeliveryId;
    }

    /**
     * Get special delivery text
     * @return string
     */
    public function getSpecialDeliveryText() {
        return $this->_specialDeliveryText;
    }
    
    /**
     * Get special delivery price
     * @return double signed 64-bit
     */
    public function getSpecialDeliveryPrice() {
    	return $this->_specialDeliveryPrice;
    }
}
?>