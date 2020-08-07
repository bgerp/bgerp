<?php

require_once 'ServerException.class.php';
require_once 'ResultAddressString.class.php';
require_once 'ResultLogin.class.php';
require_once 'ResultSite.class.php';
require_once 'ResultSiteEx.class.php';
require_once 'ResultCourierService.class.php';
require_once 'ResultCourierServiceExt.class.php';
require_once 'ResultMinMaxReal.class.php';
require_once 'ResultStreet.class.php';
require_once 'ResultQuarter.class.php';
require_once 'ResultCommonObject.class.php';
require_once 'ResultOffice.class.php';
require_once 'ResultOfficeEx.class.php';
require_once 'ResultClientData.class.php';
require_once 'ResultAddressSearch.class.php';
require_once 'ResultCalculation.class.php';
require_once 'ResultCalculationMS.class.php';
require_once 'ResultBOL.class.php';
require_once 'ResultOrderPickingInfo.class.php';
require_once 'ResultTrackPicking.class.php';
require_once 'ResultTrackPickingEx.class.php';
require_once 'ResultSpecialDeliveryRequirement.class.php';
require_once 'ResultCountry.class.php';
require_once 'ResultState.class.php';
require_once 'ResultPickingInfo.class.php';
require_once 'ResultClientInfo.class.php';
require_once 'ResultAddressInfo.class.php';
require_once 'ResultRoutingLabelInfo.class.php';

require_once 'ResultPickingExtendedInfo.class.php';
require_once 'ParamCalculation.class.php';
require_once 'ParamFilterSite.class.php';
require_once 'ParamAddress.class.php';
require_once 'ParamAddressSearch.class.php';
require_once 'ParamFilterCountry.class.php';
require_once 'ParamLanguage.class.php';
require_once 'ParamPicking.class.php';
require_once 'ParamPDF.class.php';
require_once 'ParamParcel.class.php';
require_once 'ParamOrder.class.php';
require_once 'ParamSearchByRefNum.class.php';
require_once 'ParamClientSearch.class.php';
require_once 'ParamSearchSecondaryPickings.class.php';

/**
 * Speedy EPS Service Interface.
 * This interface should be implemented by all specific protocol class implementations (SOAP, REST, etc.)
 */
interface EPSInterface {

    /**
     * Login web service method
     * @since 1.0
     * @param string $username User name
     * @param string $password User password
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultLogin Result of login
     */
    public function login($username, $password);

    /**
     * Returns whether the session is active
     * @since 1.0
     * @param string $sessionId The session ID to be tested
     * @param boolean $refreshSession In case the session is active, this parameter specifies if the session should be automatically refreshed
     * @throws ServerException Thrown in case communication with server has failed
     * @return boolean Session active flag
    */
    public function isSessionActive($sessionId, $refreshSession);

    /**
     * Returns the list of courier services valid on this date
     * @since 1.0
     * @param string $sessionId
     * @param date $date
     * @param ParamLanguage $language Language (added in 2.5.0)
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultCourierService instances
    */
    public function listServices($sessionId, $date, $language);

    /**
     * Returns the list of courier services valid on this date and sites.
     * @since 1.0
     * @param string $sessionId
     * @param datetime $date
     * @param integer $senderSiteId Signed 64-bit integer sender's site ID;
     * @param integer $receiverSiteId Signed 64-bit integer receiver's site ID;
     * @param integer $senderCountryId Signed 64-bit integer sender's country ID (added in 2.5.0);
     * @param string $senderPostCode sender's post code (added in 2.5.0);
     * @param integer $receiverCountryId Signed 64-bit integer receiver's country ID (added in 2.5.0);
     * @param string $receiverPostCode receiver's post code (added in 2.5.0);
     * @param ParamLanguage $language language (added in 2.5.0)
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultCourierServiceExt instances
     * @param $senderId  (signed 64-bit integer nullable) – Sender ID (added in 2.9.0);
     * @param $receiverId (signed 64-bit integer nullable) – Receiver ID (added in 2.9.0);
     * @param $senderOfficeId (signed 64-bit integer nullable) – Sender office ID (added in 2.9.0);
     * @param $receiverOfficeId (signed 64-bit integer nullable) – Receiver office ID (added in 2.9.0);
    */
    public function listServicesForSites(
        $sessionId, $date, $senderSiteId, $receiverSiteId, 
        $senderCountryId, $senderPostCode, $receiverCountryId, $receiverPostCode, $language,
        $senderId, $receiverId, $senderOfficeId, $receiverOfficeId
    );

