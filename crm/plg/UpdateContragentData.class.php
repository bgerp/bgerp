<?php


/**
 * Плъгин записващ данни във визитката на контрагента ако не са попълнени
 * 
 * В мениджъра трябва да има параметър
 * 
 * $updateContragentdataField array(< поле в мениджъра > => < поле във визитката >)
 * 
 * Ако има стойност в < поле в мениджъра > и в < поле във визитката > няма стойност, след запис се записва стойността от
 * документа. < поле във визитката >
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_plg_UpdateContragentData extends core_Plugin
{
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
	{
		$map = $mvc::$updateContragentdataField;
		if(isset($map)){
			if(count($map)){
				if(!$rec->folderId) return;
				
				$Cover = doc_Folders::getCover($rec->folderId);
				if(!$Cover->haveInterface('doc_ContragentDataIntf')) return;
				
				$coverRec = $Cover->rec();
				$update = FALSE;
				
				// За всяка стойност
				foreach ($map as $fld => $dataFld){
					
					// Ако в записа е попълнено полето, във визитката не е и във визитката има такова поле, обновяваме го
					if($rec->{$fld} && !$coverRec->{$dataFld} && $Cover->getInstance()->getField($dataFld, FALSE)){
						$coverRec->{$dataFld} = $rec->{$fld};
						$update = TRUE;
					}
				}
					
				if($update === TRUE){
					core_Statuses::newStatus('Визитката на контрагента е обновена успешно');
				
					// Записваме оновената визитка
					$Cover->getInstance()->save($coverRec);
				}
			}
		}
	}
}