<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'EPSInterface.class.php';

/**
 * Speedy SOAP EPS ver01 Service Interface Implementation
 */
class EPSSOAPInterfaceImpl extends SoapClient implements EPSInterface {

    /**
     * Speedy SOAP WSDL version 01 URL
     * @var string
     */
    const SPEEDY_SOAP_WSDL_V01_URL = 'https://www.speedy.bg/eps/main01.wsdl';

    /**
     * Constructs new instance of SOAP service
     * @param string $wsdlURL
     * @param options[optional]
     */
    function __construct($wsdlURL=self::SPEEDY_SOAP_WSDL_V01_URL, $options=null) {
        if (is_null($options)) {
            parent::SoapClient($wsdlURL);
        } else {
            parent::SoapClient($wsdlURL, $options);
        }
        //   echo('<BR>Connected to '.$wsdlURL);
    }

    /**
     * @see EPSInterface::login()
     */
    public function login($username, $password) {
        try {
            $loginSdtClass = new stdClass();
            $loginSdtClass->username = $username;
            $loginSdtClass->password = $password;
            $response = parent::login($loginSdtClass);
            if (isset($response->return)) {
                $resultLogin = new ResultLogin($response->return);
            } else {
                $resultLogin = null;
            }
            return $resultLogin;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::isSessionActive()
     */
    public function isSessionActive($sessionId, $refreshSession) {
        try {
            $isSessionActiveSdtClass = new stdClass();
            $isSessionActiveSdtClass->sessionId      = $sessionId;
            $isSessionActiveSdtClass->refreshSession = $refreshSession;
            $response = parent::isSessionActive($isSessionActiveSdtClass);
            if (isset($response->return)) {
                $isSessionActiveFlag = $response->return;
            } else {
                $isSessionActiveFlag = false;
            }
            return $isSessionActiveFlag;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listServices()
     */
    public function listServices($sessionId, $date, $language) {
        try {
            $listServicesStdObject = new stdClass();
            $listServicesStdObject->sessionId = $sessionId;
            $listServicesStdObject->date      = $date;
            $listServicesStdObject->language  = $language;
            $response = parent::listServices($listServicesStdObject);
            $arrListServices = array();
            if (isset($response->return)) {
                $arrStdServices = $response->return;
                if (is_array($arrStdServices)) {
                    for($i = 0; $i < count($arrStdServices); $i++) {
                        $arrListServices[$i] = new ResultCourierService($arrStdServices[$i]);
                    }
                } else {
                    $arrListServices[0] = new ResultCourierService($arrStdServices);
                }
            }
            return $arrListServices;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listServicesForSites()
     */
    public function listServicesForSites(
        $sessionId, $date, $senderSiteId, $receiverSiteId,
        $senderCountryId, $senderPostCode, $receiverCountryId, $receiverPostCode, $language,
        $senderId, $receiverId, $senderOfficeId, $receiverOfficeId
    ) {
        try {
            $listServicesForSitesStdObject = new stdClass();
            $listServicesForSitesStdObject->sessionId         = $sessionId;
            $listServicesForSitesStdObject->date              = $date;
            $listServicesForSitesStdObject->senderSiteId      = $senderSiteId;
            $listServicesForSitesStdObject->receiverSiteId    = $receiverSiteId;
            $listServicesForSitesStdObject->senderCountryId   = $senderCountryId;
            $listServicesForSitesStdObject->senderPostCode    = $senderPostCode;
            $listServicesForSitesStdObject->receiverCountryId = $receiverCountryId;
            $listServicesForSitesStdObject->receiverPostCode  = $receiverPostCode;
            $listServicesForSitesStdObject->language          = $language;
            $listServicesForSitesStdObject->senderId          = $senderId;
            $listServicesForSitesStdObject->receiverId        = $receiverId;
            $listServicesForSitesStdObject->senderOfficeId    = $senderOfficeId;
            $listServicesForSitesStdObject->receiverOfficeId  = $receiverOfficeId;
            $response = parent::listServicesForSites($listServicesForSitesStdObject);
            $arrServicesForSitesStdObject = array();
            if (isset($response->return)) {
                $arrStdServicesForSites = $response->return;
                if (is_array($arrStdServicesForSites)) {
                    for($i = 0; $i < count($arrStdServicesForSites); $i++) {
                        $arrServicesForSitesStdObject[$i] = new ResultCourierServiceExt($arrStdServicesForSites[$i]);
                    }
                } else {
                    $arrServicesForSitesStdObject[0] = new ResultCourierServiceExt($arrStdServicesForSites);
                }
            }
            return $arrServicesForSitesStdObject;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listSites()
     */
    public function listSites($sessionId, $type, $name, $language) {
        try {
            $listSitesStdObject = new stdClass();
            $listSitesStdObject->sessionId = $sessionId;
            $listSitesStdObject->type      = $type;
            $listSitesStdObject->name      = $name;
            $listSitesStdObject->language  = $language;
            $response = parent::listSites($listSitesStdObject);
            $arrListSites = array();
            if (isset($response->return)) {
                $arrStdSites = $response->return;
                if (is_array($arrStdSites)) {
                    for($i = 0; $i < count($arrStdSites); $i++) {
                        $arrListSites[$i] = new ResultSite($arrStdSites[$i]);
                    }
                } else {
                    $arrListSites[0] = new ResultSite($arrStdSites);
                }
            }
            return $arrListSites;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listSites()
     */
    public function listSitesEx($sessionId, $paramFilterSite, $language) {
        try {
            $listSitesExStdObject = new stdClass();
            $listSitesExStdObject->sessionId = $sessionId;
            $listSitesExStdObject->filter    = $paramFilterSite->toStdClass();
            $listSitesExStdObject->language  = $language;
            
            $response = parent::listSitesEx($listSitesExStdObject);
            $arrListSitesEx = array();
            if (isset($response->return)) {
                $arrStdSitesEx = $response->return;
                if (is_array($arrStdSitesEx)) {
                    for($i = 0; $i < count($arrStdSitesEx); $i++) {
                        $arrListSitesEx[$i] = new ResultSiteEx($arrStdSitesEx[$i]);
                    }
                } else {
                    $arrListSitesEx[0] = new ResultSiteEx($arrStdSitesEx);
                }
            }
            return $arrListSitesEx;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::getWeightInterval()
     */
    public function getWeightInterval(
        $sessionId, $serviceTypeId, $senderSiteId, $receiverSiteId, $date, $documents,
        $senderCountryId, $senderPostCode, $receiverCountryId, $receiverPostCode,
        $senderId, $receiverId, $senderOfficeId, $receiverOfficeId
    ) {
        try {
            $getWeightIntervalStdObject = new stdClass();
            $getWeightIntervalStdObject->sessionId         = $sessionId;
            $getWeightIntervalStdObject->serviceTypeId     = $serviceTypeId;
            $getWeightIntervalStdObject->senderSiteId      = $senderSiteId;
            $getWeightIntervalStdObject->receiverSiteId    = $receiverSiteId;
            $getWeightIntervalStdObject->date              = $date;
            $getWeightIntervalStdObject->documents         = $documents;
            $getWeightIntervalStdObject->senderCountryId   = $senderCountryId;
            $getWeightIntervalStdObject->senderPostCode    = $senderPostCode;
            $getWeightIntervalStdObject->receiverCountryId = $receiverCountryId;
            $getWeightIntervalStdObject->receiverPostCode  = $receiverPostCode;

            $getWeightIntervalStdObject->senderId          = $senderId;
            $getWeightIntervalStdObject->receiverId        = $receiverId;
            $getWeightIntervalStdObject->senderOfficeId    = $senderOfficeId;
            $getWeightIntervalStdObject->receiverOfficeId  = $receiverOfficeId;
            
            $response = parent::getWeightInterval($getWeightIntervalStdObject);
            if (isset($response->return)) {
                $resultMinMaxReal = new ResultMinMaxReal($response->return);
            } else {
                $resultMinMaxReal = null;
            }
            return $resultMinMaxReal;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::getAddressNomenclature()
     */
    public function getAddressNomenclature($sessionId, $nomenType, $countryId) {
        try {
            $getAddressNomenclatureStdObject = new stdClass();
            $getAddressNomenclatureStdObject->sessionId = $sessionId;
            $getAddressNomenclatureStdObject->nomenType = $nomenType;
            $getAddressNomenclatureStdObject->countryId = $countryId;
           
            $response = parent::getAddressNomenclature($getAddressNomenclatureStdObject);
            if (isset($response->return)) {
                $getAddressNomenclature = $response->return;
            } else {
                $getAddressNomenclature = null;
            }
            return $getAddressNomenclature;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listAllSites()
     */
    public function listAllSites($sessionId, $language, $countryId) {
        try {
            $listAllSitesStdObject = new stdClass();
            $listAllSitesStdObject->sessionId = $sessionId;
            $listAllSitesStdObject->language  = $language;
            $listAllSitesStdObject->countryId = $countryId;
            
            $response = parent::listAllSites($listAllSitesStdObject);
            $arrListAllSites = array();
            if (isset($response->return)) {
                $arrStdAllSites = $response->return;
                if (is_array($arrStdAllSites)) {
                    for($i = 0; $i < count($arrStdAllSites); $i++) {
                        $arrListAllSites[$i] = new ResultSite($arrStdAllSites[$i]);
                    }
                } else {
                    $arrListAllSites[0] = new ResultSite($arrStdAllSites);
                }
            }
            return $arrListAllSites;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::getSiteById()
     */
    public function getSiteById($sessionId, $siteId) {
        try {
            $getSiteByIdStdObject = new stdClass();
            $getSiteByIdStdObject->sessionId = $sessionId;
            $getSiteByIdStdObject->siteId = $siteId;
            $response = parent::getSiteById($getSiteByIdStdObject);
            if (isset($response->return)) {
                $resultSite = new ResultSite($response->return);
            } else {
                $resultSite = null;
            }
            return $resultSite;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::getSitesByAddrNomenType()
     */
    public function getSitesByAddrNomenType($sessionId, $addrNomen) {
        try {
            $getSitesByAddrNomenTypeStdObject = new stdClass();
            $getSitesByAddrNomenTypeStdObject->sessionId = $sessionId;
            $getSitesByAddrNomenTypeStdObject->addrNomen = $addrNomen;
            $response = parent::getSitesByAddrNomenType($getSitesByAddrNomenTypeStdObject);
            $arrListSitesByAddrNomenType = array();
            if (isset($response->return)) {
                $arrStdSitesByAddrNomenType = $response->return;
                if (is_array($arrStdSitesByAddrNomenType)) {
                    for($i = 0; $i < count($arrStdSitesByAddrNomenType); $i++) {
                        $arrListSitesByAddrNomenType[$i] = new ResultSite($arrStdSitesByAddrNomenType[$i]);
                    }
                } else {
                    $arrListSitesByAddrNomenType[0] = new ResultSite($arrStdSitesByAddrNomenType);
                }
            }
            return $arrListSitesByAddrNomenType;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listStreetTypes()
     */
    public function listStreetTypes($sessionId, $language) {
        try {
            $listStreetTypesStdObject = new stdClass();
            $listStreetTypesStdObject->sessionId = $sessionId;
            $listStreetTypesStdObject->language  = $language;
            
            $response = parent::listStreetTypes($listStreetTypesStdObject);
            $arrListStreetTypes = array();
            if (isset($response->return)) {
                $arrStdListStreetTypes = $response->return;
                if (is_array($arrStdListStreetTypes)) {
                    for($i = 0; $i < count($arrStdListStreetTypes); $i++) {
                        $arrListStreetTypes[$i] = $arrStdListStreetTypes[$i];
                    }
                } else {
                    $arrListStreetTypes[0] = $arrStdListStreetTypes;
                }
            }
            return $arrListStreetTypes;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listQuarterTypes()
     */
    public function listQuarterTypes($sessionId, $language) {
        try {
            $listQuarterTypesStdObject = new stdClass();
            $listQuarterTypesStdObject->sessionId = $sessionId;
            $listQuarterTypesStdObject->language  = $language;
            
            $response = parent::listQuarterTypes($listQuarterTypesStdObject);
            $arrListQuarterTypes = array();
            if (isset($response->return)) {
                $arrStdListQuarterTypes = $response->return;
                if (is_array($arrStdListQuarterTypes)) {
                    for($i = 0; $i < count($arrStdListQuarterTypes); $i++) {
                        $arrListQuarterTypes[$i] = $arrStdListQuarterTypes[$i];
                    }
                } else {
                    $arrListQuarterTypes[0] = $arrStdListQuarterTypes;
                }
            }
            return $arrListQuarterTypes;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listStreets()
     */
    public function listStreets($sessionId, $name, $siteId, $language) {
        try {
            $listStreetsStdObject = new stdClass();
            $listStreetsStdObject->sessionId = $sessionId;
            $listStreetsStdObject->name      = $name;
            $listStreetsStdObject->siteId    = $siteId;
            $listStreetsStdObject->language  = $language;
            
            $response = parent::listStreets($listStreetsStdObject);
            $arrlistStreets = array();
            if (isset($response->return)) {
                $arrStdListStreets = $response->return;
                if (is_array($arrStdListStreets)) {
                    for($i = 0; $i < count($arrStdListStreets); $i++) {
                        $arrlistStreets[$i] = new ResultStreet($arrStdListStreets[$i]);
                    }
                } else {
                    $arrlistStreets[0] = new ResultStreet($arrStdListStreets);
                }
            }
            return $arrlistStreets;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listQuarters()
     */
    public function listQuarters($sessionId, $name, $siteId, $language) {
        try {
            $listQuartersStdObject = new stdClass();
            $listQuartersStdObject->sessionId = $sessionId;
            $listQuartersStdObject->name      = $name;
            $listQuartersStdObject->siteId    = $siteId;
            $listQuartersStdObject->language  = $language;
            
            $response = parent::listQuarters($listQuartersStdObject);
            $arrListQuarters = array();
            if (isset($response->return)) {
                $arrStdListQuarters = $response->return;
                if (is_array($arrStdListQuarters)) {
                    for($i = 0; $i < count($arrStdListQuarters); $i++) {
                        $arrListQuarters[$i] = new ResultQuarter($arrStdListQuarters[$i]);
                    }
                } else {
                    $arrListQuarters[0] = new ResultQuarter($arrStdListQuarters);
                }
            }
            return $arrListQuarters;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listCommonObjects()
     */
    public function listCommonObjects($sessionId, $name, $siteId, $language) {
        try {
            $listCommonObjectsStdObject = new stdClass();
            $listCommonObjectsStdObject->sessionId = $sessionId;
            $listCommonObjectsStdObject->name      = $name;
            $listCommonObjectsStdObject->siteId    = $siteId;
            $listCommonObjectsStdObject->language  = $language;
            $response = parent::listCommonObjects($listCommonObjectsStdObject);
            $arrListCommonObjects = array();
            if (isset($response->return)) {
                $arrStdListCommonObjects = $response->return;
                if (is_array($arrStdListCommonObjects)) {
                    for($i = 0; $i < count($arrStdListCommonObjects); $i++) {
                        $arrListCommonObjects[$i] = new ResultCommonObject($arrStdListCommonObjects[$i]);
                    }
                } else {
                    $arrListCommonObjects[0] = new ResultCommonObject($arrStdListCommonObjects);
                }
            }
            return $arrListCommonObjects;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listBlocks()
     */
    public function listBlocks($sessionId, $name, $siteId, $language) {
        try {
            $listBlocksStdObject = new stdClass();
            $listBlocksStdObject->sessionId = $sessionId;
            $listBlocksStdObject->name      = $name;
            $listBlocksStdObject->siteId    = $siteId;
            $listBlocksStdObject->language  = $language;
            $response = parent::listBlocks($listBlocksStdObject);
            $arrListBlocks = array();
            if (isset($response->return)) {
                $arrStdListBlocks = $response->return;
                if (is_array($arrStdListBlocks)) {
                    for($i = 0; $i < count($arrStdListBlocks); $i++) {
                        $arrListBlocks[$i] = $arrStdListBlocks[$i];
                    }
                } else {
                    $arrListBlocks[0] = $arrStdListBlocks;
                }
            }
            return $arrListBlocks;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::listOffices()
     */
    public function listOffices($sessionId, $name, $siteId) {
        try {
            $listOfficesStdObject = new stdClass();
            $listOfficesStdObject->sessionId = $sessionId;
            $listOfficesStdObject->name      = $name;
            $listOfficesStdObject->siteId    = $siteId;
            $response = parent::listOffices($listOfficesStdObject);
            $arrListOffices = array();
            if (isset($response->return)) {
                $arrStdListOffices = $response->return;
                if (is_array($arrStdListOffices)) {
                    for($i = 0; $i < count($arrStdListOffices); $i++) {
                        $arrListOffices[$i] = new ResultOffice($arrStdListOffices[$i]);
                    }
                } else {
                    $arrListOffices[0] = new ResultOffice($arrStdListOffices);
                }
            }
            return $arrListOffices;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::getClientById($sessionId, $clientId)
     */
    public function getClientById($sessionId, $clientId) {
        try {
            $getClientByIdStdObject = new stdClass();
            $getClientByIdStdObject->sessionId = $sessionId;
            $getClientByIdStdObject->clientId = $clientId;
            $response = parent::getClientById($getClientByIdStdObject);
            if (isset($response->return)) {
                $resultClientData = new ResultClientData($response->return);
            } else {
                $resultClientData = null;
            }
            return $resultClientData;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::getAllowedDaysForTaking()
     */
    public function getAllowedDaysForTaking(
        $sessionId, $serviceTypeId, $senderSiteId, $senderOfficeId, $minDate, $senderCountryId, $senderPostCode, $senderId
    ) {
        try {
            $getAllowedDaysForTakingStdObject = new stdClass();
            $getAllowedDaysForTakingStdObject->sessionId       = $sessionId;
            $getAllowedDaysForTakingStdObject->serviceTypeId   = $serviceTypeId;
            $getAllowedDaysForTakingStdObject->senderSiteId    = $senderSiteId;
            $getAllowedDaysForTakingStdObject->senderOfficeId  = $senderOfficeId;
            $getAllowedDaysForTakingStdObject->minDate         = $minDate;
            $getAllowedDaysForTakingStdObject->senderCountryId = $senderCountryId;
            $getAllowedDaysForTakingStdObject->senderPostCode  = $senderPostCode;
            $getAllowedDaysForTakingStdObject->senderId        = $senderId;
            
            $response = parent::getAllowedDaysForTaking($getAllowedDaysForTakingStdObject);
            $arrGetAllowedDaysForTaking = array();
            if (isset($response->return)) {
                $arrStdGetAllowedDaysForTaking = $response->return;
                if (is_array($arrStdGetAllowedDaysForTaking)) {
                    for($i = 0; $i < count($arrStdGetAllowedDaysForTaking); $i++) {
                        $arrGetAllowedDaysForTaking[$i] = $arrStdGetAllowedDaysForTaking[$i];
                    }
                } else {
                    $arrGetAllowedDaysForTaking[0] = $arrStdGetAllowedDaysForTaking;
                }
            }
            return $arrGetAllowedDaysForTaking;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::addressSearch()
     */
    public function addressSearch($sessionId, $address) {
        try {
            $addressSearchStdObject = new stdClass();
            $addressSearchStdObject->sessionId = $sessionId;
            $addressSearchStdObject->address   = $address->toStdClass();
            $response = parent::addressSearch($addressSearchStdObject);
            $arrAddressSearch = array();
            if (isset($response->return)) {
                $arrStdAddressSearch = $response->return;
                if (is_array($arrStdAddressSearch)) {
                    for($i = 0; $i < count($arrStdAddressSearch); $i++) {
                        $arrAddressSearch[$i] = new ResultAddressSearch($arrStdAddressSearch[$i]);
                    }
                } else {
                    $arrAddressSearch[0] = new ResultAddressSearch($arrStdAddressSearch);
                }
            }
            return $arrAddressSearch;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::calculate()
     */
    public function calculate($sessionId, $calculation) {
        try {
            $calculateStdObject = new stdClass();
            $calculateStdObject->sessionId   = $sessionId;
            $calculateStdObject->calculation = $calculation->toStdClass();
            $response = parent::calculate($calculateStdObject);
            if (isset($response->return)) {
                $resultCalculation = new ResultCalculation($response->return);
            } else {
                $resultCalculation = null;
            }
            return $resultCalculation;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::calculateMultipleServices()
     */
    public function calculateMultipleServices($sessionId, $calculation, $serviceTypeIds) {
        try {
            $calculateMultipleServicesStdObject = new stdClass();
            $calculateMultipleServicesStdObject->sessionId      = $sessionId;
            $calculateMultipleServicesStdObject->calculation    = $calculation->toStdClass();
            $calculateMultipleServicesStdObject->serviceTypeIds = $serviceTypeIds;
            $calculateMultipleServicesStdObject->calculation->serviceTypeId = ParamCalculation::CALCULATE_MULTUPLE_SERVICES_SERVICE_TYPE_ID;
            $response = parent::calculateMultipleServices($calculateMultipleServicesStdObject);
            $arrCalculateMultipleServices = array();
            if (isset($response->return)) {
                $arrStdCalculateMultipleServices = $response->return;
                if (is_array($arrStdCalculateMultipleServices)) {
                    for($i = 0; $i < count($arrStdCalculateMultipleServices); $i++) {
                        $arrCalculateMultipleServices[$i] = new ResultCalculationMS($arrStdCalculateMultipleServices[$i]);
                    }
                } else {
                    $arrCalculateMultipleServices[0] = new ResultCalculationMS($arrStdCalculateMultipleServices);
                }
            }
            return $arrCalculateMultipleServices;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::calculatePicking()
     */
    public function calculatePicking($sessionId, $picking) {
        try {
            $calculatePickingStdObject = new stdClass();
            $calculatePickingStdObject->sessionId = $sessionId;
            $calculatePickingStdObject->picking   = $picking->toStdClass();
            $response = parent::calculatePicking($calculatePickingStdObject);
            if (isset($response->return)) {
                $resultCalculation = new ResultCalculation($response->return);
            } else {
                $resultCalculation = null;
            }
            return $resultCalculation;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::createBillOfLading()
     */
    public function createBillOfLading($sessionId, $picking) {
        try {
            $createBillOfLadingStdObject = new stdClass();
            $createBillOfLadingStdObject->sessionId = $sessionId;
            $createBillOfLadingStdObject->picking   = $picking->toStdClass();
            $response = parent::createBillOfLading($createBillOfLadingStdObject);
            if (isset($response->return)) {
                $resultBOL = new ResultBOL($response->return);
            } else {
                $resultBOL = null;
            }
            return $resultBOL;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::createPDF()
     */
    public function createPDF($sessionId, $params) {
        try {
            $createPDFStdObject = new stdClass();
            $createPDFStdObject->sessionId = $sessionId;
            $createPDFStdObject->params    = $params->toStdClass();
            $response = parent::createPDF($createPDFStdObject);
            if (isset($response->return)) {
                $resultPDF = $response->return;
            } else {
                $resultPDF = null;
            }
            return $resultPDF;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::createBillOfLadingPDF()
     */
    public function createBillOfLadingPDF($sessionId, $billOfLading, $includeAutoPrintJS) {
        try {
            $createBillOfLadingPDFStdObject = new stdClass();
            $createBillOfLadingPDFStdObject->sessionId          = $sessionId;
            $createBillOfLadingPDFStdObject->billOfLading       = $billOfLading;
            $createBillOfLadingPDFStdObject->includeAutoPrintJS = $includeAutoPrintJS;
            $response = parent::createBillOfLadingPDF($createBillOfLadingPDFStdObject);
            if (isset($response->return)) {
                $resultPDF = $response->return;
            } else {
                $resultPDF = null;
            }
            return $resultPDF;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::createCustomTravelLabelPDFType1()
     */
    public function createCustomTravelLabelPDFType1($sessionId, $parcelId) {
        try {
            $createCustomTravelLabelPDFType1StdObject = new stdClass();
            $createCustomTravelLabelPDFType1StdObject->sessionId = $sessionId;
            $createCustomTravelLabelPDFType1StdObject->parcelId  = $parcelId;
            $response = parent::createCustomTravelLabelPDFType1($createCustomTravelLabelPDFType1StdObject);
            if (isset($response->return)) {
                $resultPDF = $response->return;
            } else {
                $resultPDF = null;
            }
            return $resultPDF;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::invalidatePicking()
     */
    public function invalidatePicking($sessionId, $billOfLading, $cancelComment) {
        try {
            $invalidatePickingStdObject = new stdClass();
            $invalidatePickingStdObject->sessionId     = $sessionId;
            $invalidatePickingStdObject->billOfLading  = $billOfLading;
            $invalidatePickingStdObject->cancelComment = $cancelComment;
            parent::invalidatePicking($invalidatePickingStdObject);
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::updateBillOfLading()
     */
    public function updateBillOfLading($sessionId, $picking) {
        try {
            $updateBillOfLadingStdObject = new stdClass();
            $updateBillOfLadingStdObject->sessionId = $sessionId;
            $updateBillOfLadingStdObject->picking   = $picking->toStdClass();
            $response = parent::updateBillOfLading($updateBillOfLadingStdObject);
            if (isset($response->return)) {
                $resultBOL = new ResultBOL($response->return);
            } else {
                $resultBOL = null;
            }
            return $resultBOL;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::addParcel()
     */
    public function addParcel($sessionId, $parcel) {
        try {
            $addParcelStdObject = new stdClass();
            $addParcelStdObject->sessionId = $sessionId;
            $addParcelStdObject->parcel    = $parcel->toStdClass();
            $response = parent::addParcel($addParcelStdObject);
            if (isset($response->return)) {
                $result = $response->return;
            } else {
                $result = null;
            }
            return $result;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::finalizeBillOfLadingCreation()
     */
    public function finalizeBillOfLadingCreation($sessionId, $billOfLading) {
        try {
            $finalizeBillOfLadingCreationStdObject = new stdClass();
            $finalizeBillOfLadingCreationStdObject->sessionId    = $sessionId;
            $finalizeBillOfLadingCreationStdObject->billOfLading = $billOfLading;
            $response = parent::finalizeBillOfLadingCreation($finalizeBillOfLadingCreationStdObject);
            if (isset($response->return)) {
                $resultBOL = new ResultBOL($response->return);
            } else {
                $resultBOL = null;
            }
            return $resultBOL;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::createOrder()
     */
    public function createOrder($sessionId, $order) {
        try {
            $createOrderStdObject = new stdClass();
            $createOrderStdObject->sessionId = $sessionId;
            $createOrderStdObject->order     = $order->toStdClass();
            $response = parent::createOrder($createOrderStdObject);
            $arrCreateOrder = array();
            if (isset($response->return)) {
                $arrStdCreateOrder = $response->return;
                if (is_array($arrStdCreateOrder)) {
                    for($i = 0; $i < count($arrStdCreateOrder); $i++) {
                        $arrCreateOrder[$i] = new ResultOrderPickingInfo($arrStdCreateOrder[$i]);
                    }
                } else {
                    $arrCreateOrder[0] = new ResultOrderPickingInfo($arrStdCreateOrder);
                }
            }
            return $arrCreateOrder;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::getPickingParcels()
     */
    public function getPickingParcels($sessionId, $billOfLading) {
        try {
            $getPickingParcelsStdObject = new stdClass();
            $getPickingParcelsStdObject->sessionId    = $sessionId;
            $getPickingParcelsStdObject->billOfLading = $billOfLading;
            $response = parent::getPickingParcels($getPickingParcelsStdObject);
            $arrResultParcelInfo = array();
            if (isset($response->return)) {
                $arrStdResultParcelInfo = $response->return;
                if (is_array($arrStdResultParcelInfo)) {
                    for($i = 0; $i < count($arrStdResultParcelInfo); $i++) {
                        $arrResultParcelInfo[$i] = new ResultParcelInfo($arrStdResultParcelInfo[$i]);
                    }
                } else {
                    $arrResultParcelInfo[0] = new ResultParcelInfo($arrStdResultParcelInfo);
                }
            }
            return $arrResultParcelInfo;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::trackPicking()
     */
    public function trackPicking($sessionId, $billOfLading) {
        try {
            $trackPickingStdObject = new stdClass();
            $trackPickingStdObject->sessionId    = $sessionId;
            $trackPickingStdObject->billOfLading = $billOfLading;
            $response = parent::trackPicking($trackPickingStdObject);
            $arrResultTrackPicking = array();
            if (isset($response->return)) {
                $arrStdResultTrackPicking = $response->return;
                if (is_array($arrStdResultTrackPicking)) {
                    for($i = 0; $i < count($arrStdResultTrackPicking); $i++) {
                        $arrResultTrackPicking[$i] = new ResultTrackPicking($arrStdResultTrackPicking[$i]);
                    }
                } else {
                    $arrResultTrackPicking[0] = new ResultTrackPicking($arrStdResultTrackPicking);
                }
            }
            return $arrResultTrackPicking;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::trackPickingEx()
     */
    public function trackPickingEx($sessionId, $billOfLading, $language, $returnOnlyLastOperation) {
        try {
            $trackPickingExStdObject = new stdClass();
            $trackPickingExStdObject->sessionId               = $sessionId;
            $trackPickingExStdObject->billOfLading            = $billOfLading;
            $trackPickingExStdObject->language                = $language;
            $trackPickingExStdObject->returnOnlyLastOperation = $returnOnlyLastOperation;
            $response = parent::trackPickingEx($trackPickingExStdObject);
            $arrResultTrackPickingEx = array();

            if (isset($response->return)) {
                $arrStdResultTrackPickingEx = $response->return;
                if (is_array($arrStdResultTrackPickingEx)) {
                    for($i = 0; $i < count($arrStdResultTrackPickingEx); $i++) {
                        $arrResultTrackPickingEx[$i] = new ResultTrackPickingEx($arrStdResultTrackPickingEx[$i]);
                    }
                } else {
                    $arrResultTrackPickingEx[0] = new ResultTrackPickingEx($arrStdResultTrackPickingEx);
                }
            }
            return $arrResultTrackPickingEx;
        } catch (SoapFault $sf) {
				throw new ServerException($sf);
        }
    }

    /**
     * @see EPSInterface::searchPickingsByRefNumber()
     */
    public function searchPickingsByRefNumber($sessionId, $params) {
        try {
            $searchPickingsByRefNumberStdObject = new stdClass();
            $searchPickingsByRefNumberStdObject->sessionId = $sessionId;
            $searchPickingsByRefNumberStdObject->params    = $params->toStdClass();
            $response = parent::searchPickingsByRefNumber($searchPickingsByRefNumberStdObject);
            $arrSearchPickingsByRefNumber = array();
            if (isset($response->return)) {
                $arrStdSearchPickingsByRefNumber = $response->return;
                if (is_array($arrStdSearchPickingsByRefNumber)) {
                    for($i = 0; $i < count($arrStdSearchPickingsByRefNumber); $i++) {
                        $arrSearchPickingsByRefNumber[$i] = $arrStdSearchPickingsByRefNumber[$i];
                    }
                } else {
                    $arrSearchPickingsByRefNumber[0] = $arrStdSearchPickingsByRefNumber;
                }
            }
            return $arrSearchPickingsByRefNumber;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::trackParcel()
     */
    public function trackParcel($sessionId, $parcelId, $language, $returnOnlyLastOperation) {
        try {
            $trackParcelStdObject = new stdClass();
            $trackParcelStdObject->sessionId               = $sessionId;
            $trackParcelStdObject->parcelId                = $parcelId;
            $trackParcelStdObject->language                = $language;
            $trackParcelStdObject->returnOnlyLastOperation = $returnOnlyLastOperation;
            $response = parent::trackParcel($trackParcelStdObject);
            $arrResultTrackParcel = array();
    
            if (isset($response->return)) {
                $arrStdResultTrackParcel = $response->return;
                if (is_array($arrStdResultTrackParcel)) {
                    for($i = 0; $i < count($arrStdResultTrackParcel); $i++) {
                        $arrResultTrackParcel[$i] = new ResultTrackPickingEx($arrStdResultTrackParcel[$i]);
                    }
                } else {
                    $arrResultTrackParcel[0] = new ResultTrackPickingEx($arrStdResultTrackParcel);
                }
            }
            return $arrResultTrackParcel;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::trackParcelMultiple()
     */
    public function trackParcelMultiple($sessionId, $barcodes, $language, $returnOnlyLastOperation) {
  	  try {
            $trackParcelMultipleStdObject = new stdClass();
            $trackParcelMultipleStdObject->sessionId               = $sessionId;
            $trackParcelMultipleStdObject->barcodes                = $barcodes;
            $trackParcelMultipleStdObject->language                = $language;
            $trackParcelMultipleStdObject->returnOnlyLastOperation = $returnOnlyLastOperation;
            $response = parent::trackParcelMultiple($trackParcelMultipleStdObject);
            $arrResultTrackParcelMultiple = array();
    
            if (isset($response->return)) {
                $arrStdResultTrackParcelMultiple = $response->return;
                if (is_array($arrStdResultTrackParcelMultiple)) {
                    for($i = 0; $i < count($arrStdResultTrackParcelMultiple); $i++) {
                        $arrResultTrackParcelMultiple[$i] = new ResultTrackPickingEx($arrStdResultTrackParcelMultiple[$i]);
                    }
                } else {
                    $arrResultTrackParcelMultiple[0] = new ResultTrackPickingEx($arrStdResultTrackParcelMultiple);
                }
            }
            return $arrResultTrackParcelMultiple;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::getMicroregionId
     */
    public function getMicroregionId($sessionId, $coordX, $coordY) {
        try {
            $getMicroregionIdSdtClass = new stdClass();
            $getMicroregionIdSdtClass->sessionId = $sessionId;
            $getMicroregionIdSdtClass->coordX    = $coordX;
            $getMicroregionIdSdtClass->coordY    = $coordY;
            $response = parent::getMicroregionId($getMicroregionIdSdtClass);
            if (isset($response->return)) {
                $microregionId = $response->return;
            } else {
                $microregionId = null;
            }
            return $microregionId;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::searchClients()
     */
    public function searchClients($sessionId, $clientQuery) {
        try {
            $searchClientsStdObject = new stdClass();
            $searchClientsStdObject->sessionId   = $sessionId;
            $searchClientsStdObject->clientQuery = $clientQuery->toStdClass();
            $response = parent::searchClients($searchClientsStdObject);
            $arrResultClientData = array();
        
            if (isset($response->return)) {
                $arrStdResultClientData = $response->return;
                if (is_array($arrStdResultClientData)) {
                    for($i = 0; $i < count($arrStdResultClientData); $i++) {
                        $arrResultClientData[$i] = new ResultClientData($arrStdResultClientData[$i]);
                    }
                } else {
                    $arrResultClientData[0] = new ResultClientData($arrStdResultClientData);
                }
            }
            return $arrResultClientData;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::listSpecialDeliveryRequirements()
     */
    public function listSpecialDeliveryRequirements($sessionId) {
    	try {    
    		$listSpecialDeliveryRequirementsStdObject = new stdClass();
            $listSpecialDeliveryRequirementsStdObject->sessionId = $sessionId;
            $response = parent::listSpecialDeliveryRequirements($listSpecialDeliveryRequirementsStdObject);
            $arrResultSpecialDeliveryRequirement = array();
        
            if (isset($response->return)) {
                $arrStdResultSpecialDeliveryRequirement = $response->return;
                if (is_array($arrStdResultSpecialDeliveryRequirement)) {
                    for($i = 0; $i < count($arrStdResultSpecialDeliveryRequirement); $i++) {
                        $arrResultSpecialDeliveryRequirement[$i] = new ResultSpecialDeliveryRequirement($arrStdResultSpecialDeliveryRequirement[$i]);
                    }
                } else {
                    $arrResultSpecialDeliveryRequirement[0] = new ResultSpecialDeliveryRequirement($arrStdResultSpecialDeliveryRequirement);
                }
            }
            return $arrResultSpecialDeliveryRequirement;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
       
    /**
     * @see EPSInterface::validateAddress()
     */
    public function validateAddress($sessionId, $address, $validationMode) {
		try {
       		$validateAddressStdObject = new stdClass();
       		$validateAddressStdObject->sessionId = $sessionId;
       		$validateAddressStdObject->address   = $address->toStdClass();
       		$validateAddressStdObject->validationMode = $validationMode;
       		$response = parent::validateAddress($validateAddressStdObject);
            return $response->return;
       	} catch (SoapFault $sf) {
       		throw new ServerException($sf);
       	}
    }
       
	/**
	 * @see EPSInterface::listContractClients()
	 */
	public function listContractClients($sessionId) {
		try {
       		$listContractClientsStdObject = new stdClass();
       		$listContractClientsStdObject->sessionId = $sessionId;
       		$response = parent::listContractClients($listContractClientsStdObject);
       		$arrResultContractClients = array();
       
       		if (isset($response->return)) {
       			$arrStdResultContractClients = $response->return;
       			if (is_array($arrStdResultContractClients)) {
       				for($i = 0; $i < count($arrStdResultContractClients); $i++) {
       					$arrResultContractClients[$i] = new ResultClientData($arrStdResultContractClients[$i]);
       				}
       			} else {
       				$arrResultContractClients[0] = new ResultClientData($arrStdResultContractClients);
       			}
       		}
       		return $arrResultContractClients;
       	} catch (SoapFault $sf) {
       		throw new ServerException($sf);
       	}
	}
       
	/**
	 * @see EPSInterface::listOfficesEx()
	 */
	public function listOfficesEx($sessionId, $name, $siteId, $language, $countryId) {
		try {
       		$listOfficesExStdObject = new stdClass();
       		$listOfficesExStdObject->sessionId = $sessionId;
       		$listOfficesExStdObject->name      = $name;
       		$listOfficesExStdObject->siteId    = $siteId;
       		$listOfficesExStdObject->language  = $language;
       		$listOfficesExStdObject->countryId = $countryId;
       		$response = parent::listOfficesEx($listOfficesExStdObject);
       		$arrListOfficesEx = array();
       		if (isset($response->return)) {
       			$arrStdListOfficesEx = $response->return;
       			if (is_array($arrStdListOfficesEx)) {
       				for($i = 0; $i < count($arrStdListOfficesEx); $i++) {
       					$arrListOfficesEx[$i] = new ResultOfficeEx($arrStdListOfficesEx[$i]);
       				}
       			} else {
       				$arrListOfficesEx[0] = new ResultOfficeEx($arrStdListOfficesEx);
       			}
       		}
       		return $arrListOfficesEx;
       	} catch (SoapFault $sf) {
       		throw new ServerException($sf);
       	}
	}
	
	/**
	 * @see EPSInterface::deserializeAddress()
	 */
	public function deserializeAddress($sessionId, $address) {
		try {
       		$deserializeAddressStdObject = new stdClass();
       		$deserializeAddressStdObject->sessionId = $sessionId;
       		$deserializeAddressStdObject->address   = $address;
       		$response = parent::deserializeAddress($deserializeAddressStdObject);
       		if (isset($response->return)) {
       			$paramAddress = new ParamAddress($response->return);
       		} else {
       			$paramAddress = null;
       		}
       		return $paramAddress;
       	} catch (SoapFault $sf) {
       		throw new ServerException($sf);
       	}
	}
	
	/**
	 * @see EPSInterface::serializeAddress()
	 */
	public function serializeAddress($sessionId, $address) {
		try {
       		$serializeAddressStdObject = new stdClass();
       		$serializeAddressStdObject->sessionId = $sessionId;
       		$serializeAddressStdObject->address   = $address->toStdClass();
       		$response = parent::serializeAddress($serializeAddressStdObject);
       		if (isset($response->return)) {
       			$serializedAddress = $response->return;
       		} else {
       			$serializedAddress = null;
       		}
       		return $serializedAddress;
       	} catch (SoapFault $sf) {
       		throw new ServerException($sf);
       	}
	}
	
	/**
	 * @see EPSInterface::makeAddressString()
	 */
	public function makeAddressString($sessionId, $address) {
		try {
       		$makeAddressStringStdObject = new stdClass();
       		$makeAddressStringStdObject->sessionId = $sessionId;
       		$makeAddressStringStdObject->address   = $address->toStdClass();;
       		$response = parent::makeAddressString($makeAddressStringStdObject);
       		if (isset($response->return)) {
       			$resultAddressString = new ResultAddressString($response->return);
       		} else {
       			$resultAddressString = null;
       		}
       		return $resultAddressString;
       	} catch (SoapFault $sf) {
       		throw new ServerException($sf);
       	}
	}
	
	/**
	 * @see EPSInterface::getAdditionalUserParams()
	 */
	public function getAdditionalUserParams($sessionId, $date) {
		try {
       		$getAdditionalUserParamsStdObject = new stdClass();
       		$getAdditionalUserParamsStdObject->sessionId = $sessionId;
       		$getAdditionalUserParamsStdObject->date      = $date;
       		$response = parent::getAdditionalUserParams($getAdditionalUserParamsStdObject);
       		$arrListAdditionalUserParams = array();
       		if (isset($response->return)) {
       			$arrStdListAdditionalUserParams = $response->return;
       			if (is_array($arrStdListAdditionalUserParams)) {
       				for($i = 0; $i < count($arrStdListAdditionalUserParams); $i++) {
       					$arrListAdditionalUserParams[$i] = $arrStdListAdditionalUserParams[$i];
       				}
       			} else {
       				$arrListAdditionalUserParams[0] = $arrStdListAdditionalUserParams;
       			}
       		}
       		return $arrListAdditionalUserParams;
       	} catch (SoapFault $sf) {
       		throw new ServerException($sf);
       	}
	}	
	
	/**
     * @see EPSInterface::listCountries()
     */
    public function listCountries($sessionId, $name, $language) {
        try {
            $listCountriesStdObject = new stdClass();
            $listCountriesStdObject->sessionId = $sessionId;
            $listCountriesStdObject->name      = $name;
            $listCountriesStdObject->language  = $language;
            
            $response = parent::listCountries($listCountriesStdObject);
            $arrListCountriesStdObject = array();
            if (isset($response->return)) {
                $arrStdListCountriesStdObject = $response->return;
                if (is_array($arrStdListCountriesStdObject)) {
                    for($i = 0; $i < count($arrStdListCountriesStdObject); $i++) {
                        $arrListCountriesStdObject[$i] = new ResultCountry($arrStdListCountriesStdObject[$i]);
                    }
                } else {
                    $arrListCountriesStdObject[0] = new ResultCountry($arrStdListCountriesStdObject);
                }
            }
            return $arrListCountriesStdObject;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::listCountriesEx()
     */
    public function listCountriesEx($sessionId, $filter, $language) {
        try {
            $listCountriesExStdObject = new stdClass();
            $listCountriesExStdObject->sessionId = $sessionId;
            $listCountriesExStdObject->filter    = $filter->toStdClass();
            $listCountriesExStdObject->language  = $language;
            
            $response = parent::listCountriesEx($listCountriesExStdObject);
            $arrListCountriesExStdObject = array();
            if (isset($response->return)) {
                $arrStdListCountriesExStdObject = $response->return;
                if (is_array($arrStdListCountriesExStdObject)) {
                    for($i = 0; $i < count($arrStdListCountriesExStdObject); $i++) {
                        $arrListCountriesExStdObject[$i] = new ResultCountry($arrStdListCountriesExStdObject[$i]);
                    }
                } else {
                    $arrListCountriesExStdObject[0] = new ResultCountry($arrStdListCountriesExStdObject);
                }
            }
            return $arrListCountriesExStdObject;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::listStates()
     */
    public function listStates($sessionId, $countryId, $name) {
        try {
            $listStatesStdObject = new stdClass();
            $listStatesStdObject->sessionId = $sessionId;
            $listStatesStdObject->countryId = $countryId;
            $listStatesStdObject->name      = $name;
            
            $response = parent::listStates($listStatesStdObject);
            $arrListStatesStdObject = array();
            if (isset($response->return)) {
                $arrStdListStatesStdObject = $response->return;
                if (is_array($arrStdListStatesStdObject)) {
                    for($i = 0; $i < count($arrStdListStatesStdObject); $i++) {
                        $arrListStatesStdObject[$i] = new ResultState($arrStdListStatesStdObject[$i]);
                    }
                } else {
                    $arrListStatesStdObject[0] = new ResultState($arrStdListStatesStdObject);
                }
            }
            return $arrListStatesStdObject;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }   
    
    /**
     * @see EPSInterface::getStateById()
     */
    public function getStateById($sessionId, $stateId) {
        try {
            $getStateByIdStdObject = new stdClass();
            $getStateByIdStdObject->sessionId = $sessionId;
            $getStateByIdStdObject->stateId   = $stateId;
            $response = parent::getStateById($getStateByIdStdObject);
            if (isset($response->return)) {
                $resultState = new ResultState($response->return);
            } else {
                $resultState = null;
            }
            return $resultState;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }  
    
    /**
     * @see EPSInterface::getStateById()
     */
    public function validatePostCode($sessionId, $countryId, $postCode, $siteId) {
        try {
            $validatePostCodeStdObject = new stdClass();
            $validatePostCodeStdObject->sessionId = $sessionId;
            $validatePostCodeStdObject->countryId = $countryId;
            $validatePostCodeStdObject->postCode = $postCode;
            $validatePostCodeStdObject->siteId = $siteId;
            $response = parent::validatePostCode($validatePostCodeStdObject);
            if (isset($response->return)) {
                $resultFlag = $response->return;
            } else {
                $resultFlag = null;
            }
            return $resultFlag;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::getStateById()
     */
    public function getPickingDeliveryInfo($sessionId, $billOfLading, $language) {
       try {
            $getPickingDeliveryInfoStdObject = new stdClass();
            $getPickingDeliveryInfoStdObject->sessionId    = $sessionId;
            $getPickingDeliveryInfoStdObject->billOfLading = $billOfLading;
            $getPickingDeliveryInfoStdObject->language     = $language;
            
            $response = parent::getPickingDeliveryInfo($getPickingDeliveryInfoStdObject);
            $arrResultTrackPickingExStdObject = array();
            if (isset($response->return)) {
                $arrStdResultTrackPickingExStdObject = $response->return;
                if (is_array($arrStdResultTrackPickingExStdObject)) {
                    for($i = 0; $i < count($arrStdResultTrackPickingExStdObject); $i++) {
                        $arrResultTrackPickingExStdObject[$i] = new ResultTrackPickingEx($arrStdResultTrackPickingExStdObject[$i]);
                    }
                } else {
                    $arrResultTrackPickingExStdObject[0] = new ResultTrackPickingEx($arrStdResultTrackPickingExStdObject);
                }
            }
            return $arrResultTrackPickingExStdObject;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }
    
    /**
     * @see EPSInterface::searchSecondaryPickings()
     */
    public function searchSecondaryPickings($sessionId, $paramSearchSecondaryPickings) {
   		try {
            $searchSecondaryPickingsStdObject = new stdClass();
            $searchSecondaryPickingsStdObject->sessionId    = $sessionId;
            $searchSecondaryPickingsStdObject->paramSearchSecondaryPickings = $paramSearchSecondaryPickings->toStdClass();
            $response = parent::searchSecondaryPickings($searchSecondaryPickingsStdObject);
            
            $arrResultPickingInfoStdObject = array();
            if (isset($response->return)) {
                $arrStdResultPickingInfoObject = $response->return;
                if (is_array($arrStdResultPickingInfoObject)) {
                    for($i = 0; $i < count($arrStdResultPickingInfoObject); $i++) {
                        $arrResultPickingInfoStdObject[$i] = new ResultPickingInfo($arrStdResultPickingInfoObject[$i]);
                    }
                } else {
                    $arrResultPickingInfoStdObject[0] = new ResultPickingInfo($arrStdResultPickingInfoObject);
                }
            }
            return $arrResultPickingInfoStdObject;
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }  


    /**
     * @see EPSInterface::getPickingExtendedInfo()
     */
    public function getPickingExtendedInfo($sessionId, $billOfLading) {
   		try {
            $getPickingExtendedInfoStdObject = new stdClass();
            $getPickingExtendedInfoStdObject->sessionId    = $sessionId;
            $getPickingExtendedInfoStdObject->billOfLading = $billOfLading;
            $response = parent::getPickingExtendedInfo($getPickingExtendedInfoStdObject);
            
            if (isset($response->return)) {
                $ResultPickingExtendedInfo = new ResultPickingExtendedInfo($response->return);
            } else {
                $ResultPickingExtendedInfo = null;
            }
            return $ResultPickingExtendedInfo;
				
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }  


    /**
     * @see EPSInterface::getRoutingLabelInfo()
     */
    public function getRoutingLabelInfo($sessionId, $parcelId) {
   		try {
            $getRoutingLabelInfoStdObject = new stdClass();
            $getRoutingLabelInfoStdObject -> sessionId = $sessionId;
            $getRoutingLabelInfoStdObject -> parcelId = $parcelId;
				$response = parent::getRoutingLabelInfo($getRoutingLabelInfoStdObject);
            
            if (isset($response->return)) {
                $ResultRoutingLabelInfo = new ResultRoutingLabelInfo($response->return);
            } else {
                $ResultRoutingLabelInfo = null;
            }
            return $ResultRoutingLabelInfo;
				
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }  



    /**
     * @see EPSInterface::convertToWin1251()
    */
    public function convertToWin1251($sessionId, $text) {
   		try {
            $convertToWin1251StdObject = new stdClass();
            $convertToWin1251StdObject -> sessionId = $sessionId;
            $convertToWin1251StdObject -> text = $text;
            $response = parent::convertToWin1251($convertToWin1251StdObject);
            
            if (isset($response->return)) {
					 $ResultConvertToWin1251 = $response->return;
            } else {
                $ResultConvertToWin1251 = null;
            }
            return $ResultConvertToWin1251;
				
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }  


    /**
     * @see EPSInterface::mapForeignBarcode()
     */
    public function mapForeignBarcode($sessionId, $parcelId, $foreignParcelNumber) {
   		try {
            $mapForeignBarcodeObject = new stdClass();
            $mapForeignBarcodeObject -> sessionId = $sessionId;
            $mapForeignBarcodeObject -> parcelId = $parcelId;
            $mapForeignBarcodeObject -> foreignParcelNumber = $foreignParcelNumber;
            $response = parent::mapForeignBarcode($mapForeignBarcodeObject);
            
            if (isset($response->return)) {
					 $ResultMapForeignBarcode = $response->return;
            } else {
                $ResultMapForeignBarcode = null;
            }

            return $ResultMapForeignBarcode;
				
        } catch (SoapFault $sf) {
            throw new ServerException($sf);
        }
    }  
}
?>