    /**
     * Returns a list of sites matching the search criteria.
     * The result is limited to 10 records
     * The method is deprecated starting from 2.5.0, "listSitesEx" should be used instead.
     * @deprecated The method is deprecated, "createPDF" should be used instead.
     * @since 1.0
     * @param string $sessionId
     * @param string $type
     * @param string $name
     * @param ParamLanguage $paramLanguage
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultSite instances
    */
    public function listSites($sessionId, $type, $name, $paramLanguage);

    /**
     * Returns a list of sites. The method aims to find the closest matches.
     * The result is limited to 10 records
     * @since 1.0
     * @param string $sessionId
     * @param ParamFilterSite $paramFilterSite
     * @param ParamLanguage $language
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultSiteEx instances
    */
    public function listSitesEx($sessionId, $paramFilterSite, $language);

    /**
     * Returns the min/max weight allowed for the given shipment parameters
     * @since 1.0
     * @param string $sessionId
     * @param integer $serviceTypeId Signed 64-bit ID of the courier service
     * @param integer $senderSiteId Signed 64-bit Sender's site ID
     * @param integer $receiverSiteId Signed 64-bit Receiver's site ID
     * @param date $date
     * @param boolean $documents Specifies if the shipment consists of documents
     * @param integer $senderCountryId Signed 64-bit Sender's country ID (added in 2.5.0)
     * @param string $senderPostCode Sender's post code (added in 2.5.0)
     * @param integer $receiverCountryId Signed 64-bit Receiver's country ID (added in 2.5.0) 
     * @param string $receiverPostCode Receiver's post code (added in 2.5.0)
     * @throws ServerException Thrown in case communication with server has failed
     * @param $senderId  (signed 64-bit integer nullable) – Sender ID (added in 2.9.0);
     * @param $receiverId (signed 64-bit integer nullable) – Receiver ID (added in 2.9.0);
     * @param $senderOfficeId (signed 64-bit integer nullable) – Sender office ID (added in 2.9.0);
     * @param $receiverOfficeId (signed 64-bit integer nullable) – Receiver office ID (added in 2.9.0);
     * @return ResultMinMaxReal
    */
    public function getWeightInterval(
        $sessionId, $serviceTypeId, $senderSiteId, $receiverSiteId, $date, $documents,
        $senderCountryId, $senderPostCode, $receiverCountryId, $receiverPostCode,
        $senderId, $receiverId, $senderOfficeId, $receiverOfficeId
    );

    /**
     * Returns CSV-formatted data (depending on the nomenType value).
     * Column numbers can change in the future so it's recommended to address the data using the column names in the header row.
     * The data for some nomenTypes requires a payed license (additional licensing contract) and permissions (access rights).
     * To obtain such license please contact our IT department or your Speedy key account manager.
     * Type 1   - returns a list of all countries
     * Type 50  - returns a list of all states
     * Type 100 - returns a list of all sites.
     * Type 300 - returns a list of all streets (requires a license).
     * Type 400 - returns a list of all quarters (requires a license).
     * Type 500 - returns a list of all common objects (requires a license).
     * Type 700 - returns a list of all block names (requires a license).
     * Type 800 - returns a list of all post codes (requires a license).
     * @since 1.0
     * @param string $sessionId
     * @param integer $nomenType Signed 32-bit The type of address nomenclature
     * @param integer $countryId Signed 64-bit Country id (added in 2.5.0)
     * @throws ServerException Thrown in case communication with server has failed
     * @return string CSV formatted
    */
    public function getAddressNomenclature($sessionId, $nomenType, $countryId);

