<?php
/**
 * Instances of this class are used as a parameter for client phone numbers in web service calls
 */
class ParamPhoneNumber {

    /**
     * Phone number (example: "0888123456", "+35932261020" etc.).
     * Max size is 20 symbols.
     * MANDATORY: YES
     * @var string
     */
    private $_number;

    /**
     * An extension number.
     * Max size is 10 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_internal;

    /**
     * Set phone number (example: "0888123456", "+35932261020" etc.). Max size is 20 symbols.
     * @param string $number
     */
    public function setNumber($number) {
        $this->_number = $number;
    }

    /**
     * Get phone number
     * @return string
     */
    public function getNumber() {
        return $this->_number;
    }

    /**
     * Set extension number. Max size is 10 symbols.
     * @param string $internal
     */
    public function setInternal($internal) {
        $this->_internal = $internal;
    }

    /**
     * Get extension number.
     * @return string
     */
    public function getInternal() {
        return $this->_internal;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->number   = $this->_number;
        $stdClass->internal = $this->_internal;
        return $stdClass;
    }
}
?>