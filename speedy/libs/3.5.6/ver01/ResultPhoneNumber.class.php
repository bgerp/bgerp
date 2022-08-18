<?php
/**
 * Instances of this class are returned as part of client data in Speedy web service method calls
 */
class ResultPhoneNumber {

    /**
     * Phone number (example: "0888123456", "+35932261020" etc.)
     * @var string
     */
    private $_number;

    /**
     * An extension number
     * @var string
     */
    private $_internal;

    /**
     * Constructs new instance of ResultPhoneNumber
     * @param stdClass $stdClassResultPhoneNumber
     */
    function __construct($stdClassResultPhoneNumber) {
        $this->_number   = isset($stdClassResultPhoneNumber->number)   ? $stdClassResultPhoneNumber->number   : null;
        $this->_internal = isset($stdClassResultPhoneNumber->internal) ? $stdClassResultPhoneNumber->internal : null;
    }

    /**
     * Get phone number
     * @return string
     */
    public function getNumber() {
        return $this->_number;
    }

    /**
     * Get extension number
     * @return string
     */
    public function getInternal() {
        return $this->_internal;
    }
}
?>