    /**
     * Returns a list of all sites.
     * Note: This method is relatively slow (because of the size of its response). You shouldn't call it more than several times a day.
     * The methods is designed to provide data which should be locally stored/cached by client apps.
     * The address-related nomenclature data is updated only several times a year.
     * @since 1.0
     * @param string $sessionId
     * @param ParamLanguage $paramLanguage Language
     * @param integer $countryId signed 64-bit Country id (added in 2.5.0)
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultSite instances
    */
    public function listAllSites($sessionId, $paramLanguage, $countryId);

    /**
     * Returns a site by ID
     * @since 1.0
     * @param string $sessionId
     * @param integer $siteId Signed 64-bit Site ID
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultSite
    */
    public function getSiteById($sessionId, $siteId);

    /**
     * Returns sites having either full or partial address nomenclature (streets, quarters etc.).
     * @since 1.0
     * @param string $sessionId
     * @param AddrNomen $addrNomen Only values FULL and PARTIAL are allowed
     * @throws ServerException Thrown in case communication with server has failed
     * @return List of ResultSite
    */
    public function getSitesByAddrNomenType($sessionId, $addrNomen);

    /**
     * Returns a list of the most common types of streets.
     * @since 1.0
     * @param string $sessionId
     * @throws ServerException Thrown in case communication with server has failed
     * @return array string List of the most common types of streets
    */
    public function listStreetTypes($sessionId, $language);

    /**
     * Returns a list of the most common types of quarters (districts).
     * @since 1.0
     * @param string $sessionId
     * @param ParamLanguage $language
     * @throws ServerException Thrown in case communication with server has failed
     * @return array string List of the most common types of quarters (districts).
    */
    public function listQuarterTypes($sessionId, $language);

    /**
     * Returns a list of streets matching the search criteria
     * The list is limited to 10 records.
     * @since 1.0
     * @param string $sessionId
     * @param string $name Street name (or part of it)
     * @param integer $siteId Signed 64-bit Site ID
     * @param ParamLanguage $language Language
     * @throws ServerException Thrown in case communication with server has failed
     * @return array ResultStreet List of streets
    */
    public function listStreets($sessionId, $name, $siteId, $language);

    /**
     * Returns a list of quarters matching the search criteria
     * The list is limited to 10 records.
     * @since 1.0
     * @param string $sessionId
     * @param string $name Quarter name (or part of it)
     * @param integer $siteId Signed 64-bit Site ID
     * @param ParamLanguage $language Language
     * @throws ServerException Thrown in case communication with server has failed
     * @return array ResultQuarter List of streets
    */
    public function listQuarters($sessionId, $name, $siteId, $language);

    /**
     * Returns a list of common objects matching the search criteria.
     * The list is limited to 10 records.
     * @since 1.0
     * @param string $sessionId
     * @param string $name Common object name (or part of it)
     * @param integer $siteId Signed 64-bit Site ID
     * @param ParamLanguage $language Language
     * @throws ServerException Thrown in case communication with server has failed
     * @return array ResultCommonObject List of common objects
    */
    public function listCommonObjects($sessionId, $name, $siteId, $language);

    /**
     * Returns a list of blocks matching the search criteria.
     * The list is limited to 10 records.
     * @since 1.0
     * @param string $sessionId
     * @param string $name Block name (or part of it)
     * @param integer $siteId Signed 64-bit Site ID
     * @param ParamLanguage $language Language
     * @throws ServerException Thrown in case communication with server has failed
     * @return array string List of blocks
    */
    public function listBlocks($sessionId, $name, $siteId, $language);

    /**
     * Returns a list of Speedy offices matching the search criteria
     * The list is limited to 10 records.
     * @since 1.0
     * @param string $sessionId
     * @param string $name Office name (or part of it);
     * @param integer $siteId Signed 64-bit Site ID
     * @throws ServerException Thrown in case communication with server has failed
     * @return array ResultOffice List of offices
    */
    public function listOffices($sessionId, $name, $siteId);

