<?php
/**
 * Мениджър на справки по подразделения / дейности
 *
 * @category   BGERP
 * @package    budget
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      По подразделения / Дейности
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class budget_Reports extends core_Manager
{
	/**
	 *  @todo Чака за документация...
	 */
	var $title = 'По подразделения / Дейности';


	/**
	 *  @todo Чака за документация...
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
					budget_Wrapper';


	/**
	 * Права
	 */
	var $canRead = 'admin,budget';


	/**
	 *  @todo Чака за документация...
	 */
	var $canEdit = 'admin,budget';


	/**
	 *  @todo Чака за документация...
	 */
	var $canAdd = 'admin,budget';


	/**
	 *  @todo Чака за документация...
	 */
	var $canView = 'admin,budget';


	/**
	 *  @todo Чака за документация...
	 */
	var $canDelete = 'admin,budget';


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