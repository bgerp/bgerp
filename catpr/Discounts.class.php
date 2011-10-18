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