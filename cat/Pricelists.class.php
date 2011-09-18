<?php
/**
 * 
 * Ценоразписи за продуктите от каталога
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class cat_Pricelists extends core_Master
{
	var $title = 'Ценоразписи';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     cat_Wrapper, plg_Sorting';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,name';
    
    var $details = 'cat_Pricelists_Details';
    
    
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
    var $canEdit = 'admin,cat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,cat,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,cat,broker';
    
    var $canList = 'admin,cat,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,cat';
	
    
    function description()
	{
		$this->FLD('name', 'varchar', 'input,caption=Име');
	}
}