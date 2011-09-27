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
                    acc_plg_Registry, store_Wrapper, plg_RefreshRows, plg_State';
    
    
    /**
     *  Време за опресняване информацията при лист
     */
    var $refreshRowsTime = 10000;    
    
    
    
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
    var $canDelete = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 300;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'palletId, positionOld, positionNew, workerId, state, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId',      'key(mvc=store_Stores, select=name)',                   'caption=Склад');
        $this->FLD('palletId',     'key(mvc=store_Pallets, select=id)',                    'caption=Палет,input=hidden');
        $this->FLD('positionOld',  'varchar(255)',                                         'caption=Палет място (текущо)');
        $this->FLD('positionNew',  'varchar(255)',                                         'caption=Палет място (ново)');
        $this->FLD('state',        'enum(pending, active, closed)', 'caption=Състояние, input=hidden');
        $this->XPR('orderBy',      'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'active' THEN 2 WHEN 'closed' THEN 3 END)");
        $this->FLD('workerId',     'key(mvc=core_Users, select=names)', 'caption=Товарач');
        
        /*
        $this->FLD('kind',    'enum(upload=Качи,
                                    download=Свали,
                                    take=Вземи,
                                    move=Мести)',        'caption=Действие');
        
        $this->FLD('quantity',     'int',                    'caption=Количество');
        $this->FLD('units',        'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка');
        $this->FLD('possitionOld', 'varchar(255)',       'caption=Позиция->Стара');
        $this->FLD('possitionNew',    'varchar(255)',       'caption=Позиция->Нова');
        $this->FLD('processBy',    'key(mvc=core_Users, select=names)', 'caption=Изпълнител');
        $this->FLD('startOn',      'date',               'caption=Дата->Започване');
        $this->FLD('finishOn',     'date',               'caption=Дата->Приключване');
        */
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
            
            if ($rec->state != 'closed') {
                $requiredRoles = 'no_one';
            }
        }
    }    
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('orderBy');
    }    
    
    
    /**
     * В зависимост от state-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        switch($rec->state) {
        	case 'pending':
        	   $row->state = Ht::createBtn('Вземи', array($mvc, 'setPalletActive', $rec->id));
        	   break;
        	   
        	case 'closed':
               $row->state = 'На място';
               break;        	

            case 'active':
            	$userId = Users::getCurrent();
 
            	if ($userId == $rec->workerId) {
            	   $row->state = Ht::createBtn('Приключи', array($mvc, 'setPalletClosed', $rec->id));
            	} else {
            	   $row->state = 'Зает';
            	}
               break;               
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
        $form = $data->form;
        
    	$palletId = Request::get('palletId');
    	$do = Request::get('do');
    	
        switch ($do) {
        	case 'Качване':
   		        $form->title = "КАЧВАНЕ от пода на палет с ID={$palletId}";
   		        
   		        $positionOld = store_Pallets::fetchField("#id = {$palletId}", 'position');

		        // Палет място
		        $form->FNC('rackId',     'key(mvc=store_Racks,select=id)',    'caption=Палет място (ново)->Стелаж');
		        $form->FNC('rackRow',    'enum(A,B,C,D,E,F,G)', 'caption=Палет място (ново)->Ред');        
		        $form->FNC('rackColumn', 'enum(1=1,2,3,4,5,6,7,8,9,10,
		                                       11,12,13,14,15,16,17,18,
		                                       19,20,21,22,23,24)',           'caption=Палет място (ново)->Колона');
		        
		        $form->showFields = 'positionOld, rackId, rackRow, rackColumn';
		        
		        $form->setDefault('palletId', $palletId);
		        $form->setReadOnly('positionOld', $positionOld);
		        $form->setDefault('state', 'pending');
		        
		        $form->setAction(array($this, 'moveUpDo'));   
        		break;
        		
        	case 'Сваляне':
                $form->title = "СВАЛЯНЕ на пода на палет с ID={$palletId}";
                
                $positionOld = store_Pallets::fetchField("#id = {$palletId}", 'position');

                $form->showFields = 'positionOld, positionNew';
                
                $form->setDefault('palletId', $palletId);
                $form->setReadOnly('positionOld', $positionOld);
                $form->setReadOnly('positionNew', 'На пода');
                $form->setDefault('state', 'pending');
                
                $form->setAction(array($this, 'moveDownDo'));
        		break;
    
        	case 'Местене':
        		$form->title = "МЕСТЕНЕ на палет с ID={$palletId}";
        		
                // Палет място
                $form->FNC('rackId',     'key(mvc=store_Racks,select=id)',    'caption=Палет място (ново)->Стелаж');
                $form->FNC('rackRow',    'enum(A,B,C,D,E,F,G)', 'caption=Палет място (ново)->Ред');        
                $form->FNC('rackColumn', 'enum(1=1,2,3,4,5,6,7,8,9,10,
                                               11,12,13,14,15,16,17,18,
                                               19,20,21,22,23,24)',           'caption=Палет място (ново)->Колона');        		

        		$positionOld = store_Pallets::fetchField("#id = {$palletId}", 'position');
        		$positionNew = store_Pallets::fetchField("#id = {$palletId}", 'positionNew');
        		
        		$form->showFields = 'positionOld, rackId, rackRow, rackColumn';
        		
                $form->setDefault('palletId', $palletId);
                $form->setReadOnly('positionOld', $positionOld);
                $form->setDefault('state', 'pending');
                
                // Палет място (ново) - ако има нова позиция тя се зарежда по default, ако няма - старата позиция
                if ($positionNew != 'На пода' && $positionNew != NULL) {
                    $positionArr = explode("-", $positionNew);
                } else {
                	$positionArr = explode("-", $positionOld);
                }
				        
		        $rackId     = $positionArr[0];
		        $rackRow    = $positionArr[1];
		        $rackColumn = $positionArr[2];
		        
		        $form->setDefault('rackId',     $rackId);
		        $form->setDefault('rackRow',    $rackRow);
		        $form->setDefault('rackColumn', $rackColumn);                

                $form->setAction(array($this, 'moveDo'));         
        		break;
        }
        
    }
    
    
    /**
     * Сменя state в store_Movements и в store_Pallets на 'active' 
     */
    function act_SetPalletActive()
    {
        $id     = Request::get('id');
        $userId = Users::getCurrent();
        
        $rec = $this->fetch($id);
        $rec->state = 'active';
        $rec->workerId = $userId; 

        $this->save($rec);
        
        $recPallets = store_Pallets::fetch("#id = {$rec->palletId}");
        $recPallets->state = 'active';
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));
        
    }
    
    
    /**
     * Сменя state в store_Movements и в store_Pallets на 'closed' 
     */
    function act_SetPalletClosed()
    {
        $id     = Request::get('id');
        $userId = Users::getCurrent();
        
        $rec = $this->fetch($id);
        $rec->state       = 'closed';
        $rec->positionOld = $rec->positionNew;
        $rec->positionNew = NULL;
        $rec->workerId    = NULL; 

        $this->save($rec);
        
        $recPallets = store_Pallets::fetch("#id = {$rec->palletId}");
        $recPallets->state       = 'closed';
        $recPallets->position    = $recPallets->positionNew;
        $recPallets->positionNew = NULL;
         
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));
        
    }    
    
    
    /*
     * Преместване на палет от пода
     */
    function act_MoveUpDo()
    {
        $palletId = Request::get('palletId');
        
        $rec = new stdClass;
                
        // проверка за insert/update
        if (self::fetchField("#palletId={$palletId}", 'id')) {
            $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        }
        
        $rec->palletId    = $palletId;
        $rec->positionOld = store_Pallets::fetchField("id={$palletId}", 'position');
        $rackId           = Request::get('rackId');
        $rackRow          = Request::get('rackRow');
        $rackColumn       = Request::get('rackColumn');
        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
        $rec->state       = 'pending';
        
        // Проверка дали има палет с тази или към тази позиция
        $this->checkPalletFreePosition($rec->positionNew);
        
        self::save($rec);
        
        $recPallets              = new stdClass;
        $recPallets              = store_Pallets::fetch($palletId);
        $recPallets->positionNew = $rec->positionNew;
        $recPallets->state       = 'pending';
        
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));
    }

    
    /**
     * Форма за преместване на палет на пода
     */
    function act_MoveDownDo()
    {
    	$palletId = Request::get('palletId');
        
        $rec = new stdClass;
        
        $rec->palletId    = $palletId;
        $rec->positionOld = store_Pallets::fetchField("id={$palletId}", 'position');
        
        // При случай 'от пода на пода' директно state-а става 'closed' и се изтрива записа за движение на този палет
        if ($rec->positionOld == 'На пода') {
        	self::delete("#palletId = {$palletId}");
        	
            $recPallets = new stdClass;
            $recPallets = store_Pallets::fetch($palletId);
            $recPallets->positionNew = NULL;
            $recPallets->state = 'closed';
            
            store_Pallets::save($recPallets);        	
        } else {
	        $rec->positionNew = 'На пода';
	        $rec->state       = 'pending';
	        
	        self::save($rec);
	        
	        $recPallets = new stdClass;
	        $recPallets = store_Pallets::fetch($palletId);
	        $recPallets->positionNew = 'На пода';
	        $recPallets->state = 'pending';
	        
	        store_Pallets::save($recPallets);
        }
        
        return new Redirect(array('store_Pallets', 'List'));        
    }
    
    
    /**
     * Форма за преместване на палет на стелажа
     */
    function act_MoveDo()
    {
        $palletId = Request::get('palletId');
        
        $rec = new stdClass;
                
        // проверка за insert/update
        if (self::fetchField("#palletId={$palletId}", 'id')) {
            $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        }
        
        $rec->palletId    = $palletId;
        $rec->positionOld = store_Pallets::fetchField("id={$palletId}", 'position');
        $rackId           = Request::get('rackId');
        $rackRow          = Request::get('rackRow');
        $rackColumn       = Request::get('rackColumn');
        // bp($rackColumn);
        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
        $rec->state       = 'pending';
        
        // Проверка дали има палет с тази или към тази позиция
        $this->checkPalletFreePosition($rec->positionNew);        

        self::save($rec);
        
        $recPallets              = new stdClass;
        $recPallets              = store_Pallets::fetch($palletId);
        $recPallets->positionNew = $rec->positionNew;
        $recPallets->state       = 'pending';
        
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));       
    }

    
    function checkPalletFreePosition($position) {
        $recPalletsCheckOne = store_Pallets::fetch("#position    = '{$position}'");
        $recPalletsCheckTwo = store_Pallets::fetch("#positionNew = '{$position}'");
                        
        if ($recPalletsCheckOne || $recPalletsCheckTwo) {
            core_Message::redirect("Има палет на това палет място <br/>или </br>има наредено движение към това палет място", 
                                   'tpl_Error', 
                                   NULL, 
                                   array('store_Pallets', 'list'));            
        }        
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