    /**
     * Returns data for client by ID.
     * Allowed values for clientId are only the ones of members of the user's contract and the predefined partners
     * in the WebClients application.
     * @since 1.0
     * @param string $sessionId
     * @param integer $clientId Signed 64-bit integer – Client/Partner ID
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultClientData
    */
    public function getClientById($sessionId, $clientId);

    /**
     * Returns the dates when the shipment can be ordered for pick-up.
     * The "time" component represents the deadline for creating an order
     * (or the deadline for delivering the shipment to a Speedy office when senderOfficeId is set).
     * (This method could be used for the "takingDate" property of ParamPicking or ParamCalculation.)
     * Note: Either senderSiteId or senderOfficeId should be set, or neither of them. Both parameters having "not null" values is not allowed.
     * @since 1.0
     * @param string $sessionId
     * @param integer $serviceTypeId
     * @param integer $senderSiteId Signed 64-bit – Sender's site ID
     * @param integer $senderOfficeId Signed 64-bit – If the sender intends to deliver the shipment to a Speedy office, the office ID could be set as a filter
     * @param date $minDate - When the "time" component is set then this date is to be included in the result list only if the time is not after the working time of Speedy;
     * @param integer $senderCountryId Signed 64-bit Sender's country id (added in 2.5.0)
     * @param string $senderPostCode Sender's post code (added in 2.5.0)
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of dates
     * @param integer $senderId Signed 64-bit Sender's ID (added in 2.9.0)
    */
    public function getAllowedDaysForTaking(
        $sessionId, $serviceTypeId, $senderSiteId, $senderOfficeId, $minDate, $senderCountryId, $senderPostCode, $senderId
    );

    /**
     * Returns a list of addresses matching the search criteria.
     * @since 1.0
     * @param string $sessionId
     * @param ParamAddressSearch $address Search criteria (filter)
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultAddressSearch
    */
    public function addressSearch($sessionId, $address);

    /**
     * Calculate method
     * @since 1.0
     * @param string $sessionId Session ID
     * @param ParamCalculation $paramCalculation
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultCalculation
    */
    public function calculate($sessionId, $calculation);

    /**
     * This method could be used for preliminary check-up of shipment's price for a range of courier services.
     * Service type ID field of $calculation structure is overriden by this method to required value (0) before call.
     * @since 1.0
     * @param string $sessionId
     * @param ParamCalculation $calculation Data needed to perform the calculation;
     * @param array $serviceTypeIds List<signed 64-bit integer> – A list of courier service IDs for which price needs to be calculated
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultCalculationMS
    */
    public function calculateMultipleServices($sessionId, $calculation, $serviceTypeIds);

    /**
     * This is an alternative method for shipment price calculation where the parameter is of type ParamPicking.
     * Clients are encouraged to use the method that best fits their needs.
     * @since 1.0
     * @param string $sessionId
     * @param ParamPicking $picking
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultCalculation
    */
    public function calculatePicking($sessionId, $picking);

    /**
     * The method used to create BOL.
     * @since 1.0
     * @param string $sessionId
     * @param ParamPicking $picking Data for the shipment (BOL)
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultBOL
    */
    public function createBillOfLading($sessionId, $picking);

    /**
     * Used for creating PDF documents to be printed (BOLs, labels etc.)
     * Examples:
     * •Bill of Lading;
     * •Bill of Lading with "cash on delivery";
     * •Custom travel label (type 20);
     * •Custom travel label (type 20) with "cash on delivery".
     * @since 1.0
     * @param string $sessionId
     * @param ParamPDF $params
     * @throws ServerException Thrown in case communication with server has failed
     * @return array bytes
    */
    public function createPDF($sessionId, $params);

    /**
     * Creates PDF document for BOL.
     * The method is deprecated, "createPDF" should be used instead.
     * @deprecated The method is deprecated, "createPDF" should be used instead.
     * @since 1.0
     * @param string $sessionId
     * @param integer $billOfLading Signed 64-bit
     * @param boolean $includeAutoPrintJS Specifies if embedded JavaScript code for direct printing to be generated
     * @throws ServerException Thrown in case communication with server has failed
     * @return array of bytes
    */
    public function createBillOfLadingPDF($sessionId, $billOfLading, $includeAutoPrintJS);

