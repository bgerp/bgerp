<?php



/**
 * Плъгин за разширяване на външната част на е-магазина
 * 
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class eshop_plg_External extends core_Plugin
{
	
	
	/**
	 * След подготовка на страницата за външната част
	 */
	public static function on_AfterPrepareExternalPage($mvc, &$res)
	{
		//if(haveRole('debug')){
			$action = Request::get('Act');
			$ctr = Request::get('Ctr');
			if(!($action == 'view' && $ctr == 'eshop_Carts')){
				$res->replace(eshop_Carts::getStatus(), 'USERCART');
			}
		//}
		
		$res->push(('eshop/js/Scripts.js'), 'JS');
		jquery_Jquery::run($res, "eshopActions();");
		jquery_Jquery::run($res, "smartCenter();");
		jquery_Jquery::runAfterAjax($res, "smartCenter()");
	}
}