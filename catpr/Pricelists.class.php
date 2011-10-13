<?php
/**
 * 
 * Ценоразписи за продукти от каталога
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Ценоразписи
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Pricelists extends core_Master
{
	var $title = 'Ценоразписи';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools,
                     catpr_Wrapper, plg_AlignDecimals';
    
    var $details = 'catpr_Pricelists_Details';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, date, discountId, currencyId, vat';
    
    
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
	
    
    function description()
	{
		$this->FLD('date', 'date', 'mandatory,input,caption=Към Дата');
		$this->FLD('discountId', 'key(mvc=catpr_Discounts,select=name,allowEmpty)', 'input,caption=По Отстъпка');
		$this->FLD('currencyId', 'key(mvc=currency_Currencies,select=name,allowEmpty)', 'input,caption=Валута');
		$this->FLD('vat', 'percent', 'input,caption=ДДС');
	}
	
	
	function on_AfterSave($mvc, &$id, $rec)
	{
		// Изтриване на (евентуални) стари изчисления
		catpr_Pricelists_Details::delete("#pricelistId = {$rec->id}");
		
		$productsQuery = cat_Products::getQuery();
		$productsQuery->show('id');
		
		$ProductIntf = cls::getInterface('cat_ProductAccRegIntf', 'cat_Products');
		
		while ($prodRec = $productsQuery->fetch()) {
			$costRec = catpr_Costs::getProductCosts($prodRec->id, $rec->date);
			if (count($costRec) == 0) {
				continue;
			}
			$costRec = reset($costRec);
			
			$price = $ProductIntf->getProductPrice($prodRec->id, $rec->date, $rec->discountId);
			
			if (!isset($price)) {
				// Ако цената на продукта не е дефинирана (най-вероятно няма себестойност), той
				// не влиза в ценоразпис.
				continue;
			}
			
			catpr_Pricelists_Details::save(
				(object)array(
					'pricelistId'  => $rec->id,
					'priceGroupId' => $costRec->priceGroupId,
					'productId'    => $prodRec->id,
					'price'        => $price,
					'state'        => 'draft',
				)
			);
		}
	}
}