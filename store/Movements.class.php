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
    var $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RefreshRows, plg_State';
    
    
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
    var $canEdit = 'no_one';
    
    
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
    var $listItemsPerPage = 50;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,palletId, positionView, workerId, state, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId',      'key(mvc=store_Stores, select=name)', 'caption=Склад');
        $this->FLD('palletId',     'key(mvc=store_Pallets, select=id)',  'caption=Палет,input=hidden');
        $this->FLD('positionOld',  'varchar(255)',                       'caption=Палет място->Старо');
        $this->FNC('position',     'varchar(255)',                       'caption=Палет място->Текущо');
        $this->FLD('positionNew',  'varchar(255)',                       'caption=Палет място->Ново');
        $this->FNC('positionView', 'varchar(255)',                       'caption=Палет място');
        $this->FLD('state',        'enum(pending, active, closed)',      'caption=Състояние, input=hidden');
        $this->XPR('orderBy',      'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'active' THEN 2 WHEN 'closed' THEN 3 END)");
        $this->FLD('workerId',     'key(mvc=core_Users, select=names)',  'caption=Товарач');
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
        
        if ($rec->state == 'pending' || $rec->state == 'active') {
        	$position = store_Pallets::fetchField("#id = {$rec->palletId}", 'position');
            
        	if ($position == 'На пода') {
        		$row->positionView = '<b>Нов</b> -> На пода';
        	} else {
        	    $row->positionView = $position . " -> " . $rec->positionNew;
        	}
        }
        
        if ($rec->state == 'closed') {
        	if ($rec->positionOld == 'На пода' && $rec->positionNew == 'На пода') {
        	   $row->positionView = '<b>Нов</b> -> На пода';
        	} else {
        	   $row->positionView = $rec->positionOld . " -> " . $rec->positionNew;
        	}   
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
        
    	$palletId = Request::get('palletId', 'int');
    	$do = Request::get('do');
    	
        switch ($do) {
        	case 'palletUp':
   		        $form->title = "КАЧВАНЕ от пода на палет с|* ID={$palletId}";
   		        
   		        $position = 'На пода';
   		        
		        // Палет място
		        $form->FNC('rackId',     'key(mvc=store_Racks,select=id)', 'caption=Палет място (ново)->Стелаж,input');
		        $form->FNC('rackRow',    'enum(A,B,C,D,E,F,G)',            'caption=Палет място (ново)->Ред');        
		        $form->FNC('rackColumn', 'enum(1,2,3,4,5,6,7,8,9,10,
		                                       11,12,13,14,15,16,17,18,
		                                       19,20,21,22,23,24)',        'caption=Палет място (ново)->Колона');
		        $form->FNC('do',         'varchar(64)',                    'caption=Движение,input=hidden');
		        
		        $form->showFields = 'position, rackId, rackRow, rackColumn';
		        
		        $form->setDefault('palletId', $palletId);
		        $form->setReadOnly('position', $position);
		        $form->setDefault('state', 'pending');
		        
		        // Действие
		        $form->setHidden('do', $do);
        		break;
        		
        	case 'palletDown':
                $form->title = "СВАЛЯНЕ на пода на палет с|* ID={$palletId}";
                
                $position = store_Pallets::fetchField("#id = {$palletId}", 'position');

                $form->showFields = 'position, positionNew';
                
                $form->setDefault('palletId', $palletId);
                $form->setReadOnly('position', $position);
                $form->setReadOnly('positionNew', 'На пода');
                $form->setDefault('state', 'pending');
                
                // Действие
                $form->setDefault('do', 'palletDown');                
        		break;
    
        	case 'palletMove':
        		$form->title = "МЕСТЕНЕ на палет с|* ID={$palletId}";
        		
                // Палет място
                $form->FNC('rackId',     'key(mvc=store_Racks,select=id)', 'caption=Палет място (ново)->Стелаж');
                $form->FNC('rackRow',    'enum(A,B,C,D,E,F,G)',            'caption=Палет място (ново)->Ред');        
                $form->FNC('rackColumn', 'enum(1=1,2,3,4,5,6,7,8,9,10,
                                               11,12,13,14,15,16,17,18,
                                               19,20,21,22,23,24)',        'caption=Палет място (ново)->Колона');        		

        		$position    = store_Pallets::fetchField("#id = {$palletId}", 'position');
        		$positionNew = self::fetchField("#palletId = {$palletId}", 'positionNew');
        		
        		$form->showFields = 'position, rackId, rackRow, rackColumn';
        		
                $form->setDefault('palletId', $palletId);
                $form->setReadOnly('position', $position);
                $form->setDefault('state', 'pending');
                
                // Палет място (ново) - ако има нова позиция тя се зарежда по default, ако няма - старата позиция
                if ($positionNew != 'На пода' && $positionNew != NULL) {
                    $positionArr = explode("-", $positionNew);
                } else {
                	$positionArr = explode("-", $position);
                }
				        
		        $rackId     = $positionArr[0];
		        $rackRow    = $positionArr[1];
		        $rackColumn = $positionArr[2];
		        
		        $form->setDefault('rackId',     $rackId);
		        $form->setDefault('rackRow',    $rackRow);
		        $form->setDefault('rackColumn', $rackColumn);                

                // Действие
                $form->setDefault('do', 'palletMove');
        		break;
        }
    }
    
    
    /**
     *  Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            // bp($form->rec->do);
            // bp($form->rec->palletId);
            
        	$rec = $form->rec;
        	
        	switch ($rec->do) {
        		case "palletUp":
			        // проверка за insert/update
			        if (self::fetchField("#palletId={$rec->palletId}", 'id')) {
			            $rec->id = self::fetchField("#palletId={$rec->palletId}", 'id');
			        }
			        
			        $rackId           = $rec->rackId;
			        $rackRow          = $rec->rackRow;
			        $rackColumn       = $rec->rackColumn;
			        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
			        $rec->state       = 'pending';
			        
			        // Проверка дали има палет с тази или към тази позиция
			        bp(self::checkPalletFreePosition($rec->positionNew));
			        
			        /*
			        self::save($rec);
			        
			        $recPallets              = store_Pallets::fetch($palletId);
			        $recPosition             = 'На пода';
			        $recPallets->state       = 'pending';
			        
			        store_Pallets::save($recPallets);
			        
			        return new Redirect(array('store_Pallets', 'List'));
			        */        			
        			break;
        		case "palletMoveDown":
        			
        			break;  

        		case "palletMove":
        			
        			break;        			
        	}
        }
    	
    /*    if (empty($form->rec->num)) {
            return;
        }

        // Изчисление на FNC поле "isSynthetic"
        $this->on_CalcIsSynthetic($mvc, $form->rec);

        if (!$this->isUniquenum($form->rec)) {
            $form->setError('num', 'Съществува сметка с този номер');
        }
    */    
    }

    
    /*
     * Преместване на палет от пода
     */
    function act_PalletMoveUp()
    {
        $palletId = Request::get('palletId', 'int');
        
        $rec = new stdClass;
                
        // проверка за insert/update
        if (self::fetchField("#palletId={$palletId}", 'id')) {
            $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        }
        
        $rec->palletId    = $palletId;
        $rackId           = Request::get('rackId', 'int');
        $rackRow          = Request::get('rackRow');
        $rackColumn       = Request::get('rackColumn', 'int');
        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
        
        $rec->state     = 'pending';
        
        // Проверка дали има палет с тази или към тази позиция
        $this->checkPalletFreePosition($rec->positionNew);
        
        self::save($rec);
        
        $recPallets              = store_Pallets::fetch($palletId);
        $recPosition             = 'На пода';
        $recPallets->state       = 'pending';
        
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));
    }

    
    /**
     * Форма за преместване на палет на пода
     */
    function act_PalletMoveDown()
    {
    	$palletId = Request::get('palletId', 'int');
        
        $rec = new stdClass;
        
        // проверка за insert/update
        if (self::fetchField("#palletId={$palletId}", 'id')) {
            $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        }        
        
        $rec->palletId = $palletId;
        $position      = store_Pallets::fetchField("id={$palletId}", 'position');
        
        $rec->positionNew = 'На пода';
        $rec->state       = 'pending';
        
        self::save($rec);
        
        $recPallets = store_Pallets::fetch($palletId);
        $recPallets->state = 'pending';
        
        store_Pallets::save($recPallets);

        return new Redirect(array('store_Pallets', 'List'));        
    }
    
    
    /**
     * Форма за преместване на палет на стелажа
     */
    function act_PalletMove()
    {
        $palletId = Request::get('palletId', 'int');
        
        $rec = new stdClass;
                
        // проверка за insert/update
        if (self::fetchField("#palletId={$palletId}", 'id')) {
            $rec->id = self::fetchField("#palletId={$palletId}", 'id');
        }
        
        $rec->palletId    = $palletId;
        $position         = store_Pallets::fetchField("id={$palletId}", 'position');
        $rackId           = Request::get('rackId', 'int');
        $rackRow          = Request::get('rackRow');
        $rackColumn       = Request::get('rackColumn', 'int');
        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
        $rec->state       = 'pending';
        
        // Проверка дали има палет с тази или към тази позиция
        $this->checkPalletFreePosition($rec->positionNew);        

        self::save($rec);
        
        $recPallets        = store_Pallets::fetch($palletId);
        $recPallets->state = 'pending';
        
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));       
    }

    /**
     * Проверява дали дадено палет място е заето или дали има наредено движение към него  
     * 
     * @param string $position
     */
    function checkPalletFreePosition($position) {
        $palletPlaceCheckPallets   = store_Pallets::fetch("#position = '{$position}'");
        $palletPlaceCheckMovements = self::fetch("#positionNew = '{$position}' AND #state != 'closed'");
                        
        if ($palletPlaceCheckPallets || $palletPlaceCheckMovements) {
        	return "1";
        	/*
            core_Message::redirect("Има палет на това палет място <br/>или </br>има наредено движение към това палет място", 
                                   'tpl_Error', 
                                   NULL, 
                                   array('store_Pallets', 'list'));
            */                                               
        } else {
            return "2";
        }
                
    }

    
    /**
     * Сменя state в store_Movements и в store_Pallets на 'active' 
     */
    function act_SetPalletActive()
    {
        $id     = Request::get('id', 'int');
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
        $id     = Request::get('id', 'int');
        $userId = Users::getCurrent();
        
        $rec = $this->fetch($id);
        
        $recPallets = store_Pallets::fetch("#id = {$rec->palletId}");
        $recPallets->state = 'closed';
        $rec->state        = 'closed';
        $rec->positionOld = $recPallets->position; 
        $recPallets->position = $rec->positionNew;

        // bp($recPallets, $rec);
        store_Pallets::save($recPallets);
        self::save($rec);
        
        return new Redirect(array('store_Pallets', 'List'));
    }


    /**
     * Изтрива движение за палет 
     */    
    function act_DeletePalleteMovement()
    {
        $palletId = Request::get('palletId', 'int');
        
        self::delete("#palletId = {$palletId}");
        
        $recPallets = store_Pallets::fetch("#id = {$palletId}");
        $recPallets->state = 'closed';
        
        store_Pallets::save($recPallets);
        
        return new Redirect(array('store_Pallets', 'List'));
    }
    
    
    /**
     * Филтър 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view  = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->FNC('stateFilter',     'enum(pending, active, closed,)',                         'caption=Състояние');
        $data->listFilter->setDefault('stateFilter', '');
        $data->listFilter->FNC('palletIdFilter',  'key(mvc=store_Pallets, select=id, allowEmpty=true)',    'caption=Палет');
        $data->listFilter->FNC('productIdFilter', 'key(mvc=store_Products, select=name, allowEmpty=true)', 'caption=Продукт');
        
        $data->listFilter->showFields = 'stateFilter, palletIdFilter, productIdFilter';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input();

        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
        	if ($recFilter->stateFilter) {
        	   $condState = "#state = '{$recFilter->stateFilter}'";
        	}
        	
            if ($recFilter->palletIdFilter) {
               $condPalletId = "#palletId = '{$recFilter->palletIdFilter}'";
            }
                    	
            if ($recFilter->productIdFilter) {
            	// Проверка дали от този продукт има палетирано количество  
	            	if (store_Pallets::fetch("#productId = {$recFilter->productIdFilter}")) {
	                // get pallets with this product
	                $cond = "#productId = {$recFilter->productIdFilter}";
	                $queryPallets = store_Pallets::getQuery();
	                
	                while($recPallets = $queryPallets->fetch($cond)) {
	                    $palletsSqlString .= ',' . $recPallets->id;  
	                }
	                $palletsSqlString = substr($palletsSqlString, 1, strlen($palletsSqlString) - 1);
	                // END get pallets with this product
	
	                $condProductId = "#palletId IN ({$palletsSqlString})";            		
            	} else {
            		$condProductId = "1=2";
            	}
            }            
            
            if ($condState)     $data->query->where($condState);
        	if ($condPalletId)  $data->query->where($condPalletId);
        	if ($condProductId) $data->query->where($condProductId);
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