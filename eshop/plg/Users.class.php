<?php



/**
 * Клас 'eshop_plg_Users'
 *
 * Разширяващ функциононалността на core_Users свързана с ешопа
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
		$Carts = cls::get('eshop_Carts');
		
		// За всеки домейн
		$brid = log_Browsers::getBrid();
		$dQuery = cms_Domains::getQuery();
		while($dRec = $dQuery->fetch()){
			
			// Проверка има ли чернова на количка без потребител от същия брид, ако има присвоява се на логнатия потребител
			if($dCart = eshop_Carts::fetch("#domainId = {$dRec->id} AND #state = 'draft' AND #brid = '{$brid}' AND #userId IS NULL")){
				
				// Артикулите в текущата кошница
				$arr = array();
				$dQuery = eshop_CartDetails::getQuery();
				$dQuery->where("#cartId = {$dCart->id}");
				while($cRec = $dQuery->fetch()){
					$arr["{$cRec->productId}|{$cRec->packagingId}"] = $cRec;
				}
				
				// Ако логнатия потребител има чернова количка
				if($exId = eshop_Carts::force($dRec->id, $userRec->id, FALSE)){
					
					// Извличат се артикулит от нея
					$eQuery = eshop_CartDetails::getQuery();
					$eQuery->where("#cartId = {$exId}");
					while($eRec = $eQuery->fetch()){
						
						// Тези, които не присъстват в новата се прехвърлят в нея
						if(!array_key_exists("{$eRec->productId}|{$eRec->packagingId}", $arr)){
							$eRec->cartId = $dCart->id;
							eshop_CartDetails::save($eRec, 'cartId');
						}
					}
					
					// Старата количка се изтрива
					eshop_Carts::delete($exId);
				}
				
				// Последната количка се присвоява на потребителя
				$dCart->userId = $userRec->id;
				$Carts->save($dCart);
				$Carts->updateMaster($dCart);
			}
		}
	}
}