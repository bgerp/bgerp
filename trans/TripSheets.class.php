<?php


/**
 * Пътни листи и отчет на пробега към транспортни линии.
 */
class trans_TripSheets extends core_Master
{
    public $title = 'Пътни листи';
    public $singleTitle = 'Пътен лист';
    public $abbr = 'Ts';
    public $interfaces = 'doc_DocumentIntf';
    public $loadList = 'plg_RowTools2, trans_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing, plg_Search, plg_Sorting';
    public $canList = 'ceo,trans';
    public $canSingle = 'ceo,trans';
    public $canAdd = 'ceo,trans';
    public $canEdit = 'ceo,trans';
    public $canPending = 'ceo,trans';
    public $canReport = 'ceo,trans';
    public $canReject = 'ceo,trans';
    public $canRestore = 'ceo,trans';
    public $canActivate = 'no_one';
    public $canDelete = 'no_one';
    public $canChangerec = 'no_one';
    public $canOrder = 'ceo,trans';
    public $listAddBtn = false;
    public $newBtnGroup = '4.8|Логистика';
    public $singleIcon = 'img/16/lorry_go.png';
    public $singleLayoutFile = 'trans/tpl/SingleLayoutTripSheet.shtml';
    public $printBtnToolbarRow = 1;
    public $addLinkedDocumentToOriginId = true;
    public $preventCache = true;
    public $listFields = 'title=Документ,lineId,departure,vehicleNumber,driverName,distance,state,createdOn,createdBy';
    public $searchFields = 'companyName,vehicleNumber,driverName,route,cargo,transportDocuments';

    const REPORT_FIELDS = 'odometerStart,odometerEnd,departedOn,returnedOn,reportNotes,signedSheet';
    const ISSUE_FIELDS = 'lineId,originId,threadId,folderId,series,transportType,companyName,companyAddress,companyNumber,companyPhone,vehicleId,vehicleModel,vehicleNumber,driverId,driverName,secondDriver,companions,departure,expectedReturn,route,cargo,transportDocuments,cargoManifest,lineNotes,sourceData,authorizedBy,authorization,issuedOn,issuedBy,issuerName';


