<?php



/**
 * Плъгин за кеширане на делтата при продажба при контиране на документ
 * 
 * 
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      https://github.com/bgerp/ef/issues/6
 */
class sales_plg_CalcPriceDelta extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->detailSellPriceFld, 'price');
		setIfNot($mvc->detailQuantityFld, 'quantity');
		setIfNot($mvc->detailProductFld, 'productId');
		setIfNot($mvc->detailPackagingFld, 'packagingId');
	}
	
	
	/**
	 * Функция, която се извиква след активирането на документа
	 */
	public static function on_AfterActivation($mvc, &$rec)
	{
		$save = array();
		
		if($mvc instanceof sales_Sales){
			
			// Ако е продажба и не е експедирано, не се записва нищо
			$actions = type_Set::toArray($rec->contoActions);
			if(!isset($actions['ship'])) return;
		} else {
			
			// Ако не е продажба но документа НЕ е в нишка на продажба, не се записва нищо
			$threadId = (isset($rec->threadId)) ? $rec->threadId : $mvc->fetchField($rec->id, 'threadId');
			$firstDoc = doc_Threads::getFirstDocument($threadId);
			if(!$firstDoc->isInstanceOf('sales_Sales')) return;
		}
		
		$folderId = (isset($rec->folderId)) ? $rec->folderId : $mvc->fetchField($rec->id, 'folderId');
		
		// По коя политика ще се изчислява делтата
		$Cover = doc_Folders::getCover($folderId);
		$primeCostListId = cond_Parameters::getParameter($Cover->getClassId(), $Cover->that, 'deltaList');
		if(empty($primeCostListId)){
			$primeCostListId = price_ListRules::PRICE_LIST_COST;
		}
		
		// Намиране на детайлите
		$Detail = cls::get($mvc->mainDetail);
		$detailClassId = $Detail->getClassId();
		$query = $Detail->getQuery();
		$query->where("#{$Detail->masterKey} = {$rec->id}");
		
		$valior =  $rec->{$mvc->valiorFld};
		while($dRec = $query->fetch()){
			
			// Изчисляване на цената по политика
			$primeCost = price_ListRules::getPrice($primeCostListId, $dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $valior);
				
			$r = (object)array('valior'        => $valior,
							   'detailClassId' => $detailClassId,
					           'detailRecId'   => $dRec->id,
					           'quantity'      => $dRec->{$mvc->detailQuantityFld},
					           'productId'     => $dRec->{$mvc->detailProductFld},
					           'sellCost'      => $dRec->{$mvc->detailSellPriceFld},
					           'primeCost'     => $primeCost);
				
			$save[] = $r;
		}
		
		// Запис
		cls::get('sales_PrimeCostByDocument')->saveArray($save);
	}
	
	
	/**
	 * Преди запис на документ, изчислява стойността на полето `isContable`
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $rec
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		// Ако документа е спрян или оттеглен изтриват се кешираните записи
		if(isset($rec->id) && ($rec->state == 'rejected' || $rec->state == 'stopped')){
			sales_PrimeCostByDocument::removeByDoc($mvc, $rec->id);
		}
	}
}