<?php 

/**
 * Мениджър за Центровете на дейност
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Centers extends core_Master
{
    /**
     * Ид на Неопределения център на дейност
     */
    const UNDEFINED_ACTIVITY_CENTER_ID = 1;
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'planning_ActivityCenterIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Центрове на дейност';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ц-р на дейност';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, planning_Wrapper, plg_State, plg_Rejected, plg_Created, acc_plg_Registry, doc_FolderPlg, plg_Sorting, doc_plg_Close';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, planning, jobSee, planningAll';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, planning, jobSee';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, planningMaster';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo, planningMaster';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'ceo, planningMaster';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutCenters.shtml';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/big_house.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,name';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Център, departmentId, type, employmentOccupied=Назначени, employmentTotal=От общо, scheduleId=График, folderId,createdOn,createdBy';
    
    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'public';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'nkid,employmentTotal,employmentOccupied,startingOn';
    
    
    /**
     * Поле, в което да се постави връзка към папката в листови изглед
     */
    public $listFieldForFolderLink = 'folder';
    
    
    /**
     * Детайла, на модела
     *
     * @var string|array
     */
    public $details = 'stages=planning_Steps,planning_Points';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,width=100%');
        $this->FLD('type', 'enum(section=Поделение,
                                 branch=Клон,
                                 office=Офис,
                                 affiliate=Филиал,
                                 division=Дивизия,
                                 direction=Дирекция,
                                 department=Отдел,
                                 plant=Завод,
                                 workshop=Цех,
                                 store=Склад,
				                 shop=Магазин,
                                 unit=Звено,
                                 brigade=Бригада,
                                 shift=Смяна,
                                 organization=Учреждение)', 'caption=Тип, mandatory,width=100%');
        $this->FLD('departmentId', 'key(mvc=hr_Departments,select=name)', 'caption=В състава на,silent');
        $this->FLD('planningParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Параметри за планиране->Списък');
        $this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)', 'caption=Служители->НКИД, hint=Номер по НКИД');
        $this->FLD('employmentTotal', 'int', 'caption=Служители->Щат, input=none');
        $this->FLD('employmentOccupied', 'int', 'caption=Служители->Назначени, input=none');
        $this->FLD('scheduleId', 'key(mvc=hr_Schedules, select=name, allowEmpty=true)', 'caption=Работен график->Разписание,mandatory');
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=active,notNull,input=none');
        $this->FLD('mandatoryOperatorsInTasks', 'enum(auto=Автоматично,lastAndMandatory=Последно въведен (и задължително),lastAndOptional=Последно въведен (и опционално),emptyAndMandatory=Празно (и задължително),emptyAndOptional=Празно (и опционално), current=Текущ оператор)', 'caption=Прогрес в ПО->Оператор(и), notNull,value=auto');
        $this->FLD('showPreviousJobField', 'enum(auto=Автоматично,yes=Показване,no=Скриване)', 'caption=Показване на предишно задание в ПО->Избор, notNull,value=auto');
        $this->FLD('showSerialWarningOnDuplication', 'enum(auto=Автоматично,yes=Разрешено - с предупреждение,no=Разрешено - без предупреждение)', 'caption=Повторение на един производствен номер в рамките на една Операция->Избор,notNull,value=auto');
        $this->FLD('allowDuplicateSerialProgress', 'enum(auto=Автоматично,yes=Разрешено,no=Забранено)', 'caption=Използване на един производствен номер в различни Операции->Избор,notNull,value=auto');

        $this->FLD('useTareFromPackagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Източник на тара за приспадане от теглото в ПО->Опаковки');
        $this->FLD('useTareFromParamId', 'key(mvc=cat_Params,select=typeExt, allowEmpty)', 'caption=Източник на тара за приспадане от теглото в ПО->Параметър,silent,removeAndRefreshForm=useTareFromParamMeasureId');
        $this->FLD('useTareFromParamMeasureId', 'key(mvc=cat_UoM,select=name)', 'caption=Източник на тара за приспадане от теглото в ПО->Мярка,input=hidden', "unit= (|каква е мярката на избрания параметър|*)");
        $this->FLD('deviationNettoNotice', 'percent(Min=0)', 'caption=Статус при разминаване на нетото в ПО->Отбелязване');
        $this->FLD('deviationNettoWarning', 'percent(Min=0)', 'caption=Статус при разминаване на нетото в ПО->Предупреждение');
        $this->FLD('deviationNettoCritical', 'percent(Min=0)', 'caption=Статус при разминаване на нетото в ПО->Критично');
        $this->FLD('paramExpectedNetWeight', 'key(mvc=cat_Params,select=typeExt, allowEmpty)', 'caption=Източник за "единично тегло" - за сравняване на очакваното с реалното от прогреса->Параметър,silent,removeAndRefreshForm=paramExpectedNetMeasureId', "unit= |по количеството от Прогреса|*");
        $this->FLD('paramExpectedNetMeasureId', 'key(mvc=cat_UoM,select=name)', 'caption=Източник за "единично тегло" - за сравняване на очакваното с реалното от прогреса->Мярка,input=hidden', "unit= (|каква е мярката на избрания параметър|*)");
        $this->FLD('showMaxPreviousTasksInATask', 'int', 'caption=За колко от предходните Операции да се визуализира готовността->До');
        $this->FLD('autoCreateTaskState', 'enum(auto=Автоматично,pending=Заявка,draft=Чернова)', 'caption=Състояние на ПО след автоматично създаване от Рецепта->Състояние,value=auto,notNull');
        $this->FLD('supportSystemFolderId', 'key2(mvc=doc_Folders,select=title,coverClasses=support_Systems,allowEmpty)', 'caption=Система за подаване на сигнали->Система');

        $powerUserId = core_Roles::fetchByName('powerUser');
        $this->FLD('supportUsers', "keylist(mvc=core_Users, select=nick, where=#state !\\= \\'rejected\\' AND #roles LIKE '%|{$powerUserId}|%')", 'caption=Система за подаване на сигнали->Отговорници');
        $this->setDbUnique('name');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $paramSuggestions = cat_Params::getTaskParamOptions($form->rec->planningParams);
        $form->setSuggestions("planningParams", $paramSuggestions);

        $options = cat_UoM::getPackagingOptions();
        $form->setSuggestions('useTareFromPackagings', $options);
        $kgMeasureId = cat_UoM::fetchBySysId('kg')->id;
        $kgDerivitives = cat_Uom::getSameTypeMeasures($kgMeasureId, false, false);

        if(isset($rec->useTareFromParamId)){
            $form->setField('useTareFromParamMeasureId', 'input');
            $form->setOptions('useTareFromParamMeasureId', $kgDerivitives);
            $form->setDefault('useTareFromParamMeasureId', $kgMeasureId);
        }

        if(isset($rec->paramExpectedNetWeight)){
            $form->setField('paramExpectedNetMeasureId', 'input');
            $form->setOptions('paramExpectedNetMeasureId', $kgDerivitives);
            $form->setDefault('paramExpectedNetMeasureId', $kgMeasureId);
        }

        // Достъпните за избор параметри
        $paramOptions = cat_Params::getOptionsByDriverClass(array('cond_type_Double', 'cond_type_Int', 'cond_type_Formula'), 'typeExt', true);
        if(isset($rec->useTareFromParamId)){
            if(!array_key_exists($rec->useTareFromParamId, $paramOptions)){
                $paramOptions[$rec->useTareFromParamId] = cat_Params::getVerbal($rec->useTareFromParamId, 'typeExt');
            }
        }
        $form->setOptions('useTareFromParamId', array('' => '') + $paramOptions);
        $form->setOptions('paramExpectedNetWeight', array('' => '') + $paramOptions);
        $form->setField("showMaxPreviousTasksInATask", "placeholder=" . $mvc->getFieldType('showMaxPreviousTasksInATask')->toVerbal(planning_Setup::get('SHOW_PREVIOUS_TASK_BLOCKS')));
        $form->setField("deviationNettoWarning", "placeholder=" . $mvc->getFieldType('deviationNettoWarning')->toVerbal(planning_Setup::get('TASK_NET_WEIGHT_WARNING')));
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        static::checkDeviationPercents($form);
    }


    /**
     * Проверка на полетата за преудпреждения
     *
     * @param core_Form $form
     * @param string $noticeField
     * @param string $warningField
     * @param string $criticalField
     * @return void
     */
    public static function checkDeviationPercents($form, $noticeField = 'deviationNettoNotice', $warningField = 'deviationNettoWarning', $criticalField = 'deviationNettoCritical')
    {
        $rec = &$form->rec;
        $warning = isset($rec->{$warningField}) ? $rec->{$warningField} : planning_Setup::get('TASK_NET_WEIGHT_WARNING');

        if(!empty($rec->{$noticeField})){
            if(isset($warning)){
                if($rec->{$noticeField} >= $warning){
                    $form->setError("{$noticeField},{$warningField}", 'Предупреждението трябва да е по-голямо от отбелязването');
                }
            }
            if(isset($rec->{$criticalField})){
                if($rec->{$noticeField} >= $rec->{$criticalField}){
                    $form->setError("{$noticeField},{$criticalField}", 'Критичното трябва да е по-голямо от отбелязването');
                }
            }
        }

        if(!empty($warning)){
            if(isset($rec->{$criticalField})){
                if($warning >= $rec->{$criticalField}){
                    $form->setError("{$warningField},{$criticalField}", 'Критичното трябва да е по-голямо от предупреждението');
                }
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $row->folderId = doc_Folders::getFolderTitle($rec->folderId);
        }
        
        if (isset($rec->departmentId)) {
            $row->departmentId = hr_Departments::getHyperlink($rec->departmentId, true);
        }

        if(isset($rec->scheduleId)){
            $row->scheduleId = hr_Schedules::getHyperlink($rec->scheduleId, true);
        }

        $row->hrGroupId = crm_Groups::getHyperlink(static::getEmployeesGroupId($rec));

        if($rec->mandatoryOperatorsInTasks == 'auto'){
            $row->mandatoryOperatorsInTasks = $mvc->getFieldType('mandatoryOperatorsInTasks')->toVerbal(planning_Setup::get('TASK_PROGRESS_OPERATOR'));
            $row->mandatoryOperatorsInTasks = ht::createHint("<span style='color:blue'>{$row->mandatoryOperatorsInTasks}</span>", 'По подразбиране', 'notice', false);
        }

        if($rec->autoCreateTaskState == 'auto'){
            $row->autoCreateTaskState = $mvc->getFieldType('autoCreateTaskState')->toVerbal(planning_Setup::get('AUTO_CREATE_TASK_STATE'));
            $row->autoCreateTaskState = ht::createHint("<span style='color:blue'>{$row->autoCreateTaskState}</span>", 'По подразбиране', 'notice', false);
        }

        if($rec->showPreviousJobField == 'auto'){
            $row->showPreviousJobField = $mvc->getFieldType('showPreviousJobField')->toVerbal(planning_Setup::get('SHOW_PREVIOUS_JOB_FIELD_IN_TASK'));
            $row->showPreviousJobField = ht::createHint("<span style='color:blue'>{$row->showPreviousJobField}</span>", 'По подразбиране', 'notice', false);
        }

        if($rec->showSerialWarningOnDuplication == 'auto'){
            $row->showSerialWarningOnDuplication = $mvc->getFieldType('showSerialWarningOnDuplication')->toVerbal(planning_Setup::get('WARNING_DUPLICATE_TASK_PROGRESS_SERIALS'));
            $row->showSerialWarningOnDuplication = ht::createHint("<span style='color:blue'>{$row->showSerialWarningOnDuplication}</span>", 'По подразбиране', 'notice', false);
        }

        if($rec->allowDuplicateSerialProgress == 'auto'){
            $row->allowDuplicateSerialProgress = $mvc->getFieldType('allowDuplicateSerialProgress')->toVerbal(planning_Setup::get('ALLOW_SERIAL_IN_DIFF_TASKS'));
            $row->allowDuplicateSerialProgress = ht::createHint("<span style='color:blue'>{$row->allowDuplicateSerialProgress}</span>", 'По подразбиране', 'notice', false);
        }

        $row->deviationNettoWarning = isset($rec->deviationNettoWarning) ? $row->deviationNettoWarning : ht::createHint("<span style='color:blue'>{$mvc->getFieldType('deviationNettoWarning')->toVerbal(planning_Setup::get('TASK_NET_WEIGHT_WARNING'))}</span>", 'Автоматично', 'notice', false);

        if(empty($rec->showMaxPreviousTasksInATask)){
            $row->showMaxPreviousTasksInATask = $mvc->getFieldType('showMaxPreviousTasksInATask')->toVerbal(planning_Setup::get('SHOW_PREVIOUS_TASK_BLOCKS'));
            $row->showMaxPreviousTasksInATask = ht::createHint("<span style='color:blue'>{$row->showMaxPreviousTasksInATask}</span>", 'По подразбиране', 'notice', false);
        }

        if(isset($rec->useTareFromParamId) && isset($row->useTareFromParamMeasureId)){
            $row->useTareFromParamId = ht::createHint($row->useTareFromParamId, $row->useTareFromParamMeasureId);
        }

        if(isset($rec->paramExpectedNetWeight) && isset($row->paramExpectedNetMeasureId)){
            $row->paramExpectedNetWeight = ht::createHint($row->paramExpectedNetWeight, $row->paramExpectedNetMeasureId);
        }

        if(isset($rec->supportSystemFolderId)){
            $row->supportSystemFolderId = doc_Folders::recToVerbal($rec->supportSystemFolderId)->title;
        }
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    public function getItemRec($objectId)
    {
        $result = null;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object) array(
                'title' => $rec->name . ' ac',
                'num' => 'Ac' . $rec->id,
                'features' => array(),
            );

            if(isset($rec->departmentId)){
                $result->features['В състава на'] = hr_Departments::getTitleById($rec->departmentId);
                $departmentLocationId = hr_Departments::fetchField($rec->departmentId, 'locationId');
                if(isset($departmentLocationId)){
                    $result->features['Локация'] = crm_Locations::getTitleById($departmentLocationId);
                }
            }
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * След инсталирането на модела
     */
    public function loadSetupData()
    {
        if (!$this->fetchField(self::UNDEFINED_ACTIVITY_CENTER_ID, 'id')) {
            $rec = new stdClass();
            $rec->id = self::UNDEFINED_ACTIVITY_CENTER_ID;
            $rec->name = 'Неопределен';
            $rec->type = 'workshop';
            $rec->state = 'active';
            $rec->scheduleId = hr_Schedules::getDefaultScheduleId();

            core_Users::forceSystemUser();
            $this->save($rec, null, 'REPLACE');
            core_Users::cancelSystemUser();
        }
    }
    
    
    /**
     * Какви видове ресурси може да се добавят към модела
     *
     * @param stdClass $rec
     *
     * @return array - празен масив ако няма позволени ресурси
     *               ['assets'] - оборудване
     *               ['hr']     - служители
     */
    public function getResourceTypeArray_($rec)
    {
        return arr::make('assets,hr', true);
    }
    
    
    /**
     * Връща папката на неопределения център на дейност
     */
    public static function getUndefinedFolderId()
    {
        return planning_Centers::fetchField(planning_Centers::UNDEFINED_ACTIVITY_CENTER_ID, 'folderId');
    }
    
    
    /**
     * Подготовка на центровете към департаментите
     *
     * @param stdClass $data
     */
    public function prepareCenters(&$data)
    {
        $data->TabCaption = 'Центрове';
        $data->Tab = 'top';
        
        // Извличане на центровете към департамента
        $data->recs = $data->rows = array();
        $data->query = $this->getQuery();
        $data->query->where("#departmentId = {$data->masterId}");
        
        $this->prepareListFields($data);
        while ($rec = $data->query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
        
        if ($this->haveRightFor('add')) {
            $data->addUrl = array($this, 'add', 'departmentId' => $data->masterId);
        }
    }
    
    
    /**
     * Рендиране на центровете към департаментите
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderCenters($data)
    {
        // Подготовка на шаблона
        $tpl = new core_ET(tr('|*<fieldset><legend class="groupTitle">|Центрове на дейност|*[#addBtn#]</legend>[#content#]</fieldset>'));
        unset($data->listFields['departmentId']);
        unset($data->listFields['folderId']);
        
        // Рендиране на данните
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, $this->hideListFieldsIfEmpty);
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tpl->append($table->get($data->rows, $data->listFields), 'content');
        
        // Добавяне на бутон за нов център
        if (isset($data->addUrl)) {
            $addLink = ht::createLink('', $data->addUrl, false, 'ef_icon=img/16/add.png,title=Добавяне на нов център към департамента');
            $tpl->append($addLink, 'addBtn');
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        return self::getVerbal($rec, 'name');
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        // Искаме състоянието на оттеглените чернови да се казва 'Анулиран'
        if ($part == 'name') {
            if ($rec->id == self::UNDEFINED_ACTIVITY_CENTER_ID) {
                $num = planning_Setup::get('UNDEFINED_CENTER_DISPLAY_NAME');
            }
        }
    }
    
    
    /**
     * Производствени етапи в папката на центъра на дейност
     *
     * @param int|null $jobId
     * @param int|null $userId
     * 
     * @return array $options
     */
    public static function getCentersForTasks($jobId = null, $userId = null)
    {
        $options = array();
        if(isset($jobId)){
            $jobRec = planning_Jobs::fetch($jobId, 'folderId,productId');
            $Cover = doc_Folders::getCover($jobRec->folderId);

            // Ако артикула може да се създава само в един център остава само той
            if($Driver = cat_Products::getDriver($jobRec->productId)) {
                $productionData = $Driver->getProductionData($jobRec->productId);
                if(isset($productionData['centerId'])){
                    $options[$jobRec->folderId] = planning_Centers::getTitleById($productionData['centerId'], false);

                    return $options;
                }
            }

            if($Cover->isInstanceOf('planning_Centers')){
                $options[$jobRec->folderId] = $Cover->getRecTitle(false);
            }
        }
        
        $query = self::getQuery();
        $query->where("#state != 'closed' AND #state != 'rejected'");
        while($rec = $query->fetch()){  
            if (doc_Folders::haveRightToFolder($rec->folderId, $userId)) {
                $options[$rec->folderId] = self::getRecTitle($rec, false);
            }
        }
 
        return $options;
    }
    
    
    /**
     * След подготовка на филтъра
     */
    protected static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#state');
    }


    /**
     * Екшън редактиращ потребителя към първия му достъпен модел в пакета
     */
    function act_dispatch()
    {
        requireRole('powerUser');

        if(planning_DirectProductionNote::haveRightFor('list')){
            redirect(array('planning_DirectProductionNote', 'list'));
        } elseif(planning_ConsumptionNotes::haveRightFor('list')){
            redirect(array('planning_ConsumptionNotes', 'list'));
        } elseif(planning_Jobs::haveRightFor('list')){
            redirect(array('planning_Jobs', 'list'));
        } elseif(planning_Tasks::haveRightFor('list')){
            redirect(array('planning_Tasks', 'list'));
        } elseif(planning_Centers::haveRightFor('list')) {
            redirect(array('planning_Centers', 'list'));
        }

        redirect(array('bgerp_Portal', 'show'), false, 'Нямате достъп до таб от менюто', 'warning');
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Синхронизиране на системата група за оператори спрямо името на центъра
        static::syncCrmGroup($rec->id);
    }


    /**
     * В коя системна група ще бъдат служителите от центъра
     *
     * @param int $id
     * @return mixed
     */
    public static function getEmployeesGroupId($id)
    {
        $rec = static::fetchRec($id);

        return crm_Groups::getIdFromSysId("planningCenter{$rec->id}");
    }


    /**
     * Синхронизира системната група за операторите на центъра на дейност
     *
     * @param int $id
     * @return int
     */
    public static function syncCrmGroup($id)
    {
        $rec = static::fetchRec($id);
        $centerGroupId = crm_Groups::getIdFromSysId('activityCenters');
        $groupRec = (object)array('name' => $rec->name, 'sysId' => "planningCenter{$rec->id}", 'parentId' => $centerGroupId);

        return crm_Groups::forceGroup($groupRec);
    }
}
