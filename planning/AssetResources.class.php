<?php


/**
 * Мениджър на Оборудвания
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
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
     * Кой има право да форсира преизчисляването на времето?
     */
    public $canRecalctime = 'ceo, task, debug';


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
    public $details = 'assetSupport=support_TaskType,Tasks=planning_Tasks,planning_AssetResourcesNorms,planning_AssetResourceFolders,planning_AssetSparePartsDetail';
    
    
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

            if(isset($rec->scheduleId)){
                $row->scheduleId = hr_Schedules::getHyperlink($rec->scheduleId, true);
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
    function on_CalcShortName($mvc, &$rec)
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
        $resourceSuggestionsArr = doc_FolderResources::getFolderSuggestions('assets');
        $data->listFilter->setOptions('folderId', array('' => '') + $resourceSuggestionsArr);
        
        $data->listFilter->showFields = 'search,groupId,folderId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if ($data->listFilter->rec->groupId) {
            $data->query->where("#groupId = {$data->listFilter->rec->groupId}");
        }
        
        $data->listFilter->FLD('type', 'enum(material=Материален, nonMaterial=Нематериален)', 'caption=Тип, input=hidden, silent');
        $data->listFilter->input('type, folderId', true);
        
        if ($data->listFilter->rec->type) {
            $data->query->EXT('type', 'planning_AssetGroups', 'externalName=type,externalKey=groupId');
            $data->query->where(array("#type = '[#1#]'", $data->listFilter->rec->type));
        }
        
        if ($data->listFilter->rec->folderId) {
            $data->query->likeKeylist('assetFolders', $data->listFilter->rec->folderId);
            $data->query->orLikeKeylist('systemFolderId', $data->listFilter->rec->folderId);
            $data->query->orLikeKeylist('unsortedFolders', $data->listFilter->rec->folderId);
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
     * @param int|null $folderId
     * @return array $options
     */
    public static function getUsedAssetsInTasks($folderId = null)
    {
        $options = array();
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#assetId IS NOT NULL");
        $tQuery->show('assetId');
        if(isset($folderId)){
            $assetsInFolderId = array_keys(static::getByFolderId($folderId));
            $tQuery->in('assetId', $assetsInFolderId);
        }
        $assets = arr::extractValuesFromArray($tQuery->fetchAll(), 'assetId');
        foreach ($assets as $assetId){
            $options[$assetId] = planning_AssetResources::getTitleById($assetId, false);
        }

        return $options;
    }


    /**
     * Преподреждане на операциите към оборудването
     *
     * @param int $assetId
     * @return void
     */
    public static function reOrderTasks($assetId, $orderedTaskRecs = null, $alwaysReorder = false, $manualTimes = array())
    {
        $assetTasks = is_array($orderedTaskRecs) ? $orderedTaskRecs : static::getAssetTaskOptions($assetId, true);

        $i = 1;
        $tasksToUpdate = array();
        $Tasks = cls::get('planning_Tasks');
        foreach ($assetTasks as &$t) {
            if ($t->orderByAssetId != $i || $alwaysReorder) {
                $t->orderByAssetId = $i;
                $tasksToUpdate[$t->id] = $t;
            }

            if(is_array($manualTimes['expectedTimeStart']) && array_key_exists($t->id, $manualTimes['expectedTimeStart'])){
                $t->timeStart = $manualTimes['expectedTimeStart'][$t->id];
                $Tasks->logWrite('Промяна на целево начало', $t->id);
            }

            if(is_array($manualTimes['expectedTimeEnd']) && array_key_exists($t->id, $manualTimes['expectedTimeEnd'])){
                $t->timeEnd = $manualTimes['expectedTimeEnd'][$t->id];
                $Tasks->logWrite('Промяна на целеви край', $t->id);
            }
            $i++;
        }

        if(countR($tasksToUpdate)){
            cls::get('planning_Tasks')->saveArray($tasksToUpdate, 'id,orderByAssetId,timeStart,timeEnd');
        }

        $rec = static::fetchRec($assetId);
        $rec->lastReorderedTasks = dt::now();
        cls::get(get_called_class())->save_($rec, 'id,lastReorderedTasks');
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
        $tQuery->where("(#orderByAssetId IS NOT NULL OR (#orderByAssetId IS NULL AND (#state IN ('active', 'wakeup', 'pending', 'stopped')))) AND #assetId = {$assetId}");
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
     * Какъв е работния график на центъра
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

        return key($centerSchedules);
    }


    /**
     * Обект за работното време на обордуването
     *
     * @param mixed $id                 - ид или запис
     * @param datetime|null $from       - от кога, null за СЕГА
     * @param datetime|null $to         - до кога, null за Сега + уеб константата
     * @return core_Intervals|null $int -
     */
    public static function getWorkingInterval($id, $from = null, $to = null)
    {
        $scheduleId = static::getScheduleId($id);
        if(!isset($scheduleId)) return null;

        $from = isset($from) ? $from : dt::now();
        $to = isset($to) ? $to : dt::addSecs(planning_Setup::get('ASSET_HORIZON'), $from);

        $int = hr_Schedules::getWorkingIntervals($scheduleId, $from, $to, false, false);

        return $int;
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('recalctime', (object)array('id' => $data->rec->id))) {
            $data->toolbar->addBtn('Преизчисляване', array($mvc, 'recalcTimes', $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Преизчисляване на времената на операциите към оборудването');
        }

        if ($mvc->haveRightFor('recalctime', (object)array('id' => $data->rec->id, 'isDebug' => 1))) {
            $data->toolbar->addBtn('Подреждане (Дебъг)', array($mvc, 'recalcTimes', $data->rec->id, 'isDebug' => 1, 'ret_url' => true), 'ef_icon=img/16/bug.png,title=Дебъг преизчисляване на времената');
        }
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
     * Рекалкулиране на началото и края на ПО-та закачени към оборудването
     *
     * @param mixed $id           - ид или запис
     * @param datetime|null $from - от кога, null за сега
     * @param datetime|null $to   - до кога, null за до края на дефолтния период
     * @return array|void
     */
    public static function recalcTaskTimes($id, $from = null, $to = null)
    {
        // Какъв е работния график на оборудването
        $Interval = static::getWorkingInterval($id, $from, $to);
        if(!$Interval) return;

        // Кои операции са закачени за оборудването
        $tasks = static::getAssetTaskOptions($id, true);

        if(!countR($tasks)) return;

        // Кеширане на продуктовите опаковки, за артикулите в задачите
        $pPacks = array();
        $packQuery = cat_products_Packagings::getQuery();
        $in = arr::extractValuesFromArray($tasks, 'productId');
        $in += arr::extractValuesFromArray($tasks, 'jobProductId');
        $packQuery->in('productId', $in);
        $packQuery->show('quantity,productId,packagingId');
        while($pRec = $packQuery->fetch()){
            $pPacks["{$pRec->productId}|{$pRec->packagingId}"] = $pRec->quantity;
        }

        // Кои са действията с норми към машината
        $productsClassId = cat_Products::getClassId();
        $assetNorms = $normsByTask = $notIn = array();
        $normOptions = planning_AssetResourcesNorms::getNormOptions($id, $notIn, true);
        $rec = static::fetchRec($id);

        // Ако има операции с норми към оборудването
        if(countR($normOptions)){
            // Извличане от опциите само имената - без групите
            $taskIds = arr::extractValuesFromArray($tasks, 'id');

            $normsByTask = $stepCache = array();
            foreach ($tasks as $taskRec){
                // Кеш на продуктовата информация в етапите
                if(!array_key_exists($taskRec->productId, $stepCache)){
                    $stepCache[$taskRec->productId] = null;
                    if($Driver = cat_Products::getDriver($taskRec->productId)){
                        $stepCache[$taskRec->productId] = $Driver->getProductionData($taskRec->productId);
                    }
                }

                // За всяка операция се подготвят планиращите действия с очаквано к-во 0
                if(is_array($stepCache[$taskRec->productId]['actions'])){
                    foreach ($stepCache[$taskRec->productId]['actions'] as $actionProductId){
                        $normsByTask[$taskRec->id][$actionProductId] = 0;
                    }
                }
            }

            // Изчисляват се времената на планираните операции за задачата
            $pQuery = planning_ProductionTaskProducts::getQuery();
            $pQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey=productId");
            $pQuery->where("#type = 'input' AND #canStore != 'yes'");
            $pQuery->in('taskId', $taskIds);
            $pQuery->in('productId', $assetNorms);
            $pQuery->show('productId,taskId,plannedQuantity,indTime');
            while($pRec = $pQuery->fetch()){

                // Ако планираното влагане е от планиращите операции на артикула
                if(isset($normsByTask[$pRec->taskId][$pRec->productId])){
                    $normsByTask[$pRec->taskId][$pRec->productId] = planning_type_ProductionRate::getInSecsByQuantity($pRec->indTime, $pRec->plannedQuantity);
                }
            }

            // Изчисляват се реално изпълнените операции
            $detailsAssetNorms = array();
            $dQuery = planning_ProductionTaskDetails::getQuery();
            $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey=productId");
            $dQuery->where("#type = 'input' AND #state != 'rejected' AND #canStore != 'yes'");
            $dQuery->in('taskId', $taskIds);
            $dQuery->in('productId', $assetNorms);
            while($dRec = $dQuery->fetch()){

                // Ако изпълненото влагане е от планиращите операции на артикула
                if(isset($normsByTask[$dRec->taskId][$dRec->productId])){

                    // Сумира се реално изпълненото време
                    $calced = cls::get('planning_ProductionTaskDetails')->calcNormByRec($dRec, $tasks[$dRec->taskId]);
                    $detailsAssetNorms[$dRec->taskId][$dRec->productId] += $calced;
                }
            }
        }

        // Измежду планираните и реално изпълнените операции се взима това с по-голямата норма
        foreach ($normsByTask as $tId => $actions){
            foreach ($actions as $actionId => $value){
                if(isset($detailsAssetNorms[$tId][$actionId])){
                    $normsByTask[$tId][$actionId] = max($value, $detailsAssetNorms[$tId][$actionId]);
                }
            }
        }

        $interruptionArr = planning_Steps::getInterruptionArr($tasks);
        $minDuration = planning_Setup::get('MIN_TASK_DURATION');

        // Разделяне на ПО на такива с ръчно зададени времена и без
        $tasksWithManualBegin = $tasksWithoutManualBegin = array();
        array_filter($tasks, function ($t) use (&$tasksWithManualBegin, &$tasksWithoutManualBegin, $minDuration, $normsByTask, $rec) {

            // Ако има ръчна продължителност взема се тя
            $duration = $t->timeDuration;
            if(empty($duration)){

                // Ако няма изчислявам от нормата за планираното количество
                if($t->indPackagingId == $t->measureId){
                    $calcedPlannedQuantity = $t->plannedQuantity;
                } else {
                    $indProductIdKey = ($t->isFinal == 'yes') ? $t->jobProductId : $t->productId;

                    // Ако мярката за нормиране е същата като тази от етикета - взема се неговото к-во
                    if($t->indPackagingId == $t->labelPackagingId && $t->labelQuantityInPack){
                        $indQuantityInPack = $t->labelQuantityInPack;
                    } else {
                        $indQuantityInPack = isset($pPacks["{$indProductIdKey}|{$t->indPackagingId}"]) ? $pPacks["{$indProductIdKey}|{$t->indPackagingId}"] : 1;
                    }

                    $quantityInPack = isset($pPacks["{$indProductIdKey}|{$t->measureId}"]) ? $pPacks["{$indProductIdKey}|{$t->measureId}"] : 1;
                    $calcedPlannedQuantity = round(($t->plannedQuantity * $quantityInPack) / $indQuantityInPack);
                }

                $indTime = planning_type_ProductionRate::getInSecsByQuantity($t->indTime, $calcedPlannedQuantity);
                $simultaneity = $t->simultaneity ?? $rec->simultaneity;
                $duration = round($indTime / $simultaneity);
            }

            // От продължителността, се приспада произведеното досега
            $nettDuration = $duration;
            $duration = round((1 - $t->progress) * $duration);

            // Ако мин прогреса е под 100%, то се използва мин. продължителността, иначе за мин. прод. се използва 0
            $minDuration = ($t->progress >= 1) ? 1 : $minDuration;
            $duration = max($duration, $minDuration);

            // Към така изчислената продължителност се добавя тази от действията към машината
            if(array_key_exists($t->id, $normsByTask)){
                $duration += array_sum($normsByTask[$t->id]);
                $nettDuration += array_sum($normsByTask[$t->id]);
                $t->actionNorms = $normsByTask[$t->id];
            }
            $t->calcedDuration = $nettDuration;
            $t->calcedCurrentDuration = $duration;
            if(!empty($t->timeStart) || !empty($t->timeEnd)) {
                $tasksWithManualBegin[$t->id] = $t;
            } else {
                $tasksWithoutManualBegin[$t->id] = $t;
            }
        });

        // Наслагване върху графика, като с приоритет са тези с ръчно зададени времена
        // Първо те се слагат в графика, а останалите се наместват, където има място
        $updateRecs = array();
        foreach (array('tasksWithManualBegin', 'tasksWithoutManualBegin') as $varName){
            $arr = ${"{$varName}"};
            foreach($arr as $taskRec){
                $updateRecs[$taskRec->id] = static::calcTaskPlannedTime($taskRec, $Interval, $interruptionArr);
            }
        }

        // Сортиране по планирано начало, ако няма ще е най в края
        usort($updateRecs, function ($a, $b) {
            $expectedTimeStartLeft = empty($a->expectedTimeStart) ? '9999-99-99 23:59:59' : $a->expectedTimeStart;
            $expectedTimeStartRight = empty($b->expectedTimeStart) ? '9999-99-99 23:59:59' : $b->expectedTimeStart;
            if($expectedTimeStartLeft == $expectedTimeStartRight) return ($a->orderByAssetId < $b->orderByAssetId) ? -1 : 1;

            return strcasecmp($expectedTimeStartLeft, $expectedTimeStartRight);
        });

        // Преподреждане по изчислените времена
        $newOrder = 1;
        $prevId = null;
        foreach ($updateRecs as $index => $uRec){
            $uRec->freeTimeAfter = 'no';

            // Между началото на тази и предишната има интервал над определеното дига се флаг, че има дупка
            if(isset($prevId)){
                $betweenInSec = dt::secsBetween($uRec->expectedTimeStart, $updateRecs[$prevId]->expectedTimeEnd);
                if($betweenInSec > 15 * 60){
                    $updateRecs[$prevId]->freeTimeAfter = 'yes';
                }
            }
            $uRec->orderByAssetId = $newOrder;
            $newOrder++;
            $prevId = $index;
        }

        // Запис на преизчислените начала и краища на операциите
        $Tasks = cls::get('planning_Tasks');
        $Tasks->saveArray($updateRecs, 'id,freeTimeAfter,expectedTimeStart,expectedTimeEnd,orderByAssetId,calcedCurrentDuration,calcedDuration');

        // Записване на времето за обновяване
        $me = cls::get(get_called_class());
        $rec = $me->fetchRec($id);
        $rec->lastRecalcTimes = dt::now();
        $me->save_($rec, 'lastRecalcTimes');

        return $updateRecs;
    }


    /**
     * Помощна ф-я за изчисляване на планираното време на една операция
     *
     * @param stdClass $taskRec
     * @param core_Intervals $Interval
     * @param array $interruptionArr
     * @return object $res
     */
    private static function calcTaskPlannedTime($taskRec, core_Intervals &$Interval, $interruptionArr)
    {
        $res = (object)array('id' => $taskRec->id,
                             'expectedTimeStart' => null,
                             'expectedTimeEnd' => null, 'progress' => $taskRec->progress, 'actionNorms' => $taskRec->actionNorms, 'calcedDuration' => $taskRec->calcedDuration, 'calcedCurrentDuration' => $taskRec->calcedCurrentDuration,
                             'indTime' => $taskRec->indTime,
                             'indPackagingId' => $taskRec->indPackagingId,
                             'plannedQuantity' => $taskRec->plannedQuantity,
                             'duration' => $taskRec->timeDuration,
                             'timeStart' => $taskRec->timeStart, 'orderByAssetId' => $taskRec->orderByAssetId);

        // Колко ще е отместването при прекъсване
        $interruptOffset = array_key_exists($taskRec->productId, $interruptionArr) ? $interruptionArr[$taskRec->productId] : null;

        // Прави се опит за добавяне на операцията в графика
        $now = dt::now();
        $begin = null;
        if(!empty($taskRec->timeStart)){
            $begin = $taskRec->timeStart;
        } elseif(!empty($taskRec->timeEnd)){
            $begin = dt::addSecs(-1 * $taskRec->calcedCurrentDuration, $taskRec->timeEnd);
        }

        $begin = max($begin, $now);
        $begin = strtotime($begin);
        $timeArr = $Interval->consume($taskRec->calcedCurrentDuration, $begin, null, $interruptOffset);

        // Ако е успешно записват се началото и края
        if(is_array($timeArr)){
            $res->expectedTimeStart = date('Y-m-d H:i:00', $timeArr[0]);
            $res->expectedTimeEnd = date('Y-m-d H:i:00', $timeArr[1]);
        }

        return $res;
    }


    /**
     * Екшън за рекалкулиране на времето на едно оборудване
     */
    public function act_recalcTimes()
    {
        $this->requireRightFor('recalctime');
        $id = Request::get('id', 'int');
        $isDebug = Request::get('isDebug', 'int');
        $this->requireRightFor('recalctime', (object)array('id' => $id, 'isDebug' => $isDebug));
        $updateRecs = static::recalcTaskTimes($id);
        if($isDebug){
            bp($updateRecs);
        }

        followRetUrl(null, '|Времената са преизчислени|*!');
    }


    /**
     * Рекалкулира времената на ПО към оборудванията
     */
    public function cron_RecalcTaskTimes()
    {
        // Всички оборудвания, които са закачени към ПО
        $tQuery = planning_Tasks::getQuery();
        $tQuery->in('state', array('pending', 'stopped', 'active', 'wakeup'));
        $tQuery->where('#assetId IS NOT NULL');
        $assetArr = array();
        while($tRec = $tQuery->fetch()){
            $key = "{$tRec->plannedQuantity}|{$tRec->state}|{$tRec->indTime}|{$tRec->indPackagingId}|{$tRec->timeStart}|{$tRec->timeEnd}|{$tRec->timeDuration}|{$tRec->simultaneity}";
            $assetArr[$tRec->assetId][$tRec->orderByAssetId] = array('key' => $key, 'id' => $tRec->id);
        }

        // Ако няма нищо не прави
        if(!countR($assetArr)) return;

        $from = dt::now();
        $to = dt::addSecs(planning_Setup::get('ASSET_HORIZON'), $from);

        // За всяко оборудване
        foreach ($assetArr as $assetId => $assetData){

            // Сортиране по подредба и създаване на хеш за проверка
            ksort($assetData);
            $checkArr = array();
            array_walk($assetData, function($a) use (&$checkArr) {$checkArr[$a['id']] = $a['key'];});
            $newMd5 = md5(json_encode($checkArr));

            // Какъв е записания кеш към момента
            $oldMd5 = core_Permanent::get("assetTaskOrder|{$assetId}");
            if($oldMd5 != $newMd5){

                // Ако има промяна рекалкулират се времената на оборудването
                static::recalcTaskTimes($assetId, $from, $to);
                core_Permanent::set("assetTaskOrder|{$assetId}", $newMd5, 24*60*60);
            }
        }

        // Проверява зависимостите между операциите
        planning_StepConditions::checkTaskConditions();

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
