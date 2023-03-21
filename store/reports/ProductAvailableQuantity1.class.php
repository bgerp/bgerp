<?php


/**
 * Мениджър на отчети за налични количества
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Артикули наличности и лимити
 */
class store_reports_ProductAvailableQuantity1 extends frame2_driver_TableData
{

    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'quantity';


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'quantity';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,debug,manager,store,planning,purchase,cat,acc';

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
    protected $changeableFields = 'typeOfQuantity,additional,storeId,groupId,orderBy,limits,date,seeByStores';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('limits', 'enum(no=Без лимити,yes=С лимити)', 'caption=Вид,removeAndRefreshForm,after=title,silent');

        $fieldset->FLD('typeOfQuantity', 'enum(available=Налично,free=Разполагаемо)', 'caption=Количество,removeAndRefreshForm,single=none,silent,after=limits');

        $fieldset->FLD('typeOfPeriod', 'enum(toDate=Конкретна дата,period=Бъдещ момент)', 'caption=Към,removeAndRefreshForm,input=none,single=none,silent,after=typeOfQuantity');

        $fieldset->FLD('period', 'time(suggestions=1 ден|1 седмица|1 месец|6 месеца|1 година)', 'caption=Избор,placeholder=Днес,after=typeOfPeriod,input=none,single=none, unit=напред');

        $fieldset->FLD('date', 'date', 'caption=Избор,placeholder=Днес,after=period,input=none,silent,single=none');

        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,single=none,after=date');

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,placeholder=Избери група,after=storeId,mandatory,silent,single=none');

        $fieldset->FLD('orderBy', 'enum(conditionQuantity=Състояние,code=Код)', 'caption=Подреди по,maxRadio=2,columns=2,after=groups,silent');

        $fieldset->FLD('filters', 'enum(condQuantity=Състояние, no=Без филтри)', 'caption=Филтър->Филтри,removeAndRefreshForm,after=orderBy,silent');

        $fieldset->FLD('condFilter', 'set(1|под Мин.=Под минимум,3|над Макс.=Над максимум, 2|Отриц.=Отрицателни, 4|ок=ОК)', 'caption=Филтър->По състояние,columns=4,after=filters,input=none,silent');

        $fieldset->FLD('seeByStores', 'set(yes = )', 'caption=Настройки->Детайли по склад,after=condFilter,single=none');

        $fieldset->FLD('artLimits', 'blob(serialize)', 'after=seeByStores,input=none,single=none');