    /**
     * Creates PDF document of "type I". The method is deprecated, "createPDF" should be used instead.
     * @deprecated The method is deprecated, "createPDF" should be used instead.
     * @since 1.0
     * @param string $sessionId
     * @param integer $parcelId Signed 64-bit Parcel ID
     * @throws ServerException Thrown in case communication with server has failed
     * @return array of bytes
    */
    public function createCustomTravelLabelPDFType1($sessionId, $parcelId);

    /**
     * Used to cancel BOL.
     * Only allowed when the shipment is neither ordered nor picked up by Speedy.
     * @since 1.0
     * @param string $sessionId
     * @param integer $billOfLading Signed 64-bit
     * @param string $cancelComment Cancel comment
     * @throws ServerException Thrown in case communication with server has failed
    */
    public function invalidatePicking($sessionId, $billOfLading, $cancelComment);

    /**
     * This method is used to update BOL.
     * Only allowed if BOL was created with pendingShipmentDescription = true.
     * @since 1.0
     * @param string $sessionId
     * @param ParamPicking $picking Data for the shipment (BOL)
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultBOL
    */
    public function updateBillOfLading($sessionId, $picking);

    /**
     * This method is used to add parcel to an existing BOL
     * (only allowed if BOL was created with pendingParcelsDescription = true).
     * @since 1.0
     * @param string $sessionId
     * @param ParamParcel $parcel Parcel data
     * @throws ServerException Thrown in case communication with server has failed
     * @return integer Signed 64-bit parcel's ID
    */
    public function addParcel($sessionId, $parcel);

    /**
     * Makes BOL "fully created".
     * Only applies to BOLs created with pendingParcelsDescription = true.
     * @since 1.0
     * @param string $sessionId
     * @param integer $billOfLading Signed 64-bit
     * @throws ServerException Thrown in case communication with server has failed
     * @return ResultBOL
    */
    public function finalizeBillOfLadingCreation($sessionId, $billOfLading);

    /**
     * Creates an order for shipments pick-up (i.e. a visit by courier of Speedy).
     * The returned list contains objects corresponding to each BOL (one object per BOL).
     * When the validation errors list of at least one of the objects is not empty, that means the order has not been created.
     * @since 1.0
     * @param string $sessionId
     * @param ParamOrder $order Order details
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultOrderPickingInfo
    */
    public function createOrder($sessionId, $order);

    /**
     * Returns a list with all parcels of a shipment.
     * @since 1.0
     * @param string $sessionId
     * @param integer $billOfLading Signed 64-bit
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultParcelInfo
    */
    public function getPickingParcels($sessionId, $billOfLading);

    /**
     * This method can be used to track the state/history of a shipment.
     * @deprecated Use trackPickingEx instead
     * @since 1.0
     * @param string $sessionId
     * @param integer $billOfLading Signed 64-bit (@since 2.9.0 @param string $billOfLading)
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultTrackPicking
    */
    public function trackPicking($sessionId, $billOfLading);

    /**
     * This method can be used to track the state/history of a shipment.
     * @since 1.2
     * @param string $sessionId
     * @param integer $billOfLading Signed 64-bit (@since 2.9.0 @param string $billOfLading)
     * @param ParamLanguage $language BG or EN. If set to null the server defaults to BG
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultTrackPickingEx
     * @since 2.9.0
     * @param boolean returnOnlyLastOperation; false is the default value
    */
    public function trackPickingEx($sessionId, $billOfLading, $language, $returnOnlyLastOperation);
    
    /**
     * This method can be used to track the state/history of a shipment parcel.
     * @since 1.4
     * @param string $sessionId
     * @param integer $parcelId Signed 64-bit (@since 2.9.0 @param string $parcelId)
     * @param ParamLanguage $language BG or EN. If set to null the server defaults to BG
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultTrackPickingEx
     * @since 2.9.0
     * @param boolean returnOnlyLastOperation; false is the default value
     */
    public function trackParcel($sessionId, $parcelId, $language, $returnOnlyLastOperation);
    
