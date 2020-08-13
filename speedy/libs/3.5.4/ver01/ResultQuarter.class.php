<?php

/**
 * Instances of this class are returned as a result of Speedy web service queries for quarters
 */
class ResultQuarter {

    /**
     * Quarter ID
     * @var integer Signed 64-bit
     */
    private $_id;

    /**
     * Quarter type
     * @var string
     */
    private $_type;

    /**
     * Quarter name
     * @var string
     */
    private $_name;

    /**
     * Actual name (in case "name" is an old name)
     * @var string
     */
    private $_actualName;

    /**
     * Constructs new instance of ResultQuarter
     * @param stdClass $stdClassResultQuarter
     */
    function __construct($stdClassResultQuarter) {
        $this->_id          = isset($stdClassResultQuarter->id)         ? $stdClassResultQuarter->id         : null;
        $this->_type        = isset($stdClassResultQuarter->type)       ? $stdClassResultQuarter->type       : null;
        $this->_name        = isset($stdClassResultQuarter->name)       ? $stdClassResultQuarter->name       : null;
        $this->_actualName  = isset($stdClassResultQuarter->actualName) ? $stdClassResultQuarter->actualName : null;
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
     * Get quarter actual name in case name is an old name
     * @return string Quarter actual name
     */
    public function getActualName() {
        return $this->_actualName;
    }
}
?>