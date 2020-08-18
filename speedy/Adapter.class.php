<?php 

// Utility methods
require_once getFullPath("speedy/libs/" . speedy_Setup::get('CLIENT_LIBRARY_VERSION') . "/util/Util.class.php");

// Facade class
require_once getFullPath("speedy/libs/" . speedy_Setup::get('CLIENT_LIBRARY_VERSION') . "/ver01/EPSFacade.class.php");

// Implementation class
require_once getFullPath("speedy/libs/" . speedy_Setup::get('CLIENT_LIBRARY_VERSION') . "/ver01/soap/EPSSOAPInterfaceImpl.class.php");


/**
 * Модел за офиси на speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_Adapter {
    
    
    /**
     * Фасада към услугата
     *
     * @var EPSFacade
     */
    public $eps;
    
    
    /**
     * Текущия потребител
     * 
     * @var int
     */
    private $userId;
    
    
    /**
     * Резултат от логването
     */
    private $resultLogin;
    
    
    /**
     * Свързаните клиенти
     */
    private $contractClients;
    
    
    /**
     * Името на акаунта
     */
    private $accountName;
    
    
    /**
     * Свързаните клиенти
     */
    private $services = array();
    
    
    /**
     * Конструктор
     *
     * @param stdClass|string $rec systemId на сметка или запис на модела acc_Accounts
     */
    public function __construct($userId = null)
    {
        $this->userId = isset($userId) ? $userId : core_Users::getCurrent();
    }
    
    
    /**
     * Свързване с услугата на Speedy
     * 
     * @return stdClass
     */
    public function connect()
    {
        $result = new stdClass();
        $result->success = true;
        
        $userName = speedy_Setup::get('DEFAULT_ACCOUNT_USERNAME', false, $this->userId);
        $password = speedy_Setup::get('DEFAULT_ACCOUNT_PASSWORD', false, $this->userId);
        
        if(empty($userName) || empty($password)){
            $result->success = false;
            $result->errorMsg = tr("Не са настроени парола и акаунт на Спиди");
            
            return $result;
        }
        
        try{
            $this->eps = new EPSFacade(new EPSSOAPInterfaceImpl(), $userName,  $password);
            $this->resultLogin = $this->eps->getResultLogin();
            $this->contractClients = $this->eps->listContractClients();
            $this->accountName = $userName;
        } catch (ServerException $e){
            reportException($e);
            
            $result->success = false;
            $result->errorMsg = tr("Проблем при логване");
        }
       
        return $result;
    }
    
    
    /**
     * Дефолтния клиент
     * 
     * @return int
     */
    public function getDefaultClientId()
    {//bp($this->resultLogin);
        return $this->resultLogin->getClientId();
    }
    
    
    /**
     * Ид-то отговарящо на държавата от номенклатурата на Speedy
     * 
     * @param mixed $country
     * 
     * @return int|null
     */
    function getCountryId($country)
    {
        $country = is_numeric($country) ? drdata_Countries::getTitleById($country) : $country;
        
        $paramFilterCountry = new ParamFilterCountry();
        $paramFilterCountry->setName($country);
        $resultListCountriesEx = $this->eps->listCountriesEx($paramFilterCountry, 'BG');
        if(isset($resultListCountriesEx[0])){
            
            return $resultListCountriesEx[0]->getCountryId();
        }
        
        return null;
    }
    
    
    /**
     * Списък с офисите в подадената държава
     * 
     * @param int $countryId
     * 
     * @return ResultOfficeEx[] $resultListOffices
     */
    public function getOffices($countryId)
    {
        $countryId = is_numeric($countryId) ? drdata_Countries::getTitleById($countryId) : $countryId;
        $name = NULL;
        $siteId = NULL;
        $language = 'BG';
        
        $resultListOffices = $this->eps->listOfficesEx($name, $siteId, $language, $countryId);
        
        return $resultListOffices;
    }
    
    
    /**
     * Ид-то на мястото от номенклатурата на Speedy
     *
     * @param int $countryId
     * @return array
     */
    public function getSiteId($country, $pCode, $string, $officeId = null)
    {
        $language = 'BG';
        $country = is_numeric($country) ? drdata_Countries::getTitleById($country) : $country;
        
        $filter = new ParamFilterSite();
        $filter->setCountryId($country);
        if(!empty($pCode)){
            $filter->setPostCode($pCode);
        }
        
        if(!empty($string)){
            $filter->setSearchString($string);
        }
        
        $resultListSiteEx = $this->eps->listSitesEx($filter, $language);
        
        
        if(countR($resultListSiteEx)){
            
            return $resultListSiteEx[0]->getSite()->getId();
        }
        
        return null;
    }
    
    
    /**
     * Обекти към акаунта
     *
     * @return array $res
     */
    public function getSenderObjects()
    { 
       $res = array();
       foreach ($this->contractClients as $Client){
           $clientId = $Client->getClientId();
           $res[$clientId] = $this->getObjectName($clientId);
       }
       
       return $res;
    }
    
    
    /**
     * Кой е адреса на изпращача от настройките в акаунта на Speedy
     *
     * @param int $countryId
     * 
     * @return string $res
     */
    public function getObjectName($clientId)
    {
        $senderClientData = $this->eps->getClientById($clientId);
        $Address = $senderClientData->getAddress();
        $objectName = $senderClientData->getObjectName();
        
        $res = (!empty($objectName) ? "{$objectName}: " : "") . $Address->getPostCode() . " " . $Address->getSiteName() . " " . $Address->getStreetType() . " " . $Address->getStreetName() . " " . $Address->getStreetNo();
        if($quarterName = $Address->getQuarterName()){
            //$res .= " {$quarterName}";
        }
        
        return $res;
    }
    
    
    /**
     * Кои са наличните услуги за доставка до мястото
     *
     * @param int $countryId
     * @param int $toPlace
     * @param int $toPCode
     * @param int $toOfficeId
     * @throws ServerException
     * 
     * @return array $res
     */
    public function getServicesBySites($toCountryId, $toPlace, $toPCode, $toOfficeId)
    {
        $res = array();
        
        $currentDate = date('Y-m-d');
        $language = 'BG';
        
        // Адреса на изпращача, се взима от настройките му
        $senderClientData = $this->eps->getClientById($this->resultLogin->getClientId());
        $sndrSiteId = $senderClientData->getAddress()->getSiteId();
        
        // На получателя от въведените му данни
        $rcptCountryId = $rcptSiteId = $rcptPostCode = null;
        if(!isset($toOfficeId)){
            $rcptCountryId = $this->getCountryId($toCountryId);
            $rcptSiteId = $this->getSiteId($toCountryId, $toPCode, $toPlace);
            $rcptPostCode = $toPCode;
        }
        
        // Извличане на масив с наличните услуги
        $resultListServicesForSites = $this->eps->listServicesForSites($currentDate, $sndrSiteId, $rcptSiteId, null, null, $rcptCountryId, $rcptPostCode, $language, null, null, null, $toOfficeId);
      
        if(is_array($resultListServicesForSites)){
            $this->services = array();
            foreach ($resultListServicesForSites as $serviceForSite){
                $typeId = $serviceForSite->getTypeId();
                $this->services[$typeId] = $serviceForSite;
                $res[$typeId] = $serviceForSite->getName();
            }
        }
        
        return $res;
    }
    
    
    /**
     * Кои са наличните услуги за доставка до мястото
     *
     * @param int $serviceId - ид на товарителница
     * 
     * @return boolean
     */
    public function requireParcelData($serviceId)
    {
        if(is_object($this->services[$serviceId])){
            
            return $this->services[$serviceId]->getRequireParcelsData();
        }
        
        return false;
    }
    
    
    /**
     * Кои са наличните услуги за доставка до мястото
     *
     * @param int $pickingId - ид на товарителница
     * @throws ServerException
     *
     * @return string $fh    - хендлър към абсорбираната товарителница в нашата система
     */
    public function getBolPdf($pickingId)
    {
        $paramPDF = new ParamPDF();
        $paramPDF->setIds(array(0 => $pickingId));
        $paramPDF->setType(ParamPDF::PARAM_PDF_TYPE_BOL);
        $paramPDF->setIncludeAutoPrintJS(true);
        
        // Save pdf in a file
        $fileNameOnly = $this->eps->getUsername().'_picking_'.$pickingId.'_'.time().'.pdf';
        
        $fh = fileman::absorbStr($this->eps->createPDF($paramPDF), 'billOfLadings', $fileNameOnly);
        
        return $fh;
    }
    
    
    /**
     * Подробна разбивка на калкулираната цена на товарителницата
     *
     * @param stdClass $rec - данни за товавителница
     * @throws ServerException
     *
     * @return core_ET $tpl
     */
    public function calculate($rec)
    {
        $picking = $this->generatePicking($rec, true);
        $calculated = $this->eps->calculate($picking);
        $Amounts = $calculated->getAmounts();
        
        $Double = core_Type::getByName('double(decimals=2)');
        $row = new stdClass();
        $row->deadlineDelivery = dt::mysql2verbal($calculated->getDeadlineDelivery());
        
        foreach (arr::make('net,addrPickupSurcharge,addrDeliverySurcharge,discPcntFixed,discPcntAdditional,pcntFuelSurcharge,nonStdDeliveryDateSurcharge,tro,islandSurcharge,testBeforePayment,tollSurcharge,heavyPackageFee,codPremium,insurancePremium,vat,total') as $fld){
            $value = $Amounts->{"get{$fld}"}();
            $valueVerbal = $Double->toVerbal($value);
            $row->{$fld} = $valueVerbal;
        }
        
        $row->totalNoVat = $Double->toVerbal($Amounts->getTotal() - $Amounts->getVat());
        
        $tpl = getTplFromFile('speedy/tpl/CalculatedAmounts.shtml');
        $tpl->placeObject($row);
        
        return $tpl;
    }
    
    
    /**
     * Генерира товарителница в услугата на Speedy
     *
     * @param stdClass $rec - данни за товавителница
     * @throws ServerException
     *
     * @return int $bolId - ид-то на товарителницата
     */
    public function getBol($rec)
    {
        $picking = $this->generatePicking($rec, false);
        
        // Генериране на товарителница
        $resultBOL = $this->eps->createBillOfLading($picking);
        $parcels = $resultBOL->getGeneratedParcels();
        $firstParcel = $parcels[0];
        $bolId = $firstParcel->getParcelId();
        
        return $bolId;
    }
    
    
    /**
     * Генерира данни за товарителница
     *
     * @param stdClass $rec - ид на товарителница
     * @param boolean $onlyCalculate - само калкулация или товарителница
     *
     * @return ParamCalculation|ParamPicking $picking
     */
    private function generatePicking($rec, $onlyCalculate = false)
    {
        $pickingData = new StdClass();
        
        // Колко е общото тегло
        // От кои офиси да се вземе и доставки пратката, ако са зададени
        $pickingData->bringToOfficeId = null;
        $pickingData->takeFromOfficeId = $rec->receiverSpeedyOffice; 
        $pickingData->documents = ($rec->isDocuments == 'yes') ? true : false;
        $pickingData->palletized = ($rec->isPaletize == 'yes') ? true : false;
        $pickingData->fragile = ($rec->isFragile == 'yes') ? true : false;
        
        $pickingData->amountCODBase = $rec->amountCODBase;
        $pickingData->amountInsurance = $rec->amountInsurance;
        $pickingData->contents = $rec->content;
        $pickingData->packing = $rec->packaging;
        $pickingData->serviceTypeId = $rec->service;
        $pickingData->takingDate = $rec->date;
        $pickingData->payerType = ($rec->payer == 'sender') ? ParamCalculation::PAYER_TYPE_SENDER : (($rec->payer == 'receiver') ? ParamCalculation::PAYER_TYPE_RECEIVER : ParamCalculation::PAYER_TYPE_THIRD_PARTY);
        $pickingData->payerTypePackings = ($rec->payerPackaging == 'same') ? $pickingData->payerType : (($rec->payerPackaging == 'sender') ? ParamCalculation::PAYER_TYPE_SENDER : (($rec->payerPackaging == 'receiver') ? ParamCalculation::PAYER_TYPE_RECEIVER : ParamCalculation::PAYER_TYPE_THIRD_PARTY));
        
        $pickingData->returnServiceId = ($rec->returnServiceId == 'same') ? $pickingData->serviceTypeId : $rec->returnServiceId;
        $pickingData->returnPayer = ($rec->returnPayer == 'same') ? $pickingData->payerType : (($rec->returnPayer == 'sender') ? ParamCalculation::PAYER_TYPE_SENDER : (($rec->returnPayer == 'receiver') ? ParamCalculation::PAYER_TYPE_RECEIVER : ParamCalculation::PAYER_TYPE_THIRD_PARTY));
        
        if($pickingData->amountInsurance){
            setIfNot($rec->insurancePayer, 'same');
        }
        
        if(isset($rec->insurancePayer)){
            $pickingData->insurancePayer = ($rec->insurancePayer == 'same') ? $pickingData->payerType : (($rec->insurancePayer == 'sender') ? ParamCalculation::PAYER_TYPE_SENDER : (($rec->insurancePayer == 'receiver') ? ParamCalculation::PAYER_TYPE_RECEIVER : ParamCalculation::PAYER_TYPE_THIRD_PARTY));
        }
        
        // Задаване на данните на изпращача
        $senderClientData = $this->eps->getClientById($rec->senderClientId);
        $sender = new ParamClientData();
        $sender->setClientId($senderClientData->getClientId());
        $sender->setContactName($rec->senderName);
        $senderPhoneNumber = new ParamPhoneNumber();
        $senderPhoneNumber->setNumber($rec->senderPhone);
        $sender->setPhones(array(0 => $senderPhoneNumber));
       
        if(!empty($rec->senderNotes)){
            $senderAddress = new ParamAddress();
            $senderAddress->setAddressNote($rec->senderNotes);
            $sender->setAddress($senderAddress);
        }
        
        // Подготовка и задаване на данните на получателя
        $receiver = new ParamClientData();
        $receiver->setPartnerName($rec->receiverName);
        
        if($rec->isPrivatePerson == 'yes'){
            $receiver->setPrivatePersonType(1);
        } else {
            $receiver->setPrivatePersonType(2);
            $receiver->setContactName($rec->receiverPerson);
        }
        
        $receiverPhones = drdata_PhoneType::toArray($rec->receiverPhone);
        $receiverPhonesArr = array();
        foreach ($receiverPhones as $parsedPhone){
            $paramPhoneNumber = new ParamPhoneNumber();
            $paramPhoneNumber->setNumber($parsedPhone->original);
            $receiverPhonesArr[] = $paramPhoneNumber;
        }
        $receiver->setPhones($receiverPhonesArr);
        $picking = ($onlyCalculate === true) ? new ParamCalculation() : new ParamPicking();
        
        $picking->setServiceTypeId($pickingData->serviceTypeId);
        if(isset($pickingData->bringToOfficeId)){
            $picking->setWillBringToOffice($pickingData->bringToOfficeId);
        }
        
        if(isset($pickingData->takeFromOfficeId)){
            $picking->setOfficeToBeCalledId($pickingData->takeFromOfficeId);
        } else {
            $receiverSiteId = $this->getSiteId($rec->receiverCountryId, $rec->receiverPCode, $rec->receiverPlace);
            if($picking instanceof ParamCalculation){
                $picking->setReceiverSiteId($receiverSiteId);
            }
            
            $receiverAddress = new ParamAddress();
            $receiverAddress->setSiteId($receiverSiteId);
           
            $addressNote = $rec->receiverAddress . (!empty($rec->receiverNotes) ? ", {$rec->receiverNotes}" : "");
            $receiverAddress->setAddressNote($addressNote);
            if(!empty($rec->receiverBlock)){
                $receiverAddress->setBlockNo($rec->receiverBlock);
            }
            
            if(!empty($rec->receiverEntrance)){
                $receiverAddress->setEntranceNo($rec->receiverEntrance);
            }
            
            if(!empty($rec->receiverFloor)){
                $receiverAddress->setFloorNo($rec->receiverFloor);
            }
            
            if(!empty($rec->receiverApp)){
                $receiverAddress->setApartmentNo($rec->receiverApp);
            }
            
            $receiver->setAddress($receiverAddress);
        }
        
        if($picking instanceof ParamPicking){
            $picking->setContents($pickingData->contents);
            $picking->setPacking($pickingData->packing);
            $picking->setSender($sender);
            $picking->setReceiver($receiver);
            $picking->setDeliveryToFloorNo($rec->floorNum);
        } else {
            $picking->setSenderId($rec->senderClientId);
        }
        
        $picking->setDocuments($pickingData->documents);
        $picking->setPalletized($pickingData->palletized);
        $picking->setFragile($pickingData->fragile);
        
        $picking->setPayerType($pickingData->payerType);
        $picking->setPayerTypePackings($pickingData->payerTypePackings);
        $picking->setTakingDate($pickingData->takingDate);
        
        // Информация за съдържанието и наложения платеж и обявената стойност
        $codOptions = type_Set::toArray($rec->codType);
        if(!empty($pickingData->amountCODBase)){
            if(isset($codOptions['post'])){
                $picking->setRetMoneyTransferReqAmount($pickingData->amountCODBase);
            } else {
                $picking->setAmountCodBase($pickingData->amountCODBase);
            }
        }
        
        $picking->setAmountInsuranceBase($pickingData->amountInsurance);
        $picking->setPayerTypeInsurance($pickingData->insurancePayer);
        
        // Задаване на опции преди плащане, ако има
        if(in_array($rec->options, array('test', 'open'))){
            $paramOptionsBeforePayment = new ParamOptionsBeforePayment();
            $paramOptionsBeforePayment->setReturnServiceTypeId($pickingData->returnServiceId);
            $paramOptionsBeforePayment->setReturnPayerType($pickingData->returnPayer);
            
            if($rec->options == 'test'){
                $paramOptionsBeforePayment->setTest(true);
            } else {
                $paramOptionsBeforePayment->setTest(false);
            }
            
            if($rec->options == 'open'){
                $paramOptionsBeforePayment->setOpen(true);
            } else {
                $paramOptionsBeforePayment->setOpen(false);
            }
            
            $picking->setOptionsBeforePayment($paramOptionsBeforePayment);
        }
        
        $picking->setTakingDate($pickingData->takingDate);
        if(isset($codOptions['including'])){
            $picking->setIncludeShippingPriceInCod(true);
        }
        
        if($picking instanceof ParamPicking){
            $backRequest = type_Set::toArray($rec->backRequest);
            $backDocumentsRequest = isset($backRequest['document']) ? true : false;
            $picking->setBackDocumentsRequest($backDocumentsRequest);
            
            $backReceiptRequest = isset($backRequest['receipt']) ? true : false;
            $picking->setBackReceiptRequest($backReceiptRequest);
        }
      
        $parcels = type_Table::toArray($rec->parcelInfo);
        $countParcels = countR($parcels);
        $pickingData->parcelsCount = ($rec->palletCount) ? $rec->palletCount : (!empty($countParcels) ? $countParcels : 1);
        
        $pickingData->weightDeclared = ($rec->totalWeight) ? $rec->totalWeight : arr::sumValuesArray($parcels, 'weight');
        $picking->setWeightDeclared($pickingData->weightDeclared);
        
        $parcelsArray = array();
        $seqNo = 1;
        foreach ($parcels as $parcel){
            $parcelDetail = new ParamParcelInfo();
            
            $size = new Size();
            $size->setWidth($parcel->width);
            $size->setDepth($parcel->depth);
            $size->setHeight($parcel->height);
           
            $parcelDetail->setSize($size);
            $parcelDetail->setParcelId(-1);
            $parcelDetail->setSeqNo($seqNo);
            $parcelDetail->setWeight($parcel->weight);
            $parcelsArray[] = $parcelDetail;
            $seqNo++;
        }
        
        $picking->setParcelsCount($pickingData->parcelsCount);
        if(countR($parcelsArray)){
            $picking->setParcels($parcelsArray);
        }
        
        return $picking;
    }
    
    
    /**
     * Наличните дати за взимане на доставката
     * 
     * @param int $serviceId - услуга в номенклатурата на Speedy
     * @param int|null $senderOfficeId
     * @throws ServerException
     * 
     * @return array $res
     */
    public function getAllowedTakingDays($serviceId, $senderOfficeId = null)
    {
        $res = array();
        $senderClientData = $this->eps->getClientById($this->resultLogin->getClientId());
        $senderSiteId = $senderClientData->getAddress()->getSiteId();
        
        if($serviceId){
            $dates = $this->eps->getAllowedDaysForTaking($serviceId, $senderSiteId, $senderOfficeId, null);
            
            if(is_array($dates)){
                foreach ($dates as $date){
                    $res[$date] = dt::mysql2verbal($date, 'd.m.Y');
                    
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Обработва изникнало изключение
     * 
     * @param ServerException $e
     * @param string $fields
     * @param boolean|null $isHandled
     * 
     * @return string $errorMsg
     */
    public function handleException(ServerException $e, &$fields, &$isHandled = null)
    {
        $fields = 'receiverPhone';
        $isHandled = true;
        $errorMsg = $e->getMessage();
        
        if(strpos($errorMsg, '[ERR_012]') !== false){
            $errorMsg = 'Има разминаване между държавата и мястото в базата на Speedy';
            $fields = 'receiverCountryId,receiverPlace,receiverAddress';
        } elseif(strpos($errorMsg, 'Delivery to floor not allowed for service') !== false){
            $errorMsg = 'Избраната услуга непозволява качване до етаж';
            $fields = 'service,floorNum';
        } elseif(strpos($errorMsg, '[INVALID_PHONE_NUMBER') !== false){
            $errorMsg = 'Невалиден телефонен номер на получател';
            $fields = 'receiverPhone';
        } elseif(strpos($errorMsg, '[INVALID_BACK_DOCUMENT_REQUEST') !== false || strpos($errorMsg, 'INVALID_BACK_RECEIPT_REQUEST') !== false){
            $errorMsg = 'Не може да са избрани документи/разписка за връщане, при доставка до Автомат';
            $fields = 'receiverSpeedyOffice,backRequest';
        } elseif(strpos($errorMsg, '[INVALID_RECEIVER_MOBILE_PHONE_NUMBER_FOR_APT_TBC') !== false){
            $errorMsg = 'Неразпознат телефонен номер';
            $fields = 'receiverSpeedyOffice,receiverPhone';
        } elseif(strpos($errorMsg, 'COMMON_ERROR, [ERR_010] Pickings without COD') !== false){
            $errorMsg = 'Не може пощенския паричен превод да е включен в цената на наложения платеж';
            $fields = 'codType';
        } elseif(strpos($errorMsg, "[COMMON_ERROR, [ERR_011] 'PayerTypeInsurance'  MUST be set") !== false){
            $errorMsg = 'При обявената стойност, трябва да има избран платец на обявената стойност';
            $fields = 'insurancePayer,amountInsurance';
        } elseif(strpos($errorMsg, "[INVALID_PARCELS_INFO, Wrong sequential numbers") !== false){
            $errorMsg = 'Грешна последователност на палетите';
            $fields = 'parcelInfo';
        } elseif(strpos($errorMsg, '[INVALID_OPTIONS_BEFORE_PAYMENT, Option "Open before payment" with delivery to address is not allowed for selected service') !== false){
            $errorMsg = 'Опцията за отваряне преди плащане не е налична за избраната услуга';
            $fields = 'service,options';
        } elseif(strpos($errorMsg, '[INVALID_OPTIONS_BEFORE_PAYMENT, Option "Test before delivery" with delivery to address is not allowed for selected service.') !== false){
            $errorMsg = 'Опцията за тестване преди плащане не е налична за избраната услуга';
            $fields = 'service,options';
        } elseif(strpos($errorMsg, '[MISSING_REQUIRED_VALUE_PARCELS, Pallet services require first parcel info to be provided') !== false) {
            $errorMsg = 'Избраната услуга изисква да е подадена информация за първия палетите';
            $fields = 'service,parcelInfo';
        } else {
            $isHandled = false;
        }
        
        return $errorMsg;
    }
    
    
    /**
     * Кое е името на акаунта
     * 
     * @return string
     */
    public function getAccountName()
    {
        return $this->accountName;
    }
}