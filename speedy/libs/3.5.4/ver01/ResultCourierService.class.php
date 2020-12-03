<?php

require_once 'ComplementaryServiceAllowance.class.php';

/**
 * Instances of this class are returned as a result of Speedy web service calls for services
 */
class ResultCourierService {

    /**
     * Courier service type ID
     * @var integer Signed 64-bit
     */
    private $_typeId;

    /**
     * Courier service name
     * @var string
     */
    private $_name;

    /**
     * Specifies if the complementary service "Fixed time for delivery" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceFixedTimeDelivery;

    /**
     * Specifies if the complementary service "COD" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceCashOnDelivery;

    /**
     * Specifies if the complementary service "Insurance" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceInsurance;

    /**
     * Specifies if the complementary service "Return documents" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceBackDocumentsRequest;

    /**
     * Specifies if the complementary service "Return receipt" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceBackReceiptRequest;

    /**
     * Specifies if the complementary service "To be called" is banned, allowed or required
     * @var ComplementaryServiceAllowance
     */
    private $_allowanceToBeCalled;

    /**
     * Cargo type
     * @var integer Signed 32-bit
     */
    private $_cargoType;

    /**
     * Constructs new instance of ResultCourierService
     * @param stdClass $stdClassResultCourierService
     */
    function __construct($stdClassResultCourierService) {
        $this->_typeId                        = isset($stdClassResultCourierService->typeId) ? $stdClassResultCourierService->typeId : null;
        $this->_name                          = isset($stdClassResultCourierService->name)   ? $stdClassResultCourierService->name   : null;
        $this->_allowanceFixedTimeDelivery    = isset($stdClassResultCourierService->allowanceFixedTimeDelivery) ? new ComplementaryServiceAllowance($stdClassResultCourierService->allowanceFixedTimeDelivery) : null;
        $this->_allowanceCashOnDelivery       = isset($stdClassResultCourierService->allowanceCashOnDelivery) ? new ComplementaryServiceAllowance($stdClassResultCourierService->allowanceCashOnDelivery) : null;
        $this->_allowanceInsurance            = isset($stdClassResultCourierService->allowanceInsurance) ? new ComplementaryServiceAllowance($stdClassResultCourierService->allowanceInsurance) : null;
        $this->_allowanceBackDocumentsRequest = isset($stdClassResultCourierService->allowanceBackDocumentsRequest) ? new ComplementaryServiceAllowance($stdClassResultCourierService->allowanceBackDocumentsRequest) : null;
        $this->_allowanceBackReceiptRequest   = isset($stdClassResultCourierService->allowanceBackReceiptRequest) ? new ComplementaryServiceAllowance($stdClassResultCourierService->allowanceBackReceiptRequest) : null;
        $this->_allowanceToBeCalled           = isset($stdClassResultCourierService->allowanceToBeCalled) ? new ComplementaryServiceAllowance($stdClassResultCourierService->allowanceToBeCalled) : null;
        $this->_cargoType                     = isset($stdClassResultCourierService->cargoType) ? $stdClassResultCourierService->cargoType : null;
    }

    /**
     * Get courier service type ID
     * @return integer Signed 64-bit
     */
    public function getTypeId() {
        return $this->_typeId;
    }

    /**
     * Get courier service name
     * @return string
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Get state of complementary service "Fixed time for delivery" - banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceFixedTimeDelivery() {
        return $this->_allowanceFixedTimeDelivery;
    }

    /**
     * Get state of complementary service "COD" - banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceCashOnDelivery() {
        return $this->_allowanceCashOnDelivery;
    }

    /**
     * Get state of complementary service "Insurance" - banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceInsurance() {
        return $this->_allowanceInsurance;
    }

    /**
     * Get state of complementary service "Return documents" - banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceBackDocumentsRequest() {
        return $this->_allowanceBackDocumentsRequest;
    }

    /**
     * Get state of complementary service "Return receipt" - banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceBackReceiptRequest() {
        return $this->_allowanceBackReceiptRequest;
    }

    /**
     * Get state of complementary service "To be called" - banned, allowed or required
     * @return ComplementaryServiceAllowance
     */
    public function getAllowanceToBeCalled() {
        return $this->_allowanceToBeCalled;
    }

    /**
     * Get service carto type
     * @return integer Signed 32-bit
     */
    public function getCargoType() {
        return $this->_cargoType;
    }

}
?>