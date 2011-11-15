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
    var $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_State, plg_LastUsedKeys';

    
    /**
     *  @todo Чака за документация...
     */    
    var $lastUsedKeys = 'storeId';

    
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
    var $listFields = 'id, tools=Пулт, productId, quantity, comment, dimensions,
                       positionView, move';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    

    /**
     *  @todo Чака за документация...
     */
    var $details = 'store_PalletDetails';
        
    
    function description()
    {
        $this->FLD('storeId',       'key(mvc=store_Stores,select=name)',    'caption=Място->Склад,input=hidden');
    	$this->FLD('productId',     'key(mvc=store_Products, select=name)', 'caption=Продукт');
        $this->FLD('quantity',      'int',                                  'caption=Количество');
        $this->FLD('comment',       'varchar',                              'caption=Коментар');
        $this->FLD('width',         'double(decimals=2)',                   'caption=Дименсии (Max)->Широчина [м]');
        $this->FLD('depth',         'double(decimals=2)',                   'caption=Дименсии (Max)->Дълбочина [м]');
        $this->FLD('height',        'double(decimals=2)',                   'caption=Дименсии (Max)->Височина [м]');
        $this->FLD('maxWeight',     'double(decimals=2)',                   'caption=Дименсии (Max)->Тегло [kg]');
        $this->FNC('dimensions',    'varchar(255)',                         'caption=Габарити');
        $this->FLD('state',         'enum(waiting=Чакащ движение,
                                          active=Работи се, 
                                          closed=На място)',                'caption=Състояние');
        $this->FLD('position',      'varchar(16)',                          'caption=Позиция->Текуща');
        $this->FNC('positionView',  'varchar(16)' ,                         'caption=Палет място');
        $this->FNC('move',          'varchar(64)',                          'caption=Местене');
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
        if ($rec->id && ($action == 'delete')) {
            $rec = $mvc->fetch($rec->id);
            
            if ($rec->state != 'closed') {
                $requiredRoles = 'no_one';
            }
        }  
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
        
        $data->title = "Палети в СКЛАД № {$selectedStoreId}";
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
     * positionView и move - различни варианти в зависимост от position, positinNew и state 
     *  
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Imgages
        $imgUp   = ht::createElement('img', array('src' => sbf('img/up.gif',   ''),         'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));
        $imgDown = ht::createElement('img', array('src' => sbf('img/down.gif', ''),         'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));
        $imgMove = ht::createElement('img', array('src' => sbf('img/move.gif', ''),         'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));        
        $imgEdit = ht::createElement('img', array('src' => sbf('img/edit.png', ''),         'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;'));        
        $imgDel  = ht::createElement('img', array('src' => sbf('img/16/delete16.png',  ''), 'width' => '16px', 'height' => '16px', 'style' => 'float: right; margin-left: 5px;
                                                                                                                                                             margin-top: 2px '));
        
        if ($rec->position == 'На пода' && $rec->state == 'closed') {
            $row->positionView = 'На пода';
            $row->move = 'На място';
            $row->move .= ht::createLink($imgUp ,  array('store_Movements', 'add', 'palletId' => $rec->id, 'do' => 'palletUp'));
        }
        
        if ($rec->position != 'На пода' && $rec->state == 'closed') {
            $row->positionView = $rec->position;
            $row->move = 'На място';
            $row->move .= Ht::createLink($imgDown, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletDown'));
            $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove', 'position' => $rec->position));
        }        
        
        if ($rec->state == 'waiting') {
            $positionNew = store_Movements::fetchField("#palletId = {$rec->id}", 'positionNew');
            
            $row->positionView = $rec->position . ' -> ' . $positionNew;
            
            if ($rec->position == 'На пода' && $positionNew == 'На пода') {
                $row->positionView = '<b>Нов</b> -> На пода';
                $row->move = 'Чакащ';
            }           
            
            if ($rec->position == 'На пода' && $positionNew != 'На пода') {
                $row->move = 'Чакащ';
                // $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove'));
            }    
            
            if ($rec->position != 'На пода' && $positionNew == 'На пода') {
                $row->move = 'Чакащ';
                // $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove'));
            }           
            
            if ($rec->position != 'На пода' && $positionNew != 'На пода') {
                $row->move = 'Чакащ';
                $row->move .= " " . Ht::createLink($imgDel,  array('store_Movements', 'deletePalleteMovement', 'palletId' => $rec->id, 'do' => 'Отмяна на движение'));
                $row->move .= Ht::createLink($imgDown, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletDown'));
                $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove'));
            }

        }    

        if ($rec->state == 'active') {
            $positionNew = store_Movements::fetchField("#palletId = {$rec->id}", 'positionNew');
            
            if ($rec->position == 'На пода' && $positionNew == 'На пода') {
                $row->positionView = '<b>Нов</b> -> На пода';   
            } else {
                $row->positionView = $rec->position . ' -> ' . $positionNew;
            }
            
            $row->move = 'Зает';
        }

        $row->dimensions = number_format($rec->width, 2) . "x" . number_format($rec->depth, 2) . "x" . number_format($rec->height, 2) . " м, " . $rec->maxWeight . " кг";
    }

    
    /**
     * Премахва бутона за добавяне
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     * @param stdClass $rec
     */
    function on_AfterPrepareListToolbar($mvc, $data, $rec)
    {
        $data->toolbar->removeBtn('btnAdd');
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
        expect($productId = Request::get('productId', 'int'));
    	
        // По подразбиране за нов запис
        if (!$data->form->rec->id) {
	        // storeId
	        $selectedStoreId = store_Stores::getCurrent();
	        $data->form->setDefault('storeId', $selectedStoreId);
	
	        // Брой палети
	        $data->form->FNC('palletsCnt', 'int', 'caption=Брой палети');

	        // Как да се постави палета
	        $data->form->FNC('palletPlaceHowto', 'varchar(64)', 'caption=Позициониране');

            $palletPlaceHowto = array('На пода'     => 'На пода',
                                      'Автоматично' => 'Автоматично');
        
            $data->form->setSuggestions('palletPlaceHowto', $palletPlaceHowto);            
            
        	$data->form->setDefault('productId', $productId);
        	
            $data->form->setDefault('width', 1.80);           
            $data->form->setDefault('depth', 1.80);
            $data->form->setDefault('height', 2.20);
            $data->form->setDefault('maxWeight', 250.00);
            
            $data->form->setDefault('palletsCnt', 1);
            
            $data->form->setField('position', 'caption=Позиция');
            $data->form->setHidden('position', 'На пода');
            
            $data->form->setDefault('quantity', 10000);
         } 
        
        $data->form->showFields = 'productId, quantity, palletsCnt, comment, width, depth, height, maxWeight, palletPlaceHowto';
    }

    
    /**
     *  Извиква се след въвеждането на данните във формата ($form->rec)
     *  Прави проверка за количеството (дали има достатъчно от продукта за палетиране) при add
     *  
     *  @param core_Mvc $mvc
     *  @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted() && (!$form->rec->id)) {
        	// Проверка за количеството
            $selectedStoreId = store_Stores::getCurrent();
            
            $rec = $form->rec;
            
        	if (store_Pallets::checkProductQuantity($selectedStoreId, $rec) === FALSE) {
           	    $form->setError('quantity,palletsCnt', 'Наличното неплатирано количество от този 
           	                                            продукт в склада не е достатъчно за 
           	                                            изпълнение на заявената операция');
            }
            
            // Проверка в зависимост от начина на определяне на палет мястото
            switch ($rec->palletPlaceHowto) {
            	case "Автоматично":
            		break;
            		
                case "На пода":
                    break;   

                // Палет мястото е въведено ръчно    
                default:
                	$rec->palletPlaceHowto = type_StorePalletPlace::toVerbal($rec->palletPlaceHowto);
                	
                	if (!$rec->palletPlaceHowto) {
                	    $form->setError('palletPlaceHowto', 'Неправилно въведено палет място'); 
                	    break;               	   
                	}
                	
                	$positionArr = explode("-", $rec->palletPlaceHowto);
			        
			        $rackId = $positionArr[0];
			        
                	if (store_Racks::isSuitable($rackId, $rec->productId, $rec->palletPlaceHowto) === FALSE) {
		                $form->setError('palletPlaceHowto', 'Палет място <b>' . $rec->palletPlaceHowto . '</b> не може да бъде използвано !');
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
    	// При add на нов палет
    	if (!$rec->id) {
  	        // 
    		$rec->newRec = 'Yes';
    		$selectedStoreId = store_Stores::getCurrent();
  	        
    		// Ако ръчно е зададено палет място, броя на палетите автоматично става 1
    		if ($rec->palletPlaceHowto != 'На пода' && $rec->palletPlaceHowto != 'Автоматично') {
    		    $rec->palletsCnt = 1;	
    		}
    		
    		// Проверка за количеството
            if (store_Pallets::checkProductQuantity($selectedStoreId, $rec) === FALSE) {
                core_Message::redirect("Междувременно е палетирано от този продукт
                                        и наличното непалетирано количество в склада не е достатъчно 
                                        за извъшването на тази операция", 
                                       'tpl_Error', 
                                       NULL, 
                                       array("store_Products"));
            } else {
            	// При достатъчно количество за пакетиране
            	switch ($rec->palletPlaceHowto) {
            		case 'Автоматично':
                        $rec->state    = 'waiting';
                        $rec->position = 'На пода';
            
                        if ($rec->palletsCnt > 1) {
                            for ($i = 0; $i < $rec->palletsCnt; $i++) {
                                $recSave = clone ($rec);
                                $recSave->palletsCnt = 0;
                        
                                store_Pallets::save($recSave);
                            }
                    
                            return FALSE;
                        }
                        break;
            			
            		case 'На пода':
            	        $rec->state    = 'closed';
                        $rec->position = 'На пода';
            
                        if ($rec->palletsCnt > 1) {
                            for ($i = 0; $i < $rec->palletsCnt; $i++) {
                                $recSave = clone ($rec);
                                $recSave->palletsCnt = 0;
                        
                                store_Pallets::save($recSave);
                            }
                    
                            return FALSE;
                        }            			
            			break;
            			
            		default: // Ръчно въведено палет място
                        $rec->state    = 'waiting';
                        $rec->position = 'На пода';            			
            			break;                                			            			
            	}
            }
    	}
    }
    
    
    /**
     * Актуализира количествата на продуктите (1) и създава движения (2)
     * 1. Запис в store_Products на актуалните количества след създаването на палета
     * 2. Създава ново движение в store_Movements в случай, че палета е 'Автоматично' или 'Ръчно' позициониран 
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_AfterSave($mvc, &$id, $rec)
    {
        /* Change product quantity on pallets */
    	$recProducts = store_Products::fetch($rec->productId);
    	
        $productQuantityOnPallets = store_Pallets::calcProductQuantityOnPalletes($rec->productId);
        $recProducts->quantityOnPallets = $productQuantityOnPallets;
        
        store_Products::save($recProducts);
        /* ENDOF Change product quantity on pallets */
        
        /* Създава движение за нов палет, който е 'Автоматично' позициониран */
        if ($rec->newRec == 'Yes' && $rec->palletPlaceHowto == 'Автоматично') {
            // Взема селектирания склад
            $selectedStoreId = store_Stores::getCurrent();
            
            $palletId = $rec->id;

            // Генерира автоматично палет място от стратегията
            $storeRec = store_Stores::fetch($selectedStoreId);
            $strategy = cls::getInterface('store_ArrangeStrategyIntf', $storeRec->strategy);
            $palletPlaceAuto = $strategy->getAutoPalletPlace($rec->productId);
                
            // $recMovements
            $recMovements->storeId     = $selectedStoreId;
            $recMovements->palletId    = $palletId;
            $recMovements->positionOld = NULL;
            $recMovements->positionNew = $palletPlaceAuto;
            $recMovements->state = 'waiting';

            // Записва движение
            store_Movements::save($recMovements);
        }
        /* ENDOF Създава движение за нов палет, който е 'Автоматично' позициониран */ 

        /* Създава движение за нов палет, който е 'Ръчно' позициониран */
        if ($rec->newRec == 'Yes' && $rec->palletPlaceHowto != 'Автоматично' && $rec->palletPlaceHowto != 'На пода') {
            // Взема селектирания склад
            $selectedStoreId = store_Stores::getCurrent();
            
            $palletId = $rec->id;
            
            // $recMovements
            $recMovements->storeId     = $selectedStoreId;
            $recMovements->palletId    = $palletId;
            $recMovements->positionOld = NULL;
            $recMovements->positionNew = $rec->palletPlaceHowto;
            $recMovements->state = 'waiting';

            // Записва движение
            store_Movements::save($recMovements);
        }
        /* ENDOF Създава движение за нов палет, който е 'Автоматично' позициониран */        
    }
    
    
    /**
     * Изчислява количестовото от даден продукт на палети
     * 
     * @param int $productId
     * @return int $productQuantityOnPallets
     */
    private function calcProductQuantityOnPalletes($productId) {
        $query = $this->getQuery();
        $where = "#productId = {$productId}";
        
        $productQuantityOnPallets = 0;
        
        while($rec = $query->fetch($where)) {
        	$productQuantityOnPallets += $rec->quantity;
        }

        return $productQuantityOnPallets;
    }

    
    /**
     * Проверка преди палетиране дали има достатъчно количество от продукта (непалетирано)
     * 
     * @param $rec
     */
    function checkProductQuantity($selectedStoreId, $rec) {
        // Новото количество (общо), кето ще се палетира  
    	$quantityOnPalletsAdd = $rec->quantity * $rec->palletsCnt;

    	// Изчисляване на наличното непалетирано количество
        $quantityInStore = store_Products::fetchField("#id = {$rec->productId} AND
                                                                #storeId = {$selectedStoreId}", 'quantity');
                
        $quantityInStoreOnPallets = store_Products::fetchField("#id = {$rec->productId} AND
                                                                #storeId = {$selectedStoreId}", 'quantityOnPallets');
                        
        $quantityInStoreNotOnPallets = $quantityInStore - $quantityInStoreOnPallets;
        // END Изчисляване на наличното непалетирано количество

        if ($quantityInStoreNotOnPallets < $quantityOnPalletsAdd) {
            return FALSE;   
        } else {
            return TRUE;
        }
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
    	   $query->deleteRecId = $rec->id;
    	   $query->deleteRecProductId = $rec->productId;
        }
    }
    
    
    /**
     *  Ако е минала проверката за state в on_BeforeDelete, след като е изтрит записа изтриваме всички движения за него
     *  
     *  @param core_Mvc $mvc
     *  @param int $numRows
     *  @param stdClass $query
     *  @param string $cond
     */
    function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        store_Movements::delete("#palletId = {$query->deleteRecId}");
        
        // Calc store_Products quantity on pallets
        $recProducts = store_Products::fetch($query->deleteRecProductId);
        
        $productQuantityOnPallets = store_Pallets::calcProductQuantityOnPalletes($query->deleteRecProductId);
        $recProducts->quantityOnPallets = $productQuantityOnPallets;
        store_Products::save($recProducts); 

        return new Redirect(array($this));
    }
    
        
    function getPalletsInStore()
    {
        $selectedStoreId = store_Stores::getCurrent();
           
        $queryPallets = store_Pallets::getQuery();
        $where = "#storeId = {$selectedStoreId}";

        while($recPallets = $queryPallets->fetch($where)) {
            // Само тези палети, които са 'На място' и не са 'На пода'
            if ($recPallets->position != 'На пода' && $recPallets->state == 'closed') {
                $positionArr = explode("-", $recPallets->position);
                
                $rackId     = $positionArr[0];
                $rackRow    = $positionArr[1];
                $rackColumn = $positionArr[2];
                
                $palletPosition   = $rackId . "-" . $rackRow . "-" . $rackColumn;
                $palletDimensions = number_format($recPallet->width, 2) . "x" . number_format($recPallets->depth, 2) . "x" . number_format($recPallets->height, 2) . " м, max " . $recPallets->maxWeight . " кг";
                
                $recProducts = store_Products::fetch("#id = {$recPallets->productId}");
                $productName = cat_Products::fetchField("#id = {$recProducts->name}", 'name');
                
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId'] = $recPallets->id;

                // title 
                $title = "Продукт ID " . $recProducts->id . ", " . $productName . ", " . $recPallets->quantity . " бр., палет ID: " . $recPallets->id;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title'] = $title;              
            }

            // Палетите, за които има наредено преместване
            if ($recPallets->state != 'closed') {
                $where = "#storeId = {$selectedStoreId} 
                          AND #palletId = {$recPallets->id}
                          AND #state != 'closed'";
            	
            	$position = store_Movements::fetchField($where, 'positionNew');
            	
            	$positionArr = explode("-", $position);
            	
                $rackId     = $positionArr[0];
                $rackRow    = $positionArr[1];
                $rackColumn = $positionArr[2];
                
                $palletPosition   = $rackId . "-" . $rackRow . "-" . $rackColumn;
                
                $recProducts = store_Products::fetch("#id = {$recPallets->productId}");
                $productName = cat_Products::fetchField("#id = {$recProducts->name}", 'name');
                
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId'] = $recPallets->id;

                // title 
                $title = "Продукт ID " . $recProducts->id . ", " . $productName . ", " . $recPallets->quantity . " бр., палет Id: " . $recPallets->id;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title'] = $title;            	
            }
        }
        
        return $palletsInStoreArr;
    }
    
    
    /**
     * Проверява дали дадено палет място е заето или дали има наредено движение към него  
     * 
     * @param string $position
     * @return boolean
     */
    public static function checkIfPalletPlaceIsFree($position) {
        $selectedStoreId = store_Stores::getCurrent();
        
        $palletPlaceCheckPallets   = store_Pallets::fetch("#position = '{$position}' 
                                                           AND #storeId = {$selectedStoreId}");
        $palletPlaceCheckMovements = store_Movements::fetch("#positionNew = '{$position}' 
                                                             AND #state != 'closed'
                                                             AND #storeId = {$selectedStoreId}");
        
        if ($palletPlaceCheckPallets || $palletPlaceCheckMovements) {
            return FALSE;
        } else return TRUE;
    }    
    
}