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
class store_Stores extends core_Manager
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf,acc_RegisterIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Складове';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, acc_RegisterPlg, store_Wrapper';
    
    
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