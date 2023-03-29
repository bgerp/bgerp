<?php


/**
 * Мениджър на отчети технологично време за изработка на единица изделие
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Технологично време за изработка на единица изделие
 */
class planning_reports_TechnologicalTimeForMakingUnitProduct extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,debug';

    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;

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
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * По-кое поле да се групират данните след групиране, вътре в групата
     */
    protected $subGroupFieldOrder;


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
    protected $changeableFields = 'start,to';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('start', 'date', 'caption=От,after=title,single=none,silent,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=start,single=none,silent,mandatory');

        //Тип на отчета
        $fieldset->FLD('jType', 'enum(oneJob=За задание,jobsInPeriod=За период)', 'caption=Тип отчет,removeAndRefreshForm,after=to,silent');

        //Артикули
        $fieldset->FLD('product', 'key(mvc=cat_Products ,select=name,allowEmpty)', 'caption=Артикул,placeholder=Избери,removeAndRefreshForm,silent,mandatory,after=jType,single=none,class=w100');

        //Задание
        $fieldset->FLD('jobs', 'key(mvc=planning_Jobs)', 'caption=Заданиe,placeholder=Избери,mandatory,removeAndRefreshForm,after=product,silent,input=none,single=none');

        //Операции които да се изключат
        $fieldset->FLD('tasks', 'keylist(mvc=planning_Tasks,select=title,allowEmpty)', 'caption=Изключи операции,placeholder=Всички операции включени,after=jobs,silent,input=none,single=none');

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

        $form->setDefault('jType', 'jobsInPeriod');

        if ($rec->jType == 'oneJob') {
            $form->setField('jobs', 'input');
            if ($rec->jobs) {
                $form->setField('tasks', 'input');
            }

        }

        $stateArr = array('active', 'wakeup', 'closed');

        $jQuery = planning_Jobs::getQuery();

        $jQuery->in('state', $stateArr);

        $jQuery->where(array(
            "#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'",
            $rec->start . ' 00:00:00', $rec->to . ' 23:59:59'));

        while ($jRec = $jQuery->fetch()) {
            $prodsArr[$jRec->productId] = $jRec->productId;
        }

        $prodQuery = cat_Products::getQuery();
        $prodQuery->in('id', $prodsArr);
        while ($prodRec = $prodQuery->fetch()) {
            $options[$prodRec->id] = $prodRec->name;
        }
        $form->setOptions('product', $options);
        unset($options);

        if ($rec->jType == 'oneJob') {
            $jQuery = planning_Jobs::getQuery();

            $jQuery->in('state', $stateArr);
            $jQuery->where("#productId = $rec->product");

            while ($jRec = $jQuery->fetch()) {
                $options[$jRec->id] = 'Задание: ' . $jRec->id . ' / ' . $jRec->createdOn;
            }

            $form->setOptions('jobs', $options);

            unset($options);

            if ($rec->jobs && $rec->product) {
                $jobContainer = planning_Jobs::fetch($rec->jobs)->containerId;


                $taskQuery = planning_Tasks::getQuery();

                $taskQuery->where("#originId = $jobContainer");

                while ($taskRec = $taskQuery->fetch()) {
                    $suggestions[$taskRec->id] = 'Опрерация: ' . $taskRec->title;
                }
                $form->setSuggestions('tasks', $suggestions);
                unset($suggestions);
            }
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

      //  if ($rec->jType == 'jobsInPeriod') return $recs;

        if($rec->jType == 'jobsInPeriod'){

            $stateArr = array('active', 'wakeup', 'closed');

            $jQuery = planning_Jobs::getQuery();

            $jQuery->in('state', $stateArr);

            $jQuery->where(array(
                "#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'",
                $rec->start . ' 00:00:00', $rec->to . ' 23:59:59'));

            $jQuery->where("#productId = $rec->product");
            $jobsArr = arr::extractValuesFromArray($jQuery->fetchAll(),'id');



        }else{
            $jobsArr[$rec->jobs] = $rec->jobs;
        }

        $Job = cls::get('planning_Jobs');

        foreach ($jobsArr as $jobId) {

            $jobRec = $Job->fetch($jobId, 'id,productId,containerId');

            $taskQuery = planning_Tasks::getQuery();

            $taskQuery->where("#originId = $jobRec->containerId");

            if($rec->jType == 'oneJob') {
                $taskQuery->in('id', keylist::toArray($rec->tasks), true);
            }
            $sumNormTime = 0;
            $tasksArr = array();
            while ($tRec = $taskQuery->fetch()) {


                $normTime = planning_type_ProductionRate::getInSecsByQuantity($tRec->indTime, 1);

                $tasksArr[$tRec->id] = $normTime;

                $sumNormTime += $normTime;

                unset($normTime);

            }

            $recs[$jobRec->id] = (object)array(

                'jobs' => $jobRec->id,
                'sumNormTime' => $sumNormTime,
                'product' => $jobRec->productId,
                'tasks' => $tasksArr,
            );
        }


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

            $fld->FLD('jobs', 'varchar', 'caption=Задание / Артикул');
            $fld->FLD('sumNormTime', 'varchar', 'caption=Време->Общо');
            $fld->FLD('tasks', 'varchar', 'caption=Време->По операции');

        } else {

            $fld->FLD('jobs', 'varchar', 'caption=Задание');
            $fld->FLD('sumNormTime', 'varchar', 'caption=Време');

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
        $Time = cls::get('type_Time');

        $row = new stdClass();


        $row->jobs = planning_Jobs::getHyperlink($dRec->jobs);
        $row->sumNormTime = $Time->toVerbal($dRec->sumNormTime);

        if(isset($dRec->tasks)) {
            $row->tasks = '';
            foreach ($dRec->tasks as $k => $v) {
                $row->tasks .= planning_Tasks::getHyperlink($k) . ' - ' . $Time->toVerbal($v) . '</br>';
            }
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
        {
            $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN start--><div>|От|*: [#start#]</div><!--ET_END start-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN tasks--><div>|Изключени операции|*: [#tasks#]</div><!--ET_END tasks-->
                                       
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

            if (isset($data->rec->start)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->start) . '</b>', 'start');
            }

            if (isset($data->rec->to)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
            }

            if (isset($data->rec->tasks) && $data->rec->jType != 'jobsInPeriod') {
                $marker = 0;
                $taskVerb = '';
                foreach (type_Keylist::toArray($data->rec->tasks) as $task) {
                    $marker++;

                    $taskVerb .= (planning_Tasks::getHyperlink($task));

                    if ((countR(type_Keylist::toArray($data->rec->tasks))) - $marker != 0) {
                        $taskVerb .= ', ';
                    }
                }
                $fieldTpl->append('<b>' . $taskVerb . '</b>', 'tasks');
            }


            $tpl->append($fieldTpl, 'DRIVER_FIELDS');
        }
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

}
