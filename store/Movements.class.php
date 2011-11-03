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
    var $canDelete = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 50;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,palletId, positionView=Местене, workerId, state, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId',      'key(mvc=store_Stores, select=name)', 'caption=Склад');
        $this->FLD('palletId',     'key(mvc=store_Pallets, select=id)',  'caption=Палет,input=hidden');
        
        $this->FLD('positionOld',  'varchar(32)',                       'caption=Палет място->Старо');
        $this->FNC('position',     'varchar(32)',                       'caption=Палет място->Текущо');
        $this->FLD('positionNew',  'varchar(32)',                       'caption=Палет място->Ново');
        
        $this->FLD('state',        'enum(waiting, active, closed)',      'caption=Състояние, input=hidden');
        // $this->XPR('orderBy',      'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'active' THEN 2 WHEN 'closed' THEN 3 END)");
        $this->FLD('workerId',     'key(mvc=core_Users, select=names)',  'caption=Товарач');
    }
    

    /**
     * Смяна на заглавието
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     * @
     */
    function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->title = "Движения на палети в СКЛАД № {$selectedStoreId}";
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
        $data->query->orderBy('state');
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
    	// $row->state
    	switch($rec->state) {
        	case 'waiting':
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
        
        // $row->positionView
       	$position = store_Pallets::fetchField("#id = {$rec->palletId}", 'position');
       	
       	if ($rec->state == 'waiting' || $rec->state == 'active') {
       	    $row->positionView = $position . " -> " . $rec->positionNew;
       	} else {
       	    $row->positionView = $rec->positionOld . " -> " . $rec->positionNew;
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
   		        
		        // Палет място
		        $form->FNC('rackId',     'key(mvc=store_Racks,select=id)', 'caption=Палет място (ново)->Стелаж,input');
		        $form->FNC('rackRow',    'enum(A,B,C,D,E,F,G)',            'caption=Палет място (ново)->Ред');        
		        $form->FNC('rackColumn', 'enum(1,2,3,4,5,6,7,8,9,10,
		                                       11,12,13,14,15,16,17,18,
		                                       19,20,21,22,23,24)',        'caption=Палет място (ново)->Колона');
		        $form->FNC('do',         'varchar(64)',                    'caption=Движение,input=hidden');
		        
                // Dummy
                $form->FNC('palletPlaceAuto', 'varchar', 'caption=Палет място (Auto),input');
		        
		        $form->showFields = 'position, rackId, rackRow, rackColumn, palletPlaceAuto';
		        
		        $form->setReadOnly('position', 'На пода'); /* Only for user info */
		        $form->setHidden('palletId', $palletId);
		        $form->setHidden('state', 'waiting');
		        
		        // Действие
		        $form->setHidden('do', $do);
		        
		        // Dummy
		        $selectedStoreId = store_Stores::getCurrent();
		        $storeRec = store_Stores::fetch($selectedStoreId);
		        
		        $strategy = cls::getInterface('store_ArrangeStrategyIntf', $storeRec->strategy);
                $palletPlaceAuto = $strategy->getAutoPalletPlace($palletId);
		        
		        $form->setReadOnly('palletPlaceAuto', $palletPlaceAuto);
        		break;
        		
        	case 'palletDown':
                $form->title = "СВАЛЯНЕ на пода на палет с|* ID={$palletId}";
                
                $position = store_Pallets::fetchField("#id = {$palletId}", 'position');

                $form->showFields = 'position, positionNew';
                
                $form->setReadOnly('position', $position); /* Only for user info */
                $form->setReadOnly('positionNew', 'На пода');
                $form->setHidden('palletId', $palletId);
                $form->setHidden('state', 'waiting');
                
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
                $form->FNC('do', 'varchar(64)',                            'input=hidden');                

        		$position    = store_Pallets::fetchField("#id = {$palletId}", 'position');
        		$positionNew = $mvc->fetchField("#palletId = {$palletId}", 'positionNew');
        		
        		$form->showFields = 'position, rackId, rackRow, rackColumn';
        		
                $form->setReadOnly('position', $position); /* Only for user info */
        		$form->setHidden('palletId', $palletId);
                $form->setHidden('state', 'waiting');
                
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
                $form->setHidden('do', 'palletMove');
        		break;
        }
    }
    
    
    /**
     *  Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *  
     *  @param core_Mvc $mvc
     *  @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            
        	$rec = $form->rec;
        	
	        // array letter to digit
	        $rackRowsArr = array('A' => 1,
	                             'B' => 2,
	                             'C' => 3,
	                             'D' => 4,
	                             'E' => 5,
	                             'F' => 6,
	                             'G' => 7,
	                             'H' => 8);        	
        	
            // проверка за insert/update
            if ($mvc->fetchField("#palletId={$rec->palletId}", 'id')) {
                $rec->id = $mvc->fetchField("#palletId={$rec->palletId}", 'id');
            }
        	
        	switch ($rec->do) {
        		case "palletUp":
			        $rackId           = $rec->rackId;
			        $rackRow          = $rec->rackRow;
			        $rackColumn       = $rec->rackColumn;
			        $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
			        $rec->state       = 'waiting';
			        
			        $palletPlace = $rackId . "-" . $rackRow . "-" .$rackColumn;
                    
			        // Проверка дали е свободна тази позиция
			        if (store_Pallets::checkIfPalletPlaceIsFree($rec->positionNew) === FALSE) {
                        $form->setError('rackId, rackRow, rackColumn', 
                                        'Има палет на това палет място или има <br/>наредено движение към това палет място');                        
                    } else {
                        // Проверка дали тази позиция не е забранена
                        if (store_RackDetails::checkIfPalletPlaceIsNotForbidden($rackId, $palletPlace) === FALSE) {
                            $form->setError('rackId, rackRow, rackColumn', 
                                            'Тази позиция на стелажа е забранена за употреба');                     
                        } else {
		                    // Проверка за допустимите продуктови групи за стелажа
		                    if (store_Racks::checkIfProductGroupsAreAllowed($rackId, $rec->palletId) === FALSE) {
		                        $form->setError('rackId, rackRow, rackColumn', 'На тази позиция на стелажа не е позволено
		                                                                        <br/>да се складира този продукт -
		                                                                        <br/><b>непозволена продуктова група (групи) за стелажа</b>');                           
		                    }
                        }                    
                    }
        			break;
        			
        		case "palletDown":
			        $rec->positionNew = 'На пода';
			        $rec->state       = 'waiting';
        			break;  

        		case "palletMove":
        			$rackId           = $rec->rackId;
                    $rackRow          = $rec->rackRow;
                    $rackColumn       = $rec->rackColumn;
			        $rec->positionOld = store_Pallets::fetchField("id = {$rec->palletId}", 'position');
                    $rec->positionNew = $rackId . "-" . $rackRow . "-" . $rackColumn;
			        $rec->state       = 'waiting';
			        
			        $palletPlace = $rackId . "-" . $rackRow . "-" .$rackColumn;
			        
                    // Проверка дали е свободна тази позиция
                    if (store_Pallets::checkIfPalletPlaceIsFree($rec->positionNew) === FALSE) {
                        $form->setError('rackId, rackRow, rackColumn', 
                                        'Има палет на това палет място или има <br/>наредено движение към това палет място');                        
                    } else {
                        // Проверка дали тази позиция не е забранена
                        if (store_RackDetails::checkIfPalletPlaceIsNotForbidden($rackId, $palletPlace) === FALSE) {
                            $form->setError('rackId, rackRow, rackColumn', 
                                            'Тази позиция на стелажа е забранена за употреба');                     
                        } else {
                            // Проверка за допустимите продуктови групи за стелажа
                            if (store_Racks::checkIfProductGroupsAreAllowed($rackId, $rec->palletId) === FALSE) {
                                $form->setError('rackId, rackRow, rackColumn', 'На тази позиция на стелажа не е позволено
                                                                                <br/>да се складира този продукт -
                                                                                <br/><b>непозволена продуктова група (групи) за стелажа</b>');                           
                            }
                        }                    
                    }
                    break;        			
        	}
        }
    }
    
    
    /**
     * При нов запис, ако броя на палетите е повече от 1
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_BeforeSave($mvc,&$id,$rec)
    {
    	$rec->storeId = store_Stores::getCurrent();    
    }    
    
    /**
     * Смяна на state-а в store_Pallets при движение на палета
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_AfterSave($mvc, &$id, $rec)
    {
    	if ($rec->do && in_array($rec->do, array('palletUp', 'palletDown', 'palletMove'))) {
    		
    		$recPallets = store_Pallets::fetch($rec->palletId);
    		
    		$recPallets->state = 'waiting';
            store_Pallets::save($recPallets);
            
            return redirect(array('store_Pallets'));
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
        
        // store_Pallets
        $recPallets = store_Pallets::fetch("#id = {$rec->palletId}");
        $recPallets->state = 'active';
        store_Pallets::save($recPallets);
        
        return new Redirect(array($this));
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


        store_Pallets::save($recPallets);
        self::save($rec);
        
        return new Redirect(array($this));
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
        $data->listFilter->FNC('stateFilter', 'enum(waiting, active, closed,)', 'caption=Състояние');
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
    
}