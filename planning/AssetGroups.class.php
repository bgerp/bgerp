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
	public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2';
	
	
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
	public $listFields = 'name,createdOn,createdBy,state';
	
	
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
		$this->FLD('name', 'varchar(64,ci)', 'caption=Име, mandatory');
		$this->setDbUnique('name');
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec)){
			if(planning_AssetResources::fetchField("#groupId = {$rec->id} AND #state = 'active'") || planning_AssetResourcesNorms::fetchField("#objectId = {$rec->id} AND #classId = {$mvc->getClassId()}")){
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
	
	
	/**
	 * Ще има ли предупреждение при смяна на състоянието
	 * 
	 * @param stdClass $rec
	 * @return string|FALSE
	 */
	public function getChangeStateWarning($rec)
	{
		$msg = ($rec->state == 'active') ? 'Наистина ли желаете да деактивирате вида и всички оборудвания към него|*?' : 'Наистина ли желаете да активирате вида и всички оборудвания към него|*?';
		
		return $msg;
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 */
	protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
	{
		if($fields == 'state'){
			foreach (array('planning_AssetResources', 'planning_AssetResourcesNorms') as $det){
				$Detail = cls::get($det);
				$dQuery = $Detail->getQuery();
				$dQuery->where("#groupId = {$rec->id}");
				while($dRec = $dQuery->fetch()){
					$dRec->state = $rec->state;
					$Detail->save($dRec, 'state');
				}
			}
		}
	}
	
	
	/**
	 * Каква е нормата на артикула в групата
	 * 
	 * @param mixed $assets       - списък от оборудвания
	 * @param int|NULL $productId - ид на артикул
	 * @return array $result      - намерените норми
	 */
	public static function getNorm($assets, $productId = NULL)
	{
		$result = array();
		if(!$groupId = planning_AssetResources::getGroupId($assets)) return $result;
		$result = planning_AssetResourcesNorms::fetchNormRec('planning_AssetGroups', $groupId, $productId);
		
		return $result;
	}
}