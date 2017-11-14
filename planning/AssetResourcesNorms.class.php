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
class planning_AssetResourcesNorms extends core_Detail
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Норми за артикули';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Норма за артикул';
	
	
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
	public $listFields = 'groupId,productId,packagingId=Мярка/Опаковка,indTime,limit,state';
	
	
	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	public $masterKey = 'groupId';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('groupId', 'key(mvc=planning_AssetGroups,select=name,allowEmpty)', 'caption=Вид,mandatory,silent');
		$this->FLD("productId", 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул');
		$this->FLD("indTime", 'time(noSmart)', 'caption=Норма,smartCenter,mandatory');
		$this->FLD("packagingId", 'key(mvc=cat_UoM,select=shortName)', 'caption=Опаковка,smartCenter,input=hidden');
		$this->FLD("quantityInPack", 'double', 'input=hidden');
		$this->FLD("limit", 'double(min=0)', 'caption=Лимит,smartCenter');
		
		$this->setDbUnique('groupId,productId');
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
		$row->groupId = planning_AssetGroups::getHyperlink($rec->groupId, TRUE);
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
			unset($data->listFields['groupId']);
		}
	}
	
	
	/**
	 * Връща нормите закачени към зададените оборудвания
	 * 
	 * @param mixed $assets       - списък от оборудвания, трябва да са от една група
	 * @param int|NULL $productId - дали се търси конкретна норма за един артикул
	 * @return array $res         - списък с норми
	 */
	public static function getNorms($assets, $productId = NULL)
	{
		$assets = is_array($assets) ? $assets : keylist::toArray($assets);
		if(!planning_AssetGroups::haveSameGroup($assets)) return array();
		$assets = array_values($assets);
		
		// Групата от която са оборудванията
		$groupId = planning_AssetResources::fetchField($assets[0], 'groupId');
		
		// Избор на всички норми
		$res = array();
		$query = self::getQuery();
    	$query->where("#groupId = {$groupId} AND #state != 'closed'");
    	$query->show('productId,indTime,packagingId,quantityInPack');
    	if(isset($productId)){
    		$query->where("#productId = {$productId}");
    	}
    	
    	// Добавяне на артикулите
    	while($rec = $query->fetch()){
    		$res[$rec->productId] = $rec;
    	}
    	
    	// Добавяне и група в опциите
    	if(count($res)){
    		$group = planning_AssetGroups::getVerbal($groupId, 'name');
    		$res = array('g' => (object)array('group' => TRUE, 'title' => $group)) + $res;
    	}
    	
    	return $res;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'changestate' && isset($rec)){
			$groupState = planning_AssetGroups::fetchField($rec->groupId, 'state');
			if($groupState == 'closed'){
				$requiredRoles = 'no_one';
			}
		}
	}
}
