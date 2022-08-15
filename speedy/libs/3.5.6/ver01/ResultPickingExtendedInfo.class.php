<?php

require_once 'ResultParcelInfoEx.class.php';
require_once 'ResultOptionsBeforePayment.class.php';
require_once 'ResultPackings.class.php';
require_once 'ResultReturnVoucher.class.php';
require_once 'ResultDeliveryInfo.class.php';
require_once 'CODPayment.class.php';
require_once 'ResultReturnShipmentRequest.class.php';
require_once 'ResultReturnServiceRequest.class.php';
require_once 'MoneyTransferPayment.class.php';


/**
 * Instances of this class are returned as a result of getPickingExtendedInfo
 */
class ResultPickingExtendedInfo {

    /**
     * BOL of the secondary shipment.
     * @var integer Signed 64-bit
     */
    private $_billOfLading;

    /**
     * The date for shipment pick-up (the "time" component is ignored). Default value is "today".
     * @var date Taking date
     */
    private $_takingDate;
    
    /**
     * Courier service type ID
     * @var integer Signed 64-bit
     */
    private $_serviceTypeId;

    /**
     * ID of an office "to be called". Non-null and non-zero value indicates this picking as "to office". Otherwise "to address" is considered.
     * var 64-bit integer (nullable)
     */
    private $_officeToBeCalledId;

    /**
     * Fixed time for delivery ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.).
     * var signed 16-bit integer (nullable)
     */
    private $_fixedTimeDelivery;

    /**
     * Deferred delivery work days
     * var signed 32-bit integer (nullable)
     */
    private $_deferredDeliveryWorkDays;

    /**
     * Indicates if the shipment has a "request for return documents"
     * var signed boolean
     */
    private $_backDocumentsRequest;

    /**
     * Indicates if the shipment has a "request for return receipt"
     * var signed boolean
     */
    private $_backReceiptRequest;

    /**
     * ID of an office where the sender intends to deliver the shipment by him/herself. Non-null and non-zero value indicates this picking as "to office". Otherwise "from address" is considered.
     * var signed long
     */
    private $_willBringToOfficeId;

    /**
     * COD value is to be paid to a third party
     * var signed boolean
     */
    private $_payCodToThirdParty;

    /**
     * Money-transfer request amount
     * var signed 64-bit real (nullable)
     */
    private $_retMoneyTransferReqAmount;
    
    /**
     * Parcels count
     * var signed 32-bit integer
     */
    private $_parcelsCount;

    /**
     * Declared weight (the greater of "volume" and "real" weight values)
     * var signed 64-bit real
     */
    private $_weightDeclared;

    /**
     * Measured weight
     * var signed 64-bit real (nullable)
     */
    private $_weightMeasured;

    /**
     * Calculation weight
     * var signed 64-bit real (nullable)
     */
    private $_weightCalculation;

    /**
     * Contents
     * var signed string
     */
    private $_contents;

    /**
     * Packing
     * var signed string
     */
    private $_packing;

    /**
     * Indicates whether the shipment consists of documents
     * var signed boolean
     */
    private $_documents;

    /**
     * Indicates whether the shipment is fragile
     * var signed boolean
     */
    private $_fragile;

    /**
     * Indicates whether the shipment is palletized
     * var signed boolean
     */
    private $_palletized;

    /**
     * Data for the sender
     * var sender
     */
    private $_sender;

    /**
     * Data for the receiver
     * var receiver
     */
    private $_receiver;

    /**
     * Payer type (0=sender, 1=receiver or 2=third party)
     * var signed 32-bit integer
     */
    private $_payerType;

    /**
     * Payer ID
     * var signed 64-bit integer (nullable)
     */
    private $_payerRefId;

    /**
     * Insurance payer type (0=sender, 1=reciever or 2=third party)
     * var signed 64-bit integer (nullable)
     */
    private $_payerTypeInsurance;

    /**
     * Insurance payer ID
     * var signed 64-bit integer (nullable)
     */
    private $_payerRefInsuranceId;

    /**
     * Packings payer type (0=sender, 1=reciever or 2=third party)
     * var signed 32-bit integer (nullable)
     */
    private $_payerTypePackings;

