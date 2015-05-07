<?php



/**
 * Клас 'findeals_plg_GenerateAllocatedExpenses'
 * Плъгин даващ възможност на даден документ да генерира документ за корекции на стойностти
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_plg_GenerateAllocatedExpenses extends core_Plugin
{
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->declareInterface('findeals_AllocatedExpensesSourceIntf');
	}
	
	
	/**
	 * След подготовка на тулбара на единичен изглед
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		if($data->rec->state == 'active'){
			if(findeals_AllocatedExpenses::haveRightFor('add', (object)array('originId' => $data->rec->containerId)));
			$data->toolbar->addBtn('Разпределяне', array('findeals_AllocatedExpenses', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png,title=Създаване на документ за разпределяне на разходи');
		}
	}
	
	
	/**
	 * Върху кои артикули ще се коригират стойностите
	 *
	 * @param int $id - ид на обекта
	 * @return array $products - масис с ид-та на продукти
	 */
	public static function on_AfterGetProductsForAllocation($mvc, &$res, $id)
	{
		if(!$res){
			
			$Cls = cls::get($mvc->mainDetail);
			
			$products = array();
				
			$dQuery = $Cls->getQuery();
			$dQuery->where("#{$Cls->masterKey} = {$id}");
			$dQuery->show('productId');
			$dQuery->groupBy('productId');
				
			while($dRec = $dQuery->fetch()){
				$products[$dRec->productId] = $dRec->productId;
			}
			
			$res = $products;
		}
	}
	
	
	/**
	 * Дали може да се генерира документ корекция на стойностите от документа
	 *
	 * @param int $id
	 */
	public static function on_AfterCanAddAllocatedExpensesDocument($mvc, &$res, $id)
	{
		// Ако не е оказано друго, винаги може
		if(!$res){
			$res = TRUE;
		}
	}
}