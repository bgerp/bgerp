<?php
/**
 * Мениджър на заявки за покупки
 *
 * @category   BGERP
 * @package    sales
 * @author     Милен Георгиев
 * @title      Заявки за покупки
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 *
 */
class sales_Deals extends core_Manager
{
	/**
	 *  @todo Чака за документация...
	 */
	var $title = 'Сделки за продажби';


	/**
	 *  @todo Чака за документация...
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, sales_Wrapper';


	/**
	 * Права
	 */
	var $canRead = 'admin,sales';


	/**
	 *  @todo Чака за документация...
	 */
	var $canEdit = 'admin,sales';


	/**
	 *  @todo Чака за документация...
	 */
	var $canAdd = 'admin,sales';


	/**
	 *  @todo Чака за документация...
	 */
	var $canView = 'admin,sales';


	/**
	 *  @todo Чака за документация...
	 */
	var $canDelete = 'admin,sales';


	/**
	 *  @todo Чака за документация...
	 */
	var $listFields = 'tools=Пулт';


	/**
	 *  @todo Чака за документация...
	 */
	var $rowToolsField = 'tools';
	

	function description()
	{
	}
}