    /**
     * Packings payer ID
     * var signed 64-bit integer (nullable)
     */
    private $_payerRefPackingsId;

    /**
     * Client's note
     * var signed string
     */
    private $_noteClient;

    /**
     * Card/Coupon/Voucher number for fixed discount
     * var signed FixedDiscountCardId
     */
    private $_discCalc;

    /**
     * ID of the client who is to receive the return receipt and/or the return documents.
     * var signed signed 64-bit integer (nullable)
     */
    private $_retToClientId;

    /**
     * ID of the office which is to receive the return receipt and/or the return documents.
     * var signed signed 64-bit integer (nullable)
     */
    private $_retToOfficeId;

    /**
     * An optional reference code
     * var signed string
     */
    private $_ref1;

    /**
     * An optional reference code
     * var signed string
     */
    private $_ref2;

    /**
     * Data for parcels
     * var signed List<ResultParcelInfoEx>
     */
    private $_parcels;

    /**
     * List of declared pallet details
     * var signed string
     */
    private $_palletsListDeclared;

    /**
     * List of measured pallet details
     * var signed string
     */
    private $_palletsListMeasured;

    /**
     * List of calculation pallet details
     * var signed string
     */
    private $_palletsListCalculation;

    /**
     * A special delivery ID
     * var signed 32-bit integer (nullable)
     */
    private $_specialDeliveryId;

    /**
     * Optional services, allowed before payment, when cash on delivery or money transfer is enabled for the picking.
     * var ResultOptionsBeforePayment
     */
    private $_optionsBeforePayment;

    /**
     * List of return services request
     * var List<ResultReturnServiceRequest>
     */
    private $_retServicesRequest;

    /**
     * List of return services request
     * var List<ResultReturnServiceRequest>
     */
    private $_retShipmentRequest;

    /**
     * Specifies if the payer of the return receipt and/or the return documents is the same third party, which is also the payer of the courier service.
     * var signed boolean
     */
    private $_retThirdPartyPayer;

    /**
     * Packings details.
     * var List<ResultPackings>
     */
    private $_packings;

    /**
     * Details for return voucher.
     * var ResultReturnVoucher
     */
    private $_returnVoucher;

    /**
     * Indicates the floor, which the shipment should be delivered to.
     * var signed 32-bit integer (nullable)
     */
    private $_deliveryToFloorNo;

    /**
     * Amounts.
     * var signed ResultAmounts
     */
    private $_amounts;

    /**
     * Deadline for delivery.
     * var signed date
     */
    private $_deadlineDelivery;

    /**
     * Delivery information.
     * var signed ResultDeliveryInfo
     */
    private $_deliveryInfo;

    /**
     * COD payment information.
     * var signed CODPayment
     */
    private $_codPayment;

    /**
     * COD BOL number of return picking.
     * var signed 64-bit integer (nullable)
     */
    private $_redirectBillOfLading;

    /**
     * COD BOL number of return picking.
     * var signed 64-bit integer (nullable)
     */
    private $_returnBillOfLading;






    /**
     * Primary picking BOL.
     * var signed 64-bit integer (nullable)
     */
    private $_primaryPickingBOL;

    /**
     * Picking type.
     * var signed 32-bit integer (nullable)
     */
    private $_pickingType;

    /**
     * Value of pendingParcelsDescription flag.
     * var boolean (nullable)
     */
    private $_pendingParcelsDescription;

    /**
     * Value of pendingShipmentDescription flag.
     * var boolean (nullable)
     */
    private $_pendingShipmentDescription;



    /**
     * MoneyTransferPayment information.
     * var signed MoneyTransferPayment
     */
    private $_moneyTransferPayment;


