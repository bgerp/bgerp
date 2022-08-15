<?php

/**
 * @since 3.5.2
 */
require_once 'ReturnAmountDetails.class.php';

/**
 * Instances of this class are returned as a result of Speedy caclulcation web service methods
 */
class ResultReturnAmounts {

    /**
     * Money transfer premium information
     * @access private
     * @var ResultAmountDetails structure
     */
    private $_moneyTransferPremium;

    /**
     * Constructs new instance of ResultAmountDetails from stdClass
     * @param stdClass stdClassResultAmountDetails
     */
    function __construct($stdClassResultAmountDetails) {
        $this -> _moneyTransferPremium = isset($stdClassResultAmountDetails -> moneyTransferPremium) ? new ReturnAmountDetails($stdClassResultAmountDetails -> moneyTransferPremium) : null;
    }

    /**
     * Set Money transfer premium information
     * @param ResultReturnAmounts
     */
    public function setMoneyTransferPremium($moneyTransferPremium) {
        $this->_moneyTransferPremium = $moneyTransferPremium;
    }
    
    /**
     * Get Money transfer premium information
     * @return ResultAmounts Structure with amount values that form the price
     */
    public function getMoneyTransferPremium() {
        return $this->_moneyTransferPremium;
    }

}
?>