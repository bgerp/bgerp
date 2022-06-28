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
    public $canSelectDriver = 'debug';

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

        $fieldset->FLD('date', 'date', 'caption=Към дата,after=typeOfQuantity,silent,single=none');

        $fieldset->FLD('stores', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,single=none,after=date');

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=storeId,mandatory,silent,single=none');

        //Подредба на резултатите
         $fieldset->FLD('order', 'enum(desc=Низходящо, asc=Възходящо)', 'caption=Подреждане на резултата->Ред,maxRadio=2,after=orderBy,single=none');
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

        //$sQuery = store_Products::getQuery();
        $sQuery = store_StockPlanning::getQuery();

        $sQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $sQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $sQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        //Филтър по групи артикули
        $sQuery->likeKeylist('groups', $rec->groups);

        if ($rec->stores) {
            $storesArr = keylist::toArray($rec->stores);
            $sQuery->in('storeId', $storesArr);
            $storesRecsArr = $sQuery->fetchAll();

        } else {
            $storesRecsArr = arr::extractValuesFromArray($sQuery->fetchAll(), 'productId');
        }


        foreach ($storesRecsArr as $sRec) {


            if (!is_object($sRec)) {
                $sRec = store_StockPlanning::fetch("#productId = $sRec");
            }
            $pRec   = (cat_Products::fetch($sRec->productId));


            if (!$sRec->measureId) {
                $measureId = cat_Products::fetch($sRec->productId)->measureId;
            } else {
                $measureId = $sRec->measureId;
            }

            $id = $sRec->productId;

            $Quantities = store_Products::getQuantities($sRec->productId, $storesArr, $rec->date);

            $quantity = $Quantities->quantity;
            $reserved = $Quantities->reserved;
            $expected = $Quantities->expected;
            $free = $Quantities->free;

            $documentsReserved = store_StockPlanning::getRecs($sRec->productId, $storesArr, $rec->date, 'reserved');
            $documentsExpected = store_StockPlanning::getRecs($sRec->productId, $storesArr, $rec->date, 'expected');

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

            unset($documentsReserved,$documentsExpected,$Quantities,$code);

        }

        if (!empty($recs)) {

            arr::sortObjects($recs, 'code', $rec->order,'stri');
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
            $fld->FLD('quantity', 'varchar', 'caption=Количество->Налично,smartCenter');
            $fld->FLD('reserved', 'varchar', 'caption=Количество->Запазено,smartCenter');
            $fld->FLD('expected', 'varchar', 'caption=Количество->Очаквано,smartCenter');
            $fld->FLD('free', 'varchar', 'caption=Количество->Разполагаемо,smartCenter');

            if (core_Users::haveRole('debug')) {
                $fld->FLD('delrow', 'text', 'caption=Пулт,smartCenter');
            }

        } else {
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('document', 'varchar', 'caption=Документ,tdClass=centered');
            $fld->FLD('date', 'varchar', 'caption=Падеж,tdClass=centered');
            $fld->FLD('note', 'varchar', 'caption=Забележка,tdClass=centered');
            $fld->FLD('store', 'varchar', 'caption=Склад,tdClass=centered');
            $fld->FLD('docReservedQuantyti', 'varchar', 'caption=Количество->Запазено,smartCenter');
            $fld->FLD('docExpectedQuantyti', 'varchar', 'caption=Количество->Очаквано,smartCenter');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'varchar', 'caption=Количество Общо->Налично,smartCenter');
            $fld->FLD('reserved', 'varchar', 'caption=Количество Общо->Запазено,smartCenter');
            $fld->FLD('expected', 'varchar', 'caption=Количество Общо->Очаквано,smartCenter');
            $fld->FLD('free', 'varchar', 'caption=Количество Об що->Разполагаемо,smartCenter');

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
        $Double->params['smartRound'] = 'smartRound';

        $row = new stdClass();

        $pRec   = (cat_Products::fetch($dRec->productId));

        $row->code = (!empty($pRec->code)) ? $pRec->code : "Art{$pRec->id}";

        $row->productId = cat_Products::getLinkToSingle_($dRec->productId, true);


        $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');

        $row->quantity = $Double->toVerbal($dRec->quantity);
        $row->quantity = ht::styleIfNegative($row->quantity, $dRec->quantity);


        $row->reserved = $Double->toVerbal($dRec->reserved);
        $row->reserved = ht::styleIfNegative($row->reserved, $dRec->reserved);

        $date = ($rec->date) ? $rec->date : dt::today();
        $title = 'От кои документи е сформирано количеството';

        $tooltipUrl = toUrl(array('store_Products', 'ShowReservedDocs', 'productId' => $dRec->productId, 'stores' => $rec->stores, 'replaceField' => "reserved{$dRec->productId}", 'field' => 'reserved', 'date' => $date), 'local');
        $arrowImg = ht::createElement('img', array('height' => 16, 'width' => 16, 'src' => sbf('img/32/info-gray.png', '')));
        $arrow = ht::createElement('span', array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl, 'title' => $title), $arrowImg, true);
        $arrow = "<span class='additionalInfo-holder'><span class='additionalInfo' id='reserved{$dRec->productId}'></span>{$arrow}</span>";

        if ($dRec->reserved) {
            $row->reserved = $arrow . $row->reserved;
        }

        $row->expected = $Double->toVerbal($dRec->expected);
        $row->expected = ht::styleIfNegative($row->expected, $dRec->expected);

        $tooltipUrl = toUrl(array('store_Products', 'ShowReservedDocs', 'productId' => $dRec->productId, 'stores' => $rec->stores, 'replaceField' => "expected{$dRec->productId}", 'field' => 'expected', 'date' => $date), 'local');
        $arrowImg = ht::createElement('img', array('height' => 16, 'width' => 16, 'src' => sbf('img/32/info-gray.png', '')));
        $arrow = ht::createElement('span', array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl, 'title' => $title), $arrowImg, true);
        $arrow = "<span class='additionalInfo-holder'><span class='additionalInfo' id='expected{$dRec->productId}'></span>{$arrow}</span>";

        if ($dRec->expected) {
            $row->expected = $arrow . $row->expected;
        }

        $row->free = $Double->toVerbal($dRec->free);
        $row->free = ht::styleIfNegative($row->free, $dRec->free);


        $row->delrow = '';
        $row->delrow .= ht::createLink('', array('store_reports_JobsHorizons', 'editminmax', 'productId' => $dRec->productId, 'code' => $dRec->code, 'recId' => $rec->id, 'ret_url' => true), null, "ef_icon=img/16/edit.png");

        if($dRec->store){
            $row->store = store_Stores::getHyperlink($dRec->store);
        }else{
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

        $data->rec->ariculsData = countR($data->rec->data->recs);

        if (isset($data->rec->ariculsData)) {
            $fieldTpl->append('<b>' . $data->rec->ariculsData . '</b>', 'ariculsData');
        }

        if ($data->rec->typeOfQuantity == 'free') {

            $dateVerb = dt::mysql2verbal($data->rec->date, 'd.m.Y');
            $fieldTpl->append('<b>' . 'Разполагаемо към ' . $dateVerb . '</b>', 'typeOfQuantity');

        } else {

            $dateVerb = dt::mysql2verbal(dt::today(), 'd.m.Y');
            $fieldTpl->append('<b>' . 'Налично към ' . $dateVerb . '</b>', 'typeOfQuantity');

        }

        //Филтър по група
        $grFilter = $data->rec->grFilter;

        if ($grFilter) {
            $grFilterName = cat_Groups::fetch($grFilter)->name;
        } else {
            $grFilterName = 'Не е избрана';
        }
        $fieldTpl->append('<b>' . "$grFilterName" . '</b>', 'grFilter');

        $grUrl = array('store_reports_ProductAvailableQuantity1', 'groupfilter', 'recId' => $data->rec->id, 'ret_url' => true);
        $artUrl = array('store_reports_ProductAvailableQuantity1', 'artfilter', 'recId' => $data->rec->id, 'ret_url' => true);
        $exportUrl = array('store_reports_ProductAvailableQuantity1', 'exportfilter', 'recId' => $data->rec->id, 'ret_url' => true);

        $toolbar = cls::get('core_Toolbar');

        $toolbar->addBtn('Избери група', toUrl($grUrl));
        $toolbar->addBtn('Избери артикул', toUrl($artUrl));
        $toolbar->addBtn('Филтър за експорт', toUrl($exportUrl));

        $fieldTpl->append('<b>' . $toolbar->renderHtml() . '</b>', 'button');

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
        if (is_array($recsToExport)) {
            foreach ($recsToExport as $dRec) {

                $markFirst = 1;

                foreach ($dRec->documentsReserved as $docReserved) {

                    $dCloneRec = clone $dRec;

                    //$document = cls::get($docReserved->sourceClassId)->abbr . $docReserved->sourceId;
                    $DocumentRez = cls::get($docReserved->sourceClassId);
                    $docClassName = $DocumentRez->className;
                    $docRec = $docClassName::fetch($docReserved->sourceId);

                    if ($markFirst == 1) {
                        $dCloneRec->markFirst = true;
                    } else {
                        $dCloneRec->markFirst = false;
                    }

                    $dCloneRec->date = $docReserved->date;

                    $dCloneRec->document = $DocumentRez->abbr . $docReserved->sourceId;

                    $dCloneRec->note =($docClassName === 'planning_Jobs') ? $docRec->notes :$docRec->note;

                    $dCloneRec->docReservedQuantyti = $docReserved->quantityOut;

                    $storeFieldName = self::getStoreFieldsName($docClassName);

                    $dCloneRec->store = $docRec->$storeFieldName;

                    unset ($dCloneRec->documentsReserved, $dCloneRec->documentsExpected);

                    $recs[] = $this->getExportRec($rec, $dCloneRec, $ExportClass);

                    $markFirst++;

                }

                foreach ($dRec->documentsExpected as $docExpected) {

                    $dCloneRec = clone $dRec;

                    $Document = cls::get($docExpected->sourceClassId);

                    $docClassName = $Document->className;
                    $docRec = $docClassName::fetch($docExpected->sourceId);

                    if ($markFirst == 1) {
                        $dCloneRec->markFirst = true;
                    } else {
                        $dCloneRec->markFirst = false;
                    }

                    $dCloneRec->date = $docExpected->date;

                    $dCloneRec->document = $Document->abbr . $docExpected->sourceId;

                    $dCloneRec->note =($docClassName === 'planning_Jobs') ? $docRec->notes :$docRec->note;

                    $dCloneRec->docExpectedQuantyti = $docExpected->quantityIn;

                    $storeFieldName =  trim(self::getStoreFieldsName($docClassName));

                    $dCloneRec->store = $docRec->$storeFieldName;

                    unset ($dCloneRec->documentsExpected, $dCloneRec->documentsExpected);

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

        $pRec = (cat_Products::fetch($dRec->productId));

        if ( $dRec->markFirst) {
            $res->productId = $pRec->name;
            $res->code = (!empty($pRec->code)) ? $pRec->code : "Art{$pRec->id}";
            $res->quantity = $dRec->quantity;
            $res->free = $dRec->free;
            $res->expected = $dRec->expected;
            $res->reserved =$dRec->reserved;
        } else {
            $res->productId = '';
            $res->code = '';
            $res->quantity = '';
            $res->free = '';
            $res->expected = '';
            $res->reserved = '';
        }


        if ($dRec->measure) {
            $res->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        $res->date = $Date->toVerbal($dRec->date);
        $res->note= $dRec->note;

        $res->docExpectedQuantyti = $dRec->docExpectedQuantyti;
        $res->docReservedQuantyti = $dRec->docReservedQuantyti;

        if($dRec->store){
            $res->store = store_Stores::fetch($dRec->store)->name;
        }else{
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
        return ($rec->limits == 'yes') ? 'productId,conditionQuantity' : 'productId,quantity';
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

        expect($recId = Request::get('recId', 'int'));
        expect($productId = Request::get('productId', 'int'));
        expect($code = Request::get('code'));

        $rec = frame2_Reports::fetch($recId);

        $details = $rec->artLimits;

        $minVal = $details[$productId]['minQuantity'];
        $maxVal = $details[$productId]['maxQuantity'];
        $orderMeasure = $details[$productId]['orderMeasure'];
        $minOrder = $details[$productId]['minOrder'];

        $keyVal = $productId;

        $form = cls::get('core_Form');

        $form->title = "Редактиране на  |* ' " . ' ' . cat_Products::getHyperlink($productId) . "' ||*";

        $volOldMin = $minVal;
        $volOldMax = $maxVal;
        $orderMeasureOld = $orderMeasure;
        $minOrderOld = $minOrder;

        $form->FLD('volNewMin', 'double', 'caption=Въведи min,input,silent');

        $form->FLD('volNewMax', 'double', 'caption=Въведи max,input,silent');

        $form->FLD('orderMeasureNew', 'key(mvc=cat_UoM,select=name)', 'caption=Опаковка за поръчка,input,silent');

        $form->FLD('minOrderNew', 'double', 'caption=Минимална поръчка,input,silent');;
        $form->setDefault('volNewMax', $volOldMax);
        $form->setDefault('volNewMin', $volOldMin);
        $form->setDefault('orderMeasureNew', $orderMeasureOld);
        $form->setDefault('minOrderNew', $minOrderOld);

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        //Пакетажите на артикула
        $prodPackArr = arr::extractValuesFromArray(cat_Products::getProductInfo($productId)->packagings, 'packagingId');

        $q = cat_UoM::getQuery();
        $q->where("#type = 'packaging'");
        $q->in('id', $prodPackArr);

        while ($qRec = $q->fetch()) {
            $options[$qRec->id] = $qRec->name;
        }

        if (empty($prodPackArr) || empty($options)) {
            $options = array();
            $options[cat_Products::fetch($productId)->measureId] = cat_UoM::fetch(cat_Products::fetch($productId)->measureId)->name;
        }

        $form->setOptions('orderMeasureNew', $options);

        if ($form->rec->volNewMax < $form->rec->volNewMin) {

            $form->setError('volNewMin, volNewMax', ' Максималното количество не може да бъде по-малко от минималното ');
        }


        if ($form->isSubmitted()) {

            $details[$productId]['minQuantity'] = $mRec->volNewMin;
            $details[$productId]['maxQuantity'] = $mRec->volNewMax;
            $details[$productId]['orderMeasure'] = $mRec->orderMeasureNew;
            $details[$productId]['minOrder'] = $mRec->minOrderNew;

            $rec->artLimits = $details;

            frame2_Reports::save($rec);

            frame2_Reports::refresh($rec);

            return new Redirect(getRetUrl());
        }

        return $form->renderHtml();


    }

    /**
     * Филтриране на група
     *
     */
    public static function act_GroupFilter()
    {

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

        $form->FLD('groupFilter', 'key(mvc=cat_Groups,allowEmpty, select=name)', 'caption=Покажи група,placeholder=Изчисти филтъра,silent');

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
    public static function getStoreFieldsName($docClassName)
    {

        switch ($docClassName) {
            case 'planning_Jobs': $storeFieldName = 'storeId'; break;
            case 'store_Transfers': $storeFieldName = 'toStore'; break; //fromStore,toStore
            case 'purchase_Purchases': $storeFieldName = 'shipmentStoreId'; break;
            case 'store_Receipts': $storeFieldName = 'storeId'; break;
            case 'sales_Sales': $storeFieldName = 'shipmentStoreId'; break;
            case 'store_ShipmentOrders': $storeFieldName = 'storeId'; break;
            default: $storeFieldName = 'storeId'; break;
        }

        return $storeFieldName;
    }


}
