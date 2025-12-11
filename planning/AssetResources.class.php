<?php


/**
 * Мениджър на Оборудвания
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_AssetResources extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Машини, съоръжения, сгради';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'createdOn, createdBy, modifiedOn, modifiedBy';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_Search, plg_Sorting, plg_Modified, plg_Clone, plg_SaveAndNew, plg_PrevAndNext, plg_Select';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo, planningMaster';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, planning, support, taskWorker';
    
    
    /**
     * Кой има право да разглежда сингъла?
     */
    public $canSingle = 'ceo, planning, support, taskWorker';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Оборудване,code,groupId,createdOn,createdBy,state';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оборудване';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'protocols';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleAssetResource.shtml';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/equipment.png';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, code, groupId, description, protocols';
    
    
    /**
     * Детайли
     */
    public $details = 'assetSupport=support_TaskType,Tasks=planning_Tasks,planning_AssetResourcesNorms,planning_AssetResourceFolders,planning_AssetSparePartsDetail,planning_AssetScheduleBreaks';
    
    
    /**
     * Шаблон (ET) за заглавие
     *
     * @var string
     */
    public $recTitleTpl = '[[#code#]] [#name#]';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory,remember=info');
        $this->FNC('shortName', 'varchar', 'caption=Кратко име');
        $this->FLD('groupId', 'key(mvc=planning_AssetGroups,select=name,allowEmpty)', 'caption=Вид,mandatory,silent,remember');
        $this->FLD('code', 'varchar(16,autocomplete=off)', 'caption=Код,mandatory,remember=info');
        $this->FLD('protocols', 'keylist(mvc=accda_Da,select=title)', 'caption=ДА,silent,input=none');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        
        $this->FLD('image', 'fileman_FileType(bucket=planningImages)', 'caption=Допълнително->Снимка');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание');
        
        $powerUserId = core_Roles::fetchByName('powerUser');
        $this->FLD('unsortedFolders', 'keylist(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Използване в проекти->Проекти,remember');
        $this->FLD('unsortedUsers', "keylist(mvc=core_Users, select=nick, where=#state !\\= \\'rejected\\' AND #roles LIKE '%|{$powerUserId}|%')", 'caption=Използване в проекти->Отговорници,remember');
        $this->FLD('assetFolders', 'keylist(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Използване за производство->Центрове на дейност,oldFieldName=assetFolderId,remember');
        $this->FLD('scheduleId', 'key(mvc=hr_Schedules, select=name, allowEmpty)', 'caption=Използване за производство->Работен график,remember');
        $this->FLD('taskQuantization', 'enum(day=Дневно,weekly=Седмично,monthly=Месечно)', 'caption=Групиране на операции при планиране и подредба->Избор,notNull,value=weekly,autohide=any');

        $this->FLD('assetUsers', "keylist(mvc=core_Users, select=nick, where=#state !\\= \\'rejected\\' AND #roles LIKE '%|{$powerUserId}|%')", 'caption=Използване за производство->Отговорници,remember');
        $this->FLD('simultaneity', 'int(min=0)', 'caption=Използване за производство->Едновременност,notNull,value=1, oldFieldName=quantity,remember');
        $this->FLD('planningParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Използване за производство->Параметри за планиране');

        $this->FLD('systemFolderId', 'keylist(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Поддръжка->Системи,remember');
        $this->FLD('systemUsers', "keylist(mvc=core_Users, select=nick, where=#state !\\= \\'rejected\\' AND #roles LIKE '%|{$powerUserId}|%')", 'caption=Поддръжка->Отговорници,remember');
        
        $this->FLD('indicators', 'keylist(mvc=sens2_Indicators,select=title, allowEmpty)', 'caption=Други->Сензори');
        $this->FLD('cameras', 'keylist(mvc=cams_Cameras,select=title, allowEmpty)', 'caption=Други->Камери');
        $this->FLD('vehicle', 'key(mvc=tracking_Vehicles,select=number, allowEmpty)', 'caption=Други->Тракер');
        $this->FLD('lastRecalcTimes', 'datetime(format=smartTime)', 'caption=Последно->Преизчислени времена,input=none');
        $this->FLD('lastReorderedTasks', 'datetime(format=smartTime)', 'caption=Последно->Преподредени операции,input=none');
        $this->FNC('fromProtocolId', 'key(mvc=accda_Da,select=id)', 'silent,input=hidden');

        $this->setDbUnique('code');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        // От кое ДМА е оборудването
        if (!empty($rec->protocols)) {
            $protocolIds = keylist::toArray($rec->protocols);
            $protocolLinks = array();
            foreach ($protocolIds as $protocolId){
                $protocolLinks[] = accda_Da::getHyperlink($protocolId, true);
            }
            $form->info = "<div class='formCustomInfo'>" . tr('Обвързано с')  . ' ' . implode(',', $protocolLinks) . "</div>";
        }
        
        $defOptArr = array();
        if (isset($rec->id)) {
            $fQuery = planning_AssetResourceFolders::getQuery();
            $fQuery->where(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $mvc->getClassId(), $rec->id));
            while ($fRec = $fQuery->fetch()) {
                $Cover = doc_Folders::getCover($fRec->folderId);
                $keyName = $Cover->isInstanceOf('doc_UnsortedFolders') ? 'unsortedFolders' : (($Cover->isInstanceOf('support_Systems') ? 'systemFolderId' : 'assetFolders'));
                $defOptArr[$keyName]['folders'][$fRec->folderId] = $fRec->folderId;
                if ($fRec->users) {
                    $defOptArr[$keyName]['users'] = type_Keylist::merge($defOptArr[$keyName]['users'], $fRec->users);
                }
            }
        }

        // Добавяне на достъпните за избор планиращи параметри
        $paramSuggestions = cat_Params::getTaskParamOptions($form->rec->planningParams);
        $form->setSuggestions("planningParams", $paramSuggestions);

        if (!core_Packs::isInstalled('tracking')) {
            $form->setField('vehicle', 'input=none');
        }
        
        if (!core_Packs::isInstalled('cams')) {
            $form->setField('cameras', 'input=none');
        }
        
        if (!core_Packs::isInstalled('sens2')) {
            $form->setField('indicators', 'input=none');
        }
        
        // Какви са достъпните папки за оборудване
        $unsortedSuggestionsArr = array();
        $uQuery = doc_UnsortedFolders::getQuery();
        $uQuery->where("#state != 'rejected'");
        $uQuery->where("LOCATE('assets', #resourceType)");
        while($uRec = $uQuery->fetch()){
            if(doc_Folders::haveRightToFolder($uRec->folderId)){
                $unsortedSuggestionsArr[$uRec->folderId] = doc_Folders::getTitleById($uRec->folderId);
            }
        }
        if (empty($unsortedSuggestionsArr)) {
            $form->setField('unsortedFolders', 'input=hidden');
            $form->setField('unsortedUsers', 'input=hidden');
        } else {
            $form->setSuggestions('unsortedFolders', array('' => '') + $unsortedSuggestionsArr);
        }

        $resourceSuggestionsArr = doc_Folders::getSelectArr(array('titleFld' => 'title', 'restrictViewAccess' => 'yes', 'coverClasses' => 'planning_Centers'));
        if (empty($resourceSuggestionsArr)) {
            $form->setField('assetFolders', 'input=hidden');
            $form->setField('assetUsers', 'input=hidden');
        } else {
            $form->setSuggestions('assetFolders', array('' => '') + $resourceSuggestionsArr);
        }
        
        $supportSuggestionsArr = doc_Folders::getSelectArr(array('titleFld' => 'title', 'restrictViewAccess' => 'yes', 'coverClasses' => 'support_Systems'));
        if (empty($supportSuggestionsArr)) {
            $form->setField('systemFolderId', 'input=hidden');
            $form->setField('systemUsers', 'input=hidden');
        } else {
            $form->setSuggestions('systemFolderId', array('' => '') + $supportSuggestionsArr);
        }
        
        if (empty($rec->id)) {
            $defaultFolderId = Request::get('defaultFolderId', 'int');
            $defaultFolderId = !empty($defaultFolderId) ? $defaultFolderId : planning_Centers::getUndefinedFolderId();

            $Cover = doc_Folders::getCover($defaultFolderId);
            $field = $Cover->isInstanceOf('support_Systems') ? 'systemFolderId' : ($Cover->isInstanceOf('doc_UnsortedFolders') ? 'unsortedFolders' : 'assetFolders');
            $form->setDefault($field, keylist::addKey('', $defaultFolderId));
        } else {
            $form->rec->unsortedFolders = type_Keylist::fromArray($defOptArr['unsortedFolders']['folders']);
            $form->rec->unsortedUsers = type_Keylist::fromArray($defOptArr['unsortedFolders']['users']);
            $form->rec->assetFolders = type_Keylist::fromArray($defOptArr['assetFolders']['folders']);
            $form->rec->assetUsers = $defOptArr['assetFolders']['users'];
            $form->rec->systemFolderId = type_Keylist::fromArray($defOptArr['systemFolderId']['folders']);
            $form->rec->systemUsers = $defOptArr['systemFolderId']['users'];
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            $assetFolderErrors = array();
            if (empty($rec->assetFolders)) {
                if($rec->assetUsers){
                    $assetFolderErrors[] = '|Не е избран център на дейност, но са избрани отговорници|*!';
                }

                $resourceType = planning_AssetGroups::fetchField($rec->groupId, 'type');
                if($resourceType == 'material'){
                    $selectedCenterCounts = countR(keylist::toArray($rec->assetFolders));
                    if($selectedCenterCounts > 1){
                        $assetFolderErrors[] = '|Материалните ресурси НЕ МОЖЕ да са споделени в повече от един център|*!';
                    }
                }

               if(countR($assetFolderErrors)){
                   $form->setError('assetFolders', implode('<br>', $assetFolderErrors));
               }
            }
            
            if (!$rec->systemFolderId && $rec->systemUsers) {
                $form->setError('systemFolderId', 'Не е избрана система за поддръжка, но са избрани отговорници|*!');
            }

            if (!$rec->unsortedFolders && $rec->unsortedUsers) {
                $form->setError('unsortedFolders', 'Не е избран проект, но са избрани отговорници|*!');
            }

            if(empty($rec->simultaneity) && $rec->assetFolders){
                $form->setWarning('simultaneity', "Ако изберете '0' ресурсът няма да може да бъде избиран в производствени операции|*!");
            }

            if (!$rec->assetFolders && $rec->simultaneity) {
                $form->setWarning('assetFolders,simultaneity', "Избрана е едновременност, но не е избран център на дейност - ресурсът няма да може да бъде избиран в производствени операции|*!");
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->groupId = planning_AssetGroups::getHyperlink($rec->groupId, true);
        $row->created = "{$row->createdOn} " . tr('от') . " {$row->createdBy}";

        if (isset($fields['-single'])) {

            // Ако няма посочени планиращи параметри - показват се тези от групата
            if(empty($rec->planningParams)){
                $groupPlanningParams = planning_AssetGroups::fetchField($rec->groupId, 'planningParams');
                if(!empty($groupPlanningParams)){
                    $row->planningParams = $mvc->getFieldType('planningParams')->toVerbal($groupPlanningParams);
                    $row->planningParams = ht::createHint($row->planningParams, 'Посочени са в групата на оборудването', 'notice', false);
                }
            }

            $scheduleId = $rec->scheduleId ?? self::getScheduleId($rec->id);
            $row->scheduleId = hr_Schedules::getHyperlink($scheduleId, true);

            if(!isset($rec->scheduleId)){
                $row->scheduleId = ht::createHint($row->scheduleId, 'Машината няма свой график, а ползва графика на първия ѝ споделен център на дейност!');
            }

            $row->SingleIcon = ht::createElement('img', array('src' => sbf(str_replace('/16/', '/32/', $mvc->singleIcon), ''), 'alt' => ''));
            
            if ($rec->image) {
                $row->image = fancybox_Fancybox::getImage($rec->image, array(120, 120), array(1200, 1200));
            }

            // Сензорите
            if ($rec->indicators) {
                $row->sensors = sens2_Indicators::renderIndicator(type_Keylist::toArray($rec->indicators));
            }
            
            // Камери
            if ($rec->cameras) {
                $row->cams = '';
                foreach (type_Keylist::toArray($rec->cameras) as $cId) {
                    try {
                        $cRow = cams_Cameras::recToVerbal(cams_Cameras::fetchRec($cId), 'liveImg');
                        $liveImg = $cRow->liveImg;
                    } catch (core_exception_Expect $e) {
                        reportException($e);
                        $liveImg = tr('Грешка при показване');
                    }
                    
                    if (cams_Records::haveRightFor('single')) {
                        $liveImg = ht::createLinkRef($liveImg, array('cams_Records', 'cameraId' => $cId));
                    }
                    
                    $row->cams .= '<div>' . $liveImg . '</div>';
                }
            }
            
            // Проследяване
            if ($rec->vehicle) {
                $vRec = tracking_Vehicles::fetch($rec->vehicle);
                $vRow = tracking_Vehicles::recToVerbal($vRec);
                $vehicle = "{$vRow->make} {$vRow->model} {$vRow->number} -  {$vRow->personId}";
                if (tracking_Log::haveRightFor('list', $vRec)) {
                    $vehicle = ht::createLinkRef($vehicle, array('tracking_Log', 'vehicleId' => $vRec->id));
                }
                $row->tracking = "<div class='state-{$vRec->state}'>{$vehicle}</div>";
            }

            if (!empty($rec->protocols)) {
                $daArray = array();
                foreach (keylist::toArray($rec->protocols) as $protocolId){
                    $daArray[] = accda_Da::getHyperlink($protocolId, true);
                }
                $row->protocols = implode(',', $daArray);
            }
        }
    }


    /**
     *
     *
     * @param $mvc
     * @param $rec
     */
    protected static function on_CalcShortName($mvc, &$rec)
    {
        if (!$rec->shortName) {
            $rec->shortName = '[' . $rec->code . '] ' . $rec->name;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            if (isset($rec->fromProtocolId)) {
                $state = accda_Da::fetchField($rec->fromProtocolId, 'state');
                if ($state != 'active') {
                    $requiredRoles = 'no_one';
                } else {
                    if ($mvc->fetch("LOCATE('|{$rec->fromProtocolId}|', #protocols)")) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }

        // Ако е използван в група, не може да се изтрива
        if ($action == 'delete' && isset($rec->id)) {
            if (isset($rec->lastUsedOn) || planning_AssetResourcesNorms::fetchField("#classId = {$mvc->getClassId()} AND #objectId = '{$rec->id}'") || planning_AssetResourceFolders::fetchField("#classId = {$mvc->getClassId()} AND #objectId = '{$rec->id}'")) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако е в група и тя е затворена да не може да се променя
        if ($action == 'changestate' && isset($rec->groupId)) {
            $groupState = planning_AssetGroups::fetchField($rec->groupId, 'state');
            if ($groupState == 'closed') {
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'recalctime' && isset($rec)){
            if(isset($rec->isDebug)){
                if(!haveRole('debug')){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FNC('folderId', 'key(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Папка,silent,remember,input,refreshForm');
        $data->listFilter->setFieldType('state', 'enum(all=Всички,active=Активни,closed=Затворени)');
        $data->listFilter->setDefault('state', 'active');

        $resourceSuggestionsArr = doc_FolderResources::getFolderSuggestions('assets');
        $data->listFilter->setOptions('folderId', array('' => '') + $resourceSuggestionsArr);
        
        $data->listFilter->showFields = 'search,groupId,folderId,state';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->FLD('type', 'enum(material=Материален, nonMaterial=Нематериален)', 'caption=Тип, input=hidden, silent');
        $data->listFilter->input('type,folderId,state', true);

        if($filterRec = $data->listFilter->rec){
            if ($filterRec->groupId) {
                $data->query->where("#groupId = {$filterRec->groupId}");
            }

            if ($filterRec->type) {
                $data->query->EXT('type', 'planning_AssetGroups', 'externalName=type,externalKey=groupId');
                $data->query->where(array("#type = '[#1#]'", $filterRec->type));
            }

            if ($filterRec->folderId) {
                $data->query->likeKeylist('assetFolders', $filterRec->folderId);
                $data->query->orLikeKeylist('systemFolderId', $filterRec->folderId);
                $data->query->orLikeKeylist('unsortedFolders', $filterRec->folderId);
            }

            if ($filterRec->state && $filterRec->state != 'all') {
                $data->query->where("#state = '{$filterRec->state}'");
            }
        }


        $data->query->orderBy('modifiedOn', 'DESC');
    }
    
    
    /**
     * Избор на наличното оборудване в подадената папка
     *
     * @param int|null $folderId       - ид на папка, или null за всички папки
     * @param mixed $exIds             - ид-та които да се добавят към опциите
     * @param mixed $forMvc            - за кой клас
     * @param boolean $useSimultaneity - да се пропускат ли тези с нулева едновременност
     *
     * @return array $options    - налично оборудване
     */
    public static function getByFolderId($folderId = null, $exIds = null, $forMvc = null, $useSimultaneity = false)
    {
        $options = array();
        $noOptions = false;
        $forMvc = isset($forMvc) ? cls::get($forMvc) : null;

        // Ако папката не поддържа ресурси оборудване да не се връща нищо
        if (isset($folderId)) {
            if (!self::canFolderHaveAsset($folderId)) {
                $noOptions = true;
            }
        }

        // Ако ще се търсят опции
        if(!$noOptions){
            $fQuery = planning_AssetResourceFolders::getQuery();
            if (isset($folderId)) {
                $fQuery->where(array("#folderId = '[#1#]'", $folderId));
            }
            $fQuery->where(array("#classId = '[#1#]'", self::getClassId()));

            while ($fRec = $fQuery->fetch()) {
                if ($rec = self::fetch($fRec->objectId)) {
                    if ($rec->state == 'rejected' || $rec->state == 'closed') continue;
                    if($useSimultaneity && $rec->simultaneity == 0) continue;
                    if($forMvc instanceof planning_Tasks){
                        $showInPlanningTasks = planning_AssetGroups::fetchField($rec->groupId, 'showInPlanningTasks');
                        if($showInPlanningTasks != 'yes') continue;
                    }

                    $options[$rec->id] = self::getRecTitle($rec, false);
                }
            }
        }

        // Ако има съществуващи ид-та и тях ги няма в опциите да се добавят
        if(isset($exIds)) {
            $exOptions = keylist::isKeylist($exIds) ? keylist::toArray($exIds) : arr::make($exIds, true);
            foreach ($exOptions as $eId) {
                if (!array_key_exists($eId, $options)) {
                    $options[$eId] = self::getTitleById($eId, false);
                }
            }
        }

        return $options;
    }
    
    
    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = tr('Оборудване');

        // Подготовка на записите
        $query = self::getQuery();
        $query->where("#groupId = {$data->masterId}");
        $query->orderBy('state', 'ASC');
        $data->recs = $data->rows = array();
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
        
        // Има ли права за добавяне на ново оборудване
        if ($this->haveRightFor('add', (object) array('groupId' => $data->masterId))) {
            $data->addUrl = array($this, 'add', 'groupId' => $data->masterId, 'ret_url' => true);
        }
        
        return $data;
    }
    
    
    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $resTpl
     */
    public function renderDetail_($data)
    {
        $tpl = new core_ET('');
        
        // Рендиране на таблицата с оборудването
        $data->listFields = arr::make('code=Код,name=Оборудване,simultaneity=Едновременност,createdOn=Създадено->На,createdBy=Създадено->От,state=Състояние');
        $listTableMvc = clone $this;
        $listTableMvc->setField('name', 'tdClass=leftCol');

        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tpl->append($table->get($data->rows, $data->listFields));
        
        // Бутон за добавяне на ново оборудване
        if (isset($data->addUrl)) {
            $btn = ht::createBtn('Ново оборудване', $data->addUrl, false, false, "ef_icon={$this->singleIcon},title=Добавяне на ново оборудване към вида");
            $tpl->replace($btn, 'addAssetBtn');
        }

        $resTpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $resTpl->append($tpl, 'content');
        $resTpl->append(tr("Обордуване"), 'title');

        return $resTpl;
    }
    
    
    /**
     * Връща нормата на действието за оборудването
     *
     * @param int $id        - ид на оборудване
     * @param int $productId - ид на артикул
     *
     * @return stdClass|FALSE - запис на нормата или FALSE ако няма
     */
    public static function getNormRec($id, $productId)
    {
        if (empty($id)) return false;
        
        // Имали конкретна норма за артикула
        $aNorm = planning_AssetResourcesNorms::fetchNormRec('planning_AssetResources', $id, $productId);
        if (array_key_exists($productId, $aNorm)) {
            
            return $aNorm[$productId];
        }
        
        // Ако няма се търси нормата зададена в групата му
        $groupId = self::fetchField($id, 'groupId');
        $gNorm = planning_AssetResourcesNorms::fetchNormRec('planning_AssetGroups', $groupId, $productId);
        if (array_key_exists($productId, $gNorm)) {
            
            return $gNorm[$productId];
        }
        
        return false;
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        if ($folderId = Request::get('folderId', 'int')) {
            $Cover = doc_Folders::getCover($folderId);
            $data->form->title = core_Detail::getEditTitle($Cover->className, $Cover->that, $mvc->singleTitle, $data->form->rec->id, $mvc->formTitlePreposition);
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Първичния ключ на направения запис
     * @param stdClass     $rec    Всички полета, които току-що са били записани
     * @param string|array $fields Имена на полетата, които sa записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $rArr = array();
        $allFoldersArr = array();

        if ($rec->unsortedFolders) {
            $rArr[] = array('folderId' => type_Keylist::toArray($rec->unsortedFolders), 'users' => $rec->unsortedUsers);
        }

        if ($rec->assetFolders) {
            $rArr[] = array('folderId' => type_Keylist::toArray($rec->assetFolders), 'users' => $rec->assetUsers);
        }
        
        if ($rec->systemFolderId) {
            $rArr[] = array('folderId' => type_Keylist::toArray($rec->systemFolderId), 'users' => $rec->systemUsers);
        }

        $clsId = $mvc->getClassId();
        foreach ($rArr as $r) {
            if (!$r['folderId']) continue;
            
            foreach ($r['folderId'] as $fId) {
                $fRec = planning_AssetResourceFolders::fetch(array("#classId = '[#1#]' AND #objectId = '[#2#]' AND #folderId = '[#3#]'", $clsId, $rec->id, $fId));
                if (!$fRec) {
                    $fRec = new stdClass();
                    $fRec->classId = $clsId;
                    $fRec->objectId = $rec->id;
                    $fRec->folderId = $fId;
                }
                $allFoldersArr[$fId] = $fId;
                $fRec->users = $r['users'];
                
                planning_AssetResourceFolders::save($fRec);
            }
        }
        
        if ($allFoldersArr) {
            $values = implode(',', $allFoldersArr);
            planning_AssetResourceFolders::delete(array("#classId = '[#1#]' AND #objectId = '[#2#]' AND #folderId NOT IN ([#3#])", $clsId, $rec->id, $values));
        } else {
            $delFolders = false;
            if (!$fields) {
                $delFolders = true;
            } else {
                $fields = arr::make(true);
                if ($fields['assetFolders'] || $fields['systemFolderId'] || $fields['unsortedFolders']) {
                    $delFolders = true;
                }
            }
            
            if ($delFolders) {
                planning_AssetResourceFolders::delete(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $clsId, $rec->id));
            }
        }
    }


    /**
     * Изпълнява се преди записа
     * Ако липсва - записваме id-то на връзката към титлата
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if(empty($rec->id) && isset($rec->fromProtocolId)){
            $rec->protocols = keylist::addKey($rec->protocols, $rec->fromProtocolId);
        }
    }


    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if (isset($rec->fromProtocolId)) {
            accda_Da::logWrite('Създаване на ново оборудване', $rec->fromProtocolId);
        }
    }
    
    
    /**
     * Може ли в папката да се добавя оборудване
     *
     * @param int $folderId
     *
     * @return bool
     */
    public static function canFolderHaveAsset($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        $resourceTypes = $Cover->getResourceTypeArray();
        
        return isset($resourceTypes['assets']);
    }
    
    
    /**
     * Изпълнява се след опаковане на съдаржанието от мениджъра
     *
     * @param core_Manager       $mvc
     * @param string|core_ET $res
     * @param string|core_ET $tpl
     * @param stdClass       $data
     *
     * @return bool
     */
    protected static function on_BeforeRenderWrapping(core_Manager $mvc, &$res, &$tpl = null, $data = null)
    {
        $type = Request::get('type');
        
        if (!$type && $id = Request::get('id', 'int')) {
            $rec = $mvc->fetch($id);
            if ($rec->groupId) {
                $gRec = planning_AssetGroups::fetch($rec->groupId);
                $type = $gRec->type;
            }
        }
        
        if ($type == 'nonMaterial') {
            $mvc->currentTab = 'Ресурси->Нематериални';
        }
    }


    /**
     * Кои са използваните в операции ресурси
     *
     * @param mixed $folders - една папка или няколко
     * @return array $options
     */
    public static function getUsedAssetsInTasks($folders = null)
    {
        $options = array();
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#assetId IS NOT NULL");
        $tQuery->show('assetId');
        if(isset($folders)){
            $folderArr = arr::make($folders, true);
            $assetsInFolderId = array();
            foreach ($folderArr as $folderId) {
                $assetsInFolderId += static::getByFolderId($folderId);
            }

            if(countR($assetsInFolderId)){
                $tQuery->in('assetId', array_keys($assetsInFolderId));
            } else {
                $tQuery->where("1=2");
            }
        }
        $assets = arr::extractValuesFromArray($tQuery->fetchAll(), 'assetId');
        foreach ($assets as $assetId){
            $options[$assetId] = planning_AssetResources::getTitleById($assetId, false);
        }

        return $options;
    }


    /**
     * Връща опциите за избор на операциите от обордуването
     *
     * @param int $assetId                - ид на оборудване
     * @param boolean $onlyIds            - опции или само масив с ид-та
     * @param string $order               - подредба
     * @param string $useAlternativeTitle - дали да се показва алтернативното заглавие
     * @return array $res                 - желания масив
     */
    public static function getAssetTaskOptions($assetId, $onlyIds = false, $order = 'ASC', $useAlternativeTitle = false)
    {
        $res = array();
        $tQuery = planning_Tasks::getQuery();
        $tQuery->EXT('jobProductId', 'planning_Jobs', 'externalName=productId,remoteKey=containerId,externalFieldName=originId');
        $tQuery->XPR('orderByAssetIdCalc', 'double', "COALESCE(#orderByAssetId, 9999)");
        $tQuery->where("#state IN ('active', 'wakeup', 'pending', 'stopped') AND #assetId = {$assetId}");
        $tQuery->orderBy('orderByAssetIdCalc,id', $order);
        $taskRecs = $tQuery->fetchAll();

        if($onlyIds){
            $res = $taskRecs;
        } else {
            foreach ($taskRecs as $tRec){
                $res[$tRec->id] = $useAlternativeTitle ? cls::get('planning_Tasks')->getAlternativeTitle($tRec) : planning_Tasks::getTitleById($tRec->id, false);
            }
        }

        return $res;
    }


    /**
     * Какъв е работния график на центъра на оборудването
     * Ако има индивидуален - него, ако няма този на първия център
     * който е, ако и такъв няма е дефолтния
     *
     * @param int|stdClass $id - ид или запис
     * @return int|null        - ид на графика
     */
    public static function getScheduleId($id)
    {
        // Ако центъра има избран график - той е
        $rec = static::fetchRec($id);
        if(isset($rec->scheduleId)) return $rec->scheduleId;

        // Ако няма се връща първия намерен график от центровете на дейност, където е споделен
        $centerSchedules = array();
        $classId = static::getClassId();
        $centerClassId = planning_Centers::getClassId();
        $fQuery = planning_AssetResourceFolders::getQuery();
        $fQuery->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');
        $fQuery->EXT('coverId', 'doc_Folders', 'externalKey=folderId');
        $fQuery->where("#classId = {$classId} AND #objectId = {$rec->id} AND #coverClass = {$centerClassId}");
        $fQuery->show('folderId,coverId');
        while($fRec = $fQuery->fetch()){
            if($centerScheduleId = planning_Centers::fetchField($fRec->coverId, 'scheduleId')){
                $centerSchedules[$centerScheduleId] = $centerScheduleId;
            }
        }

        $scheduleCenterId = key($centerSchedules);

        return $scheduleCenterId ?? hr_Schedules::getDefaultScheduleId();
    }


    /**
     * Обект за работното време на обордуването
     *
     * @param mixed $id                 - ид или запис
     * @param datetime|null $from       - от кога, null за СЕГА
     * @param datetime|null $to         - до кога, null за Сега + уеб константата
     * @param int|null $scheduleId      - графика на машината по референция
     * @return core_Intervals|null $int -
     */
    public static function getWorkingInterval($id, $from = null, $to = null, &$scheduleId = null)
    {
        $scheduleId = static::getScheduleId($id);
        if(!isset($scheduleId)) return null;

        $from = $from ?? dt::now();
        $to = $to ?? dt::addSecs(planning_Setup::get('ASSET_HORIZON'), $from);
        $int = hr_Schedules::getWorkingIntervals($scheduleId, $from, $to);

        return $int;
    }


    /**
     * Подготовка за рендиране на единичния изглед
     *
     * @param core_Master $mvc
     * @param object      $res
     * @param object      $data
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $data->paramData = cat_products_Params::prepareClassObjectParams($mvc, $data->rec);
    }


    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (isset($data->paramData)) {
            $paramTpl = cat_products_Params::renderParams($data->paramData);
            $tpl->append($paramTpl, 'PARAMS');
        }
    }


    /**
     * Рекалкулира времената на ПО към оборудванията
     */
    public function cron_RecalcTaskTimes()
    {
        // Ако процеса е заключен да не се изпълнява отново
         if (!core_Locks::obtain('CALC_TASK_TIMES', 120, 3, 1)) {
            //$this->logNotice('Преизчисляването на времената е заключено от друг процес');
           // return;
        }

        $now = dt::now();
        // Извличане на всички ПО годни за планиране
        core_Debug::startTimer('SCHEDULE_PREPARE');
        $tasks = planning_TaskConstraints::getDefaultArr(null, 'actualStart,timeStart,calcedCurrentDuration,assetId,dueDate,state,modifiedOn');

        // Еднократно извличане на всички ограничения
        $query = planning_TaskConstraints::getQuery();
        $query->where("#type != 'earliest'");
        $constraintsArr = $query->fetchAll();

        // Разделяне на ограниченията на ПО-та
        $previousTasks = array();
        foreach ($constraintsArr as $cRec){
            if($cRec->type == 'prevId') {
                if(!empty($cRec->previousTaskId)){
                    $previousTasks[$cRec->taskId][$cRec->previousTaskId] = (object)array('previousTaskId' => $cRec->previousTaskId, 'waitingTime' => $cRec->waitingTime);
                }
            }
        }

        core_Debug::stopTimer('SCHEDULE_PREPARE');
        core_Debug::log("END SCHEDULE_PREPARE " . round(core_Debug::$timers["SCHEDULE_PREPARE"]->workingTime, 6));

        $gap = planning_Setup::get('MIN_TIME_FOR_GAP');
        $scheduledData = planning_TaskConstraints::calcScheduledTimes($tasks, $previousTasks, $now);
        $Tasks = cls::get('planning_Tasks');
        foreach ($scheduledData->tasks as $assetId => &$plannedTasks){
            $assetUrl = planning_AssetResources::getSingleUrlArray($assetId);
            $assetLink = ht::createLink($scheduledData->assets[$assetId]->code, $assetUrl, false, 'target=_blank');
            $scheduledData->debug .= "<li>Подреждане на операциите на: {$assetLink} [{$scheduledData->assets[$assetId]->scheduleName}]";

            usort($plannedTasks, function($a, $b) {
                $startA = strtotime($a->expectedTimeStart);
                $startB = strtotime($b->expectedTimeStart);
                if ($startA == $startB) {
                    $endA = strtotime($a->expectedTimeEnd);
                    $endB = strtotime($b->expectedTimeEnd);
                    return ($endA < $endB) ? -1 : (($endA > $endB) ? 1 : 0);
                }

                return ($startA < $startB) ? -1 : 1;
            });

            $order = 1;
            $prevEnd = $now;
            $Interval = $scheduledData->intervals[$assetId];

            foreach ($plannedTasks as $a) {
                $a->planningError = 'no';
                $a->gapData = null;
                if($a->expectedTimeStart == planning_TaskConstraints::NOT_FOUND_DATE){
                    $a->planningError = 'outsideSchedule';
                    $a->expectedTimeStart = null;
                    $a->expectedTimeEnd = null;
                } elseif($a->expectedTimeStart == planning_TaskConstraints::NOT_PLANNABLE) {
                    $a->planningError = 'error';
                    $a->expectedTimeStart = null;
                    $a->expectedTimeEnd = null;
                }

                // Ако има планирано начало и разликата му с края на предишната е над зададения
                if(isset($a->expectedTimeStart)){
                    $diff = max(dt::secsBetween($a->expectedTimeStart, $prevEnd), 0);

                    // Ако е над зададения интервал ще се проверява дали е дупка или е престой
                    if($diff > $gap){
                        $scheduledData->debug .= "<li>&nbsp;&nbsp;<b>Престой</b> #Opr{$a->id} - PREV {$prevEnd} -> {$a->expectedTimeStart}";

                        $start = strtotime($prevEnd);
                        $end = strtotime($a->expectedTimeStart);

                        // Разбива се интервала на толкова периоди, колкото е очакваното
                        $lastType = null;
                        $count = 0;
                        $typeIndex = array('idle' => 0, 'break' => 0);
                        $intervals = array();

                        // За всеки се взима средната дата между тях и се изчислява дали е дупка/престой
                        while ($start < $end) {
                            $next = min($start + $gap, $end);
                            $middleDateTimestamp = dt::getMiddleDate($start, $next, false);

                            $pointInv = $Interval->getByPoint($middleDateTimestamp);
                            $currentType = is_array($pointInv) ? 'idle' : 'break';
                            $scheduledData->debug .= "<li>&nbsp;&nbsp;&nbsp;&nbsp;<b>Средна дата</b> " . date('Y-m-d H:i:s', $middleDateTimestamp) . " е {$currentType}";

                            if ($lastType === null) {
                                // Първи елемент в цикъла - задаваме стойности
                                $lastType = $currentType;
                                $count = 1;
                            } elseif ($currentType === $lastType) {
                                // Продължава същият тип интервал -> увеличаваме броя
                                $count++;
                            } else {
                                // Промяна на типа -> записваме предишната серия и започваме нова
                                $typeIndex[$lastType]++;
                                $intervals[$lastType . $typeIndex[$lastType]] = array(
                                    'count' => $count,
                                    'type' => $lastType
                                );

                                // Започваме новата серия
                                $count = 1;
                                $lastType = $currentType;
                            }

                            $start = $next;
                        }

                        if ($count > 0) {
                            $typeIndex[$lastType]++;
                            $intervals[$lastType . $typeIndex[$lastType]] = array(
                                'count' => $count,
                                'type' => $lastType
                            );
                        }

                        // Накрая ще се запише последователноста на всички дупки/престои
                        $a->gapData = $intervals;
                    }
                }

                $a->orderByAssetId = $order;
                $order++;
                $prevEnd = $a->expectedTimeEnd;
            }

            $Tasks->saveArray($plannedTasks, 'id,expectedTimeStart,expectedTimeEnd,orderByAssetId,planningError,gapData');

            $rec = $this->fetchRec($assetId);
            $rec->lastRecalcTimes = dt::now();
            $this->save_($rec, 'lastRecalcTimes');
        }

        core_Locks::release('CALC_TASK_TIMES');

        if(Mode::is('debugOrder')) return $scheduledData;

        // Преизчисляване на очаквания падеж на заданията
        planning_Jobs::recalcExpectedDueDates();
    }


    /**
     * Връща краткото име на оборудването
     *
     * @param int $id               - ид на оборудване
     * @param bool $link            - дали да е линк
     * @return core_ET|string $name - името или линка
     */
    public static function getShortName($id, $link = true)
    {
        $title = static::getTitleById($id);
        $name = "<span title='{$title}'>[" . static::getVerbal($id, 'code') . "]</span>";
        if($link){
            $assetSingleUrl = planning_AssetResources::getSingleUrlArray($id);
            if(countR($assetSingleUrl)){
                $name = ht::createLink($name, $assetSingleUrl);
            }
        }

        return $name;
    }


    /**
     * Може ли материалното оборудването да се добави в посочената папка
     *
     * @param int $assetId
     * @param int $folderId
     * @return bool
     */
    public static function canAssetBeAddedToFolder($assetId, $folderId)
    {
        $resourceType = planning_AssetGroups::fetchField(planning_AssetResources::fetchField($assetId, 'groupId'), 'type');
        if ($resourceType == 'material') {
            $assetClassId = planning_AssetResources::getClassId();
            $query = planning_AssetResourceFolders::getQuery();
            $query->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');
            $query->where("#classId = {$assetClassId} AND #objectId = {$assetId} AND #folderId != '{$folderId}' AND #coverClass = " . planning_Centers::getClassId());

            if ($query->count() > 0) return false;
        }

        return true;
    }
}
