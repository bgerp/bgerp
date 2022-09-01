<?php


/**
 * Мениджър на отчети за заработки
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Заработки
 */
class planning_reports_Workflows extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,planning,hrMaster';

    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'employees';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;


    /**
     * Максимален допустим брой записи на страница
     *
     * @var int
     */
    protected $maxListItemsPerPage = 1000;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'start,to,resultsOn,centre,assetResources,employees';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('start', 'datetime', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'datetime', 'caption=До,after=start,single=none,mandatory');

        $fieldset->FLD('centre', 'keylist(mvc=planning_Centers,select=name)', 'caption=Центрове,after=to,single=none');
        $fieldset->FLD('assetResources', 'keylist(mvc=planning_AssetResources)', 'caption=Машини,placeholder=Всички,after=centre,single=none,input=none');
        $fieldset->FLD('employees', 'keylist(mvc=crm_Persons,title=name,allowEmpty)', 'caption=Служители,placeholder=Всички,after=assetResources,single=none,input=none');

        $fieldset->FLD('typeOfReport', 'enum(full=Подробен,short=Опростен)', 'caption=Тип на отчета,after=employees,mandatory,removeAndRefreshForm,single=none');

        $fieldset->FLD('resultsOn', 'enum(arts=Артикули,users=Служители,usersMachines=Служители по машини,machines=Машини)', 'caption=Разбивка по,maxRadio=4,columns=4,after=typeOfReport,single=none');

        $fieldset->FNC('indTimeSumArr', 'blob', 'caption=Времена,input=none,single=none');
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
        $suggestions = '';

        $form->setDefault('typeOfReport', 'short');
        $form->setDefault('resultsOn', 'users');
        $form->input('typeOfReport');
        if ($rec->typeOfReport == 'short') {
            $form->setField('resultsOn', 'input=none');
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
        if ($form->isSubmitted()) {

            // Проверка на периоди
            if (isset($form->rec->start, $form->rec->to) && ($form->rec->start > $form->rec->to)) {
                $form->setError('start,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
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
        $recs = array();

        $query = planning_ProductionTaskDetails::getQuery();

        $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
        $query->EXT('folderId', 'planning_Tasks', 'externalName=folderId,externalKey=taskId');
        $query->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');

        $query->where("#state != 'rejected' ");

        // Ако е посочена начална дата на период
        if ($rec->start) {
            $query->where("(#date IS NOT NULL AND #date >= '$rec->start') OR (#date IS NULL AND #createdOn >= '$rec->start')");
        }

        //Крайна дата / 'към дата'
        $date = strtotime($rec->to);
        if ($rec->to && date('H:i:s', $date) == '00:00:00') {
            $date = date('Y:m:d 23:59:59', $date);

            $query->where("(#date IS NOT NULL AND #date <= '$date')OR (#date IS NULL AND #createdOn <= '$date')");
        } else {
            $query->where("(#date IS NOT NULL AND #date <= '$rec->to')OR (#date IS NULL AND #createdOn <= '$rec->to')");
        }

        //Филтър по център на дейност
        if ($rec->centre) {

            foreach (keylist::toArray($rec->centre) as $cent) {
                $centFoldersArr[planning_Centers::fetch($cent)->folderId] = planning_Centers::fetch($cent)->folderId;
            }
            $query->in('folderId', $centFoldersArr);
        }

        //Филтър по служители
        if ($rec->employees) {
            $query->likeKeylist('employees', $rec->employees);
        }


        //Филтър по машини
        if ($rec->assetResources) {
            $assetArr = keylist::toArray($rec->assetResources);

            $query->in('fixedAsset', $assetArr);
        }

        $indTimeSumArr = array();
        while ($tRec = $query->fetch()) {
            $id = self::breakdownBy($tRec, $rec);

            $labelQuantity = 1;
            $employees = $tRec->employees;

            $counter = ($rec->typeOfReport == 'short') ? keylist::toArray($tRec->employees) : array($id => $id);

            if ($rec->employees && $rec->typeOfReport == 'short') {
                $counter = array_intersect($counter, keylist::toArray($rec->employees));
            }

            foreach ($counter as $val) {

                $Task = doc_Containers::getDocument(planning_Tasks::fetchField($tRec->taskId, 'containerId'));

                $iRec = $Task->fetch('id,containerId,measureId,folderId,quantityInPack,indTimeAllocation,labelPackagingId,indTime,indPackagingId,totalQuantity,originId');

                $quantity = $tRec->quantity;
                $weight = round( $tRec->weight, 3);
                $crapQuantity = 0;

                //Количеството се преизчилсява според мерките за производство
                $quantityInPack = 1;
                if (isset($iRec->indPackagingId)) {
                    if ($packRec = cat_products_Packagings::getPack($tRec->productId, $iRec->indPackagingId)) {

                        $quantityInPack = $packRec->quantity;
                    }

                    $quantity = round(($tRec->quantity / $quantityInPack), 3);
                }

                $normTime = planning_ProductionTaskDetails::calcNormByRec($tRec);


                if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines' || $rec->typeOfReport == 'short') {
                    $divisor = countR(keylist::toArray($tRec->employees));
                } else {
                    $divisor = 1;
                }
                if ($rec->typeOfReport == 'short') {

                    $id = $val;

                    $labelQuantity = 1 / $divisor;

                    $employees = $val;
                    $employeesName = crm_Persons::getTitleById($val);
                }

                if ($divisor) {
                    $timeAlocation = ($tRec->indTimeAllocation == 'common') ? 1 / $divisor : 1;
                    $indTimeSum = $timeAlocation * $normTime;

                } else {
                    $indTimeSum = 0;
                }

                $pRec = cat_Products::fetch($tRec->productId, 'measureId,name');

                //Ако е брак
                if ($tRec->type == 'scrap') {
                    // $crapQuantity = round(($tRec->quantity / $quantityInPack), 3);
                    $crapQuantity = round(($tRec->quantity), 3);
                    $quantity = round(($tRec->quantity*(-1)), 3);
                    $weight = round( $tRec->weight*(-1), 3);
                    $labelQuantity = 0;
                    $indTimeSum = $indTimeSum*(-1);
                }


                // Запис в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object)array(

                        'taskId' => $tRec->taskId,
                        'originId' => $tRec->originId,
                        'detailId' => $tRec->id,
                        'type' => $tRec->type,
                        'indTime' => $normTime,
                        'indTimeSum' => $indTimeSum,
                        'indPackagingId' => $iRec->indPackagingId,
                        'quantityInPack' => $iRec->quantityInPack,
                        'employees' => $employees,
                        'employeesName' => $employeesName,
                        'assetResources' => $tRec->fixedAsset,
                        'indTimeAllocation' => $iRec->indTimeAllocation,
                        'productId' => $tRec->productId,
                        'measureId' => $pRec->measureId,

                        'quantity' => $quantity,
                        'scrap' => $crapQuantity,

                        'labelMeasure' => $iRec->labelPackagingId,
                        'labelQuantity' => $labelQuantity,

                        'weight' => $weight,
                        'indTimeSumArr' => '',

                    );
                } else {
                    $obj = &$recs[$id];

                    $obj->quantity += $quantity;
                    $obj->scrap += $crapQuantity;
                    $obj->labelQuantity += $labelQuantity;
                    $obj->indTimeSum += $indTimeSum;
                    $obj->weight += $weight;
                }
            }
        }

        if (countR($recs)) {
            arr::sortObjects($recs, 'employeesName', 'asc', 'stri');
        }

        //Когато е избран тип на справката - ПОДРОБНА
        if ($rec->typeOfReport == 'full') {
            if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines') {
                $this->groupByField = 'employees';
            }

            //Разпределяне по работници,или по машини
            foreach ($recs as $key => $val) {

                if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines') {
                    $divisor = countR(keylist::toArray($val->employees));
                    $arr = keylist::toArray($val->employees);
                } else {
                    $arr = array($val->assetResources => $val->assetResources);
                    $divisor = 1;
                }

                $clone = clone $val;

                foreach ($arr as $k => $v) {
                    unset($id);

                    if (!is_null($rec->employees) && !in_array($v, keylist::toArray($rec->employees))) {
                        continue;
                    }

                    if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines') {
                        $employeesName = crm_Persons::getTitleById($v);
                    } else {
                        $employeesName = '';
                    }

                    if ($rec->resultsOn == 'arts') {
                        $id = $val->taskId . '|' . $val->productId . '|';
                    }

                    if ($rec->resultsOn == 'machines') {
                        $id = $val->taskId . '|' . $val->productId . '|' . '|' . $val->assetResources . '|';
                    }

                    if ($rec->resultsOn == 'users') {
                        $id = $val->taskId . '|' . $val->productId . '|' . '|' . $v . '|';
                    }
                    if ($rec->resultsOn == 'usersMachines') {
                        $id = $val->taskId . '|' . $val->productId . '|' . '|' . $v . '|' . '|' . $val->assetResources;
                    }

                    $labelQuantity = $clone->labelQuantity;
                    if ($divisor) {
                        $timeAlocation = ($clone->indTimeAllocation == 'common') ? 1 / $divisor : 1;
                        $indTimeSum = $timeAlocation * $clone->indTime;
                        if ($clone->type == 'input') {
                            $labelQuantity = 1;
                        }
                    } else {
                        $indTimeSum = 0;
                    }

                    $indTimeSum = $clone->indTimeSum;

                    $clone = clone $val;
                    unset($recs[$key]);
                    if (!array_key_exists($id, $recs)) {
                        $recs[$id] = (object)array(

                            'taskId' => $clone->taskId,
                            'originId' => $clone->originId,
                            'detailId' => $clone->detailId,
                            'type' => $clone->type,
                            'indTime' => $clone->indTime,
                            'indPackagingId' => $clone->indPackagingId,
                            'indTimeSum' => $indTimeSum,
                            'employees' => '|' . $v . '|',
                            'employeesName' => $employeesName,
                            'assetResources' => $clone->assetResources,
                            'indTimeAllocation' => $clone->indTimeAllocation,
                            'productId' => $clone->productId,
                            'measureId' => $clone->measureId,
                            'quantity' => $clone->quantity / $divisor,
                            'scrap' => $clone->scrap / $divisor,

                            'labelMeasure' => $clone->labelMeasure,
                            'labelQuantity' => $labelQuantity / $divisor,

                            'weight' => $clone->weight / $divisor,

                        );
                    } else {
                        $obj = &$recs[$id];

                        $obj->quantity += $clone->quantity / $divisor;
                        $obj->scrap += $clone->scrap / $divisor;
                        $obj->labelQuantity += $labelQuantity / $divisor;
                        $obj->weight += $clone->weight / $divisor;
                        $obj->indTimeSum += $indTimeSum;
                    }
                }

            }

            foreach ($recs as $key => $val) {

                $k = trim($val->employees, '|');
                $indTimeSumArr[$k] += $val->indTimeSum / 60;

            }
            arr::sortObjects($recs, 'taskId', 'asc');

            if ((countR($recs) && ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines'))) {
                arr::sortObjects($recs, 'employeesName', 'asc', 'stri');
            }

        }

        $rec->indTimeSumArr = $indTimeSumArr;

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec- записа
     *
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === false) {
            if ($rec->typeOfReport == 'full') {
                $fld->FLD('jobs', 'varchar', 'caption=Задание');
                $fld->FLD('taskId', 'varchar', 'caption=Операция');
                $fld->FLD('article', 'varchar', 'caption=Артикул');

                $fld->FLD('measureId', 'varchar', 'caption=Произведено->Мярка,tdClass=centered');
                $fld->FLD('quantity', 'double(decimals=2)', 'caption=Произведено->Кол');

                $fld->FLD('scrap', 'double(decimals=2)', 'caption=Брак');
                $fld->FLD('weight', 'double(decimals=2)', 'caption=Тегло');

                $fld->FLD('min', 'double(decimals=2)', 'caption=Минути');
                if ($rec->resultsOn != 'arts') {
                    if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines') {
                        $fld->FLD('employees', 'varchar', 'caption=Служител');
                    }
                    if ($rec->resultsOn == 'usersMachines' || $rec->resultsOn == 'machines') {
                        $fld->FLD('assetResources', 'varchar', 'caption=Оборудване');
                    }
                }
            }
            if ($rec->typeOfReport == 'short') {
                $fld->FLD('employees', 'varchar', 'caption=Служител');
                $fld->FLD('indTimeSum', 'double(smartRound,decimals=2)', 'caption=Време->min,tdClass=centered');
            }
            $fld->FLD('labelMeasure', 'varchar', 'caption=Етикет->мярка,tdClass=centered');
            $fld->FLD('labelQuantity', 'varchar', 'caption=Етикет->кол,tdClass=centered');
        } else {

            if ($rec->typeOfReport == 'full') {

                if ($rec->resultsOn != 'arts') {
                    if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines') {
                        $fld->FLD('employees', 'varchar', 'caption=Служител');
                    }

                    if ($rec->resultsOn == 'usersMachines' || $rec->resultsOn == 'machines') {
                        $fld->FLD('assetResources', 'varchar', 'caption=Оборудване');
                    }
                }
                $fld->FLD('jobs', 'varchar', 'caption=Задание');
                $fld->FLD('taskId', 'varchar', 'caption=Операция');
                $fld->FLD('article', 'varchar', 'caption=Артикул');

                $fld->FLD('measureId', 'varchar', 'caption=Произведено->Мярка,tdClass=centered');
                $fld->FLD('quantity', 'double(decimals=2)', 'caption=Произведено->Кол');

                $fld->FLD('scrap', 'double(decimals=2)', 'caption=Брак');
                $fld->FLD('weight', 'double(decimals=2)', 'caption=Тегло');

                $fld->FLD('min', 'double(decimals=2)', 'caption=Минути');
            }

            if ($rec->typeOfReport == 'short') {
                $fld->FLD('employees', 'varchar', 'caption=Служител');
                $fld->FLD('indTimeSum', 'double(decimals=2)', 'caption=Време->min,tdClass=centered');
            }

            $fld->FLD('labelMeasure', 'varchar', 'caption=Етикет->мярка,tdClass=centered');
            $fld->FLD('labelQuantity', 'double(decimals=2)', 'caption=Етикет->кол,tdClass=centered');

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

        $row = new stdClass();

        if ($dRec->originId) {
            $Job = doc_Containers::getDocument($dRec->originId);
            $row->jobs = ht::createLink($Job->getHandle(), array($Job->getInstance(), 'single', $Job->that));
        }

        $row->taskId = planning_Tasks::getHyperlink($dRec->taskId, true);
        $row->article = cat_Products::getHyperlink($dRec->productId, true);

        $row->measureId = cat_UoM::getShortName($dRec->measureId);
        $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);

        $row->labelMeasure = ($dRec->type == 'input') ? 'бр.' : cat_UoM::getShortName($dRec->labelMeasure);
        $row->labelQuantity = $Double->toVerbal($dRec->labelQuantity);

        $row->scrap = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->scrap);
        $row->weight = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->weight);


        if ($rec->typeOfReport == 'short' && isset($dRec->employees)) {
            $row->employees = crm_Persons::getTitleById(($dRec->employees)) . ' - ' . planning_Hr::getCodeLink($dRec->employees);

            $row->indTimeSum = $Double->toVerbal($dRec->indTimeSum / 60);
        } else {
            if (isset($dRec->employees)) {
                foreach (keylist::toArray($dRec->employees) as $key => $val) {

                    $indTimeSum = $Double->toVerbal($rec->indTimeSumArr[$val]);

                    $name = crm_Persons::fetch($val)->name;
                    $pers = ht::createLink($name, array('crm_Persons', 'single', $val)) . ' - ' . $indTimeSum . ' мин.';

                    $row->employees .= $pers . '</br>';
                }
            }
        }
        if (isset($dRec->assetResources)) {
            $assetResources = '[' . planning_AssetResources::fetch($dRec->assetResources)->code . ']' . planning_AssetResources::fetch($dRec->assetResources)->name;
            $row->assetResources = ht::createLink($assetResources, array('planning_AssetResources', 'single', $dRec->assetResources));
        } else {
            $row->assetResources = '';
        }

        $row->min = $Double->toVerbal($dRec->indTimeSum / 60);
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
        $Date = cls::get('type_Datetime');
        {
            $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN start--><div>|От|*: [#start#]</div><!--ET_END start-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN centre--><div>|Центрове|*: [#centre#]</div><!--ET_END centre-->
                                        <!--ET_BEGIN employees--><div>|Служители|*: [#employees#]</div><!--ET_END employees-->
                                        <!--ET_BEGIN assetResources--><div>|Оборудване|*: [#assetResources#]</div><!--ET_END assetResources-->
                                        <!--ET_BEGIN button--><div>|Филтри |*: [#button#]</div><!--ET_END button-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

            if (isset($data->rec->start)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->start) . '</b>', 'start');
            }

            if (isset($data->rec->to)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
            }

            if (isset($data->rec->centre)) {
                $marker = 0;
                $empText = 'Всички от избраните центрове';
                $worning = null;

                foreach (type_Keylist::toArray($data->rec->centre) as $ce) {
                    $marker++;

                    $centreVerb .= (planning_Centers::getHyperlink(($ce)));

                    if ((countR(type_Keylist::toArray($data->rec->centre))) - $marker != 0) {
                        $centreVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $centreVerb . '</b>', 'centre');
            }else{
                $worning = "warning='Липсва избран център на дейност'";
                $empText = 'Всички';
            }


            if (($data->rec->resultsOn == 'users' || $data->rec->resultsOn == 'usersMachines')) {
                if (isset($data->rec->employees)) {
                    $marker = 0;
                    foreach (type_Keylist::toArray($data->rec->employees) as $empl) {
                        $marker++;

                        $employeesVerb .= (crm_Persons::getHyperlink($empl,'name'));

                        if ((countR(type_Keylist::toArray($data->rec->employees))) - $marker != 0) {
                            $employeesVerb .= ', ';
                        }
                    }

                    $fieldTpl->append('<b>' . $employeesVerb . '</b>', 'employees');
                } else {
                    $fieldTpl->append('<b>' . $empText . '</b>', 'employees');
                }
            } else {
                if (isset($data->rec->employees)) {
                    $marker = 0;
                    foreach (type_Keylist::toArray($data->rec->employees) as $empl) {
                        $marker++;

                        $employeesVerb .= (crm_Persons::getHyperlink($empl,'name'));

                        if ((countR(type_Keylist::toArray($data->rec->employees))) - $marker != 0) {
                            $employeesVerb .= ', ';
                        }
                    }

                    $fieldTpl->append('<b>' . $employeesVerb . '</b>', 'employees');
                }else {
                    $fieldTpl->append('<b>' . $empText . '</b>', 'employees');
                }
            }

            if (isset($data->rec->assetResources)) {
                $marker = 0;
                foreach (type_Keylist::toArray($data->rec->assetResources) as $asset) {
                    $marker++;

                    $assetVerb .= planning_AssetResources::getHyperlink($asset);

                    if ((countR(type_Keylist::toArray($data->rec->assetResources))) - $marker != 0) {
                        $assetVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $assetVerb . '</b>', 'assetResources');
            }

            $grUrl = array('planning_reports_Workflows', 'employeesAndAssets', 'recId' => $data->rec->id, 'ret_url' => true);

            $toolbar = cls::get('core_Toolbar');

            $toolbar->addBtn('Филтър по служители и оборудване', toUrl($grUrl),null,$worning);

            $fieldTpl->append('<b>' . $toolbar->renderHtml() . '</b>', 'button');

            $tpl->append($fieldTpl, 'DRIVER_FIELDS');
        }
    }


    /**
     * Кой може да избере драйвера
     * ceo, planning+officer
     */
    public function canSelectDriver($userId = null)
    {
        if (haveRole('ceo', $userId)) {

            return true;
        }

        if (!haveRole('ceo', $userId) && haveRole('planning', $userId)) {
            if (haveRole('officer', $userId)) {

                return true;
            }

            return false;
        }

        return false;
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
        if ($dRec->originId) {
            $Job = doc_Containers::getDocument($dRec->originId);
            $handle = $Job->getHandle();

            $res->jobs = $handle;
        }
        $res->taskId = planning_Tasks::getTitleById($dRec->taskId);
        $res->article = cat_Products::getTitleById($dRec->productId);
        $res->measureId = cat_UoM::getShortName($dRec->measureId);

        if (isset($dRec->employees)) {
            foreach (keylist::toArray($dRec->employees) as $key => $val) {

                $res->employees = crm_Persons::fetch($val)->name;
            }
        }

        if (isset($dRec->assetResources)) {
            $res->assetResources = planning_AssetResources::fetch($dRec->assetResources)->name;
        } else {
            $res->assetResources = '';
        }

        if ($rec->typeOfReport == 'short') {
            $res->indTimeSum = ($dRec->indTimeSum / 60);
        }


        $res->min = ($dRec->indTimeSum / 60);

        $res->labelMeasure = ($dRec->type == 'input') ? 'бр.' : cat_UoM::getShortName($dRec->labelMeasure);
        $res->labelQuantity = ($dRec->labelQuantity);
    }


    /**
     * Връща ключ по който да се направи разбивка на резултата
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public static function breakdownBy($tRec, $rec)
    {
        $key = '';

        switch ($rec->resultsOn) {

            case 'arts':
                $key = $tRec->taskId . '|' . $tRec->productId;
                break;

            case 'users':
                $key = $tRec->taskId . '|' . $tRec->productId . '|' . $tRec->employees;
                break;

            case 'usersMachines':
                $key = $tRec->taskId . '|' . $tRec->productId . '|' . $tRec->employees . '|' . $tRec->fixedAsset;
                break;

            case 'machines':
                $key = $tRec->taskId . '|' . $tRec->productId . '|' . $tRec->fixedAsset;
                break;

            case 'jobses':
                $key = $tRec->originId . '|' . $tRec->productId . '|' . $tRec->employees;
                break;

        }

        return $key;
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
      //  $row->centre = planning_Centers::getHyperlink($rec->centre, true);
    }

    /**
     * Филтриране служител
     *
     */
    public static function act_EmployeesAndAssets()
    {

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        frame2_Reports::refresh($rec);

        $form = cls::get('core_Form');

        $form->title = "Филтър по служители и машини ";


        if ($rec->centre) {


            $suggestionsEmpl = array();
            $suggestionsAssets = array();

            foreach (keylist::toArray($rec->centre) as $val) {

                $sugg = planning_Hr::getByFolderId(planning_Centers::fetch($val)->folderId);

                if (empty($suggestionsEmpl)) {
                    $suggestionsEmpl = $sugg;
                } else {

                    foreach ($sugg as $key => $v) {

                        if (!in_array($key, array_keys($suggestionsEmpl))) {
                            $suggestionsEmpl[$key] = $v;
                        }
                    }

                }

                unset($sugg);

                $sugg = planning_AssetResources::getByFolderId(planning_Centers::fetch($val)->folderId);

                if (empty($suggestionsAssets)) {
                    $suggestionsAssets = $sugg;
                } else {
                    foreach ($sugg as $key => $v) {

                        if (!in_array($key, array_keys($suggestionsAssets))) {
                            $suggestionsAssets[$key] = $v;
                        }
                    }
                }
                unset($sugg);
            }

            $form->FLD('empployFilter', 'keylist(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Избери служители,placeholder=Изчисти филтъра,silent');

            $form->FLD('assetFilter', 'keylist(mvc=planning_AssetResources,select=name)', 'caption=Избери оборудване,placeholder=Изчисти филтъра,silent');

            $form->setSuggestions('empployFilter', $suggestionsEmpl);
            $form->setSuggestions('assetFilter', $suggestionsAssets);
            if ($rec->employees) {
                $form->rec->empployFilter = $rec->employees;
            }
            if ($rec->assetResources) {
                $form->rec->assetFilter = $rec->assetResources;
            }

            $mRec = $form->input();

            $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

            if ($form->isSubmitted()) {

                if (!$form->rec->empployFilter) {
                    $rec->employees = null;
                } else {
                    $rec->employees = $form->rec->empployFilter;
                }
                if (!$form->rec->assetFilter) {
                    $rec->assetResources = null;
                } else {
                    $rec->assetResources = $form->rec->assetFilter;
                }

                frame2_Reports::save($rec);
                frame2_Reports::refresh($rec);
                return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'ret_url' => true));
            }

        }else{
            status_Messages::newStatus('Липсва избран център на дейност' , 'warning');
            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'ret_url' => true));
        }
        return $form->renderHtml();
    }


}
