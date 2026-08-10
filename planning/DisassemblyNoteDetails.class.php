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
    public $loadList = 'plg_RowTools2, plg_Created, doc_plg_DetailRevisions, planning_Wrapper, plg_Sorting, plg_SaveAndNew, cat_plg_LogPackUsage, plg_PrevAndNext, plg_AlignDecimals2, cat_plg_ShowCodes';


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
    public $listFields = 'tools=№,productId=Артикул,packagingId,packQuantity=К-во->|*<small>|Въведено|*</small>,quantityFromBom=К-во->|*<small>|Рецепта|*</small>,costPercent=% (сб-ст)->|*<small>|Текущ|*</small>,percentFromBom=% (сб-ст)->|*<small>|Рецепта|*</small>,storeId=Склад';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'quantityFromBom,percentFromBom';


    /**
     * Полета, които при клониране да не се пренасят. Списъкът на
     * deals_ManifactureDetail се преповтаря, защото свойството се препокрива
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

        // Въвежда се само при ръчно разпределяне (@see on_AfterPrepareEditForm)
        $this->FLD('costPercent', 'percent(min=0,max=1,allowEmpty)', 'caption=% от себестойността,input=none,tdClass=accCell,hint=Каква част от себестойността на вложения артикул се пада на този ред');

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

        if (empty($rec->id)) {
            $form->setFieldType('packQuantity', 'double(Min=0)');
        } else {
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

        // Ръчен % се въвежда само при ръчно разпределяне
        if ($rec->type == 'production' && $data->masterRec->allocationBy == 'manual') {
            $form->setField('costPercent', 'input,mandatory');
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
        // В опаковки, за да е сравнимо с въведеното до него. Дели се рекът, за да
        // може plg_AlignDecimals2 да смята по него (@see planning_DirectProductNoteDetails)
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
        $data->totalPercent = 0;
        $data->percentWarning = null;
        if (!countR($data->recs ?? null)) return;

        // За целия протокол, не само за показаните редове (заради страницирането)
        $noteIds = array();
        foreach ($data->recs as $rec) {
            if ($rec->type == 'production') {
                $noteIds[$rec->noteId] = $rec->noteId;
            }
        }

        $warningArr = array();
        foreach ($noteIds as $noteId) {
            $statuses = array();
            $percentsArr = planning_DisassemblyNote::getPercents($noteId, null, $statuses);
            $allocationBy = planning_DisassemblyNote::fetchField($noteId, 'allocationBy');

            if (isset($statuses['warning'])) {
                $warningArr[$noteId] = tr($statuses['warning']);
            }

            foreach ($percentsArr as $id => $obj) {
                if (!array_key_exists($id, $data->rows)) continue;

                // Изчисленото застава на мястото на въведеното, освен при ръчно
                $percentVerbal = cat_DisassemblyBoms::getPercentVerbal($obj->percent, $allocationBy, $statuses['error'] ?? null);
                if (isset($percentVerbal)) {
                    $data->rows[$id]->costPercent = $percentVerbal;
                }

                $data->totalPercent += $obj->percent ?? 0;
            }
        }

        if (countR($warningArr)) {
            $data->percentWarning = implode('<br>', $warningArr);
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

        // Пази вече раздадения пореден номер на всяка ревизионна група (@see
        // doc_plg_DetailRevisions), за да не се пропуска номерацията заради
        // оттеглени редове от историята на един и същ логически ред
        $inputNumByGroup = $productionNumByGroup = array();

        if (countR($data->rows)) {
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];

                if (!is_object($row->tools ?? null)) {
                    $row->tools = new ET('[#TOOLS#]');
                }

                // Иконка по вид на реда - артикулите за влагане/разпад имат
                // отделна иконка от произведените/върнатите артикули
                $icon = ($rec->type == 'input')
                    ? "<span class='green' style='font-weight:bold;' title='" . tr('Влагане') . "'>⇩</span>"
                    : "<span class='red' style='font-weight:bold;' title='" . tr('Връщане') . "'>⇧</span>";

                // Основният вложен артикул се показва отделно, в собствена таблица
                // над таблицата с произведените артикули (@see renderDetail_/MAIN_INPUT_PRODUCT_TABLE),
                // а не в таблицата с (други) артикули за влагане
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

        // Запазваме еднакъв набор и ред на колоните във всички таблици.
        $commonListFields = arr::make($data->listFields, true);

        // Разпределянето се отнася само за произведените артикули
        $inputListFields = $commonListFields;
        unset($inputListFields['quantityFromBom'], $inputListFields['percentFromBom'], $inputListFields['costPercent']);
        $inputListFields['packQuantity'] = 'К-во';

        // Общи CSS класове на колоните - еднаксви и за трите таблици
        $data->listTableMvc = clone $this;
        $data->listTableMvc->appendFieldClass('productId', 'tdClass', 'disassemblyProductColumn');
        $data->listTableMvc->appendFieldClass('packQuantity', 'tdClass', 'productionQuantityColumn');
        $data->listTableMvc->FNC('tools', 'int', 'tdClass=rowNumColumn');

        // Мини-таблица с ОСНОВНИЯ артикул за разпад - показва се отделно, над
        // таблицата с произведените артикули (виж MAIN_INPUT_PRODUCT_TABLE в
        // шаблона), не заедно с (евентуални) други артикули за влагане
        if (countR($data->mainInputArr)) {
            $data->listFields['productId'] = 'Артикули за разпад|* ';
            $mData = clone $data;
            $mData->listTableMvc = clone $data->listTableMvc;
            $mData->listFields = $inputListFields;
            $mData->listFields['productId'] = 'Артикули за разпад|* ';
            $mData->rows = $data->mainInputArr;
            $mData->recs = array_intersect_key($mData->recs, $mData->rows);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$mData));
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
            $iData->listFields = $inputListFields;
            $iData->listFields['productId'] = 'Артикули за разпад|* ';
            $iData->rows = $data->inputArr;
            $iData->recs = array_intersect_key($iData->recs, $iData->rows);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$iData));
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

        // Подравняване на десетичните (@see planning_DirectProductNoteDetails)
        plg_AlignDecimals2::alignDecimals($this, $pData->recs, $pData->rows);

        // Празните колони се махат ръчно - рендира се директно през core_TableView
        $pData->listFields = core_TableView::filterEmptyColumns($pData->rows, $pData->listFields, arr::make($this->hideListFieldsIfEmpty, true));

        $productionTable = cls::get('core_TableView', array('mvc' => $pData->listTableMvc));
        $productionTable->tableClass = 'listTable disassemblyNoteTable';
        $detailsProduction = $productionTable->get($pData->rows, $pData->listFields);

        // Сборът - зелен при точно 100%, червен при над 100%
        $columns = countR($pData->listFields);
        $totalPercentVerbal = core_Type::getByName('percent')->toVerbal($data->totalPercent);
        if ($data->totalPercent > 1) {
            $totalPercentVerbal = ht::styleIfNegative($totalPercentVerbal, -1);
        } elseif ($data->totalPercent == 1) {
            $totalPercentVerbal = "<span style='color:green'>{$totalPercentVerbal}</span>";
        }
        $detailsProduction->append(tr("|*<tr style='background-color:#eee'><td colspan='{$columns}' style='text-align:right;'>|Общо|*: <b>{$totalPercentVerbal}</b></td></tr>"), 'ROW_AFTER');

        // Кои артикули остават без дял - най-горе, над таблиците
        if (!empty($data->percentWarning)) {
            $tpl->append("<div class='richtext-message richtext-warning'>{$data->percentWarning}</div>", 'percentWarning');
        }

        $tpl->append($detailsProduction, 'PRODUCED_PRODUCTS_TABLE');

        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'production'))) {
            if(!Mode::isReadOnly()){
                $tpl->append(ht::createBtn('Произведен артикул', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/door_in.png', 'title' => 'Добавяне на произведен артикул')), 'PRODUCED_PRODUCTS_TABLE');
            }
        }

        $tpl->push('planning/js/DisassemblyNoteTables.js', 'JS');
        jquery_Jquery::run($tpl, 'syncDisassemblyNoteTables();');
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

        // По количество се разпределя само между производни мерки
        if ($form->isSubmitted() && $rec->type == 'production' && planning_DisassemblyNote::fetchField($rec->noteId, 'allocationBy') == 'quantity') {
            if (!planning_DisassemblyNote::areProductionProductsInTheSameUom($rec->noteId, $rec->id ?? null, $rec->productId)) {
                $form->setError('productId', 'Артикулът трябва да е в мярка, производна на мярката на вече добавените произведени артикули|*!');
            }
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
