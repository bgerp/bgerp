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
	public $title = 'Артикули за влагане';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Артикул за влагане';
	
	
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
	public $listFields = 'groupId,productId,packagingId,indTime';
	
	
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
		
		$this->setDbUnique('groupId,productId');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		 
		$productOptions = cat_Products::getByProperty('canConvert', 'canStore');
		$form->setOptions('productId', array('' => '') + $productOptions);
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = $form->rec;
		
		if(isset($rec->productId)){
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
	}
	
	
	/**
	 * Преди подготовката на полетата за листовия изглед
	 */
	public static function on_AfterPrepareListFields($mvc, &$res, &$data)
	{
		if(isset($data->masterMvc)){
			unset($data->listFields['groupId']);
		}
	}
}