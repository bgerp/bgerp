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
    var $listFields = 'productId, quantity, comment, width, depth, height, maxWeight,
                       rackPosition, move, tools=Пулт';
    
    
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
        $this->FLD('productId',    'key(mvc=store_Products, select=name)', 'caption=Продукт');
        $this->FLD('quantity',     'int',                                  'caption=Количество');
        $this->FLD('comment',      'varchar(256)',                         'caption=Коментар');
        $this->FLD('width',        'double(decimals=2)',                   'caption=Дименсии (Max)->Широчина [м]');
        $this->FLD('depth',        'double(decimals=2)',                   'caption=Дименсии (Max)->Дълбочина [м]');
        $this->FLD('height',       'double(decimals=2)',                   'caption=Дименсии (Max)->Височина [м]');
        $this->FLD('maxWeight',    'double(decimals=2)',                   'caption=Дименсии (Max)->Тегло [kg]');
        $this->FLD('storeId',      'key(mvc=store_Stores,select=name)',    'caption=Място->Склад,input=hidden');
        $this->FLD('rackPosition', 'varchar(255)',                         'caption=Позиция');
        $this->FNC('move',         'varchar(255)',                         'caption=Преместване');
        $this->FNC('moveStatus',   'enum(Waiting, Done)',                  'caption=Преместване');
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
        $row->move = Ht::createLink('Up/Down',        array('store_Pallets', 'moveR',       'id' => $rec->id));
        $row->move .= " " . Ht::createLink('L/R',     array('store_Pallets', 'moveC',       'id' => $rec->id));
        $row->move .= " " . Ht::createLink('XYZ',     array('store_Pallets', 'moveXYZ',     'id' => $rec->id));
        $row->move .= " " . Ht::createLink('Под', array('store_Pallets', 'moveToFloor', 'id' => $rec->id));
    }

    
    /**
     *  Мести палет Up/Down
     */
    function act_MoveR()
    {
        $palletId = Request::get('id', 'int');
        
        $form = cls::get('core_form', array('method' => 'GET'));
        $form->title = "ПРЕМЕСТАНЕ Up/Dowm НА ПАЛЕТ С ID={$palletId}";

        // rackRow
        $form->FNC('rackRow', 'enum(A,B,C,D,E,F,G)', 'caption=Палет място->Ред');        
        
        $rackPosition = $this->fetchField("id={$palletId}", 'rackPosition'); 
        
        if ($rackPosition != 'На пода') {
	        $rackPositionArr = explode("-", $rackPosition);
	        $rackRow    = $rackPositionArr[1];
	        $form->setDefault('rackRow', $rackRow);
        }
        
        $form->showFields = 'rackRow';
        
        // id
        $form->FNC('id', 'int', 'input=hidden');
        $form->setDefault('id', $palletId);
        
        $form->toolbar->addSbBtn('Запис');
        
        $form->setAction(array($this, 'moveRDo'));   
      
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    function act_MoveRDo()
    {
        $palletId = Request::get('id', 'int');
        $rackRow  = Request::get('rackRow');
        bp($palletId, $rackRow);                
    }    
    
}