<?php
/**
 * Стелажи
 */
class store_Strategy extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Стратегии за подреждане на стелажите';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, store_Wrapper';
    
    
    var $lastUsedKeys = 'storeId';

    /**
     * Права
     */
    var $canRead = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canSingle = 'admin,store';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 10;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, name, parameter';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';

    
    function description()
    {
        $this->FLD('name',       'varchar(255)', 'caption=Заглавие');
        $this->FLD('parameter',  'int',          'caption=Параметър');
    }
    
    
    function getAllowedProductGroups()
    {
    }
    
    function checkProductGroupsToAllowedProductGroups()
    {
    }    
    
    function checkIfExistsThisProductInTheStore()
    {
    }
    
}