    /**
     * Constructs new instance of ResultPickingInfo from stdClass
     * @param stdClass $stdResultPickingExtendedInfo
     */
    function __construct($stdResultPickingExtendedInfo) {
        $this->_billOfLading = isset($stdResultPickingExtendedInfo->billOfLading) ? $stdResultPickingExtendedInfo->billOfLading : null;
        $this->_takingDate = isset($stdResultPickingExtendedInfo->takingDate) ? $stdResultPickingExtendedInfo->takingDate : null;
        $this->_serviceTypeId = isset($stdResultPickingExtendedInfo->serviceTypeId) ? $stdResultPickingExtendedInfo->serviceTypeId : null;
        $this->_officeToBeCalledId = isset($stdResultPickingExtendedInfo->officeToBeCalledId) ? $stdResultPickingExtendedInfo->officeToBeCalledId : null;
        $this->_fixedTimeDelivery = isset($stdResultPickingExtendedInfo->fixedTimeDelivery) ? $stdResultPickingExtendedInfo->fixedTimeDelivery : null;
        $this->_deferredDeliveryWorkDays = isset($stdResultPickingExtendedInfo->deferredDeliveryWorkDays) ? $stdResultPickingExtendedInfo->deferredDeliveryWorkDays : null;
        $this->_backDocumentsRequest = isset($stdResultPickingExtendedInfo->backDocumentsRequest) ? $stdResultPickingExtendedInfo->backDocumentsRequest : null;
        $this->_backReceiptRequest = isset($stdResultPickingExtendedInfo->backReceiptRequest) ? $stdResultPickingExtendedInfo->backReceiptRequest : null;
        $this->_willBringToOfficeId = isset($stdResultPickingExtendedInfo->willBringToOfficeId) ? $stdResultPickingExtendedInfo->willBringToOfficeId : null;
        $this->_payCodToThirdParty = isset($stdResultPickingExtendedInfo->payCodToThirdParty) ? $stdResultPickingExtendedInfo->payCodToThirdParty : null;
        $this->_retMoneyTransferReqAmount = isset($stdResultPickingExtendedInfo->retMoneyTransferReqAmount) ? $stdResultPickingExtendedInfo->retMoneyTransferReqAmount : null;
        $this->_parcelsCount = isset($stdResultPickingExtendedInfo->parcelsCount) ? $stdResultPickingExtendedInfo->parcelsCount : null;
        $this->_weightDeclared = isset($stdResultPickingExtendedInfo->weightDeclared) ? $stdResultPickingExtendedInfo->weightDeclared : null;
        $this->_weightMeasured = isset($stdResultPickingExtendedInfo->weightMeasured) ? $stdResultPickingExtendedInfo->weightMeasured : null;
        $this->_weightCalculation = isset($stdResultPickingExtendedInfo->weightCalculation) ? $stdResultPickingExtendedInfo->weightCalculation : null;
        $this->_contents = isset($stdResultPickingExtendedInfo->contents) ? $stdResultPickingExtendedInfo->contents : null;
        $this->_packing = isset($stdResultPickingExtendedInfo->packing) ? $stdResultPickingExtendedInfo->packing : null;
        $this->_documents = isset($stdResultPickingExtendedInfo->documents) ? $stdResultPickingExtendedInfo->documents : null;
        $this->_fragile = isset($stdResultPickingExtendedInfo->fragile) ? $stdResultPickingExtendedInfo->fragile : null;
        $this->_palletized = isset($stdResultPickingExtendedInfo->palletized) ? $stdResultPickingExtendedInfo->palletized : null;
        $this->_sender = isset($stdResultPickingExtendedInfo->sender) ? new ResultClientInfo($stdResultPickingExtendedInfo->sender) : null;
        $this->_receiver = isset($stdResultPickingExtendedInfo->receiver) ? new ResultClientInfo($stdResultPickingExtendedInfo->receiver) : null;
        $this->_payerType = isset($stdResultPickingExtendedInfo->payerType) ? $stdResultPickingExtendedInfo->payerType : null;
        $this->_payerRefId = isset($stdResultPickingExtendedInfo->payerRefId) ? $stdResultPickingExtendedInfo->payerRefId : null;
        $this->_payerTypeInsurance = isset($stdResultPickingExtendedInfo->payerTypeInsurance) ? $stdResultPickingExtendedInfo->payerTypeInsurance : null;
        $this->_payerRefInsuranceId = isset($stdResultPickingExtendedInfo->payerRefInsuranceId) ? $stdResultPickingExtendedInfo->payerRefInsuranceId : null;
        $this->_payerTypePackings = isset($stdResultPickingExtendedInfo->payerTypePackings) ? $stdResultPickingExtendedInfo->payerTypePackings : null;
        $this->_payerRefPackingsId = isset($stdResultPickingExtendedInfo->payerRefPackingsId) ? $stdResultPickingExtendedInfo->payerRefPackingsId : null;
        $this->_noteClient = isset($stdResultPickingExtendedInfo->noteClient) ? $stdResultPickingExtendedInfo->noteClient : null;
        $this->_discCalc = isset($stdResultPickingExtendedInfo->discCalc) ? $stdResultPickingExtendedInfo->discCalc : null;
        $this->_retToClientId = isset($stdResultPickingExtendedInfo->retToClientId) ? $stdResultPickingExtendedInfo->retToClientId : null;
        $this->_retToOfficeId = isset($stdResultPickingExtendedInfo->retToOfficeId) ? $stdResultPickingExtendedInfo->retToOfficeId : null;
        $this->_ref1 = isset($stdResultPickingExtendedInfo->ref1) ? $stdResultPickingExtendedInfo->ref1 : null;
        $this->_ref2 = isset($stdResultPickingExtendedInfo->ref2) ? $stdResultPickingExtendedInfo->ref2 : null;


        $arrResultParcelInfoEx = array();
        if (isset($stdResultPickingExtendedInfo->parcels)) 
		  			{
            	if (is_array($stdResultPickingExtendedInfo->parcels)) 
							{
               		for($i = 0; $i < count($stdResultPickingExtendedInfo->parcels); $i++) 
									{
			                  $arrResultParcelInfoEx[$i] = new ResultParcelInfoEx($stdResultPickingExtendedInfo->parcels[$i]);
                				}
            			} 
					else 
							{
                		$arrResultParcelInfoEx[0] = new ResultParcelInfoEx($stdResultPickingExtendedInfo->parcels);
			            }
        			}
        $this->_parcels = $arrResultParcelInfoEx;


        $this->_palletsListDeclared = isset($stdResultPickingExtendedInfo->palletsListDeclared) ? $stdResultPickingExtendedInfo->palletsListDeclared : null;
        $this->_palletsListMeasured = isset($stdResultPickingExtendedInfo->palletsListMeasured) ? $stdResultPickingExtendedInfo->palletsListMeasured : null;
        $this->_palletsListCalculation = isset($stdResultPickingExtendedInfo->palletsListCalculation) ? $stdResultPickingExtendedInfo->palletsListCalculation : null;
        $this->_specialDeliveryId = isset($stdResultPickingExtendedInfo->specialDeliveryId) ? $stdResultPickingExtendedInfo->specialDeliveryId : null;
        $this->_optionsBeforePayment = isset($stdResultPickingExtendedInfo->optionsBeforePayment) ? new ResultOptionsBeforePayment($stdResultPickingExtendedInfo->optionsBeforePayment) : null;
        $this->_specialDeliveryId = isset($stdResultPickingExtendedInfo->specialDeliveryId) ? $stdResultPickingExtendedInfo->specialDeliveryId : null;
        

        $arrResultReturnServiceRequest = array();
        if (isset($stdResultPickingExtendedInfo->retServicesRequest)) {
            if (is_array($stdResultPickingExtendedInfo->retServicesRequest)) {
                for($i = 0; $i < count($stdResultPickingExtendedInfo->retServicesRequest); $i++) {
                    $arrResultReturnServiceRequest[$i] = new ResultReturnServiceRequest($stdResultPickingExtendedInfo->retServicesRequest[$i]);
                }
            } else {
                $arrResultReturnServiceRequest[0] = new ResultReturnServiceRequest($stdResultPickingExtendedInfo->retServicesRequest);
            }
        }
        $this->_retServicesRequest = $arrResultReturnServiceRequest;


        $this->_retShipmentRequest = isset($stdResultPickingExtendedInfo->retShipmentRequest) ? new ResultReturnShipmentRequest($stdResultPickingExtendedInfo->retShipmentRequest) : null;
        $this->_retThirdPartyPayer = isset($stdResultPickingExtendedInfo->retThirdPartyPayer) ? $stdResultPickingExtendedInfo->retThirdPartyPayer : null;


        $arrResultPackings = array();
        if (isset($stdResultPickingExtendedInfo->packings)) {
            if (is_array($stdResultPickingExtendedInfo->packings)) {
                for($i = 0; $i < count($stdResultPickingExtendedInfo->packings); $i++) {
                    $arrResultPackings[$i] = new ResultPackings($stdResultPickingExtendedInfo->packings[$i]);
                }
            } else {
                $arrResultPackings[0] = new ResultPackings($stdResultPickingExtendedInfo->packings);
            }
        }
        $this->_packings = $arrResultPackings;

 
        $this->_returnVoucher = isset($stdResultPickingExtendedInfo->returnVoucher) ? new ResultReturnVoucher($stdResultPickingExtendedInfo->returnVoucher) : null;
        $this->_deliveryToFloorNo = isset($stdResultPickingExtendedInfo->deliveryToFloorNo) ? $stdResultPickingExtendedInfo->deliveryToFloorNo : null;
        $this->_amounts = isset($stdResultPickingExtendedInfo->amounts) ? new ResultAmounts($stdResultPickingExtendedInfo->amounts) : null;
        $this->_deadlineDelivery = isset($stdResultPickingExtendedInfo->deadlineDelivery) ? $stdResultPickingExtendedInfo->deadlineDelivery : null;
        $this->_deliveryInfo = isset($stdResultPickingExtendedInfo->deliveryInfo) ? new ResultDeliveryInfo($stdResultPickingExtendedInfo->deliveryInfo) : null;
        $this->_codPayment = isset($stdResultPickingExtendedInfo->codPayment) ? new CODPayment($stdResultPickingExtendedInfo->codPayment) : null;
        $this->_redirectBillOfLading = isset($stdResultPickingExtendedInfo->redirectBillOfLading) ? $stdResultPickingExtendedInfo->redirectBillOfLading : null;
        $this->_returnBillOfLading = isset($stdResultPickingExtendedInfo->returnBillOfLading) ? $stdResultPickingExtendedInfo->returnBillOfLading : null;

        $this->_primaryPickingBOL = isset($stdResultPickingExtendedInfo->primaryPickingBOL) ? $stdResultPickingExtendedInfo->primaryPickingBOL : null;
        $this->_pickingType = isset($stdResultPickingExtendedInfo->pickingType) ? $stdResultPickingExtendedInfo->pickingType : null;
        $this->_pendingParcelsDescription = isset($stdResultPickingExtendedInfo->pendingParcelsDescription) ? $stdResultPickingExtendedInfo->pendingParcelsDescription : null;
        $this->_pendingShipmentDescription = isset($stdResultPickingExtendedInfo->pendingShipmentDescription) ? $stdResultPickingExtendedInfo->pendingShipmentDescription : null;

        $this->_moneyTransferPayment = isset($stdResultPickingExtendedInfo->moneyTransferPayment) ? new MoneyTransferPayment($stdResultPickingExtendedInfo->moneyTransferPayment) : null;
    }

