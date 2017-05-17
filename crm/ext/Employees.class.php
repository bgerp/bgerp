<?php


/**
 * Мениджър на служебни кодове
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_Employees extends core_Manager
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'crm_ext_EmployeeCodes';
	
	
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
    public $loadList = 'crm_Wrapper';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Лица';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,planning,crm';


    /**
     * Кой може да създава
     */
    public $canAdd = 'ceo,planning,crm';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,mandatory');
        $this->FLD('code', 'varchar', 'caption=Код');
        $this->FLD('departments', 'keylist(mvc=hr_Departments,select=name,makeLinks)', 'caption=Отдел');
        
        $this->setDbUnique('personId');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle('crm_Persons', $rec->personId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = $form->rec;
    	
    	if($form->isSubmitted()){
    		
    		if(!empty($rec->code)){
    			$rec->code = strtoupper($rec->code);
    			
    			if($personId = $mvc->fetchField(array("#code = '[#1#]' AND #personId != {$rec->personId}", $rec->code), 'personId')){
    				$personLink = crm_Persons::getHyperlink($personId, TRUE);
    				$form->setError($personId, "Номерът е зает от|* {$personLink}");
    			}
    		}
    		
    		if(empty($rec->code)){
    			$rec->code = NULL;
    		}
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(empty($rec->code) && empty($rec->departments)){
    		self::delete($rec->id);
    	}
    }
    
    
    /**
     * Подготвя информацията
     *
     * @param stdClass $data
     */
    public function prepareData(&$data)
    {
    	$rec = self::fetch("#personId = {$data->masterId}");
    	
    	if(!empty($rec)){
    		$data->codeRec = $rec;
    		$row = self::recToVerbal($rec);
    		if(empty($rec->code)){
    			$row->code = "<b>" . tr('Няма') . "</b>";
    		}
    		
    		$data->row = $row;
    		
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
    	 $tpl = new core_ET(tr("|*[#resTitle#]<!--ET_BEGIN code--> <b>[#code#]</b><!--ET_END code--><!--ET_BEGIN departments--><br>|Департаменти|*: [#departments#]<!--ET_END departments-->"));
    	 $tpl->append(tr('Служебен код') . ":", 'resTitle');
    	 
    	 if(isset($data->row)){
    	 	$tpl->placeObject($data->row);
    	 } else {
    	 	$code = "<b>" . tr('Няма') . "</b>";
    	 	$tpl->append($code, 'code');
    	 }
    	 
    	 if(isset($data->addExtUrl)){
    	 	$link = ht::createLink('', $data->addExtUrl, FALSE, "title=Добавяне на служебни данни,ef_icon=img/16/add.png");
    	 	$tpl->append($link, 'emBtn');
    	 }
    	 
    	 if(isset($data->editResourceUrl)){
    	 	$link = ht::createLink('', $data->editResourceUrl, FALSE, "title=Редактиране на служебни данни,ef_icon=img/16/edit.png");
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
    			$employeeId = crm_Groups::getIdFromSysId('employees');
    			if(!keylist::isIn($employeeId, crm_Persons::fetchField($rec->personId, 'groupList'))){
    				$res = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща служебния код на лицето
     * 
     * @param int $personId   - ид на лицето
     * @param boolean $verbal - дали кода да е вервализиран
     * @return string $code   - кода
     */
    public static function getCode($personId, $verbal = FALSE)
    {
    	expect(type_Int::isInt($personId));
    	$code = static::fetchField(array("#personId = [#1#]", $personId), 'code');
    	
    	$noCode = FALSE;
    	if(empty($code)){
    		$code = "ID{$personId}";
    		$noCode = TRUE;
    	}
    	
    	if($verbal === TRUE){
    		$code = cls::get('type_Varchar')->toVerbal($code);
    		if($noCode === TRUE){
    			$code = "<span class='red'>{$code}</span>";
    			$code = ht::createHint($code, 'Служителят вече няма код', 'warning');
    		}
    	}
    	
    	return $code;
    }
    
    
    /**
     * Връща всички служители, които имат код
     * 
     * @return array $options - масив със служители
     */
    public static function getEmployeesWithCode()
    {
    	$options = array();
    	$emplGroupId = crm_Groups::getIdFromSysId('employees');
    	
    	$query = static::getQuery();
    	$query->EXT('groupList', 'crm_Persons', 'externalName=groupList,externalKey=personId');
    	$query->like("groupList", "|{$emplGroupId}|");
    	
    	$query->where("#code IS NOT NULL");
    	$query->show("personId,code");
    	
    	while($rec = $query->fetch()){
    		$options[$rec->personId] = $rec->code;
    	}
    	
    	return $options;
    }
    
    
    /**
     * Връща кода като линк
     * 
     * @param int $personId
     * @return core_ET $el
     */
    public static function getCodeLink($personId)
    {
    	$el = crm_ext_Employees::getCode($personId, TRUE);
    	$name = crm_Persons::getVerbal($personId, 'name');
    	 
    	$singleUrl = crm_Persons::getSingleUrlArray($personId);
    	if(count($singleUrl)){
    		$singleUrl['Tab'] = 'PersonsDetails';
    	}
    	 
    	$el = ht::createLink($el, $singleUrl, FALSE, "title=Към визитката на|* '{$name}'");
    	$el = ht::createHint($el, $name, 'img/16/vcard.png', FALSE);
    	
    	return $el;
    }
}