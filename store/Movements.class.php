<?php
/**
 * 
 * Движения
 */
class store_Movements extends core_Manager
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf,acc_RegisterIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Движения';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, 
                     acc_plg_Registry, store_Wrapper';
    
    
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
    var $listFields = 'storeId, kind, quantity, units, 
                       possitionOld, possition, processBy, startOn, finishOn, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад');
        $this->FLD('kind',    'enum(upload=Качи,
                                    download=Свали,
                                    take=Вземи,
                                    move=Мести)',        'caption=Действие');
        $this->FLD('palletId', 'key(mvc=store_Pallets, select=id)', 'caption=Палет,notNull');
        $this->FLD('quantity', 'int',                    'caption=Количество');
        $this->FLD('units',    'key(mvc=common_Units, select=shortName)', 'caption=Мярка');
        $this->FLD('possitionOld', 'varchar(255)',       'caption=Позиция->Стара');
        $this->FLD('possition',    'varchar(255)',       'caption=Позиция->Нова');
        $this->FLD('processBy',    'key(mvc=core_Users, select=names)', 'caption=Изпълнител');
        $this->FLD('startOn',      'date',               'caption=Дата->Започване');
        $this->FLD('finishOn',     'date',               'caption=Дата->Приключване');
    }
	
}