    /**
     * Get BOL of the secondary shipment.
     * @return integer Signed 64-bit
     */
    public function getBillOfLading() {
        return $this->_billOfLading;
    }

    /**
     * Get taking date
     * @return date Taking date
     */
    public function getTakingDate() {
        return $this->_takingDate;
    }

    /**
     * Get Courier service type ID
     * @return signed integer 64-bit
     */
    public function getServiceTypeId() {
        return $this->_serviceTypeId;
    }

    /**
     * Get ID of an office "to be called"
     * @return signed integer 64-bit
     */
    public function getOfficeToBeCalledId() {
        return $this->_officeToBeCalledId;
    }

    /**
     * Get Fixed time for delivery
     * @return signed integer 16-bit
     */
    public function getFixedTimeDelivery() {
        return $this->_fixedTimeDelivery;
    }

    /**
     * Get Deferred delivery work days
     * @return signed integer 32-bit
     */
    public function getDeferredDeliveryWorkDays() {
        return $this->_deferredDeliveryWorkDays;
    }

    /**
     * Get Indicates if the shipment has a "request for return documents"
     * @return boolean
     */
    public function getBackDocumentsRequest() {
        return $this->_backDocumentsRequest;
    }

    /**
     * Get Indicates if the shipment has a "request for return receipt"
     * @return boolean
     */
    public function getBackReceiptRequest() {
        return $this->_backReceiptRequest;
    }

