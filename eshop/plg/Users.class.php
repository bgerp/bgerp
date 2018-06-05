<?php



/**
 * Клас 'eshop_plg_Users'
 *
 * Разширяващ функциононалноста на core_Users свързана с ешопа
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_plg_Users extends core_Plugin
{
	
	
	/**
	 * Прихващаме всяко логване в системата
	 */
	public static function on_AfterLogin($mvc, $userRec, $inputs, $refresh)
	{
		if(!core_Packs::isInstalled('colab')) return;
		if(!core_Users::isContractor($userRec)) return;
		
		// За всеки домейн
		$brid = log_Browsers::getBrid();
		$dQuery = cms_Domains::getQuery();
		while($dRec = $dQuery->fetch()){
			
			// Ако потребителя има количка, домейна се пропуска
			if(eshop_Carts::force($dRec->id, $userRec->id, FALSE)) continue;
			
			// Проверка има ли чернова на количка без потребител от същия брид, ако има присвоява се на логнатия потребител
			if($dCart = eshop_Carts::fetch("#domainId = {$dRec->id} AND #state = 'draft' AND #brid = '{$brid}' AND #userId IS NULL")){
				$dCart->userId = $userRec->id;
				eshop_Carts::save($dCart, 'userId');
			}
		}
	}
}