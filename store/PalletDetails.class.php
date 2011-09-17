<?php

/**
 * Документи за склада
 */
class store_PalletDetails extends core_Detail {


    /**
     *  @todo Чака за документация...
     */
    var $title = 'Детайли на палет';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, store_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, details, tools=Пулт';

    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'palletId';

    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "store_Pallets";    
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('palletId', 'key(mvc=store_Pallets, select=id)', 'caption=Палет');
    	$this->FLD('details', 'varchar(255)', 'caption=Dummy for test');
    	
        $this->FLD('productId', 'key(mvc=store_Products, select=name)', 'caption=Съдържание->Продукт');
        $this->FLD('quantity',  'int',                                  'caption=Количество');
        $this->FLD('comment',   'varchar(256)',                         'caption=Коментар');
        $this->FLD('width',     'double(decimals=2)',                   'caption=Дименсии->Широчина [м]');
        $this->FLD('height',    'double(decimals=2)',                   'caption=Дименсии->Височина [м]');
        $this->FLD('weight',    'double(decimals=2)',                   'caption=Дименсии->Тегло [kg]');
        $this->FLD('storeId',   'key(mvc=store_Stores,select=name)',    'caption=Място->Склад');
        $this->FLD('rackNum',   'int',                                  'caption=Място->Стелаж');
        $this->FLD('row',       'enum(A,B,C,D,E,F,G,H)',                'caption=Място->Ред');
        $this->FLD('column',    'int',                                  'caption=Място->Колонa');
        $this->FLD('action',    'varchar(255)',                         'caption=Действие');    	
    }
    
}