    public function description()
    {
        $this->FLD('state', 'enum(draft=Чернова,pending=Заявка,active=Отчетен,rejected=Оттеглен)', 'caption=Състояние,input=none');
        $this->FLD('lineId', 'key(mvc=trans_Lines)', 'caption=Транспортна линия,input=hidden,silent,mandatory');
        $this->FLD('series', 'varchar(16)', 'caption=Превоз->Серия,mandatory,value=ТЛ');
        $this->FLD('transportType', 'enum(own=За собствена сметка,public=Обществен превоз,mixed=За собствена сметка и обществен превоз)', 'caption=Превоз->Режим,mandatory,value=own');
        $this->FLD('companyName', 'varchar(255)', 'caption=Предприятие->Наименование,mandatory');
        $this->FLD('companyNumber', 'varchar(32)', 'caption=Предприятие->ЕИК,mandatory');
        $this->FLD('companyAddress', 'varchar(255)', 'caption=Предприятие->Адрес,mandatory');
        $this->FLD('companyPhone', 'varchar(128)', 'caption=Предприятие->Телефон');
        $this->FLD('vehicleId', 'key(mvc=trans_Vehicles,select=name)', 'caption=Курс->МПС,mandatory');
        $this->FLD('vehicleModel', 'varchar(128)', 'caption=Курс->Марка и модел,mandatory');
        $this->FLD('vehicleNumber', 'varchar(32)', 'caption=Рег. номер,input=none');
        $this->FLD('driverId', 'key(mvc=crm_Persons,select=name)', 'caption=Курс->Водач,mandatory');
        $this->FLD('driverName', 'varchar(128)', 'caption=Водач,input=none');
        $this->FLD('secondDriver', 'varchar(128)', 'caption=Курс->Втори водач');
        $this->FLD('companions', 'varchar(255)', 'caption=Курс->Придружители');
        $this->FLD('departure', 'datetime(requireTime)', 'caption=Курс->Тръгване,mandatory');
        $this->FLD('expectedReturn', 'datetime(requireTime)', 'caption=Курс->Връщане');
        $this->FLD('route', 'text(rows=4)', 'caption=Курс->Маршрут,mandatory');
        $this->FLD('cargo', 'text(rows=3)', 'caption=Курс->Вид на товара,mandatory');
        $this->FLD('transportDocuments', 'text(rows=3)', 'caption=Курс->Документи за товара,mandatory');
        $this->FLD('cargoManifest', 'text(rows=10)', 'caption=От транспортната линия->Маршрут и товари');
        $this->FLD('lineNotes', 'richtext(rows=3,bucket=Notes)', 'caption=От транспортната линия->Бележки');
        $this->FLD('sourceData', 'blob(serialize,compress)', 'input=none');
        $this->FLD('authorizedBy', 'varchar(128)', 'caption=Разрешил превоза->Имена,mandatory');
        $this->FLD('authorization', 'varchar(255)', 'caption=Разрешил превоза->Заповед за упълномощаване');
        $this->FLD('issuedOn', 'datetime', 'caption=Издаден на,input=none');
        $this->FLD('issuedBy', 'key(mvc=core_Users)', 'caption=Издал,input=none');
        $this->FLD('issuerName', 'varchar(128)', 'caption=Издател,input=none');
        $this->FLD('odometerStart', 'int(min=0)', 'caption=Отчет->Начален километраж,unit=км,input=none');
        $this->FLD('odometerEnd', 'int(min=0)', 'caption=Отчет->Краен километраж,unit=км,input=none');
        $this->FLD('departedOn', 'datetime(requireTime)', 'caption=Отчет->Тръгване,input=none');
        $this->FLD('returnedOn', 'datetime(requireTime)', 'caption=Отчет->Връщане,input=none');
        $this->FLD('reportNotes', 'text(rows=3)', 'caption=Отчет->Отклонения и бележки,input=none');
        $this->FLD('signedSheet', 'fileman_FileType(bucket=Notes)', 'caption=Отчет->Подписан лист,input=none');
        $this->FLD('reportedOn', 'datetime', 'caption=Проверен на,input=none');
        $this->FLD('reportedBy', 'key(mvc=core_Users)', 'caption=Проверил,input=none');
        $this->FLD('reporterName', 'varchar(128)', 'caption=Проверил,input=none');
        $this->XPR('distance', 'int', '#odometerEnd - #odometerStart', 'caption=Пробег,unit=км');
        $this->setDbIndex('lineId,state');
        $this->setDbIndex('vehicleId,state,returnedOn');
    }


    public static function canAddToFolder($folderId)
    {
        return false;
    }


    public static function canAddToThread($threadId)
    {
        $first = doc_Threads::getFirstDocument($threadId);

        return $first && $first->isInstanceOf('trans_Lines') && $first->fetchField('state') != 'rejected';
    }


