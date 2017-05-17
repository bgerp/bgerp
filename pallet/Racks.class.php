<?php



/**
 * Стелажи
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_Racks extends core_Master
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'store_Racks';
	
	
    /**
     * Заглавие
     */
    var $title = 'Стелажи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, plg_RowTools, pallet_Wrapper';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'storeId';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,pallet';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,pallet';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,pallet';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,pallet';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,pallet';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,pallet';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,pallet';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 10;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'rackView=Стелаж';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'pallet_RackDetails';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'num';
    

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/rack.png';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden');
        $this->FLD('num', 'int', 'caption=Стелаж №,mandatory');
        $this->FLD('rows', 'int(max=100)', 'caption=Редове,mandatory');
        $this->FLD('columns', 'int(max=100)', 'caption=Колони,mandatory');
        $this->FLD('comment', 'text', 'caption=Коментар');
        $this->FLD('groupsAllowed', 'keylist(mvc=cat_Categories, select=name)', 'caption=Групи');
        $this->FLD('constrColumnsStep', 'int', 'caption=Носещи колони през брой палет->Места');
        
        $this->setDbUnique('storeId,num');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Забранява изтриването/редакцията на стелажите, които не са празни
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id && ($action == 'delete')) {
            $mvc->palletsInStoreArr = pallet_Pallets::getPalletsInStore();
            
            $rec = $mvc->fetch($rec->id);
            
            if ($mvc->palletsInStoreArr[$rec->id]) {
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
    protected static function on_AfterPrepareListTitle($mvc, $data)
    {
        $selectedStoreName = store_Stores::getHyperlink(store_Stores::getCurrent(), TRUE);
    	$data->title = "|Стелажи в склад|* <b style='color:green'>{$selectedStoreName}</b>";
    }
    
    
    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене на продукт в склада';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('productIdFilter', 'key(mvc=store_Products, select=productId, allowEmpty=true)', 'caption=Продукт');
        
        $data->listFilter->showFields = 'productIdFilter';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input();
        
        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
            if ($recFilter->productIdFilter) {
                $mvc->productIdFilter = $recFilter->productIdFilter;
            }
        }
        
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->query->where("#storeId = {$selectedStoreId}");
        $data->query->orderBy('id');
    }
    
    
    /**
     * Форма за add/edit на стелаж
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $form = $data->form;
        $form->setDefault('storeId', $selectedStoreId);
        
        // В случай на add
        if (!$data->form->rec->id) {
            $query = $mvc->getQuery();
            $where = "1=1";
            $query->limit(1);
            $query->orderBy('num', 'DESC');
            
            while($recRacks = $query->fetch($where)) {
                $maxNum = $recRacks->num;
            }
            
            $data->form->setDefault('num', $maxNum + 1);
            $data->form->setDefault('rows', 7);
            $data->form->setDefault('rows', 7);
            $data->form->setDefault('columns', 24);
            $data->form->setDefault('constrColumnsStep', 3);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните във формата ($form->rec)
     * При промяна параметрите на стелажа проверява дали, ако са намалени
     * редовете и (или) колоните не се премахват палет места, които са
     * заети и (или) за които има наредени движения.
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted() && ($form->rec->id)) {
            $selectedStoreId = store_Stores::getCurrent();
            $palletsInStoreArr = pallet_Pallets::getPalletsInStore();
            $rec = $form->rec;
            
            /* Ако стелажа съществува (edit) */
            if ($palletsInStoreArr[$rec->id]) {
                $currentRows = pallet_Racks::fetchField($rec->id, 'rows');
                $currentColumns = pallet_Racks::fetchField($rec->id, 'columns');
                
                /* Ако новите редове са по-малко от текущите */
                if ($rec->rows < $currentRows) {
                    $rowsForDeleteInUse = array();
                    
                    /* Проверка за всеки ред - от въведения + 1 до последния съществуващ */
                    for ($testRow = $rec->rows + 1; $testRow <= $currentRows; $testRow++) {
                        // Проверка дали в масива $palletsInStoreArr[$rec->id] за тови ред има елементи
                        if ($palletsInStoreArr[$rec->id][$mvc->rackRowConv($testRow)]) {
                            array_push($rowsForDeleteInUse, $mvc->rackRowConv($testRow));
                        }
                    }
                    
                    // Подготовка на съобщението за setError
                    if (!empty($rowsForDeleteInUse)) {
                        foreach ($rowsForDeleteInUse as $k => $v) {
                            if ($k < (count($rowsForDeleteInUse) - 2)) {
                                $rowsForDeleteInUseLetters .= $v . ", ";
                            }
                            
                            if ($k == (count($rowsForDeleteInUse) - 2)) {
                                $rowsForDeleteInUseLetters .= $v . " и ";
                            }
                            
                            if ($k == (count($rowsForDeleteInUse) - 1)) {
                                $rowsForDeleteInUseLetters .= $v;
                            }
                        }
                        
                        $form->setError('rows', 'Не е позволено намаляване броя на редовете на стелажа|* -
                                                 <br/>|на ред(ове)|* <b>' . $rowsForDeleteInUseLetters . '
                                                 </b>|има палети и (или) наредени движения|*.');
                    }
                    
                    /* ENDOF Проверка за всеки ред - от въведения + 1 до последния съществуващ */
                }
                
                /* ENDOF Ако новите редове са по-малко от текущите */
                
                /* Ако новите колони са по-малко от текущите */
                if ($rec->columns < $currentColumns) {
                    $columnsForDeleteInUse = array();
                    
                    for ($testRow = 1; $testRow <= $currentRows; $testRow++) {
                        for ($testColumn = $rec->columns + 1; $testColumn <= $currentColumns; $testColumn++) {
                            // Проверка дали в масива $palletsInStoreArr[$rec->id] за този ред и за тази колона има елементи
                            if ($palletsInStoreArr[$rec->id][$mvc->rackRowConv($testRow)][$testColumn]) {
                                if (!in_array($testColumn, $columnsForDeleteInUse)) {
                                    $columnsForDeleteInUse[] = $testColumn;
                                }
                            }
                        }
                    }
                    
                    // Подготовка на съобщението за setError
                    if (!empty($columnsForDeleteInUse)) {
                        foreach ($columnsForDeleteInUse as $k => $v) {
                            if ($k < (count($columnsForDeleteInUse) - 2)) {
                                $columnsForDeleteInUseLetters .= $v . ", ";
                            }
                            
                            if ($k == (count($columnsForDeleteInUse) - 2)) {
                                $columnsForDeleteInUseLetters .= $v . " и ";
                            }
                            
                            if ($k == (count($columnsForDeleteInUse) - 1)) {
                                $columnsForDeleteInUseLetters .= $v;
                            }
                        }
                        
                        $form->setError('columns', 'Не е позволено намаляване броя на колоните на стелажа|* -
                                                    <br/>|на колона (колони)|* <b>' . $columnsForDeleteInUseLetters . '
                                                    </b>|има палети и (или) наредени движения|*.');
                    }
                }
                
                /* ENDOF Ако новите колони са по-малко от текущите */
                
                /* Подготовка на масив с групите от стелажа */
                $currentGroupsInUseArr = array();
                $groupsAllowedArr = keylist::toArray($rec->groupsAllowed);
                $groupsForDeleteInUse = array();
                
                if (!$rowsForDeleteInUse && !$columnsForDeleteInUse) {
                    for ($testRow = 1; $testRow <= $currentRows; $testRow++) {
                        for ($testColumn = 1; $testColumn <= $currentColumns; $testColumn++) {
                            if (isset($palletsInStoreArr[$rec->id][$mvc->rackRowConv($testRow)][$testColumn]['productId'])) {
                                $productId = $palletsInStoreArr[$rec->id][$mvc->rackRowConv($testRow)][$testColumn]['productId'];
                                $catProductId = store_Products::fetchField($productId, 'productId');
                                $productGroups = cat_Products::fetchField($catProductId, 'groups');
                                $productGroupsArr = keylist::toArray($productGroups);
                                
                                foreach ($productGroupsArr as $v) {
                                    if (empty($currentGroupsInUseArr)) {
                                        $currentGroupsInUseArr[] = $v;
                                    } else {
                                        if (!in_array($v, $currentGroupsInUseArr)) {
                                            $currentGroupsInUseArr[] = $v;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                /* ENDOF Подготовка на масив с групите от стелажа */
                
                // Подготовка на стринг с имената на групите, които се използват в стелажа,
                // но са маркирани за изтриване
                foreach ($currentGroupsInUseArr as $v) {
                    if (!in_array($v, $groupsAllowedArr)) {
                        $groupName = cat_Groups::fetchField($v, 'name');
                        
                        $groupsInUseForDeleteNames .= $groupName . ", ";
                    }
                }
                
                // Ако е заявено изтриване на групи, които са в употреба за стелажа
                if (isset($groupsInUseForDeleteNames)) {
                    $groupsInUseForDeleteNames = substr($groupsInUseForDeleteNames, 0, strlen($groupsInUseForDeleteNames) - 2);
                    
                    $form->setError(' ', 'Не е позволено изтриване на групи за стелажа, които се използват|*.
                                          <br/>|За групите|* <b>|' . $groupsInUseForDeleteNames . '|*
                                          </b>|има палети и (или) наредени движения|*.');
                }
                
                /*ENDOF  Проверка на групите */
            }
            
            /* ENDOF Ако стелажа съществува (edit) */
        }
    }
    
    
    /**
     * Визуализация на стелажите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ROW_ATTR['class'] = 'noHover';
        
        $palletsInStoreArr = pallet_Pallets::getPalletsInStore();
        
        $detailsForRackArr = pallet_RackDetails::getDetailsForRack($rec->id);
        
        $constrColumnsStep = $mvc->fetchField($rec->id, 'constrColumnsStep');
        
        // html
        $html .= "<div style='clear: left;
                              padding: 5px; 
                              font-size: 20px; 
                              font-weight: bold; 
                              color: green;'>";
        
        if ($rec->num) {
            $html .= $rec->num;
        } else {
            $html .= "<span style='color: #777777;'>ID</span> <span style='color: red;'>" . $rec->id . "</span>";
        }
        
         
        $html .= "</div>";
        
        $html .= "<table cellspacing='1' class='pallet-table'>";
        
        /* За всеки ред от стелажа */
        for ($r = $rec->rows; $r >= 1; $r--) {
            $html .= "<tr>";
            
            /* Филтъра не е активиран */
            if (!$mvc->productIdFilter) {
                /* За всяка колона от стелажа */
                for ($c = 1; $c <= $rec->columns; $c++) {
                    // Палет място
                    $palletPlace = $rec->id . "-" . $mvc->rackRowConv($r) . "-" . $c;
                    
                    /* Проверка дали има палет на това палет място */
                    // Ако има палет на това палет място
                    if (isset($palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c])) {
                        $stateMovements = $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['stateMovements'];
                        
                        if (!empty($stateMovements)) {
                            if ($stateMovements == 'waiting') {
                                $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " movement_waiting'>";
                            }
                            
                            if ($stateMovements == 'active') {
                                $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " movement_active'>";
                            }
                        } else $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " in_use'>";
                        
                        $html .= Ht::createLink($mvc->rackRowConv($r) . $c,
                            array('pallet_Pallets', 'list', $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['palletId']),
                            FALSE,
                            array('title' => $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['title']));
                    } else { // Ако няма палет на това палет място
                        /* Проверка за това палет място в детайлите */
                        if (!empty($detailsForRackArr) && array_key_exists($palletPlace, $detailsForRackArr)) {
                            // Дали мястото е неизползваемо
                            if (isset($detailsForRackArr[$palletPlace]['outofuse'])) {
                                $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " outofuse'>";
                            } else {
                                // Дали мястото е резервирано
                                if (isset($detailsForRackArr[$palletPlace]['reserved'])) {
                                    $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " reserved'>";
                                } else {
                                    $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . "'>";
                                }
                            }
                        } else {
                            $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . "'>";
                        }
                        
                        /* END Проверка за това палет място в детайлите */
                        
                        $html .= $mvc->rackRowConv($r) . $c;
                    }
                    
                    /* END Проверка дали има палет на това палет място */
                    
                    $html .= "</td>";
                }
                
                /* END За всяка колона от стелажа */
            }
            
            /* ENDOF Филтъра не е активиран */
            
            /* Филтъра е активиран */
            if ($mvc->productIdFilter) {
                /* За всяка колона от стелажа */
                for ($c = 1; $c <= $rec->columns; $c++) {
                    // Палет място
                    $palletPlace = $rec->id . "-" . $mvc->rackRowConv($r) . "-" . $c;
                    
                    /* Проверка дали има палет на това палет място */
                    // Ако има палет на това палет място
                    if (isset($palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c])) {
                        $stateMovements = $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['stateMovements'];
                        
                        if (!empty($stateMovements)) {
                            if ($stateMovements == 'waiting') {
                                if ($mvc->productIdFilter == $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['productId']) {
                                    $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " movement_waiting'>";
                                } else {
                                    $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " movement_waiting out_of_filter'>";
                                }
                            }
                            
                            if ($stateMovements == 'active') {
                                if ($mvc->productIdFilter == $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['productId']) {
                                    $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " movement_active'>";
                                } else {
                                    $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " movement_active out_of_filter'>";
                                }
                            }
                        } else {
                            if ($mvc->productIdFilter == $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['productId']) {
                                $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " in_use'>";
                            } else {
                                $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " in_use out_of_filter'>";
                            }
                        }
                        
                        $html .= Ht::createLink($mvc->rackRowConv($r) . $c,
                            array('pallet_Pallets', 'list', $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['palletId']),
                            FALSE,
                            array('title' => $palletsInStoreArr[$rec->id][$mvc->rackRowConv($r)][$c]['title']));
                        
                        // Ако няма палет на това палет място
                    } else {
                        /* Проверка за това палет място в детайлите */
                        if (!empty($detailsForRackArr) && array_key_exists($palletPlace, $detailsForRackArr)) {
                            // Дали мястото е неизползваемо
                            if ($detailsForRackArr[$palletPlace]['action'] == 'outofuse') {
                                $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " outofuse'>";
                            }
                            
                            // Дали мястото е резервирано
                            if ($detailsForRackArr[$palletPlace]['action'] == 'reserved') {
                                $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " reserved'>";
                            }
                            
                            // Други проверки
                            // ...
                        } else {
                            $html .= "<td class='pallet_place " . pallet_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . "'>";
                        }
                        
                        /* END Проверка за това палет място в детайлите */
                        
                        $html .= $mvc->rackRowConv($r) . $c;
                    }
                    
                    /* END Проверка дали има палет на това палет място */
                    
                    $html .= "</td>";
                }
                
                /* END За всяка колона от стелажа */
            }
            
            /* ENDOF Филтъра е активиран */
            
            $html .= "</tr>";
        }
        
        /* END За всеки ред от стелажа */
        
        $html .= "</table>";
        
        // END html
        
        $row->rackView = $html;
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     */
    function renderSingleLayout_(&$data)
    {
        if(isset($this->singleLayoutFile)) {
            $layout = new ET(file_get_contents(getFullPath($this->singleLayoutFile)));
        } elseif(isset($this->singleLayoutTpl)) {
            $layout = new ET($this->singleLayoutTpl);
        } else {
            if(count($data->singleFields)) {
                foreach($data->singleFields as $field => $caption) {
                    $fieldsHtml .= "<tr><td>[#CAPTION_{$field}#]</td><td>[#{$field}#]</td></tr>";
                }
            }
            
            $class = $this->cssClass ? $this->cssClass : $this->className;
            
            $layout = new ET("[#SingleToolbar#]<div class='{$class}'><h2>[#SingleTitle#]</h2>" .
                "<!--ET_BEGIN DETAILS-->[#DETAILS#]<!--ET_END DETAILS--></div>");
        }
        
        $layout->translate();
        
        return $layout;
    }
    
    
    /**
     * Проверка при изтриване дали палета не е в движение
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param $query
     */
    protected static function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        
        while ($rec = $_query->fetch($cond)) {
            $query->deleteRecId = $rec->id;
        }
    }
    
    
    /**
     * Ако е минала проверката за state в on_BeforeDelete, след като е изтрит записа изтриваме всички движения за него
     *
     * @param core_Mvc $mvc
     * @param int $numRows
     * @param stdClass $query
     * @param string $cond
     */
    protected static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        pallet_RackDetails::delete("#rackId = {$query->deleteRecId}");
        
        redirect(array($this));
    }
    
    
    /**
     * Връща CSS клас за оцветяване на палет място в зависимост от носещите колони
     *
     * @param int $c
     * @param int $rackColumns
     * @param int $constrColumnsStep
     * @return string
     */
    public static function checkConstrColumns($c, $rackColumns, $constrColumnsStep)
    {
        if ($c == 1) {
            return "constrColumnLeft";
        }
        
        if ($c == $rackColumns) {
            return "constrColumnRight";
        }
        
        if ($c % $constrColumnsStep == 0) {
            return "constrColumnRight";
        } else return "";
    }
    
    
    /**
     * Проверка дали групите за продукта от палета са допустими за стелажа
     *
     * @param int $rackId
     * @param int $palletId
     * @return boolean
     */
    public static function checkIfProductGroupsAreAllowed($rackId, $productId) 
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        try {
            $pRec = store_Products::fetch($productId);
            $inst = cls::get($pRec->classId);
            $groupField = ($inst->groupField) ? $inst->groupField : 'groups';
            $productGroups = $inst->fetchField($pRec->productId, 'groups');
        } catch (core_exception_Expect $e) {
            return FALSE;
        }
        
        $productGroupsArr = keylist::toArray($productGroups);
        
        $groupsAllowed = self::fetchField($rackId, 'groupsAllowed');
        $groupsAllowedArr = keylist::toArray($groupsAllowed);
        
        if (count($groupsAllowedArr)) {
            $groupsCheck = FALSE;
            
            foreach ($productGroupsArr as $v) {
                if (in_array($v, $groupsAllowedArr)) {
                    $groupsCheck = TRUE;
                } else {
                    $groupsCheck = FALSE;
                    break;
                }
            }
            
            if ($groupsCheck === FALSE) {
                return FALSE;
            } else return TRUE;
        } else return TRUE;
    }
    
    
    /**
     * Проверка дали е валидно палет мястото - дали съществува палет id-то, и дали реда и колоната са реални
     *
     * @param string $palletPlace
     * @return boolean
     */
    public static function checkIfPalletPlaceExists($palletPlace) 
    {
        // array letter to digit
        $rackRowsArr = array('A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'F' => 6,
            'G' => 7,
            'H' => 8);
        
        $selectedStoreId = store_Stores::getCurrent();
        
        $positionArr = explode("-", $palletPlace);
        
        $rackId = $positionArr[0];
        $rackRow = $positionArr[1];
        $rackColumn = $positionArr[2];
        
        // Ако няма стелаж с това id
        if (!$recRacks = pallet_Racks::fetch(array("#id = '[#1#]' AND #storeId = '[#2#]'", $rackId, $selectedStoreId))) return FALSE;
        
        // Ако реда не е сред ключовете на масива $rackRowsArr
        if (!array_key_exists($rackRow, $rackRowsArr)) return FALSE;
        
        // Ако реда е по-голям от броя на редовете в стелажа
        if ($rackRowsArr[$rackRow] > $recRacks->rows) return FALSE;
        
        // Ако колоната е по-голяма от броя на колоните в стелажа
        if ($rackColumn > $recRacks->columns) return FALSE;
        
        return TRUE;
    }
    
    
    /**
     * Връща дали дадено палет място е подходящо за поставяне на нов палет
     * на базата на пет проверки:
     * съществуващо ПМ, свободно ПМ, неизползваемо ПМ, допустими продуктови групи, резервирано ПМ
     *
     * @param int $rackId
     * @param int $palletId
     * @param string $palletPlace
     * @return array $fResult
     */
    public static function isSuitable($rackId, $productId, $palletPlace)
    {
        $fResult = array();
        $fErrors = array();
        
        // Съществува в склада
        if (pallet_Racks::checkIfPalletPlaceExists($palletPlace) === FALSE) {
            array_push($fErrors, array('PPNE', 'Позицията не съществува в склада'));
        }
        
        // Заето
        if (pallet_Pallets::checkIfPalletPlaceIsFree($palletPlace) === FALSE) {
            array_push($fErrors, array('PPNF', 'Позицията е заета'));
        }
        
        // Продуктовите групи
        if (pallet_Racks::checkIfProductGroupsAreAllowed($rackId, $productId) === FALSE) {
            array_push($fErrors, array('PGNA', 'Тази продуктова група не е разрешена'));
        }
        
        // Неизползваемо
        if (pallet_RackDetails::checkIfPalletPlaceIsNotOutOfUse($rackId, $palletPlace) === FALSE) {
            array_push($fErrors, array('PPOOFU', 'Позицията е неизползваема'));
        }
        
        // Резервирано
        if (pallet_RackDetails::checkIfPalletPlaceIsNotReserved($rackId, $palletPlace) === FALSE) {
            array_push($fErrors, array('PPR', 'Позицията е резервирана'));
        }
        
        /*
        if (pallet_Movements::checkIfPalletPlaceHasNoAppointedMovements($palletPlace) === FALSE) {\
            array_push($fErrors, array('PPM', 'Към това палет място има назначено движение!'));
        }
        */
        
        // Ако има грешки
        if (!empty($fErrors)) $fResult = array(FALSE, $fErrors);
        
        // Ако няма грешки
        if (empty($fErrors)) $fResult = array(TRUE);
        
        return $fResult;
    }
    
    
    /**
     * По палет място (ПМ) започващо с номер на стелажа връща ПМ започващо с rackId
     *
     * @param $arrayForExplode
     * @return array $fResult
     */
    public static function ppRackNum2rackId($stringForExplode)
    {
        $stringForExplode = str::utf2ascii($stringForExplode);
        $positionArr = explode("-", $stringForExplode);
        
        $rackNum = $positionArr[0];
        $rackId = pallet_Racks::fetchField("#num = {$rackNum}", 'id');
        
        if (!$rackId) {
            $fResult[0] = FALSE;
        } else {
            $fResult[0] = TRUE;
            $rackRow = $positionArr[1];
            $rackColumn = $positionArr[2];
            
            $fResult['rackId'] = $rackId;
            $fResult['position'] = $rackId . "-" . $rackRow . "-" . $rackColumn;
        }
        
        return $fResult;
    }
    
    
    /**
     * По палет място (ПМ) започващо с rackId на стелажа връща ПМ започващо с номер
     *
     * @param $arrayForExplode
     * @return array $fResult
     */
    public static function ppRackId2RackNum($stringForExplode)
    {
        $stringForExplode = str::utf2ascii($stringForExplode);
        $positionArr = explode("-", $stringForExplode);
        
        $rackId = $positionArr[0];
        
        $rackNum = pallet_Racks::fetchField($rackId, 'num');
        
        if (!$rackNum) {
            $fResult[0] = FALSE;
        } else {
            $fResult[0] = TRUE;
            $rackRow = $positionArr[1];
            $rackColumn = $positionArr[2];
            
            $fResult['rackNum'] = $rackNum;
            $fResult['position'] = $rackNum . "-" . $rackRow . "-" . $rackColumn;
        }
        
        return $fResult;
    }
    
    
    /**
     * /** За палет място конвертира реда от string в int и обратно
     * Ако string-а е 'ALL' връща 100.
     * Ако string-а не е 'ALL" връща съответния номер на ред (за A - 1, за B -2 и т.н.)
     *
     * @param string|int $value
     * @return string|int
     */
    public static function rackRowConv($value) 
    {
        $value = str::utf2ascii($value);
        
        $rowStringArr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'ALL');
        $rowIntArr = array('1', '2', '3', '4', '5', '6', '7', '8', '100');
        
        if (in_array($value, $rowStringArr)) {
            if ($value == 'ALL') {
                return 100;
            } else {
                return (ord($value) - 64);
            }
        }
        
        if (in_array($value, $rowIntArr)) {
            if ($value == 100) {
                return 'ALL';
            } else {
                return (chr($value + 64));
            }
        }
    }
}
