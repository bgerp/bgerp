<?php
/**
 * Мениджър на групи от дълготрайни активи
 *
 * @category   BGERP
 * @package    accda
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      ДА Групи
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class accda_Groups extends core_Manager
{
	/**
	 *  @todo Чака за документация...
	 */
	var $menuPage = 'Счетоводство';


	/**
	 *  @todo Чака за документация...
	 */
	var $title = 'ДА Групи';


	/**
	 *  @todo Чака за документация...
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
					accda_Wrapper';


	/**
	 * Права
	 */
	var $canRead = 'admin,accda';


	/**
	 *  @todo Чака за документация...
	 */
	var $canEdit = 'admin,accda';


	/**
	 *  @todo Чака за документация...
	 */
	var $canAdd = 'admin,accda';


	/**
	 *  @todo Чака за документация...
	 */
	var $canView = 'admin,accda';


	/**
	 *  @todo Чака за документация...
	 */
	var $canDelete = 'admin,accda';


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