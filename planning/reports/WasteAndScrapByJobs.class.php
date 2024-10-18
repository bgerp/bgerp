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
 * @title     Производство » Отпадък и брак
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

        $fieldset->FLD('type', 'enum(job=По задание, task=По операции)', 'notNull,caption=Покажи->Артикули,maxRadio=1,after=to,single=none,removeAndRefreshForm');

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Групи артикули,after=type,placeholder=Всички,silent,single=none');

        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Дилър,single=none,after=groups');

        $fieldset->FLD('employees', 'keylist(mvc=crm_Persons,select=name,group=employees,allowEmpty=true)', 'caption=Работници,placeholder=Всички,after=dealers');

        $fieldset->FLD('assetResources', 'keylist(mvc=planning_AssetResources,select=name)', 'caption=Машини,placeholder=Всички,after=employees,single=none');

        $fieldset->FLD('centre', 'keylist(mvc=planning_Centers,select=name)', 'caption=Центрове,placeholder=Всички,after=assetResources,single=none');


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

        $form->setDefault('type', 'task');

        $form->input('type', 'silent');

        if ($rec->type == 'job') {
            $form->setField('employees', 'input=none');
            $form->setField('assetResources', 'input=none');
            $form->setField('centre', 'input=none');
        }
        if ($rec->type == 'task') {
            $form->setField('groups', 'input=none');
            $form->setField('dealers', 'input=none');
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

                $grArr = keylist::toArray($rec->groups);
                if(!keylist::isIn($grArr, $prodRec->groups) || !$prodRec->groups){
                   continue;
                }

            }

            //задания активирани в този период
            $jobsArr[$jobRec->containerId] = $jobRec;

        }

        //Изваждаме всички задачи
        $taskQuery = planning_Tasks::getQuery();

        $taskQuery->in('state', $stateArr);

        //Ако справката е по задание, филтрираме тези които са в нишките на заданията от периода
        if($rec->type == 'job'){
            $taskQuery->in('originId', array_keys($jobsArr));
        }else{

            //Ако справката е по операциии, фитрираме операциите по дата на активиране
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
        }

        $wasteQuantity = null;
        while ($taskRec = $taskQuery->fetch()) {

            if(!is_null($jobsArr[$taskRec->originId])){
                $originJobRec = $jobsArr[$taskRec->originId];
            }else{
                $JOB = doc_Containers::getDocument($taskRec->originId);
                $originJobRec = planning_Jobs::fetch($JOB->that);
            }

            $prodWeigth = cat_Products::convertToUoM($originJobRec->productId, 'kg');

            // Намиране на отпадъка
            if (!$wasteQuantity) {
                $totalWastePercent = null;
                $waste = planning_ProductionTaskProducts::getTotalWasteArr($originJobRec->threadId, $totalWastePercent);
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

            if($rec->type == 'job'){
                $id = $jobsArr[$taskRec->originId]->id;
            }else{
                $id = $taskRec->id;
            }

            if ($scrappedWeight <= 0 && $wasteWeight <= 0) continue;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'jobId' => $jobsArr[$taskRec->originId]->id,                                             //Id на заданието
                    'jobArt' => $jobsArr[$taskRec->originId]->productId,                                     // Продукта по заданието
                    'taskId' => $taskRec->id,                                                                //Id на операцията
                    'scrappedWeight' => $scrappedWeight,                                                     // количество брак
                    'wasteWeight' => $wasteWeight,
                    'prodWeight' => $prodWeigth,
                    'wasteProdWeigth' =>$wasteProdWeigth,
                    'assetResources' => $taskRec->assetId,
                    'employees' => $taskRec->employees,
                    'wasteWeightNullMark' => $wasteWeightNullMark,

                );
            } else {
                if($rec->type == 'job') {
                    $obj = &$recs[$id];
                    $obj->scrappedWeight += $scrappedWeight;
                }
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

            if($rec->type == 'job') {
                $fld->FLD('jobId', 'varchar', 'caption=Задание');
            }else{
                $fld->FLD('taskId', 'varchar', 'caption=Операция');
                $fld->FLD('assetResources', 'varchar', 'caption=Оборудване');
                $fld->FLD('employees', 'varchar', 'caption=Служители');
            }
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('scrappedWeight', 'double(decimals=2)', 'caption=Брак');
            $fld->FLD('wasteWeight', 'double(decimals=2)', 'caption=Отпадък');
            //todo
            if($rec->type == 'job') {
                $fld->FLD('positiveAvDev', 'double(decimals=2)', 'caption=Средно отклонение в количества -> Положително,tdClass=centered');
                $fld->FLD('negativeAvDev', 'double(decimals=2)', 'caption=Средно отклонение в количества -> Отрицателно,tdClass=centered');
            }

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

        if (isset($dRec->jobId)) {
            $row->jobId = planning_Jobs::getHyperlink($dRec->jobId);
        }
        if (isset($dRec->taskId)) {
            $row->taskId = planning_Tasks::getHyperlink($dRec->taskId);
        }

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
        $Users = cls::get('type_users');


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN dealers--><div>|Дилъри|*: [#dealers#]</div><!--ET_END dealers-->
                                        <!--ET_BEGIN groups--><div>|Групи|*: [#groups#]</div><!--ET_END groups-->
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

        if ($data->rec->type == 'job') {
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
        }else{

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
