<?php



/**
 * Клас 'planning_StoreTransferDocument' - Клас за наследяване на документи извършващи движения
 * между склада и производството
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class planning_StoreTransferDocument extends deals_ManifactureMaster
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		setIfNot($mvc->storeFieldName, 'storeId');
		setIfNot($mvc->taskActionLoad, 'production');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		if($data->action == 'clone') return;
		$form = &$data->form;
		$rec = $form->rec;
	
		if(isset($rec->id)) return;
		$form->setField($mvc->storeFieldName, "removeAndRefreshForm=quantity");
		
		// Ако има избран склад
		if(isset($rec->{$mvc->storeFieldName})){
			$details = $mvc->getProductsFromTasks($rec);
			$Detail = cls::get($mvc->mainDetail);
			
			// Всички документи в нишката, които са активни
			$cQuery = doc_Containers::getQuery();
			$cQuery->where("#threadId = {$rec->threadId} AND #state = 'active'"); 
			$containers = arr::extractValuesFromArray($cQuery->fetchAll(), 'id');
			
			// За всеки подаден дефолтен артикул
			foreach ($details as $dRec){
				$caption = cat_Products::getTitleById($dRec->productId);
				$caption .= " / " . cat_UoM::getShortName($dRec->packagingId);
				$caption= str_replace(',', ' ', $caption);
				$subCaption = 'К-во';
				$batch = '';
				
				$selectedByNow = NULL;
				if(core_Packs::isInstalled('batch')){
					$Def = batch_Defs::getBatchDef($dRec->productId);
					
					// Ако има партидност и тя е от определен тип
					if(is_object($Def) && $Def instanceof batch_definitions_Varchar){
						
						// Стойноста на партидата ще е задачата
						$dRec->batch = planning_Tasks::getBatchName($dRec->taskId);
						$subCaption = $dRec->batch;
						
						// Колко е изпълнено досега
						$bQuery = batch_BatchesInDocuments::getQuery();
						$bQuery->XPR('sumQuantity', 'double', 'SUM(#quantity)');
						$bQuery->in("containerId", $containers);
						$bQuery->where("#productId = {$dRec->productId} AND #batch = '{$dRec->batch}' AND #storeId = {$rec->{$mvc->storeFieldName}} AND #operation = '{$Detail->batchMovementDocument}'");
						$bQuery->show('sumQuantity');
						$selectedByNow = $bQuery->fetch()->sumQuantity;
					}
				}
	
				// Ако няма партиди гледа се колко е изпълнено досега
				if(empty($selectedByNow)){
					$dQuery = $Detail->getQuery();
					$dQuery->XPR('sumQuantity', 'double', 'SUM(#quantity)');
					$dQuery->EXT('storeId', $mvc->className, "externalName={$mvc->storeFieldName},externalKey={$Detail->masterKey}");
					$dQuery->EXT('state', $mvc->className, "externalName=state,externalKey={$Detail->masterKey}");
					$dQuery->EXT('containerId', $mvc->className, "externalName=containerId,externalKey={$Detail->masterKey}");
					$dQuery->in("containerId", $containers);
					$dQuery->where("#state = 'active'");
					$dQuery->where("#productId = {$dRec->productId} AND #storeId = {$rec->{$mvc->storeFieldName}}");
					$dQuery->show('sumQuantity');
					$selectedByNow = $dQuery->fetch()->sumQuantity;
				}
				
				// Дефолтното к-во се приспада
				$defaultQuantity = ($dRec->quantity - $selectedByNow) / $dRec->quantityInPack;
				
				// Показване на полетата без партиди
				$form->FLD("quantity|{$batch}|{$dRec->id}|", "double(Min=0)","input,caption={$caption}->{$subCaption}");
				if($defaultQuantity > 0){
					$form->setDefault("quantity|{$batch}|{$dRec->id}|", $defaultQuantity);
				}
				
				$rec->detailsDef["quantity|{$batch}|{$dRec->id}|"] = $dRec;
			}
		}
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	protected static function on_AfterCreate($mvc, $rec)
	{
		if(is_array($rec->detailsDef)){
			$Detail = cls::get($mvc->mainDetail);
	
			// За всеки детайл
			foreach ($rec->detailsDef as $key => $dRec){
				
				// Ако има въведено количество записва се
				if(!empty($rec->{$key})){
					unset($dRec->id);
					$dRec->quantity = $rec->{$key} * $dRec->quantityInPack;
					$dRec->noteId = $rec->id;
					$dRec->isEdited = TRUE;
					$Detail->save($dRec);
				}
			}
		}
	}
	
	
	/**
	 * Връща артикулите, които са вложени/произведени по задачи към документа
	 * 
	 * @param stdClass $rec
	 * @return array
	 * 		o productId      - ид на артикула
	 * 		o quantity       - к-во в основна мярка
	 * 		o quantityInPack - к-во в опаковка
	 * 		o packagingId    - ид на опаковка
	 * 		o taskId         - ид на операция
	 */
	public function getProductsFromTasks($rec)
	{
		$rec = $this->fetchRec($rec);
		$originId = doc_Threads::getFirstContainerId($rec->threadId);
			
		$dQuery = planning_ProductionTaskProducts::getQuery();
		$dQuery->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
		$dQuery->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
		$dQuery->XPR('quantity', 'double', '#totalQuantity');
		$dQuery->where("#originId = {$originId} AND #canConvert = 'yes' AND #storeId = {$rec->storeId} AND #totalQuantity != 0 AND #type = '{$this->taskActionLoad}'");
		$dQuery->show('productId,quantityInPack,packagingId,taskId,quantity');
	
		return $dQuery->fetchAll();
	}
}