    /**
     * Get ID of an office where the sender intends to deliver the shipment by him/herself. Non-null and non-zero value indicates this picking as "to office". Otherwise "from address" is considered.
     * @return long
     */
    public function getWillBringToOfficeId() {
        return $this->_willBringToOfficeId;
    }

    /**
     * Get COD value is to be paid to a third party
     * @return boolean
     */
    public function getPayCodToThirdParty() {
        return $this->_payCodToThirdParty;
    }

    /**
     * Get money-transfer request amount
     * @return signed 64-bit real (nullable)
     */
    public function getRetMoneyTransferReqAmount() {
        return $this->_retMoneyTransferReqAmount;
    }

    /**
     * Get Parcels count
     * @return signed integer 32-bit
     */
    public function getParcelsCount() {
        return $this->_parcelsCount;
    }

    /**
     * Get Declared weight
     * @return signed 64-bit real
     */
    public function getWeightDeclared() {
        return $this->_weightDeclared;
    }

    /**
     * Get Measured weight
     * @return signed 64-bit real (nullable)
     */
    public function getWeightMeasured() {
        return $this->_weightMeasured;
    }

    /**
     * Get Calculation weight
     * @return signed 64-bit real (nullable)
     */
    public function getWeightCalculation() {
        return $this->_weightCalculation;
    }

