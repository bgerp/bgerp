<?php



/**
 * Мениджър на групите на оборудването
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_AssetGroups extends core_Master
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Видове оборудване';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper';
	
	
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
	public $canList = 'ceo, planning';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'name,createdOn,createdBy';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Вид';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutAssetGroup.shtml';
	
	
	/**
	 * Детайли
	 */
	public $details = 'planning_AssetResourcesNorms,planning_AssetResources';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'name';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('name', 'varchar(64,ci)', 'caption=Наименование, mandatory');
		
		$this->setDbUnique('name');
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec)){
			if(planning_AssetResources::fetchField("#groupId = {$rec->id} AND #state = 'active'") || planning_AssetResourcesNorms::fetchField("#groupId = {$rec->id}")){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * Дали оборудванията са от една и съща група
	 * 
	 * @param array|string $assets - ид-та на оборудвания
	 * @return boolean
	 */
	public static function haveSameGroup($assets)
	{
		$assets = is_array($assets) ? $assets : keylist::toArray($assets);
		if(!count($assets)) return TRUE;
		
		$aQuery = planning_AssetResources::getQuery();
		$aQuery->in("id", $assets);
		$aQuery->show('groupId');
		$aQuery->groupBy('groupId');
		$found = $aQuery->fetchAll();
		$found = is_array($aQuery->fetchAll()) ? $aQuery->fetchAll() : array();
		
		return count($found) == 1;
	}
}