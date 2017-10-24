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
	public $title = 'Групи на оборудването';
	
	
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
	public $listFields = 'name,count,createdOn,createdBy';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Група';
	
	
	/**
	 * Детайли
	 */
	public $details = 'planning_AssetResourcesNorms';
	
	
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
		$this->FNC('count', 'int', 'caption=Оборудване');
		
		$this->setDbUnique('name');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$count = planning_AssetResources::count("#groupId = {$rec->id} AND #state = 'active'");
		$row->count = core_Type::getByName('int')->toVerbal($count);
		$row->count = ht::createLinkRef($row->count, array('planning_AssetResources', 'list', 'groupId' => $rec->id));
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec)){
			if(planning_AssetResources::fetchField("#groupId = {$rec->id} AND #state = 'active'")){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	public static function haveSameGroup($assets)
	{
		$assets = is_array($assets) ? $assets : keylist::toArray($assets);
		if(!count()) return;
	}
}