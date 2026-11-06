<?php


/**
 * Клас 'trans_TripSheets'
 *
 * Пътни листи и отчет на пробега към транспортни линии
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
class trans_TripSheets extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Пътни листи';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Пътен лист';


    /**
     * Абревиатура
     */
    public $abbr = 'Ts';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing, plg_Search, plg_Sorting';


    /**
     * Кой може да разглежда списъка?
     */
    public $canList = 'ceo,trans,seePrice';


    /**
     * Кой може да разглежда сингъла?
     */
    public $canSingle = 'ceo,trans,seePrice';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo,trans';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo,trans';


    /**
     * Кой може да издава (прави на заявка)?
     */
    public $canPending = 'ceo,trans';


    /**
     * Кой може да отчита пробега и разходите?
     */
    public $canReport = 'ceo,acc';


    /**
     * Кой може да оттегля?
     */
    public $canReject = 'ceo,trans';


    /**
     * Кой може да възстановява?
     */
    public $canRestore = 'ceo,trans';


    /**
     * Кой може да активира?
     */
    public $canActivate = 'no_one';


    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да променя активирания документ?
     */
    public $canChangerec = 'no_one';


    /**
     * Кой може да печата заповедта за превоз?
     */
    public $canOrder = 'ceo,trans';


    /**
     * Да се показва ли бутон за нов запис в списъка
     */
    public $listAddBtn = false;


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.8|Логистика';


    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/trip_sheet.png';


    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutTripSheet.shtml';


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
    public $listFields = 'title=Документ,lineId,departure,vehicleNumber,driverName,distance,state,createdOn,createdBy';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'companyName,vehicleNumber,driverName,route,cargo,transportDocuments';


    /**
     * Полетата, които се въвеждат при отчитане
     */
    const REPORT_FIELDS = 'odometerStart,odometerEnd,departedOn,returnedOn,expenseCurrencyId,expenses,reportNotes,signedSheet';


    /**
     * Полетата на отчета, видими само за тези, които виждат цените
     */
    const PRIVATE_REPORT_FIELDS = self::REPORT_FIELDS . ',reportedOn,reportedBy,reporterName,distance';


    /**
     * Полета, които се скриват от потребители без достъп до цените
     */
    public $priceFields = self::PRIVATE_REPORT_FIELDS;


    /**
     * Полетата, които не се променят след издаване
     */
    const ISSUE_FIELDS = 'lineId,originId,threadId,folderId,series,transportType,companyName,companyAddress,companyNumber,companyPhone,vehicleId,vehicleModel,vehicleNumber,driverId,driverName,secondDriver,companions,departure,expectedReturn,route,cargo,transportDocuments,cargoManifest,lineNotes,sourceData,authorizedBy,authorization,issuedOn,issuedBy,issuerName';


    /**
     * Описание на модела
     */
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
        $this->FLD('cargo', 'text(rows=2)', 'caption=Курс->Вид на товара,mandatory,hint=Кратко общо описание. Например: опаковки и консумативи. Посочете опасността само след проверка');
        $this->FLD('transportDocuments', 'text(rows=3)', 'caption=Курс->Документи за товара,mandatory');
        $this->FLD('cargoManifest', 'text(rows=10)', 'caption=От транспортната линия->Маршрут и товари');
        $this->FLD('lineNotes', 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Бележки');
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
        $this->FLD('expenses', trans_TripExpenses::getType(), 'caption=Разходи->По пера,input=none');
        $this->FLD('expenseCurrencyId', 'key(mvc=currency_Currencies,select=code)', 'caption=Разходи->Валута,input=none');
        $this->FLD('reportNotes', 'text(rows=3)', 'caption=Отчет->Отклонения и бележки,input=none');
        $this->FLD('signedSheet', 'fileman_FileType(bucket=Notes)', 'caption=Отчет->Подписан лист,input=none');
        $this->FLD('reportedOn', 'datetime', 'caption=Проверен на,input=none');
        $this->FLD('reportedBy', 'key(mvc=core_Users)', 'caption=Проверил,input=none');
        $this->FLD('reporterName', 'varchar(128)', 'caption=Проверил,input=none');
        $this->XPR('distance', 'int', '#odometerEnd - #odometerStart', 'caption=Пробег,unit=км');

        $this->setDbIndex('lineId,state');
        $this->setDbIndex('vehicleId,state,returnedOn');
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
        return false;
    }


    /**
     * Може ли документът да се добави в посочената нишка - само в неоттеглена транспортна линия
     *
     * @param int $threadId
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $first = doc_Threads::getFirstDocument($threadId);

        return $first && $first->isInstanceOf('trans_Lines') && $first->fetchField('state') != 'rejected';
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

        return tr('Пътен лист') . ' №' . ($rec->id ?? '');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form ?? null;
        $rec = $form->rec;
        $form->setOptions('driverId', trans_Vehicles::getDriverOptions());
        $form->setDefault('authorizedBy', core_Users::fetchField(core_Users::getCurrent(), 'names'));
        $refresh = !empty($rec->id) && Request::get('fromLine', 'int') && $mvc->haveRightFor('edit', $rec);
        if (!empty($rec->id) && !$refresh) {
            self::prepareCargoSummaryForm($form);

            return;
        }

        $form->setDefault('series', 'ТЛ');
        if ($refresh) {
            $form->FLD('fromLine', 'int', 'input=hidden,silent');
            $form->setDefault('fromLine', 1);
        }

        $first = !empty($rec->threadId) ? doc_Threads::getFirstDocument($rec->threadId ?? null) : null;
        if (!$first || !$first->isInstanceOf('trans_Lines')) {
            return;
        }

        $line = $first->fetch();
        trans_Lines::requireRightFor('single', $line);
        $form->setDefault('lineId', $line->id ?? null);
        $form->setDefault('originId', $line->containerId ?? null);
        $previous = clone $rec;

        // При обновяване от линията данните се презаписват, иначе са само дефолти
        $defaults = trans_LineDocuments::getTripDefaults($line->id);
        foreach ($defaults as $field => $value) {
            if ($refresh) {
                $rec->{$field} = $value;
            } else {
                $form->setDefault($field, $value);
            }
        }
        if ($refresh && ($previous->sourceData['manifestVersion'] ?? 0) >= 2) {
            $rec->cargo = $previous->cargo ?? '';
        }

        $company = crm_Companies::fetchOwnCompany();
        $form->setDefault('companyName', $company->company ?? '');
        $form->setDefault('companyNumber', $company->uicId ?? '');
        $form->setDefault('companyAddress', trim(($company->pCode ?? '') . ' ' . ($company->place ?? '') . ', ' . ($company->address ?? ''), ' ,'));
        $form->setDefault('companyPhone', $company->tel ?? '');

        $form->setField('route', 'unit=Подредете реалните посещения и добавете началния и крайния пункт. Предложението е по реда на документите');
        if ($refresh) {
            $form->info = 'Данните са предложени от текущата транспортна линия. Прегледайте ги и запишете промените.';
        }
        self::prepareCargoSummaryForm($form);
    }


    /**
     * Подготвя формата за обобщението на товара по документи
     *
     * @param core_Form $form
     *
     * @return void
     */
    private static function prepareCargoSummaryForm($form)
    {
        $rec = $form->rec;
        if (($rec->sourceData['manifestVersion'] ?? 0) < 2) {
            return;
        }

        // Издадените листове пазят вида си, а черновите са с краткото описание и документите
        $rec->sourceData['manifestVersion'] = 4;
        $form->setField('cargoManifest,transportDocuments', 'input=none');
        $form->FLD('cargoSourceHash', 'varchar(32)', 'input=hidden');
        $form->setDefault('cargoSourceHash', $rec->sourceData['hash'] ?? '');
        if (($rec->transportType ?? null) == 'mixed') {
            $form->setField('cargo', 'hint=Общ кратък вид на товара приложим и за собствените стоки в заповедта за превоз');
        }
    }


    /**
     * Името на полето за товара на един документ
     *
     * @param array  $item
     * @param string $field
     *
     * @return string
     */
    public static function getCargoFieldName($item, $field)
    {
        return 'cargo_' . $field . '_' . (!empty($item['containerId']) ? (int) $item['containerId'] : md5($item['reference']));
    }


    /**
     * Документът на товара с датата му
     *
     * @param array $item
     *
     * @return string
     */
    private static function getCargoReference($item)
    {
        return ($item['reference'] ?? '') . (!empty($item['documentDate']) ? ' / ' . dt::mysql2verbal($item['documentDate'], 'd.m.Y') : '');
    }


    /**
     * Пренася корекциите на потребителя по документа на товара; непроменените следват източника
     * null е изрично изчистване, а не липсваща корекция
     *
     * @param array    $summary
     * @param stdClass $previous
     *
     * @return array
     */
    public static function mergeCargoSummary($summary, $previous)
    {
        $saved = $baseline = array();
        foreach ($previous->sourceData['cargoSummary'] ?? array() as $item) {
            $saved[$item['containerId'] ?? $item['reference']] = $item;
        }
        foreach ($previous->sourceData['cargoSource'] ?? array() as $item) {
            $baseline[$item['containerId'] ?? $item['reference']] = $item;
        }

        foreach ($summary as &$item) {
            $key = isset($saved[$item['containerId']]) ? $item['containerId'] : $item['reference'];
            if (!isset($saved[$key])) {
                continue;
            }

            $old = $saved[$key];
            $base = $baseline[$key] ?? $old;
            foreach (array('packaging', 'totalWeight', 'volume', 'description') as $field) {
                if (!array_key_exists($field, $old)) {
                    continue;
                }
                $value = $old[$field];
                $original = $base[$field] ?? null;
                $same = in_array($field, array('totalWeight', 'volume'))
                    ? (($value === null || $value === '') ? ($original === null || $original === '') : ($original !== null && $original !== '' && (double) $value === (double) $original))
                    : (string) $value === (string) $original;
                if (!$same || $field == 'description') {
                    $item[$field] = $value;
                }
            }
            if (($previous->sourceData['manifestVersion'] ?? 0) == 2) {
                $item['description'] = $previous->cargo ?? '';
            }
        }
        unset($item);

        return $summary;
    }


    /**
     * Обновява текстовете на товара за търсенето и заповедта за превоз
     * Отпечатъкът и изходните стойности не включват корекциите на потребителя
     *
     * @param stdClass $rec
     *
     * @return void
     */
    private static function updateCargoSummary($rec)
    {
        if (($rec->sourceData['manifestVersion'] ?? 0) < 3) {
            return;
        }

        if ($rec->sourceData['manifestVersion'] >= 4) {
            $rec->transportDocuments = implode('; ', array_map(array(__CLASS__, 'getCargoReference'), $rec->sourceData['cargoSummary'] ?? array()));
            $rec->cargoManifest = '';
            $rec->sourceData['own']['cargo'] = $rec->cargo ?? '';

            return;
        }

        $descriptions = $ownDescriptions = $manifest = $references = array();
        foreach ($rec->sourceData['cargoSummary'] ?? array() as $item) {
            $description = trim($item['description'] ?? '');
            if ($description !== '') {
                $descriptions[$description] = $description;
                if (!empty($item['isOwn'])) {
                    $ownDescriptions[$description] = $description;
                }
            }
            $reference = self::getCargoReference($item);
            $references[] = $reference;
            $manifest[] = trans_LineDocuments::getManifestText($reference, $item);
        }
        $rec->cargo = implode('; ', $descriptions);
        $rec->cargoManifest = implode("\n", $manifest);
        $rec->transportDocuments = implode("\n", $references);
        $rec->sourceData['own']['cargo'] = implode('; ', $ownDescriptions);
    }


    /**
     * Показва само записаното обобщение, за да не се чете товарът наново в издаден лист
     *
     * @param array $summary
     * @param bool  $withDescription
     *
     * @return string
     */
    public static function renderCargoSummary($summary, $withDescription = false)
    {
        if (!$summary) {
            return '';
        }

        $number = core_Type::getByName('double(smartRound)');
        $html = '<table class="innerTable border-table paddingCells trip-cargo-summary" style="width:100%"><thead><tr>';
        $captions = array('Документ', 'Пакетаж', 'Бруто, kg', 'Обем, m³');
        if ($withDescription) {
            $captions[] = 'Вид';
        }
        foreach ($captions as $caption) {
            $html .= '<th>' . tr($caption) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($summary as $item) {
            $html .= '<tr style="page-break-inside:avoid"><td>' . type_Varchar::escape(self::getCargoReference($item)) . '</td><td>'
                . type_Varchar::escape(strlen($item['packaging'] ?? '') ? $item['packaging'] : tr('неуточнен')) . '</td>';
            foreach (array('totalWeight', 'volume') as $field) {
                $html .= '<td style="text-align:right">' . (isset($item[$field]) ? $number->toVerbal($item[$field]) : '—') . '</td>';
            }
            if ($withDescription) {
                $html .= '<td>' . type_Varchar::escape($item['description'] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()) {
            return;
        }

        $rec = $form->rec;
        if (($rec->sourceData['manifestVersion'] ?? 0) >= 3) {
            if (($rec->cargoSourceHash ?? '') !== ($rec->sourceData['hash'] ?? '')) {
                $form->setError('route', 'Транспортната линия е променена след отваряне на формата. Отворете отново Обновяване от ТЛ и прегледайте данните');
            }
            self::updateCargoSummary($rec);
        }
        if (!empty($rec->expectedReturn) && (($rec->expectedReturn ?? '') < ($rec->departure ?? ''))) {
            $form->setError('expectedReturn', 'Връщането не може да предхожда тръгването');
        }
        if (($rec->state ?? null) == 'active') {
            $form->setError('departure', 'Пробегът се въвежда с бутона Отчитане след издаване на листа');
        }
        if (($rec->state ?? null) == 'pending') {
            foreach ($mvc->getIssueErrors($rec) as $field => $message) {
                $form->setError($field, $message);
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

        $state = $rec->state ?? 'draft';
        if ($action == 'edit') {

            // Формата проверява правата отново след смяната на състоянието при издаване
            $saved = !empty($rec->id) ? $mvc->fetch($rec->id, '*', false) : $rec;
            if (($saved->state ?? 'draft') != 'draft' || !empty($saved->issuedOn)) {
                $roles = 'no_one';
            }
        }
        if ($action == 'pending' && ($state != 'draft' || !empty($rec->issuedOn))) {
            $roles = 'no_one';
        }
        if ($action == 'report' && (!in_array($state, array('pending', 'active')) || empty($rec->issuedOn) || !self::canViewReport($userId))) {
            $roles = 'no_one';
        }
        if ($action == 'order' && (empty($rec->issuedOn) || !in_array($rec->transportType ?? null, array('own', 'mixed')) || $state == 'rejected')) {
            $roles = 'no_one';
        }
        if (in_array($action, array('report', 'order')) && !empty($rec->id) && !$mvc->haveRightFor('single', $rec, $userId)) {
            $roles = 'no_one';
        }
        if ($action == 'restore' && ($rec->brState ?? null) == 'active' && $mvc->getOverlap($rec)) {
            $roles = 'no_one';
        }

        // Нужна е неоттеглена линия, до която потребителят има достъп
        if (in_array($action, array('add', 'pending', 'report', 'restore', 'order'))) {
            $lineId = $rec->lineId ?? null;
            if (!$lineId && !empty($rec->threadId)) {
                $first = doc_Threads::getFirstDocument($rec->threadId ?? null);
                if ($first && $first->isInstanceOf('trans_Lines')) {
                    $lineId = $first->that ?? null;
                }
            }
            if ($lineId) {
                $line = trans_Lines::fetch($lineId);
                if (!$line || ($line->state ?? null) == 'rejected' || ($action != 'report' && !trans_Lines::haveRightFor('single', $line, $userId))) {
                    $roles = 'no_one';
                }
                if ($action == 'add' && ($line->state ?? null) != 'active') {
                    $roles = 'no_one';
                }
            } elseif ($action != 'add' || !empty($rec->threadId)) {
                $roles = 'no_one';
            }
        }
    }


    /**
     * Преди запис - издаване, заключване на издадените данни и проверка на отчета
     */
    protected static function on_BeforeSave($mvc, &$id, &$rec, &$fields = null)
    {
        unset($rec->_attachWaybills);
        $old = !empty($rec->id) ? $mvc->fetch($rec->id ?? null, '*', false) : null;
        if (!empty($rec->_tripReport) && (!$old || !$mvc->haveRightFor('report', $old))) {
            return false;
        }

        // Издаденият лист пази данните си, а отчетът се променя само от отчитането
        if (!empty($old->issuedOn)) {
            foreach (arr::make(self::ISSUE_FIELDS) as $field) {
                $rec->{$field} = $old->{$field} ?? null;
            }
            if (empty($rec->_tripReport)) {
                foreach (arr::make(self::REPORT_FIELDS . ',reportedOn,reportedBy,reporterName') as $field) {
                    $rec->{$field} = $old->{$field} ?? null;
                }
            }
        }

        $full = (object) array_merge((array) $old, (array) $rec);
        if (empty($full->issuedOn) && ($full->sourceData['manifestVersion'] ?? 0) >= 3) {
            self::updateCargoSummary($full);
            foreach (array('cargo', 'cargoManifest', 'transportDocuments', 'sourceData') as $field) {
                $rec->{$field} = $full->{$field};
            }
            if ($fields !== null) {
                $fields = arr::make($fields, true) + arr::make('cargo,cargoManifest,transportDocuments,sourceData', true);
            }
        }

        $line = trans_Lines::fetch($full->lineId ?? null);
        expect($line && ($line->containerId ?? null) == ($full->originId ?? null)
            && ($line->threadId ?? null) == ($full->threadId ?? null), 'Невалидна транспортна линия');

        // Издаване
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
            if ($fields !== null) {
                $fields = arr::make($fields, true) + arr::make('vehicleNumber,driverName,issuedOn,issuedBy,issuerName', true);
            }
        }

        // Отчитане - заключва се МПС-то, за да не се припокрият два отчета
        if (($full->state ?? null) == 'active') {
            if (empty($full->issuedOn)) {
                return false;
            }
            if (!empty($rec->_tripReport)) {
                $rec->expenses = $full->expenses = trans_TripExpenses::normalize($full->expenses ?? null, substr($full->departedOn ?? $full->departure ?? dt::today(), 0, 10));
            }
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


    /**
     * Грешките, които пречат на издаването
     *
     * @param stdClass $rec
     *
     * @return array
     */
    private function getIssueErrors($rec)
    {
        $errors = array();
        $errors += trans_Waybills::getTripBindingErrors($rec);
        if (!empty($rec->sourceData['hash'])) {
            $current = trans_LineDocuments::getTripDefaults($rec->lineId);
            if ($rec->sourceData['hash'] != $current['sourceData']['hash']) {
                $errors['route'] = 'Транспортната линия е променена. Използвайте Обновяване от ТЛ и прегледайте данните преди издаване';
            }
            if (!empty($current['sourceData']['unavailable'])) {
                $errors['transportDocuments'] = 'Нямате достъп до всички стокови документи на линията';
            }
            if (!empty($current['sourceData']['waybills']) && ($rec->transportType ?? null) == 'own') {
                $errors['transportType'] = 'Линията включва товарителници за външни товари. Изберете обществен или смесен превоз';
            }
        }

        foreach (arr::make('series,companyName,companyNumber,companyAddress,vehicleModel,departure,route,cargo,transportDocuments,authorizedBy') as $field) {
            if (trim($rec->{$field} ?? '') === '') {
                $errors[$field] = 'Попълнете реквизитите преди издаване на пътния лист';
            }
        }

        if (($rec->sourceData['manifestVersion'] ?? 0) == 3) {
            foreach ($rec->sourceData['cargoSummary'] ?? array() as $item) {
                if (trim($item['description'] ?? '') === '') {
                    $errors[self::getCargoFieldName($item, 'description')] = 'Попълнете кратък вид на товара за документа преди издаване';
                }
                foreach (array('totalWeight', 'volume') as $field) {
                    if (isset($item[$field]) && (!is_numeric($item[$field]) || $item[$field] < 0)) {
                        $errors[self::getCargoFieldName($item, $field)] = 'Въведете неотрицателно число';
                    }
                }
            }
        }

        $vehicle = !empty($rec->vehicleId) ? trans_Vehicles::fetch($rec->vehicleId ?? null) : null;
        if (empty($vehicle->number)) {
            $errors['vehicleId'] = 'Изберете МПС с попълнен регистрационен номер';
        }
        if (empty($rec->driverId) || !crm_Persons::fetch($rec->driverId ?? null)) {
            $errors['driverId'] = 'Изберете водач';
        }
        if (!empty($rec->expectedReturn) && ($rec->expectedReturn ?? '') < ($rec->departure ?? '')) {
            $errors['expectedReturn'] = 'Връщането не може да предхожда тръгването';
        }

        return $errors;
    }


    /**
     * След запис - товарителниците, заключването и кешът
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if (!empty($rec->_attachWaybills)) {
            trans_Waybills::attachIssuedTrip($mvc->fetch($id, '*', false));
        }
        if (($rec->state ?? null) == 'active') {
            core_Locks::release('transTripVehicle' . ($rec->vehicleId ?? 0));
        }
        if (!empty($rec->originId)) {
            doc_DocumentCache::cacheInvalidation($rec->originId ?? null);
        }
    }


    /**
     * Проверява отчетните данни, включително нулев начален километраж
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public static function getReportErrors($rec)
    {
        $errors = array();
        foreach (array('odometerStart', 'odometerEnd', 'departedOn', 'returnedOn') as $field) {
            if (!isset($rec->{$field}) || $rec->{$field} === '') {
                $errors[$field] = 'Полето е задължително при отчитане';
            }
        }
        if (isset($rec->odometerStart, $rec->odometerEnd)) {
            if (($rec->odometerStart ?? 0) < 0 || ($rec->odometerEnd ?? 0) < ($rec->odometerStart ?? 0)) {
                $errors['odometerStart,odometerEnd'] = 'Крайният километраж трябва да е поне колкото началния, а началният да не е отрицателен';
            }
        }
        if (!empty($rec->departedOn) && !empty($rec->returnedOn) && ($rec->returnedOn ?? '') < ($rec->departedOn ?? '')) {
            $errors['departedOn,returnedOn'] = 'Връщането не може да предхожда тръгването';
        }

        $errors += trans_TripExpenses::getErrors($rec->expenses ?? null);
        if (!isset($errors['expenses']) && countR(type_Table::toArray(trans_TripExpenses::normalize($rec->expenses ?? null)))
            && (empty($rec->expenseCurrencyId) || !currency_Currencies::fetch($rec->expenseCurrencyId))) {
            $errors['expenseCurrencyId'] = 'Изберете валута за разходите';
        }

        return $errors;
    }


    /**
     * Отчетен пътен лист на същото МПС, с който се припокриват пробегът или времето
     *
     * @param stdClass $rec
     *
     * @return stdClass|false
     */
    private function getOverlap($rec)
    {
        if (self::getReportErrors($rec)) {
            return false;
        }

        $query = $this->getQuery();
        $query->where(array("#vehicleId = [#1#] AND #state = 'active' AND #id != [#2#]", (int) ($rec->vehicleId ?? 0), (int) ($rec->id ?? 0)));
        $query->where(array("((#odometerStart < [#1#] AND #odometerEnd > [#2#]) OR (#departedOn < '[#3#]' AND #returnedOn > '[#4#]'))",
            (int) ($rec->odometerEnd ?? 0), (int) ($rec->odometerStart ?? 0), $rec->returnedOn ?? '', $rec->departedOn ?? ''));

        return $query->fetch();
    }


    /**
     * Екшън за отчитане на пробега и разходите
     */
    public function act_Report()
    {
        $id = Request::get('id', 'int');
        $rec = $this->fetchRec($id);
        $this->requireRightFor('report', $rec);
        $form = cls::get('core_Form');
        $form->setAction(array($this, 'report', $id));
        $reportFields = array_diff(arr::make(self::REPORT_FIELDS), array('signedSheet'));
        foreach ($reportFields as $field) {
            $definition = $this->getField($field);
            $type = $definition->type ?? null;
            $form->FLD($field, clone $type, array('caption' => $definition->caption ?? $field));
            $form->setDefault($field, $rec->{$field} ?? null);
        }
        $form->FLD('id', 'int', 'input=hidden,silent');
        $form->setDefault('id', $id);
        $form->FLD('reportHash', 'varchar(32)', 'input=hidden');
        $form->setDefault('reportHash', self::getReportHash($rec));
        $form->setDefault('departedOn', $rec->departure ?? null);
        $form->setDefault('returnedOn', $rec->expectedReturn ?? null);
        $form->setField('departedOn', 'hint=Предложено от планираното тръгване. Проверете действителните дата и час');
        $form->setField('returnedOn', 'hint=Когато има планирано връщане то се предлага за проверка');
        $form->setDefault('expenseCurrencyId', currency_Currencies::getIdByCode(acc_Periods::getBaseCurrencyCode($rec->departure ?? dt::today())));
        $form->setField('expenses', 'hint=Незадължително. Всяко зареждане или разход е отделен ред. Мярката се определя от перото; сумите са с ДДС');
        foreach (arr::make('odometerStart,odometerEnd,departedOn,returnedOn') as $field) {
            $form->setField($field, 'mandatory');
        }

        // Началният километраж се предлага от края на предходния отчет на МПС-то
        $previousQuery = $this->getQuery();
        $previousQuery->where(array("#vehicleId = [#1#] AND #state = 'active' AND #returnedOn <= '[#2#]' AND #id != [#3#]", (int) ($rec->vehicleId ?? 0), $rec->departedOn ?? $rec->departure ?? '', (int) $id));
        $previousQuery->orderBy('returnedOn', 'DESC');
        $previous = $previousQuery->fetch();
        if ($previous) {
            $form->setDefault('odometerStart', $previous->odometerEnd ?? null);
            $form->setSuggestions('odometerStart', array(($previous->odometerEnd ?? 0) => ($previous->odometerEnd ?? 0)));
        }

        $form->title = 'Пробег и разходи към|* ' . $this->getLink($id, 0);
        $form->input();
        if ($form->isSubmitted()) {
            $report = clone $rec;
            foreach ($reportFields as $field) {
                $report->{$field} = $form->rec->{$field} ?? null;
            }
            $report->expenses = trans_TripExpenses::normalize($report->expenses ?? null, substr($report->departedOn ?? $rec->departure ?? dt::today(), 0, 10));
            foreach (self::getReportErrors($report) as $field => $message) {
                $form->setError($field, $message);
            }
            if (!$form->gotErrors() && $this->getOverlap($report)) {
                $form->setError('odometerStart,odometerEnd,departedOn,returnedOn', 'Пробегът или времето се припокрива с друг отчетен пътен лист');
            }
            if ($previous && isset($report->odometerStart) && ($report->odometerStart ?? null) != ($previous->odometerEnd ?? null)) {
                $form->setWarning('odometerStart', 'Началният километраж се различава от края на предходния отчет|*: ' . ($previous->odometerEnd ?? ''));
            }

            if (!$form->gotErrors()) {
                $lock = 'transTripVehicle' . ($rec->vehicleId ?? 0);
                if (core_Locks::obtain($lock, 30, 3, 3)) {
                    try {
                        $current = $this->fetch($id, '*', false);
                        $this->requireRightFor('report', $current);

                        // Отчетът е променен от друг, докато формата е била отворена
                        if ($form->rec->reportHash !== self::getReportHash($current)) {
                            $form->setError('odometerStart,expenses', 'Друг потребител е променил отчета. Отворете го отново преди запис');
                        } else {
                            $report->state = 'active';
                            $report->reportedOn = dt::now();
                            $report->reportedBy = core_Users::getCurrent();
                            $report->reporterName = core_Users::fetchField($report->reportedBy ?? null, 'names');
                            $report->_tripReport = true;
                            $report->_isActivated = empty($current->reportedOn);
                            if ($this->save($report)) {
                                $this->logWrite('Отчитане на пробег и разходи', $id);

                                return new Redirect(array($this, 'single', $id));
                            }
                        }
                    } finally {
                        core_Locks::release($lock);
                    }
                }
                $form->setError('odometerStart,odometerEnd', 'Отчетът не е записан. Проверете данните и опитайте отново');
            }
        }

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon=img/16/tick.png');
        $form->toolbar->addBtn('Отказ', array($this, 'single', $id), 'ef_icon=img/16/close-red.png');

        $tpl = $form->renderHtml();
        $tpl->push('trans/js/TripExpenses.js', 'JS');
        $expenseDate = substr($form->rec->departedOn ?? $rec->departedOn ?? $rec->departure ?? dt::today(), 0, 10);
        jquery_Jquery::run($tpl, 'transTripExpensesInit(' . json_encode(trans_TripExpenses::getCategoryUnits()) . ', ' . json_encode(dt::mysql2verbal($expenseDate, 'd.m.Y')) . ');');

        return $this->renderWrapping($tpl);
    }


    /**
     * Отпечатък на отчета, за да се открие паралелна промяна
     *
     * @param stdClass $rec
     *
     * @return string
     */
    private static function getReportHash($rec)
    {
        $values = array();
        foreach (arr::make(self::PRIVATE_REPORT_FIELDS . ',state') as $field) {
            $values[$field] = $rec->{$field} ?? null;
        }

        return md5(serialize($values));
    }


    /**
     * Може ли потребителят да вижда отчета (пробег и разходи)
     *
     * @param int|null $userId
     *
     * @return bool
     */
    public static function canViewReport($userId = null)
    {
        return !core_Users::haveRole('noPrice', $userId) && core_Users::haveRole('ceo,seePrice', $userId);
    }


    /**
     * Преди вербализиране - скриване на отчета от тези, които не виждат цените
     */
    protected static function on_BeforeGetVerbal($mvc, &$res, $rec, $fieldName)
    {
        if (!self::canViewReport() && in_array($fieldName, arr::make(self::PRIVATE_REPORT_FIELDS))) {
            $res = doc_plg_HidePrices::getBuriedElement();

            return false;
        }
    }


    /**
     * След свързаните файлове - подписаният лист е част от отчета
     */
    protected static function on_AfterGetLinkedFiles($mvc, &$res, $rec)
    {
        if (self::canViewReport()) {
            return;
        }

        $rec = $mvc->fetchRec($rec);
        if (!empty($rec->signedSheet) && is_array($res)) {
            unset($res[$rec->signedSheet]);
        }
    }


    /**
     * След прикачените файлове - както при свързаните
     */
    protected static function on_AfterGetAttachments($mvc, &$res, $rec)
    {
        self::on_AfterGetLinkedFiles($mvc, $res, $rec);
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec ?? null;
        $toolbar = $data->toolbar ?? null;
        if ($mvc->haveRightFor('edit', $rec)) {
            $toolbar->addBtn('Обновяване от ТЛ', array($mvc, 'edit', $rec->id, 'fromLine' => 1, 'ret_url' => true), 'row=2,ef_icon=img/16/arrow_refresh.png,title=Предлагане на актуалните данни от линията за преглед и запис');
        }
        if ($mvc->haveRightFor('report', $rec)) {
            $toolbar->addBtn('Пробег и разходи', array($mvc, 'report', $rec->id ?? null), 'ef_icon=img/16/lightning.png');
        }
        if ($mvc->haveRightFor('order', $rec)) {
            $toolbar->addBtn('Печат заповед', array($mvc, 'single', $rec->id ?? null, 'Printing' => 'yes', 'tripView' => 'order'), 'target=_blank,ef_icon=img/16/printer.png');
        }
        if (!empty($rec->issuedOn)) {
            $toolbar->addBtn('Издаден лист', array($mvc, 'single', $rec->id ?? null, 'Printing' => 'yes', 'tripView' => 'issued'), 'row=2,target=_blank,ef_icon=img/16/printer.png');
        }
    }


    /**
     * Рендиране на изгледа - при печат винаги на български, а заповедта е отделен шаблон
     */
    public function renderSingleLayout_(&$data)
    {
        $printing = Mode::is('printing');
        if ($printing) {
            core_Lg::push('bg');
        }
        try {
            if (Request::get('tripView') == 'order') {
                $this->requireRightFor('order', $data->rec ?? null);

                return getTplFromFile('trans/tpl/TripOrder.shtml');
            }

            $tpl = parent::renderSingleLayout_($data);
            if (($data->rec->sourceData['manifestVersion'] ?? 0) >= 4) {
                $tpl->removeBlock('cargoManifest');
                $tpl->removeBlock('cargoSummary');
            } elseif (($data->rec->sourceData['manifestVersion'] ?? 0) >= 2) {
                $tpl->removeBlock('cargoManifest');
                $tpl->removeBlock('transportDocuments');
                if ($data->rec->sourceData['manifestVersion'] >= 3) {
                    $tpl->removeBlock('cargo');
                }
            }

            return $tpl;
        } finally {
            if ($printing) {
                core_Lg::pop();
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id ?? null, 0);
        if (!empty($rec->lineId)) {
            $row->lineId = trans_Lines::getHyperlink($rec->lineId ?? null, true);
        }
        $canViewReport = self::canViewReport();
        if (!$canViewReport) {
            $row->distance = doc_plg_HidePrices::getBuriedElement();
        } elseif (($rec->state ?? null) != 'active') {
            $row->distance = tr('Неотчетен');
        }

        if (isset($fields['-single'])) {
            $row->sheetNumber = str_pad((string) ($rec->id ?? ''), 10, '0', STR_PAD_LEFT);
            $row->issueDate = !empty($rec->issuedOn) ? dt::mysql2verbal($rec->issuedOn ?? '', 'd.m.Y') : tr('ЧЕРНОВА');
            if (empty($rec->vehicleNumber) && !empty($rec->vehicleId)) {
                $row->vehicleNumber = $mvc->getFieldType('vehicleNumber')->toVerbal(trans_Vehicles::fetchField($rec->vehicleId, 'number'));
            }
            $row->driverName = $row->driverName ?? $mvc->getVerbal($rec, 'driverId');
            $row->documentStatus = $row->state ?? '';
            if (in_array($rec->sourceData['manifestVersion'] ?? 0, array(2, 3)) && Request::get('tripView') != 'order') {
                $row->cargoSummary = self::renderCargoSummary($rec->sourceData['cargoSummary'] ?? array(), $rec->sourceData['manifestVersion'] >= 3);
                $row->cargoManifest = $row->transportDocuments = '';
            }

            // В заповедта при смесен превоз се показват само собствените стоки
            if (Request::get('tripView') == 'order' && ($rec->transportType ?? null) == 'mixed') {
                $ownFields = ($rec->sourceData['manifestVersion'] ?? null) == 2 ? 'route,transportDocuments' : 'route,cargo,transportDocuments';
                foreach (arr::make($ownFields) as $field) {
                    $row->{$field} = $mvc->getFieldType($field)->toVerbal($rec->sourceData['own'][$field] ?? '');
                }
            }
            if (Mode::is('printing') && ($rec->state ?? null) != 'active') {
                $row->distance = '';
            }

            $showReport = $canViewReport && Request::get('tripView') != 'issued';
            $currency = !empty($rec->expenseCurrencyId) && $showReport
                ? currency_Currencies::fetchField($rec->expenseCurrencyId, 'code')
                : acc_Periods::getBaseCurrencyCode($rec->departure ?? dt::today());
            $row->expenseReport = $showReport && !empty($rec->expenses) ? trans_TripExpenses::render($rec->expenses, $currency) : '';
            if (!$showReport) {
                foreach (arr::make(self::PRIVATE_REPORT_FIELDS) as $field) {
                    $row->{$field} = '';
                }
            }

            // Празни редове за движението - поне по един за всяко място от товара
            $places = array();
            foreach ($rec->sourceData['cargoSummary'] ?? array() as $item) {
                foreach (array('loadingPlace', 'unloadingPlace') as $field) {
                    if (!empty($item[$field])) {
                        $places[$item[$field]] = true;
                    }
                }
            }
            $movementRows = ($rec->sourceData['manifestVersion'] ?? 0) >= 4 ? max(4, count($places) + 1) : 4;
            $row->movementRows = str_repeat('<tr style="height:35px;page-break-inside:avoid"><td>&nbsp;</td><td style="width:35%"></td><td></td><td></td><td></td><td></td></tr>', $movementRows);

            // Празните полета се печатат с точки за ръчно попълване
            foreach (arr::make('odometerStart,odometerEnd,departedOn,returnedOn,reportedOn,reporterName,secondDriver,companions,distance') as $field) {
                if (!isset($row->{$field}) || $row->{$field} === '') {
                    $row->{$field} = '................................';
                }
            }
        }
    }


    /**
     * Обобщение на пътните листи на линията: пробег и неотчетени
     *
     * @param int $lineId
     *
     * @return stdClass
     */
    public static function getLineSummary($lineId)
    {
        $res = (object) array('distance' => null, 'unreported' => 0, 'sheets' => array());
        $query = self::getQuery();
        $query->where(array("#lineId = [#1#] AND #state != 'rejected'", (int) $lineId));
        $query->orderBy('id', 'ASC');
        while ($rec = $query->fetch()) {
            $sheet = clone $rec;
            if (!self::canViewReport()) {
                foreach (arr::make(self::PRIVATE_REPORT_FIELDS) as $field) {
                    unset($sheet->{$field});
                }
            }
            $res->sheets[] = $sheet;
            if (($rec->state ?? null) == 'active') {
                if (self::canViewReport()) {
                    $res->distance = ($res->distance ?? 0) + ($rec->odometerEnd ?? 0) - ($rec->odometerStart ?? 0);
                }
            } else {
                $res->unreported++;
            }
        }

        return $res;
    }
}
