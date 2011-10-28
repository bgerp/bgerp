<?php
/**
 * Стелажи
 */
class store_Racks extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Стелажи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, 
                     acc_plg_Registry, store_Wrapper';
    
    
    var $lastUsedKeys = 'storeId';

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
    var $canSingle = 'admin,store';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 10;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'rackView';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = 'store_RackDetails';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId',           'key(mvc=store_Stores,select=name)', 'caption=Склад, input=hidden');
        $this->FLD('num',               'int',                               'caption=Стелаж №');
        $this->FLD('rows',              'enum(1,2,3,4,5,6,7,8)',             'caption=Редове,mandatory');
        $this->FLD('columns',           'int(max=24)',                       'caption=Колони,mandatory');
        $this->FLD('specification',     'varchar(255)',                      'caption=Спецификация');
        $this->FLD('comment',           'text',                              'caption=Коментар');
        $this->FNC('rackView',          'text',                              'caption=Стелажи');
        $this->FLD('constrColumnsStep', 'int',                               'caption=Носещи колони през брой палет места');
        
        $this->setDbUnique('num');
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
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if ($rec->id && ($action == 'delete')) {

    		$mvc->palletsInStoreArr = store_Pallets::getPalletsInStore();

            $rec = $mvc->fetch($rec->id);
            
            if ($mvc->palletsInStoreArr[$rec->id]) {
                $requiredRoles = 'no_one';
            }
        } 
        
        /*
        if ($rec->id && ($action == 'edit')) {
            $rec = $mvc->fetch($rec->id);
            
            if ($mvc->palletsInStoreArr[$rec->id]) {
                $requiredRoles = 'no_one';
            }
        }
        */        
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
        
    	$data->title = "Стелажи в СКЛАД № {$selectedStoreId}";
    }    
    

    /**
     * Форма за add/edit на стелаж 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $data)
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
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->query->where("#storeId = {$selectedStoreId}");
        $data->query->orderBy('id');
    }
    
    
    /**
     * Визуализация на стелажите 
     *  
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ROW_ATTR['class'] = 'noHover';
         
    	$palletsInStoreArr = $mvc->palletsInStoreArr;
        
        $detailsForRackArr = store_RackDetails::getDetailsForRack($rec->id);
        $constrColumnsStep = $mvc->fetchField("#id = {$rec->id}", 'constrColumnsStep');
        
        // array letter to digit
        $rackRowsArr = array('A' => 1,
                             'B' => 2,
                             'C' => 3,
                             'D' => 4,
                             'E' => 5,
                             'F' => 6,
                             'G' => 7,
                             'H' => 8);
        
        // array digit to letter
        $rackRowsArrRev = array('1' => A,
                                '2' => B,
                                '3' => C,
                                '4' => D,
                                '5' => E,
                                '6' => F,
                                '7' => G,
                                '8' => H);
        
        // html
        $html .= "<div style='clear: left; 
                              padding: 5px; 
                              font-size: 20px; 
                              font-weight: bold; 
                              color: green;'>";
                              
        $html .= $rec->num;
        
        // Ако има права за delete добавяме линк с икона за delete
        if ($mvc->haveRightFor('delete', $rec)) {
	        $delImg = "<img src=" . sbf('img/16/delete-icon.png') . " style='position: relative; top: 1px;'>";
	        $delUrl = toUrl(array($mvc, 'delete', $rec->id, 'ret_url' => TRUE));
	        $delLink = ht::createLink($delImg, $delUrl);
	        
            $html .= " " . $delLink;
        }
        
        // Ако има права за edit добавяме линк с икона за edit
        if ($mvc->haveRightFor('edit', $rec)) {
            $editImg = "<img src=" . sbf('img/16/edit-icon.png') . " style='position: relative; top: 1px;'>";
            $editUrl = toUrl(array($mvc, 'edit', $rec->id, 'ret_url' => TRUE));
            $editLink = ht::createLink($editImg, $editUrl);
            
            $html .= " " . $editLink;
        }        
        
        // Ако има права за single добавяме линк с икона за single
        if ($mvc->haveRightFor('single', $rec)) {
            $singleImg = "<img src=" . sbf('img/16/view.png') . " style='position: relative; top: 1px;'>";
            $singleUrl = toUrl(array($mvc, 'single', $rec->id, 'ret_url' => TRUE));
            $singleLink = ht::createLink($singleImg, $singleUrl);
            
            $html .= " " . $singleLink;
        }        
        
        $html .= "</div>";
        
        $html .= "<table cellspacing='1' style='clear: left;'>";
     
        /* За всеки ред от стелажа */
        for ($r = $rec->rows; $r >= 1; $r--) {
            $html .= "<tr>";
            
            /* За всяка колона от стелажа */
            for ($c = 1; $c <= $rec->columns; $c++) {
            	// Палет място
            	$palletPlace = $rec->id . "-" . $rackRowsArrRev[$r] . "-" .$c;
            	
            	/* Проверка дали има палет на това палет място */
                // Ако има палет на това палет място
                if (isset($palletsInStoreArr[$rec->id][$rackRowsArrRev[$r]][$c])) {
                    $html .= "<td class='pallet_place " . store_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . "'><b>" . Ht::createLink($rackRowsArrRev[$r] . $c, 
                                                    array('store_Pallets', 
                                                          'list',
                                                          $palletsInStoreArr[$rec->id][$rackRowsArrRev[$r]][$c]['palletId']), 
                                                    FALSE, 
                                                    array('title' => $palletsInStoreArr[$rec->id][$rackRowsArrRev[$r]][$c]['title'])) . "</b>";   
                // Ако няма палет на това палет място
                } else {
            		/* Проверка за това палет място в детайлите */
	            	if (!empty($detailsForRackArr) && array_key_exists($palletPlace, $detailsForRackArr)) {
	            		// Дали мястото е забранено 
	            		if ($detailsForRackArr[$palletPlace]['action'] == 'forbidden') {
	            			$html .= "<td class='pallet_place " . store_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . " forbidden'>";
	            		}
	            		
	            		// Други проверки
	            		// ...
	            	} else {
	            		$html .= "<td class='pallet_place " . store_Racks::checkConstrColumns($c, $rec->columns, $constrColumnsStep) . "'>";
	            	}
	            	/* END Проверка за това палет място в детайлите */
                	
                	$html .= $rackRowsArrRev[$r] . $c;
                }
                /* END Проверка дали има палет на това палет място */            	
            	
                $html .= "</td>";               
            }
            /* END За всяка колона от стелажа */
            
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
     function renderSingleLayout_($data)
    {
        if(isset($this->singleLayoutFile)) {
            $layout = new ET(file_get_contents(getFullPath($this->singleLayoutFile)));
        } elseif( isset($this->singleLayoutTpl) ) {
            $layout = new ET($this->singleLayoutTpl);
        } else {
            if( count($data->singleFields) ) {
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
     * Default стойности в детайлите за носеща колона 
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_AfterSave($mvc, &$id, $rec)
    {
		$recDetails->rackId = $rec->id;
		$recDetails->rRow = 'ALL';
		$recDetails->rColumn = 'ALL';
		$recDetails->action = 'constrColumnsStep';
		$recDetails->metric = 3;
    	
		store_RackDetails::save($recDetails);
    }

    
    /**
     * Връща CSS клас за оцветявяне на палет място в зависимост от носещите колони
     * 
     * @param int $c
     * @param int $rackColumns
     * @param int $constrColumnsStep
     * @return string
     */
    static function checkConstrColumns($c, $rackColumns, $constrColumnsStep)
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

}