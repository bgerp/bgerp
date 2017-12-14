<?php



/**
 * Мениджър за човешките ресурси в производството
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class planning_Hr extends core_Manager
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'crm_ext_Employees';
	
	
	/**
     * Заглавие
     */
    public $title = 'Служебни информации';

    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Служебна информация';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'planning_Wrapper,plg_Created,plg_RowTools2,plg_Search';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Ресурси->Служители';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,planningMaster';


    /**
     * Кой може да създава
     */
    public $canAdd = 'ceo,planningMaster';
    
    
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
    public $listFields = 'code,personId,folders,createdOn,createdBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'personId, code, folders';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,mandatory,caption=Лице');
        $this->FLD('code', 'varchar', 'caption=Код');
        $this->FLD('folders', 'keylist(mvc=doc_Folders,select=title)', 'caption=Папки,mandatory,oldFieldName=departments');
        
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
    	
    	if(empty($rec->personId)){
    		$form->setField('personId', 'input');
    		$form->setOptions('personId', array('' => '') + crm_Persons::getEmployeesOptions(TRUE, TRUE));
    	}
    
    	// Допустимите папки
    	$suggestions = doc_FolderResources::getFolderSuggestions('hr');
    	$form->setSuggestions('folders', array('' => '') + $suggestions);
    	
    	// По дефолт е избрана папката на неопределения център
    	$defFolderId = planning_Centers::getUndefinedFolderId();
    	$form->setDefault('folders', keylist::fromArray(array($defFolderId => $defFolderId)));
    }
    
    
    /**
     * Преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->code)){
    		$rec->code = self::getDefaultCode($rec->personId);
    	}
    	
    	if(empty($rec->folders)){
    		$rec->folders = keylist::addKey('', planning_Centers::getUndefinedFolderId());
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->personId)){
    		$data->form->title = core_Detail::getEditTitle('crm_Persons', $rec->personId, 'служебната информация', $rec->id, $mvc->formTitlePreposition);
    	}
    }
    
    
    /**
     * Дефолтния код за лицето
     * 
     * @param int $personId
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
    	
    	if($form->isSubmitted()){
    		$rec->code = strtoupper($rec->code);
    			
    		if($personId = $mvc->fetchField(array("#code = '[#1#]' AND #personId != {$rec->personId}", $rec->code), 'personId')){
    			$personLink = crm_Persons::getHyperlink($personId, TRUE);
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
    	$personState = crm_Persons::fetchField($rec->personId, 'state');
    	$row->ROW_ATTR['class'] = "state-{$personState}";
    	$row->created = "{$row->createdOn} " . tr("от") . " {$row->createdBy}";
    	$row->personId = crm_Persons::getHyperlink($rec->personId, TRUE);
    	$row->folders = doc_Folders::getVerbalLinks($rec->folders, TRUE);
    	
    	if(!crm_Persons::isInGroup($rec->personId, 'employees')){
    		$row->personId = ht::createHint($row->personId, "Лицето вече не е в група 'Служители", 'warning', FALSE);
    	}
    }
    
    
    /**
     * Подготвя информацията
     *
     * @param stdClass $data
     */
    public function prepareData_(&$data)
    {
    	$rec = self::fetch("#personId = {$data->masterId}");
    	
    	if(!empty($rec)){
    		$data->row = self::recToVerbal($rec);
    		if($this->haveRightFor('edit', $rec->id)){
    			$data->editResourceUrl = array($this, 'edit', $rec->id, 'ret_url' => TRUE);
    		}
    	} else {
    		if($this->haveRightFor('add', (object)array('personId' => $data->masterId))){
    			$data->addExtUrl = array($this, 'add', 'personId' => $data->masterId, 'ret_url' => TRUE);
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
    	 $tpl->append(tr('Служебен код') . ":", 'resTitle');
    	 $tpl->placeObject($data->row);
    	 
    	 if($eRec = hr_EmployeeContracts::fetch("#personId = {$data->masterId}")){
    	 	$tpl->append(hr_EmployeeContracts::getHyperlink($eRec->id, TRUE), 'contract');
    	 	$tpl->append(hr_Positions::getHyperlink($eRec->positionId), 'positionId');
    	 }
    	 
    	 if(isset($data->addExtUrl)){
    	 	$link = ht::createLink('', $data->addExtUrl, FALSE, "title=Добавяне на служебни данни,ef_icon=img/16/add.png,style=float:right; height: 16px;");
    	 	$tpl->append($link, 'emBtn');
    	 }
    	 
    	 if(isset($data->editResourceUrl)){
    	 	$link = ht::createLink('', $data->editResourceUrl, FALSE, "title=Редактиране на служебни данни,ef_icon=img/16/edit.png,style=float:right; height: 16px;");
    	 	$tpl->append($link, 'emBtn');
    	 }
    	 
    	 $tpl->removeBlocks();
    	 
    	 return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec->personId)){
    		if(!crm_Persons::haveRightFor('edit', $rec->personId)){
    			$res = 'no_one';
    		}
    		
    		if($res != 'no_one'){
    			if(!crm_Persons::isInGroup($rec->personId, 'employees')){
    				$res = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща всички служители, избрани като ресурси в папката
     * 
     * @param int $folderId   - ид на папка
     * @return array $options - масив със служители
     */
    public static function getEmployees($folderId)
    {
    	// Ако папката не поддържа ресурси служители да не се връща нищо
    	$Cover = doc_Folders::getCover($folderId);
    	$resourceTypes = $Cover->getResourceTypeArray();
    	if(!isset($resourceTypes['hr'])) return $res;
    	
    	$options = array();
    	$emplGroupId = crm_Groups::getIdFromSysId('employees');
    	
    	$query = static::getQuery();
    	$query->EXT('groupList', 'crm_Persons', 'externalName=groupList,externalKey=personId');
    	$query->EXT('state', 'crm_Persons', 'externalName=state,externalKey=personId');
    	$query->like("groupList", "|{$emplGroupId}|");
    	$query->where("LOCATE('|{$folderId}|', #folders) AND (#state != 'rejected' && #state != 'closed')");
    	$query->show("personId,code,state");
    	
    	while($rec = $query->fetch()){
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
     * @param int $personId  - ид на служителя
     * @return core_ET $link - линк към визитката
     */
    public static function getCodeLink($personId)
    {
    	$code = planning_Hr::fetchField("#personId = {$personId}", 'code');
    	$name = crm_Persons::getVerbal($personId, 'name');
    	 
    	$singleUrl = crm_Persons::getSingleUrlArray($personId);
    	if(count($singleUrl)){
    		$singleUrl['Tab'] = 'PersonsDetails';
    	}
    	
    	$link = ht::createLink($code, $singleUrl, FALSE, "title=Към визитката на|* '{$name}'");
    	$link = ht::createHint($link, $name, 'img/16/vcard.png', FALSE);
    	
    	return $link;
    }
}