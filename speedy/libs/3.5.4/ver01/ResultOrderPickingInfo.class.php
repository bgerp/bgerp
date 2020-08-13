<?php
/**
 * Instances of this class are returned as a result of order web service class
 */
class ResultOrderPickingInfo {

    /**
     * BOL number
     * @var integer signed 64-bit
     */
    private $_billOfLading;

    /**
     * A list of validation errors (empty list means there is no problem with this BOL)
     * @var array List of strings
     */
    private $_errorDescriptions;

    /**
     * Constructs new instance of ResultOrderPickingInfo from stdClass
     * @param stdClass $stdResultOrderPickingInfo
     */
    function __construct($stdResultOrderPickingInfo) {
        $this->_billOfLading = isset($stdResultOrderPickingInfo->billOfLading) ? $stdResultOrderPickingInfo->billOfLading : null;
        $arrErrorDescriptions = array();
        if (isset($stdResultOrderPickingInfo->errorDescriptions)) {
            if (is_array($stdResultOrderPickingInfo->errorDescriptions)) {
                for($i = 0; $i < count($stdResultOrderPickingInfo->errorDescriptions); $i++) {
                    $arrErrorDescriptions[$i] = $stdResultOrderPickingInfo->errorDescriptions[$i];
                }
            } else {
                $arrErrorDescriptions[0] = $stdResultOrderPickingInfo->errorDescriptions;
            }
        }
        $this->_errorDescriptions = $arrErrorDescriptions;
    }

    /**
     * Get BOL number
     * @return integer signed 64-bit
     */
    public function getBillOfLading() {
        return $this->_billOfLading;
    }

    /**
     * Get list of validation errors (empty list means there is no problem with this BOL)
     * @return array List of strings
     */
    public function getErrorDescriptions() {
        return $this->_errorDescriptions;
    }
}
?>