        $fieldset->FLD('arhGroups', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,input=none,silent,single=none');

        $fieldset->FLD('orderLimit', 'double', 'caption=Настройки->% за поръчка, unit=%-а от максималното количество,input,single=none');


        $fieldset->FNC('button', 'varchar', 'caption=Бутон,input=none,single=none');
        $fieldset->FNC('exportFilter', 'varchar', 'caption=Експорт филтър,input=none,single=none');
        $fieldset->FNC('grFilter', 'varchar', 'caption=Филтър по група,input=none,single=none');


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
        $rec->flag = true;

        $form->setDefault('orderBy', 'conditionQuantity');

        $form->setDefault('typeOfQuantity', 'free');

        $form->setDefault('typeOfPeriod', 'toDate');

        $form->setDefault('filters', 'no');

        $form->setDefault('condFilter', '');

        $form->setDefault('orderLimit', 80);

        if ($rec->arhGroups) {
            $rec->groups = $rec->arhGroups;
        }

        if ($rec->limits == 'no') {

            unset($rec->orderBy);
            unset($rec->groupsChecked);
            $form->setField('orderBy', 'input=none');
            $form->setField('orderLimit', 'input=hidden');
        }

        if ($rec->typeOfQuantity == 'free') {

            $form->setField('typeOfPeriod', 'input');

            if ($rec->typeOfPeriod == 'toDate') {

                $form->setField('date', 'input');
            }
            if ($rec->typeOfPeriod == 'period') {

                $form->setField('period', 'input');
            }


        }

        if ($rec->filters == 'condQuantity') {
            $form->setField('condFilter', 'input');
        }

        $suggestions = array('' => '', '50' => 50, '60' => 60, '70' => 70, '80' => 80);
        $form->setSuggestions('orderLimit', $suggestions);

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
        if ($form->isSubmitted()) {

            $rec->arhGroups = $rec->groups;
            unset($rec->grFilter);

        }
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
        $recs = $storesQuatity = $artLimitsArr = array();

        $tempArrRec = frame2_Reports::fetch($rec->id);

        $tempArr = $tempArrRec->artLimits;

        if (is_array($tempArr) && !empty($tempArr)) {
            $artLimitsArr = frame2_Reports::fetch($rec->id)->artLimits;
        }

        $codes = array();

        // Подготвяме заявката за извличането на записите от store_Products

        $sQuery = store_Products::getQuery();

        $sQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $sQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $sQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');;

        //Филтър по групи артикули
        $sQuery->likeKeylist('groups', $rec->groups);

        // Филтриране по склад, ако е зададено
        if (isset($rec->storeId)) {
            $storArr = keylist::toArray($rec->storeId);
            $sQuery->in('storeId', $storArr);
        }

        while ($recProduct = $sQuery->fetch()) {

            $productId = $recProduct->productId;

            if ($rec->typeOfQuantity == 'free' && $recProduct->storeId) {

                // Гледаме разполагаемото количество
                if ($rec->typeOfPeriod == 'toDate') {
                    $date = ($rec->date) ? $rec->date : dt::today();
                } else {
                    $date = dt::addSecs($rec->period, dt::today(), false) . ' 23:59:59';
                    $rec->date = $date;
                }


                $quantity = store_Products::getQuantities($productId, $recProduct->storeId, $date)->free;

            } else {

                // Гледаме наличното количество
                $quantity = $recProduct->quantity;

            }

            if ($recProduct->storeId && $productId) {
                $stKey = $productId . '|' . $recProduct->storeId;
                $storesQuatity[$stKey] += $quantity;
            }

            if ($obj = &$recs[$productId]) {
                $obj->quantity += $quantity;
            } else {

                if (!in_array($productId, array_keys($artLimitsArr))) {
                    $artLimitsArr[$productId] = array('minQuantity' => 0, 'maxQuantity' => 0, 'orderMeasure' => 0, 'minOrder' => 0);
                    $minQuantity = 0;
                    $maxQuantity = 0;
                    $orderMeasure = 0;
                    $minOrder = 0;

                } else {
                    $minQuantity = $artLimitsArr[$productId]['minQuantity'];
                    $maxQuantity = $artLimitsArr[$productId]['maxQuantity'];
                    $orderMeasure = $artLimitsArr[$productId]['orderMeasure'];
                    $minOrder = $artLimitsArr[$productId]['minOrder'];
                }

                $code = ($recProduct->code) ?: 'Art' . $productId;


                $recs[$productId] = (object)array(
                    'measure' => $recProduct->measureId,
                    'productId' => $productId,
                    'storesQuatity' => 0,
                    'quantity' => $quantity,
                    'minQuantity' => $minQuantity,
                    'maxQuantity' => $maxQuantity,
                    'orderMeasure' => $orderMeasure,
                    'minOrder' => $minOrder,
                    'code' => $code,
                    'groups' => $recProduct->groups,
                );
            }
        }
        $rec->artLimits = $artLimitsArr;

        if (!is_null($recs)) {
            arr::sortObjects($recs, 'code', 'asc');
        }

        //Разпределяне по складове
        $temp = array();
        foreach ($storesQuatity as $key => $val) {

            list($newKey, $stId) = explode('|', $key);
            if (!in_array($newKey, array_keys($temp))) {
                $temp[$newKey] = array($stId . '|' . $val);
            } else {
                array_push($temp[$newKey], $stId . '|' . $val);

            }
        }

        // Определяне на индикаторите за "свръх наличност" и "под минимум";
        foreach ($recs as $productId => $prodRec) {

            $prodRec->storesQuatity = $temp[$productId];

            $prodRec->conditionQuantity = '4|ок';
            $prodRec->conditionColor = 'green';
            if ($prodRec->maxQuantity == 0 && $prodRec->minQuantity == 0 && $prodRec->minQuantity != '0') {
                //  continue;
            }


            if ($prodRec->quantity > $prodRec->maxQuantity && ($prodRec->maxQuantity != 0)) {
                $prodRec->conditionQuantity = '3|над Макс.';
                $prodRec->conditionColor = 'blue';
            } elseif ($prodRec->quantity < $prodRec->minQuantity) {
                $prodRec->conditionQuantity = '1|под Мин.';
                $prodRec->conditionColor = 'red';
            }
            if ($prodRec->quantity < 0) {
                $prodRec->conditionQuantity = '2|Отриц.';
                $prodRec->conditionColor = 'red';
            }
        }

        //Филтри за показване
        //Филтър по състояние
        if ($rec->filters == 'condQuantity') {

            $condFilter = $rec->condFilter;
            foreach ($recs as $key => $oneRec) {
                if (!in_array($oneRec->conditionQuantity, explode(',', $condFilter))) {
                    unset($recs[$key]);
                }

            }


        }


        if (!is_null($recs)) {
            if ($rec->orderBy) {
                arr::sortObjects($recs, $rec->orderBy, 'asc');
            } else {
                arr::sortObjects($recs, 'quantity', 'desc');
            }
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

            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'varchar', 'caption=Количество,smartCenter');


            if ($rec->limits == 'yes') {
                $fld->FLD('minQuantity', 'double(smartRound,decimals=3)', 'caption=Лимит->Мин.,smartCenter');
                $fld->FLD('maxQuantity', 'double(smartRound,decimals=3)', 'caption=Лимит->Макс.,smartCenter');
                $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
                $fld->FLD('delrow', 'text', 'caption=Пулт,smartCenter');
            }
            if (haveRole('debug')) {
//                $fld->FLD('orderMeasure', 'key(mvc=cat_UoM,select=name)', 'caption=За поръчка->Мярка,tdClass=centered');
//                $fld->FLD('minOrder', 'varchar', 'caption=За поръчка->Мин опаковки,smartCenter');
//                $fld->FLD('packOrder', 'varchar', 'caption=За поръчка->Опаковки,smartCenter');
            }
        } else {
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('orderMeasure', 'varchar', 'caption=За поръчка->Мярка,tdClass=centered');
            $fld->FLD('packOrder', 'double(decimals=3)', 'caption=За поръчка->Опаковки,smartCenter');
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
        $Int = cls::get('type_Int');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 3;
        $Double->params['smartRound'] = 'smartRound';

        $row = new stdClass();
        $row->productId = cat_Products::getShortHyperlink($dRec->productId, true);
        if ($rec->seeByStores != 'yes') {
            if (isset($dRec->quantity)) {

                $row->quantity = $Double->toVerbal($dRec->quantity);
                $row->quantity = ht::styleIfNegative($row->quantity, $dRec->quantity);
            }
        } else {

            $row->quantity = '<b>' . 'Общо: ' . $Double->toVerbal($dRec->quantity) . '</b>' . "</br>";

            foreach ($dRec->storesQuatity as $val) {

                list($storeId, $stQuantity) = explode('|', $val);
                $row->quantity .= store_Stores::getTitleById($storeId) . ': ' . ($stQuantity) . "</br>";
                $row->quantity = ht::styleIfNegative($row->quantity, $stQuantity);
            }
        }

        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        if (isset($dRec->orderMeasure)) {
            $row->orderMeasure = cat_UoM::fetchField($dRec->orderMeasure, 'shortName');
        }

        if (isset($dRec->minOrder)) {
            $row->minOrder = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->minOrder);

        }

