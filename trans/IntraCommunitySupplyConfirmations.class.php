<?php

/**
 * Документ  за Потвърждение за вътрешнообщностна доставка
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_IntraCommunitySupplyConfirmations extends trans_abstract_ShipmentDocument
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';


    /**
     * Заглавие
     */
    public $title = 'Потвърждения за ВОД';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Потвърждение за ВОД';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'trans_Wrapper, bgerp_plg_Blank, doc_ActivatePlg, plg_Printing, doc_plg_TplManager, plg_RowTools2, doc_DocumentPlg, doc_EmailCreatePlg, plg_Search, plg_Sorting';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, trans';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, trans';


    /**
     * Кой може да създава?
     */
    public $canAdd = 'ceo, trans';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo, trans';


    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, trans';


    /**
     * Кои полета ще виждаме в листовия изглед
     */
    public $listFields = 'title=ВОД, originId=Към документ,deliveryCountryId=Държава,folderId=Папка,state,createdOn, createdBy';


    /**
     * Абревиатура
     */
    public $abbr = 'ICS';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'contragentName,contragentAddress,contragentVatNo,deliveryCountryId,senderName,deliveryAddress,shipmentDocument,invoiceDocument,transportDocument,forwarderName,forwarderVehicle';


    /**
     * Икона на документа
     */
    public $singleIcon = 'img/16/document_accept.png';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('contragentName', 'varchar', 'caption=Контрагент->Име, mandatory, class=contactData, mandatory');
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Контрагент->Адрес,class=contactData, mandatory');
        $this->FLD('contragentVatNo', 'drdata_VatType', 'caption=Контрагент->VAT №, mandatory');

        $this->FLD('deliveryCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Доставка->Държава, mandatory');
        $this->FLD('deliveryAddress', 'varchar(255)', 'caption=Доставка->Място, mandatory');
        $this->FLD('deliveryTime', 'datetime', 'caption=Доставка->Дата');
        $this->FLD('senderName', 'varchar(128)', 'caption=Доставка->Предал');
        $this->FLD('transportType', 'varchar(255)', 'caption=Доставка->Вид транспорт');

        $this->FLD('shipmentDocument', 'varchar(128)', 'caption=Документи->ЕН № / Дата, mandatory');
        $this->FLD('invoiceDocument', 'varchar(255)', 'caption=Документи->Фактура № / Дата');
        $this->FLD('transportDocument', 'varchar(128)', 'caption=Документи->ЧМР № / Дата');

        $this->FLD('forwarderName', 'varchar(255)', 'caption=Превозвач->Фирма, mandatory');
        $this->FLD('forwarderVehicle', 'varchar(128)', 'caption=Превозвач->МПС №, mandatory');
        $this->FLD('sendBackEmail', 'email', 'caption=Имейл за изпращане->Имейл, mandatory, mandatory');
    }


    /**
     * Помощна ф-я, която връща адреса на един ред
     *
     * @param int $countryId
     * @param string|null $pCode
     * @param string|null $place
     * @param string|null $address
     * @return string
     */
    private function getInlineAddress($countryId, $pCode, $place, $address)
    {
        $addressArr['countryId'] = is_numeric($countryId) ? drdata_Countries::getCountryName($countryId, core_Lg::getCurrent()) : $countryId;
        if(!empty($pCode)){
            $addressArr['place'] = $pCode;
        }
        if(!empty($place)){
            $addressArr['place'] .= (empty($addressArr['place']) ? '' : ' ') . transliterate(tr($place));
        }
        $addressArr['address'] = transliterate(tr($address));

        return implode(', ', $addressArr);
    }


    /**
     * Помощна ф-я връщаща адресните данни на контрагента транслитерирани спрямо текущия език
     *
     * @param $contragentClassId
     * @param $contragentId
     * @return string $address
     */
    private function getContragentAddress($contragentClassId, $contragentId)
    {
        $Contragent = cls::get($contragentClassId);
        $contragentRec = $Contragent->fetch($contragentId, 'pCode,place,address,country');

        core_Lg::push('en');
        $address = $this->getInlineAddress($contragentRec->country, $contragentRec->pCode, $contragentRec->place, $contragentRec->address);
        core_Lg::pop();

        return $address;
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        if(isset($rec->originId)){
            $Document = doc_Containers::getDocument($rec->originId);
            $documentRec = $Document->fetch();
            $logisticData = $Document->getLogisticData();

            // Данните за контрагента
            $contragentData = cls::get($documentRec->contragentClassId)->getContragentData($documentRec->contragentId);
            core_Lg::push('en');
            $contragentName = transliterate(tr(cls::get($documentRec->contragentClassId)->fetchField($documentRec->contragentId, 'name')));
            $form->setDefault('contragentName', $contragentName);
            core_Lg::pop();
            $form->setDefault('contragentVatNo', $contragentData->vatNo);
            $form->setDefault('contragentAddress', $mvc->getContragentAddress($documentRec->contragentClassId, $documentRec->contragentId));

            $countryId = drdata_Countries::getIdByName($logisticData['toCountry']);
            $form->setDefault('deliveryCountryId', $countryId);

            $shipmentDocument = "{$Document->that} / {$Document->getVerbal('valior')}";
            $form->setDefault('shipmentDocument', $shipmentDocument);

            // Ако има ф-ри в нишката предлагат се като предложения
            $invoicesInThread = deals_Helper::getInvoicesInThread($rec->threadId);
            if(countR($invoicesInThread)){
                $invoiceOptions = array();
                foreach ($invoicesInThread as $iContainerId => $number) {
                    $InvoiceDocument = doc_Containers::getDocument($iContainerId);
                    $invoiceOptions[$iContainerId] = "{$number} / {$InvoiceDocument->getVerbal('date')}";
                }

                $defaultInvoiceValue = $invoiceOptions[$documentRec->fromContainerId];
                $invoiceOptions = array_combine($invoiceOptions, $invoiceOptions);
                $form->setSuggestions('invoiceDocument', array('' => '') + $invoiceOptions);
                $form->setDefault('invoiceDocument', $defaultInvoiceValue);
            }

            // Ако има ЧМР взимам датата на доставка и транспротната фирма и МПС номера от там
            if($cmrRec = trans_Cmrs::fetch("#originId = {$rec->originId} AND #state = 'active'")){

                $cmrDateField = ($cmrRec->establishedDate) ? 'establishedDate' : 'createdOn';
                Mode::push('text', 'plain');
                $transportDocument = "{$cmrRec->cmrNumber} / " . trans_Cmrs::getVerbal($cmrRec, $cmrDateField);
                Mode::pop('text');
                $form->setDefault('transportDocument', $transportDocument);

                // Взима се МПС номера от ЧМР-то
                if(!empty($cmrRec->vehicleReg)){
                    $form->setDefault('forwarderVehicle', $cmrRec->vehicleReg);
                }

                // Ако има данни за превозвача в ЧМР-то взимат се те
                if(!empty($cmrRec->cariersData)){
                    $carrierData = explode("\n", $cmrRec->cariersData);
                    array_walk($carrierData, function (&$a) {$a = trim($a);});

                    $carrierData = implode(', ', $carrierData);
                    $carrierData = rtrim($carrierData, ', ');
                    $carrierData = str_replace(' , ', ', ', $carrierData);
                    $form->setDefault('forwarderName', $carrierData);
                }
            }

            // Ако има ТЛ към документа
            if(isset($documentRec->lineId)){
                $lineRec = trans_Lines::fetch($documentRec->lineId);

                // Ако няма превозвач от ЧМР-то взима се този от линията, ако има
                if(empty($rec->forwarderName) && !empty($lineRec->forwarderId)){
                    core_Lg::push('en');
                    $forwarderNameInLine = transliterate(crm_Companies::fetchField($lineRec->forwarderId, 'name'));
                    core_Lg::pop();
                    $form->setDefault('forwarderName', $forwarderNameInLine);
                }

                // Ако няма МПС номер от ЧМР-то взима се този от линията, ако има
                if(empty($rec->forwarderVehicle) && !empty($lineRec->vehicle)){
                    if ($vehicleRec = trans_Vehicles::fetch(array("#name = '[#1#]'", $lineRec->vehicle))) {
                        $form->setDefault('forwarderVehicle', $vehicleRec->number);
                    }
                }
            }

            // Какъв е адреса на доставка на английски
            if(empty($rec->deliveryAddress)){
                core_Lg::push('en');
                $deliveryAddress = $mvc->getInlineAddress($logisticData['toCountry'], $logisticData['toPCode'], $logisticData['toPlace'], $logisticData['toAddress']);
                core_Lg::pop();
                $form->setDefault('deliveryAddress', $deliveryAddress);
            }

            // Имейлите на "Моята фирма"
            $ourCompany = crm_Companies::fetchOurCompany();
            if(!empty($ourCompany->email)){
                $emails = type_Emails::toArray($ourCompany->email);
                $emailOptions = array_combine($emails, $emails);
                $form->setSuggestions('sendBackEmail', array('' => '') + $emailOptions);
                $form->setDefault('sendBackEmail', key($emailOptions));
            }

            $transportTypes = array('our' => 'Стоките са доставени или транспортирани от нас, използвайки наши собствени средства', 'thirdParty' => 'Стоките са доставени или транспортирани от трето лице от наше име');
            $form->setOptions('transportType', array('' => '') + $transportTypes);
        }
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $ourCompany = crm_Companies::fetchOurCompany();

        // Данните на моята фирма
        $row->ourCompanyName = $ourCompany->name;
        $row->ourCompanyAddress = $mvc->getInlineAddress($ourCompany->country, $ourCompany->pCode, $ourCompany->place, $ourCompany->address);
        $row->ourCompanyVatId = $ourCompany->vatId;

        // Данните на моята фирма на английски
        core_Lg::push('en');
        $row->ourCompanyNameEn = transliterate(tr($ourCompany->name));
        $row->ourCompanyAddressEn = $mvc->getInlineAddress($ourCompany->country, $ourCompany->pCode, $ourCompany->place, $ourCompany->address);
        core_Lg::pop();

        $row->deliveryCountry = drdata_Countries::getCountryName($rec->deliveryCountryId, 'bg');
        $row->deliveryCountryEn = drdata_Countries::getCountryName($rec->deliveryCountryId, 'en');

        if(isset($fields['-list'])){
            $row->title = $mvc->getLink($rec->id, 0);
            $row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
        }

        foreach (array('deliveryTime', 'shipmentDocument', 'invoiceDocument', 'transportDocument', 'senderName') as $fld){
            if(empty($rec->{$fld})){
                $row->{$fld} = "<span style='font-weight:normal'>.........................................</span>";
            }
        }

        if($rec->transportType == 'thirdParty'){
            $row->THIRD_PARTY_TRANSPORT_CHECK_BOX = html_entity_decode("&#128505;", ENT_COMPAT, 'UTF-8');
            $row->THIRD_PARTY_TRANSPORT = ' ';
        } elseif($rec->transportType == 'our'){
            $row->OUR_TRANSPORT_CHECK_BOX = html_entity_decode("&#128505;", ENT_COMPAT, 'UTF-8');
            $row->OUR_TRANSPORT = ' ';
        } else {
            $checkboxEmpty = html_entity_decode("&#9744;", ENT_COMPAT, 'UTF-8');
            $row->THIRD_PARTY_TRANSPORT = '<small>или <i class="quiet">(or)</i></small>';
            $row->OUR_TRANSPORT = ' ';
            $row->OUR_TRANSPORT_CHECK_BOX = $row->THIRD_PARTY_TRANSPORT_CHECK_BOX = $checkboxEmpty;
        }
    }


    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        $originId = Request::get('originId', 'int');

        return isset($originId);
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $res = '';
        $tplArr = array();
        $tplArr[] = array('name' => 'Потвърждения за вътрешнообщностна доставка', 'content' => 'trans/tpl/SingleLayoutIntraCommunitySupplyConfirmations.shtml', 'lang' => 'bg', 'narrowContent' => null);
        $res .= doc_TplManager::addOnce($this, $tplArr);

        return $res;
    }


    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашето потвърждение за ВОД") . ': #[#handle#]');
        $tpl->append($handle, 'handle');

        return $tpl->getContent();
    }


    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;

        if($form->isSubmitted()){
            $bgId = drdata_Countries::getIdByName('Bulgaria');
            if($rec->deliveryCountryId == $bgId || !drdata_Countries::isEu($rec->deliveryCountryId)){
                $form->setError('deliveryCountryId', "Държавата за доставка трябва да е в Чужбина ЕС");
            }
        }
    }
}