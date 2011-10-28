<?php
/**
 * 
 * Пакет от отстъпки по ценови групи към дата
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Отстъпки
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Discounts extends core_Master
{
	var $title = 'Отстъпки';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools,
                     catpr_Wrapper, plg_Sorting';
    
    var $details = 'catpr_Discounts_Details';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, name';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,catpr,broker';
    
    var $canList = 'admin,catpr,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,catpr';
	
    var $cssClass = 'document';
    
    function description()
	{
		$this->FLD('name', 'varchar', 'input,caption=Наименование');
	}
	
	/**
	 * @param core_Manager $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if ($action == 'edit' && !$mvc->haveRightFor('delete', $rec, $userId)) {
    		$requiredRoles = 'no_one';
    	}
    }
		
	function on_AfterPrepareEditForm($mvc, $data)
	{
		$form = $data->form;
		
		$paramsModel  = 'catpr_Pricegroups';
		$paramsKey    = 'priceGroupId';
		$detailsModel = 'catpr_Discounts_Details';
		$detailsValue = 'discount';
		
		/* @var $detailsMgr core_Detail */
		$detailsMgr = &cls::get($detailsModel);
		
		/* @var $paramsMgr core_Manager */
		$paramsMgr  = &cls::get($paramsModel);
		
		/* @var $paramsQuery core_Query */
		$paramsQuery = $paramsMgr->getQuery();
		
		expect(is_a($detailsMgr, 'core_Detail'));
		
		$valueType = $detailsMgr->getField($detailsValue)->type;
		
		while ($paramRec = $paramsQuery->fetch()) {
			$id = $val = NULL;
			if ($form->rec->id) {
				$detailRec = $detailsMgr->fetch("#{$detailsMgr->masterKey} = {$form->rec->id} AND #{$paramsKey} = {$paramRec->id}");
				$id = $detailRec->id;
				$val = $detailRec->discount;
			}
			$form->FLD("value_{$paramRec->id}", $valueType, "input,caption=Отстъпки->{$paramRec->name},value={$val}");
			$form->FLD("id_{$paramRec->id}", "key(mvc={$detailsMgr->className})", "input=hidden,value={$id}");
		}

		if ($form->rec->id) {
			$form->title = 'Редактиране на пакет |*"' . $form->rec->name . '"';
		} else {
			$form->title = 'Нов пакет отстъпки';
		}
	}
	
	
	function on_AfterSave($mvc, &$id, $rec)
	{
		/* @var $priceGroupQuery core_Query */
		$priceGroupQuery = catpr_Pricegroups::getQuery();
		
		while ($priceGroupRec = $priceGroupQuery->fetch()) {
			$detailRec = (object)array(
				'id'           => $rec->{"id_{$priceGroupRec->id}"},
				'discountId'   => $rec->id,
				'priceGroupId' => $priceGroupRec->id,
				'discount'     => $rec->{"value_{$priceGroupRec->id}"}
			);
			
			catpr_Discounts_Details::save($detailRec);
		}
	}
	
	
	function on_AfterDelete($mvc)
	{
		
	}
	
	/**
	 * Процента в пакет отстъпки, дадена за ценова група продукти към дата
	 *
	 * @param int $id ИД на пакета отстъпки - key(mvc=catpr_Discounts)
	 * @param int $priceGroupId ИД на ценова група продукти key(mvc=catpr_Pricegroups)
	 * @param string $date
	 * @return double число между 0 и 1, определящо отстъпката при зададените условия.
	 */
	static function getDiscount($id, $priceGroupId)
	{
		$discount = catpr_Discounts_Details::fetchField("#discountId = {$id} AND #priceGroupId = {$priceGroupId}", 'discount');
		$discount = (double)$discount;
		
		return $discount;
	}
}