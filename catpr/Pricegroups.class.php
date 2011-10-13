<?php
/**
 * 
 * Ценови групи на продуктите от каталога
 * 
 * Ценовите групи са средство за обединение на продукти (@see cat_Products) споделящи общи 
 * правила за ценообразуване.
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Ценови групи
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Pricegroups extends core_Manager
{
	var $title = 'Ценови групи';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     catpr_Wrapper, plg_Sorting, plg_Rejected';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,name, baseDiscount';
    
    
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
		$this->FLD('name', 'varchar', 'input,caption=Наименование');
		$this->FLD('baseDiscount', 'percent', 'input,caption=Базова Отстъпка');
	}
}