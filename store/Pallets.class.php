<?php
/**
 * 
 * Палети
 */
class store_Pallets extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Палети';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, 
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
    var $listFields = 'productId, quantity, comment, width, depth, height, maxWeight,
                       rackPosition, move, moveStatus, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    

    /**
     *  @todo Чака за документация...
     */
    var $details = array('store_PalletDetails');
        
    
    function description()
    {
        $this->FLD('productId',       'key(mvc=store_Products, select=name)', 'caption=Продукт');
        $this->FLD('quantity',        'int',                                  'caption=Количество');
        $this->FLD('comment',         'varchar(256)',                         'caption=Коментар');
        $this->FLD('width',           'double(decimals=2)',                   'caption=Дименсии (Max)->Широчина [м]');
        $this->FLD('depth',           'double(decimals=2)',                   'caption=Дименсии (Max)->Дълбочина [м]');
        $this->FLD('height',          'double(decimals=2)',                   'caption=Дименсии (Max)->Височина [м]');
        $this->FLD('maxWeight',       'double(decimals=2)',                   'caption=Дименсии (Max)->Тегло [kg]');
        $this->FLD('storeId',         'key(mvc=store_Stores,select=name)',    'caption=Място->Склад,input=hidden');
        $this->FLD('rackPosition',    'varchar(255)',                         'caption=Позиция->Текуща');
        // $this->FLD('rackPositionNew', 'varchar(255)',                         'caption=Позиция->Чакаща');
        $this->FNC('move',            'varchar(255)',                         'caption=Преместване->Действие');
        $this->FLD('moveStatus',      'enum(Waiting, Done)',                  'caption=Преместване->Състояние');
    }
    
    
    /**
     * Преди извличане на записите
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$selectedStoreId}");
    }

    
    /**
     * При редакция на палетите
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        // storeId
    	$selectedStoreId = store_Stores::getCurrent();
        $data->form->setDefault('storeId', $selectedStoreId);        

        // Дименции по подразбиране
        if (!$data->form->rec->id) {
            $data->form->setDefault('width', 1.80);           
            $data->form->setDefault('depth', 1.80);
            $data->form->setDefault('height', 2.20);
            $data->form->setDefault('maxWeight', 250.00);        	
        }
        
        $data->form->showFields = 'productId, quantity, comment, width, depth, height, maxWeight';        
    }

    
    /**
     * rackPosition
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */    
    function on_BeforeSave($mvc,&$id,$rec)
    {
    	if (!$rec->id) {
            $rec->rackPosition = 'На пода';
    	}    
    }

    
    /**
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->move = Ht::createLink('Качване',        array('store_Movements', 'moveUP',    'id' => $rec->id));
        $row->move .= " " . Ht::createLink('На пода', array('store_Pallets', 'moveC',       'id' => $rec->id));
        $row->move .= " " . Ht::createLink('Местене', array('store_Pallets', 'moveXYZ',     'id' => $rec->id));
        
        if ($row->moveStatus == 'Waiting') {
            $row->ROW_ATTR .= new ET(' style="background-color: #ffbbbb;"');
        }
    }

}