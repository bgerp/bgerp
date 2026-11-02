<?php


/**
 * Domestic consignment notes for external cargo, without warehouse movements.
 */
class trans_Waybills extends core_Master
{
    public $title = 'Товарителници';
    public $singleTitle = 'Товарителница';
    public $abbr = 'Wb';
    public $interfaces = 'doc_DocumentIntf';
    public $loadList = 'plg_RowTools2, trans_Wrapper, doc_DocumentPlg, doc_ActivatePlg, trans_plg_LinesPlugin, plg_Printing, plg_Search, plg_Sorting';
    public $canList = 'ceo,trans';
    public $canSingle = 'ceo,trans';
    public $canAdd = 'ceo,trans';
    public $canEdit = 'ceo,trans';
    public $canActivate = 'ceo,trans';
    public $canReject = 'ceo,trans';
    public $canRestore = 'ceo,trans';
    public $canPending = 'no_one';
    public $canDelete = 'no_one';
    public $canChangerec = 'no_one';
    public $canChangeline = 'ceo,trans';
    public $newBtnGroup = '4.9|Логистика';
    public $singleIcon = 'img/16/lorry.png';
    public $singleLayoutFile = 'trans/tpl/SingleLayoutWaybill.shtml';
    public $printBtnToolbarRow = 1;
    public $addLinkedDocumentToOriginId = true;
    public $preventCache = true;
    public $listFields = 'title=Документ,lineId,senderName,recipientName,loadingDate,vehicleNumber,state,createdOn';
    public $searchFields = 'senderName,senderAddress,recipientName,recipientAddress,carrierName,loadingPlace,unloadingPlace,vehicleNumber,notes';

    const CARGO_FIELDS = 'series,senderName,senderAddress,recipientName,recipientAddress,loadingPlace,loadingDate,unloadingPlace,unloadingDate,goods,totalWeight,volume,notes,instructions,lineNotes';
    const ISSUE_FIELDS = 'originId,threadId,folderId,series,issueDate,issuePlace,senderCompanyId,recipientCompanyId,loadingLocationId,unloadingLocationId,senderName,senderAddress,recipientName,recipientAddress,carrierName,carrierAddress,carrierNumber,loadingPlace,loadingDate,unloadingPlace,unloadingDate,goods,totalWeight,volume,distance,dangerousClass,dangerousName,companions,companionCount,freightPrice,additionalCosts,currencyId,instructions,vehicleModel,vehicleNumber,driverName,tripSheetId,tripReference,sourceContainerId,notes,issuedOn';


    public function description()
    {
        $this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Оттеглен)', 'input=none,caption=Състояние');
        $this->FLD('lineId', 'key(mvc=trans_Lines)', 'input=none,caption=Транспортна линия');
        // Retain references on existing records; external cargo is entered independently.
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
        $this->FLD('distance', 'double(min=0)', 'caption=Разтоварване->Пробег,unit=км');
        // Keep incomplete rows for validation instead of silently dropping them.
        $this->FLD('goods', 'table(columns=name|packaging|marks|quantity|weight,captions=Наименование|Опаковка/мярка|Маркировка/№|Брой/количество|Тегло kg,mandatory=mandatory,render=trans_Waybills::renderGoods)', 'caption=Товар->Стоки,mandatory');
        $this->FLD('totalWeight', 'double(min=0)', 'caption=Товар->Общо бруто тегло,unit=kg');
        $this->FLD('volume', 'double(min=0)', 'caption=Товар->Обем,unit=m³');
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


    public static function canAddToFolder($folderId)
    {
        return doc_Folders::fetchCoverClassName($folderId) == 'crm_Companies';
    }


    public static function canAddToThread($threadId)
    {
        return trans_TripSheets::canAddToThread($threadId) || self::canAddToFolder(doc_Threads::fetchField($threadId, 'folderId'));
    }


