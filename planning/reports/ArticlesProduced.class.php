<?php


/**
 * Мениджър на отчети за произведени артикули
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Произведени артикули
 */
class planning_reports_ArticlesProduced extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, planning, acc';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from, to, duration, compare, compareStart, seeCrmGroup, seeGroup, group, groups, groupBy, orderBy, consumed, groupsMat, dealers, contragent, crmGroup, articleType, orderBy, grouping, updateDays, updateTime';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        //Период
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');

        //accProd Функционалност
        $fieldset->FLD('accProd', 'enum(yes=ДА, no=НЕ)', 'caption=Справка по accProd,after=to,removeAndRefreshForm,silent,single=none');


        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Произведени артикули->Групи артикули,after=accProd,removeAndRefreshForm,placeholder=Всички,silent,single=none');


        //Групиране на резултата
        $fieldset->FLD('groupBy', 'enum(no=Без групиране, department=Център на дейност,storeId=Склад,month=По месеци)', 'notNull,caption=Групиране и подреждане->Групиране,after=groups');


        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(code=Код,name=Артикул,quantity=Количество)', 'caption=Групиране и подреждане->Подреждане по,after=groupBy');

        $fieldset->FLD('consumed', 'enum(no=НЕ, yes=ДА)', 'caption=Вложени материали->Покажи вложените материали,removeAndRefreshForm,after=orderBy,silent');
        $fieldset->FLD('consumedFrom', 'enum(protocols= протоколи, boms= рецепти)', 'caption=Вложени материали->Вложени по,removeAndRefreshForm,after=consumed,input=hidden,silent,single=none');
        //Групи артикули
        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('groupsMat', 'keylist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Вложени материали->Група артикули,placeholder = Всички,after=consumedFrom,single=none,input=hidden');
        } else {
            $fieldset->FLD('groupsMat', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Вложени материали->Група артикули,placeholder = Всички,after=consumed,single=none,input=hidden');
        }


        $fieldset->FNC('montsArr', 'varchar', 'caption=Месеци по,after=groupsMat,input=hiden,single=none');
        $fieldset->FNC('totalConsumed', 'varchar', 'caption=Обща стойност на вложените материали,after=montsArr,input=hiden,single=none');


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
        if ($form->isSubmitted()) {
            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('groupBy', 'no');
        $form->setDefault('orderBy', 'code');
        $form->setDefault('totalConsumed', null);
        $form->setDefault('consumedFrom', 'protocols');
        $form->setDefault('accProd', 'no');

        if (!core_Packs::isInstalled('extrapack')) {
            $form->setField('accProd', 'input=hidden');
        }
        if ($rec->accProd == 'yes') {
            $form->setField('orderBy', 'input=hidden');
            $form->setField('groupBy', 'input=hidden');
            $form->setField('consumed', 'input=hidden');
        }

        if ($rec->consumed == 'yes') {
            $form->setField('groupsMat', 'input');
            $form->setField('consumedFrom', 'input');
            // $form->setField('groups', 'input=hidden');
            $form->setField('groupBy', 'input=hidden');
            $form->setOptions('orderBy', array('code' => 'Код'));


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
        if ($rec->groupBy != 'no' && $rec->consumed != 'yes') {
            $this->groupByField = $rec->groupBy;
        }
        $recs = $consumedItems = array();

        //Произведени артикули
        $planningQuery = planning_DirectProductionNote::getQuery();

        $planningQuery->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        $planningQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $planningQuery->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');

        $planningQuery->where("#state = 'active'");

        //Филтриране на периода
        $planningQuery->where(array(
            "#valior >= '[#1#]' AND #valior <= '[#2#]'",
            $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'));

        //Филтър по групи артикули
        if ($rec->groups) {

            $planningQuery->likeKeylist('groupMat', $rec->groups);

        }

        $montArr = $monthQuantityArr = array();
        $pDpClassId = planning_DirectProductionNote::getClassId();
        $debitAccId = acc_Accounts::fetch("#num = 321")->id;

        $amountTotal = array();

        while ($planningRec = $planningQuery->fetch()) {

            $month = substr($planningRec->valior, 0, 7);
            if (!in_array($month, $montArr)) {

                array_push($montArr, $month);
            }

            //Задание за производство към което е протокола за производство
            $Document = doc_Containers:: getDocument($planningRec->originId);

            $className = $Document->className;

            //Ако протокола за производство е към задача вземаме заданието от което е направена задачата
            if ($className != 'planning_Jobs') {
                $taskRec = $className::fetch($Document->that);

                $Document = doc_Containers:: getDocument($taskRec->originId);

                $className = $Document->className;
            }

            //Център на дейност
            $departmentId = $className::fetch($Document->that)->department;

            //Склад на заприхождаване
            $storeId = $planningRec->storeId;

            //Вложени материали
            if ($rec->consumed == 'yes') {
                $dpRecDetArr = array();

                //Ако е избрана опция за вложените материали по ПРОТОКОЛИ за производство
                if ($rec->consumedFrom == 'protocols') {
                    $query = acc_Journal::getQuery();
                    $dpRec = $query->fetch("#docType = $pDpClassId AND #docId = $planningRec->id AND #state = 'active' ");
                    $dpQuery = acc_JournalDetails::getQuery();
                    $dpQuery->where("#journalId = $dpRec->id AND #debitAccId = $debitAccId");

                    while ($dpRecDet = $dpQuery->fetch()) {
                        unset($amount, $quantity, $matRec, $matItemRec, $matClassName);

                        if ($dpRecDet->creditItem1) {
                            $matItemRec = acc_Items::fetch($dpRecDet->creditItem1);
                            $matClassName = core_Classes::fetch($matItemRec->classId)->name;

                            //rec-а на вложения материал
                            $matRec = $matClassName::fetch($matItemRec->objectId);

                            $id = $planningRec->productId . '|' . $matRec->id;
                        } else {
                            $id = $planningRec->productId . '|' . 'distrib';
                        }
                        $dpRecDetArr[$id] = (object)array('dpRecDet' => $dpRecDet,
                            'matRec' => $matRec);
                    }
                }

                $bommMaterials = array();
                //Ако е избрана опция за вложените материали по РЕЦЕПТИ
                if ($rec->consumedFrom == 'boms') {
                    $lastActivBomm = cat_Products::getLastActiveBom($planningRec->productId);
                    $arr = $arr1 = array();
                    if ($lastActivBomm) {

                        //Вложени материали по рецепта (някои може да са заготовки т.е. да имат рецепти за влагане
                        // на по низши материали или заготовки)
                        $bommMaterials = self::getBaseMaterialFromBoms($lastActivBomm, $arr, $arr1);

                    } else {
                        continue;
                    }

                    // Масив артикули и количество необходими за изпълнение на заданията //
                    foreach ($bommMaterials as $material) {

                        $id = $planningRec->productId . '|' . $material->productId;

                        $jobsQuantityMaterial = (double)$material->quantity;

                        if (!array_key_exists($id, $dpRecDetArr)) {
                            $dpRecDetArr[$id] = (object)array(

                                'productId' => $material->productId,

                                'quantity' => $jobsQuantityMaterial
                            );
                        } else {
                            $obj = &$dpRecDetArr[$id];

                            $obj->quantity += $jobsQuantityMaterial;
                        }
                    }
                }

                foreach ($dpRecDetArr as $id => $val) {

                    if ($rec->consumedFrom == 'protocols') {
                        $matRec = $val->matRec;
                        $quantity = $val->dpRecDet->creditQuantity;
                        $amount = $val->dpRecDet->amount;

                    } else {
                        $matRec = cat_Products::fetch($val->productId);

                        $quantity = $val->quantity;
                        $amount = cat_Products::getWacAmountInStore(1, $val->productId, $planningRec->valior);

                    }

                    //филтър по група на вложеното
                    if ($rec->groupsMat) {

                        //Ако има избрани групи материали филтрираме само тях
                        if (!(keylist::isIn(keylist::toArray($rec->groupsMat), $matRec->groups))) continue;
                    }

                    $amountTotal[$planningRec->productId] += $amount;                          //Обща сума на вложените материали

                    // Запис в масива на материалите
                    if (!array_key_exists($id, $consumedItems)) {
                        $consumedItems[$id] = (object)array(

                            'code' => $matRec->code,                                            //Код на материала
                            'productId' => $matRec->id,                                         //Id на материала
                            'measure' => $matRec->measureId,                                    //Мярка на материала
                            'name' => $matRec->name,                                            //Име на материала
                            'storeId' => '',                                                    //Склад на заприхождаване
                            'department' => '',                                                 //Център на дейност

                            'quantity' => $quantity,                                            //Количество
                            'amount' => $amount,                                                //Стойност

                            'monthQuantity' => '',
                            'group' => $matRec->groups,                                          // В кои групи е включен материала
                            'month' => '',
                            'consumedType' => 'consum',

                        );
                    } else {
                        $obj = &$consumedItems[$id];

                        $obj->quantity += $quantity;
                        $obj->amount += $amount;

                    }

                }

            }

            $id = $planningRec->productId . '|' . '';

            //Мярка на артикула
            $measureArtId = cat_Products::fetchField($planningRec->productId, 'measureId');

            //Произведено количество
            $quantity = $planningRec->quantity;

            //Код на артикула
            $artCode = !is_null($planningRec->code) ? $planningRec->code : "Art{$planningRec->productId}";

            if ($rec->groupBy == 'month') {
                unset($this->groupByField);
                $monthQuantityArr[$planningRec->productId][$month] += $quantity;
            }

            if ($rec->accProd == 'yes') {

                //ID на параметрите
                $transportWeightId = cat_Params::force('transportWeight', 'transportWeight', 'varchar', null, '');
                $weightId = cat_Params::force('weight', 'weight', 'varchar', null, '');
                $weightKgId = cat_Params::force('weightKg', 'weightKg', 'varchar', null, '');
                $accProdParamId = cat_Params::force('accProd', 'accProd', 'varchar', null, '');

                //Масив с параметрите на артикула
                $prodParamsArr = cat_Products::getParams($planningRec->productId);

                //Параметър accProd
                list($a, $accProd) = explode('.', $prodParamsArr[$accProdParamId]);
                //$accProd = trim($a);
                $accProd = $prodParamsArr[$accProdParamId];

                //Определяне на теглото
                $prodTransportWeight = $prodParamsArr[$transportWeightId];

                $prodWeight = $prodParamsArr[$weightId] / 1000;

                $prodWeightKg = $prodParamsArr[$weightKgId];

                $prodWeight = $prodWeight ? $prodWeight : $prodWeightKg;

                $prodWeight = $prodWeight ? $prodWeight : $prodTransportWeight;

                //Ако сновната мерна единица е кг, то параметъра тегло е 1
                $prodMeasureId = cat_Products::fetch($planningRec->productId)->measureId;
                $kgMeasureId = cat_UoM::getQuery()->fetch("#name = 'килограм'")->id;
                if ($prodMeasureId == $kgMeasureId) {
                    $prodWeight = 1;
                }
                $weight = $prodWeight ? $quantity * $prodWeight : 0;
            }

            // Запис в масива на артикула
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'code' => $artCode,                                              //Код на артикула
                    'productId' => $planningRec->productId,                          //Id на артикула
                    'measure' => $measureArtId,                                      //Мярка
                    'accProd' => $accProd,                                           //accProd
                    'name' => cat_Products::getTitleById($planningRec->productId),   //Име
                    'storeId' => $storeId,                                           //Склад на заприхождаване
                    'department' => $departmentId,                                   //Център на дейност

                    'quantity' => $quantity,                                         //Текущ период - количество
                    'amount' => $amountTotal[$planningRec->productId],
                    'weight' => $weight,


                    'monthQuantity' => $monthQuantityArr[$planningRec->productId],
                    'group' => $planningRec->groupMat,                               // В кои групи е включен артикула
                    'month' => '',                                                   // месец на производство
                    'consumedType' => 'prod'


                );
            } else {
                $obj = &$recs[$id];

                $obj->quantity += $quantity;
                $obj->weight += $weight;
                $obj->amount = $amountTotal[$planningRec->productId];
                $obj->monthQuantity = $monthQuantityArr[$planningRec->productId];
            }
        }

        if ($rec->consumed == 'yes') {

            foreach ($consumedItems as $cKey => $cVal) {

                $recs[$cKey] = $cVal;

            }
        }

        ksort($recs);

        $rec->montsArr = $montArr;


        //Ако е избрана опция за accProd преработваме масива
        if ($rec->accProd == 'yes') {
            $temp = array();
            foreach ($recs as $val) {
                $id = $val->accProd;
                if (!array_key_exists($id, $temp)) {
                    $temp[$id] = (object)array(

                        'accProd' => $val->accProd,                                           //accProd
                        'weight' => $val->weight,
                    );
                } else {
                    $obj = &$temp[$id];
                    $obj->weight += $val->weight;
                }


            }
            arsort($temp);

            $recs = array();
            $recs = $temp;
        }

        //Подредба на резултатите
        if (!is_null($recs) && $rec->accProd == 'no') {
            $typeOrder = ($rec->orderBy == 'name' || $rec->orderBy == 'code') ? 'stri' : 'native';

            $orderBy = $rec->orderBy;

            if ($rec->consumed != 'yes') {
                arr::sortObjects($recs, $orderBy, 'ASC', $typeOrder);
            }

        }

        if (!empty($amountTotal && $rec->accProd == 'no')) {
            $rec->totalConsumed = array_sum($amountTotal);
        }
        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($rec->accProd == 'yes') {
            $fld->FLD('accProd', 'varchar', "caption=accProd");
            $fld->FLD('weight', 'double(smartRound,decimals=2)', "smartCenter,caption=Тегло");

            return $fld;
        }

        $text = ($rec->groupBy != 'month') ? 'Количество' : 'Общо';
        if ($rec->consumed == 'yes') {
            $fld->FLD('type', 'varchar', 'caption=тип');
        }

        $fld->FLD('code', 'varchar', 'caption=Код');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption=$text");
        if ($rec->consumed == 'yes') {
            $fld->FLD('amount', 'varchar', 'caption=Стойност,tdClass=centered');
        }
        if ($rec->groupBy != 'month') {

            $fld->FLD('department', 'key(mvc=planning_Centers,select=name)', 'caption=Център на дейност');
            $fld->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        } else {
            $monthArr = $rec->montsArr;
            sort($monthArr);

            foreach ($monthArr as $val) {
                $year = substr($val, 0, 4);
                $month = substr($val, -2);
                $months = array('01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec');

                $monthName = $months[($month)];

                $fld->FLD($val, 'double(smartRound,decimals=2)', "smartCenter,caption=${year}->${monthName}");
            }
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 4;
        $Enum = cls::get('type_Enum', array('options' => array('prod' => 'произв.', 'consum' => 'вл.')));

        $row = new stdClass();

        if ($rec->accProd == 'yes') {

            $accProd = $dRec->accProd ? $dRec->accProd : 'Няма';
            $row->accProd = $accProd;
            $row->weight = $Double->toVerbal($dRec->weight);

            return $row;


        }


        $row->type = $Enum->toVerbal($dRec->consumedType);

        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $aaa = ($dRec->consumedType == 'prod') ? true : false;
            $row->productId = cat_Products::getHyperlink($dRec->productId, $aaa);
        } else {
            $row->productId = 'Разпределени разходи';
        }
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        if (isset($dRec->storeId)) {
            $row->storeId = '';

            if ($rec->data->groupByField == 'storeId') {
                $row->storeId .= 'Склад: ';
                $row->storeId .= store_Stores::getLinkToSingle_($dRec->storeId, 'name');
            }

        }
        if ($dRec->consumedType == 'consum') {
            $row->storeId = '';
        }

        if (isset($dRec->department) && $dRec->consumedType == 'prod') {
            $row->department = '';
            if ($rec->data->groupByField == 'department') {
                $row->department .= 'Център на дейност: ';
            }

            $row->department .= planning_Centers::getLinkToSingle_($dRec->department, 'name');
        } else {
            $row->department = 'Не е посочен';
        }
        if ($dRec->consumedType == 'consum') {
            $row->department = '';
        }

        if (isset($dRec->quantity)) {
            $row->quantity = $Double->toVerbal($dRec->quantity);
        }

        if (isset($dRec->amount)) {
            $row->amount = $Double->toVerbal($dRec->amount);
        }


        if (($rec->groupBy == 'month') && (is_array($dRec->monthQuantity))) {
            foreach ($dRec->monthQuantity as $key => $val) {

                $row->$key = $Double->toVerbal($val);

            }
        }

        if ($dRec->consumedType == 'prod' && $rec->consumed == 'yes') {
            $row->ROW_ATTR['class'] = 'bold state-active';
        }


        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN groupsMat--><div>|Групи материали|*: [#groupsMat#]</div><!--ET_END groupsMat-->
                                        <!--ET_BEGIN totalConsumed--><div>|Общо вложени|*: [#totalConsumed#] лв.</div><!--ET_END totalConsumed--> 
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }

        $marker = 0;
        if ($data->rec->consumed == 'yes') {
            if (isset($data->rec->groupsMat)) {
                foreach (type_Keylist::toArray($data->rec->groupsMat) as $groupMat) {

                    $marker++;
                    $groupVerb .= (cat_Groups::fetch($groupMat)->name);

                    if ((countR((type_Keylist::toArray($data->rec->groupsMat))) - $marker) != 0) {
                        $groupVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groupsMat');
            } else {
                $fieldTpl->append('<b>' . 'Всички' . '</b>', 'groupsMat');
            }
        }

        if ($data->rec->consumed == 'yes') {
            $fieldTpl->append('<b>' . $Double->toVerbal($data->rec->totalConsumed) . '</b>', 'totalConsumed');
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
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $Enum = cls::get('type_Enum', array('options' => array('prod' => 'произв.', 'consum' => 'вл.')));

        $res->type = $Enum->toVerbal($dRec->consumedType);
    }

    /**
     * Рекурсивно извеждане на вложените материали
     *
     * @param stdClass $lastActivBomm
     * @return array $material
     *
     */

    private function getBaseMaterialFromBoms($lastActivBomm, &$arr, &$arr1)
    {

        //Вложени материали по рецепта (някои може да са заготовки т.е. да имат рецепти за влагане на по низши материали или заготовки)
        $bommMaterials = cat_Boms::getBomMaterials($lastActivBomm->id, $lastActivBomm->quantity);
        foreach ($bommMaterials as $baseMat) {
            $arr1[$baseMat->productId] = $baseMat->quantity;

        }


        foreach ($bommMaterials as $material) {
            if (cat_Products::getLastActiveBom($material->productId)) {
                $lastActivBomm = cat_Products::getLastActiveBom($material->productId);

                self::getBaseMaterialFromBoms($lastActivBomm, $arr, $arr1);

            } else {

                $id = $material->productId;

                $jobsQuantityMaterial = (double)$arr1[$lastActivBomm->productId] * $material->quantity / $lastActivBomm->quantity;

                if (!array_key_exists($id, $arr)) {
                    $arr[$id] = (object)array(
                        'productId' => $material->productId,
                        'quantity' => $jobsQuantityMaterial
                    );
                } else {
                    $obj = &$arr[$id];
                    $obj->quantity += $jobsQuantityMaterial;
                }
            }

        }

        return $arr;
    }

}
