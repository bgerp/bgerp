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
    public $canSelectDriver = 'ceo, асс, planning, debug';


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
        // Период на справката
        $fieldset->FLD('from', 'date',
            'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date',
            'caption=До,after=from,single=none,mandatory');

        // Избор на тип справка – по задание или по операции
        $fieldset->FLD('type', 'enum(job=По задание, task=По операции)',
            'notNull,caption=Покажи->Артикули,maxRadio=1,after=to,single=none,silent,removeAndRefreshForm=orderBy');

        // Филтриране по групи артикули
        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name)',
            'caption=Групи артикули,after=type,placeholder=Всички,silent,single=none');

        // Филтър по дилъри (потребители с определени роли)
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)',
            'caption=Дилър,single=none,after=groups');

        // Филтър по работници
        $fieldset->FLD('employees', 'keylist(mvc=crm_Persons,select=name,group=employees,allowEmpty=true)',
            'caption=Работници,placeholder=Всички,after=dealers');

        // Филтър по машини (активи)
        $fieldset->FLD('assetResources', 'keylist(mvc=planning_AssetResources,select=name)',
            'caption=Машини,placeholder=Всички,after=employees,single=none');

        // Филтър по центрове
        $fieldset->FLD('centre', 'keylist(mvc=planning_Centers,select=name)',
            'caption=Центрове,placeholder=Всички,after=assetResources,single=none');

        // Сортиране по показател (напр. по брак или отпадък)
        $fieldset->FLD('orderBy', 'enum(jobId=Задание, taskId=Операция, scrappedWeight=Брак, wasteWeight=Отпадък)',
            'caption=Подреждане на резултата->Показател,maxRadio=4,columns=3,silent,after=centre');

        // Ред на сортиране – низходящ или възходящ
        $fieldset->FLD('order', 'enum(desc=Низходящо, asc=Възходящо)',
            'caption=Подреждане на резултата->Ред,maxRadio=2,after=orderBy,single=none');

        // Групиране на резултата – по артикули, по групи, или без
        $fieldset->FLD('groupBy', 'enum(no=Без групиране,article=Артикули,articleGroup=Групи артикули)',
            'caption=Подреждане на резултата->Групиране,after=order,columns=3,maxRadio=3');

        // Режим на зареждане – пасивно или активно
        $fieldset->FLD('pasive', 'enum( yes=Активно, no=Пасивно)',
            'caption=Подреждане на резултата->Режим,after=groupBy,single=none,removeAndRefreshForm,silent');

        // Таблично поле за въвеждане на данни за групи (тегло, брак, отпадък)
        $fieldset->FLD('GrFill',
            "table(
            columns=grp|wght|scrpWeight|wstWeight,
            captions=Група|Произведено количество  кг|Отпадък  кг|%|
            widths=30em|5em|5em|5em,
            suggestions[grp]=cat_Groups::suggestions()
        )",
            'caption=Зареждане на групи||Extras->Зареди||Additional,autohide,advanced,after=groupBy,export=Csv,single=none,silent,input=none'
        );
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
        $rec = &$form->rec;


        $details = array();

        if ($rec->pasive == 'no') {
            $groupsQuery = cat_Groups::getQuery();

            while ($gRec = $groupsQuery->fetch()) {

                $details[$gRec->id] = $gRec->name;

            }
        }

        // $suggestions = array_combine(array_values($suggestions), array_values($suggestions));
        $form->setFieldTypeParams('GrFill', array('grp_sgt' => $details));

        if ($form->isSubmitted()) {
            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
            if (is_null($form->rec->groups) && $form->rec->groupBy == 'articleGroup') {
                $form->setError('groups', 'Когато групирането е по групи, трябва да има избрана поне една група');
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

        if ($rec->pasive == 'no') {
            $form->setField('GrFill', 'input');
        }

        $form->setDefault('pasive', 'yes');
        $form->setDefault('from', '1970-01-01');
        $form->setDefault('to', dt::today().'23:59:59');

        if ($rec->type == 'job') {
            $form->setField('employees', 'input=none');
            $form->setField('assetResources', 'input=none');
            $form->setField('centre', 'input=none');
            $form->setOptions('orderBy', array('jobId' => 'Задание', 'scrappedWeight' => 'Брак', 'wasteWeight' => 'Отпадък'));
            $form->setField('orderBy', 'jobId');
        }
        if ($rec->type == 'task') {
            $form->setField('groups', 'input=none');
            $form->setField('dealers', 'input=none');
            $form->setOptions('orderBy', array('taskId' => 'Операция', 'scrappedWeight' => 'Брак', 'wasteWeight' => 'Отпадък'));
            $form->setField('orderBy', 'taskId');
        }


        $suggestions = $suggestionsAsset = array();

        $stateArr = array('active', 'wakeup', 'closed');

        $jQuery = planning_Tasks::getQuery();
        $jQuery->in('state', $stateArr);
        $jQuery->where(array("#activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from, $rec->to . '23:59:59'));
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

        $recs = $jobsArr = array();
        //СПЕЦИАЛЕН СЛУЧАЙ
        if (($rec->pasive == 'no')) {
            $recs = $this->prepareRecsFromGrFill($rec);
            return $recs;
        }

        // ЗАДАВАМЕ ГРУПИРАНЕТО СПОРЕД ИЗБОРА ОТ ФОРМАТА
        if ($rec->groupBy == 'article') {
            $this->groupByField = 'jobArt';
        }


        $stateArr = array('active', 'wakeup', 'closed');

        // Изваждаме всички задания за периода без оттеглените и черновите
        $jobQuery = planning_Jobs::getQuery();
        $jobQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

        $jobQuery->where(array("#activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from, $rec->to . ' 23:59:59'));
        $jobQuery->in('state', 'rejected, draft', true);

        //Филтър по създател на заданието
        if (isset($rec->dealers)) {
            $dealersArr = keylist::toArray($rec->dealers);
            $jobQuery->in('createdBy', $dealersArr);
        }

        //Филтър по група артикули
        if (isset($rec->groups)) {

            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $jobQuery, $rec->groups, 'productId');
        }

        while ($jobRec = $jobQuery->fetch()) {

            //задания активирани в този период
            $jobsArr[$jobRec->containerId] = $jobRec;

        }

        //Изваждаме всички задачи
        $taskQuery = planning_Tasks::getQuery();

        $taskQuery->in('state', $stateArr);

        //Ако справката е по задание, филтрираме тези които са в нишките на заданията от периода
        if ($rec->type == 'job') {
            $taskQuery->in('originId', array_keys($jobsArr));
        } else {

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

        if ($taskQuery->count() > 0) {
            $tasksArr = arr::extractValuesFromArray($taskQuery->fetchAll(), 'id');
        }

        $taskDetRecArr = $taskRec = array();
        $taskDetQuery = planning_ProductionTaskDetails::getQuery();
        $taskDetQuery->in('taskId', $tasksArr);
        $taskDetQuery->where("#type = 'scrap'");
        $taskDetQuery->where("#state = 'active'");

        while ($taskDetRec = $taskDetQuery->fetch()) {

            $taskDetRecArr[] = $taskDetRec;
        }

        $wasteQuantity = null;

        while ($taskRec = $taskQuery->fetch()) {

            $tasksArr[$taskRec->id] = $taskRec->id;

            if (!is_null($jobsArr[$taskRec->originId])) {
                $originJobRec = $jobsArr[$taskRec->originId];
            } else {
                $JOB = doc_Containers::getDocument($taskRec->originId);
                $originJobRec = planning_Jobs::fetch($JOB->that);
            }

            $prodWeigth = cat_Products::convertToUoM($originJobRec->productId, 'kg');

            // Намиране на отпадъка
            if (!$wasteQuantity) {
                $totalWastePercent = null;
                if ($rec->type == 'job') {
                    $waste = planning_ProductionTaskProducts::getTotalWasteArr($originJobRec->threadId, $totalWastePercent);
                } else {
                    $waste = planning_ProductionTaskProducts::getTotalWasteArr($taskRec->threadId, $totalWastePercent);
                }
            }

            $wasteWeightNullMark = null;     //Ако има поне един отпадък без тегло да се отбележи в изгледа с ? след цифрата

            foreach ($waste as $v) {

                if ($v->quantity) {

                    if (self::isWeightMeasure($v->packagingId) === false) {

                        $wasteProdWeigth = cat_Products::convertToUoM($v->productId, 'kg');

                        if (!is_null($wasteProdWeigth)) {
                            if ($rec->type == 'job') {
                                $wasteWeight += $v->quantity * $v->quantityInPack * $wasteProdWeigth;
                            } else {
                                $wasteWeight += $v->quantity * $wasteProdWeigth;
                            }

                        } else {
                            $wasteWeightNullMark = true;
                            $wasteWeight = null;
                        }

                    } else {
                        $wasteProdWeigth = cat_Products::convertToUoM($v->productId, 'kg');
                        if ($rec->type == 'job') {
                            $wasteWeight += $v->quantity * $v->quantityInPack * $wasteProdWeigth;
                        } else {
                            $wasteWeight += $v->quantity * $wasteProdWeigth;
                        }
                    }
                }
            }

            // Намиране на брака

            $scrappedWeight = 0;
            foreach ($taskDetRecArr as $key => $val) {
                if ($taskRec->id != $val->taskId) continue;

                if ($val->netWeight > 0) {
                    $scrappedWeight += $val->netWeight;
                } else {
                    $scrappedWeight += $val->weight;
                }
            }

            $productGroups = null;

            if ($rec->type == 'job' && $rec->groupBy == 'articleGroup' && !is_null($rec->groups)) {
                $productRec = cat_Products::fetch($jobsArr[$taskRec->originId]->productId);

                // Преобразуваме двете keylist полета в масиви
                $productGroupsArr = keylist::toArray($productRec->groups);
                $filterGroupsArr = keylist::toArray($rec->groups);

                // Сечение на двата масива
                $intersection = array_intersect($productGroupsArr, $filterGroupsArr);

                // Ако има съвпадения — обратно в keylist формат
                if (!empty($intersection)) {
                    $productGroups = keylist::fromArray($intersection);
                } else {
                    $productGroups = null;
                }
            }


            if ($rec->type == 'job') {
                $id = $jobsArr[$taskRec->originId]->id;

            } else {
                $id = $taskRec->id;
            }

            if ($scrappedWeight <= 0 && $wasteWeight <= 0) continue;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'jobId' => $jobsArr[$taskRec->originId]->id,                                             //Id на заданието
                    'jobArt' => $jobsArr[$taskRec->originId]->productId,                                     // Продукта по заданието
                    'taskId' => $taskRec->id,                                                                //Id на операцията
                    'jobArtGroups' => $productGroups,
                    'scrappedWeight' => $scrappedWeight,                                                     // количество брак
                    'wasteWeight' => $wasteWeight,
                    'prodWeight' => $prodWeigth,
                    'wasteProdWeigth' => $wasteProdWeigth,
                    'assetResources' => $taskRec->assetId,
                    'employees' => $taskRec->employees,
                    'wasteWeightNullMark' => $wasteWeightNullMark,

                );
            } else {
                if ($rec->type == 'job') {
                    $obj = &$recs[$id];
                    $obj->scrappedWeight += $scrappedWeight;
                }
            }
            $wasteWeight = 0;
        }

        // Създаваме празен масив, в който ще събираме агрегираните резултати по групи
        $tempArr = array();

// Проверяваме дали сме в режим на групиране по артикули групи
        if ($rec->groupBy == 'articleGroup' && !is_null($rec->groups)) {

            // Преобразуваме keylist-а със зададените групи в масив за по-лесна обработка
            $groupsArr = keylist::toArray($rec->groups);

            // Обхождаме всяка избрана група от филтъра
            foreach ($groupsArr as $groupId) {

                // За всяка група въртим всички записи от изчислените $recs
                foreach ($recs as $rval) {

                    // Вземаме групите на артикула от текущия запис
                    $jobArtGroupsArr = keylist::toArray($rval->jobArtGroups);

                    // Ако групата, която обработваме в момента ($groupId), съществува в групите на артикула
                    if (in_array($groupId, $jobArtGroupsArr)) {

                        // Ако все още не сме добавили тази група в $tempArr — създаваме нов запис
                        if (!isset($tempArr[$groupId])) {
                            $tempArr[$groupId] = (object)array(
                                'group' => $groupId,
                                // Копираме част от полетата от текущия запис (взимаме ги от първото срещане)
                                'jobId' => $rval->jobId,
                                'jobArt' => $rval->jobArt,
                                'jobArtGroups' => $rval->jobArtGroups,

                                // Създаваме сумарните полета, започвайки от 0
                                'scrappedWeightGroup' => 0,
                                'wasteWeightGroup' => 0,

                                // Запазваме и оригиналните стойности за справка (ако ти трябват)
                                'scrappedWeight' => 0,
                                'wasteWeight' => 0,
                                'prodWeight' => $rval->prodWeight,
                                'wasteWeightNullMark' => $rval->wasteWeightNullMark,
                            );
                        }

                        // Сумираме количествата брак и отпадък в новите агрегационни полета
                        $tempArr[$groupId]->scrappedWeightGroup += $rval->scrappedWeight;
                        $tempArr[$groupId]->wasteWeightGroup += $rval->wasteWeight;

                        $tempArr[$groupId]->scrappedWeight += $rval->scrappedWeight;
                        $tempArr[$groupId]->wasteWeight += $rval->wasteWeight;


                    }
                }
            }
            // Подменяме оригиналния масив с агрегирания
            $recs = $tempArr;
            // подменяме групирането да е по група артикули
            if ($rec->groupBy == 'articleGroup') {

                $this->summaryListFields = 'scrappedWeight,wasteWeight';
            }
        }


        if (!empty(($recs) && $rec->groupBy != 'articleGroup')) {
            arr::sortObjects($recs, $rec->orderBy, $rec->order);
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

            // СПЕЦИАЛЕН СЛУЧАЙ
            if (($rec->pasive == 'no')) {

                $fld->FLD('group', 'varchar', 'caption=Група артикули');
                $fld->FLD('weight', 'double(decimals=2)', 'caption=Произведено количество [кг]');
                $fld->FLD('scrappedWeight', 'double(decimals=2)', 'caption=Отпадък [кг]');
                $fld->FLD('wasteWeight', 'double(decimals=2)', 'caption=%');

                return $fld;
            }


            if ($rec->groupBy == 'articleGroup') {
                // Когато групираме по групи артикули:
                $fld->FLD('group', 'varchar', 'caption=Група артикули');
                //  $fld->FLD('scrappedWeight', 'double(decimals=2)', 'caption=Брак');
                //  $fld->FLD('wasteWeight', 'double(decimals=2)', 'caption=Отпадък');
            }
            // if ($rec->groupBy == 'no') {
            // Всички останали случаи (досегашното поведение)
            if ($rec->type == 'job') {
                $fld->FLD('jobId', 'varchar', 'caption=Задание');
                if ($rec->groupBy == 'article') {
                    $fld->FLD('jobArt', 'varchar', 'caption=Артикул');
                }
                if ($rec->groupBy == 'articleGroup') {
                    //  $fld->FLD('group', 'varchar', 'caption=Група артикули');
                }
            } else {
                $fld->FLD('taskId', 'varchar', 'caption=Операция');
                $fld->FLD('assetResources', 'varchar', 'caption=Оборудване');
                $fld->FLD('employees', 'varchar', 'caption=Служители');
            }
            //  $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('scrappedWeight', 'double(decimals=2)', 'caption=Брак [kg]');
            $fld->FLD('wasteWeight', 'double(decimals=2)', 'caption=Отпадък [kg]');
            //  }
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

        // СПЕЦИАЛЕН СЛУЧАЙ
        // if (!is_null($rec->GrFill)) {
        if (($rec->pasive == 'no')) {

            $row->group = cat_Groups::getHyperlink($dRec->group);
            $row->weight = $Double->toVerbal($dRec->weight);
            $row->scrappedWeight = $Double->toVerbal($dRec->scrappedWeight);
            $row->wasteWeight = $Double->toVerbal($dRec->wasteWeight);

            return $row;
        }


        // Когато сме в групиране по групи артикули
        if ($rec->groupBy == 'articleGroup') {

            // Хиперлинк на групата
            $row->group = cat_Groups::getHyperlink($dRec->group);

            // Показваме сумираните количества брак и отпадък
            $row->scrappedWeight = $Double->toVerbal($dRec->scrappedWeightGroup);
            $row->wasteWeight = $Double->toVerbal($dRec->wasteWeightGroup);

            return $row;
        }

        // При нормалните (негрупирани) случаи:

        if (isset($dRec->jobId)) {
            $row->jobId = planning_Jobs::getHyperlink($dRec->jobId);
        }

        if (isset($dRec->taskId)) {
            $row->taskId = planning_Tasks::getHyperlink($dRec->taskId);
        }

        if (isset($dRec->jobArt)) {
            $row->jobArt = cat_Products::getHyperlink($dRec->jobArt);
        }

        if (isset($dRec->assetResources)) {
            $row->assetResources = planning_AssetResources::getHyperlink($dRec->assetResources);
        }

        if (isset($dRec->employees)) {
            $employeesArr = keylist::toArray($dRec->employees);
            $row->employees = '';
            foreach ($employeesArr as $personId) {
                $row->employees .= crm_Persons::getTitleById($personId) . "<br>";
            }
        }

        // Стандартно показване на брак и отпадък
        $row->scrappedWeight = $Double->toVerbal($dRec->scrappedWeight);

        if (isset($dRec->wasteProdWeigth)) {
            $row->wasteWeight = $Double->toVerbal($dRec->wasteWeight);
            if ($dRec->wasteWeightNullMark === true) {
                $row->wasteWeight .= "<span class='red'>?</span>";
            }
        } else {
            $row->wasteWeight = '?';
        }

        // Мярка (винаги "кг" в твоя случай)
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
        } else {

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
        if (in_array($mesureId, array_keys($kgMeasures))) {
            return true;
        }

        return false;
    }

    /**
     * Подготвя записи от данните в полето GrFill.
     *
     * Методът обхожда масива GrFill, който е сериализиран в JSON и съдържа теглови данни за групи.
     * Връща само онези записи, при които поне една от стойностите (тегло, брак, отпадък) е различна от 0.
     * Името на групата се съхранява като ID, за по-лесна вербализация при извеждане.
     *
     * @param stdClass $rec Записът от формата, който съдържа полето GrFill
     * @return array Масив от обекти със следната структура:
     *               [
     *                   groupId => (object)[
     *                       'group' => int,              // ID на групата (ще се вербализира по-късно)
     *                       'weight' => float,           // Тегло
     *                       'scrappedWeight' => float,   // Брак
     *                       'wasteWeight' => float       // Отпадък
     *                   ],
     *                   ...
     *               ]
     */
    // Връща масив със записи от табличното поле GrFill, използвайки ID на група, вместо име
    protected function prepareRecsFromGrFill($rec)
    {
        $recs = array();

        // Преобразуваме JSON форматираното поле в масив
        $grFillData = (array)json_decode($rec->GrFill, true);

        // Обхождаме всяка група по индекс
        foreach ($grFillData['grp'] as $i => $groupName) {
            // Тегло, брак и отпадък от съответната колона
            $weight = (float)$grFillData['wght'][$i];
            $scrapped = (float)$grFillData['scrpWeight'][$i];
            $waste = (float)$grFillData['wstWeight'][$i];

            // Пропускаме празни редове (всичко 0)
            if ($weight == 0 && $scrapped == 0 && $waste == 0) {
                continue;
            }

            // Взимаме ID на групата по име
            $groupId = cat_Groups::fetchField("#name = '{$groupName}'", 'id');

            // Добавяме в резултата
            $recs[$groupId] = (object)[
                'group' => $groupId,
                'weight' => $weight,
                'scrappedWeight' => $scrapped,
                'wasteWeight' => $waste
            ];
        }

        // Премахваме всички записи, където и трите стойности са 0
        foreach ($recs as $id => $r) {
            if ($r->weight == 0 && $r->scrappedWeight == 0 && $r->wasteWeight == 0) {
                unset($recs[$id]);
            }
        }

        return $recs;
    }
}
