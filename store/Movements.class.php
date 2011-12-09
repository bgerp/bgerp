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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id && ($action == 'delete')) {
            $rec = $mvc->fetch($rec->id);
            
            if ($rec->state != 'closed') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($rec->id && ($action == 'edit')) {
           if ($do = Request::get('do')) {
               if ($do == 'palletMove') {
                   $requiredRoles = 'store,admin';                 
               }
            } else {
               $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'add') {
        	if ($do = Request::get('do')) {
        	   if ($do == 'palletMove') {
                   $requiredRoles = 'store,admin';        	       
        	   }
        	} else {
        	   $requiredRoles = 'no_one';
        	}
        }        
    }
    
    
    /**
     * Смяна на заглавието
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->title = "Движения на палети в СКЛАД № {$selectedStoreId}";
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
        	   $row->state .= Ht::createBtn('Отказ', array($mvc, 'denyPalletMovement', $rec->id));
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
        
        /* $row->positionView */
       	$position = store_Pallets::fetchField("#id = {$rec->palletId}", 'position');
       	
       	if ($position != 'На пода') {
            $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($position);
            $position = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);
       	}
       	
       	if ($rec->positionNew != 'На пода') {
            $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($rec->positionNew);
            $row->positionNew = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);       	    
       	} else {
       		$row->positionNew = 'На пода';
       	}

        if ($rec->positionOld != 'На пода' && $rec->positionOld != NULL) {
            $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($rec->positionOld);
            $row->positionOld = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);                           
        } else if ($rec->positionOld == 'На пода') {
        	$row->positionOld = 'На пода';
        }      	
       	
       	if ($rec->state == 'waiting' || $rec->state == 'active') {
       	    $row->positionView = $position . " -> " . $row->positionNew;
       	} else {
       	    $row->positionView = $row->positionOld . " -> " . $row->positionNew;
       	}
       	/* ENDOF $row->positionView */
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
        
    	$palletId  = Request::get('palletId', 'int');
    	$productId = store_Pallets::fetchField($palletId, 'productId');
    	
    	$do = Request::get('do');
    	
        switch ($do) {
        	case 'palletUp':
   		        $form->title = "КАЧВАНЕ <b>от пода</b> на палет с|* ID=<b>{$palletId}</b>";
		        $form->FNC('do', 'varchar(64)', 'caption=Движение,input=hidden');
		        
	            // Как да се постави палета
	            $form->FNC('palletPlaceHowto', 'varchar(64)', 'caption=Позициониране');
	
	            $palletPlaceHowto = array(''            => '',
	                                      'Автоматично' => 'Автоматично');
	        
	            $form->setSuggestions('palletPlaceHowto', $palletPlaceHowto);		        
		        
		        $form->showFields = 'palletPlaceHowto';
		        
		        $form->setHidden('palletId', $palletId);
		        $form->setHidden('state', 'waiting');
		        
		        // Действие
		        $form->setHidden('do', 'palletUp');
        		break;
        		
        	case 'palletDown':
                $position = store_Pallets::fetchField("#id = {$palletId}", 'position');
                
	            $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($position);
	            $position = $ppRackId2RackNumResult['position'];
	            unset($ppRackId2RackNumResult);            
                
                $form->title = "СВАЛЯНЕ |*<b>|на пода|*</b>| на палет с|* ID=<b>{$palletId}</b>
                                <br/>|от палет място |*<b>{$position}</b>|";
                $form->FNC('do', 'varchar(64)', 'caption=Движение,input=hidden');
                
                
                $form->showFields = 'positionNew';
                
                $form->setHidden('positionNew', 'На пода');
                $form->setHidden('palletId', $palletId);
                $form->setHidden('state', 'waiting');
                
                // Действие
                $form->setHidden('do', 'palletDown');                
        		break;
    
        	case 'palletMove':
        		$position = store_Pallets::fetchField("#id = {$palletId}", 'position');
        		
        		if ($position != 'На пода') {
	                $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($position);
	                $position = $ppRackId2RackNumResult['position'];
	                unset($ppRackId2RackNumResult);            
        		}
        		 
                $form->title = "ПРЕМЕСТВАНЕ от палет място <b>{$position}</b> на палет с|* ID=<b>{$palletId}</b>
                                <br/>към друго палет място в склада";
                $form->FNC('do', 'varchar(64)', 'caption=Движение,input=hidden');
                
                // Как да се постави палета
                $form->FNC('palletPlaceHowto', 'varchar(64)', 'caption=Позициониране');
    
                $palletPlaceHowto = array(''            => '',
                                          'Автоматично' => 'Автоматично');
            
                $form->setSuggestions('palletPlaceHowto', $palletPlaceHowto);             
                
                $form->showFields = 'palletPlaceHowto';
                
                $form->setHidden('palletId', $palletId);
                $form->setHidden('state', 'waiting');
                
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
        	
            // Взема селектирания склад
            $selectedStoreId = store_Stores::getCurrent();
                    	
        	$productId = store_Pallets::fetchField($rec->palletId, 'productId');
        	
            // проверка за insert/update
            if ($mvc->fetchField("#palletId={$rec->palletId}", 'id')) {
                $rec->id = $mvc->fetchField("#palletId={$rec->palletId}", 'id');
            }
        	
        	switch ($rec->do) {
        		case "palletUp":
        	        // Проверка в зависимост от начина на определяне на палет мястото
        	        
		            switch ($rec->palletPlaceHowto) {
		                case "Автоматично":
				            // Генерира автоматично палет място от стратегията
				            $storeRec = store_Stores::fetch($selectedStoreId);
				            $strategy = cls::getInterface('store_ArrangeStrategyIntf', $storeRec->strategy);
				            $palletPlaceAuto = $strategy->getAutoPalletPlace($productId);
				            
				            if ($palletPlaceAuto == NULL) {
                                $form->setError('palletPlaceHowto', 'Автоматично не може да бъде предложено палет място в склада');
				            } else {
				                $rec->positionNew = $palletPlaceAuto;
				            }
				            break;
		                    
		                // Палет мястото е въведено ръчно    
		                default:
		                	$rec->palletPlaceHowto = store_type_PalletPlace::fromVerbal($rec->palletPlaceHowto);
		                    
		                    if ($rec->palletPlaceHowto === FALSE) {
		                        $form->setError('palletPlaceHowto', 'Неправилно въведено палет място'); 
		                        break;                     
		                    }
		                    
		                    $ppRackNum2rackIdResult = store_Racks::ppRackNum2rackId($rec->palletPlaceHowto);
		                    
		                    if ($ppRackNum2rackIdResult[0] === FALSE) {
		                    	$form->setError('palletPlaceHowto', 'Няма стелаж с въведения номер');
		                    	break;
		                    } else {
		                        $rec->palletPlaceHowto = $ppRackNum2rackIdResult['position'];
		                    }
                            		                    
                            $rackId = $ppRackNum2rackIdResult['rackId'];                            		                    		                    
		                     
		                    $isSuitableResult = store_Racks::isSuitable($rackId, $productId, $rec->palletPlaceHowto); 
		                    
		                    if ($isSuitableResult[0] === FALSE) {
		                        $fErrors = $isSuitableResult[1];
		                        store_Pallets::prepareErrorsAndWarnings($fErrors, $form);
		                    } else {
		                        $rec->positionNew = $rec->palletPlaceHowto;  
                                $rec->positionOld = 'На пода';
		                    }    
		                    break;
		            }        			
        			break;
        			
        		case "palletDown":
			        $rec->positionNew = 'На пода';
			        $rec->state       = 'waiting';
        			break;  

        		case "palletMove":
                    // Проверка в зависимост от начина на определяне на палет мястото
                    
                    switch ($rec->palletPlaceHowto) {
                        case "Автоматично":
                            // Генерира автоматично палет място от стратегията
                            $storeRec = store_Stores::fetch($selectedStoreId);
                            $strategy = cls::getInterface('store_ArrangeStrategyIntf', $storeRec->strategy);
                            $palletPlaceAuto = $strategy->getAutoPalletPlace($productId);
                            
                            if ($palletPlaceAuto == NULL) {
                                $form->setError('palletPlaceHowto', 'Автоматично не може да бъде предложено палет място в склада');                            
                            } else {
                                $rec->positionNew = $palletPlaceAuto;
                            }                            
                            break;
                            
                        // Палет мястото е въведено ръчно    
                        default:
                            $rec->palletPlaceHowto = store_type_PalletPlace::fromVerbal($rec->palletPlaceHowto);
                            
                            if ($rec->palletPlaceHowto === FALSE) {
                                $form->setError('palletPlaceHowto', 'Неправилно въведено палет място'); 
                                break;                     
                            }
                            
                            $ppRackNum2rackIdResult = store_Racks::ppRackNum2rackId($rec->palletPlaceHowto);
                            
                            if ($ppRackNum2rackIdResult[0] === FALSE) {
                                $form->setError('palletPlaceHowto', 'Няма стелаж с въведения номер');
                                break;
                            } else {
                                $rec->palletPlaceHowto = $ppRackNum2rackIdResult['position'];
                            }
                            
                            $rackId = $ppRackNum2rackIdResult['rackId'];                                                                                  

                            $isSuitableResult = store_Racks::isSuitable($rackId, $productId, $rec->palletPlaceHowto); 
                            
                            if ($isSuitableResult[0] === FALSE) {
                                $fErrors = $isSuitableResult[1];
                                store_Pallets::prepareErrorsAndWarnings($fErrors, $form);
                            } else {
                                $rec->positionNew = $rec->palletPlaceHowto;  
                                $rec->positionOld = $rec->position;
                            }
                            break;
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
     *  
     *  @return core_Redirect
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
     * 
     * @return core_Redirect
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
     * Сменя state в store_Movements и в store_Pallets на 'closed' 
     * 
     * @return core_Redirect
     */
    function act_DenyPalletMovement()
    {
        $id     = Request::get('id', 'int');
        $userId = Users::getCurrent();
        
        $rec = $this->fetch($id);
        
        $recPallets = store_Pallets::fetch("#id = {$rec->palletId}");
        
        $recPallets->state = 'closed';
        store_Pallets::save($recPallets);
        
        self::delete($rec->id);
        
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
    
    
    /**
     * Проверка дали за дадено палет място няма наредно движение 
     *
     * @param string $palletPlace
     * @return boolean
     */
    static function checkIfPalletPlaceHasNoAppointedMovements($palletPlace) {
        $selectedStoreId = store_Stores::getCurrent();
        
        if ($recMovements = store_Movements::fetch("#positionNew = '{$palletPlace}' AND #storeId = {$selectedStoreId}")) return FALSE;
        
        return TRUE;
    }    
    
}