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
    protected $groupByField ;


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
        $fieldset->FLD('start', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=start,single=none,mandatory');

        //Артикули
        $fieldset->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,forceAjax)', 'caption=Артикул,placeholder=Избери,removeAndRefreshForm,silent,mandatory,after=to,single=none,class=w100');

        //Задание
        $fieldset->FLD('jobs', 'keylist(mvc=planning_Jobs)', 'caption=Заданиe,placeholder=Избери,mandatory,after=productId,silent,single=none');

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

        $suggestions = array();
        foreach (keylist::toArray($rec->jobs) as $val) {
       //     $suggestions[$val] = planning_Jobs::getTitleById($val);
        }

        $stateArr = array('active', 'wakeup', 'closed');

        $jQuery = planning_Jobs::getQuery();
        $jQuery->in('state', $stateArr);
        $jQuery->where("#productId = $rec->productId");
     //   $jQuery->show('productId');
        while ($jRec = $jQuery->fetch()) {
            if (!array_key_exists($jRec->id, $suggestions)) {
                $suggestions[$jRec->id] = planning_Jobs::getTitleById($jRec->id);
            }
        }

        asort($suggestions);

        $form->setSuggestions('jobs', $suggestions);


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

//        $jRec = planning_Jobs::fetch(trim($rec->jobs,'|'));
//
//        $query = planning_ProductionTaskDetails::getQuery();
//
//        $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
//        $query->EXT('folderId', 'planning_Tasks', 'externalName=folderId,externalKey=taskId');
//        $query->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
//
//        $query->where("#state != 'rejected' ");
//
//        $taskQuery = planning_Tasks::getQuery();
//        $taskQuery->where("#originId = $jRec->containerId");
//        while ($tRec = $taskQuery->fetch()){
//            $normTime = planning_ProductionTaskDetails::calcNormByRec($tRec);
//            if ($tRec->indTime)bp($normTime,$tRec->indTime);
//        }




     //   bp($jRec,$taskQuery->fetchAll(),$rec);

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

                $fld->FLD('jobs', 'varchar', 'caption=Задание');

        } else {

                $fld->FLD('jobs', 'varchar', 'caption=Задание');
                $fld->FLD('taskId', 'varchar', 'caption=Операция');

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
                                       
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

            if (isset($data->rec->start)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->start) . '</b>', 'start');
            }

            if (isset($data->rec->to)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
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
