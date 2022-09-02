<?php

require_once 'ResultAmounts.class.php';

/**
 * Instances of this class are returned as a result of Speedy caclulcation web service methods
 */
class ResultCalculation {

    /**
     * Shipment's price (structure with values that form the price)
     * @access private
     * @var ResultAmounts Amounts structure
     */
    private $_amounts;

    /**
     * The pick-up date
     * @access private
     * @var date
     */
    private $_takingDate;

    /**
     * Deadline for delivery
     * @access private
     * @var date
     */
    private $_deadlineDelivery;

    /**
     * Specifies if the discounts are potentially partial (the final discounts might be bigger depending on the other participants' contracts).
     * @access private
     * @var boolean
     */
    private $_partialDiscount;

    /**
     * Constructs new instance of ResultCalculation from stdClass
     * @param stdClass $stdClassResultCalculation
     */
    function __construct($stdClassResultCalculation) {
        $this->_amounts          = isset($stdClassResultCalculation->amounts)          ? new ResultAmounts($stdClassResultCalculation->amounts) : null;
        $this->_takingDate       = isset($stdClassResultCalculation->takingDate)       ? $stdClassResultCalculation->takingDate                 : null;
        $this->_deadlineDelivery = isset($stdClassResultCalculation->deadlineDelivery) ? $stdClassResultCalculation->deadlineDelivery           : null;
        $this->_partialDiscount  = isset($stdClassResultCalculation->partialDiscount)  ? $stdClassResultCalculation->partialDiscount            : null;
    }

    /**
     * Get shipment's price
     * @return ResultAmounts Structure with amount values that form the price
     */
    public function getAmounts() {
        return $this->_amounts;
    }

    /**
     * Get pick-up date
     * @return date
     */
    public function getTakingDate() {
        return $this->_takingDate;
    }

    /**
     * Get deadline for delivery
     * @return date
     */
    public function getDeadlineDelivery() {
        return $this->_deadlineDelivery;
    }

    /**
     * Get partial discounts flag
     * @return boolean
     */
    public function isPartialDiscount() {
        return $this->_partialDiscount;
    }
}
?>