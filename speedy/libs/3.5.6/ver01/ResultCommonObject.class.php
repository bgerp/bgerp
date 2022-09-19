<?php

/**
 * Instances of this class are returned as a result of Speedy web service queries for common objects
 */
class ResultCommonObject {

    /**
     * Common object ID
     * @var integer Signed 64-bit
     */
    private $_id;

    /**
     * Common object type
     * @var string
     */
    private $_type;

    /**
     * Common object name
     * @var string
     */
    private $_name;

    /**
     * Common object address
     * @var string
     */
    private $_address;

    /**
     * Constructs new instance of ResultCommonObject
     * @param stdClass $stdClassResultCommonObject
     */
    function __construct($stdClassResultCommonObject) {
        $this->_id      = isset($stdClassResultCommonObject->id)      ? $stdClassResultCommonObject->id      : null;
        $this->_type    = isset($stdClassResultCommonObject->type)    ? $stdClassResultCommonObject->type    : null;
        $this->_name    = isset($stdClassResultCommonObject->name)    ? $stdClassResultCommonObject->name    : null;
        $this->_address = isset($stdClassResultCommonObject->address) ? $stdClassResultCommonObject->address : null;
    }

    /**
     * Get quarter ID
     * @return integer Signed 64-bit quarter ID
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Get quarter type
     * @return string Quarter type
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * Get quarter name
     * @return string Quarter name
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Get common object address
     * @return string Common object address
     */
    public function getAddress() {
        return $this->_address;
    }
}
?>