        $orderArr = self::getPacksForOrder($dRec, $rec);

        $row->packOrder = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($orderArr->packOrder);

        if (isset($dRec->minQuantity)) {
            $t = core_Type::getByName('double(smartRound,decimals=3)');
            $row->minQuantity = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->minQuantity);

        }

        if (isset($dRec->maxQuantity)) {

            $row->maxQuantity = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->maxQuantity);
        }

        if ((isset($dRec->conditionQuantity))) {
            list($a, $conditionQuantity) = explode('|', $dRec->conditionQuantity);

            $row->conditionQuantity = "<span style='color: $dRec->conditionColor'>$conditionQuantity</span>";
        }
        $row->delrow = '';
        //$row->delrow .= ht::createLink('', array('store_reports_ProductAvailableQuantity1', 'delRow', 'productId' => $dRec->productId, 'code' => $dRec->code, 'recId' => $rec->id, 'ret_url' => true), null, "ef_icon=img/16/delete.png");
        $row->delrow .= ht::createLink('', array('store_reports_ProductAvailableQuantity1', 'editminmax', 'productId' => $dRec->productId, 'code' => $dRec->code, 'recId' => $rec->id, 'ret_url' => true), null, "ef_icon=img/16/edit.png");


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

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN arhGroups--><div>|Наблюдавани групи|*: [#arhGroups#]</div><!--ET_END arhGroups-->
                                        <!--ET_BEGIN ariculsData--><div>|Брой артикули|*: [#ariculsData#]</div><!--ET_END ariculsData-->
                                        <!--ET_BEGIN storeId--><div>|Складове|*: [#storeId#]</div><!--ET_END storeId-->
                                        <!--ET_BEGIN typeOfQuantity--><div>|Количество|*: [#typeOfQuantity#]</div><!--ET_END typeOfQuantity-->
                                        <!--ET_BEGIN grFilter--><div>|Филтър по група |*: [#grFilter#]</div><!--ET_END grFilter-->
                                        <!--ET_BEGIN button--><div>|Филтри |*: [#button#]</div><!--ET_END button-->
                                    </div>
                                
                                 </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->arhGroups)) {
            $marker = 0;
            foreach (keylist::toArray($data->rec->arhGroups) as $group) {
                $marker++;

                $groupVerb .= cat_Groups::fetch($group)->name;

                if ((countR(keylist::toArray($data->rec->arhGroups))) - $marker != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'arhGroups');
        }

        if (isset($data->rec->storeId)) {

            $marker = 0;
            foreach (type_Keylist::toArray($data->rec->storeId) as $store) {
                $marker++;

                $storeIdVerb .= (store_Stores::getTitleById($store));

                if ((countR(type_Keylist::toArray($data->rec->storeId))) - $marker != 0) {
                    $storeIdVerb .= ', ';
                }

            }

            $fieldTpl->append('<b>' . $storeIdVerb . '</b>', 'storeId');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'storeId');
        }

        $data->rec->ariculsData = countR($data->rec->data->recs) - 1;

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

        $exportFilterArr = explode(',', $rec->exportFilter);

        expect(cls::haveInterface('export_ExportTypeIntf', $ExportClass));
        $recsToExport = $this->getRecsForExport($rec, $ExportClass);

        $recs = array();
        if (is_array($recsToExport)) {
            foreach ($recsToExport as $dRec) {

                if ($rec->exportFilter && in_array($dRec->conditionQuantity, $exportFilterArr)) {
                    $recs[] = $this->getExportRec($rec, $dRec, $ExportClass);
                } elseif (!$rec->exportFilter) {
                    $recs[] = $this->getExportRec($rec, $dRec, $ExportClass);
                }

            }
        }
        //unset($rec->exportFilter);
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

        $orderArr = self::getPacksForOrder($dRec, $rec);

        $pRec = (cat_Products::fetch($dRec->productId));

        $res->productId = $pRec->name;

        $res->code = (!empty($pRec->code)) ? $pRec->code : "Art{$pRec->id}";

        $res->suggQuantity = $orderArr->suggQuantity;

        $res->packOrder = $orderArr->packOrder;

        if ($dRec->orderMeasure) {
            $res->orderMeasure = cat_UoM::fetchField($dRec->orderMeasure, 'shortName');
        } else {
            $res->orderMeasure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        if ($dRec->orderMeasure) {
            $res->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
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

        //Пакетажите на артикула за избор
        $prodPackArr = arr::extractValuesFromArray(cat_Products::getProductInfo($productId)->packagings, 'packagingId');
        $productRec = cat_Products::getProductInfo($productId)->productRec;

        //Добавяме възможност за избор освен пакетажа и основната мярка
        $prodPackArr[$productRec->measureId] = $productRec->measureId;

        $q = cat_UoM::getQuery();
        // $q->where("#type = 'packaging'");
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
                if (($packOrder * $quantityInPack + $dRec->quantity) > $dRec->maxQuantity) $packOrder--;
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
                    if (($packOrder * $quantityInPack + $dRec->quantity) > $dRec->maxQuantity) $packOrder--;
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
                        if (($packOrder * $quantityInPack + $dRec->quantity) > $dRec->maxQuantity) $packOrder--;
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


}
