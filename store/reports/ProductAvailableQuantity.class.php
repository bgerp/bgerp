<?php


/**
 * Мениджър на отчети за налични количества
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Артикули наличности и лимити (стара; извън поддръжка)
 */
class store_reports_ProductAvailableQuantity extends frame2_driver_TableData
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
    public $canSelectDriver = 'debug';


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
    protected $changeableFields = 'typeOfQuantity,additional,storeId,groupId,orderBy,limmits,date,seeByStores';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('limmits', 'enum(no=Без лимити,yes=С лимити)', 'caption=Вид,removeAndRefreshForm,after=title,silent');

        $fieldset->FLD('typeOfQuantity', 'enum(available=Налично,free=Разполагаемо)', 'caption=Количество,removeAndRefreshForm,single=none,silent,after=limmits');

        $fieldset->FLD('date', 'date', 'caption=Към дата,after=typeOfQuantity,input=hidden,silent,single=none');

        $fieldset->FLD('additional', 'table(columns=code|name|minQuantity|maxQuantity,captions=Код на артикула|Мярка / Наименование|Мин к-во|Макс к-во,widths=5em|20em|5em|5em,validate=store_reports_ProductAvailableQuantity::validateTable)', 'caption=Артикули||Additional,autohide,advanced,after=date,single=none');

        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,single=none,after=additional');
        $fieldset->FLD('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=storeId,silent,single=none,removeAndRefreshForm');

        $fieldset->FLD('inputArts', 'varchar', 'caption=Наблюдавани артикули,after=groupId,input=hidden,single=none');
        $fieldset->FLD('orderBy', 'enum(conditionQuantity=Състояние,code=Код)', 'caption=Подреди по,maxRadio=2,columns=2,after=inputArts,silent');

        $fieldset->FLD('seeByStores', 'set(yes = )', 'caption=Детайлно,after=orderBy,single=none');

        $fieldset->FNC('groupsChecked', 'varchar', 'caption=Избрани групи,after=seeByStores,input=hidden,single=none');

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
        $form->input('additional');
        $form->setDefault('typeOfQuantity', 'free');

        if ($rec->limmits == 'no') {

            unset($rec->orderBy);
            unset($rec->groupsChecked);
            $form->setField('orderBy', 'input=none');
        }

        if ($rec->typeOfQuantity == 'free') {
            $form->setField('date', 'input');
        }

        if (!$rec->additional) {
            $form->setField('groupId', 'mandatory');
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
        if ($form->rec->limmits == 'yes') {
            if (is_string($form->rec->additional)) {
                $details = json_decode($form->rec->additional);
            } else {
                $details = $form->rec->additional;
            }
        } else {
            $form->setField('additional', 'input=none');
        }

        $fldArr = array('minQuantity','maxQuantity');
        foreach ($fldArr as $fld){
            if(is_array($details->$fld)) {
                foreach ($details->$fld as $key => $val) {
                    if ($val) {
                        $Double = core_Type::getByName('double');
                        $details->$fld[$key] = $Double->fromVerbal($val);
                    }
                }
            }
        }


        if ($form->isSubmitted()) {

            //При избрано количество РАЗПОЛАГАЕМО проверяваме датата да е поголяма от днес
            if ($form->rec->date && $form->rec->typeOfQuantity == 'free' && $form->rec->date < dt::today()) {
                $form->setError('date', 'Датата не може да бъде минала');
            }

            if ($form->rec->limmits == 'no') {
                $form->rec->additional = array();
            }

            if ($form->rec->limmits == 'yes') {
                if (is_array($details->code)) {
                    $maxPost = ini_get('max_input_vars') - self::MAX_POST_ART;

                    $arts = countR($details->code);
                    $form->rec->inputArts = $arts;

                    if ($arts > $maxPost) {
                        $form->setError('droupId', 'Лимитът за следени продукти е достигнат.
                            За да добавите нов артикул трябва да премахнете поне един от вече включените. ');
                    }

                    foreach ($details->code as $v) {
                        $v = trim($v);

                        if (!$v) {
                            $form->setError('additional', 'Не попълнен код на артикул');
                        } else {
                            if (!cat_Products::getByCode($v)) {
                                $form->setError('additional', 'Не съществуващ артикул с код: ' . $v);
                            }
                        }
                    }

                    if (is_array($details->minQuantity)) {
                        foreach ($details->minQuantity as $v) {
                            $v = trim($v);

                            if ($v < 0) {
                                $form->setError('additional', 'Количествата трябва  да са положителни');
                            }
                        }
                    }

                    if (is_array($details->maxQuantity)) {
                        foreach ($details->maxQuantity as $v) {
                            $v = trim($v);

                            if ($v < 0) {
                                $form->setError('additional', 'Количествата трябва  да са положителни');
                            }
                        }
                    }

                    foreach ($details->code as $key => $v) {
                        if ($details->minQuantity[$key] && $details->maxQuantity[$key]) {
                            if ((double)$details->minQuantity[$key] > (double)$details->maxQuantity[$key]) {
                                $form->setError('additional', 'Максималното количество не може да бъде по-малко от минималното');
                            }
                        }
                    }

                    $grDetails = (array)$details;

                    foreach ($grDetails['name'] as $k => $detail) {
                        if (!$detail && $grDetails['code'][$k]) {
                            $prId = cat_Products::getByCode($grDetails['code'][$k]);

                            if ($prId->productId) {
                                $measureName = cat_UoM::getTitleById(cat_Products::fetchField($prId->productId, 'measureId'));

                                $prName = $measureName . ' | ' . cat_Products::fetchField($prId->productId, 'name');

                                $grDetails['name'][$k] = $prName;

                                $grDetails['measure'][$k] = $measureName;
                            }
                        }
                    }

                    $jDetails = json_encode(self::removeRpeadValues($grDetails));

                    $form->rec->additional = $jDetails;
                }
            }
        } else {
            $rec = $form->rec;

            if ($form->rec->limmits == 'no') {
                $form->rec->additional = array();
                $rec->groupsChecked = $rec->groupId;
            }

            if ($form->rec->limmits == 'yes') {
                if ($form->cmd == 'refresh' && $rec->groupId) {

                    $maxPost = ini_get('max_input_vars') - self::MAX_POST_ART;

                    $arts = countR($details->code);

                    $form->rec->inputArts = $arts;

                    $grInArts = cat_Groups::fetch($rec->groupId)->productCnt;

                    $groupName = cat_Products::getTitleById($rec->groupId);

                    $prodForCut = ($arts + $grInArts) - $maxPost;

                    $numbersOfItemsToAdd = (ini_get('max_input_vars') / 4) - (51 + $arts);

                    if ((($arts + $numbersOfItemsToAdd) * 4) > $maxPost) {
                        $form->setError('droupId', "Лимита за следени продукти е достигнат.
                            За да добавите група \" ${groupName}\" трябва да премахнете ${prodForCut} артикула ");
                    } else {

                        // Добавя цяла група артикули

                        $rQuery = cat_Products::getQuery();
                        if (!$rec->groupsChecked){
                            $rec->groupsChecked = $rec->groupId;
                        }else{
                            $rec->groupsChecked .= ','.$rec->groupId;
                        }

                        $details = (array)$details;

                        $rQuery->where("#groups Like'%|{$rec->groupId}|%'");

                        while ($grProduct = $rQuery->fetch()) {
                            $measureName = cat_UoM::getTitleById(cat_Products::fetchField($grProduct->id, 'measureId'));

                            $grDetails['code'][] = $grProduct->code;

                            $grDetails['name'][] = $measureName . ' | ' . cat_Products::fetchField($grProduct->id, 'name');

                            $grDetails['measure'][] = $measureName;

                            $grDetails['minQuantity'][] = $grProduct->minQuantity;

                            $grDetails['maxQuantity'][] = $grProduct->maxQuantity;
                        }

                        // Премахва артикули ако вече са добавени
                        if (is_array($grDetails['code'])) {
                            foreach ($grDetails['code'] as $k => $v) {
                                if ($details['code'] && in_array($v, $details['code'])) {
                                    unset($grDetails['code'][$k]);
                                    unset($grDetails['name'][$k]);
                                    unset($grDetails['measure'][$k]);
                                    unset($grDetails['minQuantity'][$k]);
                                    unset($grDetails['maxQuantity'][$k]);
                                }
                            }
                        }

                        // Премахване на нестандартнитв артикули
                        if (is_array($grDetails['name'])) {
                            foreach ($grDetails['name'] as $k => $v) {
                                if ($grDetails['code'][$k]) {
                                    $isPublic = (cat_Products::fetch(cat_Products::getByCode($grDetails['code'][$k])->productId)->isPublic);
                                }

                                if (!$grDetails['code'][$k] || $isPublic == 'no') {
                                    unset($grDetails['code'][$k]);
                                    unset($grDetails['name'][$k]);
                                    unset($grDetails['measure'][$k]);
                                    unset($grDetails['minQuantity'][$k]);
                                    unset($grDetails['maxQuantity'][$k]);
                                }
                            }
                        }

                        // Ограничава броя на артикулите за добавяне
                        $count = 0;
                        $countUnset = 0;

                        if (is_array($grDetails['code'])) {
                            foreach ($grDetails['code'] as $k => $v) {
                                $count++;

                                if ($count > self::NUMBER_OF_ITEMS_TO_ADD) {
                                    unset($grDetails['code'][$k]);
                                    unset($grDetails['name'][$k]);
                                    unset($grDetails['measure'][$k]);
                                    unset($grDetails['minQuantity'][$k]);
                                    unset($grDetails['maxQuantity'][$k]);
                                    $countUnset++;
                                    continue;
                                }

                                $details['code'][] = $grDetails['code'][$k];
                                $details['name'][] = $grDetails['name'][$k];
                                $details['measure'][] = $grDetails['measure'][$k];
                                $details['minQuantity'][] = $grDetails['minQuantity'][$k];
                                $details['maxQuantity'][] = $grDetails['maxQuantity'][$k];
                            }

                            if ($countUnset > 0) {
                                $groupName = cat_Groups::getTitleById($rec->groupId);
                                $maxArt = self::NUMBER_OF_ITEMS_TO_ADD;

                                $form->setWarning('groupId', "${countUnset} артикула от група ${groupName} няма да  бъдат добавени.
                                    Максимален брой артикули за еднократно добавяне - ${maxArt}.
                                    Може да добавите още артикули от групата при следваща редакция.");
                            }
                        }

                        $jDetails = json_encode($details);

                        $form->rec->additional = $jDetails;
                    }
                }
            }
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
        $recs = $storesQuatity = array();

        $codes = array();

        if (is_string($rec->additional)) {
            $additional = json_decode($rec->additional, false);
        } else {
            $additional = (object)$rec->additional;
        }

        $minQuantity = $maxQuantity = array();

        // Подготвяме заявката за извличането на записите от store_Products

        $sQuery = store_Products::getQuery();

        $sQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $sQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $sQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        if ($rec->limmits == 'no') {
            // Филтриране по група продукти
            $sQuery->where("#groups LIKE '%|{$rec->groupId}|%'");
        } else {
            // Филтриране по кодове
            if (is_array($additional->code)) {
                foreach ($additional->code as $rowId => $code) {
                    $code = mb_strtolower($code);
                    $codes[$code] = $code;
                    $minQuantity[$code] = $additional->minQuantity[$rowId];
                    $maxQuantity[$code] = $additional->maxQuantity[$rowId];
                }
                $codeList = '|' . implode('|', $codes) . '|';
            }

            $sQuery->where(array("'[#1#]' LIKE CONCAT('%|', LOWER(COALESCE(#code, CONCAT('Art', #id))), '|%')", $codeList));
        }

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

                //  $quantity = $recProduct->quantity - $recProduct->reservedQuantity + $recProduct->expectedQuantity;
            } else {
                // Гледаме наличното количество
                $quantity = $recProduct->quantity;
            }


            $stKey = $productId . '|' . $recProduct->storeId;
            $storesQuatity[$stKey] += $quantity;


            if ($obj = &$recs[$productId]) {
                $obj->quantity += $quantity;
            } else {
                $key = mb_strtolower($recProduct->code);

                if (is_string($minQuantity[$key]) && strpos($minQuantity[$key], ',')) {
                    $pos = strpos($minQuantity[$key], ',');
                    $minQuantity[$key][$pos] = '.';
                }

                if (is_string($maxQuantity[$key]) && strpos($maxQuantity[$key], ',')) {
                    $pos = strpos($maxQuantity[$key], ',');
                    $maxQuantity[$key][$pos] = '.';
                }

                $recs[$productId] = (object)array(
                    'measure' => $recProduct->measureId,
                    'productId' => $productId,
                    'storesQuatity' => '',
                    'quantity' => $quantity,
                    'minQuantity' => $minQuantity[$key],
                    'maxQuantity' => $maxQuantity[$key],
                    'code' => $recProduct->code,
                );
            }
        }

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

        if ($rec->limmits == 'yes') {
            $fld->FLD('minQuantity', 'double(smartRound,decimals=2)', 'caption=Минимално,smartCenter');
            $fld->FLD('maxQuantity', 'double(smartRound,decimals=2)', 'caption=Максимално,smartCenter');
            $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
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
                                    </div>
                                
                                 </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->groupsChecked)) {
            $marker = 0;
            $arr = explode(',',$data->rec->groupsChecked);
            foreach ($arr as $group) {
                $marker++;

                $groupVerb .= cat_Groups::fetch($group)->name;

                if ((countR($arr)) - $marker != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groupsChecked');
        }

      //  if ($data->rec->limmits == 'no') {
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

        if ($data->rec->limmits == 'yes') {
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

        $tableData = (array) $tableData;
        if (empty($tableData)) {

            return;
        }

        $res = $error = $errorFields = array();

        foreach ($tableData['minQuantity'] as $key => $minQuantity) {

            if(!empty($minQuantity)){
                $Double = core_Type::getByName('double');
                $q2 = $Double->fromVerbal($minQuantity);
                if (!$q2) {
                    $error[] = 'Невалидна стойност';
                    $errorFields['minQuantity'][$key] = 'Невалидна стойност';
                }

            }
        }

        foreach ($tableData['maxQuantity'] as $key => $maxQuantity) {

            if(!empty($maxQuantity)){
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
        return ($rec->limmits == 'yes') ? 'productId,conditionQuantity' : 'productId,quantity';
    }
}