    /**
     * This method can be used to track the state/history of a shipment parcel.
     * @since 2.8.0
     * @param string $sessionId
     * @param List of integer (Signed 64-bit) $barcodes (@since 2.9.0 @param string $barcodes)
     * @param ParamLanguage $language BG or EN. If set to null the server defaults to BG
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultTrackPickingEx
     * @since 2.9.0
     * @param boolean returnOnlyLastOperation; false is the default value
     */
    public function trackParcelMultiple($sessionId, $barcodes, $language, $returnOnlyLastOperation); 

    /**
     * Search BOLs by reference codes (ref1 and/or ref2).
     * @since 1.0
     * @param string $sessionId
     * @param ParamSearchByRefNum $params
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of BOLs found (signed 64-bit integers)
    */
    public function searchPickingsByRefNumber($sessionId, $params);
    
    /**
     * Get microregion id for provided GPS corrdinates
     * @since 1.5
     * @param string $sessionId
     * @param signed 64-bit treal $coordX
     * @param signed 64-bit treal $coordY
     * @throws ServerException Thrown in case communication with server has failed
     * @return Mocregion id (signed 64-bit integer)
     */
    public function getMicroregionId($sessionId, $coordX, $coordY);
    
    /**
     * Returns data for clients by specified client ID or other search criteria.
     * If client ID is specified the behaviour of this method is the same as getClientById.
     * Otherwise, the search returns results that satisfy search criteria
     * @since 1.6
     * @param string $sessionId
     * @param ParamClientSearch $clientQuery
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultClientData
     */
    public function searchClients($sessionId, $clientQuery);
    
    /**
     * Returns list with available special delivery requirements for logged user
     * @param string $sessionId
     * @throws ServerException Thrown in case communication with server has failed
     * @return array List of ResultSpecialDeliveryRequirement
     * @since 2.1.0
     */
    public function listSpecialDeliveryRequirements($sessionId);
    
    /**
     * Validates address and returns validation result
     *   - validationMode = 0 (default) - Extended validation w/o GIS info (address uniqueness is not verified);
     *   - validationMode = 1 (NOT IMPLEMENTED YET - reserved for future implementation) Extended validation with GIS info (address uniqueness is verified);
     *   - validationMode = 2 - basic validation (the same as address validation in createBillOfLading)
     * @param string $sessionId
     * @param ParamAddress $address
     * @param integer $validationMode signed 32 bit
     * @throws ServerException Thrown in case communication with server has failed
     * @throws PickingValidationException Thrown in case address validation has failed
     * @return boolean Validation result flag
     * @since 2.2.0
     */
    public function validateAddress($sessionId, $address, $validationMode);
    
    /**
     * Returns all client objects ( including logged user's ) having the same contract as logged client's contract.
     * @param string $sessionId
     * @throws ServerException Thrown in case communication with server has failed
     * @return List of ResultClientData 
     * @since 2.2.0
     */
    public function listContractClients($sessionId);
    
    /**
     * Returns a list of Speedy offices matching the search criteria
     * The list is limited to 10 records.
     * @since 2.2.0
     * @param string $sessionId
     * @param string $name Office name (or part of it);
     * @param integer $siteId Signed 64-bit Site ID
     * @param ParamLanguage $language Language
     * @throws ServerException Thrown in case communication with server has failed
     * @return array ResultOfficeEx List of offices
     * @since 2.9.1
     * @param integer $countryId signed 64-bit integer nullable Country ID
     */
    public function listOfficesEx($sessionId, $name, $siteId, $language, $countryId);
    
    /**
     * Returns deserialized address from serialized string address
     * @param string $sessionId 
     * @param string $address Serialized address
     * @return ParamAddress Deserliazed address
     * @throws ServerException Thrown in case communication with server has failed
     * @since 2.3.0
     */
    public function deserializeAddress($sessionId, $address);
    
    /**
     * Returns deserialized address from serialized string address
     * @param string $sessionId
     * @param ParamAddress $address Address
     * @return Serialized string address
     * @throws ServerException Thrown in case communication with server has failed
     * @since 2.3.0
     */
    public function serializeAddress($sessionId, $address);
    
