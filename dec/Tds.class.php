<?php


/**
 * Технически спецификации (TDS) на артикулите
 *
 *
 * @category  bgerp
 * @package   dec
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class dec_Tds extends core_Master
{
    /**
     * Старо име на класа
     */
    public $oldClassName = 'tds_Documents';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';


    /**
     * Документът може да се споделя с партньори
     */
    public $visibleForPartners = true;


    /**
     * Заглавие
     */
    public $title = 'Технически спецификации';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Техническа спецификация';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, doc_plg_TplManager, plg_Clone, bgerp_plg_Blank, recently_Plugin, doc_ActivatePlg, plg_Printing, plg_RowTools2, doc_DocumentPlg, doc_EmailCreatePlg, acc_plg_DocumentSummary, plg_Search, change_Plugin';


    /**
     * Кой има право да листва?
     */
    public $canList = 'ceo,dec';


    /**
     * Кой има право да разглежда сингъла?
     */
    public $canSingle = 'ceo,dec';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,dec';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,dec';


    /**
     * Кой има право да активира?
     */
    public $canActivate = 'ceo,dec';


    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'ceo,dec';


    /**
     * Кой има право да променя активиран документ?
     */
    public $canChangerec = 'ceo,dec';


    /**
     * Полета, които могат да се променят с change_Plugin
     */
    public $changableFields = 'documentNumber,revision,date,signerName,unitWeight,intendedUse,useRestrictions,foodContactStatus,countriesOfUse,recycledContent,recyclability,complianceDeclarationIds,certifications,complianceDocuments,storageConditions,packagingDescription,tolerances,notes,disclaimer';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'productData,productDataOn,originCountrySnapshot,declarationSnapshot';


    /**
     * Полета в списъчния изглед
     */
    public $listFields = 'id,documentNumber,date,revision,productId,folderId,state,createdOn,createdBy';


    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'dec/tpl/SingleLayoutTds.shtml';


    /**
     * Плейсхолдър за избрания шаблон на документа
     */
    public $templateFld = 'content';


    /**
     * Абревиатура
     */
    public $abbr = 'Tds';


    /**
     * Полета, по които може да се филтрира по дата
     */
    public $filterDateField = 'date,createdOn,modifiedOn';


    /**
     * Полета за търсене
     */
    public $searchFields = 'productId,documentNumber,signerName,intendedUse,countriesOfUse,id';


    /**
     * Поле за линк към единичния изглед в списъка
     */
    public $rowToolsSingleField = 'documentNumber';


    /**
     * Документът се създава от бутон в артикула, не от списъка
     */
    public $listAddBtn = false;


    /**
     * Икона на документа
     */
    public $singleIcon = 'img/16/technical-sheet.png';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('template', 'key(mvc=doc_TplManager,select=name)', 'caption=Документ||Document->Изглед,mandatory,silent,notChangeableByContractor,removeAndRefreshForm=intendedUse|foodContactStatus|recycledContent|complianceDeclarationIds|packagingDescription|storageConditions|certifications|tolerances|disclaimer');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,withClosed,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Артикул,input=none,mandatory');

        $this->FLD('documentNumber', 'varchar(64)', 'caption=Документ||Document->Номер||Number,mandatory');
        $this->FLD('revision', 'varchar(16)', 'caption=Документ||Document->Версия||Revision,mandatory');
        $this->FLD('date', 'date', 'caption=Документ||Document->Издаден на||Issued on,mandatory');
        $this->FLD('signerName', 'varchar(255)', 'caption=Подпис||Signature->За производителя||For the manufacturer,mandatory,recently,remember');
        $this->FLD('unitWeight', 'double(Min=0,smartRound)', 'caption=Технически данни||Technical data->Тегло на единично изделие||Unit weight,unit=гр');
        $this->FLD('intendedUse', 'richtext(rows=3)', 'caption=Приложение||Application->Предназначение||Intended use,mandatory');
        $this->FLD('useRestrictions', 'richtext(rows=2)', 'caption=Приложение||Application->Ограничения||Use restrictions');
        $this->FLD('foodContactStatus', 'enum(yes=Да||Yes,no=Не||No,notSpecified=Не е посочено||Not specified)', 'caption=Съответствие||Compliance->Контакт с храни||Food contact,mandatory');
        $this->FLD('countriesOfUse', 'varchar(255)', 'caption=Съответствие||Compliance->Държави на употреба||Countries of use');
        $this->FLD('recycledContent', 'varchar(255)', 'caption=Съответствие||Compliance->Рециклирано съдържание||Recycled content');
        $this->FLD('recyclability', 'varchar(255)', 'caption=Съответствие||Compliance->Рециклируемост||Recyclability');
        $this->FLD('complianceDeclarationIds', 'keylist(mvc=dec_Statements,select=title)', 'caption=Съответствие||Compliance->Декларации при поискване||Declarations available on request,maxColumns=2');
        $this->FLD('certifications', 'richtext(rows=2)', 'caption=Съответствие||Compliance->Сертификати||Certifications');
        $this->FLD('complianceDocuments', 'richtext(rows=2)', 'caption=Съответствие||Compliance->Свързани документи||Related documents');

        $this->FLD('storageConditions', 'richtext(rows=2)', 'caption=Допълнителна информация||Additional information->Условия за съхранение||Storage conditions');
        $this->FLD('packagingDescription', 'richtext(rows=2)', 'caption=Допълнителна информация||Additional information->Опаковка||Packaging description');
        $this->FLD('tolerances', 'richtext(rows=3)', 'caption=Допълнителна информация||Additional information->Технически допуски||Technical tolerances');
        $this->FLD('notes', 'richtext(rows=2)', 'caption=Допълнителна информация||Additional information->Бележки||Notes');
        $this->FLD('disclaimer', 'richtext(rows=2)', 'caption=Допълнителна информация||Additional information->Отказ от отговорност||Disclaimer');

        $this->FLD('productData', 'blob(serialize, compress)', 'caption=Snapshot->Product data,input=none');
        $this->FLD('productDataOn', 'datetime(format=smartTime)', 'caption=Snapshot->Product data on,input=none');
        $this->FLD('originCountrySnapshot', 'varchar(128)', 'caption=Snapshot->Origin,input=none');
        $this->FLD('declarationSnapshot', 'blob(1000000)', 'caption=Snapshot->Declarations,input=none');

        $this->setDbIndex('productId');
    }


    /**
     * Стойности по подразбиране от артикула ориджин
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $productRec = null;

        if (!empty($rec->originId)) {
            $productRec = self::getProductFromOrigin($rec->originId);
            if ($productRec) {
                $form->setDefault('productId', $productRec->id);
                $form->setDefault('documentNumber', 'TDS-' . self::getProductCode($productRec));
            }
        }

        // Езикът на документа следва избрания шаблон
        $folderId = $rec->folderId ?? ($productRec->folderId ?? null);
        $form->setDefault('template', $mvc->getDefaultTemplate((object) array('folderId' => $folderId)));
        $language = self::getRecordLanguage($rec);

        // Твърденията са на езика на шаблона
        $declarationOptions = dec_Statements::getStatementOptions($language, $rec->complianceDeclarationIds ?? null);
        if (countR($declarationOptions)) {
            $form->setSuggestions('complianceDeclarationIds', $declarationOptions);
        } else {
            $form->setField('complianceDeclarationIds', 'input=none');
        }

        $form->setDefault('revision', '1');
        $form->setDefault('date', dt::today());

        $signerSuggestions = self::getSignerSuggestions();
        if ($signerSuggestions) {
            $form->setSuggestions('signerName', $signerSuggestions);
            $form->setDefault('signerName', key($signerSuggestions));
        }

        if ($productRec) {
            $defaults = self::getProductDefaults($productRec, $language);

            // Деактивираните твърдения не се предлагат по подразбиране
            if (!empty($defaults->complianceDeclarationIds)) {
                $defaults->complianceDeclarationIds = keylist::fromArray(array_intersect_key(keylist::toArray($defaults->complianceDeclarationIds), $declarationOptions));
            }
            foreach ($defaults as $name => $value) {
                if (!$form->getField($name, false)) {
                    continue;
                }
                if (isset($value) && trim((string) $value) !== '') {
                    $form->setDefault($name, $value);
                }
            }
        }
    }


    /**
     * При промяна на активен документ - предупреждение, ако артикулът е променен след снимката
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if (!$form->isSubmitted() || empty($rec->__isBeingChanged)) {

            return;
        }

        $storedRec = $mvc->fetch($rec->id);
        if (!self::isProductDataChanged($storedRec)) {

            return;
        }

        $form->setWarning('revision', 'Данните на артикула са променени междувременно. Те ще бъдат опреснени, а версията - увеличена с 1|*!');
        if (!$form->isSubmitted()) {

            return;
        }

        // Ръчно сменената версия не се пипа
        if ($rec->revision == $storedRec->revision && ctype_digit((string) $storedRec->revision)) {
            $rec->revision = (string) ($storedRec->revision + 1);
        }

        // Опресняването е промяна, дори да не е сменено друго поле
        $rec->_refreshProductData = true;
        $rec->__noChange = false;
    }


    /**
     * Записва моментна снимка на данните на артикула при активиране и опресняване
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        $recId = $id ?: ($rec->id ?? null);
        $storedRec = $recId ? $mvc->fetch($recId) : null;
        if ($storedRec) {
            foreach ($storedRec as $name => $value) {
                if (!property_exists($rec, $name)) {
                    $rec->{$name} = $value;
                }
            }
        }

        if (empty($rec->productId) && !empty($rec->originId)) {
            $productRec = self::getProductFromOrigin($rec->originId);
            if ($productRec) {
                $rec->productId = $productRec->id;
            }
        }

        expect(!empty($rec->productId), 'A product is required for the TDS document');

        if (empty($rec->template)) {
            $rec->template = $mvc->getDefaultTemplate($rec);
        }

        if (empty($rec->documentNumber)) {
            $rec->documentNumber = 'TDS-' . self::getProductCode(cat_Products::fetch($rec->productId));
        }

        $rec->revision = $rec->revision ?: '1';
        $rec->date = $rec->date ?: dt::today();
        $language = self::getRecordLanguage($rec);

        if (!$storedRec) {
            $rec->originCountrySnapshot = self::getDriverTdsData(cat_Products::fetch($rec->productId), $language)['originCountry'] ?? null;
        }

        // Черновата е на живо, а активираният документ пази данните на артикула
        if (($rec->state ?? null) == 'active' && (empty($rec->productData) || !empty($rec->_refreshProductData))) {
            $rec->productData = self::createProductSnapshot($rec->productId, $language);
            $rec->productDataOn = dt::now();
        }

        // Активираният документ пази декларациите си, докато не се сменят
        if (($rec->state ?? null) == 'active') {
            $declarationsChanged = ($storedRec->complianceDeclarationIds ?? null) != ($rec->complianceDeclarationIds ?? null);
            if (!isset($rec->declarationSnapshot) || $declarationsChanged) {
                $rec->declarationSnapshot = self::renderComplianceDeclarations($rec, $language);
            }
        }
    }


    /**
     * Подготвя стойностите за шаблона към клиента
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $language = self::getRecordLanguage($rec);
        $labels = self::getLabels($language);

        $row->documentTitle = $labels['documentTitle'];
        $row->documentSubtitle = $labels['documentSubtitle'];
        $productData = self::getProductData($rec);
        $productName = core_Type::getByName('varchar')->toVerbal($productData->name ?? '');

        // При печат, PDF и имейл е без линк, както в core_Master::getHyperlink()
        $productUrl = (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) ? array() : cat_Products::getSingleUrlArray($rec->productId);
        $row->productName = ht::createLinkRef($productName, $productUrl);
        $row->productCode = core_Type::getByName('varchar')->toVerbal($productData->code ?? '');
        $row->manufacturer = core_Type::getByName('varchar')->toVerbal(self::getManufacturer($language, $productData->manufacturer ?? null));
        $row->originCountry = $mvc->getVerbal($rec, 'originCountrySnapshot');
        $row->productDescription = self::removeInternalLinks($productData->description ?? '');

        // В черновата данните на артикула са текущите и се записват чак при активиране
        if (($rec->state ?? null) == 'draft' && !Mode::isReadOnly()) {
            $row->liveDataHint = ht::createHint('', 'Данните на артикула са текущите и ще бъдат записани при активиране|*!');
        }

        // Кога са записани данните на артикула - само на екрана
        if (!empty($rec->productDataOn) && !Mode::isReadOnly()) {
            $row->PRODUCT_DATA_ON = " <span class='quiet small'>(" . tr('данни към') . ' ' . $mvc->getVerbal($rec, 'productDataOn') . ')</span>';
        }

        $row->date = dt::mysql2verbal($rec->date ?: $rec->createdOn, 'd.m.Y');
        $row->ESHOP_LINKS = self::renderEshopLinks($rec->productId);
        $unitWeight = $mvc->getVerbal($rec, 'unitWeight');
        $row->unitWeightCaption = !empty($rec->unitWeight) ? $labels['unitWeight'] : '';
        $row->unitWeightValue = !empty($rec->unitWeight) ? $unitWeight . ' g' : '';

        $row->INTENDED_USE_SECTION = self::renderSection($mvc, $rec, $labels['intendedUseSection'], array(
            'intendedUse' => array('caption' => $labels['intendedUse'], 'richtext' => true),
            'useRestrictions' => array('caption' => $labels['useRestrictions'], 'richtext' => true),
        ));

        // Опциите на енума са с преводи - вербализират се на езика на документа
        core_Lg::push($language);
        $foodContactStatus = $mvc->getVerbal($rec, 'foodContactStatus');
        core_Lg::pop();

        $row->COMPLIANCE_SECTION = self::renderSection($mvc, $rec, $labels['complianceSection'], array(
            'foodContactStatus' => array('caption' => $labels['foodContact'], 'value' => $foodContactStatus, 'html' => true),
            'countriesOfUse' => array('caption' => $labels['countriesOfUse']),
            'recycledContent' => array('caption' => $labels['recycledContent']),
            'recyclability' => array('caption' => $labels['recyclability']),
            'complianceDeclarationIds' => array(
                'caption' => $labels['declarations'],
                'value' => !empty($rec->declarationSnapshot) ? $rec->declarationSnapshot : self::renderComplianceDeclarations($rec, $language),
                'html' => true,
            ),
            'certifications' => array('caption' => $labels['certifications'], 'richtext' => true),
            'complianceDocuments' => array('caption' => $labels['relatedDocuments'], 'richtext' => true),
        ));

        $row->STORAGE_SECTION = self::renderSection($mvc, $rec, $labels['storageSection'], array(
            'storageConditions' => array('caption' => $labels['storageConditions'], 'richtext' => true),
        ));

        $row->PACKAGING_SECTION = self::renderSection($mvc, $rec, $labels['packagingSection'], array(
            'packagingDescription' => array('caption' => $labels['packagingDescription'], 'richtext' => true),
        ));

        $row->NOTES_SECTION = self::renderSection($mvc, $rec, $labels['notesSection'], array(
            'tolerances' => array('caption' => $labels['tolerances'], 'richtext' => true),
            'notes' => array('caption' => $labels['notes'], 'richtext' => true),
            'disclaimer' => array('caption' => $labels['disclaimer'], 'richtext' => true),
        ));

        if (isset($fields['-list'])) {
            $row->productId = cat_Products::getHyperlink($rec->productId, true);
        }
    }


    /**
     * Подготовка на филтъра в списъчния изглед
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->setField('productId', 'input,placeholder=Всички,silent,formOrder=1100');
        $data->listFilter->showFields .= ',productId';
        $data->listFilter->input();

        $filterRec = $data->listFilter->rec;
        if (!empty($filterRec->productId)) {
            $data->query->where(array("#productId = [#1#]", $filterRec->productId));
        }
    }


    /**
     * Добавя стиловете на документа в единичния изглед и при печат
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tpl->push('dec/tpl/css/tds.css', 'CSS');
    }


    /**
     * Предложения за подписващ от страна на производителя
     */
    protected static function getSignerSuggestions()
    {
        $options = array();
        $currentUserName = trim((string) core_Users::getCurrent('names'));
        if ($currentUserName !== '') {
            $options[$currentUserName] = $currentUserName;
        }

        $groupId = crm_Groups::fetchField("#sysId = 'managers'", 'id');
        if ($groupId) {
            $query = crm_Persons::getQuery();
            plg_ExpandInput::applyExtendedInputSearch('crm_Persons', $query, $groupId);
            while ($personRec = $query->fetch()) {
                $name = crm_Persons::getVerbal($personRec, 'name');
                $options[$name] = $name;
            }
        }

        return $options;
    }


    /**
     * Създаване само към артикул, чийто сингъл потребителят може да види
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'activate' && empty($rec->id)) {
            $requiredRoles = 'no_one';
        }

        if ($action == 'add' && $requiredRoles != 'no_one') {
            $productRec = !empty($rec->originId) ? self::getProductFromOrigin($rec->originId) : null;
            if (!$productRec || !cat_Products::haveRightFor('single', $productRec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Данните на документа за doc_DocumentIntf
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $productTitle = !empty($rec->productData) ? (((object) $rec->productData)->name ?? '') : cat_Products::getTitleById($rec->productId);

        $row->title = "TDS {$rec->documentNumber} - {$productTitle}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;

        return $row;
    }


    /**
     * Документът не може да започва нова нишка в папка
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }


    /**
     * Документът се добавя само в нишката на артикула ориджин
     */
    public static function canAddToThread($threadId)
    {
        $originId = Request::get('originId', 'int');
        if (!$originId) {
            return false;
        }

        $productRec = self::getProductFromOrigin($originId);

        return $productRec && (int) $productRec->threadId === (int) $threadId;
    }


    /**
     * Текст по подразбиране на имейла към документа
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля, запознайте се с нашата техническа спецификация') . ': #[#handle#]');
        $tpl->append($handle, 'handle');

        return $tpl->getContent();
    }


    /**
     * Зарежда шаблоните на документа в doc_TplManager
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array(
            'name' => 'Техническа спецификация',
            'content' => 'dec/tpl/TechnicalDataSheetBg.shtml',
            'lang' => 'bg',
        );
        $tplArr[] = array(
            'name' => 'Technical data sheet',
            'content' => 'dec/tpl/TechnicalDataSheet.shtml',
            'lang' => 'en',
        );

        return doc_TplManager::addOnce($this, $tplArr);
    }


    /**
     * Шаблон по подразбиране - български само в папка на контрагент от България или без държава
     */
    public function getDefaultTemplate_($rec)
    {
        $isBg = false;
        if (!empty($rec->folderId)) {
            $Cover = doc_Folders::getCover($rec->folderId);
            if ($Cover->isInstanceOf('crm_Companies') || $Cover->isInstanceOf('crm_Persons')) {
                $countryId = $Cover->fetchField('country');
                $isBg = empty($countryId) || $countryId == drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
            }
        }

        $options = $isBg ? $this->getTemplateBgOptions() : $this->getTemplateEnOptions();

        return countR($options) ? key($options) : null;
    }


    /**
     * Езикът на записан или нов документ
     */
    protected static function getRecordLanguage($rec)
    {
        return !empty($rec->template) ? doc_TplManager::fetchField($rec->template, 'lang') : 'en';
    }


    /**
     * Надписите в динамичните секции на шаблона
     */
    protected static function getLabels($language)
    {
        core_Lg::push($language);
        $labels = array(
            'documentTitle' => mb_strtoupper(tr('Техническа спецификация')),
            'documentSubtitle' => tr('Технически данни за продукта'),
            'unitWeight' => tr('Тегло на единично изделие'),
            'intendedUseSection' => tr('Предназначение и приложение'),
            'intendedUse' => tr('Предназначение'),
            'useRestrictions' => tr('Ограничения за употреба'),
            'complianceSection' => tr('Съответствие и регулаторна информация'),
            'foodContact' => tr('Контакт с храни'),
            'countriesOfUse' => tr('Държави на употреба'),
            'recycledContent' => tr('Рециклирано съдържание'),
            'recyclability' => tr('Рециклируемост'),
            'declarations' => tr('Декларации при поискване'),
            'certifications' => tr('Сертификати'),
            'relatedDocuments' => tr('Свързани документи||Related documents'),
            'storageSection' => tr('Съхранение и употреба'),
            'storageConditions' => tr('Условия за съхранение'),
            'packagingSection' => tr('Опаковка||Packaging'),
            'packagingDescription' => tr('Описание на опаковката'),
            'notesSection' => tr('Допълнителни бележки'),
            'tolerances' => tr('Технически допуски'),
            'notes' => tr('Бележки'),
            'disclaimer' => tr('Отказ от отговорност'),
        );
        core_Lg::pop();

        return $labels;
    }


    /**
     * Линкове към страниците в е-магазина, от които артикулът може да се купи
     */
    protected static function renderEshopLinks($productId)
    {
        if (!core_Packs::isInstalled('eshop')) {

            return null;
        }

        $dQuery = eshop_ProductDetails::getQuery();
        $dQuery->EXT('domainId', 'eshop_Products', 'externalName=domainId,externalKey=eshopProductId');
        $dQuery->EXT('eshopProductState', 'eshop_Products', 'externalName=state,externalKey=eshopProductId');
        $dQuery->EXT('saleState', 'eshop_Products', 'externalName=saleState,externalKey=eshopProductId');
        $dQuery->where(array("#productId = [#1#] AND #state = 'active' AND #eshopProductState = 'active' AND #saleState != 'closed'", (int) $productId));
        $dQuery->in('action', array('buy', 'both'));
        $dQuery->orderBy('id', 'ASC');

        // По един линк на домейн - езиковите версии на един хост са отделни домейни
        $links = array();
        while ($dRec = $dQuery->fetch()) {
            if (isset($links[$dRec->domainId])) {
                continue;
            }

            $domainRec = cms_Domains::fetch($dRec->domainId);
            if (!is_object($domainRec) || ($domainRec->state ?? null) != 'active') {
                continue;
            }

            $url = eshop_Products::getUrl($dRec->eshopProductId, true);
            if (empty($url)) {
                continue;
            }

            $links[$dRec->domainId] = (object) array('host' => $domainRec->domain, 'url' => $url);
        }

        $res = array();
        foreach ($links as $link) {
            $pushDomain = ($link->host != 'localhost');
            if ($pushDomain) {
                Mode::push('BGERP_CURRENT_DOMAIN', $link->host);
            }

            $url = toUrl($link->url, 'absolute');
            if ($pushDomain) {
                Mode::pop('BGERP_CURRENT_DOMAIN');
            }

            $res[] = ht::createLink(type_Varchar::escape(rawurldecode($url)), $url, false, array('target' => '_blank'));
        }

        return implode('<br>', $res);
    }


    /**
     * Рендира списък на декларациите, издавани при поискване
     */
    protected static function renderComplianceDeclarations($rec, $language)
    {
        $statementIds = keylist::toArray($rec->complianceDeclarationIds ?? null);
        $items = '';
        foreach ($statementIds as $statementId) {
            $sRec = dec_Statements::fetch($statementId);
            if (!$sRec) {
                continue;
            }

            // Без кратък текст се показва само заглавието
            $title = dec_Statements::getVerbal($sRec, 'title');
            $shortText = !empty($sRec->shortText) ? '<span>' . dec_Statements::getVerbal($sRec, 'shortText') . '</span>' : '';
            $items .= "<li class='tdsComplianceItem'><strong>{$title}</strong>{$shortText}</li>";
        }

        if (!$items) {

            return '';
        }

        core_Lg::push($language);
        $requestText = tr('Продуктът отговаря на приложимите изисквания, посочени по-долу. При поискване производителят издава отделни декларации за съответствие за конкретния продукт.||The product complies with the applicable requirements listed below. Upon request, the manufacturer issues separate declarations of compliance for the specific product.');
        core_Lg::pop();

        return "<div class='tdsComplianceRequest'>" . ht::escapeAttr($requestText) . "</div><ul class='tdsComplianceList'>{$items}</ul>";
    }


    /**
     * Стойности по подразбиране за формата от драйвера на артикула
     */
    protected static function getProductDefaults($productRec, $language)
    {
        $defaults = (object) self::getDriverTdsData($productRec, $language);

        if (empty($defaults->packagingDescription)) {
            $defaults->packagingDescription = self::getPackagingDescription($productRec, $language);
        }

        // Стандартните текстове са на езика на документа
        core_Lg::push($language);
        if (empty($defaults->tolerances)) {
            $defaults->tolerances = tr('Посочените стойности за тегло, размери, дебелина, цвят и други технически характеристики са номинални. Допустимите отклонения се определят от договорените условия за конкретната поръчка и приложимите Общи условия на производителя. Когато конкретни допуски не са изрично уговорени, се прилагат обичайните за съответния продукт и производствен процес отраслови допуски.||The stated values for weight, dimensions, thickness, colour and other technical characteristics are nominal. Permissible tolerances are governed by the terms agreed for the specific order and the manufacturer\'s applicable General Terms and Conditions. Where no specific tolerances have been expressly agreed, customary industry tolerances for the relevant product and manufacturing process apply.');
        }
        $defaults->disclaimer = tr('Тази техническа спецификация обобщава характеристиките на продукта и приложимите регулаторни области. Тя не представлява декларация за съответствие или протокол от изпитване; конкретни документи се издават при поискване, когато са приложими.||This technical data sheet summarises the product characteristics and applicable regulatory areas. It is not a declaration of compliance or a test report; specific documents are issued on request where applicable.');
        core_Lg::pop();

        return $defaults;
    }


    /**
     * Данните за TDS от драйвера на артикула на езика на документа
     */
    protected static function getDriverTdsData($productRec, $language)
    {
        $Driver = cat_Products::getDriver($productRec);
        if (!$Driver) {

            return array();
        }

        core_Lg::push($language);
        $data = (array) $Driver->getTdsData($productRec, $language);
        core_Lg::pop();

        return $data;
    }


    /**
     * Описание на опаковката от активните опаковки на артикула
     */
    protected static function getPackagingDescription($productRec, $language)
    {
        $query = cat_products_Packagings::getQuery();
        $query->where(array("#productId = [#1#]", $productRec->id));
        $query->where("#state != 'closed'");
        $query->EXT('packagingType', 'cat_UoM', 'externalName=type,externalKey=packagingId');
        $query->where("#packagingType = 'packaging'");
        $query->orderBy('isBase=DESC');
        $query->orderBy('quantity=ASC');
        $query->show('packagingId,quantity');

        $rows = array();
        core_Lg::push($language);
        $baseUnit = cat_UoM::getShortName($productRec->measureId);
        $doubleType = core_Type::getByName('double(smartRound)');
        while ($packRec = $query->fetch()) {
            $packaging = cat_UoM::getVerbal($packRec->packagingId, 'name');
            $quantity = html_entity_decode($doubleType->toVerbal($packRec->quantity), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $quantity = str_replace("\xC2\xA0", ' ', $quantity);
            $rows[] = "{$packaging} - {$quantity} {$baseUnit}";
        }
        core_Lg::pop();

        return implode("\n", array_unique($rows));
    }


    /**
     * Артикулът от ориджина, ако е валиден
     */
    protected static function getProductFromOrigin($originId)
    {
        try {
            $origin = doc_Containers::getDocument($originId);
            if (!$origin->isInstanceOf('cat_Products')) {
                return null;
            }

            $productRec = $origin->fetch();

            return cat_Products::getDriver($productRec) ? $productRec : null;
        } catch (core_exception_Expect $e) {
            return null;
        }
    }


    /**
     * Записва в лога опресняването на данните на артикула
     */
    protected static function on_AfterSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if (!empty($rec->_refreshProductData)) {
            $mvc->logWrite('Опресняване на данните на артикула', $rec->id);
        }
    }


    /**
     * Данните на артикула - от снимката, а без нея на живо
     */
    protected static function getProductData($rec)
    {
        if (!empty($rec->productData)) {

            return (object) $rec->productData;
        }

        return self::createProductSnapshot($rec->productId, self::getRecordLanguage($rec));
    }


    /**
     * Дали артикулът или производителят са променени след снимката
     */
    protected static function isProductDataChanged($rec)
    {
        if (empty($rec->productData)) {

            return false;
        }

        // Артикулът е редактиран след снимката, както в cat_ProductTplCache
        $productData = (object) $rec->productData;
        if (!empty($rec->productDataOn) && cat_Products::fetchField($rec->productId, 'modifiedOn') > $rec->productDataOn) {

            return true;
        }

        return ($productData->manufacturer ?? null) != self::getManufacturer(self::getRecordLanguage($rec));
    }


    /**
     * Моментна снимка на данните на артикула
     */
    protected static function createProductSnapshot($productId, $lang)
    {
        $Products = cls::get('cat_Products');
        expect($productRec = $Products->fetchRec($productId));
        expect($Driver = $Products->getDriver($productRec));

        core_Lg::push($lang);
        $name = cat_Products::getDisplayName($productRec);
        $descriptionData = cat_Products::prepareDescription($productRec->id, 'public');
        $descriptionData->renderContext = 'tds';
        $descriptionData->Embedder = $Products;
        $description = $Driver->renderProductDescription($descriptionData);
        $description = $description instanceof core_ET ? $description->getContent() : $description;
        core_Lg::pop();

        $snapshot = new stdClass();
        $snapshot->name = $name;
        $snapshot->code = self::getProductCode($productRec);
        $snapshot->manufacturer = self::getManufacturer($lang);
        $snapshot->description = self::removeInternalLinks($description);

        return $snapshot;
    }


    /**
     * Кодът на артикула, а без код - Art и ид-то
     */
    protected static function getProductCode($productRec)
    {
        return !empty($productRec->code) ? $productRec->code : 'Art' . $productRec->id;
    }


    /**
     * Рендира секция, ако има попълнени полета
     */
    protected static function renderSection($mvc, $rec, $title, $fields)
    {
        $rows = '';

        foreach ($fields as $name => $field) {
            $value = array_key_exists('value', $field) ? $field['value'] : ($rec->{$name} ?? null);
            if (!isset($value) || trim(strip_tags((string) $value)) === '') {
                continue;
            }

            if (!empty($field['html'])) {
                $value = (string) $value;
            } elseif (!empty($field['richtext'])) {
                $value = $mvc->getVerbal($rec, $name);
                $value = $value instanceof core_ET ? $value->getContent() : $value;
                if ($name == 'packagingDescription') {
                    $value = str_replace(array('&amp;nbsp;', '&nbsp;', "\xC2\xA0"), ' ', $value);
                }
            } else {
                $value = ht::escapeAttr($value);
            }

            $rows .= "<tr><th scope='row'>{$field['caption']}</th><td>{$value}</td></tr>";
        }

        if (!$rows) {
            return '';
        }

        return "<section class='tdsSection'><h2 class='tdsSectionTitle'>{$title}</h2><table class='tdsDataTable'>{$rows}</table></section>";
    }


    /**
     * Маха линковете, предназначени за вътрешна навигация
     */
    protected static function removeInternalLinks($description)
    {
        $description = preg_replace('/<a\b[^>]*class=([\"\'])[^\"\']*(?:productPreviewHolder|linkWithIcon)[^\"\']*\1[^>]*>(.*?)<\/a>/is', '$2', $description);

        return preg_replace('/<p\b[^>]*>\s*(?:Отвори\p{M}*\s+3D\s+изглед|Open\s+3D\s+view)\s*<\/p>/iu', '', $description);
    }


    /**
     * Името на моята фирма
     */
    public static function getManufacturer($language = null, $storedName = null)
    {
        $storedName = trim((string) $storedName);
        if ($storedName !== '' && !($language != 'bg' && preg_match('/\p{Cyrillic}/u', $storedName))) {
            return $storedName;
        }

        $name = (string) (crm_Companies::fetchOwnCompany()->company ?? '');
        if ($language != 'bg') {
            core_Lg::push('en');
            $name = transliterate(tr($name));
            core_Lg::pop();
        }

        return $name;
    }
}
