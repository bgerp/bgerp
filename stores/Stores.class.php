<?php
/**
 * 
 * Складове
 * 
 * Мениджър на складове
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 * @TODO Това е само примерна реализация за тестване на продажбите. Да се вземе реализацията
 * от bagdealing.
 * 
 */
class stores_Stores extends core_Manager implements intf_Store
{
    /**
     *  @todo Чака за документация...
     */
    var $menuPage = 'Счетоводство';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Сметкоплан';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_AccRegistry, common_Wrapper';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 300;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, name, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
    	$this->FLD('name', 'varchar(128)', 'caption=Име');
    }
	
    
    /**
     * Имплементация на @see intf_Register::getAccItemRec()
     * 
     */
    function getAccItemRec($rec)
    {
    	return (object)array(
    		'title' => $rec->name
    	);
    }
    
}