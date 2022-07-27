<?php 

/**
 * Мениджър за Центровете на дейност
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Centers extends core_Master
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'planning_ActivityCenters';
    
    
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
    public $canList = 'ceo, planning, job';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, planning, job';
    
    
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
        $this->FLD('mandatoryOperatorsInTasks', 'enum(auto=Автоматично,yes=Задължително,no=Опционално)', 'caption=Прогрес в ПО->Оператор(и), notNull,value=auto');
        $this->FLD('showPreviousJobField', 'enum(auto=Автоматично,yes=Показване,no=Скриване)', 'caption=Показване на предишно задание в ПО->Избор, notNull,value=auto');

        $this->setDbUnique('name');
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

        if($rec->mandatoryOperatorsInTasks == 'auto'){
            $row->mandatoryOperatorsInTasks = $mvc->getFieldType('mandatoryOperatorsInTasks')->toVerbal(planning_Setup::get('TASK_PROGRESS_MANDATORY_OPERATOR'));
            $row->mandatoryOperatorsInTasks = ht::createHint("<span style='color:blue'>{$row->mandatoryOperatorsInTasks}</span>", 'По подразбиране', 'notice', false);
        }

        if($rec->showPreviousJobField == 'auto'){
            $row->showPreviousJobField = $mvc->getFieldType('showPreviousJobField')->toVerbal(planning_Setup::get('SHOW_PREVIOUS_JOB_FIELD_IN_TASK'));
            $row->showPreviousJobField = ht::createHint("<span style='color:blue'>{$row->showPreviousJobField}</span>", 'По подразбиране', 'notice', false);
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
    public function getResourceTypeArray($rec)
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
     * @param int $folderId   - ид на папка
     * @param int|null $exId  - ид на предишно избран артикул
     * @param bool $verbal    - само ид-та или имена на артикулите
     * @return array $options - върнатите опции
     */
    public static function getPlanningStepOptionsByFolderId($folderId, $exId = null, $verbal = false)
    {
        $Cover = doc_Folders::getCover($folderId);
        $finalSteps = $nonFinalSteps = array();
        $sQuery = planning_Steps::getQuery();

        // Извличат се ПЕ към този център на дейност
        $productClassId = cat_Products::getClassId();
        $sQuery->where("#centerId = {$Cover->that} AND #state != 'closed' AND #state != 'rejected' AND #classId = {$productClassId}");
        while($sRec = $sQuery->fetch()){

            // Разделят се дали са финални или междинни
            if($Extended = planning_Steps::getExtended($sRec)){
                if($sRec->isFinal == 'yes'){
                    $finalSteps[$Extended->that] = ($verbal) ? $Extended->getTitleById(false) : $Extended->that;
                } else {
                    $nonFinalSteps[$Extended->that] = ($verbal) ? $Extended->getTitleById(false) : $Extended->that;
                }
            }
        }

        // Ако има съществуващо ид и то не е сред наличните добавям го в правилния масив
        if(isset($exId)){
            if(!array_key_exists($exId, $finalSteps) || !array_key_exists($exId, $nonFinalSteps)){
                $isExIdFinal = planning_Steps::fetchField("#classId = {$productClassId} AND #objectId = {$exId}", 'isFinal');
                $exIdVal = ($verbal) ? cat_Products::getTitleById($exId) : $exId;
                if($isExIdFinal == 'no'){
                    $nonFinalSteps[$exId] = $exIdVal;
                } else {
                    $finalSteps[$exId] = $exIdVal;
                }
            }
        }

        // Ако се показват вербални опции - слага се група на опциите и се обръщат имената на артикулите
        if($verbal){
            $options = array();
	    if(countR($nonFinalSteps)){
                $options += array('nfs' => (object) array('group' => true, 'title' => tr('Междинни етапи'))) + $nonFinalSteps;
            }
            if(countR($finalSteps)){
                $options += array('fs' => (object) array('group' => true, 'title' => tr('Финални етапи'))) + $finalSteps;
            }            
        } else {
            $options = $nonFinalSteps + $finalSteps;
        }

        return $options;
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
}
