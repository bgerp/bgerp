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
    var $listFields = 'productId, quantity, comment, dimensions,
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
        $this->FLD('storeId',      'key(mvc=store_Stores,select=name)',    'caption=Място->Склад,input=hidden');
    	$this->FLD('productId',    'key(mvc=store_Products, select=name)', 'caption=Продукт');
        $this->FLD('quantity',     'int',                                  'caption=Количество');
        $this->FLD('comment',      'varchar(256)',                         'caption=Коментар');
        $this->FLD('width',        'double(decimals=2)',                   'caption=Дименсии (Max)->Широчина [м]');
        $this->FLD('depth',        'double(decimals=2)',                   'caption=Дименсии (Max)->Дълбочина [м]');
        $this->FLD('height',       'double(decimals=2)',                   'caption=Дименсии (Max)->Височина [м]');
        $this->FLD('maxWeight',    'double(decimals=2)',                   'caption=Дименсии (Max)->Тегло [kg]');
        $this->FNC('dimensions',   'varchar(255)',                         'caption=Широчина<br/>Дълбочина<br/>Височина<br/>Макс. тегло');
        $this->FLD('rackPosition', 'varchar(255)',                         'caption=Позиция');
        $this->FNC('move',         'varchar(255)',                         'caption=Преместване->Действие');
        $this->FLD('moveStatus',   'enum(Чакащ, На място)',                'caption=Преместване->Състояние,input=hidden');
    }
    
    
    /**
     * Извличане записите само от избрания склад
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
     * При редакция на палетите дименции по подразбиране
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

        // Дименции по подразбиране за нов запис
        if (!$data->form->rec->id) {
            $data->form->setDefault('width', 1.80);           
            $data->form->setDefault('depth', 1.80);
            $data->form->setDefault('height', 2.20);
            $data->form->setDefault('maxWeight', 250.00);
            $data->form->setDefault('moveStatus', 'На място');       	
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
     * Ако 'moveStatus' е 'Чакащ' скриваме опциите за 'Действие' и оцветяваме реда
     *  
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	if ($rec->moveStatus == 'На място') {
    		if ($rec->rackPosition == 'На пода') {
        		$row->move = Ht::createLink('Качване', array('store_Movements', 'moveUpForm',   'id' => $rec->id));	
    		} else {
    		    $row->move = Ht::createLink('Местене', array('store_Movements', 'moveForm',     'id' => $rec->id));
    		    $row->move .= ", " . Ht::createLink('Сваляне на пода', array('store_Movements', 'moveDownDo', 'id' => $rec->id));
    		}
    	}
        
        if ($rec->moveStatus == 'Чакащ') {
            $row->ROW_ATTR .= new ET(' style="background-color: #ffbbbb;"');
        }
        
        if ($rec->moveStatus == 'На място') {
            $row->ROW_ATTR .= new ET(' style="background-color: #ddffdd;"');
        }        
        
        $row->dimensions =  $mvc->getVerbal($rec, 'width') . "м | 
                        " . $mvc->getVerbal($rec, 'depth') . "м |
                        " . $mvc->getVerbal($rec, 'height') . "м | 
                        " . $mvc->getVerbal($rec, 'maxWeight') . "кг";
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