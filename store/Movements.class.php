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
        $this->FLD('moveStatus',   'enum(Waiting,Done)',                 'caption=Статус');
        
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
     * Ако статуса е 'Waiting'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($row->moveStatus == 'Waiting') {
            $row->ROW_ATTR .= new ET(' style="background-color: #ffbbbb;"');
        }
    }

    
    /**
     * 
     * 
     * @param core_Manager $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_AfterSave($mvc, &$id, &$rec)
    {   	
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
        $data->form->showFields = 'moveStatus';        
    }

    
    /**
     *  Мести палет Up/Down
     */
    function act_MoveUp()
    {
        $palletId = Request::get('id', 'int');
        
        $form = cls::get('core_form', array('method' => 'GET'));
        $form->title = "ПРЕМЕСТАНЕ Up/Dowm НА ПАЛЕТ С ID={$palletId}";

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
    
    
    function act_MoveUpDo()
    {
        $rec = new stdClass;
        $palletId        = Request::get('palletId', 'int');
        
        $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        
        $rec->palletId = $palletId;
        $rec->positionOld = store_Pallets::fetchField("id={$palletId}", 'rackPosition');
        $rackId          = Request::get('rackId');
        $rackRow         = Request::get('rackRow');
        $rackColumn      = Request::get('rackRow');
        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
        $rec->moveStatus = 'Waiting';
        // bp($rec);
        self::save($rec);
        
        return new Redirect(array('store_Pallets', 'List'));
    }     
	
}