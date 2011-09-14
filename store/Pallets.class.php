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
                       rackPosition, tools=Пулт';
    
    
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
        $this->FLD('action',       'varchar(255)',                         'caption=Действие');
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
        
        // Палет място
        $data->form->FNC('position', 'enum(На пода, На стелаж)', 'caption=Палет място');
        
        $data->form->FNC('rackNum', 'int', 'caption=Палет място->Номер стелаж');
        $data->form->FNC('rackRow', 'enum(A,B,C,D,E,F,G)', 'caption=Палет място->Ред');
        $data->form->FNC('rackColumn', 'enum(1,2,3,4,5,6,7,8,9,10,
                                             11,12,13,14,15,16,17,18,
                                             19,20,21,22,23,24)', 'caption=Палет място->Колона');
        
        $data->form->showFields = 'productId, quantity, comment, width, depth, height, maxWeight, position,
                                   rackNum, rackRow, rackColumn';        
        
        // prepare rackNumArr
        $Racks = cls::get('store_Racks');
        $queryRacks = $Racks->getQuery();
        
        while($rec = $queryRacks->fetch("#storeId = {$selectedStoreId}")) {
            $rackNumArr[$rec->num] = $rec->num; 
        }        
        // END prepare rackNumArr
        
        // При edit на запис
        if ($data->form->rec->id) {
        	// Ако е на стелаж
            if ($data->form->rec->rackPosition != 'На пода') {
            	$rackPosition = explode("-", $data->form->rec->rackPosition);
            	$rackNum    = $rackPosition[0];
            	$rackRow    = $rackPosition[1];
            	$rackColumn = $rackPosition[2]; 
            	
		        // rackNum
		        $data->form->setOptions('rackNum', $rackNumArr);
		        $data->form->setDefault('rackNum', $rackNum);

		        // position
		        $data->form->setDefault('position', 'На стелаж');
		        
		        // rackRow
		        $data->form->setDefault('rackRow', $rackRow);
		        
                // rackColumn
                $data->form->setDefault('rackColumn', $rackColumn);		        
            } else {
                // position
                $data->form->setDefault('position', 'На пода'); 

                // rackNum
                $data->form->setOptions('rackNum', $rackNumArr);                
            }
        } else {
            // rackNum
            $data->form->setOptions('rackNum', $rackNumArr);            
        }
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
    	if ($rec->position == 'На пода') {
        	$rec->rackPosition = 'На пода';
        } elseif ($rec->position == 'На стелаж') {
            $rec->rackPosition = $rec->rackNum . "-" . $rec->rackRow . "-" . $rec->rackColumn;
        }
    }    

    
}