    /**
     * Get Contents
     * @return signed string
     */
    public function getContents() {
        return $this->_contents;
    }

    /**
     * Get Packing
     * @return signed string
     */
    public function getPacking() {
        return $this->_packing;
    }

    /**
     * Get Indicates whether the shipment consists of documents
     * @return signed boolean
     */
    public function getDocuments() {
        return $this->_documents;
    }

    /**
     * Get Indicates whether the shipment is fragile
     * @return boolean
     */
    public function getFragile() {
        return $this->_fragile;
    }

    /**
     * Get Indicates whether the shipment is palletized
     * @return boolean
     */
    public function getPalletized() {
        return $this->_palletized;
    }

    /**
     * Get Indicates whether the shipment is palletized
     * @return boolean
     */
    public function getSender() {
        return $this->_sender;
    }

    /**
     * Get Indicates whether the shipment is palletized
     * @return boolean
     */
    public function getReceiver() {
        return $this->_receiver;
    }

    /**
     * Get Payer type (0=sender, 1=receiver or 2=third party)
     * @return 32-bit integer
     */
    public function getPayerType() {
        return $this->_payerType;
    }

    /**
     * Get Payer ID
     * @return 64-bit integer (nullable)
     */
    public function getPayerRefId() {
        return $this->_payerRefId;
    }

    /**
     * Get Insurance payer type (0=sender, 1=reciever or 2=third party)
     * @return 32-bit integer (nullable)
     */
    public function getPayerTypeInsurance() {
        return $this->_payerTypeInsurance;
    }

    /**
     * Get Insurance payer ID
     * @return 64-bit integer (nullable)
     */
    public function getPayerRefInsuranceId() {
        return $this->_payerRefInsuranceId;
    }

    /**
     * Get Packings payer type (0=sender, 1=reciever or 2=third party)
     * @return 32-bit integer (nullable)
     */
    public function getPayerTypePackings() {
        return $this->_payerTypePackings;
    }

    /**
     * Get Packings payer ID
     * @return signed 64-bit integer (nullable)
     */
    public function getPayerRefPackingsId() {
        return $this->_payerRefPackingsId;
    }

    /**
     * Get Client's note
     * @return string
     */
    public function getNoteClient() {
        return $this->_noteClient;
    }

    /**
     * Get Card/Coupon/Voucher number for fixed discount
     * @return FixedDiscountCardId
     */
    public function getDiscCalc() {
        return $this->_discCalc;
    }

    /**
     * Get ID of the client who is to receive the return receipt and/or the return documents.
     * @return signed 64-bit integer (nullable)
     */
    public function getRetToClientId() {
        return $this->_retToClientId;
    }

    /**
     * Get ID of the office which is to receive the return receipt and/or the return documents.
     * @return signed 64-bit integer (nullable)
     */
    public function getRetToOfficeId() {
        return $this->_retToOfficeId;
    }

    /**
     * Get An optional reference code
     * @return signed string
     */
    public function getRef1() {
        return $this->_ref1;
    }

