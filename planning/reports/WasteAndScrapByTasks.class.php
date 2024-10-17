<?php


/**
 * Мениджър на отчети за отпадък и брак по операции
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Отпадък и брак по операции
 */
class planning_reports_WasteAndScrapByTasks extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, debug';

    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'scrappedWeight,wasteWeight';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


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
    protected $changeableFields = 'from, to';


    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


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

        $fieldset->FLD('employees', 'keylist(mvc=crm_Persons,select=name,group=employees,allowEmpty=true)', 'caption=Работници,placeholder=Всички,after=to');

        $fieldset->FLD('assetResources', 'keylist(mvc=planning_AssetResources,select=name)', 'caption=Машини,placeholder=Всички,after=employees,single=none');

        $fieldset->FLD('centre', 'keylist(mvc=planning_Centers,select=name)', 'caption=Центрове,after=assetResources,single=none');

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

        $suggestions = $suggestionsAsset = array();

        $stateArr = array('active', 'wakeup', 'closed');

        $jQuery = planning_Tasks::getQuery();
        $jQuery->in('state', $stateArr);
        $jQuery->where(array("#activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from, $rec->to . ' 23:59:59'));
        $jQuery->show('employees,assetId');

        while ($jRec = $jQuery->fetch()) {

            foreach (keylist::toArray($jRec->employees) as $v) {

                if (!in_array($v, $suggestions)) {
                    $suggestions[$v] = crm_Persons::getTitleById($v);
                }
            }


        }

        asort($suggestions);
        $form->setSuggestions('employees', $suggestions);

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

        $stateArr = array('active', 'wakeup', 'closed');

        //Изваждаме всички задачи от периода
        $taskQuery = planning_Tasks::getQuery();

        $taskQuery->in('state', $stateArr);

        $taskQuery->where(array("#activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from, $rec->to . ' 23:59:59'));

        //Филтър по машини
        if ($rec->assetResources) {
            $assetArr = keylist::toArray($rec->assetResources);

            $taskQuery->in('assetId', $assetArr);
        }

        //Филтър по служители
        if ($rec->employees) {
            $taskQuery->likeKeylist('employees', $rec->employees);
        }

        //Филтър по център на дейност
        if ($rec->centre) {

            foreach (keylist::toArray($rec->centre) as $cent) {
                $centFoldersArr[planning_Centers::fetch($cent)->folderId] = planning_Centers::fetch($cent)->folderId;
            }
            $taskQuery->in('folderId', $centFoldersArr);
        }

        $wasteQuantity = null;

        while ($taskRec = $taskQuery->fetch()) {

            bp($taskRec);

            $JOB = doc_Containers::getDocument($taskRec->originId);
            $jobRec = planning_Jobs::fetch($JOB->that);

            $prodWeigth = cat_Products::convertToUoM($jobRec->productId, 'kg');

            if (!$wasteQuantity) {
                $totalWastePercent = null;
                $waste = planning_ProductionTaskProducts::getTotalWasteArr($taskRec->threadId, $totalWastePercent);

            }

            $wasteWeightNullMark = null;     //Ако има поне един отпадък без тегло да се отбележи в изгледа с ? след цифрата

            foreach ($waste as $v) {
                if ($v->quantity) {

                    if (planning_reports_WasteAndScrapByJobs::isWeightMeasure($v->packagingId) === false) {

                        $wasteProdWeigth = cat_Products::convertToUoM($v->productId, 'kg');

                        if (!is_null($wasteProdWeigth)) {
                            $wasteWeight += $v->quantity * $wasteProdWeigth;

                        } else {
                            $wasteWeightNullMark = true;
                            $wasteWeight = null;
                        }

                    } else {
                        $wasteProdWeigth = cat_Products::convertToUoM($v->productId, 'kg');
                        $wasteWeight += $v->quantity * $wasteProdWeigth;

                    }
                }
            }

            if (!is_null($prodWeigth)) {
                $scrappedWeight = $taskRec->scrappedQuantity * $prodWeigth;
            } else {
                $scrappedWeight = null;
            }

            $id = $taskRec->id;

            if ($scrappedWeight <= 0 && $wasteWeight <= 0) continue;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'taskId' => $taskRec->id,                                          //Id на операцията
                    'jobArt' => $jobRec->productId,                                   // Продукта по заданието
                    'scrappedWeight' => $scrappedWeight,                            // количество брак
                    'wasteWeight' => $wasteWeight,
                    'prodWeight' => $prodWeigth,
                    'wasteProdWeigth' => $wasteProdWeigth,
                    'assetResources' => $taskRec->assetId,
                    'employees' => $taskRec->employees,
                    'jobId' => $jobRec->id,
                    'wasteWeightNullMark' => $wasteWeightNullMark

                );
            }

            $wasteWeight = 0;
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
            $fld->FLD('taskId', 'varchar', 'caption=Операция');
            $fld->FLD('assetResources', 'varchar', 'caption=Оборудване');
            $fld->FLD('employees', 'varchar', 'caption=Служители');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('scrappedWeight', 'double(decimals=2)', 'caption=Брак');
            $fld->FLD('wasteWeight', 'double(decimals=2)', 'caption=Отпадък');

        } else {


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
        $Double->params['decimals'] = 3;

        $row = new stdClass();

        $row->taskId = planning_Tasks::getHyperlink($dRec->taskId);

        if (isset($dRec->wasteProdWeigth)) {
            $row->wasteWeight = $Double->toVerbal($dRec->wasteWeight);
            if ($dRec->wasteWeightNullMark === true) {
                $row->wasteWeight .= "<span class='red'>?</span>";
            }
        } else {
            $row->wasteWeight = '?';
        }

        if (isset($dRec->prodWeight)) {
            $row->scrappedWeight = $Double->toVerbal($dRec->scrappedWeight);

        } else {
            $row->scrappedWeight = '?';
        }

        if (isset($dRec->assetResources)) {
            $row->assetResources = planning_AssetResources::getHyperlink($dRec->assetResources);
        }

        if (isset($dRec->employees)) {
            $row->employees = '';
            foreach (keylist::toArray($dRec->employees) as $val) {

                //$row->employees .= crm_Persons::getTitleById(($val)) . ' - ' . planning_Hr::getCodeLink($val) . ',' . "</br>";
                $row->employees .= crm_Persons::getTitleById(($val)) . "</br>";
            }


        }

        $kgMeasureId = cat_UoM::getQuery()->fetch("#name = 'килограм'")->id;
        $row->measure = cat_UoM::getShortName($kgMeasureId);


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
                                        <!--ET_BEGIN employees--><div>|Служители|*: [#employees#]</div><!--ET_END employees-->
                                        <!--ET_BEGIN assetResources--><div>|Оборудване|*: [#assetResources#]</div><!--ET_END assetResources-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }

        if (isset($data->rec->assetResources)) {
            $marker = 0;
            foreach (keylist::toArray($data->rec->assetResources) as $val) {
                $marker++;
                $valVerb = planning_AssetResources::getHyperlink($val);

                if ((countR(type_Keylist::toArray($data->rec->assetResources))) - $marker != 0) {
                    $valVerb .= ', ';
                }

                $fieldTpl->append('<b>' . $valVerb . '</b>', 'assetResources');
            }
        } else {
            $fieldTpl->append('<b>' . "Всички" . '</b>', 'assetResources');
        }

        if (isset($data->rec->employees)) {
            $marker = 0;
            foreach (keylist::toArray($data->rec->employees) as $val) {
                $marker++;
                $valVerb = crm_Persons::getTitleById($val);

                if ((countR(type_Keylist::toArray($data->rec->employees))) - $marker != 0) {
                    $valVerb .= ', ';
                }

                $fieldTpl->append('<b>' . $valVerb . '</b>', 'employees');
            }
        } else {
            $fieldTpl->append('<b>' . "Всички" . '</b>', 'employees');
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

}
