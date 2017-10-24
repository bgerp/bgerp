<?php



/**
 * Клас 'colab_plg_FolderToPartners'
 *
 * Плъгин за споделяне на папка към партньори.
 * Добавя към корицата на обекта в таба за права, секция за споделяне със партньори
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class colab_plg_FolderToPartners extends core_Plugin
{
	
	
	/**
	 * След подготовка на таба със правата
	 */
	public static function on_AfterPrepareRights($mvc, $res, $data)
	{
		colab_FolderToPartners::preparePartners($data);
	}
	
	
	/**
	 * След рендиране на таба със правата
	 */
	public static function on_AfterRenderRights($mvc, &$tpl, $data)
	{
		colab_FolderToPartners::renderPartners($data, $tpl);
	}
}