<?php


/**
 * Мениджър за човешките ресурси в производството
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
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
    public $loadList = 'planning_Wrapper,plg_PrevAndNext,plg_Select,plg_Sorting,plg_Created,plg_RowTools2,plg_Search,label_plg_Print';
    
    
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
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name)', 'input=hidden,silent,mandatory,caption=Оператор');
        $this->FLD('code', 'varchar', 'caption=Код');
        $this->FLD('scheduleId', 'key(mvc=hr_Schedules, select=name, allowEmpty)', 'caption=Работен график');
        $this->FNC('centers', 'keylist(mvc=doc_Folders,select=title)', 'mandatory, input, caption=Центрове на дейност');
        $this->setDbIndex('code');
        $this->setDbUnique('personId');
    }

    
    /**
     * Изчисляване на функционалното поле centers
     */
    public function on_CalcCenters(core_Mvc $mvc, $rec)
    {
        $folderQuery = planning_AssetResourceFolders::getQuery();
        $folderQuery->where("#classId={$this->getClassId()} AND #objectId = {$rec->id}");
        $folderQuery->show('folderId');
        $folders = arr::extractValuesFromArray($folderQuery->fetchAll(), 'folderId');

        if(countR($folders)) {
            $cQuery = planning_Centers::getQuery();
            $cQuery->show('folderId');
            $cQuery->in('folderId', $folders);
            $centers = arr::extractValuesFromArray($cQuery->fetchAll(), 'folderId');
            $rec->centers = keylist::fromArray($centers);
        }
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
     * @return core_ET $tpl;
     */
    public function renderData($data)
    {
        $tpl = getTplFromFile('crm/tpl/HrDetail.shtml');
        $tpl->append(tr('Служебен код') . ':', 'resTitle');
        
        if($data->row->_rowTools instanceof core_RowToolbar){
            $data->row->code_toolbar = $data->row->_rowTools->renderHtml();
        }

        if(isset($data->row->scheduleId)) {
            $data->row->scheduleId = hr_Schedules::getHyperLink($data->rec->scheduleId, true);
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
     * Връща приложимото работно време за дадения служител
     * Вземат се по реда на приоритет:
     *    о Ако има зададен Работен график в този модел
     *    о Работния график на департамента на служителя
     *    о Дефолтния работен график = null
     *
     * @param int $personId
     *
     * @return int?
     */
    public static function getSchedule($personId)
    {
        $scheduleId = null;
                
        // Опитваме се да вземем персоналния график на служителя
        $hrRec = self::fetch("#personId = {$personId}");

        if(isset($hrRec->scheduleId)) {
            $scheduleId = $hrRec->scheduleId;
        } else {
            $state = hr_EmployeeContracts::getQuery();
            $state->where("#personId='{$personId}' AND #state = 'active'");
            if ($employeeContractDetails = $state->fetch()) {
                if(isset($employeeContractDetails->departmentId)) {
                    $pcRec = planning_Centers::fetch($employeeContractDetails->departmentId);
                    if(isset($pcRec->scheduleId)) {
                        $scheduleId = $pcRec->scheduleId;
                    }
                }
            }
        }

        if(empty($scheduleId)){
            $scheduleId = hr_Schedules::getDefaultScheduleId();
        }

        return $scheduleId;
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
     * @param int|null $folderId   - ид на папка, NULL за всички
     * @param mixed $exIds         - ид-та които да се добавят към опциите
     * @param boolean $codesAsKeys - дали ключа да са кодовете или ид-то на лицето
     * @return array $options      - опции за избор
     */
    public static function getByFolderId($folderId = null, $exIds = null, $codesAsKeys = false)
    {
        $options = $tempOptions = $codes = array();
        $noOptions = false;

        // Ако папката не поддържа ресурси оператори да не се връща нищо
        if(isset($folderId)){
            $Cover = doc_Folders::getCover($folderId);
            $resourceTypes = $Cover->getResourceTypeArray();
            if (!isset($resourceTypes['hr'])) {
                $noOptions = true;
            }
        }

        if(!$noOptions){
            $employeeGroupId = crm_Groups::getIdFromSysId('employees');
            $classId = self::getClassId();
            $fQuery = planning_AssetResourceFolders::getQuery();
            $fQuery->where("#classId = {$classId}");
            if(isset($folderId)){
                $fQuery->where("#folderId = {$folderId}");
            }
            $fQuery->show('objectId');
            $objectIds = arr::extractValuesFromArray($fQuery->fetchAll(), 'objectId');

            $query = static::getQuery();
            $query->EXT('groupList', 'crm_Persons', 'externalName=groupList,externalKey=personId');
            $query->EXT('state', 'crm_Persons', 'externalName=state,externalKey=personId');
            $query->like('groupList', "|{$employeeGroupId}|");
            $query->where("#state != 'rejected' && #state != 'closed'");
            $query->show('personId,code');
            if (countR($objectIds)) {
                $query->in('id', $objectIds);
            } else {
                $query->where('1=2');
            }

            while ($rec = $query->fetch()) {
                $codes[$rec->personId] = $rec->code;
                $tempOptions[$rec->personId] = crm_Persons::getVerbal($rec->personId, 'name');
            }
        }

        // Ако има съществуващи ид-та и тях ги няма в опциите да се добавят
        if(isset($exIds)) {
            $exOptions = keylist::isKeylist($exIds) ? keylist::toArray($exIds) : arr::make($exIds, true);
            foreach ($exOptions as $eId) {
                if (!array_key_exists($eId, $tempOptions)) {
                    $exCode = static::fetchField("#personId = {$eId}", 'code');
                    $codes[$eId] = $exCode;
                    $tempOptions[$eId] = crm_Persons::getVerbal($eId, 'name');
                }
            }
        }

        asort($tempOptions);
        foreach ($tempOptions as $personId => $val){
            $key = $codesAsKeys ? $codes[$personId] : $personId;
            $options[$key] = "{$codes[$personId]} - {$val}";
        }

        return $options;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('centerId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Център на дейност');
        $data->listFilter->showFields = 'search,centerId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        if($rec = $data->listFilter->rec){
            if(isset($rec->centerId)){
                $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');

                $folderQuery = planning_AssetResourceFolders::getQuery();
                $folderQuery->where("#classId = {$mvc->getClassId()} AND #folderId = {$folderId}");
                $ids = arr::extractValuesFromArray($folderQuery->fetchAll(), 'objectId');
                if(countR($ids)){
                    $data->query->in('id', $ids);
                } else {
                    $data->query->where("1=2");
                }
            }
        }
    }
    
    
    /**
     * Връща кода като линк
     *
     * @param int $personId - ид на служителя
     * @return core_ET $link - линк към визитката
     */
    public static function getCodeLink($personId)
    {
        $singleUrl = array();
        $code = planning_Hr::fetchField("#personId = {$personId}", 'code');
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
            $singleUrl = crm_Persons::getSingleUrlArray($personId);
            if (countR($singleUrl)) {
                $singleUrl['Tab'] = 'PersonsDetails';
            }
        }

        $name = crm_Persons::getVerbal($personId, 'name');
        $link = ht::createLink($code, $singleUrl, false, "title={$name}");
        
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
        if(countR($syncFolders)){
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
            if(countR($alreadyIn)){
                foreach ($alreadyIn as $id){
                    $AssetFolders->delete($id);
                }
            }
        }
    }
    
    
    /**
     * Обръща масив от потребители в имена с техните кодове
     *
     * @param mixed $arr         - масив или кейлист
     * @param bool  $withLinks   - дали да са линкове
     * @param bool  $codesAsKeys - дали ключа да е кода или ид-то
     *
     * @return array $arr
     */
    public static function getPersonsCodesArr($arr, $withLinks = false, $codesAsKeys = false)
    {
        $res = $tempKeys = $codes = array();
        $arr = (keylist::isKeylist($arr)) ? keylist::toArray($arr) : arr::make($arr, true);
        if (empty($arr)) return $res;

        $arr = array_keys($arr);
        foreach ($arr as $id) {
            $rec = planning_Hr::fetch("#personId = {$id}");
            if (empty($rec)) continue;

            $tempKeys[$id] = crm_Persons::getVerbal($id, 'name');
            $code = ($withLinks === true) ? self::getCodeLink($id) : $rec->code;
            $codes[$id] = $code;
        }

        asort($tempKeys);

        foreach ($tempKeys as $k => $v) {
            $key = ($codesAsKeys) ? $codes[$k] : $k;
            $res[$key] = "{$codes[$k]} - {$v}";
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


    /**
     * Парсира стринг към кейлист с лица
     *
     * @param string $string - стринг
     * @return object|null   - обект с парсиран стринга и грешките, ако има
     * @throws core_exception_Expect
     */
    public static function parseStringToKeylist($string)
    {
        $string = trim($string);
        if(empty($string)) return null;
        $string = trim($string, ',');
        $string = str::removeWhiteSpace($string, ',');

        // Нормализиране на кодовете
        $parsedCodes = $persons = $errorArr = array();
        $exploded = explode(',', $string);
        array_walk($exploded, function($a) use (&$parsedCodes){$v = trim($a);$v = strtoupper($v);if(!empty($v)) {$parsedCodes[$v] = $v;}});

        if(empty($parsedCodes)) return null;

        // Ако по този код има оператор - извлича се, ако няма ще се добави като грешка
        foreach ($parsedCodes as $code){
            $personId = static::getPersonIdByCode($code);
            if($personId){
                $persons[$personId] = $personId;
            } else {
                $errorArr[] = "<b>{$code}</b>";
            }
        }

        $res = (object)array('keylist' => keylist::fromArray($persons));
        if(countR($errorArr)){
            $res->error = "Следните кодове нямат оператори|*:" . implode(', ', $errorArr);
        }

        return $res;
    }


    /**
     * Обръща кейлист в стринг, готов за парсиране от 'parseStringToKeylist($string)'
     *
     * @param string $keylist - кейлист с лица
     * @return string|null    - парсиран стринг
     */
    public static function keylistToParsableString($keylist)
    {
        $personIds = keylist::toArray($keylist);
        if(!countR($personIds)) return null;

        $query = static::getQuery();
        $query->in('personId', $keylist);
        $query->show('code');
        $codes = arr::extractValuesFromArray($query->fetchAll(), 'code');
        if(!countR($codes)) return null;

        return implode(',', $codes);
    }


    /**
     * Връща ид-то на лицето с подадения код
     *
     * @param varchar $code - код
     * @return int|null     - ид на намереното лице (ключ към crm_Persons)
     */
    public static function getPersonIdByCode($code)
    {
        $normalizedCode = strtoupper(trim($code));
        $personId = planning_Hr::fetchField(array("#code='[#1#]'", $normalizedCode), 'personId');

        return (!empty($personId)) ? $personId : null;
    }
}
