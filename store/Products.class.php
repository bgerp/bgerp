<?php
/**
 * Продукти
 */
class store_Products extends core_Manager
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf,acc_RegisterIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Продукти';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, 
                     acc_plg_Registry, store_Wrapper, plg_Selected';
    
    
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
    var $listFields = 'name, key, group, comment, onFloor, onPallets,
                       quantity, weight, density, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    function description()
    {
        $this->FLD('name',      'varchar(255)',                             'caption=Име');
        $this->FLD('key',       'varchar(255)',                             'caption=Ключ');
        $this->FLD('group',     'key(mvc=store_ProductGroups,select=name)', 'caption=Група');
        $this->FLD('comment',   'text',                                     'caption=Коментар');
        $this->FLD('onFloor',   'int',                                      'caption=Наличност->На пода');
        $this->FLD('onPallets', 'int',                                      'caption=Наличност->На палети');
        $this->FLD('quantity',  'int',                                      'caption=Наличност->Общо');
        $this->FLD('weight',    'double(decimals=2)',                       'caption=Тегло');
        $this->FLD('density',   'double(decimals=2)',                       'caption=Плътност');        
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
    
    


    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec  = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId)); 
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */        
    
    
}