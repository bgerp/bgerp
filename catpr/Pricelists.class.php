<?php
/**
 * 
 * Ценоразписи за продуктите от каталога
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class catpr_Pricelists extends core_Master
{
	var $title = 'Ценоразписи';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     catpr_Wrapper, plg_Sorting';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,name';
    
    var $details = 'catpr_Pricelists_Details';
    
    
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
		$this->FLD('name', 'varchar', 'input,caption=Име');
	}
}