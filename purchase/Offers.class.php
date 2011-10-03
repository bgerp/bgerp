<?php
/**
 * Мениджър на оферти за покупки
 *
 * @category   BGERP
 * @package    purchase
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Оферти за покупки
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class purchase_Offers extends core_Manager
{
	/**
	 *  @todo Чака за документация...
	 */
	var $title = 'Оферти за покупки';


	/**
	 *  @todo Чака за документация...
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
					purchase_Wrapper';


	/**
	 * Права
	 */
	var $canRead = 'admin,purchase';


	/**
	 *  @todo Чака за документация...
	 */
	var $canEdit = 'admin,purchase';


	/**
	 *  @todo Чака за документация...
	 */
	var $canAdd = 'admin,purchase';


	/**
	 *  @todo Чака за документация...
	 */
	var $canView = 'admin,purchase';


	/**
	 *  @todo Чака за документация...
	 */
	var $canDelete = 'admin,purchase';


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