    /**
     * Get An optional reference code
     * @return signed string
     */
    public function getRef2() {
        return $this->_ref2;
    }

    /**
     * Get Data for parcels
     * @return List<ResultParcelInfoEx>
     */
    public function getParcels() {
        return $this->_parcels;
    }

    /**
     * Get List of declared pallet details
     * @return string
     */
    public function getPalletsListDeclared() {
        return $this->_palletsListDeclared;
    }

    /**
     * Get List of measured pallet details
     * @return string
     */
    public function getPalletsListMeasured() {
        return $this->_palletsListMeasured;
    }

    /**
     * Get List of calculation pallet details
     * @return string
     */
    public function getPalletsListCalculation() {
        return $this->_palletsListCalculation;
    }

    /**
     * Get a special delivery ID
     * @return 32-bit integer (nullable)
     */
    public function getSpecialDeliveryId() {
        return $this->_specialDeliveryId;
    }

    /**
     * Get Optional services, allowed before payment, when cash on delivery or money transfer is enabled for the picking.
     * @return ResultOptionsBeforePayment
     */
    public function getOptionsBeforePayment() {
        return $this->_optionsBeforePayment;
    }

    /**
     * Get List of return services request.
     * @return List<ResultReturnServiceRequest>
     */
    public function getRetServicesRequest() {
        return $this->_retServicesRequest;
    }

    /**
     * Get Return shipment request
     * @return ResultReturnShipmentRequest
     */
    public function getRetShipmentRequest() {
        return $this->_retShipmentRequest;
    }

    /**
     * Get Specifies if the payer of the return receipt and/or the return documents is the same third party, which is also the payer of the courier service.
     * @return boolean
     */
    public function getRetThirdPartyPayer() {
        return $this->_retThirdPartyPayer;
    }

    /**
     * Get Packings details.
     * @return List<ResultPackings>
     */
    public function getPackings() {
        return $this->_packings;
    }

    /**
     * Get Details for return voucher
     * @return ResultReturnVoucher
     */
    public function getReturnVoucher() {
        return $this->_returnVoucher;
    }

    /**
     * Get Indicates the floor, which the shipment should be delivered to.
     * @return 32-bit integer (nullable)
     */
    public function getDeliveryToFloorNo() {
        return $this->_deliveryToFloorNo;
    }

    /**
     * Get Amounts.
     * @return ResultAmounts
     */
    public function getAmounts() {
        return $this->_amounts;
    }

    /**
     * Get Deadline for delivery.
     * @return date
     */
    public function getDeadlineDelivery() {
        return $this->_deadlineDelivery;
    }

    /**
     * Get Delivery information.
     * @return ResultDeliveryInfo
     */
    public function getDeliveryInfo() {
        return $this->_deliveryInfo;
    }

    /**
     * Get COD payment information.
     * @return CODPayment
     */
    public function getCodPayment() {
        return $this->_codPayment;
    }

    /**
     * Get BOL number of redirect picking.
     * @return 64-bit integer (nullable)
     */
    public function getRedirectBillOfLading() {
        return $this->_redirectBillOfLading;
    }
    
    /**
     * Get BOL number of return pickings.
     * @return 64-bit integer (nullable)
     */
    public function getReturnBillOfLading() {
        return $this->_returnBillOfLading;
    }





    /**
     * Get Primary picking BOL.
     * @return 64-bit integer (nullable)
     */
    public function getPrimaryPickingBOL() {
        return $this->_primaryPickingBOL;
    }

    /**
     * Get Picking type.
     * @return signed 32-bit integer (nullable)
     */
    public function getPickingType() {
        return $this->_pickingType;
    }

    /**
     * Get Value of pendingParcelsDescription flag.
     * @return boolean (nullable)
     */
    public function getPendingParcelsDescription() {
        return $this->_pendingParcelsDescription;
    }

    /**
     * Get Value of pendingShipmentDescription flag.
     * @return boolean (nullable)
     */
    public function getPendingShipmentDescription() {
        return $this->_pendingShipmentDescription;
    }


    /**
     * Get MoneyTransferPayment information.
     * @return MoneyTransferPayment
     */
    public function getMoneyTransferPayment() {
        return $this->_moneyTransferPayment;
    }
    
}
?>