    public static function getNumber($rec)
    {
        return ($rec->series ?? '') . ' № ' . str_pad((string) ($rec->id ?? ''), 10, '0', STR_PAD_LEFT);
    }


    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);
        $title = 'Товарителница ' . self::getNumber($rec);

        return $escaped ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : $title;
    }


    public function getDocumentRow_($id)
    {
        $rec = $this->fetchRec($id);

        return (object) array('title' => $this->getRecTitle($rec), 'recTitle' => $this->getRecTitle($rec),
            'authorId' => $rec->createdBy ?? null, 'author' => $this->getVerbal($rec, 'createdBy'), 'state' => $rec->state ?? null);
    }


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
                if (empty($rec->{$locationField})) $form->setDefault($movement . 'Place', $address);
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
            foreach (self::getLineDefaults($rec->lineId) as $field => $value) $form->setDefault($field, $value);
        }
        $form->setDefault('loadingDate', dt::today());
        $form->info = 'Товарителницата описва външен товар. Включете я в транспортна линия чрез „Транспорт“ и я активирайте. Данните се пренасят от линията в пътния лист, а номерът му се попълва тук автоматично при издаването му.';
    }


    private static function getLineDefaults($lineId)
    {
        $line = trans_Lines::fetch($lineId);
        if (!$line) return array();
        $result = array('loadingDate' => substr($line->start, 0, 10));
        $vehicle = trans_Vehicles::fetch(array("#name = '[#1#]' OR #number = '[#1#]'", $line->vehicle ?? ''));
        if ($vehicle) {
            $result['vehicleModel'] = $vehicle->name;
            $result['vehicleNumber'] = $vehicle->number;
        }
        if (!empty($line->forwarderPersonId)) $result['driverName'] = crm_Persons::fetchField($line->forwarderPersonId, 'name');

        return $result;
    }


    public static function getCargoHash($rec)
    {
        $data = array();
        foreach (arr::make(self::CARGO_FIELDS) as $field) $data[$field] = (string) ($rec->{$field} ?? '');
        $data['goods'] = type_Table::toArray($rec->goods ?? null);

        return md5(serialize($data));
    }


    public function getValidationErrors($rec, $activating = false)
    {
        $errors = array();
        $goods = type_Table::toArray($rec->goods ?? null);
        if (!$goods) $errors['goods'] = 'Въведете поне един товар';
        foreach ($goods as $item) {
            $quantity = core_Type::getByName('double')->fromVerbal($item->quantity ?? '');
            $weight = core_Type::getByName('double')->fromVerbal($item->weight ?? '');
            if (trim($item->name ?? '') === '' || trim($item->packaging ?? '') === '' || $quantity === false || $quantity === null || $quantity <= 0
                || (strlen((string) ($item->weight ?? '')) && ($weight === false || $weight < 0))) {
                $errors['goods'] = 'Всеки ред трябва да има наименование, опаковка/мярка и положително количество; теглото не може да е отрицателно';
            }
        }
        if (!empty($rec->unloadingDate) && ($rec->unloadingDate < ($rec->loadingDate ?? ''))) $errors['unloadingDate'] = 'Разтоварването не може да предхожда натоварването';
        if ((!empty($rec->dangerousClass)) != (!empty($rec->dangerousName))) $errors['dangerousClass,dangerousName'] = 'Посочете и класа, и наименованието на опасния товар';
        if ((isset($rec->freightPrice) || isset($rec->additionalCosts)) && empty($rec->currencyId)) $errors['currencyId'] = 'Изберете валута за цената на превоза';
        foreach (array('sender' => 'loading', 'recipient' => 'unloading') as $party => $movement) {
            $companyId = $rec->{$party . 'CompanyId'} ?? null;
            if ($companyId && !crm_Companies::haveRightFor('single', $companyId)) $errors[$party . 'CompanyId'] = 'Няма достъп до избраната фирма';
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
            if (!$line || $line->state == 'rejected' || !trans_Lines::haveRightFor('single', $line)) $errors['lineId'] = 'Изберете достъпна транспортна линия';
        }
        if ($activating) {
            foreach (arr::make('series,issueDate,issuePlace,senderName,senderAddress,recipientName,recipientAddress,carrierName,carrierAddress,loadingPlace,loadingDate,unloadingPlace,vehicleModel,vehicleNumber') as $field) {
                if (trim($rec->{$field} ?? '') === '') $errors[$field] = 'Попълнете реквизитите преди активиране';
            }
        }

        return $errors;
    }


    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()) return;
        foreach ($mvc->getValidationErrors($form->rec, $form->cmd == 'active') as $field => $message) $form->setError($field, $message);
    }


    protected static function on_BeforeActivation($mvc, $rec)
    {
        $old = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : null;
        $full = (object) array_merge((array) $old, (array) $rec);
        if ($errors = $mvc->getValidationErrors($full, true)) {
            core_Statuses::newStatus(reset($errors), 'error');
            return false;
        }
    }


    protected static function on_BeforeSave($mvc, &$id, &$rec, &$fields = null)
    {
        $old = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : null;
        if (!empty($old->tripSheetId)) $rec->lineId = $old->lineId;
        if (!empty($old->issuedOn)) {
            foreach (arr::make(self::ISSUE_FIELDS) as $field) $rec->{$field} = $old->{$field} ?? null;
        }
        $full = (object) array_merge((array) $old, (array) $rec);
        if (!empty($full->lineId) && empty($old->issuedOn)) {
            foreach (self::getLineDefaults($full->lineId) as $field => $value) {
                if (empty($full->{$field})) {
                    $full->{$field} = $rec->{$field} = $value;
                    if ($fields !== null) $fields = arr::make($fields, true) + array($field => $field);
                }
            }
        }
        if (!empty($old->lineId) && $old->lineId != ($full->lineId ?? null)) $mvc->updateLines[$old->lineId] = $old->lineId;
        $lineChanged = ($old->lineId ?? null) != ($full->lineId ?? null);
        if ((empty($old->issuedOn) || $lineChanged) && ($full->state ?? null) != 'rejected') {
            if ($errors = $mvc->getValidationErrors($full, empty($old->issuedOn) && ($full->state ?? null) == 'active')) {
                core_Statuses::newStatus(reset($errors), 'error');
                return false;
            }
            if (empty($old->issuedOn) && ($full->state ?? null) == 'active') {
                $rec->issuedOn = dt::now();
                if ($fields !== null) $fields = arr::make($fields, true) + arr::make('issuedOn', true);
            }
        }
    }


    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if (!$rec || $roles == 'no_one') return;
        if ($action == 'edit') {
            // Native activation from an edit form changes the submitted state before this check.
            $saved = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : $rec;
            if (($saved->state ?? 'draft') != 'draft' || !empty($saved->issuedOn)) $roles = 'no_one';
        }
        if ($action == 'activate' && (($rec->state ?? 'draft') != 'draft' || !empty($rec->issuedOn))) $roles = 'no_one';
        if ($action == 'changeline' && !empty($rec->tripSheetId)) $roles = 'no_one';
        if (in_array($action, array('single', 'add', 'edit', 'activate', 'restore', 'changeline'))) {
            $lineId = $rec->lineId ?? null;
            if (!$lineId && !empty($rec->threadId)) {
                $first = doc_Threads::getFirstDocument($rec->threadId);
                if ($first && $first->isInstanceOf('trans_Lines')) $lineId = $first->that;
            }
            if ($lineId) {
                $line = trans_Lines::fetch($lineId);
                if (!$line || !trans_Lines::haveRightFor('single', $line, $userId)
                    || ($line->state == 'rejected' && $action != 'single')) $roles = 'no_one';
            }
        }
    }


    public static function renderGoods($value, $type)
    {
        $html = '<table class="innerTable border-table paddingCells" style="width:100%"><tr>';
        foreach ($type->getColumns() as $column) $html .= '<th>' . htmlspecialchars($column->caption, ENT_QUOTES, 'UTF-8') . '</th>';
        $html .= '</tr>';
        foreach (type_Table::toArray($value) as $item) {
            $html .= '<tr>';
            foreach ($type->getColumns() as $field => $column) $html .= '<td>' . htmlspecialchars((string) ($item->{$field} ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '</tr>';
        }

        return $html . '</table>';
    }


    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        $row->lineId = !empty($rec->lineId) ? trans_Lines::getHyperlink($rec->lineId, true) : '';
        if (isset($fields['-single'])) {
            $row->waybillNumber = htmlspecialchars(self::getNumber($rec), ENT_QUOTES, 'UTF-8');
            $row->documentStatus = empty($rec->issuedOn) ? tr('ЧЕРНОВА — НЕИЗДАДЕНА') : (($rec->state ?? null) == 'rejected' ? tr('ОТТЕГЛЕНА') : '');
            if (!empty($rec->issuedOn) && empty($rec->tripSheetId) && $rec->state != 'rejected') $row->documentStatus = tr('Очаква издаване на пътния лист от транспортната линия');
            if (!empty($rec->sourceContainerId)) $row->sourceContainerId = doc_Containers::getDocument($rec->sourceContainerId)->getLink(0);
            foreach (arr::make('unloadingDate,distance,totalWeight,dangerousClass,dangerousName,companions,companionCount,freightPrice,additionalCosts,tripReference') as $field) {
                if (!isset($row->{$field}) || $row->{$field} === '') $row->{$field} = '........................';
            }
        }
    }


    public function renderSingleLayout_(&$data)
    {
        if (Mode::is('printing')) core_Lg::push('bg');
        try {
            return parent::renderSingleLayout_($data);
        } finally {
            if (Mode::is('printing')) core_Lg::pop();
        }
    }


    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if (!empty($rec->originId)) doc_DocumentCache::cacheInvalidation($rec->originId);
        // Document plugin handlers run first, so the container is already current.
        $full = $mvc->fetch($id, '*', false);
        if (!empty($full->lineId)) {
            trans_LineDetails::sync($full->lineId, $full->containerId);
            unset($mvc->syncLineDetails[$full->lineId], $mvc->updateLines[$full->lineId]);
        }
    }


    public function getTransportLineInfo_($id, $lineId)
    {
        $rec = $this->fetchRec($id);
        $location = !empty($rec->unloadingLocationId) ? crm_Locations::fetch($rec->unloadingLocationId) : null;

        return trans_Helper::normalizeTransportLineInfo(array('state' => $rec->state,
            'weight' => $rec->totalWeight ?? null, 'volume' => $rec->volume ?? null,
            'address' => $rec->senderName . ': ' . $rec->loadingPlace . ' → ' . $rec->recipientName . ': ' . $rec->unloadingPlace,
            'contragentName' => $rec->senderName . ' → ' . $rec->recipientName,
            'notes' => trans_LineDocuments::getGoodsText($rec->goods) . "\n" . ($rec->notes ?? '') . "\n" . ($rec->lineNotes ?? ''),
            'countryId' => $location->countryId ?? null, 'place' => $location->place ?? null,
            'deliveryOn' => $rec->unloadingDate ?? null));
    }


    /**
     * Validate the waybills already included in a trip's line snapshot.
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
            if ($bill->vehicleNumber != $vehicleNumber) $errors['vehicleId'] = 'МПС трябва да съвпада с това в товарителниците на линията';
            if (!empty($bill->tripSheetId) && $bill->tripSheetId != ($trip->id ?? null)
                && trans_TripSheets::fetchField($bill->tripSheetId, 'state') != 'rejected') {
                $errors['transportDocuments'] = 'Товарителница от линията вече е включена в друг издаден пътен лист';
            }
        }

        return $errors;
    }


    /**
     * Fill the trip reference once, without changing the issued cargo or parties.
     */
    public static function attachIssuedTrip($trip)
    {
        expect(!empty($trip->issuedOn) && in_array($trip->state, array('pending', 'active')));
        expect(!self::getTripBindingErrors($trip));
        foreach ($trip->sourceData['waybills'] ?? array() as $id => $hash) {
            if (self::fetchField($id, 'tripSheetId') == $trip->id) continue;
            $update = (object) array('id' => $id, 'tripSheetId' => $trip->id,
                'tripReference' => $trip->series . ' № ' . str_pad((string) $trip->id, 10, '0', STR_PAD_LEFT) . ' / ' . dt::mysql2verbal($trip->issuedOn, 'd.m.Y'));
            // This narrowly scoped metadata update intentionally preserves the issued snapshot.
            cls::get(get_called_class())->save_($update, 'tripSheetId,tripReference');
            doc_DocumentCache::cacheInvalidation(self::fetchField($id, 'containerId'));
            self::logWrite('Попълнен пътен лист от ТЛ', $id);
        }
    }
}