    /**
     * Make address text representations - city address, local address, full address
     * @param string $sessionId
     * @param ParamAddress $address Base address
     * @return ResultAddressString
     * @since 2.3.0
     */
    public function makeAddressString($sessionId, $address);
    
    /**
     * Get list of additional user paramters
     * @param string $sessionId
     * @param date $date Effective date. If null is provided then current date is applied
     * @return array signed 32-bit integers - List of additional user parameters
     * @since 2.3.0
     */
    public function getAdditionalUserParams($sessionId, $date);
    
    /**
     * Returns a list of countries matching the search criteria.
     * The result is limited to 10 records
     * @param string $sessionId
     * @param string $name Country name or part of it
     * @param ParamLanguage $language Language 
     * @return array of ResultCountry
     * @throws ServerException Thrown in case communication with server has failed
     * @since 2.5.0
     */
    public function listCountries($sessionId, $name, $language);
    
    /**
     * Returns a list of countries matching the search criteria.
     * The result is limited to 10 records
     * @param string $sessionId
     * @param ParamFilterCountry $filter Country search filter
     * @param ParamLanguage $language Language 
     * @return array of ResultCountry
     * @throws ServerException Thrown in case communication with server has failed
     * @since 2.5.0
     */
    public function listCountriesEx($sessionId, $filter, $language);
    
    /**
     * Returns a list of country states matching the search criteria.
     * The result is limited to 10 records
     * @param string $sessionId
     * @param integer $countryId signed 64-bit Country id
     * @param string $name Country state name or part of it
     * @return array of ResultState
     * @throws ServerException Thrown in case communication with server has failed
     * @since 2.5.0
     */
    public function listStates($sessionId, $countryId, $name);
    
    /**
     * Returns a country state by id
     * @param string $sessionId
     * @param string $stateId Country state id
     * @return ResultState Country state
     * @throws ServerException Thrown in case communication with server has failed
     * @since 2.5.0
     */
    public function getStateById($sessionId, $stateId);
    
    /**
     * Validates post code
     * @param string $sessionId
     * @param integer $countryId signed 64-bit Country id
     * @param string $postCode Post code
     * @param integer $siteId signed 64-bit Site id
     * @return True or false regarding the post code validation result
     * @throws ServerException Thrown in case communication with server has failed
     * @since 2.5.0
     */
    public function validatePostCode($sessionId, $countryId, $postCode, $siteId);
    
    /**
     * Returns a list with all not canceled pickings, which are secondary to the picking with the specified billOfLading. 
     * @param string $sessionId
     * @param ParamSearchSecondaryPickings $paramSearchSecondaryPickings
     * @return List of ResultPickingInfo Information about secondary pickings
     * @since 2.6.0
     */
    public function searchSecondaryPickings($sessionId, $paramSearchSecondaryPickings);

    /**
     * Returns extended information about the picking with the specified billOfLading. 
     * @param string $sessionId
     * @param  integer $billOfLading signed 64-bit
     * @return List of ResultPickingExtendedInfo Information about picking
     * @since 2.9.0
     */
    public function getPickingExtendedInfo($sessionId, $billOfLading);

    /**
     * Returns routing information for specified parcel number. 
     * @param string $sessionId
     * @param integer $parcelId  signed 64-bit
     * @return List of ResultRoutingLabelInfo routing information for specified parcel number
     * @since 3.0.0
     */
    public function getRoutingLabelInfo($sessionId, $parcelId);

    /**
     * Returns the transliterated input text as result, it excludes latin characters (a-z, A-Z), cyrillic characters (а-я, А–Я) and digits (0-9). 
     * @param string $parcelId 
     * @param string $text
     * @return transliterated input text as result
     * @since 3.4.0
     */
    public function convertToWin1251($sessionId, $text);

    /**
     * @param signed 64-bit integer $parcelId 
     * @param string $foreignParcelNumber
     * @since 3.5.3
     */
    public function mapForeignBarcode($sessionId, $parcelId, $foreignParcelNumber);

}
?>