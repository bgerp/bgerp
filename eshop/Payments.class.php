<?php



/**
 * Регистър на артикулите в каталога
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class eshop_Payments extends embed_Manager {
    
	
	/**
	 * Заглавие
	 */
	public $title = "Начини на плащане в е-магазина";
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Начин на плащане';
	
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'eshop_PaymentIntf';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, plg_State2, eshop_Wrapper, plg_Created, plg_Modified';
	
	
	/**
	 * Кой може да добавя?
	 */
	public $canAdd = 'eshop,ceo,admin';
	
	
	/**
	 * Кой може да пише?
	 */
	public $canWrite = 'eshop,ceo,admin';
	
	
	/**
	 * Кой може да го разгледа?
	 */
	public $canList = 'eshop,ceo,admin';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'title,state,createdOn,createdBy,modifiedOn,modifiedBy';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar', 'caption=Наименование');
		
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'changestate' || $action == 'delete') && isset($rec)){
			if(eshop_Settings::fetch("#payments LIKE '%|{$rec->id}|%'")){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * Извиква се след SetUp-а на таблицата за модела
	 */
	function loadSetupData()
	{
		$file = "eshop/csv/Payments.csv";
		$fields = array(0 => "title", 1 => "driverClass", 2 => "state");
	
		$cntObj = csv_Lib::importOnce($this, $file, $fields);
		$res = $cntObj->html;
	
		return $res;
	}
	
	
	/**
	 * Изпълнява се преди импортирването на данните
	 */
	protected static function on_BeforeImportRec($mvc, &$rec)
	{
		core_Classes::add($rec->driverClass);
		$rec->driverClass = cls::get($rec->driverClass)->getClassId();
	}
}