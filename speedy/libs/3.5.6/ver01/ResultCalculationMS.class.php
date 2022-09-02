<?php
/**
 * Instances of this class are returned as a result of calculation Speedy web service requestst for multiple services
 */
class ResultCalculationMS {

    /**
     * Courier service type ID
     * @var integer Signed 64-bit
     */
    private $_serviceTypeId;

    /**
     * Validation error during calculation attempt
     * @var string
     */
    private $_errorDescription;

    /**
     * The result of calculation (in case no error has occurred)
     * @var ResultCalculation
     */
    private $_resultInfo;

    /**
     * Constructs new instance of ResultCalculationMS from stdClass
     * @param stdClass $stdClassResultCalculationMS
     */
    function __construct($stdClassResultCalculationMS) {
        $this->_serviceTypeId    = isset($stdClassResultCalculationMS->serviceTypeId)    ? $stdClassResultCalculationMS->serviceTypeId                     : null;
        $this->_errorDescription = isset($stdClassResultCalculationMS->errorDescription) ? $stdClassResultCalculationMS->errorDescription                  : null;
        $this->_resultInfo       = isset($stdClassResultCalculationMS->resultInfo)       ? new ResultCalculation($stdClassResultCalculationMS->resultInfo) : null;
    }

    /**
     * Get courier service type ID
     * @return integer Signed 64-bit
     */
    public function getServiceTypeId() {
        return $this->_serviceTypeId;
    }

    /**
     * Get validation error during calculation attempt
     * @return string
     */
    public function getErrorDescription() {
        return $this->_errorDescription;
    }

    /**
     * Get result of calculation (in case no error has occurred)
     * @return ResultCalculation
     */
    public function getResultInfo() {
        return $this->_resultInfo;
    }
}
?>