<?php


/**
 * Клас 'planning_DisassemblyNoteDetails'
 *
 * Детайли на мениджъра на детайлите на протокола за разпад. Два вида редове:
 * - 'input'      - артикулът за разпад (в тази версия - само 1 ред, точно артикула
 *                  от Заданието за разпад)
 * - 'production' - произведените от разпада артикули (могат да са няколко)
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_DisassemblyNoteDetails extends deals_ManifactureDetail
{

    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за разпад';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, doc_plg_DetailRevisions, planning_Wrapper, plg_Sorting, plg_SaveAndNew, cat_plg_LogPackUsage, plg_PrevAndNext, plg_AlignDecimals2, cat_plg_ShowCodes, cat_plg_DisassemblyDocDetail';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,production,store';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,production,store';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,production';


    /**
     * Може ли да се импортират цени
     */
    public $allowPriceImport = false;


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=№,productId=Артикул,packagingId,packQuantity=К-во->|*<small>|Въведено|*</small>,quantityFromBom=К-во->|*<small>|Рецепта|*</small>,costPercent=% (сб-ст),storeId=Склад';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'quantityFromBom';


    /**
     * Поле с артикула - за подредбата на бутоните напред/назад (@see cat_plg_ShowCodes)
     */
    public $productFieldName = 'productId';


    /**
     * Полета, които при клониране да не се пренасят. Списъкът на
     * deals_ManifactureDetail се преповтаря, защото свойството се препокрива.
     * За `costPercent` решава режимът (@see on_BeforeSaveClonedDetail)
     */
    public $fieldsNotToClone = 'createdBy,createdOn,requestedQuantity,contoPercent';


    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Разпад';


    /**
     * В кои състояния на мастъра може да се редактира детайла
     */
    protected $allowedInMasterStates = array('draft', 'pending', 'active');


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_DisassemblyNote)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('type', 'enum(input=Влагане,production=Произвеждане)', 'caption=Действие,silent,input=hidden');
        $this->FLD('isMainInput', 'enum(no=Не,yes=Да)', 'caption=Основен артикул за влагане,input=none,notNull,value=no');
        parent::setDetailFields($this);

        // Снимка от рецептата, само за сравнение (@see planning_DisassemblyNote::transferBomDetails)
        $this->FLD('quantityFromBom', 'double', 'caption=От рецепта,input=none,tdClass=noteBomCol aright');
        $this->FLD('percentFromBom', 'percent(decimals=2)', 'caption=% от рецепта,input=none,tdClass=noteBomCol aright');

        // Снимка на процента, с който документът е контиран - само информативно
        $this->FLD('contoPercent', 'percent(decimals=2)', 'caption=Контиран %,input=none,column=none');

        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty,mandatory)', 'caption=Склад,input=none,tdClass=custom-field nowrap', array('thAttr' => array('style' => 'width:160px')));
        $this->setField('packagingId', "tdClass=small-field");
        $this->setDbIndex('productId');
        $this->setDbIndex('noteId,type');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $data->singleTitle = ($rec->type == 'input') ? 'артикул за разпад' : 'произведен артикул';
        $data->defaultMeta = ($rec->type == 'input') ? 'canConvert,canStore' : 'canManifacture';
        $data->defaultNotHaveMeta = 'generic';
        if($rec->type == 'input'){
            $form->setFieldType('packQuantity', 'double(Min=0)');
        }

        if (!empty($rec->id)) {
            $form->setReadOnly('productId');
        }

        $jobRec = planning_DisassemblyNote::getJobRec($rec->noteId);
        if ($rec->type == 'input') {

            // В тази версия само 1 артикул за влагане - точно този от Заданието
            if (is_object($jobRec)) {
                $form->setFieldTypeParams('productId', array('onlyIn' => array($jobRec->productId)));
                $form->setDefault('productId', $jobRec->productId);
                if (empty($rec->id)) {
                    $form->setReadOnly('productId');
                }
            }
        }

        // Показване на поле за избор на склад при нужда
        if(isset($rec->productId)){
            $productRec = cat_Products::fetch($rec->productId, 'canStore');
            if($productRec->canStore == 'yes'){
                $form->setField('storeId', 'input,mandatory');
                if(isset($data->masterRec->storeId)){
                    $form->setDefault('storeId', $data->masterRec->storeId);
                }
            }
        }
    }


    /**
     * Ръчно въведените проценти се пренасят при клониране - при другите режими
     * се изчисляват наново и записаното няма стойност
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
        if (isset($rec->costPercent) && $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'allocationBy') != 'manual') {
            $rec->costPercent = null;
        }
    }


    /**
     * Определя посоката на партидното движение
     */
    public function getBatchMovementDocument($rec)
    {
        return $rec->type == 'production' ? 'in' : 'out';
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec)) {
            $rec = $mvc->fetchRec($rec);
            if ($rec->isMainInput == 'yes') {
                $requiredRoles = 'no_one';
            } elseif($rec->noteId) {
                $masterRec = $mvc->Master->fetch($rec->noteId, 'state');
                if($masterRec->state != 'draft') {
                    if($mvc->count("#type = 'production' AND #noteId = '{$rec->noteId}'") == 1) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }

        // В тази версия - само 1 артикул за влагане (точно от Заданието)
        if ($action == 'add' && isset($rec->type) && $rec->type == 'input') {
            if ($mvc->count("#noteId = {$rec->noteId} AND #type = 'input'")) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Преди подготвяне на едит формата
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // В опаковки, за да е сравнимо с въведеното; рекът се дели заради plg_AlignDecimals2
        if (!empty($rec->quantityFromBom) && !empty($rec->quantityInPack)) {
            $rec->quantityFromBom /= $rec->quantityInPack;
            $row->quantityFromBom = $mvc->getFieldType('quantityFromBom')->fromVerbal($rec->quantityFromBom);
        }

        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        } else {
            $productRec = cat_Products::fetch($rec->productId, 'canStore');
            if($productRec->canStore == 'yes'){
                $row->storeId = "<span class='red'>n/a</span>";
            }
        }
    }


    /**
     * При оттеглените версии скриваме повторения артикул, но оставяме
     * рендирания snapshot на партидите, когато такъв съществува.
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $activeGroups = doc_plg_DetailRevisions::groupsWithActiveRow($data->recs);
        $rejectedIds = array();
        foreach ($data->recs as $rec) {
            if ((($rec->state ?? null) == 'rejected') && isset($activeGroups[$rec->revisionRootId ?: $rec->id])) {
                $rejectedIds[$rec->id] = $rec->id;
            }
        }

        if (!countR($rejectedIds) || !core_Packs::isInstalled('batch')) {

            return;
        }

        $idsWithBatches = array();
        Mode::push('showHistoricBatches', true);
        try {
            $query = batch_BatchesInDocuments::getQuery();
            $query->where("#detailClassId = {$mvc->getClassId()}");
            $query->in('detailRecId', $rejectedIds);
            $query->show('detailRecId');
            while ($bRec = $query->fetch()) {
                $idsWithBatches[$bRec->detailRecId] = true;
            }
        } finally {
            Mode::pop('showHistoricBatches');
        }

        foreach ($rejectedIds as $id) {
            if (!isset($idsWithBatches[$id])) {
                $data->rows[$id]->productId = '';
            }
        }
    }


    /**
     * След подготовката на редовете - процентите зависят от всички произведени
     * редове наведнъж, затова не се смятат в recToVerbal
     */
    protected static function on_AfterPrepareListRows($mvc, &$res, &$data)
    {
        if (!countR($data->recs ?? null)) return;

        // Живите проценти ги е сметнал плъгинът - тук е само специфичното за протокола
        foreach ($data->recs as $id => $rec) {
            if ($rec->type != 'production' || !array_key_exists($id, $data->rows)) continue;

            // Контираният ред показва процента от журнала, а не живо изчисления
            $percent = $data->percentsArr[$id]->percent ?? null;
            if (isset($rec->contoPercent)) {
                $data->rows[$id]->costPercent = $mvc->getVerbal($rec, 'contoPercent');

                // Сборът отдолу е на показаното, не на живо изчисленото
                $data->totalPercent += $rec->contoPercent - ($percent ?? 0);
            }

            if (isset($rec->percentFromBom)) {
                $percentFromBom = $mvc->getFieldType('percentFromBom')->toVerbal($rec->percentFromBom);
                $data->rows[$id]->costPercent = ht::createHint($data->rows[$id]->costPercent, "По рецепта|*: {$percentFromBom}", 'notice', false);
            }

            // Живото се разминава с контираното - журналът ще настигне при реконтирането
            if (isset($rec->contoPercent) && isset($percent) && abs($rec->contoPercent - $percent) > 0.0001) {
                $percentVerbal = $mvc->getFieldType('contoPercent')->toVerbal($percent);
                $data->rows[$id]->costPercent = ht::createHint($data->rows[$id]->costPercent, "При реконтиране ще стане|*: {$percentVerbal}", 'warning', false);
            }
        }
    }


    /**
     * След подготовка на детайлите - разделяне на редовете по вид (за 2-те таблици)
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
        $data->inputArr = $data->productionArr = $data->mainInputArr = array();
        $countInput = $countProduction = 1;
        $Int = cls::get('type_Int');

        // Номер на всяка ревизионна група (@see doc_plg_DetailRevisions), за да не
        // се пропуска номерацията заради оттеглените редове на същия логически ред
        $inputNumByGroup = $productionNumByGroup = array();

        if (countR($data->rows)) {
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];

                if (!is_object($row->tools ?? null)) {
                    $row->tools = new ET('[#TOOLS#]');
                }

                // Иконка по вид на реда
                $icon = ($rec->type == 'input')
                    ? "<span class='green' style='font-weight:bold;' title='" . tr('Влагане') . "'>⇩</span>"
                    : "<span class='red' style='font-weight:bold;' title='" . tr('Връщане') . "'>⇧</span>";

                // Основният вложен артикул е в собствена таблица над произведените
                // (@see renderDetail_/MAIN_INPUT_PRODUCT_TABLE)
                if ($rec->type == 'input' && $rec->isMainInput == 'yes') {
                    $row->tools->append("{$icon} " . $Int->toVerbal(1), 'TOOLS');
                    $data->mainInputArr[$id] = $row;
                    continue;
                }

                $groupKey = $rec->revisionRootId ?: $rec->id;

                if ($rec->type == 'input') {
                    if (!isset($inputNumByGroup[$groupKey])) {
                        $inputNumByGroup[$groupKey] = $countInput++;
                    }
                    $num = $Int->toVerbal($inputNumByGroup[$groupKey]);
                    $data->inputArr[$id] = $row;
                } else {
                    if (!isset($productionNumByGroup[$groupKey])) {
                        $productionNumByGroup[$groupKey] = $countProduction++;
                    }
                    $num = $Int->toVerbal($productionNumByGroup[$groupKey]);
                    $data->productionArr[$id] = $row;
                }

                $row->tools->append("{$icon} {$num}", 'TOOLS');
            }
        }
    }


    /**
     * Променяме рендирането на детайлите - 2 отделни таблици
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new ET('');

        if (Mode::is('printing')) {
            unset($data->listFields['tools']);
        }

        // Върху всички редове, за да са еднакви знаците и в трите таблици
        plg_AlignDecimals2::alignDecimals($this, $data->recs, $data->rows);

        // Трите таблици са с еднакви колони - JS-ът го изисква (@see
        // planning/js/DisassemblyNoteTables.js). Празните се махат ръчно, защото
        // се рендира директно през core_TableView
        $commonListFields = arr::make($data->listFields, true);
        $commonListFields = core_TableView::filterEmptyColumns($data->rows, $commonListFields, arr::make($this->hideListFieldsIfEmpty, true));

        // Общи CSS класове на колоните - еднаксви и за трите таблици
        $data->listTableMvc = clone $this;
        $data->listTableMvc->appendFieldClass('productId', 'tdClass', 'disassemblyProductColumn');
        $data->listTableMvc->appendFieldClass('packQuantity', 'tdClass', 'productionQuantityColumn');
        $data->listTableMvc->FNC('tools', 'int', 'tdClass=rowNumColumn');

        // Колоната с инструментите я слага plg_RowTools2 само в таблица с редове
        $rowToolsFld = null;

        // Мини-таблица с ОСНОВНИЯ артикул за разпад - отделно, над произведените
        if (countR($data->mainInputArr)) {
            $data->listFields['productId'] = 'Артикули за разпад|* ';
            $mData = clone $data;
            $mData->listTableMvc = clone $data->listTableMvc;
            $mData->listFields = $commonListFields;
            $mData->listFields['productId'] = 'Артикули за разпад|* ';
            $mData->rows = $data->mainInputArr;
            $mData->recs = array_intersect_key($mData->recs, $mData->rows);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$mData));
            $rowToolsFld = $mData->listFields['_rowTools'] ?? $rowToolsFld;
            $mData->listTableMvc->appendFieldClass('code', 'tdClass', 'productionCodeColumn');
            $mData->listTableMvc->appendFieldClass('code', 'tdClass', 'rightCol');
            $mData->listFields['storeId'] = 'От склад';

            $mainInputTable = cls::get('core_TableView', array('mvc' => $mData->listTableMvc));
            $mainInputTable->tableClass = 'listTable disassemblyNoteTable';
            $detailsMainInput = $mainInputTable->get($mData->rows, $mData->listFields);
            $tpl->append($detailsMainInput, 'MAIN_INPUT_PRODUCT_TABLE');
        }

        // Таблица с (други) артикули за разпад (влагане) - само ако има такива
        if (countR($data->inputArr)) {
            $data->listFields['productId'] = 'Артикули за разпад|* ';
            $iData = clone $data;
            $iData->listTableMvc = clone $data->listTableMvc;
            $iData->listFields = $commonListFields;
            $iData->listFields['productId'] = 'Артикули за разпад|* ';
            $iData->rows = $data->inputArr;
            $iData->recs = array_intersect_key($iData->recs, $iData->rows);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$iData));
            $rowToolsFld = $iData->listFields['_rowTools'] ?? $rowToolsFld;
            $iData->listTableMvc->appendFieldClass('code', 'tdClass', 'productionCodeColumn rightCol');
            $iData->listFields['storeId'] = 'От склад';

            $inputTable = cls::get('core_TableView', array('mvc' => $iData->listTableMvc));
            $inputTable->tableClass = 'listTable disassemblyNoteTable';
            $detailsInput = $inputTable->get($iData->rows, $iData->listFields);
            $tpl->append($detailsInput, 'INPUT_PRODUCTS_TABLE');
        }

        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'input'))) {
            $tpl->append(ht::createBtn('Артикули за разпад', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на артикула за разпад')), 'INPUT_PRODUCTS_TABLE');
        }

        // Таблица с произведените от разпада артикули
        $data->listFields['productId'] = 'Произведени артикули|* ';
        $pData = clone $data;
        $pData->listTableMvc = clone $data->listTableMvc;
        $pData->listFields = $commonListFields;
        $pData->listFields['productId'] = 'Произведени артикули|* ';
        $pData->rows = $data->productionArr;
        $pData->recs = array_intersect_key($pData->recs, $pData->rows);
        $pData->listFields['storeId'] = 'В склад';

        $this->invoke('BeforeRenderListTable', array(&$tpl, &$pData));
        $pData->listTableMvc->appendFieldClass('code', 'tdClass', 'productionCodeColumn rightCol');

        // Празната таблица я няма - слага се празна, за да са с еднакъв брой колони
        if (isset($rowToolsFld) && !isset($pData->listFields['_rowTools'])) {
            $pData->listFields = arr::combine(array('_rowTools' => $rowToolsFld), $pData->listFields);
        }

        $productionTable = cls::get('core_TableView', array('mvc' => $pData->listTableMvc));
        $productionTable->tableClass = 'listTable disassemblyNoteTable';
        $detailsProduction = $productionTable->get($pData->rows, $pData->listFields);

        // Сборът - зелен при точно 100%, червен при над 100%
        cat_plg_DisassemblyDocDetail::appendTotalRow($detailsProduction, $data->totalPercent, countR($pData->listFields));

        // Кои артикули остават без дял - само преди контиране, после процентите
        // са снимка от журнала
        $masterState = $data->masterData->rec->state ?? null;
        if (!empty($data->percentWarning) && in_array($masterState, array('draft', 'pending'))) {
            $tpl->append("<div class='richtext-message richtext-warning disassemblyPercentWarning'>{$data->percentWarning}</div>", 'percentWarning');
        }

        $tpl->append($detailsProduction, 'PRODUCED_PRODUCTS_TABLE');

        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'production'))) {
            if(!Mode::isReadOnly()){
                $tpl->append(ht::createBtn('Произвеждане', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/door_in.png', 'title' => 'Добавяне на произведен артикул')), 'PRODUCED_PRODUCTS_TABLE');
            }
        }

        cat_plg_DisassemblyDocDetail::appendAllocateBtn($tpl, $this->Master, $data->masterId, 'PRODUCED_PRODUCTS_TABLE', 'margin-top:5px;margin-bottom:15px;');

        $tpl->push('planning/js/DisassemblyNoteTables.js', 'JS');
        jquery_Jquery::run($tpl, 'render_syncDisassemblyNoteTables();');
        jquery_Jquery::runAfterAjax($tpl, 'syncDisassemblyNoteTables');

        return $tpl;
    }


    /**
     * да Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        if ($rec->type == 'input' && isset($rec->productId) && !empty($rec->storeId)) {
            $masterRec = planning_DisassemblyNote::fetch($rec->noteId, 'deadline,valior');
            $deliveryDate = (!empty($masterRec->deadline)) ? $masterRec->deadline : $masterRec->valior;
            $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId ?? null, $rec->packQuantity ?? null, $rec->storeId, $deliveryDate);
            $form->info = $storeInfo->formInfo;
        }
    }


    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        $res->operation = array();
        if($rec->type == 'input') {
            $res->operation['out'] = $rec->storeId;
        } else {
            $res->operation['in'] = $rec->storeId;
        }
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if($data->masterRec->state == 'active'){
            $data->form->toolbar->setWarning('save', 'Протоколът е вече контиран, при запис ще бъде реконтиран|*!');
            if ($data->form->toolbar->haveButton('saveAndNew')) {
                $data->form->toolbar->setWarning('saveAndNew', 'Протоколът е вече контиран, при запис ще бъде реконтиран|*!');
            }
        }
    }
}
