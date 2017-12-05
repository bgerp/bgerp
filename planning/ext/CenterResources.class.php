<?php



/**
 * Помощен детайл подготвящ и обединяващ заедно ресурсите на центровете на дейност
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ext_CenterResources extends core_Manager
{
	
	
	/**
	 * Единично заглавие
	 */
	public $title = 'Ресурси към оборудване';
	
	
	/**
	 * Единично заглавие
	 */
	public $singleTitle = 'Ресурс към оборудване';
	
	
	/**
	 * Колко да са на страница заданията
	 */
	public $listCodesPerPage = 20;
	
	
	/**
	 * Колко да са на страница другите документи
	 */
	public $listAssetsPerPage = 20;
	
	
	/**
	 * Кой може да избира ресурс
	 */
	public $canSelectresource = 'powerUser';
	
	
	/**
	 * Подготвя ресурсите на центъра на дейност
	 */
	public function prepareAssets_(&$data)
	{
		$data->TabCaption = 'Ресурси';
		
		// Подготовка на данните за служителите
		$data->eData = clone $data;
		$data->eData->itemsPerPage = $this->listCodesPerPage;
		$data->eData->listTableMvc = clone cls::get('crm_ext_Employees');
		$this->prepareResources($data->eData, 'crm_ext_Employees');
		
		// Подготовка на данните за оборудването
		$data->aData = clone $data;
		$data->aData->itemsPerPage = $this->listAssetsPerPage;
		$data->aData->listTableMvc = clone cls::get('planning_AssetResources');
		$this->prepareResources($data->aData, 'planning_AssetResources');
	}

	
	/**
	 * Подготвя ресурсите
	 * 
	 * @param stdClass $data     - датата
	 * @param string $DetailName - на кой клас
	 */
	private function prepareResources(&$data, $DetailName)
	{
		$data->recs = $data->rows = array();
    	$query = $DetailName::getQuery();
    	if($query->getField('state', FALSE)){
    		$query->where("#state != 'rejected'");
    	}
    	
    	if(!($DetailName == 'planning_AssetResources' && $data->masterId == planning_Centers::UNDEFINED_ACTIVITY_CENTER_ID)){
    		$query->where("LOCATE('|{$data->masterId}|', #departments) OR #departments IS NULL");
    	}
    	
    	// Подготовка на пейджъра
    	$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $data->itemsPerPage));
    	$data->Pager->setPageVar('planning_Centers', $data->masterId, $DetailName);
    	$data->Pager->setLimit($query);
    	
    	// Извличане на записите
    	while($dRec = $query->fetch()){
    		$data->recs[$dRec->id] = $dRec;
    		$data->rows[$dRec->id] = $DetailName::recToVerbal($dRec);
    	}
    	
    	// Подготовка на полетата за показване
    	$listFields = ($DetailName == 'crm_ext_Employees') ? "code=Код,personId=Служител,created=Създаване" : "name=Оборудване,groupId=Вид,created=Създаване";
    	$data->listFields = arr::make($listFields, TRUE);
    	
    	$type = ($DetailName == 'planning_AssetResources') ? 'asset' : 'employee';
    	if($this->haveRightFor('selectresource', (object)array('centerId' => $data->masterId, 'type' => $type))){
    		$data->addUrl = array($this, 'selectresource', 'centerId' => $data->masterId, 'type' => $type, 'ret_url' => TRUE);
    	}
	}
	
	
	/**
	 * Рендиране на ресурсите
	 * 
	 * @param stdClass $data     - датата
	 * @param string $DetailName - на кой клас
	 */
	private function renderResources(&$data, $DetailName)
	{
		$Document = cls::get($DetailName);
		$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
		if($DetailName == 'crm_ext_Employees'){
			$tpl->replace("style='margin-top:10px'", 'STYLE');
		} else {
			$hint = ',title=Добавяне на ново оборудване към центъра на дейност';
		}
		
		$title = ($DetailName == 'crm_ext_Employees') ? 'Служители' : 'Оборудване';
		$tpl->append($title, 'title');
		if(isset($data->addUrl)){
			$tpl->append(ht::createLink('', $data->addUrl, FALSE, "ef_icon=img/16/edit.png{$hint}"), 'title');
		}
		
		foreach ($data->listFields as $fldName => $fldCaption){
			if($data->listTableMvc->getField($fldName, FALSE)){
				$data->listTableMvc->setField($fldName, 'tdClass=leftCol');
			}
		}
		
		$Document->invoke('BeforeRenderListTable', array($tpl, &$data));
		$table = cls::get('core_TableView', array('mvc' => $data->listTableMvc));
		$details = $table->get($data->rows, $data->listFields);
		
		$tpl->append($details, 'content');
		if(isset($data->Pager)){
			$tpl->append($data->Pager->getHtml(), 'content');
		}
		
		$tpl->removePlaces();
		$tpl->removeBlocks();
		
		return $tpl;
	}
	
	
	/**
	 * Рендира таблицата с документите
	 * 
	 * @param stdClass $data
	 * @return void|core_ET
	 */
	public function renderAssets_(&$data)
	{
		$tpl = new core_ET("");
		
		$tpl->append($this->renderResources($data->aData, 'planning_AssetResources'));
		$tpl->append($this->renderResources($data->eData, 'crm_ext_Employees'));
		
		return $tpl;
	}
	
	
	/**
	 * Промяна на оборудванията
	 */
	function act_SelectResource()
	{
		$this->requireRightFor('selectresource');
		expect($centerId = Request::get('centerId', 'int'));
		expect($type = Request::get('type', 'enum(employee,asset)'));
		expect($cRec = planning_Centers::fetch($centerId));
		$this->requireRightFor('selectresource', (object)array('centerId' => $centerId, 'type' => $type));
		$this->load('planning_Wrapper');
		$this->currentTab = 'Центрове';
		
		$form = cls::get('core_Form');
		$options = $default = array();
		
		// Ако се променят оборудванията
		if($type == 'asset'){
			$typeTitle = 'оборудванията';
			$form->FLD('select', 'keylist(mvc=planning_AssetResources,select=name)', "caption=Оборудвания");
			$aQuery = planning_AssetResources::getQuery();
			$aQuery->where("#state != 'closed'");
			while($aRec = $aQuery->fetch()){
				$recTitle = planning_AssetResources::getRecTitle($aRec, FALSE);
				$options[$aRec->id] = $recTitle;
				
				if(keylist::isIn($centerId, $aRec->departments) || is_null($aRec->departments)){
					$default[$aRec->id] = $recTitle;
				}
			}
		} else {
			
			// Ако се променят служителите
			$typeTitle = 'служителите';
			$form->FLD('select', 'keylist(mvc=crm_Persons,select=name)', "caption=Служители");
			$options = crm_Persons::getEmployeesOptions();
			$dQuery = crm_ext_Employees::getQuery();
			$dQuery->where("LOCATE('|{$centerId}|', #departments)");
			$dQuery->show('personId');
			$default = arr::extractValuesFromArray($dQuery->fetchAll(), 'personId');
		}
		
		// Задаване на полетата от формата
		$form->title = "Промяна на {$typeTitle} към|* " . cls::get('planning_Centers')->getFormTitleLink($centerId);
		$form->setSuggestions('select', $options);
		$form->setDefault('select', keylist::fromArray($default));
		$form->input();
		
		// При събмит на формата
		if($form->isSubmitted()){
			$selected = keylist::toArray($form->rec->select);
			
			// Избраните се обновява департамента им
			foreach ($selected as $id => $name){
				if($type == 'asset'){
					$eRec = planning_AssetResources::fetch($id);
					$eRec->departments = keylist::addKey($eRec->departments, $centerId);
					planning_AssetResources::save($eRec);
				} else {
					if($pRec = crm_ext_Employees::fetch("#personId = {$id}")){
						$pRec->departments = keylist::addKey($pRec->departments, $centerId);
						crm_ext_Employees::save($pRec);
					} else {
						crm_ext_Employees::save((object)array("personId" => $id, 'departments' => keylist::addKey('', $centerId)));
					}
				}
			}
				
			// Махане на съществуващите
			$removeArr = array_diff_key($default, $selected);
			foreach ($removeArr as $rId => $rName){
				if($type == 'asset'){
					$eRec = planning_AssetResources::fetch($rId);
					$eRec->departments = keylist::removeKey($eRec->departments, $centerId);
					planning_AssetResources::save($eRec);
				} else {
					$eRec = crm_ext_Employees::fetch("#personId = {$rId}");
					$eRec->departments = keylist::removeKey($eRec->departments, $centerId);
					crm_ext_Employees::save($eRec);
				}
			}
			
			followRetUrl(NULL, 'Информацията е обновена успешно');
		}
		
		// Бутони
		$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на промените');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
		
		// Записваме, че потребителя е разглеждал този списък
		$this->logInfo("Промяна на ресурсите на центъра на дейност");
		 
		return $this->renderWrapping($form->renderHtml());
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'selectresource' && isset($rec)){
			$folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
			if(!doc_Folders::haveRightToFolder($folderId, $userId) || $rec->centerId == planning_Centers::UNDEFINED_ACTIVITY_CENTER_ID){
				$requiredRoles = 'no_one';
			} elseif($rec->type == 'asset'){
				if(!planning_AssetResources::haveRightFor('add')){
					$requiredRoles = 'no_one';
				}
			} elseif($rec->type == 'employee'){
				if(!crm_ext_Employees::haveRightFor('edit')){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
}  
    