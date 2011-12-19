<?php
/**
 * Палети
 */
class store_Pallets extends core_Manager
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
    var $listFields = 'id=Палет, tools=Пулт, productId, quantity, dimensions, 
                       positionView=Позиция, move=Състояние';
    
    
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
        $this->FLD('label',         'varchar(64)',                             'caption=Етикет');
        $this->FLD('storeId',       'key(mvc=store_Stores,select=name)',       'caption=Място->Склад,input=hidden');
        $this->FLD('productId',     'key(mvc=store_Products, select=name)',    'caption=Продукт');
        $this->FLD('quantity',      'int',                                     'caption=Количество');
        $this->FLD('comment',       'varchar',                                 'caption=Коментар');
        $this->FLD('dimensions',    'key(mvc=store_PalletTypes,select=title)', 'caption=Габарити');
        $this->FLD('state',         'enum(waiting=Чакащ движение,
                                          active=Работи се, 
                                          closed=На място)',                'caption=Състояние');
        $this->FLD('position',      'varchar(16)',                          'caption=Позиция->Текуща');
        // $this->FNC('positionView',  'varchar(16)' ,                         'caption=Палет място');
        // $this->FNC('move',          'varchar(64)',                          'caption=Местене');
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
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене на палет в склада';
        $data->listFilter->view  = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->FNC('productIdFilter', 'key(mvc=store_Products, select=name, allowEmpty=true)', 'caption=Продукт');

        $data->listFilter->showFields = 'productIdFilter';

        // Активиране на филтъра
        $recFilter = $data->listFilter->input();

        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
            if ($recFilter->productIdFilter) {
                $cond = "#productId = {$recFilter->productIdFilter}";
            	$data->query->where($cond);
            }
        }
    }    

    
    /**
     * positionView и move - различни варианти в зависимост от position, positionNew и state 
     *  
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	// Дефинираме иконките, които ще използваме
        $imgUp   = ht::createElement('img', array('src' => sbf('img/up.gif',   ''),         'width' => '16px', 'height' => '16px', 
                                                                                            'style' => 'float: right; margin-left: 5px;'));
        $imgDown = ht::createElement('img', array('src' => sbf('img/down.gif', ''),         'width' => '16px', 'height' => '16px', 
                                                                                            'style' => 'float: right; margin-left: 5px;'));
        $imgMove = ht::createElement('img', array('src' => sbf('img/move.gif', ''),         'width' => '16px', 'height' => '16px', 
                                                                                            'style' => 'float: right; margin-left: 5px;'));        
        $imgEdit = ht::createElement('img', array('src' => sbf('img/edit.png', ''),         'width' => '16px', 'height' => '16px', 
                                                                                            'style' => 'float: right; margin-left: 5px;'));        
        $imgDel  = ht::createElement('img', array('src' => sbf('img/16/delete16.png',  ''), 'width' => '16px', 'height' => '16px', 
                                                                                            'style' => 'float: right; margin-left: 5px;
                                                                                                                      margin-top: 2px '));
        // ENDOF Дефинираме иконките, които ще използваме
        
        // id и label
        if ($rec->label != '') {
        	$row->id  = $rec->label;
        } else {
            $row->id  = '#' . $row->id;
        }
        
        // comment
        if ($rec->comment != '') {
        	$row->id .= '<p style=\'clear: left; max-width: 120px; color: #555555; font-size: 12px;\'>' . $rec->comment . '</p>';
        }
        
        // Ако state е 'closed' и позицията е 'На пода'
        /* if ($rec->state == 'closed' && $rec->position == 'На пода') { */
        if ($rec->state == 'closed' && preg_match("/^Зона:/u", $rec->position)) {        	
            $row->positionView = $rec->position;
            $row->move = 'На място';
            $row->move .= ht::createLink($imgUp ,  array('store_Movements', 'add', 'palletId' => $rec->id, 'do' => 'palletUp'));
        }
        
        // Ако state е 'closed' и позицията не е 'На пода'
        /* if ($rec->state == 'closed' && $rec->position != 'На пода') { */
        if ($rec->state == 'closed' && !preg_match("/^Зона:/u", $rec->position)) {	
            $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($rec->position);
            $row->position = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);        	
        	
            $row->positionView = $row->position;
            $row->move = 'На място';
            $row->move .= Ht::createLink($imgDown, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletDown'));
            $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove', 'position' => $rec->position));
        }        

        // Ако state е 'waiting'
        if ($rec->state == 'waiting') {
        	$positionNew = store_Movements::fetchField("#palletId = {$rec->id}", 'positionNew');
        	
            /* if ($positionNew != 'На пода') { */
            if (!preg_match("/^Зона:/u", $positionNew)) {	
	            $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($positionNew);
	            $positionNew = $ppRackId2RackNumResult['position'];
	            unset($ppRackId2RackNumResult);
	            $row->move = 'Чакащ';
            }
            
            // if ($rec->position != 'На пода') {
            if (!preg_match("/^Зона:/u", $rec->position)) {    	
                $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($rec->position);
                $row->position = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);            
            }
            
            // if ($rec->position == 'На пода') {
            if (preg_match("/^Зона:/u", $rec->position)) {
            	// $row->position = 'На пода';
            	$row->position = $rec->position;
            }            
            
            $row->positionView = $row->position . ' -> ' . $positionNew;

            // if ($rec->position == 'На пода' && $positionNew == 'На пода') {
            if (preg_match("/^Зона:/u", $rec->position) && preg_match("/^Зона:/u", $positionNew)) {
                // $row->positionView = '<b>Нов</b> -> На пода';
                $row->positionView = '<b>Нов</b> -> ' . $positionNew;
                $row->move = 'Чакащ';
            }           
            
            // if ($rec->position == 'На пода' && $positionNew != 'На пода') {
            if (preg_match("/^Зона:/u", $rec->position) && !preg_match("/^Зона:/u", $positionNew)) {
                $row->move = 'Чакащ';
            }    
            
            // if ($rec->position != 'На пода' && $positionNew == 'На пода') {
            if (!preg_match("/^Зона:/u", $rec->position) && preg_match("/^Зона:/u", $positionNew)) {
                $row->move = 'Чакащ';
            }           
            
            // if ($rec->position != 'На пода' && $positionNew != 'На пода' && $rec->state == 'closed') {
            if (!preg_match("/^Зона:/u", $rec->position) && 
                !preg_match("/^Зона:/u", $positionNew) && 
                $rec->state == 'closed') {
                $row->move = 'Чакащ';
                $row->move .= " " . Ht::createLink($imgDel,  array('store_Movements', 'deletePalleteMovement', 'palletId' => $rec->id, 'do' => 'Отмяна на движение'));
                $row->move .= Ht::createLink($imgDown, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletDown'));
                $row->move .= " " . Ht::createLink($imgMove, array('store_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove'));
            }
        }    
        
        // Ако state e 'active'
        if ($rec->state == 'active') {
            $positionNew = store_Movements::fetchField("#palletId = {$rec->id}", 'positionNew');
            
            // if ($positionNew != 'На пода') {
            if (!preg_match("/^Зона:/u", $positionNew)) {	
                $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($positionNew);
                $positionNew = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);            
            }
            
            // if ($rec->position != 'На пода') {
            if (!preg_match("/^Зона:/u", $rec->position)) {	
                $ppRackId2RackNumResult = store_Racks::ppRackId2RackNum($rec->position);
                $row->position = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);            
            } else {
               // $row->position = 'На пода';
               $row->position = $rec->position;
            }            
            
            // if ($row->position == 'На пода' && $positionNew == 'На пода') {
            if (preg_match("/^Зона:/u", $row->position) && preg_match("/^Зона:/u", $positionNew)) {
                $row->positionView = '<b>Нов</b> -> ' . $positionNew;   
            } else {
            	$row->positionView = $row->position . ' -> ' . $positionNew;
            }
            
            $row->move = 'Зает';
        }

        // dimensions
        // $row->dimensions = number_format($rec->width, 2) . "x" . number_format($rec->depth, 2) . "x" . number_format($rec->height, 2) . " м, " . $rec->maxWeight . " кг";
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

            // Избор на зона
            $data->form->FNC('zone', 'varchar(64)', 'caption=Позициониране->1. Палетиране в зона');            
            
            // Как да се постави палета
            $data->form->FNC('palletPlaceHowto', 'varchar(64)', 'caption=Позициониране->2. Генериране на движение<br/>към позиция');
            
            // Подготвя $palletPlaceHowto suggestions
            $palletPlaceHowto = array(''            => '',
                                      'Автоматично' => 'Автоматично');
            
            $data->form->setSuggestions('palletPlaceHowto', $palletPlaceHowto);
            // ENDOF Подготвя $palletPlaceHowto suggestions
            
            // Подготвя zones suggestions
	        $queryZones = store_Zones::getQuery();
	        $where = "#storeId = {$selectedStoreId}";
	        
	        while($recZones = $queryZones->fetch($where)) {
	           $zones[$recZones->code] = $recZones->comment;
	        }
	        
	        unset($queryZones, $where, $recZones);
	        
	        $data->form->setOptions('zone', $zones);
            // ENDOF Подготвя zones suggestions
            
            $data->form->setDefault('productId', $productId);
            
            $data->form->setDefault('palletsCnt', 1);
            
            /*
            $data->form->setField('position', 'caption=Позиция');
            $data->form->setHidden('position', 'На пода');
            */
            
            $data->form->setDefault('quantity', 10000);
         } 
        
        $data->form->showFields = 'label, productId, quantity, palletsCnt, comment, dimensions, zone, palletPlaceHowto';
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
            
            if (store_Pallets::checkProductQuantity($rec) === FALSE) {
                $form->setError('quantity,palletsCnt', 'Наличното непалетирано количество от този продукт|* 
                                                        <br/>|в склада не е достатъчно за изпълнение|*
                                                        <br/>|на заявената операция');
            }
            
            // Проверка в зависимост от начина на определяне на палет мястото
            switch ($rec->palletPlaceHowto) {
                case "Автоматично":
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
	                    
                    $isSuitableResult = store_Racks::isSuitable($rackId, $rec->productId, $rec->palletPlaceHowto); 
	                    
                    if ($isSuitableResult[0] === FALSE) {
                        $fErrors = $isSuitableResult[1];
                        store_Pallets::prepareErrorsAndWarnings($fErrors, $form);
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
            $rec->newRec = TRUE;
            $selectedStoreId = store_Stores::getCurrent();
            
            // Ако ръчно е зададено палет място, броя на палетите автоматично става 1
            if ($rec->palletPlaceHowto != 'Автоматично') {
                $rec->palletsCnt = 1;   
            }
            
             // Проверка за количеството
            if (store_Pallets::checkProductQuantity($rec) === FALSE) {
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
                        $rec->position = 'Зона: ' . $rec->zone;
            
                        if ($rec->palletsCnt > 1) {
                            for ($i = 0; $i < $rec->palletsCnt; $i++) {
                                $recSave = clone ($rec);
                                $recSave->palletsCnt = 0;
                        
                                store_Pallets::save($recSave);
                            }
                    
                            return FALSE;
                        }
                        break;

                    default: // Ръчно въведено палет място или на пода в зона
                        if ($rec->palletPlaceHowto != '' && $rec->palletPlaceHowto != 'Автоматично') {
                        	// Ръчно въведено
	                        $rec->state    = 'waiting';
	                        $rec->position = 'Зона: ' . $rec->zone;                        
                        } else {
                        	// На пода в зона
	                        $rec->state    = 'closed';
	                        $rec->position = 'Зона: ' . $rec->zone;
	            
	                        if ($rec->palletsCnt > 1) {
	                            for ($i = 0; $i < $rec->palletsCnt; $i++) {
	                                $recSave = clone ($rec);
	                                $recSave->palletsCnt = 0;
	                        
	                                store_Pallets::save($recSave);
	                            }
	                    
	                            return FALSE;
	                        }                       
                        }

                        break;                                                                  
                }
            }
        }
        
        // bp($rec);
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
        if ($rec->newRec == TRUE) {
            /* Change product quantity on pallets */
            $recProducts = store_Products::fetch($rec->productId);
            
            $productQuantityOnPallets = store_Pallets::calcProductQuantityOnPalletes($rec->productId);
            $recProducts->quantityOnPallets = $productQuantityOnPallets;
            
            store_Products::save($recProducts);
            /* ENDOF Change product quantity on pallets */
            
            /* Създава движение за нов палет, който е 'Автоматично' позициониран */
            if ($rec->palletPlaceHowto == 'Автоматично') {
                // Взема селектирания склад
                $selectedStoreId = store_Stores::getCurrent();
                
                $palletId = $rec->id;
    
                // Генерира автоматично палет място от стратегията
                $storeRec = store_Stores::fetch($selectedStoreId);
                $strategy = cls::getInterface('store_ArrangeStrategyIntf', $storeRec->strategy);
                $palletPlaceAuto = $strategy->getAutoPalletPlace($rec->productId);
                
                // Всички палет места за заети или групата на продукта не е допустима
                if ($palletPlaceAuto == NULL) {
                    $rec->state = 'closed';
                    $rec->newRec = FALSE;
                    store_Pallets::save($rec);
                    
                    return;
                }
                    
                // $recMovements
                $recMovements->storeId     = $selectedStoreId;
                $recMovements->palletId    = $palletId;
                $recMovements->positionOld = 'Зона: ' .$rec->zone;
                $recMovements->positionNew = $palletPlaceAuto;
                $recMovements->state       = 'waiting';
                
                // Записва движение
                store_Movements::save($recMovements);
            }
            /* ENDOF Създава движение за нов палет, който е 'Автоматично' позициониран */
            
            /* Създава движение за нов палет, който е 'Ръчно' позициониран */
            /* if ($rec->newRec == TRUE && 
                $rec->palletPlaceHowto != 'Автоматично' && 
                $rec->palletPlaceHowto != 'На пода') { */            
            if ($rec->newRec == TRUE && 
                $rec->palletPlaceHowto != 'Автоматично' && 
                !preg_match("/^Зона:/u", $rec->palletPlaceHowto)) {
                // Взема селектирания склад
                $selectedStoreId = store_Stores::getCurrent();
                
                $palletId = $rec->id;
                
                // $recMovements
                $recMovements->storeId     = $selectedStoreId;
                $recMovements->palletId    = $palletId;
                $recMovements->positionOld = 'Зона: - ';
                $recMovements->positionNew = $rec->palletPlaceHowto;
                $recMovements->state = 'waiting';
    
                // Записва движение
                store_Movements::save($recMovements);
                
                redirect(array('store_Racks'));
            }
            /* ENDOF Създава движение за нов палет, който е 'Автоматично' позициониран */
        }
    }
    
    
    /**
     * Проверка при изтриване дали палета не е в движение 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param $query
     * @param string $cond
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
     *  @return core_Redirect
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
    
    
    /**
     * Изчислява количестовото от даден продукт на палети
     * 
     * @param int $productId
     * @return int $productQuantityOnPallets
     */
    function calcProductQuantityOnPalletes($productId) {
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
     * @param int $selectedStoreId
     * @param $rec
     * @return boolean
     */
    function checkProductQuantity($rec) {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();    	
    	
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
     * Връща масив с палетите в склада - тези които са 'На място' и не са 'На пода' 
     * и тези, за които има наредено преместване
     * 
     * Елементи на масива:
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId']
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title']
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['productId']
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['stateMovements']
     */    
    function getPalletsInStore()
    {
        $selectedStoreId = store_Stores::getCurrent();
           
        $queryPallets = store_Pallets::getQuery();
        $where = "#storeId = {$selectedStoreId}";

        while($recPallets = $queryPallets->fetch($where)) {
            // Само тези палети, които са 'На място' и не са 'На пода'
            // if ($recPallets->position != 'На пода' && $recPallets->state == 'closed') {
            if (!preg_match("/^Зона:/u", $recPallets->position) && $recPallets->state == 'closed') {
                $positionArr = explode("-", $recPallets->position);
                
                $rackId     = $positionArr[0];
                $rackRow    = $positionArr[1];
                $rackColumn = $positionArr[2];
                
                $palletPosition   = $rackId . "-" . $rackRow . "-" . $rackColumn;
                $palletDimensions = number_format($recPallet->width, 2) . "x" . number_format($recPallets->depth, 2) . "x" . number_format($recPallets->height, 2) . " м, max " . $recPallets->maxWeight . " кг";
                
                $recProducts = store_Products::fetch("#id = {$recPallets->productId}");
                $productName = cat_Products::fetchField("#id = {$recProducts->name}", 'name');
                
                /* push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId']  = $recPallets->id;
                $title = "Продукт ID " . $recProducts->id . ", " . $productName . ", " . $recPallets->quantity . " бр., палет ID: " . $recPallets->id;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title']     = $title;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['productId'] = $recPallets->productId;
                /* ENDOF push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */                             
            }

            // Палетите, за които има наредено преместване
            if ($recPallets->state != 'closed') {
                $where = "#storeId = {$selectedStoreId} 
                          AND #palletId = {$recPallets->id}
                          AND #state != 'closed'";
                
                $position = store_Movements::fetchField($where, 'positionNew');
                $state    = store_Movements::fetchField($where, 'state');
                
                $positionArr = explode("-", $position);
                
                $rackId     = $positionArr[0];
                $rackRow    = $positionArr[1];
                $rackColumn = $positionArr[2];
                
                $palletPosition   = $rackId . "-" . $rackRow . "-" . $rackColumn;
                
                $recProducts = store_Products::fetch("#id = {$recPallets->productId}");
                $productName = cat_Products::fetchField("#id = {$recProducts->name}", 'name');
                
                /* push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId']       = $recPallets->id;
                $title = "Продукт ID " . $recProducts->id . ", " . $productName . ", " . $recPallets->quantity . " бр., палет Id: " . $recPallets->id;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title']          = $title;              
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['productId'] = $recPallets->productId;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['stateMovements'] = $state;                
                /* ENDOF push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */
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
    static function checkIfPalletPlaceIsFree($position)
    {
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

    
    /**
     * Подготвя предупреждения и грешки
     * 
     * @param array $fErrors
     */
    function prepareErrorsAndWarnings($fErrors, $form) 
    {
        $countErrors = 0;
        $countWarnings = 0;
                        
        foreach($fErrors as $v) {
            /* Подготовка на setError() */
            if ($v[0] == 'PPNE' || 
                $v[0] == 'PPNF' ||
                $v[0] == 'PPOOFU') {
                $countErrors += 1;
                                
                if ($countErrors == 1) {
                    $errorReasons = '|*<b>|' . $v[1] . '|*</b>'; 
                } else {
                    $errorReasons .= '<br/><b>|' . $v[1] . '|*</b>';
                }
            }
            /* ENDOF Подготовка на setError() */

            /* Подготовка на setWarning() */
            if ($v[0] == 'PGNA' ||
                $v[0] == 'PPR') {
                $countWarnings += 1;
                                
                if ($countWarnings == 1) {
                    $warningReasons = '|*<b>|' . $v[1] . '|*</b>';
                } else {
                    $warningReasons .= '<br/><b>|' . $v[1] . '|*</b>';
                }
            }
            /* ENDOF Подготовка на setWarning() */
        }
                        
        // Ако има грешки
        if ($countErrors) {
            $form->setError('palletPlaceHowto', $errorReasons);
        }

        // Ако има предупреждения
        if ($countWarnings) {
            $form->setWarning('palletPlaceHowto', $warningReasons);                           
        }            
    }
}