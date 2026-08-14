<?php


/**
 * Мениджър на отчети за хоризонти на заданията
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Хоризонти на заданията
 */
class store_reports_JobsHorizons extends frame2_driver_TableData
{

    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'quantity,code';


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields;


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,store,debug';

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_PrevAndNext';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'conditionQuantity';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'date,stores,groups';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('date', 'date', 'caption=Към дата,after=title,silent,single=none');

        $fieldset->FLD('stores', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholderType=all,single=none,after=date');

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=stores,mandatory,silent,single=none');

        //Подредба на резултатите
        $fieldset->FLD('order', 'enum(desc=Низходящо, asc=Възходящо)', 'caption=Подреждане на резултата->Ред,maxRadio=2,after=groups,single=none');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('order', 'desc');

    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {

        $rec = $form->rec;

    }

    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {

        $recs = $storesRecsArr = $storesArr = array();

        // Подготвяме заявката за извличането на записите от store_Products

        $sQuery = store_StockPlanning::getQuery();

        $sQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $sQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $sQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        //Филтър по групи артикули
        plg_ExpandInput::applyExtendedInputSearch('cat_Products', $sQuery, $rec->groups ?? null, 'productId');


        if (!empty($rec->stores)) {
            $storesArr = keylist::toArray($rec->stores);
            $sQuery->in('storeId', $storesArr);
            $storesRecsArr = $sQuery->fetchAll();

        } else {
            $storesRecsArr = $sQuery->fetchAll();
        }


        foreach ($storesRecsArr as $sRec) {

            $pRec = cat_Products::fetch($sRec->productId);
            if (!$pRec) {
                continue;
            }

            if (empty($sRec->measureId)) {
                $measureId = $pRec->measureId;
            } else {
                $measureId = $sRec->measureId;
            }

            $id = $sRec->productId;

            $Quantities = store_Products::getQuantities($sRec->productId, $storesArr, $rec->date ?? null);

            $quantity = $Quantities->quantity;
            $reserved = $Quantities->reserved;
            $expected = $Quantities->expected;
            $free = $Quantities->free;

            $documentsReserved = store_StockPlanning::getRecs($sRec->productId, $storesArr, $rec->date ?? null, 'reserved');
            $documentsExpected = store_StockPlanning::getRecs($sRec->productId, $storesArr, $rec->date ?? null, 'expected');

            $code = ($pRec->code) ?: 'Art' . $pRec->productId;

            $recs[$id] = (object)array(
                'productId' => $sRec->productId,
                'measure' => $measureId,
                'quantity' => $quantity,
                'reserved' => $reserved,
                'expected' => $expected,
                'free' => $free,
                'code' => $code,
                'documentsReserved' => $documentsReserved,
                'documentsExpected' => $documentsExpected,

            );

            unset($documentsReserved, $documentsExpected, $Quantities, $code);

        }

        if (!empty($recs)) {

            arr::sortObjects($recs, 'code', $rec->order ?? 'desc', 'stri');
        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === false) {

            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Налично,smartCenter');
            $fld->FLD('reserved', 'double(decimals=2)', 'caption=Количество->Запазено,smartCenter');
            $fld->FLD('expected', 'double(decimals=2)', 'caption=Количество->Очаквано,smartCenter');
            $fld->FLD('free', 'double(decimals=2)', 'caption=Количество->Разполагаемо,smartCenter');

        } else {
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('document', 'varchar', 'caption=Документ,tdClass=centered');
            $fld->FLD('date', 'varchar', 'caption=Падеж,tdClass=centered');
            $fld->FLD('note', 'varchar', 'caption=Поръчка,tdClass=centered');
            $fld->FLD('store', 'varchar', 'caption=Склад,tdClass=centered');
            $fld->FLD('docReservedQuantyti', 'double(decimals=2)', 'caption=Количество->Запазено');
            $fld->FLD('docExpectedQuantyti', 'double(decimals=2)', 'caption=Количество->Очаквано');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'varchar', 'caption=Количество Общо->Налично');
            $fld->FLD('reserved', 'varchar', 'caption=Количество Общо->Запазено');
            $fld->FLD('expected', 'varchar', 'caption=Количество Общо->Очаквано');
            $fld->FLD('free', 'double(decimals=2)', 'caption=Количество Об що->Разполагаемо');

        }


        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Date = cls::get('type_Date');
        $Int = cls::get('type_Int');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 3;

        $row = new stdClass();

        $pRec = cat_Products::fetch($dRec->productId);

        $row->code = (!empty($pRec->code)) ? $pRec->code : "Art{$dRec->productId}";

        $row->productId = cat_Products::getLinkToSingle_($dRec->productId, true);


        $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');

        $row->quantity = $Double->toVerbal($dRec->quantity);
        $row->quantity = ht::styleIfNegative($row->quantity, $dRec->quantity);


        $row->reserved = $Double->toVerbal($dRec->reserved);
        $row->reserved = ht::styleIfNegative($row->reserved, $dRec->reserved);

        $date = !empty($rec->date) ? $rec->date : dt::today();
        $title = 'От кои документи е сформирано количеството';

        $tooltipUrl = toUrl(array('store_Products', 'ShowReservedDocs', 'productId' => $dRec->productId, 'stores' => $rec->stores ?? null, 'replaceField' => "reserved{$dRec->productId}", 'field' => 'reserved', 'date' => $date), 'local');
        $arrowImg = ht::createElement('img', array('height' => 16, 'width' => 16, 'src' => sbf('img/32/info-gray.png', '')));
        $arrow = ht::createElement('span', array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl, 'title' => $title), $arrowImg, true);
        $arrow = "<span class='additionalInfo-holder'><span class='additionalInfo' id='reserved{$dRec->productId}'></span>{$arrow}</span>";

