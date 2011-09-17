<?php
/**
 * 
 * Движения
 */
class store_Movements extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Движения';
    
    
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
    var $listFields = 'palletId, positionOld, positionNew, moveStatus,tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId',      'key(mvc=store_Stores, select=name)', 'caption=Склад');
        $this->FLD('palletId',     'key(mvc=store_Pallets, select=id)',  'caption=Палет,notNull');
        $this->FLD('positionOld',  'varchar(255)',                       'caption=Позиция->Стара');
        $this->FLD('positionNew',  'varchar(255)',                       'caption=Позиция->Нова');
        $this->FLD('moveStatus',   'enum(Чакащ, На място)',              'caption=Състояние');
        
        /*
        $this->FLD('kind',    'enum(upload=Качи,
                                    download=Свали,
                                    take=Вземи,
                                    move=Мести)',        'caption=Действие');
        
        $this->FLD('quantity',     'int',                    'caption=Количество');
        $this->FLD('units',        'key(mvc=common_Units, select=shortName)', 'caption=Мярка');
        $this->FLD('possitionOld', 'varchar(255)',       'caption=Позиция->Стара');
        $this->FLD('possitionNew',    'varchar(255)',       'caption=Позиция->Нова');
        $this->FLD('processBy',    'key(mvc=core_Users, select=names)', 'caption=Изпълнител');
        $this->FLD('startOn',      'date',               'caption=Дата->Започване');
        $this->FLD('finishOn',     'date',               'caption=Дата->Приключване');
        */
    }
    
    
    /**
     * Ако статуса е 'Чакащ'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($row->moveStatus == 'Чакащ') {
            $row->ROW_ATTR .= new ET(' style="background-color: #ffbbbb;"');
        } else {
            $row->ROW_ATTR .= new ET(' style="background-color: #ddffdd;"');
        }
    }

    
    /**
     * При редакция
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->showFields = 'moveStatus';
        $data->form->setAction(array($this, 'changeMoveStatus', $data->form->rec->id));
        $data->form->setHidden('palletId', $data->form->rec->palletId);
    }
    
    /**
     * Смяна на moveStatus в store_Movements и в store_Pallets
     */
    function act_ChangeMoveStatus()
    {
        // store_Movements
    	$rec = new stdClass;
    	$rec->id = Request::get('id');
        $rec->palletId = Request::get('palletId');
        $rec->moveStatus = Request::get('moveStatus');
        
        self::save($rec);
        
        if ($rec->moveStatus != 'Чакащ') {
	        // store_Pallets
	        $recPallets = store_Pallets::fetch($rec->palletId);
	        $recPallets->moveStatus = $rec->moveStatus;
	        $recPallets->rackPosition = self::fetchField("#id={$rec->id}", 'positionNew');
	        store_Pallets::save($recPallets);
	        
	        return new Redirect(array('store_Pallets', 'List'));            
        } else {
            return new Redirect(array($this, 'List'));
        }
        
    }
    
    
    /**
     * Форма за преместване на палет
     */
    function act_MoveForm()
    {
        $palletId = Request::get('id', 'int');
        
        $form = cls::get('core_form', array('method' => 'GET'));
        $form->title = "ПРЕМЕСТВАНЕ НА ПАЛЕТ С ID={$palletId}";

        // Палет място
        $form->FNC('rackId',     'key(mvc=store_Racks,select=id)', 'caption=Палет място->Палет');
        $form->FNC('rackRow',    'enum(A,B,C,D,E,F,G)', 'caption=Палет място->Ред');        
        $form->FNC('rackColumn', 'enum(1,2,3,4,5,6,7,8,9,10,
                                       11,12,13,14,15,16,17,18,
                                       19,20,21,22,23,24)', 'caption=Палет място->Колона');
        
        $rackPosition = store_Pallets::fetchField($palletId, 'rackPosition');
        $rackPositionArr = explode("-", $rackPosition);
        
        $rackId     = $rackPositionArr[0];
        $rackRow    = $rackPositionArr[1];
        $rackColumn = $rackPositionArr[2];
        
        $form->setDefault('rackId', $rackId);
        $form->setDefault('rackRow', $rackRow);
        $form->setDefault('rackColumn', $rackColumn);
        
        $form->showFields = 'palletId, rackId, rackRow, rackColumn';
        
        // id
        $form->FNC('palletId', 'int', 'input=hidden');
        $form->setDefault('palletId', $palletId);
        
        $form->toolbar->addSbBtn('Запис');
        
        $form->setAction(array($this, 'moveUpDo'));   
      
        return $this->renderWrapping($form->renderHtml());
    }    

    
    /**
     * Форма за преместване на палет от пода
     */
    function act_MoveUpForm()
    {
        $palletId = Request::get('id', 'int');
        
        $form = cls::get('core_form', array('method' => 'GET'));
        $form->title = "КАЧВАНЕ ОТ ПОДА НА ПАЛЕТ С ID={$palletId}";

        // Палет място
        $form->FNC('rackId',     'key(mvc=store_Racks,select=id)', 'caption=Палет място->Палет');
        $form->FNC('rackRow',    'enum(A,B,C,D,E,F,G)', 'caption=Палет място->Ред');        
        $form->FNC('rackColumn', 'enum(1,2,3,4,5,6,7,8,9,10,
                                       11,12,13,14,15,16,17,18,
                                       19,20,21,22,23,24)', 'caption=Палет място->Колона');
        
        $form->showFields = 'palletId, rackId, rackRow, rackColumn';
        
        // id
        $form->FNC('palletId', 'int', 'input=hidden');
        $form->setDefault('palletId', $palletId);
        
        $form->toolbar->addSbBtn('Запис');
        
        $form->setAction(array($this, 'moveUpDo'));   
      
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /*
     * Преместване на палет от пода
     */
    function act_MoveUpDo()
    {
        $rec = new stdClass;
        $palletId        = Request::get('palletId', 'int');
        
        // проверка за insert/update
        if (self::fetchField("#palletId={$palletId}", 'id')) {
            $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        }
        
        $rec->palletId = $palletId;
        $rec->positionOld = store_Pallets::fetchField("id={$palletId}", 'rackPosition');
        $rackId          = Request::get('rackId');
        $rackRow         = Request::get('rackRow');
        $rackColumn      = Request::get('rackColumn');
        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
        $rec->moveStatus = 'Чакащ';
        self::save($rec);
        
        // update store_Pallets
        $recPallets = store_Pallets::fetch($palletId);
        $recPallets->moveStatus = 'Чакащ';
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));
    }

    
    /**
     * Форма за преместване на палет на пода
     */
    function act_MoveDownDo()
    {
        $palletId = Request::get('id', 'int');
        
        $rec = new stdClass;
        
        // проверка за insert/update
        if (self::fetchField("#palletId={$palletId}", 'id')) {
            $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        }
        
        $rec->palletId = $palletId;
        $rec->positionOld = store_Pallets::fetchField("id={$palletId}", 'rackPosition');
        $rec->positionNew = 'На пода';
        $rec->moveStatus = 'Чакащ';
        self::save($rec);
        
        // update store_Pallets
        $recPallets = store_Pallets::fetch($palletId);
        $recPallets->moveStatus = 'Чакащ';
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));        
        
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