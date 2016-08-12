<?php



/**
 * Плъгин за документи към, които може да се разпределят разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_ExpenseAllocation extends core_Plugin
{
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription($mvc)
	{
		// Дефолтни имена на полетата от модела
		setIfNot($mvc->packQuantityFld, 'packQuantity');
		setIfNot($mvc->packagingIdFld, 'packagingId');
		setIfNot($mvc->quantityInPackFld, 'quantityInPack');
		setIfNot($mvc->productIdFld, 'productId');
		setIfNot($mvc->quantityFld, 'quantity');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
		if(isset($rec->id)) return;
		$firstDocument = doc_Threads::getFirstDocument($data->masterRec->threadId);
		if(!$firstDocument->isInstanceOf('purchase_Purchases') && !$firstDocument->isInstanceOf('findeals_Deals')) return;
		
		$form->FNC('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=productId,caption=Разход за,removeAndRefreshForm=allocationBy');
		$form->FNC('allocationBy', 'enum(no=Няма,value=По стойност,quantity=По количество,weight=По тегло,volume=По обем)', 'input=none,caption=Разпределяне,after=expenseItemId');
		
		if(isset($rec->productId)){
			$pRec = cat_Products::fetch($rec->productId, 'canConvert,fixedAsset,canStore');
			
			if($pRec->canStore == 'no' && $pRec->fixedAsset == 'no' && $pRec->canConvert == 'no'){
				if(acc_Lists::getItemsCountInList('costObjects') > 1){
					$form->setField('expenseItemId', 'input');
					if($exItemId = Request::get('expenseItemId', 'int')){
						$form->setDefault('expenseItemId', $exItemId);
					}
					
					if(isset($rec->expenseItemId)){
						$itemClassId = acc_Items::fetchField($rec->expenseItemId, 'classId');
						if($itemClassId == sales_Sales::getClassId() || $itemClassId == purchase_Purchases::getClassId()){
							$form->setField('allocationBy', 'input');
							$form->setDefault('allocationBy', 'no');
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = $form->rec;
		
		if($form->isSubmitted()){
			if(isset($rec->id)){
				$allocated = acc_CostAllocations::getAllocatedInDocument($mvc->getClassId(), $rec->id);
				$inputQuantity = $rec->{$mvc->quantityInPackFld} * $rec->{$mvc->packQuantityFld};
				
				if($inputQuantity < $allocated){
					$allocatedVerbal = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($allocated);
					$uomName = cat_UoM::getShortName(key(cat_Products::getPacks($rec->productId)));
					$form->setError($mvc->packQuantityFld, "Въведеното к-во е по-малко от к-то разпределеното по разходи|* <b>{$allocatedVerbal}</b> |{$uomName}|*");
				}
			}
		}
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		if(isset($rec->expenseItemId)){
			$containerId = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'containerId');
		
			$costRec = (object)array('detailClassId' => $mvc->getClassId(),
									 'expenseItemId' => $rec->expenseItemId,
									 'allocationBy'  => $rec->allocationBy,
								     'detailRecId'   => $rec->id,
					                 'productId'     => $rec->{$mvc->productIdFld},
					                 'quantity'      => $rec->{$mvc->quantityFld},
					                 'containerId'   => $containerId);
			
			acc_CostAllocations::save($costRec);
		}
	}
	
	
	public static function on_AfterGetMaxQuantity($mvc, &$res, $id)
	{
		if(!$res){
			$res = $mvc->fetchField($id, $mvc->quantityFld);
		}
	}
	
	
	/**
	 * След изтриване на запис
	 */
	public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
	{
		foreach ($query->getDeletedRecs() as $id => $rec) {
			acc_CostAllocations::delete("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$id}");
		}
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		$rows = &$data->rows;
		if(!count($rows)) return;
		
		foreach ($rows as $id => $row){
			$rec = $data->recs[$id];
			$row->productId .= acc_CostAllocations::getAllocatedExpenses($mvc, $rec->id, $data->masterData->rec->containerId, $rec->{$mvc->productIdFld}, $rec->{$mvc->packagingIdFld}, $rec->{$mvc->quantityInPackFld});
		}
	}
}