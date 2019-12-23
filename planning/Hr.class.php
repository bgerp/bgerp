<?php


/**
 * Мениджър за човешките ресурси в производството
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     0.12
 */
class planning_Hr extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'label_SequenceIntf=planning_interface_HrLabelImpl';


    /**
     * Заглавие
     */
    public $title = 'Информация за операторите';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Служебна информация';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'planning_Wrapper,plg_Created,plg_RowTools2,plg_Search,label_plg_Print';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,planningMaster';
    
    
    /**
     * Кой може да създава
     */
    public $canAdd = 'ceo,planningMaster';
    
    
    /**
     * Кой може да листва
     */
    public $canList = 'ceo,planning';
    
    
    /**
     * Кой има достъп до сингъла?
     */
    public $canSingle = 'ceo, planning';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'на';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,personId,createdOn,createdBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'personId, code';
    
    
    /**
     * Детайли
     */
    public $details = 'planning_AssetResourceFolders';
    
    
    /**
     * Поле за единичния изглед
     */
    public $rowToolsSingleField = 'code';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,mandatory,caption=Оператор');
        $this->FLD('code', 'varchar', 'caption=Код');
        $this->FNC('centers', 'keylist(mvc=doc_Folders,select=title)', 'mandatory, input, caption=Центрове на дейност');
        
        // TODO - ще се премахне след като минат миграциите
        $this->FLD('folders', 'keylist(mvc=doc_Folders,select=title)', 'caption=Папки,mandatory,oldFieldName=departments, input=none, column=none, single=none');
        
        $this->setDbIndex('code');
        $this->setDbUnique('personId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if (empty($rec->personId)) {
            $form->setField('personId', 'input');
            $form->setOptions('personId', array('' => '') + crm_Persons::getEmployeesOptions(true, false));
        }
        
        $allowedCenterSuggestions = doc_Folders::getOptionsByCoverInterface('planning_ActivityCenterIntf');
        $form->setSuggestions('centers', $allowedCenterSuggestions);
        
        if(isset($rec->id)){
            
            // Показват се всички центрове за избрани където е включен
            $assetQuery = planning_AssetResourceFolders::getQuery();
            $assetQuery->where("#classId = {$mvc->getClassId()} AND #objectId = {$rec->id}");
            $alreadyIn = arr::extractValuesFromArray($assetQuery->fetchAll(), 'folderId');
            $form->setDefault('centers', $alreadyIn);
        } else {
            $defaultCenterFolderId = keylist::addKey('', planning_Centers::getUndefinedFolderId());
            $form->setDefault('centers', $defaultCenterFolderId);
        }
    }
    
    
    /**
     * Преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (empty($rec->code)) {
            $rec->code = self::getDefaultCode($rec->personId);
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if (isset($rec->personId)) {
            $data->form->title = core_Detail::getEditTitle('crm_Persons', $rec->personId, 'служебната информация', $rec->id, $mvc->formTitlePreposition);
        }
    }
    
    
    /**
     * Дефолтния код за лицето
     *
     * @param int $personId
     *
     * @return string
     */
    public static function getDefaultCode($personId)
    {
        return "ID{$personId}";
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
            $rec->code = strtoupper($rec->code);
            
            if ($personId = $mvc->fetchField(array("#code = '[#1#]' AND #personId != {$rec->personId}", $rec->code), 'personId')) {
                $personLink = crm_Persons::getHyperlink($personId, true);
                $form->setError($personId, "Номерът е зает от|* {$personLink}");
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
        if (isset($rec->personId)) {
            $personState = crm_Persons::fetchField($rec->personId, 'state');
            $row->ROW_ATTR['class'] = "state-{$personState}";
            $row->personId = crm_Persons::getHyperlink($rec->personId, true);
            
            if (!crm_Persons::isInGroup($rec->personId, 'employees')) {
                $row->code = ht::createHint($row->code, "Лицето вече не е в група 'Служители", 'warning', false);
            }
        }
        
        $row->created = "{$row->createdOn} " . tr('от') . " {$row->createdBy}";
    }
    
    
    /**
     * Подготвя информацията
     *
     * @param stdClass $data
     */
    public function prepareData_(&$data)
    {
        $rec = self::fetch("#personId = {$data->masterId}");
        if (!empty($rec)) {
            $data->rec = $rec;
            $data->row = self::recToVerbal($rec);
            
            $fodlerQuery = planning_AssetResourceFolders::getQuery();
            $fodlerQuery->where("#classId={$this->getClassId()} AND #objectId = {$data->rec->id}");
            $fodlerQuery->show('folderId');
            $folders = arr::extractValuesFromArray($fodlerQuery->fetchAll(), 'folderId');
            $data->row->centers = core_Type::getByName('keylist(mvc=doc_Folders,select=title)')->toVerbal(keylist::fromArray($folders));
        } else {
            if ($this->haveRightFor('add', (object) array('personId' => $data->masterId))) {
                $data->addExtUrl = array($this, 'add', 'personId' => $data->masterId, 'ret_url' => true);
            }
        }
    }
    
    
    /**
     * Рендира информацията
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl;
     */
    public function renderData($data)
    {
        $tpl = getTplFromFile('crm/tpl/HrDetail.shtml');
        $tpl->append(tr('Служебен код') . ':', 'resTitle');
        
        if($data->row->_rowTools instanceof core_RowToolbar){
            $data->row->code_toolbar = $data->row->_rowTools->renderHtml();
        }
        $tpl->placeObject($data->row);
        
        if ($eRec = hr_EmployeeContracts::fetch("#personId = {$data->masterId}")) {
            $tpl->append(hr_EmployeeContracts::getHyperlink($eRec->id, true), 'contract');
            $tpl->append(hr_Positions::getHyperlink($eRec->positionId), 'positionId');
        }
        
        if (isset($data->addExtUrl)) {
            $link = ht::createLink('', $data->addExtUrl, false, 'title=Добавяне на служебни данни,ef_icon=img/16/add.png,style=float:right; height: 16px;');
            $tpl->append($link, 'emBtn');
        }
        
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec->personId)) {
            if (!crm_Persons::haveRightFor('edit', $rec->personId)) {
                $res = 'no_one';
            }
            
            if ($res != 'no_one') {
                if (!crm_Persons::isInGroup($rec->personId, 'employees')) {
                    $res = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Връща всички оператори, избрани като ресурси в папката
     *
     * @param int $folderId - ид на папка, NULL за всички
     *
     * @return array $options
     */
    public static function getByFolderId($folderId)
    {
        $options = array();
        
        // Ако папката не поддържа ресурси оператори да не се връща нищо
        $Cover = doc_Folders::getCover($folderId);
        $resourceTypes = $Cover->getResourceTypeArray();
        if (!isset($resourceTypes['hr'])) {
            
            return $options;
        }
        
        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        
        $classId = self::getClassId();
        $fQuery = planning_AssetResourceFolders::getQuery();
        $fQuery->where("#classId = {$classId} AND #folderId = {$folderId}");
        $fQuery->show('objectId');
        $objectIds = arr::extractValuesFromArray($fQuery->fetchAll(), 'objectId');
        
        $query = static::getQuery();
        $query->EXT('groupList', 'crm_Persons', 'externalName=groupList,externalKey=personId');
        $query->EXT('state', 'crm_Persons', 'externalName=state,externalKey=personId');
        $query->like('groupList', "|{$emplGroupId}|");
        $query->where("#state != 'rejected' && #state != 'closed'");
        $query->show('personId,code');
        
        if (count($objectIds)) {
            $query->in('id', $objectIds);
        } else {
            $query->where('1=2');
        }
        
        while ($rec = $query->fetch()) {
            $options[$rec->personId] = $rec->code;
        }
        
        return $options;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Връща кода като линк
     *
     * @param int $personId - ид на служителя
     *
     * @return core_ET $link - линк към визитката
     */
    public static function getCodeLink($personId)
    {
        $code = planning_Hr::fetchField("#personId = {$personId}", 'code');
        $name = crm_Persons::getVerbal($personId, 'name');
        
        $singleUrl = array();
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
            $singleUrl = crm_Persons::getSingleUrlArray($personId);
            if (count($singleUrl)) {
                $singleUrl['Tab'] = 'PersonsDetails';
            }
        }
        
        $link = ht::createLink($code, $singleUrl, false, "title=Към визитката на|* '{$name}'");
        
        return $link;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Първичния ключ на направения запис
     * @param stdClass     $rec     Всички полета, които току-що са били записани
     * @param string|array $fields  Имена на полетата, които sa записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $syncFolders = keylist::toArray($rec->centers);
        if(count($syncFolders)){
            $AssetFolders = cls::get('planning_AssetResourceFolders');
            
            // Досегашните записи
            $aQuery = $AssetFolders->getQuery();
            $aQuery->where("#classId = {$mvc->getClassId()} AND #objectId = {$rec->id}");
            $aQuery->show('folderId');
            $alreadyIn = array();
            while($aRec = $aQuery->fetch()){
                $alreadyIn[$aRec->folderId] = $aRec->id;
            }
           
            // Обновяват се
            foreach ($syncFolders as $folderId){
                $dRec = (object) array('classId' => $mvc->getClassId(), 'objectId' => $rec->id, 'folderId' => $folderId);
                $fields = $exRec = null;
                if ($AssetFolders->isUnique($dRec, $fields, $exRec)) {
                    $AssetFolders->save($dRec);
                }
                
                unset($alreadyIn[$folderId]);
            }
            
            // Тези, които не са се обновили се изтриват
            if(count($alreadyIn)){
                foreach ($alreadyIn as $id){
                    $AssetFolders->delete($id);
                }
            }
        }
    }
    
    
    /**
     * Обръща масив от потребители в имена с техните кодове
     *
     * @param mixed $arr       - масив или кейлист
     * @param bool  $withLinks
     *
     * @return array $arr
     */
    public static function getPersonsCodesArr($arr, $withLinks = false)
    {
        $res = array();
        $arr = (keylist::isKeylist($arr)) ? keylist::toArray($arr) : arr::make($arr, true);
        if (empty($arr)) {
            
            return $res;
        }
        
        $arr = array_keys($arr);
        if (is_array($arr)) {
            foreach ($arr as $id) {
                $rec = planning_Hr::fetch("#personId = {$id}");
                if (empty($rec)) {
                    continue;
                }
                $code = ($withLinks === true) ? self::getCodeLink($id) : $rec->code;
                $res[$id] = $code;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Прави заглавие на МО от данните в записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $code = self::getVerbal($rec, 'code');
        $name = crm_Persons::getVerbal($rec->personId, 'name');
        
        return "{$name} ({$code})";
    }
}
