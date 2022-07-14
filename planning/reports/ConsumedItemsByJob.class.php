<?php


/**
 * Мениджър на отчети за вложени артикули по задания
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Вложени артикули по задания
 */
class planning_reports_ConsumedItemsByJob extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, debug, acc, planning';


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'code,name, consumedQuantity,consumedAmount,returnedQuantity,returnedAmount,totalAmount,totalQuantity';


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'consumedQuantity,consumedAmount,returnedQuantity,returnedAmount,totalAmount,totalQuantity';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


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
    protected $changeableFields = 'jobses, from, to, groups';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {


        //Център на дейност
        $fieldset->FLD('department', 'keylist(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Ц-р дейност,after=title,placeholder=Всички,removeAndRefreshForm,silent');

        //Задания
        $fieldset->FLD('jobses', 'keylist(mvc=planning_Jobs,allowEmpty)', 'caption=Задания,placeholder=Всички активни,after=department,single=none');

        //Да има ли филтър по артикул
        $fieldset->FLD('option', 'enum(yes=Включен,no=Изключен)', 'caption=Артикули по задание->Филтър по артикул,after=jobses,removeAndRefreshForm,silent');

        //Артикули
        $fieldset->FLD('products', 'keylist(mvc=cat_Products,select=name)', 'caption=Артикули по задание->Артикул,placeholder=Всички,after=option,single=none,input=none,class=w100');


        //Групи артикули
        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Вложени артикули->По групи,placeholder = Всички,after=products,single=none');
        } else {
            $fieldset->FLD('groups', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Вложени артикули->По групи,placeholder = Всички,after=products,single=none');
        }

        //Период
        $fieldset->FLD('from', 'date', 'caption=От,after=groups,removeAndRefreshForm,single=none,silent,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,removeAndRefreshForm,single=none,silent,mandatory');

        //Групиране на резултатите
        $fieldset->FLD('groupBy', 'enum(no=Без групиране,jobId=Задание,jobArt=Артикул в заданието, valior=Дата,mounth=Месец,year=Година)', 'caption=Групиране по,after=to,single=none,refreshForm,silent');


        //По кои цени да се смятат себестойностите
        $fieldset->FLD('pricesType', 'enum(selfPrice=Политика "Себестойност",catalog=Политика "Каталог", accPrice=Счетоводна )', 'caption=Себестойности->Стойност по,after=groupBy,single=none,silent');


        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(valior=Дата,name=Артикул, code=Код,consumedQuantity=Вложено количество,returnedQuantity=Върнато количество,totalQuantity=Резултат количество,totalAmount=Резултат стойност)', 'caption=Подреждане->Показател,after=pricesType,single=none,silent');
        $fieldset->FLD('orderType', 'enum(asc=Нарастващо,desc=Намаляващо)', 'caption=Подреждане->Подреждане,after=orderBy,single=none,silent');

        $fieldset->FLD('seeAmount', 'set(yes = )', 'caption=Покажи стойности,after=orderType,single=none');


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

        if ($rec->option == 'yes') {
            $form->setField('products', 'input');
            $form->setField('jobses', 'input=none');
            $form->setField('department', 'input=none');
        }


        $form->setDefault('orderBy', 'code');
        $form->setDefault('option', 'no');
        $form->setDefault('groupBy', 'no');
        $form->setDefault('orderType', 'desc');
        $form->input('seeAmount');


        $suggestions = array();
        foreach (keylist::toArray($rec->jobses) as $val) {
            $suggestions[$val] = planning_Jobs::getTitleById($val);
        }

        $stateArr = array('active', 'wakeup', 'closed');

        $jQuery = planning_Jobs::getQuery();
        $jQuery->in('state', $stateArr);
        $jQuery->show('productId');
        while ($jRec = $jQuery->fetch()) {
            if (!array_key_exists($jRec->id, $suggestions)) {
                $suggestions[$jRec->id] = planning_Jobs::getTitleById($jRec->id);
            }
        }

        asort($suggestions);

        $form->setSuggestions('jobses', $suggestions);

        //Когато е избрано 'по артикули'зареждаме за избор само онези артикули, които имат задания през периода
        if ($rec->option == 'yes') {

            $jQuery = planning_Jobs::getQuery();

            $jQuery->in('state', $stateArr);

            if ($rec->department) {
                $jQuery->in('department', keylist::toArray($rec->department));
            }

            $jQuery->show('productId');
            $prodArr = arr::extractValuesFromArray($jQuery->fetchAll(), 'productId');
            if (!empty($prodArr)) {
                foreach ($prodArr as $val) {
                    $prodSuggestions[$val] = cat_Products::getTitleById($val);
                }
            } else {
                $rec->products = null;
                $prodSuggestions = array('' => '');
            }
            $form->setSuggestions('products', $prodSuggestions);
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
        //Показването да бъде ли ГРУПИРАНО
        if ($rec->groupBy != 'no') {
            $this->groupByField = $rec->groupBy;
        }

        $recs = array();

        if ($rec->option != 'yes') {

            $jobsThreadArr = $jobsContainersArr = array();

            //Ако има избран център на дейност филтрираме заданията по избраните центрове на дейност
            if ($rec->department || $rec->jobses) {

                $plQuery = planning_Jobs::getQuery();
                if ($rec->department) {
                    $plQuery->in('department', keylist::toArray($rec->department));
                }

                if ($rec->jobses && $plQuery->count() > 0) {
                    $plQuery->in('id', keylist::toArray($rec->jobses));

                    while ($plRec = $plQuery->fetch()) {
                        $jobsThreadArr[$plRec->id] = $plRec->threadId;
                        $jobsContainersArr[$plRec->id] = $plRec->containerId;
                    }

                    if (!empty($jobsContainersArr)) {
                        $tQuery = planning_Tasks::getQuery();
                        $tQuery->where("#state != 'rejected'");
                        $tQuery->in('originId', $jobsContainersArr);
                        while ($tRec = $tQuery->fetch()) {
                            if (in_array($tRec->threadId, $jobsThreadArr)) {
                                continue;
                            }
                            $jobsThreadArr[$tRec->originId] = $tRec->threadId;
                        }
                    }

                }
                if (empty($jobsThreadArr)) {
                    while ($plRec = $plQuery->fetch()) {
                        $jobsThreadArr[$plRec->id] = $plRec->threadId;
                    }
                }
                if (empty($jobsThreadArr)) return $recs;
            }

        }
        //Вложени и върнати артикули в нишките на заданията

        $mvcArr = array('planning_DirectProductionNote' => 'planning_DirectProductNoteDetails',
            'planning_ReturnNotes' => 'planning_ReturnNoteDetails',
            'planning_ConsumptionNotes' => 'planning_ConsumptionNoteDetails'
        );
        foreach ($mvcArr as $master => $details) {


            //Вложени и върнати артикули по протоколи, които са в нишките на избраните задания
            $pQuery = $details::getQuery();

            $pQuery->EXT('valior', "${master}", 'externalName=valior,externalKey=noteId');
            $pQuery->EXT('state', "${master}", 'externalName=state,externalKey=noteId');
            if ($master == 'planning_DirectProductionNote') {
                $pQuery->EXT('inputStoreId', "${master}", 'externalName=inputStoreId,externalKey=noteId');
            }

            $pQuery->EXT('threadId', "${master}", 'externalName=threadId,externalKey=noteId');
            $pQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
            $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $pQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');


            $pQuery->where(array("#valior >= '[#1#]' AND #valior <= '[#2#]'", $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'));


            $pQuery->where("#state != 'rejected'");
            $pQuery->where("#canStore != 'no'");

            //Ако има избрани задания или центрове на дейност вадим само от техните нишки
            if (!empty($jobsThreadArr)) {
                $pQuery->in('threadId', ($jobsThreadArr));
            }

            //Филтър по група артикули
            if (isset($rec->groups)) {
                $pQuery->likeKeylist('groups', $rec->groups);
            }

            // Синхронизира таймлимита с броя записи
            $timeLimit = $pQuery->count() * 0.05;

            if ($timeLimit >= 30) {
                core_App::setTimeLimit($timeLimit);
            }

            while ($pRec = $pQuery->fetch()) {

                if ($master == 'planning_DirectProductionNote' && !$pRec->storeId) {

                    continue;
                }

                $consumedQuantity = $returnedQuantity = $pRec->quantity;

                if ($master == 'planning_ReturnNotes') {
                    $consumedQuantity = 0;
                } else {
                    $returnedQuantity = 0;
                }
                $code = $pRec->code ? $pRec->code : 'Art' . $pRec->productId;

                $name = cat_Products::fetch($pRec->productId)->name;

                $jobId = doc_Threads::getFirstDocument($pRec->threadId)->that;
                $jobProductId = planning_Jobs::fetch($jobId)->productId;
                if ($rec->option == 'yes' && $rec->products) {

                    $checkedProds = keylist::toArray($rec->products);
                    if (!in_array($jobProductId, $checkedProds)) continue;

                }
                list($year, $mounth) = explode('-', $pRec->valior);

                if ($rec->groupBy) {
                    switch ($rec->groupBy) {

                        case 'jobId':
                            $secondPartKey = $jobId;
                            break;

                        case 'jobArt':
                            $secondPartKey = $jobProductId;
                            break;

                        case 'valior':
                            $secondPartKey = $pRec->valior;
                            break;

                        case 'mounth':
                            $secondPartKey = $mounth;
                            break;

                        case 'year':
                            $secondPartKey = $year;
                            break;

                    }

                    $id = $pRec->productId . '|' . $secondPartKey;
                } else {
                    $id = $pRec->productId;
                }

                //Себестойност на артикула
                $selfPrice = self::getProductPrice($pRec, $master, $rec->pricesType);

                // Запис в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object)array(

                        'jobId' => $jobId,                                                         //Id на заданието
                        'jobArt' => $jobProductId,                                           // Продукта по заданието
                        'valior' => $pRec->valior,
                        'mounth' => $mounth,
                        'year' => $year,
                        'productId' => $pRec->productId,                                           //Id на артикула
                        'code' => $code,                                                           //код на артикула
                        'name' => $name,                                                           //Име на артикула
                        'selfPrice' => $selfPrice,                                                 //Себестойност на артикула
                        'consumedQuantity' => $consumedQuantity,                                   //Вложено количество
                        'consumedAmount' => $consumedQuantity * $selfPrice,                          //Стойност на вложеното количество

                        'returnedQuantity' => $returnedQuantity,                                   //Върнато количество
                        'returnedAmount' => $returnedQuantity * $selfPrice,                          //Стойност на върнатото количество

                        'totalQuantity' => '',
                        'totalAmount' => '',

                    );
                } else {
                    $obj = &$recs[$id];

                    $obj->consumedQuantity += $consumedQuantity;
                    $obj->consumedAmount += $consumedQuantity * $selfPrice;

                    $obj->returnedQuantity += $returnedQuantity;
                    $obj->returnedAmount += $returnedQuantity * $selfPrice;
                }
            }
        }
        foreach ($recs as $key => $val) {
            $val->totalQuantity = $val->consumedQuantity - $val->returnedQuantity;
            $val->totalAmount = ($val->consumedAmount - $val->returnedAmount);
        }

        //Подредба на резултатите
        if (!is_null($recs)) {
            $order = ($rec->orderBy == 'name' || $rec->orderBy == 'code') ? 'stri' : 'native';
            $orderType = $rec->orderType;
            $orderBy = $rec->orderBy;

            arr::sortObjects($recs, $orderBy, $orderType, $order);
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
        if ($export === false) {
            $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
            $fld->FLD('name', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');

            $fld->FLD('consumedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Вложено->Количество');
            if (!is_null($rec->seeAmount)) {
                $fld->FLD('consumedAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Вложено->Стойност');
            }
            $fld->FLD('returnedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Върнато->Количество');
            if (!is_null($rec->seeAmount)) {
                $fld->FLD('returnedAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Върнато->Стойност');
            }

            $fld->FLD('totalQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Резултат->Количество');
            if (!is_null($rec->seeAmount)) {
                $fld->FLD('totalAmount', 'double(smartRound,decimals=2)', 'caption=Резултат->Стойност');
            }
        } else {
            $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
            $fld->FLD('name', 'varchar', 'caption=Артикул');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('consumedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Вложено->Количество');
            if (!is_null($rec->seeAmount)) {
                $fld->FLD('consumedAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Вложено->Стойност');
            }
            $fld->FLD('returnedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Върнато->Количество');
            if (!is_null($rec->seeAmount)) {
                $fld->FLD('returnedAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Върнато->Стойност');
            }

            $fld->FLD('totalQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Резултат->Количество');
            if (!is_null($rec->seeAmount)) {
                $fld->FLD('totalAmount', 'double(smartRound,decimals=2)', 'caption=Резултат->Стойност');
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
        $Double->params['decimals'] = 2;
        $Date = cls::get('type_Date');

        $row = new stdClass();

        if ($rec->groupBy == 'valior') {
            $row->valior = $Date->toVerbal($dRec->valior);
        }

        if ($rec->groupBy == 'mounth') {
            $row->mounth = $dRec->mounth . '-' . $dRec->year;
        }

        if ($rec->groupBy == 'year') {
            $row->year = $dRec->year;
        }

        if ($rec->groupBy == 'jobId') {
            $row->jobId = planning_Jobs::getLinkToSingle($dRec->jobId);
        }

        if ($rec->groupBy == 'jobArt') {
            $row->jobArt = cat_Products::getLinkToSingle($dRec->jobArt, 'name');
        }

        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $row->name = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }

        $row->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');


        if (isset($dRec->consumedQuantity)) {
            $row->consumedQuantity = $Double->toVerbal($dRec->consumedQuantity);
        }
        if (isset($dRec->consumedAmount)) {
            $row->consumedAmount = $Double->toVerbal($dRec->consumedAmount);
        }


        if (isset($dRec->returnedQuantity)) {
            $row->returnedQuantity = $Double->toVerbal($dRec->returnedQuantity);
        }
        if (isset($dRec->returnedAmount)) {
            $row->returnedAmount = $Double->toVerbal($dRec->returnedAmount);
        }


        $row->totalQuantity = $Double->toVerbal($dRec->totalQuantity);

        $row->totalAmount = $Double->toVerbal($dRec->totalAmount);

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
        $Enum = cls::get('type_Enum', array('options' => array('selfPrice' => 'политика"Себестойност"', 'catalog' => 'политика"Каталог"', 'accPrice' => 'Счетоводна')));
        $currency = 'лв.';

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN jobses--><div>|Избрани задания|*: [#jobses#]</div><!--ET_END jobses-->
                                        <!--ET_BEGIN groups--><div>|Групи продукти|*: [#groups#]</div><!--ET_END groups-->
                                        <!--ET_BEGIN products--><div>|Артикули|*: [#products#]</div><!--ET_END products-->
                                        <!--ET_BEGIN pricesType--><div>|Стойност|*: [#pricesType#]</div><!--ET_END pricesType-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }

        if (isset($data->rec->pricesType)) {
            $fieldTpl->append('<b>' . $Enum->toVerbal($data->rec->pricesType) . '</b>', 'pricesType');
        }

        $marker = 0;
        if (isset($data->rec->groups)) {
            foreach (type_Keylist::toArray($data->rec->groups) as $group) {
                $marker++;

                $groupVerb .= (cat_Groups::getTitleById($group));

                if ((countR((type_Keylist::toArray($data->rec->groups))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groups');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'groups');
        }

        $marker = 0;
        if ($data->rec->option == 'yes') {
            if (isset($data->rec->products)) {
                foreach (type_Keylist::toArray($data->rec->products) as $product) {
                    $marker++;

                    $productVerb .= (cat_Products::getTitleById($product));

                    if ((countR((type_Keylist::toArray($data->rec->products))) - $marker) != 0) {
                        $productVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $productVerb . '</b>', 'products');
            } else {
                $fieldTpl->append('<b>' . 'Всички' . '</b>', 'products');
            }
        }

        $marker = 0;
        if ($data->rec->option == 'no') {
            if (isset($data->rec->jobses)) {
                foreach (type_Keylist::toArray($data->rec->jobses) as $job) {
                    $marker++;

                    $jRec = planning_Jobs::fetch($job);

                    $jContainer = $jRec->containerId;

                    $Job = doc_Containers::getDocument($jContainer);

                    $handle = $Job->getHandle();

                    $singleUrl = $Job->getUrlWithAccess($Job->getInstance(), $job);

                    $jobVerb .= ht::createLink("#{$handle}", $singleUrl);

                    if ((countR((type_Keylist::toArray($data->rec->jobses))) - $marker) != 0) {
                        $jobVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $jobVerb . '</b>', 'jobses');
            } else {
                $fieldTpl->append('<b>' . 'Всички' . '</b>', 'jobses');
            }
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

        $res->name = cat_Products::fetch($dRec->productId)->name;
        $res->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');
    }


    /**
     * Подава цена на артикула според избания тип цени
     * само за нуждите на тази справка
     *
     */
    private static function getProductPrice($pRec, $master, $priceType)
    {
        if ($priceType == 'accPrice') {
            $docTypeId = core_Classes::getId($master);
            $resonId = acc_Operations::getIdByTitle('Влагане на материал в производството');

            if (!$masterJurnalId = acc_Journal::fetch("#docType = ${docTypeId} AND #docId = {$pRec->noteId}")->id) return;
            //$masterJurnalId = acc_Journal::fetch("#docType = ${docTypeId} AND #docId = {$pRec->noteId}")->id;

            $jdQuery = acc_JournalDetails::getQuery();

            $jdQuery->where("#journalId = ${masterJurnalId} AND #reasonCode = ${resonId}");


            while ($jdRec = $jdQuery->fetch()) {
                $prodJournalId = acc_Items::fetch($jdRec->creditItem2)->objectId;

                if ($pRec->productId == $prodJournalId) {

                    return $jdRec->creditPrice;
                }
            }
        }

        if ($priceType == 'selfPrice') {
            $primeCostlistId = price_ListRules::PRICE_LIST_COST;

            $date = price_ListToCustomers::canonizeTime($pRec->valior);

            $primeCost = price_ListRules::getPrice($primeCostlistId, $pRec->productId, $pRec->packagingId, $date);

            return $primeCost;
        }

        if ($priceType == 'catalog') {
            $primeCostlistId = price_ListRules::PRICE_LIST_CATALOG;

            $date = price_ListToCustomers::canonizeTime($pRec->valior);

            $catalogCost = price_ListRules::getPrice($primeCostlistId, $pRec->productId, $pRec->packagingId, $date);

            return $catalogCost;
        }
    }
}
