<?php
/**
 * Instances of this class are returned as a result of services for allowed float ranges of certain parameters
 * (f.e. getWightInterval)
 */
class ResultMinMaxReal {

    /**
     * Min value
     * @var double 64-bit
     */
    private $_minValue;

    /**
     * Max value
     * @var double 64-bit
     */
    private $_maxValue;

    /**
     * Constructs new instance of ResultMinMaxReal
     * @param stdClass $stdClassResultMinMaxReal
     */
    function __construct($stdClassResultMinMaxReal) {
        $this->_minValue = isset($stdClassResultMinMaxReal->minValue) ? $stdClassResultMinMaxReal->minValue : null;
        $this->_maxValue = isset($stdClassResultMinMaxReal->maxValue) ? $stdClassResultMinMaxReal->maxValue : null;
    }

    /**
     * Get min value
     * @return double 64-bit
     */
    public function getMinValue() {
        return $this->_minValue;
    }

    /**
     * Get max value
     * @return double 64-bit
     */
    public function getMaxValue() {
        return $this->_maxValue;
    }
}
?>