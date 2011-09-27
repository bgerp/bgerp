<?php
/**
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
                     acc_plg_Registry, store_Wrapper, plg_State';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'noone';
    
    
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
    var $canDelete = 'noone';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 300;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, productId, quantity, comment, dimensions,
                       positionView, move, tools=Пулт';
    
    
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
        $this->FLD('storeId',       'key(mvc=store_Stores,select=name)',    'caption=Място->Склад,input=hidden');
    	$this->FLD('productId',     'key(mvc=store_Products, select=name)', 'caption=Продукт');
        $this->FLD('quantity',      'int',                                  'caption=Количество');
        $this->FLD('comment',       'varchar(256)',                         'caption=Коментар');
        $this->FLD('width',         'double(decimals=2)',                   'caption=Дименсии (Max)->Широчина [м]');
        $this->FLD('depth',         'double(decimals=2)',                   'caption=Дименсии (Max)->Дълбочина [м]');
        $this->FLD('height',        'double(decimals=2)',                   'caption=Дименсии (Max)->Височина [м]');
        $this->FLD('maxWeight',     'double(decimals=2)',                   'caption=Дименсии (Max)->Тегло [kg]');
        $this->FNC('dimensions',    'varchar(255)',                         'caption=Габарити');
        $this->FLD('state',         'enum(pending=Чакащ движение,
                                          active=Работи се, 
                                          closed=На място)',                'caption=Състояние');
        $this->FLD('position',      'varchar(255)',                         'caption=Позиция->Текуща');
        $this->FLD('positionNew',   'varchar(255)',                         'caption=Позиция->Нова');
        $this->FNC('positionView',  'varchar(255)',                         'caption=Позиция');
        
        $this->FNC('move',          'varchar(255)',                         'caption=Действие');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Забранява изтриването за записи, които не са със state 'closed'
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id && ($action == 'delete')  ) {
            $rec = $mvc->fetch($rec->id);
            
            if ($rec->state == 'closed' || $rec->state == 'pending') {
                $requiredRoles = 'admin,store';
            }
        }
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
        
        $data->query->orderBy('state');
    }

    
    /**
     * При добавяне/редакция на палетите - данни по подразбиране 
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

        // По подразбиране за нов запис
        if (!$data->form->rec->id) {
            $data->form->setDefault('width', 1.80);           
            $data->form->setDefault('depth', 1.80);
            $data->form->setDefault('height', 2.20);
            $data->form->setDefault('maxWeight', 250.00);
            $data->form->setField('position', 'caption=Позиция');
            $data->form->setReadOnly('position', 'На пода');
            
            $data->form->setDefault('state', 'closed');       	
        }
        
        $data->form->showFields = 'productId, quantity, comment, width, depth, height, maxWeight, position';        
    }
   
 
    /**
     * positionView и move - различни варианти в зависимост от position, positinNew и state 
     *  
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	// Imgages
    	$imgUp   = ht::createElement('img', array('src' => sbf('img/up.gif',   ''), 'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));
        $imgDown = ht::createElement('img', array('src' => sbf('img/down.gif', ''), 'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));
        $imgMove = ht::createElement('img', array('src' => sbf('img/move.gif', ''), 'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));        
        $imgEdit = ht::createElement('img', array('src' => sbf('img/edit.png', ''), 'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));        
        
        if ($rec->position == 'На пода' && $rec->positionNew == NULL && $rec->state == 'closed') {
            $row->positionView = 'На пода';
            $row->move = ht::createLink($imgUp , array('store_Movements', 'add', 'palletId' => $rec->id, 'do' => 'Качване'));
        }
        
        if ($rec->position != 'На пода' && $rec->positionNew == NULL && $rec->state == 'closed') {
            $row->positionView = $rec->position;
            $row->move = Ht::createLink($imgDown, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'Сваляне'));
            $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'Местене'));
        }        
        
        if ($rec->positionNew == 'На пода' && $rec->state == 'pending') {
            $row->positionView = $rec->position . ' -> ' . $rec->positionNew;
            $row->move = 'Чакащ';
            $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'Местене')); 
        }

        if ($rec->positionNew != NULL && $rec->positionNew != 'На пода' && $rec->state == 'pending') {
            $row->positionView = $rec->position . ' -> ' . $rec->positionNew;
            $row->move = 'Чакащ';
            $row->move .= Ht::createLink($imgDown, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'Сваляне'));
            $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'Местене')); 
        }
                        
        if ($rec->state == 'active') {
            $row->positionView = $rec->position . ' -> ' . $rec->positionNew;
            $row->move = 'Зает';
        }

        $row->dimensions = number_format($rec->width, 2) . "x" . number_format($rec->depth, 2) . "x" . number_format($rec->height, 2) . " м, " . $rec->maxWeight . " кг";
        
    }
    
    /**
     * Проверка при изтриване дали палета не е в движение 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param $query
     */    
    function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
             
        while ($rec = $_query->fetch($cond)) {
        	if ($rec->state == 'active') {
                  core_Message::redirect("Невъзможно изтриване - с този палет се работи", 
                    'tpl_Error', 
                    NULL, 
                    array($mvc, 'list')
                );
        	}
        	
        	$query->deleteRecId = $rec->id;
        }
        
    }
    
    
    /**
     *  Ако е минала проверката за state в on_BeforeDelete, след като е изтрит записа изтриваме всички движения за него
     */
    function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        store_Movements::delete("#palletId = {$query->deleteRecId}");
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