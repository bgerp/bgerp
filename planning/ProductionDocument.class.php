<?php


/**
 * Клас 'planning_ProductionDocument' - базов клас за наследяване
 * на документи за засклаждане напроизведен артикул
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class planning_ProductionDocument extends deals_ManifactureMaster
{


	/**
	 * Проверка имали по нов производствен документ
	 * 
	 * @param stdClass $rec
	 */
	protected function hasNewerProductionDocument($rec)
	{
		// Проверяваме имали активен протокол за производство или 
		// бързо производство създаден след текущия документ
		foreach (array('planning_DirectProductionNote', 'planning_ProductionNotes') as $Class){
			$dQuery = $Class::getQuery();
			$dQuery->EXT('containerCreatedOn', 'doc_Containers', 'externalName=createdOn,externalKey=containerId');
			$dQuery->where("#state = 'active' AND #containerCreatedOn > '{$rec->createdOn}'");
			if($this instanceof $Class){
				$dQuery->where("#id != {$rec->id}");
			}
			$dQuery->orderBy('id', 'DESC');
			$dQuery->limit(1);
			 
			// Ако има намерен документ
			if($dQuery->fetch()) return TRUE;
		}
		
		return FALSE;
	}
	
	
	/**
	 * След подготовка на тулбара на единичен изглед
	 */
	protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		$rec = $data->rec;
		
		if($rec->state == 'active'){
			
			// Ако има по нов документ не може да се оттегля документа
			if($data->toolbar->hasBtn("btnDelete{$rec->containerId}")){
				if($mvc->hasNewerProductionDocument($rec)){
					$data->toolbar->setError(array("btnDelete{$rec->containerId}"), "Не може да бъде оттеглен, докато има по-нов производствен документ");
				}
			}
			
			// Ако има по нов документ не може да се възстановява документа
		} elseif($rec->state == 'rejected' && $rec->brState == 'active'){
			if($data->toolbar->hasBtn("btnRestore{$rec->containerId}")){
				if($mvc->hasNewerProductionDocument($rec)){
					$data->toolbar->setError(array("btnRestore{$rec->containerId}"), "Не може да бъде възстановен, докато има по-нов производствен документ");
				}
			}
		}
	}
	
	
	/**
	 * Изпълнява се преди оттеглянето на документа
	 */
	public static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		if($rec->state == 'active'){
			expect(!$mvc->hasNewerProductionDocument($rec));
		}
	}
}