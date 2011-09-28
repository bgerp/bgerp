<?php
/**
 * Мениджър на приходи и разходи
 *
 * @category   BGERP
 * @package    budget
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Приходи и Разходи
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class budget_IncomeExpenses extends core_Manager
{
	/**
	 *  @todo Чака за документация...
	 */
	var $title = 'Приходи и Разходи';


	/**
	 *  @todo Чака за документация...
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
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