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
 * @title     Склад » Артикули наличности и лимити 1
 */
class store_reports_ProductAvailableQuantity1 extends frame2_driver_TableData
{
    const NUMBER_OF_ITEMS_TO_ADD = 250;

    const MAX_POST_ART = 50;


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
    public $canSelectDriver = 'ceo,manager,store,planning,purchase';


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

        $fieldset->FLD('date', 'date', 'caption=Към дата,after=typeOfQuantity,input=hidden,silent,single=none');

        $fieldset->FLD('artLimits', 'blob(serialize)', 'after=date,input=none,single=none');

        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,single=none,after=additional');

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=storeId,mandatory,silent,single=none');


        $fieldset->FLD('inputArts', 'varchar', 'caption=Наблюдавани артикули,after=groupId,input=hidden,single=none');
        $fieldset->FLD('orderBy', 'enum(conditionQuantity=Състояние,code=Код)', 'caption=Подреди по,maxRadio=2,columns=2,after=inputArts,silent');

        $fieldset->FLD('seeByStores', 'set(yes = )', 'caption=Детайлно,after=orderBy,single=none');

        $fieldset->FNC('groupsChecked', 'varchar', 'caption=Избрани групи,after=seeByStores,input=hidden,single=none');
        $fieldset->FNC('button', 'varchar', 'caption=Бутон,after=groupsChecked,input=hidden,single=none');

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

//        $form->setDefault('orderBy', 'conditionQuantity');
//
//        $form->setDefault('typeOfQuantity', 'free');
//
//        if ($rec->limits == 'no') {
//
//            unset($rec->orderBy);
//            unset($rec->groupsChecked);
//            $form->setField('orderBy', 'input=none');
//        }

