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
	 * Колко да са на страница заданията
	 */
	public $listCodesPerPage = 20;
	
	
	/**
	 * Колко да са на страница другите документи
	 */
	public $listAssetsPerPage = 20;
	
	
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
    	$query->where("LOCATE('|{$data->masterId}|', #departments)");
    	
    	// Подготовка на пейджъра
    	$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $data->itemsPerPage));
    	$data->Pager->setPageVar('planning_ActivityCenters', $data->masterId, $DetailName);
    	$data->Pager->setLimit($query);
    	
    	// Извличане на записите
    	while($dRec = $query->fetch()){
    		$data->recs[$dRec->id] = $dRec;
    		$data->rows[$dRec->id] = $DetailName::recToVerbal($dRec);
    	}
    	
    	// Подготовка на полетата за показване
    	$listFields = ($DetailName == 'crm_ext_Employees') ? "code=Код,personId=Служител,created=Създаване" : "name=Оборудване,groupId=Вид,created=Създаване";
    	$data->listFields = arr::make($listFields, TRUE);
    	
    	// Бутон за добавяне
    	if($DetailName == 'planning_AssetResources'){
    		if(planning_AssetResources::haveRightFor('add')){
    			$data->addUrl = array('planning_AssetResources', 'add', 'departmentId' => $data->masterId, 'ret_url' => TRUE);
    		}
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
		}
		
		$title = ($DetailName == 'crm_ext_Employees') ? 'Служители' : 'Оборудване';
		$tpl->append($title, 'title');
		if(isset($data->addUrl)){
			$tpl->append(ht::createLink('', $data->addUrl, FALSE, "ef_icon=img/16/add.png"), 'title');
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
}  
    