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
	 * Работен кеш
	 */
	protected $arr = array();
	
	
	/**
	 * Проверка имали по нов производствен документ
	 * 
	 * @param stdClass $rec
	 */
	protected function getNewerProductionDocumentHandle($rec)
	{
		if(isset($this->arr[$rec->id])) return $this->arr[$rec->id];
		
		$res = FALSE;
		
		// Ако е протокол за производство
		if($this instanceof planning_ProductionNotes){
			$query = planning_ProductionNoteDetails::getQuery();
			$query->where("#noteId = {$rec->id}");
			$query->show('jobId');
			while($dRec = $query->fetch()){
				
				// Ако за заданието на някой от детайлите му има по нов документ
				if($handle = $this->hasNewerProductionDocument($this, $rec, $dRec->jobId)){
					$res = $handle;
				}
			}
			
			// Ако е протокол за бързо производство
		} elseif($this instanceof planning_DirectProductionNote){
			$jobId = doc_Containers::getDocument($rec->originId)->that;
			if($handle = $this->hasNewerProductionDocument($this, $rec, $jobId)){
				$res = $handle;
			}
		}
		
		$this->arr[$rec->id] = $res;
		
		return $res;
	}
	
	
	/**
	 * Имали по нов производсвтен документ по заданието
	 * 
	 * @param core_Mvc $mvc
	 * @param int $id
	 * @param int $jobId
	 * @return string|FALSE - хендлъра на по-новия документ
	 */
	private function hasNewerProductionDocument(core_Mvc $mvc, $id, $jobId)
	{
		$rec = $mvc->fetchRec($id);
		
		// Проверяваме в протколите за бързо производство
		$dQuery = planning_DirectProductionNote::getQuery();
		$dQuery->EXT('containerCreatedOn', 'doc_Containers', 'externalName=createdOn,externalKey=containerId');
		$dQuery->where("#state = 'active' AND #containerCreatedOn > '{$rec->createdOn}'");
		
		// Имали такъв с по-нова дата към същото задание
		$jobContainerId = planning_Jobs::fetchField($jobId, 'containerId');
		$dQuery->where("#originId = {$jobContainerId}");
		if($mvc instanceof planning_DirectProductionNote){
			$dQuery->where("#id != {$rec->id}");
		}
		$dQuery->show('id');
		$dQuery->orderBy('id', 'DESC');
		$dQuery->limit(1);
		
		// Ако има намерен документ
		if($fRec = $dQuery->fetch()){
			return planning_DirectProductionNote::getHandle($fRec->id);
		}
		
		$db = new core_Db();
		if ($db->tableExists("planning_production_note_details") && ($db->tableExists("planning_production_note"))) {
			
			// Проверяваме към протоколите за производство
			$dQuery = planning_ProductionNoteDetails::getQuery();
			$dQuery->EXT('state', 'planning_ProductionNotes', 'externalName=state,externalKey=noteId');
			$dQuery->EXT('containerId', 'planning_ProductionNotes', 'externalName=containerId,externalKey=noteId');
			$dQuery->where("#state = 'active'");
			$dQuery->where("#jobId = {$jobId}");
			if($mvc instanceof planning_ProductionNotes){
				$dQuery->where("#id != {$rec->id}");
			}
			
			// Ако протокола е по-нов и има детайл към същото задание
			$dQuery->orderBy('id', 'DESC');
			while($dRec = $dQuery->fetch()){
				$cCreatedOn = doc_Containers::fetchField($dRec->containerId, 'createdOn');
				if($cCreatedOn > $rec->createdOn){
					return planning_ProductionNotes::getHandle($dRec->noteId);
				}
			}
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
				if($handle = $mvc->getNewerProductionDocumentHandle($rec)){
					$data->toolbar->setError(array("btnDelete{$rec->containerId}"), "Не може да бъде оттеглен, докато има по-нов производствен документ|* #{$handle}");
				}
			}
			
			// Ако има по нов документ не може да се възстановява документа
		} elseif($rec->state == 'rejected' && $rec->brState == 'active'){
			if($data->toolbar->hasBtn("btnRestore{$rec->containerId}")){
				if($handle = $mvc->getNewerProductionDocumentHandle($rec)){
					$data->toolbar->setError(array("btnRestore{$rec->containerId}"), "Не може да бъде възстановен, докато има по-нов производствен документ|* #{$handle}");
				}
			}
		}
	}
	
	
	/**
	 * Изпълнява се преди оттеглянето на документа
	 */
	public static function on_BeforeReject($mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		if($rec->state == 'active'){
			expect(!$mvc->getNewerProductionDocumentHandle($rec));
		}
	}
	
	
	/**
	 * Проверка дали нов документ може да бъде добавен в
	 * посочената папка като начало на нишка
	 *
	 * @param $folderId int ид на папката
	 */
	public static function canAddToFolder($folderId)
	{
		return FALSE;
	}
	
	
	/**
	 * Проверка дали нов документ може да бъде добавен в
	 * посочената нишка
	 *
	 * @param int $threadId key(mvc=doc_Threads)
	 * @return boolean
	 */
	public static function canAddToThread($threadId)
	{
		// Може да добавяме или към нишка с начало задание
		$firstDoc = doc_Threads::getFirstDocument($threadId);
		if($firstDoc->isInstanceOf('planning_Jobs')){
	
			return TRUE;
		}
		 
		$folderId = doc_Threads::fetchField($threadId, 'folderId');
		$folderClass = doc_Folders::fetchCoverClassName($folderId);
	
		// или към нишка в папка на склад
		return cls::haveInterface('store_AccRegIntf', $folderClass);
	}
}