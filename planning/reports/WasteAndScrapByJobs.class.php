<?php


/**
 * Мениджър на отчети за отпадък и брак по задания
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
 * @title     Производство » Отпадък и брак по задания
 */
class planning_reports_WasteAndScrapByJobs extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, debug';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;

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

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Групи артикули,after=to,placeholder=Всички,silent,single=none');

        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Дилър,single=none,after=groups');


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

        $recs = $jobsArr = array();

        $stateArr = array('active', 'wakeup', 'closed');

        // Изваждаме всички задания за периода без оттеглените и черновите
        $jobQuery = planning_Jobs::getQuery();

        $jobQuery->where(array("#activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from, $rec->to . ' 23:59:59'));
        $jobQuery->in('state', 'rejected, draft', true);

        //Филтър по създател на заданието
        if (isset($rec->dealers)) {
            $dealersArr = keylist::toArray($rec->dealers);
            $jobQuery->in('createdBy', $dealersArr);
        }

        while ($jobRec = $jobQuery->fetch()) {

            //Филтър по група артикули
            if (isset($rec->groups)) {

                $prodRec = cat_Products::fetch($jobRec->productId);
                $jobQuery->likeKeyList('groups', $rec->groups);
            }

            //задания активирани в този период
            $jobsArr[$jobRec->containerId] = $jobRec;

        }

        //Изваждаме всички задачи в нишките на заданията от периода
        $taskQuery = planning_Tasks::getQuery();

        $taskQuery->in('state', $stateArr);

        $taskQuery->in('originId', array_keys($jobsArr));

        $wasteQuantity = null;
        while ($taskRec = $taskQuery->fetch()) {

            $prodWeigth = cat_Products::convertToUoM($jobsArr[$taskRec->originId]->productId, 'kg');

            // Намиране на отпадъка
            if (!$wasteQuantity) {
                $totalWastePercent = null;
                $waste = planning_ProductionTaskProducts::getTotalWasteArr($jobsArr[$taskRec->originId]->threadId, $totalWastePercent);
            }

            $wasteWeightNullMark = null;     //Ако има поне един отпадък без тегло да се отбележи в изгледа с ? след цифрата

            foreach ($waste as $v) {

                if ($v->quantity ) {

                    if(self::isWeightMeasure($v->packagingId) === false){

                        $wasteProdWeigth = cat_Products::convertToUoM($v->productId, 'kg');

                        if (!is_null($wasteProdWeigth)) {
                            $wasteWeight += $v->quantity * $v->quantityInPack * $wasteProdWeigth;

                        } else {
                            $wasteWeightNullMark = true;
                            $wasteWeight = null;
                        }

                    }else{
                        $wasteProdWeigth = cat_Products::convertToUoM($v->productId, 'kg');
                        $wasteWeight +=$v->quantity * $v->quantityInPack * $wasteProdWeigth;

                    }
                }
            }

            // Намиране на брака
            if (!is_null($prodWeigth)) {
                $scrappedWeight = $taskRec->scrappedQuantity * $prodWeigth;
            } else {
                $scrappedWeight = null;
            }

            if ($scrappedWeight <= 0 && $wasteWeight <= 0)continue;

            $id = $jobsArr[$taskRec->originId]->id;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'jobId' => $jobsArr[$taskRec->originId]->id,                                             //Id на заданието
                    'jobArt' => $jobsArr[$taskRec->originId]->productId,                                     // Продукта по заданието
                    'scrappedWeight' => $scrappedWeight,                                                     // количество брак
                    'wasteWeight' => $wasteWeight,
                    'prodWeight' => $prodWeigth,
                    'wasteProdWeigth' =>$wasteProdWeigth,
                    'wasteWeightNullMark' => $wasteWeightNullMark,

                );
            } else {
                $obj = &$recs[$id];

                $obj->scrappedWeight += $scrappedWeight;

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
            $fld->FLD('jobId', 'varchar', 'caption=Задание');
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

        $row->jobId = planning_Jobs::getHyperlink($dRec->jobId);

        if (isset($dRec->wasteProdWeigth)) {
            $row->wasteWeight = $Double->toVerbal($dRec->wasteWeight);
            if($dRec->wasteWeightNullMark === true){
                $row->wasteWeight .= "<span class='red'>?</span>";
            }
        }else {
            $row->wasteWeight = '?';
        }

        if (isset($dRec->prodWeight)) {
            $row->scrappedWeight = $Double->toVerbal($dRec->scrappedWeight);

        } else {
            $row->scrappedWeight = '?';
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
        $Users = cls::get('type_users');


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN dealers--><div>|Дилъри|*: [#dealers#]</div><!--ET_END dealers-->
                                        <!--ET_BEGIN groups--><div>|Групи|*: [#groups#]</div><!--ET_END groups-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }

        if (isset($data->rec->dealers)) {
            $fieldTpl->append('<b>' . $Users->toVerbal($data->rec->dealers) . '</b>', 'dealers');

        } else {
            $fieldTpl->append('<b>' . "Всички" . '</b>', 'dealers');
        }

        if (isset($data->rec->groups)) {
            $marker = 0;
            foreach (keylist::toArray($data->rec->groups) as $val) {
                $marker++;
                $valVerb = cat_Groups::getTitleById($val);

                if ((countR(type_Keylist::toArray($data->rec->groups))) - $marker != 0) {
                    $valVerb .= ', ';
                }


                $fieldTpl->append('<b>' . $valVerb . '</b>', 'groups');
            }
        } else {
            $fieldTpl->append('<b>' . "Всички" . '</b>', 'groups');
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
     * Определя дали дадена мерна единица е тегловна
     *
     * @return bool
     *
     */
    public static function isWeightMeasure($mesureId)
    {

        $kgMeasures = cat_UoM::getSameTypeMeasures(cat_UoM::fetchBySysId('kg')->id);
        if(in_array($mesureId,array_keys($kgMeasures))){
            return true;
        }

        return false;
    }

}
