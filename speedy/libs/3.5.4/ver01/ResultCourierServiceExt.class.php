<?php

require_once 'ResultCourierService.class.php';

/**
 * Instances of this class are returned as a result of Speedy web service calls for services alloweed between sites
 */
class ResultCourierServiceExt extends ResultCourierService {

    /**
     * The deadline for shipment delivery
     * @var datetime
     */
    private $_deliveryDeadline;

    /**
     * Specifies if the complementary service "Delivery to floor" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceDeliveryToFloor;

    /**
     * Specifies if the complementary service "Options Before Payment" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceOptionsBeforePayment;

    /**
     * Specifies if the complementary service "Return Voucher" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceReturnVoucher;

    /**
     * Shows if parcels require weight and size description
     * @var boolean
     * @since 3.3.2
     */
    private $_requireParcelsData;

    /**
     * Constructs new instance of ResultCourierServiceExt
     * @param stdClass $stdClassResultCourierServiceExt
     */
    function __construct($stdClassResultCourierServiceExt) {
        parent::__construct($stdClassResultCourierServiceExt);
        $this->_deliveryDeadline = isset($stdClassResultCourierServiceExt -> deliveryDeadline) ? $stdClassResultCourierServiceExt -> deliveryDeadline : null;
        $this->_allowanceDeliveryToFloor = isset($stdClassResultCourierServiceExt -> allowanceDeliveryToFloor) ? $stdClassResultCourierServiceExt -> allowanceDeliveryToFloor : null;
        $this->_allowanceOptionsBeforePayment = isset($stdClassResultCourierServiceExt -> allowanceOptionsBeforePayment) ? $stdClassResultCourierServiceExt -> allowanceOptionsBeforePayment : null;
        $this->_allowanceReturnVoucher = isset($stdClassResultCourierServiceExt -> allowanceReturnVoucher) ? $stdClassResultCourierServiceExt -> allowanceReturnVoucher : null;
        $this->_requireParcelsData = isset($stdClassResultCourierServiceExt -> requireParcelsData) ? $stdClassResultCourierServiceExt -> requireParcelsData : null;
   }

    /**
     * Get deadline for shipment delivery
     * @return datetime Deadline for shipment delivery
     */
    public function getDeliveryDeadline() {
        return $this->_deliveryDeadline;
    }

    /**
     * Get state of complementary service "Delivery to floor" is banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceDeliveryToFloor() {
        return $this->_allowanceDeliveryToFloor;
    }

    /**
     * Get state of complementary service "Options Before Payment" is banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceOptionsBeforePayment() {
        return $this->_allowanceOptionsBeforePayment;
    }

    /**
     * Get state of complementary service "Return Voucher" is banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceReturnVoucher() {
        return $this->_allowanceReturnVoucher;
    }

    /**
     * Shows if parcels require weight and size description
     * @return boolean
     */
    public function getRequireParcelsData() {
        return $this->_requireParcelsData;
    }

}
?>