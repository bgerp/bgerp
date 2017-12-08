<?php



/**
 * Клас 'rack_plg_Document'
 * Плъгин за връзка между експедиционни документи и палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_plg_Document extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		// Задаване на дефолти
		expect($mvc instanceof core_Detail);
		setIfNot($mvc->masterStoreFld, 'storeId');
		setIfNot($mvc->productFieldName, 'productId');
	}
	
	
	/**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
		if(!count($data->rows)) return;
		
		// Само за 'Заявки'
		if($data->masterData->rec->state != 'pending') return;
		$storeId = $data->masterData->rec->{$mvc->masterStoreFld};
		
		// За всеки запис
		foreach ($data->rows as $id => &$row){
			$rec = $data->recs[$id];
			
			// Ако може да филтрира палетите
			if($mvc->haveRightFor('filterpallets', (object)array('storeId' => $storeId, 'productId' => $rec->{$mvc->productFieldName}))){
				core_RowToolbar::createIfNotExists($row->_rowTools);
				$url = array($mvc, 'redirectToRackPallets', 'storeId' => $storeId, 'productId' => $rec->{$mvc->productFieldName});
				$row->_rowTools->addLink('Палет', $url, array('ef_icon' => "img/16/pallet1.png", 'title' => "Избор на палет"));
			}
		}
	}
	
	
	/**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
		// Екшън форсиращ избрания склад и редиректващ към стелажите
    	if($action == 'redirecttorackpallets'){
			expect($storeId = core_Request::get('storeId', 'int'));
			$mvc->requireRightFor('filterpallets', (object)array('storeId' => $storeId));
			expect($productId = core_Request::get('productId', 'int'));
			
			store_Stores::selectCurrent($storeId);
			$productId = rack_Products::fetchField("#storeId = {$storeId} AND #productId = {$productId}");
			redirect(array('rack_Pallets', 'list', 'productId' => $productId));
		}
	}
	
	
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	// Проверки на правата за филтриране на палетите
    	if($action == 'filterpallets' && isset($rec)){
    		if(!store_Stores::haveRightFor('select', $rec->storeId)){
    			$requiredRoles = 'no_one';
    		} elseif(isset($rec->productId) && !rack_Products::fetchField("#storeId = {$rec->storeId} AND #productId = {$rec->productId}")){
    			$requiredRoles = 'no_one';
    		} else {
    			$requiredRoles = rack_Pallets::getRequiredRoles('list');
    		}
    	}
    }
}