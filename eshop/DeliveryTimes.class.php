<?php



/**
 * Времена на доставка в онлайн магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class eshop_DeliveryTimes extends core_Manager {
    
	
	/**
	 * Заглавие
	 */
	public $title = "Времена на доставка в онлайн магазина";
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Начин на плащане';
	
	
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
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar', 'caption=Наименование');
	}
}