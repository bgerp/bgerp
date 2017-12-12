<?php



/**
 * Мениджър на нормите за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_AssetResourcesNorms extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Норми за дейности';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Норма за дейности';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_AlignDecimals2';
	
	
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
	public $canDelete = 'no_one';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, planning';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'objectId,productId=Дейност,packagingId=Мярка/Опаковка,indTime,limit,state';
	
	
	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	public $hideListFieldsIfEmpty = 'state';
	
	
	/**
	 * Дали в листовия изглед да се показва бутона за добавяне
	 */
	public $listAddBtn = FALSE;
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('objectId', 'int', 'caption=Оборудване/Група,mandatory,silent,input=hidden,tdClass=leftCol');
		$this->FLD('classId', 'class', 'caption=Клас,mandatory,silent,input=hidden');
		$this->FLD("productId", 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул');
		$this->FLD("indTime", 'time(noSmart)', 'caption=Норма,smartCenter,mandatory');
		$this->FLD("packagingId", 'key(mvc=cat_UoM,select=shortName)', 'caption=Опаковка,smartCenter,input=hidden');
		$this->FLD("quantityInPack", 'double', 'input=hidden');
		$this->FLD("limit", 'double(min=0)', 'caption=Лимит,smartCenter');
		
		$this->setDbUnique('classId,objectId,productId');
		$this->setDbIndex('classId,objectId');
	}
	
	
	/**
	 * Подготовка на детайла
	 * 
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareDetail_(&$data)
	{
		$data->recs = $data->rows = array();
		$masterClassId = $data->masterMvc->getClassId();
		$query = self::getQuery();
		$query->where("#classId = {$masterClassId} AND #objectId = {$data->masterId} AND #state != 'closed'");
		
		// Извличане на записите
		while($rec = $query->fetch()){
			$data->recs[$rec->productId] = $rec;
			$data->rows[$rec->productId] = $this->recToVerbal($rec);
		}
		
		// Бутон за добавяне на нова норма
		if($this->haveRightFor('add', (object)array('classId' => $masterClassId, 'objectId' => $data->masterId))){
			$addUrl = array($this, 'add', 'classId' => $masterClassId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
			$data->addUrl = $addUrl;
		}
		
		// Ако се показва в Оборудването
		if($data->masterMvc instanceof  planning_AssetResources){
			
			// Взимат се всички норми от групата му
			$gQuery = self::getQuery();
			$gQuery->where("#classId = {$data->masterMvc->Master->getClassId()} AND #objectId = {$data->masterData->rec->groupId} AND #state != 'closed'");
			$gQuery->notIn('productId', arr::extractValuesFromArray($data->recs, 'productId'));
			
			// Те ще се показват под неговите норми
			while($rec = $gQuery->fetch()){
				$data->recs[$rec->productId] = $rec;
				$row = $this->recToVerbal($rec);
				$row->ROW_ATTR['class'] = 'zebra1';
				core_RowToolbar::createIfNotExists($row->_rowTools);
				$row->_rowTools->removeBtn("*");
				$row->indTime = ht::createHint($row->indTime, 'Нормата идва от вида на оборудването', 'notice', FALSE);
				
				unset($row->state);
				if(isset($addUrl)){
					$addUrl['productId'] = $rec->productId;
					$row->_rowTools->addLink('', $addUrl, array('ef_icon' => "img/16/add.png", 'title' => "Задаване на норма само за това оборудване"));
				}
				
				$data->rows[$rec->productId] = $row;
			}
		}
		
		// Подготовка на полетата на таблицата
		$this->prepareListFields($data);
	}
	
	
	/**
	 * Рендиране на детайла
	 * 
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public function renderDetail_($data)
	{
		$tpl = new core_ET("");
		$tpl = $this->renderList($data);
		
		if(isset($data->addUrl)){
			$addBtn = ht::createBtn('Нова норма', $data->addUrl, FALSE, FALSE, 'ef_icon=img/16/star_2.png,title=Добавяне на нова норма');
			$tpl->replace($addBtn, 'ListToolbar');
		}
		
		return $tpl;
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		
		// Добавяне само на вложимите услуги
		$productOptions = cat_Products::getByProperty('canConvert', 'canStore');
		$form->setOptions('productId', array('' => '') + $productOptions);
		$form->setSuggestions('limit', array('' => '', '1' => '1'));
	}
	
	
	/**
	 * След подготовката на заглавието на формата
	 */
	protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
	{
		$data->form->title = core_Detail::getEditTitle($data->form->rec->classId, $data->form->rec->objectId, $mvc->singleTitle, $data->form->rec->id);
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = $form->rec;
		
		if($form->isSubmitted()){
			$rec->packagingId = cat_Products::fetchField($rec->productId, 'measureId');
			$rec->quantityInPack = 1;
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
		$row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, TRUE);
		if(!isset($rec->limit)){
			$row->limit = "<i class='quiet'>" . tr('няма||no') . "</i>";
		}
	}
	
	
	/**
	 * Преди подготовката на полетата за листовия изглед
	 */
	protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
	{
		if(isset($data->masterMvc)){
			unset($data->listFields['objectId']);
		}
	}
	
	
	/**
	 * Намира норма за артикула
	 * 
	 * @param mixed $class          - клас към който е нормата       
	 * @param int $objectId         - ид на обект
	 * @param int|NULL $productId   - ид на точен артикул
	 * @param array|NULL $notIn     - ид на артикули да се изключат
	 * @return array $res	        - запис на нормата
	 */
	public static function fetchNormRec($class, $objectId, $productId = NULL, $notIn = NULL)
	{
		$res = array();
		$classId = cls::get($class)->getClassId();
		
		$query = self::getQuery();
		$query->where("#classId = {$classId} AND #objectId = {$objectId} AND #state != 'closed'");
		$query->show('productId,indTime,packagingId,quantityInPack,limit');
		$query->notIn("productId", $notIn);
		if(isset($productId)){
			$query->where("#productId = {$productId}");
		}
		
		while($rec = $query->fetch()){
			$res[$rec->productId] = $rec;
		}
		
		return $res;
	}
	
	
	/**
	 * Връща опциите за избор на действия за оборудването
	 * 
	 * @param mixed $assets     - списък с оборудвания
	 * @param array|NULL $notIn - ид-та на артикули, които да се игнорират
	 * @return array $options   - имена на действия, групирани по оборудвания
	 */
	public static function getNormOptions($assets, $notIn = NULL)
	{
		$options = array();
		
		// Извличат се нормите от групата
		if(!$groupId = planning_AssetResources::getGroupId($assets)) return $options;
		$groupAssets = self::fetchNormRec('planning_AssetGroups', $groupId, NULL, $notIn);
		$notIn += arr::make(array_keys($groupAssets), TRUE);
		
		$arr = array();
		if(count($groupAssets)){
			$group = planning_AssetGroups::getVerbal($groupId, 'name');
			$options = array('g' => (object)array('group' => TRUE, 'title' => $group));
			foreach ($groupAssets as $productId => $rec){
				$arr[$rec->productId] = cat_Products::getTitleById($productId, FALSE);
			}
			$options += $arr;
		}
		
		// За всяко оборудване, добавят се неговите специфични действия, които ги няма в групата му
		foreach ($assets as $assetId){
			$assetArr = array();
			$assetNorms = self::fetchNormRec('planning_AssetResources', $assetId, NULL, $notIn);
			foreach ($assetNorms as $productId => $rec1){
				$assetArr[$rec1->productId] = cat_Products::getTitleById($productId, FALSE);
			}
			
			if(count($assetArr)){
				$assetName = planning_AssetResources::getTitleById($assetId, FALSE);
				$options += array("a{$assetId}" => (object)array('group' => TRUE, 'title' => $assetName)) + $assetArr;
			}
		}
		
		// Връщане на готовите опции
		return $options;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'changestate' && isset($rec)){
			$groupState = cls::get($rec->classId)->fetchField($rec->objectId, 'state');
			if($groupState == 'closed'){
				$requiredRoles = 'no_one';
			}
		}
		
		if($action == 'add' && isset($rec)){
			if(empty($rec->classId) || empty($rec->objectId)){
				$requiredRoles = 'no_one';
			} elseif($rec->classId != planning_AssetResources::getClassId() && $rec->classId != planning_AssetGroups::getClassId()){
				$requiredRoles = 'no_one';
			}
		}
	}
}
