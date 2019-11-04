<?php


/**
 * Мениджър на Оборудвания
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
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
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_Search, plg_Sorting, plg_Modified, plg_Clone, plg_SaveAndNew';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';
    
    
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
    public $listFields = 'name=Оборудване,code,groupId,assetFolderId=Център на дейност,systemFolderId=Система,createdOn,createdBy,state';
    
    
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
    public $hideListFieldsIfEmpty = 'protocolId';
    
    
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
    public $searchFields = 'name, code, groupId, protocolId';
    
    
    /**
     * Детайли
     */
    public $details = 'planning_AssetResourceFolders,planning_AssetResourcesNorms';
    
    
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
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory, remember');
        $this->FLD('groupId', 'key(mvc=planning_AssetGroups,select=name,allowEmpty)', 'caption=Вид,mandatory,silent, remember');
        $this->FLD('code', 'varchar(16)', 'caption=Код,mandatory, remember');
        $this->FLD('protocolId', 'key(mvc=accda_Da,select=id)', 'caption=Протокол за пускане в експлоатация,silent,input=hidden, remember');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none, remember');
        
        $this->FLD('image', 'fileman_FileType(bucket=planningImages)', 'caption=Допълнително->Снимка');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание');
        
        $powerUserId = core_Roles::fetchByName('powerUser');
        
        $this->FLD('assetFolderId', 'keylist(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Използване->Център на дейност, remember');
        $this->FLD('assetUsers', "keylist(mvc=core_Users, select=nick, where=#state !\\= \\'rejected\\' AND #roles LIKE '%|{$powerUserId}|%')", 'caption=Използване->Отговорници, remember');
        $this->FLD('simultaneity', 'int', 'caption=Използване->Едновременност,notNull,value=1, oldFieldName=quantity, remember');
        
        $this->FLD('systemFolderId', 'keylist(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Поддръжка->Система, remember');
        $this->FLD('systemUsers', "keylist(mvc=core_Users, select=nick, where=#state !\\= \\'rejected\\' AND #roles LIKE '%|{$powerUserId}|%')", 'caption=Поддръжка->Отговорници, remember');
        
        $this->FLD('indicators', 'keylist(mvc=sens2_Indicators,select=title, allowEmpty)', 'caption=Сензори, remember');
        
        $this->FLD('cameras', 'keylist(mvc=cams_Cameras,select=title, allowEmpty)', 'caption=Камери, remember');
        
        $this->FLD('vehicle', 'key(mvc=tracking_Vehicles,select=number, allowEmpty)', 'caption=Тракер, remember');
        
        // TODO - ще се премахне след като минат миграциите
        $this->FLD('folders', 'keylist(mvc=doc_Folders,select=title)', 'caption=Папки,mandatory,oldFieldName=departments, input=none, column=none, single=none');
        
        $this->FNC('codeAndName', 'varchar');
        
        $this->setDbUnique('code');
        $this->setDbUnique('protocolId');
    }
    
    
    /**
     * Изчисляване на името и кода
     */
    protected static function on_CalcCodeAndName($mvc, &$rec)
    {
        $rec->codeAndName = $rec->code . ' - ' . $rec->name;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        // От кое ДМА е оборудването
        if (isset($rec->protocolId)) {
            $daTitle = accda_Da::fetchField($rec->protocolId, 'title');
            $form->setDefault('name', $daTitle);
            $form->info = tr('От') . ' ' . accda_Da::getHyperLink($rec->protocolId, true);
        }
        
        $defOptArr = array();
        if ($rec->id) {
            $fQuery = planning_AssetResourceFolders::getQuery();
            $fQuery->where(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $mvc->getClassId(), $rec->id));
            while ($fRec = $fQuery->fetch()) {
                if (!$fRec->folderId) {
                    continue ;
                }
                $cover = doc_Folders::getCover($fRec->folderId);
                
                $systemFolderName = 'assetFolderId';
                
                if ($cover->className == 'support_Systems') {
                    $systemFolderName = 'systemFolderId';
                }
                
                $defOptArr[$systemFolderName]['folders'][$fRec->folderId] = $fRec->folderId;
                if ($fRec->users) {
                    $defOptArr[$systemFolderName]['users'] = type_Keylist::merge($defOptArr[$systemFolderName]['users'], $fRec->users);
                }
            }
        }
        
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
        $resourceSuggestionsArr = doc_Folders::getSelectArr(array('titleFld' => 'title', 'restrictViewAccess' => 'yes', 'coverClasses' => 'planning_Centers'));
        if (empty($resourceSuggestionsArr)) {
            $form->setField('assetFolderId', 'input=hidden');
            $form->setField('assetUsers', 'input=hidden');
        } else {
            $form->setSuggestions('assetFolderId', array('' => '') + $resourceSuggestionsArr);
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
            $form->setDefault('assetFolderId', keylist::addKey('', $defaultFolderId));
        } else {
            $form->rec->assetFolderId = type_Keylist::fromArray($defOptArr['assetFolderId']['folders']);
            $form->rec->assetUsers = $defOptArr['assetFolderId']['users'];
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
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if (!$form->rec->assetFolderId && $form->rec->assetUsers) {
                $form->setError('assetFolderId', 'Не е избрана папка');
            }
            
            if (!$form->rec->systemFolderId && $form->rec->systemUsers) {
                $form->setError('systemFolderId', 'Не е избрана папка');
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     * planning_Centers::getUndefinedFolderId()
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $limitForDocs = 5;
        
        $row->groupId = planning_AssetGroups::getHyperlink($rec->groupId, true);
        $row->created = "{$row->createdOn} " . tr('от') . " {$row->createdBy}";
        
        if (isset($rec->protocolId)) {
            $row->protocolId = accda_Da::getHyperlink($rec->protocolId, true);
        }
        
        if (isset($fields['-single'])) {
            $row->SingleIcon = ht::createElement('img', array('src' => sbf(str_replace('/16/', '/32/', $mvc->singleIcon), ''), 'alt' => ''));
            
            if ($rec->image) {
                $row->image = fancybox_Fancybox::getImage($rec->image, array(120, 120), array(1200, 1200));
            }
            
            // Намираме всички папки
            $fQuery = planning_AssetResourceFolders::getQuery();
            $fQuery->where(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $mvc->getClassId(), $rec->id));
            $fArr = array();
            while ($fRec = $fQuery->fetch()) {
                $fArr[$fRec->folderId] = array('folderId' => $fRec->folderId, 'users' => $fRec->users, 'rec' => $fRec);
            }
            
            $row->systemFolderId = '';
            $row->assetFolderId = '';
            foreach ($fArr as $f) {
                if (!$f['folderId']) {
                    continue;
                }
                
                $cover = doc_Folders::getCover($f['folderId']);
                if ($cover->className == 'support_Systems') {
                    $row->systemFolderId .= $row->systemFolderId ? '<br>' : '';
                    $row->systemFolderId .= doc_Folders::getLink($f['folderId']);
                    
                    // Показваме отговорниците
                    if ($f['users']) {
                        $row->systemFolderId .= ' (';
                        $isFirst = true;
                        foreach (type_Keylist::toArray($f['users']) as $uId) {
                            $row->systemFolderId .= $isFirst ? '': ', ';
                            $isFirst = false;
                            $row->systemFolderId .= crm_Profiles::createLink($uId);
                        }
                        $row->systemFolderId .= ')';
                    }
                    
                    $issues = '';
                    if (doc_Folders::haveRightFor('single', $f['folderId'])) {
                        $driverClassField = cls::get('cal_Tasks')->driverClassField;
                        
                        $sQuery = cal_Tasks::getQuery();
                        $sQuery->where(array("#folderId = '[#1#]'", $f['folderId']));
                        $sQuery->where("#state != 'rejected'");
                        $sQuery->where(array("#{$driverClassField} = '[#1#]'", support_TaskType::getClassId()));
                        
                        $sQuery->orderBy('state', 'ASC');
                        $sQuery->orderBy('modifiedOn', 'DESC');
                        
                        $cnt = 0;
                        while ($sRec = $sQuery->fetch()) {
                            if ($sRec->assetResourceId != $rec->id) {
                                continue;
                            }
                            if (++$cnt > $limitForDocs) {
                                break;
                            }
                            
                            $linkTitle = cal_Tasks::getVerbal($sRec->id, 'progress');
                            $linkTitle .= ' ' . cal_Tasks::getVerbal($sRec->id, 'title');
                            
                            // Вземаме линка
                            $link = ht::createLink($linkTitle, cal_Tasks::getSingleUrlArray($sRec->id), null, array('ef_icon' => cal_Tasks::getIcon($sRec->id)));
                            
                            $issues .= "<div class='state-{$sRec->state}'>" . $link . '</div>';
                        }
                    }
                    
                    if ($issues) {
                        $row->systemFolderId .= '<div style="padding-left: 20px;">' . $issues . '</div>';
                    }
                } else {
                    $row->assetFolderId .= $row->assetFolderId ? '<br>' : '';
                    $row->assetFolderId .= doc_Folders::getLink($f['folderId']);
                    
                    // Показваме отговорниците
                    if ($f['users']) {
                        $row->assetFolderId .= ' (';
                        $isFirst = true;
                        foreach (type_Keylist::toArray($f['users']) as $uId) {
                            $row->assetFolderId .= $isFirst ? '': ', ';
                            $isFirst = false;
                            $row->assetFolderId .= crm_Profiles::createLink($uId);
                        }
                        $row->assetFolderId .= ')';
                    }
                    
                    $jobs = '';
                    if (doc_Folders::haveRightFor('single', $f['folderId'])) {
                        $pQuery = planning_Tasks::getQuery();
                        $pQuery->where(array("#folderId = '[#1#]'", $f['folderId']));
                        $pQuery->likeKeylist('fixedAssets', $rec->id);
                        $pQuery->where("#state != 'rejected'");
                        $pQuery->orderBy('state', 'ASC');
                        $pQuery->orderBy('modifiedOn', 'DESC');
                        $pQuery->limit($limitForDocs);
                        
                        while ($pRec = $pQuery->fetch()) {
                            $jobs .= "<div class='state-{$pRec->state}'>" . planning_Tasks::getHyperlink($pRec->id, true) . '</div>';
                        }
                    }
                    
                    if ($jobs) {
                        $row->assetFolderId .= '<div style="padding-left: 20px;">' . $jobs . '</div>';
                    }
                }
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
                        $cRow = cams_Cameras::recToVerbal($cId, 'liveImg');
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
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param planning_AssetResources $mvc
     * @param core_ET                 $tpl
     * @param stdClass                $data
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (!$data->row->jobs && !$data->row->assetFolderId) {
            $tpl->removeBlock('jobs');
        }
        
        if (!$data->row->issues && !$data->row->systemFolderId) {
            $tpl->removeBlock('issues');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            if (isset($rec->protocolId)) {
                $state = accda_Da::fetchField($rec->protocolId, 'state');
                if ($state != 'active') {
                    $requiredRoles = 'no_one';
                } else {
                    if ($mvc->fetch("#protocolId = {$rec->protocolId}")) {
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
            $data->query->likeKeylist('assetFolderId', $data->listFilter->rec->folderId);
            $data->query->orLikeKeylist('systemFolderId', $data->listFilter->rec->folderId);
        }
        
        $data->query->orderBy('modifiedOn', 'DESC');
    }
    
    
    /**
     * Избор на наличното оборудване в подадената папка
     *
     * @param int|null $folderId - ид на папка
     *
     * @return array $option    - налично оборудване
     */
    public static function getByFolderId($folderId = null)
    {
        $options = array();
        
        // Ако папката не поддържа ресурси оборудване да не се връща нищо
        if (isset($folderId)) {
            if (!self::canFolderHaveAsset($folderId)) {
                
                return $options;
            }
        }
        
        $fQuery = planning_AssetResourceFolders::getQuery();
        if (isset($folderId)) {
            $fQuery->where(array("#folderId = '[#1#]'", $folderId));
        }
        $fQuery->where(array("#classId = '[#1#]'", self::getClassId()));
        
        while ($fRec = $fQuery->fetch()) {
            if ($rec = self::fetch($fRec->objectId)) {
                if ($rec->state == 'rejected') {
                    continue;
                }
                $options[$rec->id] = self::getRecTitle($rec, false);
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
        // Подготовка на записите
        $query = self::getQuery();
        $query->where("#groupId = {$data->masterId}");
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
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new core_ET('');
        
        // Рендиране на таблицата с оборудването
        $data->listFields = arr::make('name=Оборудване,simultaneity=Едновременност,createdOn=Създадено->На,createdBy=Създадено->От,state=Състояние');
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tpl->append($table->get($data->rows, $data->listFields));
        
        // Бутон за добавяне на ново оборудване
        if (isset($data->addUrl)) {
            $btn = ht::createBtn('Ново оборудване', $data->addUrl, false, false, "ef_icon={$this->singleIcon},title=Добавяне на ново оборудване към вида");
            $tpl->replace($btn, 'addAssetBtn');
        }
        
        return $tpl;
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
        if (empty($id)) {
            
            return false;
        }
        
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
     * Извлича общата група на оборудванията
     *
     * @param mixed $assets - списък с оборудвания
     *
     * @return int|FALSE $groupId - намерената група или FALSE ако са от различни групи
     */
    public static function getGroupId(&$assets)
    {
        $assets = is_array($assets) ? $assets : keylist::toArray($assets);
        if (!planning_AssetGroups::haveSameGroup($assets)) {
            
            return false;
        }
        $groupId = planning_AssetResources::fetchField(key($assets), 'groupId');
        
        return (!empty($groupId)) ? $groupId : false;
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
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $rArr = array();
        $allFoldersArr = array();
        
        if ($rec->assetFolderId) {
            $rArr[] = array('folderId' => type_Keylist::toArray($rec->assetFolderId), 'users' => $rec->assetUsers);
        }
        
        if ($rec->systemFolderId) {
            $rArr[] = array('folderId' => type_Keylist::toArray($rec->systemFolderId), 'users' => $rec->systemUsers);
        }
        
        $clsId = $mvc->getClassId();
        foreach ($rArr as $r) {
            if (!$r['folderId']) {
                continue;
            }
            
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
                if ($fields['assetFolderId'] || $fields['systemFolderId']) {
                    $delFolders = true;
                }
            }
            
            if ($delFolders) {
                planning_AssetResourceFolders::delete(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $clsId, $rec->id));
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if (isset($rec->protocolId)) {
            accda_Da::logWrite('Създаване на ново оборудване', $rec->protocolId);
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
     * @param core_Mvc       $mvc
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
}