        if ($dRec->reserved) {
            $row->reserved = $arrow . $row->reserved;
        }

        $row->expected = $Double->toVerbal($dRec->expected);
        $row->expected = ht::styleIfNegative($row->expected, $dRec->expected);

        $tooltipUrl = toUrl(array('store_Products', 'ShowReservedDocs', 'productId' => $dRec->productId, 'stores' => $rec->stores ?? null, 'replaceField' => "expected{$dRec->productId}", 'field' => 'expected', 'date' => $date), 'local');
        $arrowImg = ht::createElement('img', array('height' => 16, 'width' => 16, 'src' => sbf('img/32/info-gray.png', '')));
        $arrow = ht::createElement('span', array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl, 'title' => $title), $arrowImg, true);
        $arrow = "<span class='additionalInfo-holder'><span class='additionalInfo' id='expected{$dRec->productId}'></span>{$arrow}</span>";

        if ($dRec->expected) {
            $row->expected = $arrow . $row->expected;
        }

        $row->free = $Double->toVerbal($dRec->free);
        $row->free = ht::styleIfNegative($row->free, $dRec->free);


        if (!empty($dRec->store)) {
            $row->store = store_Stores::getHyperlink($dRec->store);
        } else {
            $row->store = 'Без';
        }


        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        $groupVerb = $storeIdVerb = '';

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN groups--><div>|Наблюдавани групи|*: [#groups#]</div><!--ET_END groups-->
                                        <!--ET_BEGIN date--><div>|Хоризонт|*: [#date#]</div><!--ET_END date-->
                                        <!--ET_BEGIN ariculsData--><div>|Брой артикули|*: [#ariculsData#]</div><!--ET_END ariculsData-->
                                        <!--ET_BEGIN stores--><div>|Складове|*: [#stores#]</div><!--ET_END stores-->
                                    </div>
                                
                                 </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->groups)) {
            $marker = 0;
            foreach (keylist::toArray($data->rec->groups) as $group) {
                $marker++;

                $groupVerb .= cat_Groups::fetch($group)->name;

                if ((countR(keylist::toArray($data->rec->groups))) - $marker != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groups');
        }

        if (isset($data->rec->date)) {

            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->date) . '</b>', 'date');
        }

        if (isset($data->rec->stores)) {

            $marker = 0;
            foreach (type_Keylist::toArray($data->rec->stores) as $store) {
                $marker++;

                $storeIdVerb .= (store_Stores::getTitleById($store));

                if ((countR(type_Keylist::toArray($data->rec->stores))) - $marker != 0) {
                    $storeIdVerb .= ', ';
                }

            }

            $fieldTpl->append('<b>' . $storeIdVerb . '</b>', 'stores');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'stores');
        }

        $data->rec->ariculsData = countR($data->rec->data->recs ?? array());

        if (isset($data->rec->ariculsData)) {
            $fieldTpl->append('<b>' . $data->rec->ariculsData . '</b>', 'ariculsData');
        }

        if (($data->rec->typeOfQuantity ?? null) == 'free') {

            $dateVerb = dt::mysql2verbal($data->rec->date, 'd.m.Y');
            $fieldTpl->append('<b>' . 'Разполагаемо към ' . $dateVerb . '</b>', 'typeOfQuantity');

        } else {

            $dateVerb = dt::mysql2verbal(dt::today(), 'd.m.Y');
            $fieldTpl->append('<b>' . 'Налично към ' . $dateVerb . '</b>', 'typeOfQuantity');

        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }

    /**
     * Връща редовете на CSV файл-а
     *
     * @param stdClass $rec - запис
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return array $recs                - записите за експорт
     */
    public function getExportRecs($rec, $ExportClass)
    {

        expect(cls::haveInterface('export_ExportTypeIntf', $ExportClass));
        $recsToExport = $this->getRecsForExport($rec, $ExportClass);

        $recs = array();
        $markFirst = 0;
        if (is_array($recsToExport)) {
            foreach ($recsToExport as $dRec) {

                foreach ($dRec->documentsReserved ?? array() as $docReserved) {

                    $dCloneRec = clone $dRec;

                    $DocumentRez = cls::get($docReserved->sourceClassId);
                    $docClassName = $DocumentRez->className;
                    $docRec = $docClassName::fetch($docReserved->sourceId);
                    if (!$docRec) {
                        continue;
                    }
                    $dCloneRec->date = $docReserved->date;

                    $dCloneRec->document = $DocumentRez->abbr . $docReserved->sourceId;

                    //Определяме note
                    $note = null;
                    if ($docClassName === 'planning_Jobs') {
                        if (!empty($docRec->saleId)) {
                            $saleRec = sales_Sales::fetch($docRec->saleId);
                            $note = $saleRec->reff ?? null;
                        }else{
                            $note = $docRec->notes ?? null;
                        }
                    }else{
                        $firstDocument = !empty($docRec->threadId) ? doc_Threads::getFirstDocument($docRec->threadId) : null;

                        if($firstDocument && $firstDocument->isInstanceOf('sales_Sales')){
                            $note = $firstDocument->fetch()->reff ?? null;
                        }else{
                            $note = $docRec->note ?? null;
                        }
                    }

                    $dCloneRec->note = $note;

                    $dCloneRec->docReservedQuantyti = $docReserved->quantityOut;

                    $dCloneRec->store = $docReserved->storeId;

                    unset ($dCloneRec->documentsReserved, $dCloneRec->documentsExpected);

                    $recs[] = $this->getExportRec($rec, $dCloneRec, $ExportClass);

                    $markFirst++;

                }

                foreach ($dRec->documentsExpected ?? array() as $docExpected) {

                    $dCloneRec = clone $dRec;

                    $Document = cls::get($docExpected->sourceClassId);

                    $docClassName = $Document->className;

                    $docRec = $docClassName::fetch($docExpected->sourceId);
                    if (!$docRec) {
                        continue;
                    }

                    $dCloneRec->date = $docExpected->date;

                    $dCloneRec->document = $Document->abbr . $docExpected->sourceId;

                    //Определяме note
                    $note = null;
                    if ($docClassName === 'planning_Jobs') {
                        if (!empty($docRec->saleId)) {
                            $saleRec = sales_Sales::fetch($docRec->saleId);
                            $note = $saleRec->reff ?? null;
                        }else{
                            $note = $docRec->notes ?? null;
                        }
                    }else{
                        $firstDocument = !empty($docRec->threadId) ? doc_Threads::getFirstDocument($docRec->threadId) : null;
                        if($firstDocument && $firstDocument->isInstanceOf('sales_Sales')){
                            $note = $firstDocument->fetch()->reff ?? null;
                        }else{
                            $note = $docRec->note ?? null;
                        }
                    }

                    $dCloneRec->note = $note;

                    $dCloneRec->docExpectedQuantyti = $docExpected->quantityIn;

                    $dCloneRec->store = $docExpected->storeId;

                    unset($dCloneRec->documentsReserved, $dCloneRec->documentsExpected);

                    $recs[] = $this->getExportRec($rec, $dCloneRec, $ExportClass);

                    $markFirst++;

                }
            }
        }

        return $recs;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {

        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $pRec = cat_Products::fetch($dRec->productId);

            $res->productId = $pRec->name ?? null;
            $res->code = (!empty($pRec->code)) ? $pRec->code : "Art{$dRec->productId}";
            $res->quantity = $dRec->quantity;
            $res->free =$dRec->free;
            $res->expected = $dRec->expected;
            $res->reserved = $dRec->reserved;

        if (!empty($dRec->measure)) {
            $res->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        $res->date = $Date->toVerbal($dRec->date ?? null);
        $res->note = $dRec->note ?? null;

        $res->docExpectedQuantyti = $dRec->docExpectedQuantyti ?? null;
        $res->docReservedQuantyti = $dRec->docReservedQuantyti ?? null;

        if (!empty($dRec->store)) {
            $storeRec = store_Stores::fetch($dRec->store);
            $res->store = $storeRec->name ?? null;
        } else {
            $res->store = 'Без';
        }

    }

    /**
     * Кои полета да се следят при обновяване, за да се бие нотификация
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public function getNewFieldsToCheckOnRefresh($rec)
    {
        return 'productId,quantity';
    }

    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction(frame2_driver_Proto $Driver, &$res, $action)
    {

    }


    /**
     * Промяна на стойностите min и max
     *
     */
    public function act_EditMinMax()
    {
        return new Redirect(getRetUrl());
    }

    /**
     * Филтриране на група
     *
     */
    public static function act_GroupFilter()
    {
        return new Redirect(getRetUrl());

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        frame2_Reports::refresh($rec);

        $form = cls::get('core_Form');

        $form->title = "Филтър за група ";

        if ($rec->arhGroups) {

            foreach (keylist::toArray($rec->arhGroups) as $val) {

                $groupsSuggestionsArr[$val] = cat_Groups::fetch($val)->name;

                //Проверка за подгрупи
                $q = cat_Groups::getQuery()->where("#parentId = $val");

                if (!empty($q->fetchAll())) {
                    foreach ($q->fetchAll() as $subGr) {

                        $subGrArr = self::getGroupsSubLevels($subGr->id);

                        foreach ($subGrArr as $v) {
                            $groupsSuggestionsArr[$v] = cat_Groups::fetch($v)->name;
                        }

                    }

                }

            }

        }

        $form->FLD('groupFilter', 'key(mvc=cat_Groups,allowEmpty, select=name)', 'caption=Покажи група,placeholderType=all,silent');

        $form->setOptions('groupFilter', $groupsSuggestionsArr);

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            if (!$form->rec->groupFilter) {
                $rec->groups = $rec->arhGroups;
            } else {
                $rec->groups = '|' . $form->rec->groupFilter . '|';
            }
            $rec->grFilter = $form->rec->groupFilter;

            frame2_Reports::save($rec);
            frame2_Reports::refresh($rec);
            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'grFilter' => $form->rec->groupFilter, 'ret_url' => true));

        }

        return $form->renderHtml();
    }

    /**
     * Филтриране на артикул
     */
    public static function act_ArtFilter()
    {
        return new Redirect(getRetUrl());

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        frame2_Reports::refresh($rec);

        $form = cls::get('core_Form');

        $form->title = "Филтър по артикул";

        foreach (array_keys($rec->data->recs) as $val) {

            $pRec = cat_Products::fetch($val);
            $code = $pRec->code ?: 'Art' . $pRec->productId;
            $artSuggestionsArr[$val] = $code . '|' . $pRec->name;

        }

        $form->FLD('artFilter', 'key(mvc=cat_Products, select=name)', 'caption=Артикул,silent');

        $form->setOptions('artFilter', $artSuggestionsArr);

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            foreach ($rec->data->recs as $pRec) {
                if ($form->rec->artFilter != $pRec->productId) {
                    unset($rec->data->recs[$pRec->productId]);
                }
            }

            frame2_Reports::save($rec);
            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'artFilter' => $form->rec->artFilter, 'ret_url' => true));

        }

        return $form->renderHtml();
    }


    /**
     * Филтрър за експорт
     */
    public static function act_ExportFilter()
    {
        return new Redirect(getRetUrl());

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        frame2_Reports::refresh($rec);

        $form = cls::get('core_Form');

        $form->title = "Филтър за експорт";

        $form->FLD('exportFilter', 'set(1|под Мин.=Под минимум,3|над Макс.=Над максимум, 2|Отриц.=Отрицателни, 4|ок=ОК)', 'caption=Артикули с количества,columns=4,silent');

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Експорт', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            $rec->exportFilter = $form->rec->exportFilter;

            frame2_Reports::save($rec);

            $classId = core_Classes::getId('frame2_Reports');
            Request::setProtected(array('classId', 'docId'));
            $retUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId);
            $exportUrl = array('export_Export', 'export', 'classId' => $classId, 'docId' => $rec->id, 'ret_url' => $retUrl);

            return new Redirect(toUrl($exportUrl));

        }

        return $form->renderHtml();
    }

    /**
     * Вземане на поднивата на групите
     */
    public static function getGroupsSubLevels($groupId)
    {

        $subGrArr[$groupId] = $groupId;

        $groupsQuery = cat_Groups::getQuery();

        $groupsQuery->where("#parentId = $groupId");

        while ($gRec = $groupsQuery->fetch()) {

            $groupsQuery1 = cat_Groups::getQuery();

            if (!$groupsQuery1->fetchAll()) {
                self::getGroupsSubLevels($gRec->id);
            } else {
                $subGrArr[$gRec->id] = $gRec->id;
            }

        }

        return $subGrArr;

    }

    /**
     * Определяне на опаковки за поръчка
     */
    public static function getPacksForOrder($dRec, $rec)
    {
        $orderArr = array();

        $pRec = (cat_Products::fetch($dRec->productId));

        if ($dRec->maxQuantity) {

            //Предложено количество за поръчка
            $suggQuantity = $dRec->maxQuantity * $rec->orderLimit / 100 - $dRec->quantity;

            //Пакети за поръчка
            $quantityInPack = cat_Products::getProductInfo($pRec->id)->packagings[$dRec->orderMeasure]->quantity;

            if ($quantityInPack) {
                $packOrder = ceil($suggQuantity / $quantityInPack);
                $packOrder = ($dRec->minOrder < $packOrder) ? $packOrder : $dRec->minOrder;
            } else {
                $packOrder = $suggQuantity;
            }

            $orderArr = (object)array('packOrder' => $packOrder,
                'suggQuantity' => $suggQuantity);

        } else {
            if ($dRec->minQuantity) {

                $suggQuantity = $dRec->minQuantity * 3 - $dRec->quantity;

                //Пакети за поръчка
                $quantityInPack = cat_Products::getProductInfo($pRec->id)->packagings[$dRec->orderMeasure]->quantity;
                if ($quantityInPack) {
                    $packOrder = ceil($suggQuantity / $quantityInPack);
                    $packOrder = ($dRec->minOrder < $packOrder) ? $packOrder : $dRec->minOrder;
                } else {
                    $packOrder = 0;
                }

                $orderArr = (object)array('packOrder' => $packOrder,
                    'suggQuantity' => $suggQuantity);


            } else {
                if ($dRec->quantity < 0) {

                    $suggQuantity = $dRec->quantity * (-1);

                    //Пакети за поръчка
                    $quantityInPack = cat_Products::getProductInfo($pRec->id)->packagings[$dRec->orderMeasure]->quantity;

                    if ($quantityInPack) {
                        $packOrder = ceil($suggQuantity / $quantityInPack);
                        $packOrder = ($dRec->minOrder < $packOrder) ? $packOrder : $dRec->minOrder;
                    } else {
                        $packOrder = $suggQuantity;
                    }
                    $orderArr = (object)array('packOrder' => $packOrder,
                        'suggQuantity' => $suggQuantity);

                }
            }

        }

        //Ако предложението за поръчка е отрицателно, то се нулира
        if ($orderArr->packOrder < 0 || $orderArr->suggQuantity < 0) {
            $orderArr->packOrder = $orderArr->suggQuantity = 0;
        }

        return $orderArr;

    }


    /**
     * Определяне името на полето за склад
     */
    public static function getStoreFieldsName($docClassName, $t)
    {

        switch ($docClassName) {
            case 'planning_Jobs':
                $storeFieldName = 'storeId';
                break;
            case $docClassName == 'store_Transfers' && $t == 'out':
                $storeFieldName = 'fromStore';
                break;
            case $docClassName == 'store_Transfers' && $t == 'in':
                $storeFieldName = 'toStore';
                break; //fromStore,toStore
            case 'purchase_Purchases':
                $storeFieldName = 'shipmentStoreId';
                break;
            case 'store_Receipts':
                $storeFieldName = 'storeId';
                break;
            case 'sales_Sales':
                $storeFieldName = 'shipmentStoreId';
                break;
            case 'store_ShipmentOrders':
                $storeFieldName = 'storeId';
                break;
            default:
                $storeFieldName = 'storeId';
                break;
        }

        return $storeFieldName;
    }


}