    public function getDocumentRow_($id)
    {
        $rec = $this->fetchRec($id);

        return (object) array('title' => $this->getRecTitle($rec), 'recTitle' => $this->getRecTitle($rec),
            'authorId' => $rec->createdBy ?? null, 'author' => $this->getVerbal($rec, 'createdBy'), 'state' => $rec->state ?? null);
    }


    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);

        return tr('Пътен лист') . ' №' . ($rec->id ?? '');
    }


    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form ?? null;
        $rec = $form->rec;
        $form->setOptions('driverId', trans_Vehicles::getDriverOptions());
        $refresh = !empty($rec->id) && Request::get('fromLine', 'int') && $mvc->haveRightFor('edit', $rec);
        if (!empty($rec->id) && !$refresh) return;
        $form->setDefault('series', 'ТЛ');
        if ($refresh) {
            $form->FLD('fromLine', 'int', 'input=hidden,silent');
            $form->setDefault('fromLine', 1);
        }

        $first = !empty($rec->threadId) ? doc_Threads::getFirstDocument($rec->threadId ?? null) : null;
        if (!$first || !$first->isInstanceOf('trans_Lines')) return;
        $line = $first->fetch();
        trans_Lines::requireRightFor('single', $line);
        $form->setDefault('lineId', $line->id ?? null);
        $form->setDefault('originId', $line->containerId ?? null);
        foreach (trans_LineDocuments::getTripDefaults($line->id) as $field => $value) {
            if ($refresh) $rec->{$field} = $value;
            else $form->setDefault($field, $value);
        }
        $company = crm_Companies::fetchOwnCompany();
        $form->setDefault('companyName', $company->company ?? '');
        $form->setDefault('companyNumber', $company->uicId ?? '');
        $form->setDefault('companyAddress', trim(($company->pCode ?? '') . ' ' . ($company->place ?? '') . ', ' . ($company->address ?? ''), ' ,'));
        $form->setDefault('companyPhone', $company->tel ?? '');

        $form->setField('route', 'unit=Подредете реалните посещения и добавете началния и крайния пункт. Предложението е по реда на документите');
        if ($refresh) $form->info = 'Данните са предложени от текущата транспортна линия. Прегледайте ги и запишете промените.';
    }


    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()) return;
        $rec = $form->rec;
        if (!empty($rec->expectedReturn) && (($rec->expectedReturn ?? '') < ($rec->departure ?? ''))) {
            $form->setError('expectedReturn', 'Връщането не може да предхожда тръгването');
        }
        if (($rec->state ?? null) == 'active') $form->setError('departure', 'Пробегът се въвежда с бутона Отчитане след издаване на листа');
        if (($rec->state ?? null) == 'pending') {
            foreach ($mvc->getIssueErrors($rec) as $field => $message) $form->setError($field, $message);
        }
    }


    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if (!$rec || $roles == 'no_one') return;
        $state = $rec->state ?? 'draft';
        if ($action == 'edit') {
            // The native form checks edit rights again after applying its issue command.
            $saved = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : $rec;
            if (($saved->state ?? 'draft') != 'draft' || !empty($saved->issuedOn)) $roles = 'no_one';
        }
        if ($action == 'pending' && ($state != 'draft' || !empty($rec->issuedOn))) $roles = 'no_one';
        if ($action == 'report' && ($state != 'pending' || empty($rec->issuedOn))) $roles = 'no_one';
        if ($action == 'order' && (empty($rec->issuedOn) || !in_array($rec->transportType ?? null, array('own', 'mixed')) || $state == 'rejected')) $roles = 'no_one';
        if (in_array($action, array('report', 'order')) && !empty($rec->id) && !$mvc->haveRightFor('single', $rec, $userId)) $roles = 'no_one';
        if ($action == 'restore' && ($rec->brState ?? null) == 'active' && $mvc->getOverlap($rec)) $roles = 'no_one';
        if (in_array($action, array('add', 'pending', 'report', 'restore', 'order'))) {
            $lineId = $rec->lineId ?? null;
            if (!$lineId && !empty($rec->threadId)) {
                $first = doc_Threads::getFirstDocument($rec->threadId ?? null);
                if ($first && $first->isInstanceOf('trans_Lines')) $lineId = $first->that ?? null;
            }
            if ($lineId) {
                $line = trans_Lines::fetch($lineId);
                if (!$line || ($line->state ?? null) == 'rejected' || !trans_Lines::haveRightFor('single', $line, $userId)) $roles = 'no_one';
            } elseif ($action != 'add' || !empty($rec->threadId)) {
                $roles = 'no_one';
            }
        }
    }


    protected static function on_BeforeSave($mvc, &$id, &$rec, &$fields = null)
    {
        unset($rec->_attachWaybills);
        $old = !empty($rec->id) ? $mvc->fetch($rec->id ?? null, '*', false) : null;
        if (!empty($old->issuedOn)) {
            foreach (arr::make(self::ISSUE_FIELDS) as $field) $rec->{$field} = $old->{$field} ?? null;
            if (empty($rec->_tripReport)) {
                foreach (arr::make(self::REPORT_FIELDS . ',reportedOn,reportedBy,reporterName') as $field) $rec->{$field} = $old->{$field} ?? null;
            }
        }
        $full = (object) array_merge((array) $old, (array) $rec);
        $line = trans_Lines::fetch($full->lineId ?? null);
        expect($line && ($line->containerId ?? null) == ($full->originId ?? null)
            && ($line->threadId ?? null) == ($full->threadId ?? null), 'Невалидна транспортна линия');

        if (($full->state ?? null) == 'pending' && empty($full->issuedOn)) {
            $errors = $mvc->getIssueErrors($full);
            if ($errors) {
                core_Statuses::newStatus(reset($errors), 'error');
                return false;
            }
            $vehicle = trans_Vehicles::fetch($full->vehicleId ?? null);
            $driver = crm_Persons::fetch($full->driverId ?? null);
            expect($vehicle && $driver, 'Липсва МПС или водач');
            $rec->vehicleNumber = $vehicle->number ?? '';
            $rec->driverName = $driver->name ?? '';
            $rec->issuedOn = dt::now();
            $rec->issuedBy = core_Users::getCurrent();
            $rec->issuerName = core_Users::fetchField($rec->issuedBy ?? null, 'names');
            $rec->_attachWaybills = true;
            if ($fields !== null) $fields = arr::make($fields, true) + arr::make('vehicleNumber,driverName,issuedOn,issuedBy,issuerName', true);
        }
        if (($full->state ?? null) == 'active') {
            if (empty($full->issuedOn)) return false;
            $lock = 'transTripVehicle' . ($full->vehicleId ?? 0);
            if (!core_Locks::obtain($lock, 30, 3, 3)) {
                core_Statuses::newStatus('Друг потребител отчита същото МПС. Опитайте отново', 'warning');
                return false;
            }
            $errors = self::getReportErrors($full);
            if ($errors || $mvc->getOverlap($full)) {
                core_Locks::release($lock);
                core_Statuses::newStatus($errors ? reset($errors) : 'Пробегът или времето се припокрива с друг отчетен пътен лист', 'error');
                return false;
            }
        }
    }


    private function getIssueErrors($rec)
    {
        $errors = array();
        $errors += trans_Waybills::getTripBindingErrors($rec);
        if (!empty($rec->sourceData['hash'])) {
            $current = trans_LineDocuments::getTripDefaults($rec->lineId);
            if ($rec->sourceData['hash'] != $current['sourceData']['hash']) $errors['route'] = 'Транспортната линия е променена. Използвайте Обновяване от ТЛ и прегледайте данните преди издаване';
            if (!empty($current['sourceData']['unavailable'])) $errors['transportDocuments'] = 'Нямате достъп до всички стокови документи на линията';
            if (!empty($current['sourceData']['waybills']) && ($rec->transportType ?? null) == 'own') $errors['transportType'] = 'Линията включва товарителници за външни товари. Изберете обществен или смесен превоз';
        }
        foreach (arr::make('series,companyName,companyNumber,companyAddress,vehicleModel,departure,route,cargo,transportDocuments,authorizedBy') as $field) {
            if (trim($rec->{$field} ?? '') === '') $errors[$field] = 'Попълнете реквизитите преди издаване на пътния лист';
        }
        $vehicle = !empty($rec->vehicleId) ? trans_Vehicles::fetch($rec->vehicleId ?? null) : null;
        if (empty($vehicle->number)) $errors['vehicleId'] = 'Изберете МПС с попълнен регистрационен номер';
        if (empty($rec->driverId) || !crm_Persons::fetch($rec->driverId ?? null)) $errors['driverId'] = 'Изберете водач';
        if (!empty($rec->expectedReturn) && ($rec->expectedReturn ?? '') < ($rec->departure ?? '')) {
            $errors['expectedReturn'] = 'Връщането не може да предхожда тръгването';
        }

        return $errors;
    }


    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if (!empty($rec->_attachWaybills)) trans_Waybills::attachIssuedTrip($mvc->fetch($id, '*', false));
        if (($rec->state ?? null) == 'active') core_Locks::release('transTripVehicle' . ($rec->vehicleId ?? 0));
        if (!empty($rec->originId)) doc_DocumentCache::cacheInvalidation($rec->originId ?? null);
    }


    /**
     * Проверява отчетните данни, включително нулев начален километраж.
     */
    public static function getReportErrors($rec)
    {
        $errors = array();
        foreach (array('odometerStart', 'odometerEnd', 'departedOn', 'returnedOn') as $field) {
            if (!isset($rec->{$field}) || $rec->{$field} === '') $errors[$field] = 'Полето е задължително при отчитане';
        }
        if (isset($rec->odometerStart, $rec->odometerEnd)) {
            if (($rec->odometerStart ?? 0) < 0 || ($rec->odometerEnd ?? 0) < ($rec->odometerStart ?? 0)) {
                $errors['odometerStart,odometerEnd'] = 'Крайният километраж трябва да е поне колкото началния, а началният да не е отрицателен';
            }
        }
        if (!empty($rec->departedOn) && !empty($rec->returnedOn) && ($rec->returnedOn ?? '') < ($rec->departedOn ?? '')) {
            $errors['departedOn,returnedOn'] = 'Връщането не може да предхожда тръгването';
        }

        return $errors;
    }


    private function getOverlap($rec)
    {
        if (self::getReportErrors($rec)) return false;
        $query = $this->getQuery();
        $query->where(array("#vehicleId = [#1#] AND #state = 'active' AND #id != [#2#]", (int) ($rec->vehicleId ?? 0), (int) ($rec->id ?? 0)));
        $query->where(array("((#odometerStart < [#1#] AND #odometerEnd > [#2#]) OR (#departedOn < '[#3#]' AND #returnedOn > '[#4#]'))",
            (int) ($rec->odometerEnd ?? 0), (int) ($rec->odometerStart ?? 0), $rec->returnedOn ?? '', $rec->departedOn ?? ''));

        return $query->fetch();
    }


    public function act_Report()
    {
        $id = Request::get('id', 'int');
        $rec = $this->fetchRec($id);
        $this->requireRightFor('report', $rec);
        $form = cls::get('core_Form');
        $form->setAction(array($this, 'report', $id));
        foreach (arr::make(self::REPORT_FIELDS) as $field) {
            $definition = $this->getField($field);
            $type = $definition->type ?? null;
            $form->FLD($field, clone $type, array('caption' => $definition->caption ?? $field));
        }
        $form->FLD('id', 'int', 'input=hidden,silent');
        $form->setDefault('id', $id);
        foreach (arr::make('odometerStart,odometerEnd,departedOn,returnedOn') as $field) $form->setField($field, 'mandatory');
        $previousQuery = $this->getQuery();
        $previousQuery->where(array("#vehicleId = [#1#] AND #state = 'active' AND #returnedOn <= '[#2#]'", (int) ($rec->vehicleId ?? 0), $rec->departure ?? ''));
        $previousQuery->orderBy('returnedOn', 'DESC');
        $previous = $previousQuery->fetch();
        if ($previous) $form->setSuggestions('odometerStart', array(($previous->odometerEnd ?? 0) => ($previous->odometerEnd ?? 0)));
        $form->title = 'Отчитане на|* ' . $this->getLink($id, 0);
        $form->input();
        if ($form->isSubmitted()) {
            $report = clone $rec;
            foreach (arr::make(self::REPORT_FIELDS) as $field) $report->{$field} = $form->rec->{$field} ?? null;
            foreach (self::getReportErrors($report) as $field => $message) $form->setError($field, $message);
            if (!$form->gotErrors() && $this->getOverlap($report)) $form->setError('odometerStart,odometerEnd,departedOn,returnedOn', 'Пробегът или времето се припокрива с друг отчетен пътен лист');
            if ($previous && isset($report->odometerStart) && ($report->odometerStart ?? null) != ($previous->odometerEnd ?? null)) {
                $form->setWarning('odometerStart', 'Началният километраж се различава от края на предходния отчет|*: ' . ($previous->odometerEnd ?? ''));
            }
            if (!$form->gotErrors()) {
                $lock = 'transTripVehicle' . ($rec->vehicleId ?? 0);
                if (core_Locks::obtain($lock, 30, 3, 3)) {
                    try {
                        $current = $this->fetch($id, '*', false);
                        $this->requireRightFor('report', $current);
                        $report->state = 'active';
                        $report->reportedOn = dt::now();
                        $report->reportedBy = core_Users::getCurrent();
                        $report->reporterName = core_Users::fetchField($report->reportedBy ?? null, 'names');
                        $report->_tripReport = true;
                        $report->_isActivated = true;
                        if ($this->save($report)) {
                            $this->logWrite('Отчитане на пробег', $id);
                            return new Redirect(array($this, 'single', $id));
                        }
                    } finally {
                        core_Locks::release($lock);
                    }
                }
                $form->setError('odometerStart,odometerEnd', 'Отчетът не е записан. Проверете данните и опитайте отново');
            }
        }
        $form->toolbar->addSbBtn('Отчитане', 'save', 'ef_icon=img/16/tick.png');
        $form->toolbar->addBtn('Отказ', array($this, 'single', $id), 'ef_icon=img/16/close-red.png');

        return $this->renderWrapping($form->renderHtml());
    }


    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec ?? null;
        $toolbar = $data->toolbar ?? null;
        if ($mvc->haveRightFor('edit', $rec)) $toolbar->addBtn('Обновяване от ТЛ', array($mvc, 'edit', $rec->id, 'fromLine' => 1, 'ret_url' => true), 'row=2,ef_icon=img/16/arrow_refresh.png,title=Предлагане на актуалните данни от линията за преглед и запис');
        if ($mvc->haveRightFor('report', $rec)) $toolbar->addBtn('Отчитане', array($mvc, 'report', $rec->id ?? null), 'ef_icon=img/16/tick.png');
        if ($mvc->haveRightFor('order', $rec)) $toolbar->addBtn('Печат заповед', array($mvc, 'single', $rec->id ?? null, 'Printing' => 'yes', 'tripView' => 'order'), 'target=_blank,ef_icon=img/16/printer.png');
        if (!empty($rec->issuedOn)) $toolbar->addBtn('Издаден лист', array($mvc, 'single', $rec->id ?? null, 'Printing' => 'yes', 'tripView' => 'issued'), 'row=2,target=_blank,ef_icon=img/16/printer.png');
    }


    public function renderSingleLayout_(&$data)
    {
        $printing = Mode::is('printing');
        if ($printing) core_Lg::push('bg');
        try {
            if (Request::get('tripView') == 'order') {
                $this->requireRightFor('order', $data->rec ?? null);
                return getTplFromFile('trans/tpl/TripOrder.shtml');
            }

            return parent::renderSingleLayout_($data);
        } finally {
            if ($printing) core_Lg::pop();
        }
    }


    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id ?? null, 0);
        if (!empty($rec->lineId)) $row->lineId = trans_Lines::getHyperlink($rec->lineId ?? null, true);
        if (($rec->state ?? null) != 'active') $row->distance = tr('Неотчетен');
        if (isset($fields['-single'])) {
            $row->sheetNumber = str_pad((string) ($rec->id ?? ''), 10, '0', STR_PAD_LEFT);
            $row->issueDate = !empty($rec->issuedOn) ? dt::mysql2verbal($rec->issuedOn ?? '', 'd.m.Y') : tr('ЧЕРНОВА');
            if (empty($rec->vehicleNumber) && !empty($rec->vehicleId)) {
                $row->vehicleNumber = $mvc->getFieldType('vehicleNumber')->toVerbal(trans_Vehicles::fetchField($rec->vehicleId, 'number'));
            }
            $row->driverName = $row->driverName ?? $mvc->getVerbal($rec, 'driverId');
            $row->documentStatus = $row->state ?? '';
            if (Request::get('tripView') == 'order' && ($rec->transportType ?? null) == 'mixed') {
                foreach (arr::make('route,cargo,transportDocuments') as $field) $row->{$field} = $mvc->getFieldType($field)->toVerbal($rec->sourceData['own'][$field] ?? '');
            }
            if (Mode::is('printing') && ($rec->state ?? null) != 'active') $row->distance = '';
            if (Request::get('tripView') == 'issued') {
                foreach (arr::make(self::REPORT_FIELDS . ',reportedOn,reporterName,distance') as $field) $row->{$field} = '';
            }
            foreach (arr::make('odometerStart,odometerEnd,departedOn,returnedOn,reportedOn,reporterName,secondDriver,companions,distance') as $field) {
                if (!isset($row->{$field}) || $row->{$field} === '') $row->{$field} = '................................';
            }
        }
    }


    public static function getLineSummary($lineId)
    {
        $res = (object) array('distance' => null, 'unreported' => 0, 'sheets' => array());
        $query = self::getQuery();
        $query->where(array("#lineId = [#1#] AND #state != 'rejected'", (int) $lineId));
        $query->orderBy('id', 'ASC');
        while ($rec = $query->fetch()) {
            $res->sheets[] = $rec;
            if (($rec->state ?? null) == 'active') {
                $res->distance = ($res->distance ?? 0) + ($rec->odometerEnd ?? 0) - ($rec->odometerStart ?? 0);
            } else {
                $res->unreported++;
            }
        }

        return $res;
    }
}