        if ($rec->typeOfQuantity == 'free') {
            $form->setField('date', 'input');
        }

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


        }
    }

    public static function on_AfterSave($d, $mvc, &$id, $rec, &$fields = null, $mode = null)
    {


    }

    public static function on_BeforeSave($d, $mvc, &$id, $rec, &$fields = null, $mode = null)
    {

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
        // bp($rec);

        $recs = $storesQuatity = $artLimitsArr = array();


        $tempArrRec = frame2_Reports::fetch($rec->id);
        $tempArr = $tempArrRec->artLimits;


        if (is_array($tempArr) && !empty($tempArr)) { // bp($tempArr);
            $artLimitsArr = frame2_Reports::fetch($rec->id)->artLimits;
        }

        $codes = array();

        // Подготвяме заявката за извличането на записите от store_Products

        $sQuery = store_Products::getQuery();

        $sQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $sQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $sQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');;
//        if ($rec->limits == 'no') {
//            // Филтриране по група продукти
//            $sQuery->where("#groups LIKE '%|{$rec->groupId}|%'");
//        } else {
//            // Филтриране по кодове
//            if (is_array($additional->code)) {
//                foreach ($additional->code as $rowId => $code) {
//                    $code = mb_strtolower($code);
//                    $codes[$code] = $code;
//                    $minQuantity[$code] = $additional->minQuantity[$rowId];
//                    $maxQuantity[$code] = $additional->maxQuantity[$rowId];
//                }
//                $codeList = '|' . implode('|', $codes) . '|';
//            }
//
//            $sQuery->where(array("'[#1#]' LIKE CONCAT('%|', LOWER(COALESCE(#code, CONCAT('Art', #id))), '|%')", $codeList));
//        }


        //Филтър по групи артикули
        $sQuery->likeKeylist('groups', $rec->groups);

        // Филтриране по склад, ако е зададено
        if (isset($rec->storeId)) {
            $storArr = keylist::toArray($rec->storeId);
            $sQuery->in('storeId', $storArr);
        }

        while ($recProduct = $sQuery->fetch()) {

            $productId = $recProduct->productId;

            if ($rec->typeOfQuantity == 'free') {
                // Гледаме разполагаемото количество

                $date = ($rec->date) ? $rec->date : dt::today();
                $quantity = store_Products::getQuantities($productId, $recProduct->storeId, $date)->free;
            } else {
                // Гледаме наличното количество
                $quantity = $recProduct->quantity;
            }


            $stKey = $productId . '|' . $recProduct->storeId;
            $storesQuatity[$stKey] += $quantity;


            if ($obj = &$recs[$productId]) {
                $obj->quantity += $quantity;
            } else {
                //   $key = mb_strtolower($recProduct->code);

//                if (is_string($minQuantity[$key]) && strpos($minQuantity[$key], ',')) {
//                    $pos = strpos($minQuantity[$key], ',');
//                    $minQuantity[$key][$pos] = '.';
//                }
//
//                if (is_string($maxQuantity[$key]) && strpos($maxQuantity[$key], ',')) {
//                    $pos = strpos($maxQuantity[$key], ',');
//                    $maxQuantity[$key][$pos] = '.';
//                }

//                bp($recs);


                if (!in_array($productId, array_keys($artLimitsArr))) {
                    $artLimitsArr[$productId] = array('minQuantity' => '', 'maxQuantity' => '');
                    $minQuantity = '';
                    $maxQuantity = '';

                } else {
                    $minQuantity = $artLimitsArr[$productId]['minQuantity'];
                    $maxQuantity = $artLimitsArr[$productId]['maxQuantity'];
                }

                $code = ($recProduct->code) ?: 'Art' . $productId;
                $recs[$productId] = (object)array(
                    'measure' => $recProduct->measureId,
                    'productId' => $productId,
                    'storesQuatity' => '',
                    'quantity' => $quantity,
                    'minQuantity' => $minQuantity,
                    'maxQuantity' => $maxQuantity,
                    'code' => $code,
                );
            }
        }
        $rec->artLimits = $artLimitsArr;

        if (!is_null($recs)) {
            arr::sortObjects($recs, 'code', 'asc');
        }
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

            $prodRec->conditionQuantity = '3|ок';
            $prodRec->conditionColor = 'green';
            if ($prodRec->maxQuantity == 0 && $prodRec->minQuantity == 0 && $prodRec->minQuantity != '0') {
                continue;
            }
            if ($prodRec->quantity > $prodRec->maxQuantity && ($prodRec->maxQuantity != 0)) {
                $prodRec->conditionQuantity = '2|свръх наличност';
                $prodRec->conditionColor = 'blue';
            } elseif ($prodRec->quantity < $prodRec->minQuantity) {
                $prodRec->conditionQuantity = '1|под минимум';
                $prodRec->conditionColor = 'red';
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

        if ($export !== false) {
            $fld->FLD('code', 'varchar', 'caption=Код');
        }
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        $fld->FLD('quantity', 'double(smartRound,decimals=3)', 'caption=Количество,smartCenter');


        if ($rec->limits == 'yes') {
            $fld->FLD('minQuantity', 'double(smartRound,decimals=2)', 'caption=Минимално,smartCenter');
            $fld->FLD('maxQuantity', 'double(smartRound,decimals=2)', 'caption=Максимално,smartCenter');
            $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
            $fld->FLD('delrow', 'text', 'caption=Действие,smartCenter');
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

        $row = new stdClass();
        $t = core_Type::getByName('double(smartRound,decimals=3)');
        $row->productId = cat_Products::getShortHyperlink($dRec->productId, true);
        if ($rec->seeByStores != 'yes') {
            if (isset($dRec->quantity)) {
                $row->quantity = $t->fromVerbal($dRec->quantity);
                $row->quantity = ht::styleIfNegative($row->quantity, $dRec->quantity);
            }
        } else {

            $row->quantity = '<b>' . 'Общо: ' . $t->fromVerbal($dRec->quantity) . '</b>' . "</br>";

            foreach ($dRec->storesQuatity as $val) {

                list($storeId, $stQuantity) = explode('|', $val);
                $row->quantity .= store_Stores::getTitleById($storeId) . ': ' . ($stQuantity) . "</br>";
                $row->quantity = ht::styleIfNegative($row->quantity, $stQuantity);
            }
        }

        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        if (isset($dRec->minQuantity)) {
            $t = core_Type::getByName('double(smartRound,decimals=3)');
            $row->minQuantity = $t->fromVerbal($dRec->minQuantity);
            $row->minQuantity = $t->toVerbal($row->minQuantity);
        }

        if (isset($dRec->maxQuantity)) {
            $t = core_Type::getByName('double(smartRound,decimals=3)');
            $row->maxQuantity = $t->fromVerbal($dRec->maxQuantity);
            $row->maxQuantity = $t->toVerbal($row->maxQuantity);
        }

        if ((isset($dRec->conditionQuantity) && ((isset($dRec->minQuantity)) || (isset($dRec->maxQuantity))))) {
            list($a, $conditionQuantity) = explode('|', $dRec->conditionQuantity);

            $row->conditionQuantity = "<span style='color: {$dRec->conditionColor}'>${conditionQuantity}</span>";
//bp();
            $row->delrow = ht::createLink('', array('store_reports_ProductAvailableQuantity1', 'delRow', 'productId' => $dRec->productId, 'code' => $dRec->code, 'recId' => $rec->id, 'ret_url' => true), null, "ef_icon=img/16/delete.png");
            $row->delrow .= ht::createLink('', array('store_reports_ProductAvailableQuantity1', 'editminmax', 'productId' => $dRec->productId, 'code' => $dRec->code, 'recId' => $rec->id, 'ret_url' => true), null, "ef_icon=img/16/edit.png");
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

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN groupsChecked--><div>|Наблюдавани групи|*: [#groupsChecked#]</div><!--ET_END groupsChecked-->
                                        <!--ET_BEGIN inputArts--><div>|Наблюдавани артикули|*: [#inputArts#]</div><!--ET_END inputArts-->
                                        <!--ET_BEGIN ariculsData--><div>|Артикули с данни|*: [#ariculsData#]</div><!--ET_END ariculsData-->
                                        <!--ET_BEGIN storeId--><div>|Складове|*: [#storeId#]</div><!--ET_END storeId-->
                                        <!--ET_BEGIN typeOfQuantity--><div>|Количество|*: [#typeOfQuantity#]</div><!--ET_END typeOfQuantity-->
                                        <!--ET_BEGIN button--><div>|Филтър по група |*: [#button#]</div><!--ET_END button-->
                                    </div>
                                
                                 </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->groupsChecked)) {
            $marker = 0;
            $arr = explode(',', $data->rec->groupsChecked);
            foreach ($arr as $group) {
                $marker++;

                $groupVerb .= cat_Groups::fetch($group)->name;

                if ((countR($arr)) - $marker != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groupsChecked');
        }

        //  if ($data->rec->limits == 'no') {
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
        // }

        $data->rec->ariculsData = countR($data->rec->data->recs) - 1;

        if ($data->rec->limits == 'yes') {
            if (isset($data->rec->inputArts)) {
                $fieldTpl->append('<b>' . $data->rec->inputArts . '</b>', 'inputArts');
            }

            if (isset($data->rec->ariculsData)) {
                $fieldTpl->append('<b>' . $data->rec->ariculsData . '</b>', 'ariculsData');
            }
        }
        if ($data->rec->typeOfQuantity == 'free') {

            $dateVerb = dt::mysql2verbal($data->rec->date, 'd.m.Y');
            $fieldTpl->append('<b>' . 'Разполагаемо към ' . $dateVerb . '</b>', 'typeOfQuantity');

        } else {

            $dateVerb = dt::mysql2verbal(dt::today(), 'd.m.Y');
            $fieldTpl->append('<b>' . 'Налично към ' . $dateVerb . '</b>', 'typeOfQuantity');

        }

        $url = array('store_reports_ProductAvailableQuantity1', 'groupimport', 'ret_url' => true);

        $toolbar = cls::get('core_Toolbar');

        $toolbar->addBtn('Избери група', toUrl($url));

        $fieldTpl->append('<b>' . $toolbar->renderHtml() . '</b>', 'button');

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetCsvRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec)
    {
        $code = cat_Products::fetchField($dRec->productId, 'code');
        $res->code = (!empty($code)) ? $code : "Art{$dRec->productId}";
    }


    /**
     * Изчиства повтарящи се стойности във формата
     *
     * @param
     *            $arr
     *
     * @return array
     */
    public static function removeRpeadValues($arr)
    {
        $tempArr = (array)$arr;

        $tempProducts = array();
        if (is_array($tempArr['code'])) {
            foreach ($tempArr['code'] as $k => $v) {
                if (in_array($v, $tempProducts)) {
                    unset($tempArr['minQuantity'][$k]);
                    unset($tempArr['maxQuantity'][$k]);
                    unset($tempArr['name'][$k]);
                    unset($tempArr['code'][$k]);
                    continue;
                }

                $tempProducts[$k] = $v;
            }
        }

        $groupNamerr = $tempArr;

        return $arr;
    }

    /**
     * Валидира таблицата
     *
     * @param mixed $tableData
     * @param core_Type $Type
     * @return void|string|array
     */
    public static function validateTable($tableData, $Type)
    {

        $tableData = (array)$tableData;
        if (empty($tableData)) {

            return;
        }

        $res = $error = $errorFields = array();

        foreach ($tableData['minQuantity'] as $key => $minQuantity) {

            if (!empty($minQuantity)) {
                $Double = core_Type::getByName('double');
                $q2 = $Double->fromVerbal($minQuantity);
                if (!$q2) {
                    $error[] = 'Невалидна стойност';
                    $errorFields['minQuantity'][$key] = 'Невалидна стойност';
                }

            }
        }

        foreach ($tableData['maxQuantity'] as $key => $maxQuantity) {

            if (!empty($maxQuantity)) {
                $Double = core_Type::getByName('double');
                $q2 = $Double->fromVerbal($maxQuantity);
                if (!$q2) {
                    $error[] = 'Невалидна стойност';
                    $errorFields['maxQuantity'][$key] = 'Невалидна стойност';
                }

            }
        }

        if (countR($error)) {
            $error = implode('|*<li>|', $error);
            $res['error'] = $error;
        }

        if (countR($errorFields)) {
            $res['errorFields'] = $errorFields;
        }

        return $res;
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
    public static function on_BeforeAction($mvc, &$res, $action)
    {

        // self::act_GroupImport();

        //   if ($action == 'groupimport') {

        //  }
    }

    /**
     * Изключва един том от по-голям
     */
    public static function act_DelRow()
    {
        requireRole('debug');

        expect($recId = Request::get('recId', 'int'));
        expect($productId = Request::get('productId', 'int'));
        expect($code = Request::get('code'));
        $rec = frame2_Reports::fetch($recId);

        $details = $rec->artLimits;

        unset($details[$productId]);

        $rec->artLimits = $details;

        unset($rec->data->recs[$productId]);

        frame2_Reports::save($rec);

        return new Redirect(getRetUrl());
    }

    /**
     * Промяна на стойностите min и max
     */
    public function act_EditMinMax()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */

        requireRole('debug');

        expect($recId = Request::get('recId', 'int'));
        expect($productId = Request::get('productId', 'int'));
        expect($code = Request::get('code'));
        $rec = frame2_Reports::fetch($recId);

        $details = $rec->artLimits;

        $minVal = $details[$productId]['minQuantity'];
        $maxVal = $details[$productId]['maxQuantity'];
        $keyVal = $productId;
        $nameVal = "Продукт $productId";

        $form = cls::get('core_Form');

        $form->title = "Промяна на min и max за |* ' " . ' ' . $nameVal . "' ||*";

        $form->FLD('volOldMin', 'varchar', 'caption=Стойност min,silent');

        $volOldMin = $minVal;

        $form->setReadOnly('volOldMin', "$volOldMin");

        $form->input('volOldMin');

        $form->FLD('volNewMin', 'varchar', 'caption=Въведи min,input');

        $form->FLD('volOldMax', 'varchar', 'caption=Стойност max,silent');
        $form->FLD('volNewMax', 'varchar', 'caption=Въведи max в,input');

        $volOldMax = $maxVal;
        $form->setReadOnly('volOldMax', "$volOldMax");

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            $details[$productId]['minQuantity'] = $mRec->volNewMin;
            $details[$productId]['maxQuantity'] = $mRec->volNewMax;

            $rec->artLimits = $details;

            frame2_Reports::save($rec);

            frame2_Reports::refresh($rec);

            return new Redirect(getRetUrl());
        }

        return $form->renderHtml();


    }
}
