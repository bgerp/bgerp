<?php


/**
 * Абстрактен клас за наследяване от оферти
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_QuotationMaster extends core_Master
{
    /**
     * Клас за сделка, който последва офертата
     */
    protected $dealClass;


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf';


    /**
     * Поле за търсене по дата
     */
    public $filterDateField = 'createdOn, date, modifiedOn';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date, title=Документ, folderId, state, createdOn, createdBy';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'paymentMethodId, reff, company, person, email, folderId';


    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'crm_ContragentAccRegIntf';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'validFor' => 'lastDocUser|lastDoc',
        'paymentMethodId' => 'clientCondition|lastDocUser|lastDoc',
        'currencyId' => 'lastDocUser|lastDoc|CoverMethod',
        'chargeVat' => 'clientCondition|lastDocUser|lastDoc|defMethod',
        'others' => 'lastDocUser|lastDoc',
        'deliveryTermId' => 'clientCondition|lastDocUser|lastDoc',
        'deliveryPlaceId' => 'lastDocUser|lastDoc|',
        'company' => 'clientData',
        'pCode' => 'clientData',
        'place' => 'clientData',
        'address' => 'clientData',
        'contragentCountryId' => 'clientData',
        'template' => 'lastDocUser|lastDoc|defMethod',
    );


    /**
     * Кои полета ако не са попълнени във визитката на контрагента да се попълнят след запис
     */
    public static $updateContragentdataField = array(
        'email' => 'email',
        'tel' => 'tel',
        'fax' => 'fax',
        'pCode' => 'pCode',
        'place' => 'place',
        'address' => 'address',
    );


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'reff, date, expectedTransportCost';


    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'deliveryTermId, paymentMethodId';


    /**
     * Кои полета да са нередактируеми, ако има вече детайли
     */
    protected $readOnlyFieldsIfHaveDetail;


    /**
     * Задължителни полета на модела
     */
    protected static function setQuotationFields($mvc)
    {
        $mvc->FLD('date', 'date', 'caption=Дата');
        $mvc->FLD('reff', 'varchar(255,nullIfEmpty)', 'caption=Ваш реф.,class=contactData');

        $mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $mvc->FLD('contragentId', 'int', 'input=hidden');
        $mvc->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Плащане->Метод');
        $mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Плащане->Валута,removeAndRefreshForm=currencyRate');
        $mvc->FLD('currencyRate', 'double(decimals=5)', 'caption=Плащане->Курс,input=hidden');
        $mvc->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Освободено от ДДС, no=Без начисляване на ДДС)', 'caption=Плащане->ДДС');
        $mvc->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,silent,removeAndRefreshForm=deliveryData|deliveryPlaceId|deliveryAdress|deliveryCalcTransport');

        $mvc->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Обект,hint=Изберете обект');
        $mvc->FLD('deliveryAdress', 'varchar', 'caption=Доставка->Място');
        $mvc->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до');
        $mvc->FLD('deliveryTermTime', 'time(uom=days,suggestions=1 ден|5 дни|10 дни|1 седмица|2 седмици|1 месец)', 'caption=Доставка->Срок дни');
        $mvc->FLD('deliveryData', 'blob(serialize, compress)', 'input=none');

        $mvc->FLD('company', 'varchar', 'caption=Получател->Фирма, changable, class=contactData,input=hidden');
        $mvc->FLD('person', 'varchar', 'caption=Име, changable, class=contactData,after=reff');
        $mvc->FLD('email', 'varchar', 'caption=Имейл, changable, class=contactData,after=person');
        $mvc->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Тел., changable, class=contactData,after=email');
        $mvc->FLD('fax', 'drdata_PhoneType(type=fax)', 'caption=Факс, changable, class=contactData,after=tel');
        $mvc->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Получател->Държава,mandatory,contactData,contragentDataField=countryId,input=hidden');
        $mvc->FLD('pCode', 'varchar', 'caption=Получател->П. код, changable, class=contactData,input=hidden');
        $mvc->FLD('place', 'varchar', 'caption=Получател->Град/с, changable, class=contactData,input=hidden');
        $mvc->FLD('address', 'varchar', 'caption=Получател->Адрес, changable, class=contactData,input=hidden');

        $mvc->FLD('validFor', 'time(uom=days,suggestions=10 дни|15 дни|30 дни|45 дни|60 дни|90 дни)', 'caption=Допълнително->Валидност,mandatory');
    }


    /**
     * Подготвя формата за редактиране
     */
    public function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        $form = $data->form;
        $form->setField('deliveryAdress', array('placeholder' => '|Държава|*, |Пощенски код|*'));
        $form->setFieldTypeParams('deliveryTime', array('defaultTime' => trans_Setup::get('END_WORK_TIME')));
        $rec = &$data->form->rec;

        $folderId = $rec->folderId;
        if(empty($rec->folderId)){
            if(isset($rec->originId)){
                $folderId = doc_Containers::fetchField($rec->originId, 'folderId');
            } elseif($rec->threadId){
                $folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
            }
        }

        $contragentClassId = doc_Folders::fetchCoverClassId($folderId);
        $contragentId = doc_Folders::fetchCoverId($folderId);
        $form->setDefault('contragentClassId', $contragentClassId);
        $form->setDefault('contragentId', $contragentId);

        $locations = crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId, false);
        if (countR($locations)) {
            $form->setOptions('deliveryPlaceId', array('' => '') + $locations);
        }

        if (isset($form->rec->id)) {
            if (cls::get($this->mainDetail)->fetch("#quotationId = {$form->rec->id}")) {
                $readOnlyFields = arr::make($this->readOnlyFieldsIfHaveDetail, true);
                if(empty($form->rec->deliveryCalcTransport)){
                    unset($readOnlyFields['deliveryCalcTransport']);
                }
                foreach ($readOnlyFields as $fld) {
                    $form->setReadOnly($fld);
                }
            }
        }

        if (!$rec->person) {
            $form->setSuggestions('person', crm_Companies::getPersonOptions($rec->contragentId, false));
        }

        return $data;
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        if(!crm_Companies::isOwnCompanyVatRegistered()) {
            $form->setReadOnly('chargeVat');
        }

        $form->input('deliveryTermId');
        if(isset($rec->deliveryTermId)){
            if(cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId)){
                $calcCost = cond_DeliveryTerms::fetchField($rec->deliveryTermId, 'calcCost');
                if($form->getField('deliveryCalcTransport', false)){
                    $form->setField('deliveryCalcTransport', 'input');
                    $form->setDefault('deliveryCalcTransport', $calcCost);
                }
            }

            cond_DeliveryTerms::prepareDocumentForm($rec->deliveryTermId, $form, $mvc);
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            if (empty($rec->currencyRate)) {
                $rec->currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, null);
                if (!$rec->currencyRate) {
                    $form->setError('currencyRate', 'Не може да се изчисли курс');
                }
            }

            if (isset($rec->date, $rec->validFor)) {
                $expireOn = dt::verbal2mysql(dt::addSecs($rec->validFor, $rec->date), false);
                if ($expireOn < dt::today()) {
                    $form->setWarning('date,validFor', 'Валидността на офертата е преди текущата дата');
                }
            }

            // Избрания ДДС режим съответства ли на дефолтния
            $defVat = $mvc->getDefaultChargeVat($rec);
            if ($vatWarning = deals_Helper::getVatWarning($defVat, $rec->chargeVat)) {
                $form->setWarning('chargeVat', $vatWarning);
            }

            // Избраната валута съответства ли на дефолтната
            $defCurrency = cls::get($rec->contragentClassId)->getDefaultCurrencyId($rec->contragentId);
            $currencyState = currency_Currencies::fetchField("#code = '{$defCurrency}'", 'state');
            if ($defCurrency != $rec->currencyId && $currencyState != 'active') {
                $form->setWarning('currencyId', "Избрана e различна валута от очакваната|* <b>{$defCurrency}</b>");
            }

            if (isset($rec->deliveryTermTime, $rec->deliveryTime)) {
                $form->setError('deliveryTime,deliveryTermTime', 'Трябва да е избран само един срок на доставка');
            }

            // Проверка за валидност на адресите
            if (!empty($rec->deliveryPlaceId) && !empty($rec->deliveryAdress)) {
                $form->setError('deliveryPlaceId,deliveryAdress', 'Не може двете полета да са едновременно попълнени');
            } elseif (!empty($rec->deliveryAdress)) {
                if (!drdata_Address::parsePlace($rec->deliveryAdress)) {
                    $form->setError('deliveryAdress', 'Адресът трябва да съдържа държава и пощенски код');
                }
            }

            if(isset($rec->deliveryTermId)){
                cond_DeliveryTerms::inputDocumentForm($rec->deliveryTermId, $form, $mvc);
            }
        }
    }


    /**
     * Дали да се начислява ДДС
     */
    public function getDefaultChargeVat($rec)
    {
        // Ako "Моята фирма" е без ДДС номер - без начисляване
        if(!crm_Companies::isOwnCompanyVatRegistered()) return 'no';

        // После се търси по приоритет
        foreach (array('clientCondition', 'lastDocUser', 'lastDoc') as $strategy){
            $chargeVat = cond_plg_DefaultValues::getDefValueByStrategy($this, $rec, 'chargeVat', $strategy);
            if(!empty($chargeVat)) return $chargeVat;
        }

        return deals_Helper::getDefaultChargeVat($rec->folderId);
    }


    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();

        $row->title = self::getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }


    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        $coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);

        return cls::haveInterface('crm_ContragentAccRegIntf', $coverClass);
    }


    /**
     * Връща масив от използваните документи в офертата
     *
     * @param int $id - ид на оферта
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - Инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        return deals_Helper::getUsedDocs($this, $id);
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $mvc = cls::get(get_called_class());
        $rec = static::fetchRec($rec);

        $abbr = $mvc->abbr;
        $abbr[0] = strtoupper($abbr[0]);

        $date = dt::mysql2verbal($rec->date, 'd.m.year');
        $crm = cls::get($rec->contragentClassId);
        $cRec = $crm->getContragentData($rec->contragentId);
        $contragent = str::limitLen($cRec->company ? $cRec->company : $cRec->person, 32);

        if ($escaped) {
            $contragent = type_Varchar::escape($contragent);
        }

        return "{$abbr}{$rec->id}/{$date}/{$contragent}";
    }


    /**
     * След извличане на името на документа за показване в RichText-а
     */
    protected static function on_AfterGetDocNameInRichtext($mvc, &$docName, $id)
    {
        // Ако има реф да се показва към името му
        $reff = $mvc->getVerbal($id, 'reff');
        if (strlen($reff) != 0) {
            $docName .= "({$reff})";
        }
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);

        return $this->save($rec, 'modifiedOn,modifiedBy,searchKeywords');
    }


    /**
     * Състояние на нишката
     */
    public static function getThreadState($id)
    {
        $createdBy = self::fetchField($id, 'createdBy');

        return ($createdBy == core_Users::SYSTEM_USER) ? 'opened' : null;
    }


    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        $row = parent::recToVerbal_($rec, $fields);
        $mvc = cls::get(get_called_class());

        if (empty($rec->date)) {
            $row->date = ht::createHint('', 'Датата ще бъде записана при активиране');
        }

        if ($fields['-single']) {

            // Линк към от коя оферта е клонирано
            if(isset($rec->clonedFromId)){
                $row->clonedFromId = "#" . self::getHandle($rec->clonedFromId);
                if(!Mode::isReadOnly()){
                    $row->clonedFromId = ht::createLink($row->clonedFromId, self::getSingleUrlArray($rec->clonedFromId));
                }
            }

            if (isset($rec->validFor)) {
                // До коя дата е валидна
                $validDate = dt::addSecs($rec->validFor, $rec->date);
                $row->validDate = $mvc->getFieldType('date')->toVerbal($validDate);

                $date = dt::verbal2mysql($validDate, false);
                if ($date < dt::today()) {
                    if (!Mode::isReadOnly()) {
                        $row->validDate = "<span class='red'>{$row->validDate}</span>";

                        if ($rec->state == 'draft') {
                            $row->validDate = ht::createHint($row->validDate, 'Валидността на офертата е преди текущата дата', 'warning');
                        } elseif ($rec->state != 'rejected') {
                            $row->validDate = ht::createHint($row->validDate, 'Офертата е изтекла', 'warning');
                        }
                    }
                }
            }

            if(!Mode::isReadOnly()){
                $folderCover = doc_Folders::getCover($rec->folderId);
                if($folderCover->that != $rec->contragentId || $folderCover->getClassId() != $rec->contragentClassId){
                    $row->company = "<span class ='red'>{$row->company}</span>";
                    $row->company = ht::createHint($row->company, 'Контрагента в офертата, се различава от този в папката', 'error', false);
                }
            }

            $row->number = $mvc->getHandle($rec->id);
            $row->username = core_Users::recToVerbal(core_Users::fetch($rec->createdBy), 'names')->names;
            $row->username = transliterate(tr($row->username));

            $profRec = crm_Profiles::fetchRec("#userId = {$rec->createdBy}");
            if (!empty($profRec)) {
                if ($position = crm_Persons::fetchField($profRec->personId, 'buzPosition')) {
                    $row->position = cls::get('type_Varchar')->toVerbal($position);
                }
            }

            $ownCompanyData = crm_Companies::fetchOwnCompany();

            $Varchar = cls::get('type_Varchar');
            $row->MyCompany = $Varchar->toVerbal($ownCompanyData->company);
            $row->MyCompany = transliterate(tr($row->MyCompany));

            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $cData = $contragent->getContragentData();

            $fld = ($rec->tplLang == 'bg') ? 'commonNameBg' : 'commonName';
            $row->mycompanyCountryId = drdata_Countries::getVerbal($ownCompanyData->countryId, $fld);

            foreach (array('pCode', 'place', 'address') as $fld) {
                if ($cData->{$fld}) {
                    $row->{"contragent{$fld}"} = $Varchar->toVerbal($cData->{$fld});
                }

                if ($ownCompanyData->{$fld}) {
                    $row->{"mycompany{$fld}"} = $Varchar->toVerbal($ownCompanyData->{$fld});
                    $row->{"mycompany{$fld}"} = transliterate(tr($row->{"mycompany{$fld}"}));
                }
            }

            if ($rec->currencyRate == 1) {
                unset($row->currencyRate);
            }

            if ($rec->others) {
                $others = explode('<br>', $row->others);
                $row->others = '';
                foreach ($others as $other) {
                    $row->others .= "<li>{$other}</li>";
                }
            }

            if(isset($rec->deliveryTermId)){
                if ($Driver = cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId)) {
                    $deliveryDataArr = $Driver->getVerbalDeliveryData($rec->deliveryTermId, $rec->deliveryData, get_called_class());
                    foreach ($deliveryDataArr as $delObj){
                        $row->deliveryBlock .= "<li>{$delObj->caption}: {$delObj->value}</li>";
                    }
                }
            }

            // Показване на допълнителните условия от артикулите
            $additionalConditions = deals_Helper::getConditionsFromProducts($mvc->mainDetail, $mvc, $rec->id, $rec->tplLang);
            if (is_array($additionalConditions)) {
                foreach ($additionalConditions as $cond) {
                    $row->others .= "<li>{$cond}</li>";
                }
            }

            $deliveryAdress = '';
            if (!empty($rec->deliveryAdress)) {
                $deliveryAdress .= $mvc->getFieldType('deliveryAdress')->toVerbal($rec->deliveryAdress);
            } else {
                $placeId = ($rec->deliveryPlaceId) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null;
                $deliveryAdress .= cond_DeliveryTerms::addDeliveryTermLocation($rec->deliveryTermId, $rec->contragentClassId, $rec->contragentId, null, $placeId, $rec->deliveryData, $mvc);
            }

            if(isset($rec->deliveryTermId) && !Mode::isReadOnly()){
                $row->deliveryTermId = ht::createLink($row->deliveryTermId, cond_DeliveryTerms::getSingleUrlArray($rec->deliveryTermId));
            }

            if (!empty($deliveryAdress)) {
                if(isset($rec->deliveryTermId)){
                    $row->deliveryTermId = "{$row->deliveryTermId}, {$deliveryAdress}";
                } else {
                    $row->deliveryPlaceId = $deliveryAdress;
                }
            }

            if (!empty($profRec)) {
                $createdRec = crm_Persons::fetch($profRec->id);
            }

            $buzAddress = ($createdRec->buzAddress) ? $createdRec->buzAddress : $ownCompanyData->place;
            if ($buzAddress) {
                $row->buzPlace = cls::get('type_Varchar')->toVerbal($buzAddress);
                $row->buzPlace = core_Lg::transliterate($row->buzPlace);
            }

            if(!empty($row->deliveryPlaceId)){
                $row->deliveryPlaceCaption = isset($rec->deliveryTermId) ? tr('Място на доставка') : tr('За адрес');
            }
        }

        if ($fields['-list']) {
            $row->title = $mvc->getLink($rec->id, 0);
        }

        return $row;
    }


    /**
     * Помощна ф-я за връщане на всички продукти от офертата.
     * Ако има вариации на даден продукт и не може да се
     * изчисли общата сума ф-ята връща NULL
     *
     * @param int  $id           - ид на оферта
     * @param bool $onlyStorable - дали да са само складируемите
     *
     * @return array|NULL - продуктите
     */
    protected function getItems($id, $onlyStorable = false, $groupByProduct = false)
    {
        $Detail = cls::get($this->mainDetail);
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$id} AND #optional = 'no'");

        if ($onlyStorable === true) {
            $query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $query->where("#canStore = 'yes'");
        }

        $products = array();
        while ($detail = $query->fetch()) {
            $index = ($groupByProduct === true) ? $detail->productId : "{$detail->productId}|{$detail->packagingId}";

            if (array_key_exists($index, $products) || !$detail->quantity) return;
            $products[$index] = $detail;
        }

        return (countR($products)) ? array_values($products) : null;
    }


    /**
     * Метод за бързо създаване на чернова сделка към контрагент
     *
     * @param mixed $contragentClass - ид/инстанция/име на класа на котрагента
     * @param int   $contragentId    - ид на контрагента
     * @param int   $date            - дата
     * @param array $fields          - стойности на полетата на сделката
     *
     *   o $fields['originId']              - вальор (ако няма е текущата дата)
     *   o $fields['reff']                  - вашия реф на продажбата
     *   o $fields['currencyCode']          - код на валута (ако няма е основната за периода)
     * 	 o $fields['rate']                  - курс към валутата (ако няма е този към основната валута)
     * 	 o $fields['paymentMethodId']       - ид на платежен метод (Ако няма е плащане в брой, @see cond_PaymentMethods)
     * 	 o $fields['chargeVat']             - да се начислява ли ДДС - yes=Да, separate=Отделен ред за ДДС, exempt=Освободено,no=Без начисляване(ако няма, се определя според контрагента)
     * 	 o $fields['deliveryTermId']        - ид на метод на доставка (@see cond_DeliveryTerms)
     *   o $fields['deliveryCalcTransport'] - дали да се начислява скрит или явен транспорт (@see cond_DeliveryTerms)
     * 	 o $fields['validFor']              - срок на годност
     *   o $fields['company']               - фирма
     *   o $fields['person']                - лице
     *   o $fields['email']                 - имейли
     *   o $fields['tel']                   - телефон
     *   o $fields['fax']                   - факс
     *   o $fields['pCode']                 - пощенски код
     *   o $fields['place']                 - град
     *   o $fields['address']               - адрес
     *   o $fields['deliveryAdress']        - адрес за доставка
     *
     * @return mixed - ид на запис или FALSE
     */
    public static function createNewDraft($contragentClass, $contragentId, $date = null, $fields = array())
    {
        // Проверки
        $me = cls::get(get_called_class());
        expect($Cover = cls::get($contragentClass), 'Невалиден клас');
        expect(cls::haveInterface('crm_ContragentAccRegIntf', $Cover), 'Класа не е на контрагент');
        expect($Cover->fetch($contragentId), 'Няма такъв контрагент');
        expect($data = $Cover->getContragentData($contragentId), 'Няма данни за контрагента');

        // Подготовка на мастъра
        $newRec = new stdClass();
        $newRec->date = (isset($date)) ? $date : null;
        $newRec->reff = (isset($fields['reff'])) ? $fields['reff'] : null;
        $newRec->contragentClassId = $Cover->getClassId();
        $newRec->contragentId = $contragentId;
        $newRec->originId = (isset($fields['originId'])) ? $fields['originId'] : null;

        if (!empty($fields['deliveryAdress'])) {
            expect(drdata_Address::parsePlace($fields['deliveryAdress']), 'Адресът трябва да съдържа държава и пощенски код');
            $newRec->deliveryAdress = $fields['deliveryAdress'];
        }

        if (isset($newRec->originId)) {
            $origin = doc_Containers::getDocument($newRec->originId);
            $newRec->folderId = $origin->fetchField('folderId');
            $newRec->threadId = $origin->fetchField('threadId');
        } else {
            $newRec->folderId = $Cover->forceCoverAndFolder($contragentId);
        }

        $newRec->currencyId = (isset($fields['currencyCode'])) ? $fields['currencyCode'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'currencyId');
        expect(currency_Currencies::getIdByCode($newRec->currencyId), 'Невалиден код');

        $newRec->currencyRate = (isset($fields['rate'])) ? $fields['rate'] : currency_CurrencyRates::getRate($newRec->date, $newRec->currencyId, null);
        expect(cls::get('type_Double')->fromVerbal($newRec->currencyRate), 'Невалиден курс');

        $newRec->chargeVat = (isset($fields['chargeVat'])) ? $fields['chargeVat'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'chargeVat');
        expect(in_array($newRec->chargeVat, array('yes', 'no', 'exempt', 'separate')), 'Невалидно ДДС');

        // Намиране на метода за плащане
        $newRec->paymentMethodId = (isset($fields['paymentMethodId'])) ? $fields['paymentMethodId'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'paymentMethodId');
        if (isset($newRec->paymentMethodId)) {
            expect(cond_PaymentMethods::fetch($newRec->paymentMethodId), 'Невалиден метод за плащане');
        }

        // Условието на доставка
        $newRec->deliveryTermId = (isset($fields['deliveryTermId'])) ? $fields['deliveryTermId'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'deliveryTermId');
        if (isset($newRec->deliveryTermId)) {
            expect(cond_DeliveryTerms::fetch($newRec->deliveryTermId), 'Невалидно условие на доставка');
        }

        // Срока на валидност, ако не е зададен е дефолтния
        $newRec->validFor = (isset($fields['validFor'])) ? $fields['validFor'] : sales_Setup::get('DEFAULT_VALIDITY_OF_QUOTATION');
        if (isset($newRec->validFor)) {
            expect(type_Int::isInt($newRec->validFor), 'Срока на валидност трябва да е в секунди');
        }

        // Адресните данни
        foreach (array('company', 'person', 'email', 'tel', 'fax', 'pCode', 'place', 'address') as $fld) {
            if (isset($fields[$fld])) {
                expect($newRec->{$fld} = cls::get('type_Varchar')->fromVerbal($fields[$fld]), 'Невалидни адресни данни');
            } else {
                if (($Cover instanceof crm_Persons) && $fld == 'address') {
                    $fld = 'p'.ucfirst($fld);
                }
                if (!empty($data->{$fld})) {
                    $value = $data->{$fld};
                    if ($fld == 'email') {
                        $emails = type_Emails::toArray($data->{$fld});
                        $value = isset($emails[0]) ? $emails[0] : null;
                    } elseif ($fld == 'tel') {
                        $tels = drdata_PhoneType::toArray($data->{$fld});
                        if(is_object($tels[0])){
                            $value = '+' . $tels[0]->countryCode . $tels[0]->areaCode . $tels[0]->number;
                        } else {
                            $value = null;
                        }
                    }

                    $newRec->{$fld} = $value;
                }
            }
        }

        // Държавата
        $newRec->contragentCountryId = (isset($fields['countryId'])) ? $fields['countryId'] : $data->countryId;
        expect(drdata_Countries::fetch($newRec->contragentCountryId), 'Невалидна държава');
        $newRec->template = static::getDefaultTemplate($newRec);

        if(!empty($fields['others'])){
            $newRec->others = $fields['others'];
        }

        if(isset($newRec->deliveryTermId)){
            if(cond_DeliveryTerms::getTransportCalculator($newRec->deliveryTermId)){
                $newRec->deliveryCalcTransport = isset($fields['deliveryCalcTransport']) ? $fields['deliveryCalcTransport'] : cond_DeliveryTerms::fetchField($newRec->deliveryTermId, 'calcCost');
            }
        }

        // Подмяна на същесъвуващ документ върху който да се запише
        //@todo след рилийз да се махне
        if(isset($fields['_replaceContainerId'])){
            $cRec = doc_Containers::fetch($fields['_replaceContainerId']);
            $newRec->containerId = $fields['_replaceContainerId'];
            $newRec->folderId = $cRec->folderId;
            $newRec->threadId = $cRec->threadId;
            $newRec->createdOn = $cRec->createdOn;
            $newRec->modifiedOn = $cRec->modifiedOn;
        } else {
            // Създаване на запис
            static::route($newRec);
        }

        // Опиваме се да запишем мастъра на офертата
        if ($id = static::save($newRec)) {
            doc_ThreadUsers::addShared($newRec->threadId, $newRec->containerId, core_Users::getCurrent());

            return $id;
        }

        return false;
    }


    /**
     * Добавя нов ред в главния детайл на чернова сделка.
     * Ако има вече такъв артикул добавен към сделката, наслагва к-то, цената и отстъпката
     * на новия запис към съществуващия (цените и отстъпките стават по средно притеглени)
     *
     * @param int   $id           - ид на сделка
     * @param int   $productId    - ид на артикул
     * @param float $packQuantity - количество продадени опаковки (ако няма опаковки е цялото количество)
     * @param int   $packagingId  - ид на опаковка (не е задължителна)
     * @param float $price        - цена на единична бройка, без ДДС в основна валута
     * @param bool  $optional     - дали артикула е опционален или не
     * @param array $other        - масив с допълнителни параметри
     *                            double ['discount']       - отстъпка (опционална)
     *                            double ['tolerance']      - толеранс (опционален)
     *                            mixed  ['term']           - срок на доставка (опционален)
     *                            html   ['notes']          - забележки (опционален)
     *                            double ['quantityInPack'] - к-во в опаковка (опционален)
     *
     * @return mixed $id/FALSE     - ид на запис или FALSE
     */
    public static function addRow($id, $productId, $packQuantity, $packagingId = null, $price = null, $optional = false, $other = array())
    {
        // Проверка на параметрите
        $me = cls::get(get_called_class());
        $Detail = cls::get($me->mainDetail);
        expect($rec = static::fetch($id), 'Няма такава оферта');
        expect($rec->state == 'draft', 'Офертата трябва да е чернова');
        expect($productId, 'Трябва да е подаден артикул');
        expect($productRec = cat_Products::fetch($productId, 'id,canSell,measureId'), 'Няма такъв артикул');
        expect($productRec->canSell == 'yes', 'Артикулът не е продаваем');
        expect($packQuantity = cls::get('type_Double')->fromVerbal($packQuantity), 'Невалидно количество');

        // Подготовка на записа
        $newRec = new stdClass();
        $newRec->quotationId = $rec->id;
        $newRec->productId = $productId;
        $newRec->showMode = 'auto';
        $newRec->vatPercent = cat_Products::getVat($productId, $rec->date);
        $newRec->optional = ($optional === true) ? 'yes' : 'no';
        expect(in_array($newRec->optional, array('yes', 'no')));

        // Проверка на опаковката
        $newRec->packagingId = isset($packagingId) ? $packagingId : $productRec->measureId;
        $packs = cat_Products::getPacks($productId);
        expect(array_key_exists($newRec->packagingId, $packs), 'Артикулът няма такава опаковка');

        // Намиране на к-то в опаковка
        $pack = cat_products_Packagings::getPack($productId, $packagingId);
        $newRec->quantityInPack = (isset($other['quantityInPack'])) ? $other['quantityInPack'] : ((is_object($pack)) ? $pack->quantity : 1);
        expect($newRec->quantityInPack = cls::get('type_Double')->fromVerbal($newRec->quantityInPack), 'Проблем с количеството в опаковка');

        // Колко е общото количество
        $newRec->quantity = $newRec->quantityInPack * $packQuantity;

        // Дали отстъпката е между 0 и 1
        if (isset($other['discount'])) {
            expect($newRec->discount = cls::get('type_Double')->fromVerbal($other['discount']));
            expect($newRec->discount >= 0 && $newRec->discount <= 1, 'Отстъпката трябва да е между 0 и 1');
        }

        // Дали толеранса е между 0 и 1
        if (isset($other['tolerance'])) {
            expect($newRec->tolerance = cls::get('type_Double')->fromVerbal($other['tolerance']));
            expect($newRec->tolerance >= 0 && $newRec->tolerance <= 1);
        }

        if (isset($other['term'])) {
            expect($newRec->term = cls::get('type_Time')->fromVerbal($other['term']));
        }

        if (isset($other['notes'])) {
            $newRec->notes = cls::get('type_Richtext')->fromVerbal($other['notes']);
        }

        // Ако няма цена, прави се опит да се намери
        if (isset($price)) {
            $newRec->price = $price;
            expect($newRec->price = cls::get('type_Double')->fromVerbal($newRec->price), 'Невалидна цена');
        }

        // Изчисляване на транспортните разходи
        if (core_Packs::isInstalled('tcost') && $me instanceof sales_Quotations) {
            $form = $Detail::getForm();
            $clone = clone $rec;
            $clone->deliveryPlaceId = (!empty($rec->deliveryPlaceId)) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null;

            sales_TransportValues::prepareFee($newRec, $form, $clone, array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId', 'deliveryData' => 'deliveryData'));
        }

        // Проверки на записите
        if ($sameProduct = $Detail->fetch("#{$Detail->masterKey} = {$newRec->quotationId} AND #productId = {$newRec->productId}")) {
            if ($newRec->optional == 'no' && $sameProduct->optional == 'yes') {
                expect(false, 'Не може да добавите продукта като задължителен, защото фигурира вече като опционален');
            }
        }

        if ($Detail->fetch("#{$Detail->masterKey} = {$newRec->quotationId} AND #productId = {$newRec->productId}  AND #quantity='{$newRec->quantity}'")) {
            expect(false, 'Избрания продукт вече фигурира с това количество');
        }

        // Запис на детайла
        return $Detail->save($newRec);
    }


    /**
     * Създаване на продажба от оферта
     *
     * @param stdClass $rec
     *
     * @return int $dealId
     */
    protected function createDeal($rec)
    {
        $DealClass = cls::get($this->dealClass);
        $templateId = cond_plg_DefaultValues::getFromLastDocument($DealClass, $rec->folderId, 'template');

        if (empty($templateId)) {
            $templateId = cond_plg_DefaultValues::getFromLastDocument($DealClass, $rec->folderId, 'template', false);
        }

        if (empty($templateId)) {
            $templateId = $DealClass::getDefaultTemplate((object) array('folderId' => $rec->folderId));
        }

        // Подготвяме данните на мастъра на генерираната продажба
        $fields = array('currencyId' => $rec->currencyId,
            'currencyRate' => $rec->currencyRate,
            'paymentMethodId' => $rec->paymentMethodId,
            'deliveryTermId' => $rec->deliveryTermId,
            'caseId' => cash_Cases::getCurrent('id', false),
            'chargeVat' => $rec->chargeVat,
            'note' => $rec->others,
            'originId' => $rec->containerId,
            'template' => $templateId,
            'deliveryAdress' => $rec->deliveryAdress,
            'deliveryTime' => $rec->deliveryTime,
            'deliveryTermTime' => $rec->deliveryTermTime,
            'deliveryData' => $rec->deliveryData,
            'deliveryCalcTransport' => $rec->deliveryCalcTransport,
            'deliveryLocationId' => crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id'),
        );

        $folderId = cls::get($rec->contragentClassId)->forceCoverAndFolder($rec->contragentId);
        if($DealClass instanceof sales_Sales){
            $fields['dealerId'] = $DealClass::getDefaultDealerId($folderId, $fields['deliveryLocationId']);
            if(!empty($rec->bankAccountId)){
                $fields['bankAccountId'] = bank_OwnAccounts::fetchField($rec->bankAccountId, 'bankAccountId');
            }
        } else {
            if(!empty($rec->bankAccountId)){
                $fields['bankAccountId'] = $rec->bankAccountId;
            }
        }

        // Създаваме нова продажба от офертата
        $dealId = $DealClass::createNewDraft($rec->contragentClassId, $rec->contragentId, $fields);

        return $dealId;
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'activate') {
            if (!$rec->id) {

                // Ако документа се създава, то не може да се активира
                $res = 'no_one';
            } else {
                $Detail = cls::get($mvc->mainDetail);

                // За да се активира, трябва да има детайли
                if (!$Detail::fetchField("#{$Detail->masterKey} = {$rec->id}")) {
                    $res = 'no_one';
                }
            }
        }

        // Ако офертата е изтекла и е затврорена, не може да се отваря
        if ($action == 'close' && isset($rec)) {
            if ($rec->state == 'closed' && isset($rec->validFor, $rec->date)) {
                $validTill = dt::verbal2mysql(dt::addSecs($rec->validFor, $rec->date), false);
                if ($validTill < dt::today()) {
                    $res = 'no_one';
                }
            }
        }

        if ($action == 'dealfromquotation') {

            $sRec = isset($rec->folderId) ? (object)array('folderId' => $rec->folderId) : null;
            $res = cls::get($mvc->dealClass)->getRequiredRoles('add', $sRec, $userId);

            if(isset($rec)){
                $Detail = cls::get($mvc->mainDetail);
                if($res != 'no_one'){
                    if(!$Detail->count("#{$Detail->masterKey} = {$rec->id}")){
                        $res = 'no_one';
                    } else {
                        // Ако има разминаване между контрагента в офертата и данните от папката, забранява се създаване на сделката
                        $folderCover = doc_Folders::getCover($rec->folderId);
                        if($folderCover->that != $rec->contragentId || $folderCover->getClassId() != $rec->contragentClassId){
                            $res = 'no_one';
                        }
                    }
                }
            }
        }
    }


    /**
     * Екшън за създаване на чернова сделка от оферта
     */
    public function act_FilterProductsForDeal()
    {
        $this->requireRightFor('dealfromquotation');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        expect($rec->state == 'active');
        $this->requireRightFor('dealfromquotation', $rec);

        // Подготовка на формата за филтриране на данните
        $form = $this->getFilterForm($rec->id, $id);
        $form->input();

        if ($form->isSubmitted()) {
            $products = (array) $form->rec;

            $setError = true;
            $errFields = array();
            foreach ($products as $index1 => $quantity1) {
                if (!empty($quantity1)) {
                    $setError = false;
                } else {
                    $errFields[] = $index1;
                }
            }

            if ($setError === true) {
                $form->setError(implode(',', $errFields), 'Не са зададени количества');
            }

            if (!$form->gotErrors()) {
                $sId = null;
                try{
                    $errorMsg = 'Проблем при създаването на сделка';
                    $sId = $this->createDeal($rec);
                } catch(core_exception_Expect $e){
                    $errorMsg = $e->getMessage();
                    reportException($e);
                    $this->logErr($errorMsg, $rec->id);
                }

                if(empty($sId)){
                    followRetUrl(null, $errorMsg, 'error');
                }

                $Detail = cls::get($this->mainDetail);
                $DealClassName = $this->dealClass;
                foreach ($products as $dRecId) {
                    if(empty($dRecId)) continue;

                    $dRec = $Detail->fetch($dRecId);

                    // Копира се и транспорта, ако има
                    $addedRecId = $DealClassName::addRow($sId, $dRec->productId, $dRec->packQuantity, $dRec->price, $dRec->packagingId, $dRec->discount, $dRec->tolerance, $dRec->term, $dRec->notes);
                    if($DealClassName == 'sales_Sales'){
                        $tRec = sales_TransportValues::get($this, $id, $dRecId);
                        if (isset($tRec->fee)) {
                            sales_TransportValues::sync($DealClassName, $sId, $addedRecId, $tRec->fee, $tRec->deliveryTime, $tRec->explain);
                        }
                    }
                }

                // Редирект към сингъла на новосъздадената сделка
                return new Redirect(array($DealClassName, 'single', $sId));
            }
        }

        if (core_Users::haveRole('partner')) {
            plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
        }

        // Рендиране на обвивката
        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        $dData = $data->{$mvc->mainDetail};
        if ($dData->summary) {
            $data->row = (object) ((array)$data->row + (array)$dData->summary);
        }

        if ($dData->countNotOptional && $dData->notOptionalHaveOneQuantity) {
            core_Lg::push($data->rec->tplLang);
            $keys = array_keys($dData->rows);
            $firstProductRow = $dData->rows[$keys[0]][0];

            if ($firstProductRow->tolerance) {
                $data->row->others .= '<li>' . tr('Толеранс к-во') .": {$firstProductRow->tolerance}</li>";
            }

            if (isset($firstProductRow->term)) {
                $data->row->others .= '<li>' . tr('Срок за д-ка') .": {$firstProductRow->term}</li>";
            }

            if (isset($firstProductRow->weight)) {
                $data->row->others .= '<li>' . tr('Транспортно тегло') .": {$firstProductRow->weight}</li>";
            }
            core_Lg::pop();
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if ($rec->state == 'active') {

            if(isset($mvc->dealClass)){
                $Detail = cls::get($mvc->mainDetail);
                $DealClass = cls::get($mvc->dealClass);
                $singleTitle = mb_strtolower($DealClass->singleTitle);
                if ($mvc->haveRightFor('dealfromquotation', (object) array('id' => $rec->id, 'folderId' => $rec->folderId, 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId))) {
                    $items = $mvc->getItems($rec->id);

                    // Ако има поне един опционален артикул или има варианти на задължителните, бутона сочи към екшън за определяне на количествата
                    if ($Detail->fetch("#quotationId = {$rec->id} AND #optional = 'yes'") || !$items) {
                        $data->toolbar->addBtn($DealClass->singleTitle, array($mvc, 'FilterProductsForDeal', $rec->id, 'ret_url' => true), false, "ef_icon=img/16/star_2.png,title=Създаване на {$singleTitle} по офертата");

                        // Иначе, към създаването на нова сделка
                    } else {
                        $warning = '';
                        $title = "Прехвърляне на артикулите в съществуваща|* |{$DealClass->singleTitle}|*";
                        if (!$DealClass::count("#state = 'draft' AND #contragentId = {$rec->contragentId} AND #contragentClassId = {$rec->contragentClassId}")) {
                            $warning = "Сигурни ли сте, че искате да създадете нова|* |{$singleTitle}|*?";
                            $title = "Създаване на {$singleTitle} от офертата";
                            $efIcon = 'img/16/star_2.png';
                        } else {
                            $efIcon = $DealClass->singleIcon;
                        }

                        $data->toolbar->addBtn($DealClass->singleTitle, array($mvc, 'CreateDeal', $rec->id, 'ret_url' => true), array('warning' => $warning), "ef_icon={$efIcon},title={$title}");
                    }
                }
            }
        }
    }


    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     *
     * @param int $id - ид на записа
     *
     * @return core_Form - готовата форма
     */
    protected function getFilterForm($id)
    {
        $form = cls::get('core_Form');
        $DealClass = cls::get($this->dealClass);
        $singleTitle = mb_strtolower($DealClass->singleTitle);
        $form->title = "Създаване на {$singleTitle} от|* " . $this->getFormTitleLink($id);
        $form->info = tr('Моля уточнете, кои редове ще се прехвърлят в сделката');
        $filteredProducts = $this->filterProducts($id);

        foreach ($filteredProducts as $index => $product) {
            $default = null;
            if ($product->optional == 'yes') {
                $product->title = "Опционални->{$product->title}";
                $product->options = array('' => '') + $product->options;
                $mandatory = '';
            } else {
                $product->title = "Оферирани->{$product->title}";
                $mandatory = '';
                if (countR($product->options) > 1) {
                    $product->options = array('' => '') + $product->options;
                    $mandatory = 'mandatory';
                }
            }
            $form->FNC($index, 'double(decimals=2)', "input,caption={$product->title},hint={$product->hint},{$mandatory}");
            if (countR($product->options) == 1) {
                $default = key($product->options);
            }

            $product->options = $product->options + array('0' => '0');
            $form->setOptions($index, $product->options);
            $form->setDefault($index, $default);
        }

        $form->toolbar->addSbBtn('Създаване', 'save', 'ef_icon = img/16/disk.png, title = Прехвърляне в документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title = Прекратяване на действията');

        return $form;
    }


    /**
     * Групира продуктите от офертата с техните к-ва
     *
     * @param int $id - ид на оферта
     *
     * @return array $products - филтрираните продукти
     */
    protected function filterProducts($id)
    {
        $Detail = clone cls::get($this->mainDetail);

        $rec = $this->fetchRec($id);
        $products = array();
        $query = $Detail->getQuery();
        $query->where("#quotationId = {$id}");
        $query->orderBy('optional=ASC,id=ASC');
        $dRecs = $query->fetchAll();

        deals_Helper::fillRecs($Detail, $dRecs, $rec);

        foreach ($dRecs as $dRec) {
            $index = "{$dRec->productId}|{$dRec->optional}|{$dRec->packagingId}|" .md5($dRec->notes);

            if (!array_key_exists($index, $products)) {
                $title = cat_Products::getTitleById($dRec->productId);
                $title = str_replace(',', '.', $title);
                if (isset($dRec->packagingId)) {
                    $title .= ' / ' . cat_UoM::getShortName($dRec->packagingId);
                }

                $hint = null;
                if (!empty($dRec->notes)) {
                    $title .= ' / ' . str::limitLen(strip_tags(core_Type::getByName('richtext')->toVerbal($dRec->notes)), 10);
                    $hint = $dRec->notes;
                }
                $products[$index] = (object) array('title' => $title, 'options' => array(), 'optional' => $dRec->optional, 'suggestions' => false, 'hint' => $hint);
            }

            if ($dRec->optional == 'yes') {
                $products[$index]->suggestions = true;
            }

            if ($dRec->quantity) {
                core_Mode::push('text', 'plain');
                $packQuantity = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packQuantity);
                $packPrice = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packPrice);

                $val = "{$packQuantity} / {$packPrice} " . $rec->currencyId;
                foreach (array('discount', 'tolerance', 'term') as $fld){
                    if(!empty($dRec->{$fld})){
                        $Type = ($fld != 'term') ? core_Type::getByName('percent') : core_Type::getByName('time');
                        $val .= " / " . $Type->toVerbal($dRec->{$fld});
                    }
                }
                core_Mode::pop('text');

                $products[$index]->options[$dRec->id] = $val;
            }
        }

        return $products;
    }


    /**
     * Екшън генериращ продажба от оферта
     */
    public function act_CreateDeal()
    {
        $this->requireRightFor('dealfromquotation');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetchRec($id));
        expect($rec->state = 'active');
        expect($items = $this->getItems($id));
        $this->requireRightFor('dealfromquotation', $rec);
        $force = Request::get('force', 'int');
        $DealClass = cls::get($this->dealClass);
        $dealSingleTitle = mb_strtolower($DealClass->singleTitle);

        // Ако не форсираме нова продажба
        if (!$force && !core_Users::isContractor()) {

            // Опит да се намери съществуваща чернова сделка
            if (!Request::get('dealId', "key(mvc={$DealClass->className})") && !Request::get('stop')) {

                return new Redirect(array($DealClass, 'ChooseDraft', 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId, 'ret_url' => true, 'quotationId' => $rec->id));
            }
        }

        // Ако няма създава се нова сделка
        if (!$sId = Request::get('dealId', "key(mvc={$DealClass->className})")) {
            try{
                $sId = $this->createDeal($rec);
                $DealClass->logWrite('Създаване от оферта', $sId);
            } catch(core_exception_Expect $e){
                reportException($e);
                $this->logErr($e->dump[0], $rec->id);
                followRetUrl(null, "Проблем при създаване на {$dealSingleTitle} от оферта", 'error');
            }
        }

        // За всеки детайл на офертата подаваме го като детайл на сделката
        foreach ($items as $item) {
            $addedRecId = $DealClass::addRow($sId, $item->productId, $item->packQuantity, $item->price, $item->packagingId, $item->discount, $item->tolerance, $item->term, $item->notes);

            if($DealClass instanceof sales_Sales){
                // Копира се и транспорта, ако има
                $cRec = sales_TransportValues::get($this, $item->quotationId, $item->id);
                if (isset($cRec)) {
                    sales_TransportValues::sync('sales_Sales', $sId, $addedRecId, $cRec->fee, $cRec->deliveryTime);
                }
            }
        }

        $this->logWrite("Създаване на {$dealSingleTitle} от оферта", $id);

        // Редирект към новата сделка
        return new Redirect(array($DealClass, 'single', $sId), "|Успешно е създадена {$dealSingleTitle} от офертата");
    }


    /**
     * Затваряне на изтекли оферти по крон
     */
    public function cron_CloseQuotations()
    {
        $today = dt::today();

        // Селектиране на тези оферти, с изтекла валидност
        $query = $this->getQuery();
        $query->where("#state = 'active'");
        $query->where('#validFor IS NOT NULL');
        $query->XPR('expireOn', 'datetime', 'CAST(DATE_ADD(#date, INTERVAL #validFor SECOND) AS DATE)');
        $query->where("#expireOn < '{$today}'");
        $query->show('id');

        // Затварят се
        while ($rec = $query->fetch()) {
            try {
                $rec->state = 'closed';
                $this->save_($rec, 'state');
                $this->logWrite("Затваряне на изтекла " . mb_strtolower($this->singleTitle), $rec->id);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
    }


    /**
     * Екшън за автоматичен редирект към създаване на детайл
     */
    function act_autoCreateInFolder()
    {
        $this->requireRightFor('add');
        expect($folderId = Request::get('folderId', 'int'));
        $this->requireRightFor('add', (object)array('folderId' => $folderId));
        expect(doc_Folders::haveRightToFolder($folderId));

        // Има ли избрана константа
        list($pack,) = explode('_', $this->className);
        $packClass = "{$pack}_Setup";
        $constValue = $packClass::get('NEW_QUOTATION_AUTO_ACTION_BTN');

        if($constValue == 'form') {

            return Redirect(array($this, 'add', 'folderId' => $folderId, 'ret_url' => getRetUrl()));
        }

        // Генерира дефолтите според папката
        $Cover = doc_Folders::getCover($folderId);
        $fields = array();
        $fieldsWithStrategy = array_keys(static::$defaultStrategies);
        foreach ($fieldsWithStrategy as $field){
            $fields[$field] = cond_plg_DefaultValues::getDefaultValue($this, $folderId, $field);
        }

        // Създаване на мастър на документа
        try{
            $masterId = static::createNewDraft($Cover->getClassId(), $Cover->that, null, $fields);
            if(isset($productId)){
                static::logWrite('Създаване от артикул', $masterId);
            } else {
                static::logWrite('Създаване', $masterId);
            }
        } catch(core_exception_Expect $e){
            reportException($e);

            followRetUrl(null, "Проблем при създаване на оферта");
        }

        $redirectUrl = array($this, 'single', $masterId);
        $Detail = cls::get($this->mainDetail);

        // Редирект към добавянето на детайл
        if($constValue == 'addProduct') {
            if($Detail->haveRightFor('add', (object)array("{$Detail->masterKey}" => $masterId))){
                $redirectUrl = array($Detail, 'add', "{$Detail->masterKey}" => $masterId, 'optional' => 'no', 'ret_url' => array($this, 'single', $masterId));
            }
        } elseif($constValue == 'createProduct'){
            if($Detail->haveRightFor('createproduct', (object)array("{$Detail->masterKey}" => $masterId))){
                $redirectUrl = array($Detail, 'createproduct', "{$Detail->masterKey}" => $masterId, 'optional' => 'no', 'ret_url' => array($this, 'single', $masterId));
            }
        }

        return Redirect($redirectUrl);
    }


    /**
     * Връща файла, който се използва в документа
     *
     * @param object $rec
     * @return array
     */
    public function getLinkedFiles($rec)
    {
        $files = deals_Helper::getLinkedFilesInDocument($this, $rec, 'others', 'notes');

        return $files;
    }
}
