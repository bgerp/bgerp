<?php


/**
 * Абстрактен клас за наследяване от оферти
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
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
        $mvc->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Плащане->Банкова с-ка');
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
        $rec = &$data->form->rec;

        $contragentClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
        $contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        $form->setDefault('contragentClassId', $contragentClassId);
        $form->setDefault('contragentId', $contragentId);

        $locations = crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId, false);
        if (countR($locations)) {
            $form->setOptions('deliveryPlaceId', array('' => '') + $locations);
        }

        if (isset($form->rec->id)) {
            if (cls::get($this->mainDetail)->fetch("#quotationId = {$form->rec->id}")) {
                $readOnlyFields = arr::make($this->readOnlyFieldsIfHaveDetail, true);
                foreach ($readOnlyFields as $fld) {
                    $form->setReadOnly($fld);
                }
            }
        }

        if (!$rec->person) {
            $form->setSuggestions('person', crm_Companies::getPersonOptions($rec->contragentId, false));
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

            cond_DeliveryTerms::prepareDocumentForm($rec->deliveryTermId, $form, $this);
        }

        return $data;
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

        return "{$abbr}{$rec->id}/{$date} {$contragent}";
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
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if (Request::get('Rejected', 'int')) {

            return;
        }

        $data->listFilter->FNC('sState', 'enum(all=Всички,draft=Чернова,pending=Заявка,active=Активен,closed=Приключен)', 'caption=Състояние,autoFilter');
        $data->listFilter->showFields .= ',sState';
        $data->listFilter->setDefault('sState', 'active');
        $data->listFilter->input();

        if ($rec = $data->listFilter->rec) {
            if (isset($rec->sState) && $rec->sState != 'all') {
                $data->query->where("#state = '{$rec->sState}'");
            }
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
            //$additionalConditions = deals_Helper::getConditionsFromProducts($mvc->mainDetail, $mvc, $rec->id, $rec->tplLang);
            if (is_array($additionalConditions)) {
                foreach ($additionalConditions as $cond) {
                    $row->others .= "<li>{$cond}</li>";
                }
            }

            if (isset($rec->bankAccountId)) {
                $ownAccount = bank_OwnAccounts::getOwnAccountInfo($rec->bankAccountId);
                $url = (!Mode::isReadOnly()) ? bank_OwnAccounts::getSingleUrlArray($rec->bankAccountId) : array();
                $row->bankAccountId = ht::createLink($ownAccount->iban, $url);
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

            if (empty($rec->deliveryTime) && empty($rec->deliveryTermTime)) {
                $deliveryTermTime = $mvc->getMaxDeliveryTime($rec->id);
                if ($deliveryTermTime) {
                    $deliveryTermTime = cls::get('type_Time')->toVerbal($deliveryTermTime);
                    $row->deliveryTermTime = ht::createHint($deliveryTermTime, 'Времето за доставка се изчислява динамично възоснова на най-големия срок за доставка от артикулите');
                }
            }
        }

        if ($fields['-list']) {
            $row->title = $mvc->getLink($rec->id, 0);
        }

        return $row;
    }


    /**
     * Най-големия срок на доставка
     *
     * @param int $id
     *
     * @return int|NULL
     */
    public function getMaxDeliveryTime($id)
    {
        $maxDeliveryTime = null;

        $Detail = cls::get($this->mainDetail);
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$id} AND #optional = 'no'");
        $query->show("productId,term,quantity,quotationId");

        while ($dRec = $query->fetch()) {
            $term = $dRec->term;
            if (!isset($term)) {
                $term = cat_Products::getDeliveryTime($dRec->productId, $dRec->quantity);

                $cRec = sales_TransportValues::get($this, $dRec->quotationId, $dRec->id);
                if (isset($cRec->deliveryTime)) {
                    $term = $cRec->deliveryTime + $term;
                }
            }

            if (isset($term)) {
                $maxDeliveryTime = max($maxDeliveryTime, $term);
            }
        }

        return $maxDeliveryTime;
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

        if(isset($newRec->deliveryTermId)){
            if(cond_DeliveryTerms::getTransportCalculator($newRec->deliveryTermId)){
                $newRec->deliveryCalcTransport = isset($fields['deliveryCalcTransport']) ? $fields['deliveryCalcTransport'] : cond_DeliveryTerms::fetchField($newRec->deliveryTermId, 'calcCost');
            }
        }

        // Създаване на запис
        static::route($newRec);

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
        $fields['dealerId'] = sales_Sales::getDefaultDealerId($folderId, $fields['deliveryLocationId']);

        // Създаваме нова продажба от офертата
        $saleId = $DealClass::createNewDraft($rec->contragentClassId, $rec->contragentId, $fields);
        if (isset($saleId) && isset($rec->bankAccountId)) {
            $uRec = (object) array('id' => $saleId, 'bankAccountId' => bank_OwnAccounts::fetchField($rec->bankAccountId, 'bankAccountId'));
            $DealClass->save_($uRec);
        }

        return $saleId;
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
    }
}
