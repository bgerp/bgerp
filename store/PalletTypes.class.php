<?php
/**
 * Видове палети
 */
class store_PalletTypes extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Видове палети';


    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, store_Wrapper, plg_RowTools';


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
    var $listFields = 'id,title,width=Широчина,depth=Дълбочина,height=Височина,maxWeight=Макс. тегло,tools=Пулт';


    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';

    
    function description()
    {
        $this->FLD('title',     'varchar(16)',         'caption=Заглавие,mandatory');
        $this->FLD('width',     'double(decimals=2)', 'caption=Палет->Широчина [м]');
        $this->FLD('depth',     'double(decimals=2)', 'caption=Палет->Дълбочина [м]');
        $this->FLD('height',    'double(decimals=2)', 'caption=Палет->Височина [м]');
        $this->FLD('maxWeight', 'double(decimals=2)', 'caption=Палет->Тегло [kg]');
    }

}