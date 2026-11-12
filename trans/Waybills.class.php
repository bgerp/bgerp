<?php


/**
 * Клас 'trans_Waybills'
 *
 * Товарителници за вътрешен превоз на външен товар, без складови движения
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Waybills extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Товарителници';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Товарителница';


    /**
     * Абревиатура
     */
    public $abbr = 'Wb';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper, doc_DocumentPlg, doc_ActivatePlg, trans_plg_LinesPlugin, plg_Printing, acc_plg_DocumentSummary, plg_Search, plg_Sorting';


    /**
     * Кой може да разглежда списъка?
     */
    public $canList = 'ceo,trans';


    /**
     * Кой може да разглежда сингъла?
     */
    public $canSingle = 'ceo,trans';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo,trans';


    /**
     * Да се показва ли бутон за нов запис в списъка
     */
    public $listAddBtn = false;


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo,trans';


    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo,trans';


    /**
     * Кой може да оттегля?
     */
    public $canReject = 'ceo,trans';


    /**
     * Кой може да възстановява?
     */
    public $canRestore = 'ceo,trans';


    /**
     * Кой може да прави на заявка?
     */
    public $canPending = 'no_one';


    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да променя активирания документ?
     */
    public $canChangerec = 'no_one';


    /**
     * Кой може да сменя транспортната линия?
     */
    public $canChangeline = 'ceo,trans';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.9|Логистика';


    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/waybill.png';


    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutWaybill.shtml';


    /**
     * На кой ред от тулбара да е бутонът за печат
     */
    public $printBtnToolbarRow = 1;


    /**
     * Да се добавя ли документът като свързан към оригиналния
     */
    public $addLinkedDocumentToOriginId = true;


    /**
     * Да не се кешира документът
     */
    public $preventCache = true;


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ,lineId,senderName,recipientName,loadingDate,vehicleNumber,state,createdOn';


    /**
     * Полета за филтриране по дата
     * @see acc_plg_DocumentSummary
     */
    public $filterDateField = 'issueDate,loadingDate,createdOn';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'senderName,senderAddress,recipientName,recipientAddress,carrierName,loadingPlace,unloadingPlace,vehicleNumber,notes';


    /**
     * Полетата на товара, от които се смята отпечатъкът му
     */
    const CARGO_FIELDS = 'series,senderName,senderAddress,recipientName,recipientAddress,loadingPlace,loadingDate,unloadingPlace,unloadingDate,goods,totalWeight,volume,notes,instructions,lineNotes';


    /**
     * Полетата, които не се променят след издаване
     */
    /**
     * Полетата, които се вземат от транспортната линия и не се променят ръчно
     */
    const LINE_FIELDS = 'vehicleModel,vehicleNumber,driverName';


    const ISSUE_FIELDS = 'originId,threadId,folderId,series,issueDate,issuePlace,senderCompanyId,recipientCompanyId,loadingLocationId,unloadingLocationId,senderName,senderAddress,recipientName,recipientAddress,carrierName,carrierAddress,carrierNumber,loadingPlace,loadingDate,unloadingPlace,unloadingDate,goods,totalWeight,volume,distance,dangerousClass,dangerousName,companions,companionCount,freightPrice,additionalCosts,currencyId,instructions,vehicleModel,vehicleNumber,driverName,tripSheetId,tripReference,sourceContainerId,notes,issuedOn';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Оттеглен)', 'input=none,caption=Състояние');
        $this->FLD('lineId', 'key(mvc=trans_Lines)', 'input=none,caption=Транспортна линия');

        // Пази се в старите записи; външният товар се въвежда самостоятелно
        $this->FLD('sourceContainerId', 'key(mvc=doc_Containers,allowEmpty)', 'input=none');
        $this->FLD('series', 'varchar(16)', 'caption=Документ->Серия,mandatory,value=ТВ');
        $this->FLD('issueDate', 'date', 'caption=Документ->Дата,mandatory');
        $this->FLD('issuePlace', 'varchar(128)', 'caption=Документ->Издадена в,mandatory');
        $this->FLD('senderCompanyId', 'key2(mvc=crm_Companies,select=name,allowEmpty)', 'caption=Изпращач->Фирма,silent,removeAndRefreshForm=senderName|senderAddress|loadingLocationId|loadingPlace');
        $this->FLD('senderName', 'varchar(255)', 'caption=Изпращач->Наименование,mandatory');
        $this->FLD('senderAddress', 'varchar(255)', 'caption=Изпращач->Адрес,mandatory');
        $this->FLD('recipientCompanyId', 'key2(mvc=crm_Companies,select=name,allowEmpty)', 'caption=Получател->Фирма,silent,removeAndRefreshForm=recipientName|recipientAddress|unloadingLocationId|unloadingPlace');
        $this->FLD('recipientName', 'varchar(255)', 'caption=Получател->Наименование,mandatory');
        $this->FLD('recipientAddress', 'varchar(255)', 'caption=Получател->Адрес,mandatory');
        $this->FLD('carrierName', 'varchar(255)', 'caption=Превозвач->Фирма,mandatory');
        $this->FLD('carrierAddress', 'varchar(255)', 'caption=Превозвач->Адрес,mandatory');
        $this->FLD('carrierNumber', 'varchar(32)', 'caption=Превозвач->ЕИК');
        $this->FLD('loadingLocationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Натоварване->Склад/обект,silent,removeAndRefreshForm=loadingPlace');
        $this->FLD('loadingPlace', 'varchar(255)', 'caption=Натоварване->Адрес,mandatory');
        $this->FLD('loadingDate', 'date', 'caption=Натоварване->Дата,mandatory');
        $this->FLD('unloadingLocationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Разтоварване->Склад/обект,silent,removeAndRefreshForm=unloadingPlace');
        $this->FLD('unloadingPlace', 'varchar(255)', 'caption=Разтоварване->Адрес,mandatory');
        $this->FLD('unloadingDate', 'date', 'caption=Разтоварване->Дата');
        $this->FLD('distance', 'double(min=0,smartRound)', 'caption=Разтоварване->Пробег,unit=км');

        // Непълните редове остават, за да се покаже грешка, вместо да изчезнат
        $this->FLD('goods', 'table(columns=name|packaging|marks|quantity|weight,captions=Наименование|Опаковка/мярка|Маркировка/№|Брой/количество|Тегло kg,mandatory=mandatory,render=trans_Waybills::renderGoods)', 'caption=Товар->Стоки,mandatory');
        $this->FLD('totalWeight', 'double(min=0,smartRound)', 'caption=Товар->Общо бруто тегло,unit=kg');
        $this->FLD('volume', 'double(min=0,smartRound)', 'caption=Товар->Обем,unit=m³');
        $this->FLD('dangerousClass', 'varchar(32)', 'caption=Опасен товар->Клас');
        $this->FLD('dangerousName', 'varchar(255)', 'caption=Опасен товар->Наименование');
        $this->FLD('companions', 'varchar(255)', 'caption=Придружители->Имена');
        $this->FLD('companionCount', 'int(min=0)', 'caption=Придружители->Брой');
        $this->FLD('freightPrice', 'double(min=0,decimals=2)', 'caption=Цена на превоза->Превозна цена');
        $this->FLD('additionalCosts', 'double(min=0,decimals=2)', 'caption=Цена на превоза->Допълнителни разходи');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies,select=code)', 'caption=Цена на превоза->Валута');
        $this->FLD('instructions', 'text(rows=3)', 'caption=Допълнително->Инструкции на превозвача');
        $this->FLD('notes', 'text(rows=3)', 'caption=Допълнително->Забележки');
        $this->FLD('tripSheetId', 'key(mvc=trans_TripSheets,allowEmpty)', 'input=none,caption=Курс->Пътен лист');
        $this->FLD('vehicleModel', 'varchar(128)', 'caption=Курс->Марка и модел');
        $this->FLD('vehicleNumber', 'varchar(32)', 'caption=Курс->Регистрационен номер');
        $this->FLD('driverName', 'varchar(128)', 'caption=Курс->Водач');
        $this->FLD('tripReference', 'varchar(128)', 'input=none');
        $this->FLD('issuedOn', 'datetime', 'input=none');

        $this->setDbIndex('lineId,state');
    }


    /**
     * Може ли документът да се добави в посочената папка
     *
     * @param int $folderId
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        return doc_Folders::fetchCoverClassName($folderId) == 'crm_Companies';
    }


    /**
     * Може ли документът да се добави в посочената нишка
     *
     * @param int $threadId
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        return trans_TripSheets::canAddToThread($threadId) || self::canAddToFolder(doc_Threads::fetchField($threadId, 'folderId'));
    }


    /**
     * Номерът на товарителницата - серия и номер с водещи нули
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public static function getNumber($rec)
    {
        return ($rec->series ?? '') . ' № ' . str_pad((string) ($rec->id ?? ''), 10, '0', STR_PAD_LEFT);
    }


    /**
     * Заглавие на записа
     *
     * @param stdClass|int $rec
     * @param bool         $escaped
     *
     * @return string
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);
        $title = 'Товарителница ' . self::getNumber($rec);

        return $escaped ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : $title;
    }


    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     *
     * @return stdClass
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetchRec($id);

        return (object) array('title' => $this->getRecTitle($rec),
            'recTitle' => $this->getRecTitle($rec),
            'authorId' => $rec->createdBy ?? null,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state ?? null);
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $first = !empty($rec->threadId) ? doc_Threads::getFirstDocument($rec->threadId) : null;
        if (empty($rec->id) && $first && $first->isInstanceOf('trans_Lines')) {
            $form->setDefault('lineId', $first->that);
            $form->setDefault('originId', $first->fetchField('containerId'));
        }
        if (!empty($rec->folderId) && self::canAddToFolder($rec->folderId)) {
            $form->setDefault('senderCompanyId', doc_Folders::fetchCoverId($rec->folderId));
        }

        // Изпращачът и получателят с техните обекти за натоварване и разтоварване
        foreach (array('sender' => 'loading', 'recipient' => 'unloading') as $party => $movement) {
            $companyId = $rec->{$party . 'CompanyId'} ?? null;
            $locationField = $movement . 'LocationId';
            $options = array('' => '');
            if ($companyId && crm_Companies::haveRightFor('single', $companyId)) {
                $company = crm_Companies::fetch($companyId);
                $address = trim(($company->pCode ?? '') . ' ' . ($company->place ?? '') . ', ' . ($company->address ?? ''), ', ');
                $form->setDefault($party . 'Name', $company->name);
                $form->setDefault($party . 'Address', $address);
                $options += crm_Locations::getContragentOptions('crm_Companies', $companyId, true, true);
                if (empty($rec->{$locationField})) {
                    $form->setDefault($movement . 'Place', $address);
                }
            }
            $form->setOptions($locationField, $options);
            if (!empty($rec->{$locationField}) && isset($options[$rec->{$locationField}])) {
                $form->setDefault($movement . 'Place', trans_LineDocuments::plain($options[$rec->{$locationField}]));
            }
        }

        $own = crm_Companies::fetchOwnCompany();
        $form->setDefault('series', 'ТВ');
        $form->setDefault('issueDate', dt::today());
        $form->setDefault('currencyId', currency_Currencies::getIdByCode(acc_Periods::getBaseCurrencyCode($rec->issueDate)));
        $form->setDefault('issuePlace', $own->place ?? '');
        $form->setDefault('carrierName', $own->company ?? '');
        $form->setDefault('carrierNumber', $own->uicId ?? '');
        $form->setDefault('carrierAddress', trim(($own->pCode ?? '') . ' ' . ($own->place ?? '') . ', ' . ($own->address ?? ''), ', '));
        if (!empty($rec->lineId)) {
            trans_Lines::requireRightFor('single', $rec->lineId);
            $lineFields = arr::make(self::LINE_FIELDS, true);
            foreach (self::getLineDefaults($rec->lineId) as $field => $value) {
                if (isset($lineFields[$field]) && empty($rec->issuedOn)) {
                    $rec->{$field} = $value;
                } else {
                    $form->setDefault($field, $value);
                }
            }

            // Поотделно, защото setReadOnly() преизползва стойността на първото поле
            foreach ($lineFields as $field) {
                $form->setReadOnly($field);
            }
        }
        $form->setDefault('loadingDate', dt::today());
        $form->info = "<div class='richtext-info-no-image'>" . tr('Товарителницата описва външен товар. Включете я в транспортна линия чрез „Транспорт“ и я активирайте. Данните се пренасят от линията в пътния лист, а номерът му се попълва тук автоматично при издаването му.')
            . '<br>' . tr('За всеки ред от стоките са задължителни наименование, опаковка/мярка и брой/количество. Маркировката и теглото не са задължителни.') . '</div>';
    }


    /**
     * Дефолтите от транспортната линия: дата, МПС и водач
     *
     * @param int $lineId
     *
     * @return array
     */
    private static function getLineDefaults($lineId)
    {
        $line = trans_Lines::fetch($lineId);
        if (!$line) {
            return array();
        }

        $result = array('loadingDate' => substr($line->start ?? '', 0, 10), 'vehicleModel' => '', 'vehicleNumber' => '', 'driverName' => '');
        $vehicle = trans_Vehicles::fetch(array("#name = '[#1#]' OR #number = '[#1#]'", $line->vehicle ?? ''));
        if ($vehicle) {
            $result['vehicleModel'] = $vehicle->name;
            $result['vehicleNumber'] = $vehicle->number;
        } elseif (!empty($line->vehicle)) {

            // МПС извън регистъра - само текстът от линията, без номер
            $result['vehicleModel'] = $line->vehicle;
        }
        if (!empty($line->forwarderPersonId)) {
            $result['driverName'] = crm_Persons::fetchField($line->forwarderPersonId, 'name');
        }

        return $result;
    }


    /**
     * Отпечатък на товара, по който пътният лист разбира дали е променен
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public static function getCargoHash($rec)
    {
        $data = array();
        foreach (arr::make(self::CARGO_FIELDS) as $field) {
            $data[$field] = (string) ($rec->{$field} ?? '');
        }
        $data['goods'] = type_Table::toArray($rec->goods ?? null);

        return md5(serialize($data));
    }


    /**
     * Грешките в записа, а при активиране и липсващите реквизити
     *
     * @param stdClass $rec
     * @param bool     $activating
     *
     * @return array
     */
    public function getValidationErrors($rec, $activating = false)
    {
        $errors = array();
        $goods = type_Table::toArray($rec->goods ?? null);
        if (!$goods) {
            $errors['goods'] = 'Въведете поне един товар';
        }
        // Грешките по редове - конкретно кое липсва или е грешно
        $messages = array();
        $rowNum = 0;
        foreach ($goods as $item) {
            $rowNum++;
            $rowErrors = array();
            $quantity = core_Type::getByName('double')->fromVerbal($item->quantity ?? '');
            $weight = core_Type::getByName('double')->fromVerbal($item->weight ?? '');
            if (trim($item->name ?? '') === '') {
                $rowErrors[] = 'въведете наименование';
            }
            if (trim($item->packaging ?? '') === '') {
                $rowErrors[] = 'въведете опаковка/мярка';
            }
            if (trim((string) ($item->quantity ?? '')) === '') {
                $rowErrors[] = 'въведете брой/количество';
            } elseif ($quantity === false || $quantity === null || $quantity <= 0) {
                $rowErrors[] = 'брой/количество трябва да е положително число';
            }
            if (strlen((string) ($item->weight ?? '')) && ($weight === false || $weight < 0)) {
                $rowErrors[] = 'теглото трябва да е число от 0 нагоре';
            }
            if ($rowErrors) {
                $messages[] = 'Ред ' . $rowNum . ': ' . implode('; ', $rowErrors);
            }
        }
        if ($messages) {
            $errors['goods'] = implode("\n", $messages);
        }

        if (!empty($rec->unloadingDate) && ($rec->unloadingDate < ($rec->loadingDate ?? ''))) {
            $errors['unloadingDate'] = 'Разтоварването не може да предхожда натоварването';
        }
        if ((!empty($rec->dangerousClass)) != (!empty($rec->dangerousName))) {
            $errors['dangerousClass,dangerousName'] = 'Посочете и класа, и наименованието на опасния товар';
        }
        if ((isset($rec->freightPrice) || isset($rec->additionalCosts)) && empty($rec->currencyId)) {
            $errors['currencyId'] = 'Изберете валута за цената на превоза';
        }

        foreach (array('sender' => 'loading', 'recipient' => 'unloading') as $party => $movement) {
            $companyId = $rec->{$party . 'CompanyId'} ?? null;
            if ($companyId && !crm_Companies::haveRightFor('single', $companyId)) {
                $errors[$party . 'CompanyId'] = 'Няма достъп до избраната фирма';
            }
            $locationId = $rec->{$movement . 'LocationId'} ?? null;
            if ($locationId) {
                $location = crm_Locations::fetch($locationId);
                if (!$location || $location->contragentCls != crm_Companies::getClassId() || $location->contragentId != $companyId) {
                    $errors[$movement . 'LocationId'] = 'Складът/обектът трябва да принадлежи на избраната фирма';
                }
            }
        }

        if (!empty($rec->lineId)) {
            $line = trans_Lines::fetch($rec->lineId);
            if (!$line || $line->state == 'rejected' || !trans_Lines::haveRightFor('single', $line)) {
                $errors['lineId'] = 'Изберете достъпна транспортна линия';
            }
        }

        if ($activating) {
            foreach (arr::make('series,issueDate,issuePlace,senderName,senderAddress,recipientName,recipientAddress,carrierName,carrierAddress,loadingPlace,loadingDate,unloadingPlace,vehicleModel,vehicleNumber') as $field) {
                if (trim($rec->{$field} ?? '') === '') {
                    $errors[$field] = 'Попълнете реквизитите преди активиране';
                    if (!empty($rec->lineId) && in_array($field, arr::make(self::LINE_FIELDS))) {
                        $errors[$field] = 'Изберете МПС от регистъра в транспортната линия';
                    }
                }
            }
        }

        return $errors;
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()) {
            return;
        }

        foreach ($mvc->getValidationErrors($form->rec, $form->cmd == 'active') as $field => $message) {
            $form->setError($field, $message);
        }
    }


    /**
     * Преди активиране - проверка на реквизитите
     */
    protected static function on_BeforeActivation($mvc, $rec)
    {
        $old = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : null;
        $full = (object) array_merge((array) $old, (array) $rec);
        if ($errors = $mvc->getValidationErrors($full, true)) {
            core_Statuses::newStatus(reset($errors), 'error');

            return false;
        }
    }


    /**
     * Преди запис - издадената товарителница пази данните си
     */
    protected static function on_BeforeSave($mvc, &$id, &$rec, &$fields = null)
    {
        $old = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : null;
        if (!empty($old->tripSheetId)) {
            $rec->lineId = $old->lineId;
        }
        if (!empty($old->issuedOn)) {
            foreach (arr::make(self::ISSUE_FIELDS) as $field) {
                $rec->{$field} = $old->{$field} ?? null;
            }
        }

        $full = (object) array_merge((array) $old, (array) $rec);
        if (!empty($full->lineId) && empty($old->issuedOn)) {
            foreach (self::getLineDefaults($full->lineId) as $field => $value) {
                if (empty($full->{$field}) || in_array($field, arr::make(self::LINE_FIELDS))) {
                    $full->{$field} = $rec->{$field} = $value;
                    if ($fields !== null) {
                        $fields = arr::make($fields, true) + array($field => $field);
                    }
                }
            }
        }

        if (!empty($old->lineId) && $old->lineId != ($full->lineId ?? null)) {
            $mvc->updateLines[$old->lineId] = $old->lineId;
        }
        $lineChanged = ($old->lineId ?? null) != ($full->lineId ?? null);
        if ((empty($old->issuedOn) || $lineChanged) && ($full->state ?? null) != 'rejected') {
            if ($errors = $mvc->getValidationErrors($full, empty($old->issuedOn) && ($full->state ?? null) == 'active')) {
                core_Statuses::newStatus(reset($errors), 'error');

                return false;
            }
            if (empty($old->issuedOn) && ($full->state ?? null) == 'active') {
                $rec->issuedOn = dt::now();
                if ($fields !== null) {
                    $fields = arr::make($fields, true) + arr::make('issuedOn', true);
                }
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if (!$rec || $roles == 'no_one') {
            return;
        }

        if ($action == 'edit') {

            // При активиране от формата състоянието вече е сменено, затова се гледа записаното
            $saved = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : $rec;
            if (($saved->state ?? 'draft') != 'draft' || !empty($saved->issuedOn)) {
                $roles = 'no_one';
            }
        }
        if ($action == 'activate' && (($rec->state ?? 'draft') != 'draft' || !empty($rec->issuedOn))) {
            $roles = 'no_one';
        }
        if ($action == 'changeline' && !empty($rec->tripSheetId)) {
            $roles = 'no_one';
        }

        // Нужен е достъп до линията, а оттеглената позволява само разглеждане
        if (in_array($action, array('single', 'add', 'edit', 'activate', 'restore', 'changeline'))) {
            $lineId = $rec->lineId ?? null;
            if (!$lineId && !empty($rec->threadId)) {
                $first = doc_Threads::getFirstDocument($rec->threadId);
                if ($first && $first->isInstanceOf('trans_Lines')) {
                    $lineId = $first->that;
                }
            }
            if ($lineId) {
                $line = trans_Lines::fetch($lineId);
                if (!$line || !trans_Lines::haveRightFor('single', $line, $userId)
                    || ($line->state == 'rejected' && $action != 'single')) {
                    $roles = 'no_one';
                }
            }
        }
    }


    /**
     * Показва таблицата със стоките
     *
     * @param mixed      $value
     * @param type_Table $type
     *
     * @return string
     */
    public static function renderGoods($value, $type)
    {
        $html = '<table class="innerTable border-table paddingCells" style="width:100%"><tr>';
        foreach ($type->getColumns() as $column) {
            $html .= '<th>' . htmlspecialchars($column->caption, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr>';
        foreach (type_Table::toArray($value) as $item) {
            $html .= '<tr>';
            foreach ($type->getColumns() as $field => $column) {
                $class = in_array($field, array('quantity', 'weight')) ? ' class="aright"' : '';
                $html .= "<td{$class}>" . htmlspecialchars((string) ($item->{$field} ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</table>';
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        $row->lineId = !empty($rec->lineId) ? trans_Lines::getHyperlink($rec->lineId, true) : '';
        if (isset($fields['-list'])) {
            foreach (array('sender', 'recipient') as $party) {
                $companyId = $rec->{"{$party}CompanyId"} ?? null;
                if (!empty($companyId) && isset($row->{"{$party}Name"})) {
                    $attr = array('ef_icon' => cls::get('crm_Companies')->getSingleIcon($companyId), 'class' => 'specialLink');
                    $row->{"{$party}Name"} = ht::createLink($row->{"{$party}Name"}, crm_Companies::getSingleUrlArray($companyId), false, $attr);
                }
            }
        }

        if (isset($fields['-single'])) {
            $row->waybillNumber = htmlspecialchars(self::getNumber($rec), ENT_QUOTES, 'UTF-8');
            $row->documentStatus = empty($rec->issuedOn) ? tr('ЧЕРНОВА — НЕИЗДАДЕНА') : (($rec->state ?? null) == 'rejected' ? tr('ОТТЕГЛЕНА') : '');
            if (!empty($rec->issuedOn) && empty($rec->tripSheetId) && $rec->state != 'rejected') {
                $row->documentStatus = tr('Очаква издаване на пътния лист от транспортната линия');
            }
            if (!empty($rec->sourceContainerId)) {
                $row->sourceContainerId = doc_Containers::getDocument($rec->sourceContainerId)->getLink(0);
            }

            // Празните полета се печатат с точки за ръчно попълване
            foreach (arr::make('unloadingDate,distance,totalWeight,dangerousClass,dangerousName,companions,companionCount,freightPrice,additionalCosts,tripReference') as $field) {
                if (!isset($row->{$field}) || $row->{$field} === '') {
                    $row->{$field} = '........................';
                }
            }
        }
    }


    /**
     * Рендиране на изгледа - при печат винаги на български
     */
    public function renderSingleLayout_(&$data)
    {
        if (Mode::is('printing')) {
            core_Lg::push('bg');
        }
        try {
            return parent::renderSingleLayout_($data);
        } finally {
            if (Mode::is('printing')) {
                core_Lg::pop();
            }
        }
    }


    /**
     * След запис - обновяване на кеша и на линията
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if (!empty($rec->originId)) {
            doc_DocumentCache::cacheInvalidation($rec->originId);
        }

        // Контейнерът вече е обновен от плъгина за документи
        $full = $mvc->fetch($id, '*', false);
        if (!empty($full->lineId)) {
            trans_LineDetails::sync($full->lineId, $full->containerId);
            unset($mvc->syncLineDetails[$full->lineId], $mvc->updateLines[$full->lineId]);
        }
    }


    /**
     * Информацията за товарителницата в транспортната линия
     *
     * @param int $id
     * @param int $lineId
     *
     * @return array
     */
    public function getTransportLineInfo_($id, $lineId)
    {
        $rec = $this->fetchRec($id);
        $location = !empty($rec->unloadingLocationId) ? crm_Locations::fetch($rec->unloadingLocationId) : null;

        return trans_Helper::normalizeTransportLineInfo(array('state' => $rec->state,
            'weight' => $rec->totalWeight ?? null,
            'volume' => $rec->volume ?? null,
            'address' => $rec->senderName . ': ' . $rec->loadingPlace . ' → ' . $rec->recipientName . ': ' . $rec->unloadingPlace,
            'contragentName' => $rec->senderName . ' → ' . $rec->recipientName,
            'notes' => trans_LineDocuments::getGoodsText($rec->goods) . "\n" . ($rec->notes ?? '') . "\n" . ($rec->lineNotes ?? ''),
            'countryId' => $location->countryId ?? null,
            'place' => $location->place ?? null,
            'deliveryOn' => $rec->unloadingDate ?? null));
    }


    /**
     * Проверява товарителниците, включени в данните на пътния лист от линията
     *
     * @param stdClass $trip
     *
     * @return array
     */
    public static function getTripBindingErrors($trip)
    {
        $errors = array();
        $vehicleNumber = !empty($trip->vehicleId) ? trans_Vehicles::fetchField($trip->vehicleId, 'number') : null;
        if (!empty($trip->sourceData['waybills']) && !in_array($trip->transportType ?? null, array('public', 'mixed'))) {
            $errors['transportType'] = 'За външни товари изберете обществен или смесен превоз';
        }
        foreach ($trip->sourceData['waybills'] ?? array() as $id => $hash) {
            $bill = self::fetch($id, '*', false);
            if (!$bill || !self::haveRightFor('single', $bill) || $bill->lineId != $trip->lineId
                || $bill->state != 'active' || self::getCargoHash($bill) != $hash) {
                $errors['transportDocuments'] = 'Активирайте товарителниците в ТЛ и прегледайте актуалния товар преди издаване';
                continue;
            }
            if ($bill->vehicleNumber != $vehicleNumber) {
                $errors['vehicleId'] = 'МПС трябва да съвпада с това в товарителниците на линията';
            }
            if (!empty($bill->tripSheetId) && $bill->tripSheetId != ($trip->id ?? null)
                && trans_TripSheets::fetchField($bill->tripSheetId, 'state') != 'rejected') {
                $errors['transportDocuments'] = 'Товарителница от линията вече е включена в друг издаден пътен лист';
            }
        }

        return $errors;
    }


    /**
     * Записва издадения пътен лист в товарителниците, без да променя товара и страните
     *
     * @param stdClass $trip
     *
     * @return void
     */
    public static function attachIssuedTrip($trip)
    {
        expect(!empty($trip->issuedOn) && in_array($trip->state, array('pending', 'active')));
        expect(!self::getTripBindingErrors($trip));
        foreach ($trip->sourceData['waybills'] ?? array() as $id => $hash) {
            if (self::fetchField($id, 'tripSheetId') == $trip->id) {
                continue;
            }
            $update = (object) array('id' => $id, 'tripSheetId' => $trip->id,
                'tripReference' => $trip->series . ' № ' . str_pad((string) $trip->id, 10, '0', STR_PAD_LEFT) . ' / ' . dt::mysql2verbal($trip->issuedOn, 'd.m.Y'));

            // Само тези две полета, за да не се пипат издадените данни
            cls::get(get_called_class())->save_($update, 'tripSheetId,tripReference');
            doc_DocumentCache::cacheInvalidation(self::fetchField($id, 'containerId'));
            self::logWrite('Попълнен пътен лист от ТЛ', $id);
        }
    }
}
