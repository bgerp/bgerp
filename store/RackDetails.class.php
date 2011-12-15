<?php 
/**
 * Менаджира детайлите на стелажите (Details)
 */
class store_RackDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на стелаж";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Логистика";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, store_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'rackId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'tools=Пулт, rackId, rRow, rColumn, action, metric';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "store_Racks";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $currentTab = 'store_Racks';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin, store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin, store';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('rackId',  'key(mvc=store_Racks)',      'caption=Позиция->Стелаж, input=hidden');
        $this->FLD('rRow',    'enum(A,B,C,D,E,F,G,H,ALL)', 'caption=Позиция->Ред');
        $this->FLD('rColumn', 'varchar(3)',                 'caption=Позиция->Колона');
        $this->FLD('action',  'enum(outofuse=неизползваемо,
                                    reserved=резервирано,
                                    maxWeight=макс. тегло (кг), 
                                    maxWidth=макс. широчина (м),
                                    maxHeight=макс. височина (м))', 'caption=Параметър->Име');
        $this->FLD('metric',  'double(decimals=2)',                 'caption=Параметър->Стойност');
    }
    
    
    /**
     * Prepare 'num'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->rackId = store_Racks::fetchField("#id = {$rec->rackId}", 'num');    	
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
        $rackId  = $data->form->rec->rackId;
        $rackNum = store_Racks::fetchField("#id = {$rackId}", 'num');
    	
        $data->form->title = 'Добавяне на параметри за стелаж № ' . $rackNum;
        
        $rRows = store_Racks::fetchField("#id = {$rackId}", 'rows');
        $rColumns = store_Racks::fetchField("#id = {$rackId}", 'columns');
        
        for ($j = 1; $j <= $rRows; $j++) {
            $rRowsOpt[store_Racks::rackRowConv($j)] = store_Racks::rackRowConv($j);
        }
        $rRowsOpt['ALL'] = 'Всички';
        unset($j);
        
        for ($i = 1; $i <= $rColumns; $i++) {
            $rColumnsOpt[$i] = $i;
        }
        $rColumnsOpt['ALL'] = 'Всички';
        unset($i);
        
        $data->form->setOptions('rRow', $rRowsOpt);
        $data->form->setOptions('rColumn', $rColumnsOpt);        
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

        	// Текущите детайли за стелажа
        	$detailsForRackArr = store_RackDetails::getDetailsForRack($rec->rackId);
        	
        	// Палетите на (към) стелажа
            $palletsInStoreArr = store_Pallets::getPalletsInStore();        	
        	
            // Параметри на стелажа
	        $rackRows    = store_Racks::fetchField("#id = {$rec->rackId}", 'rows'); 
	        $rackColumns = store_Racks::fetchField("#id = {$rec->rackId}", 'columns');        	
        	
            /* Проверки за други детайли, палети и движения към ПМ-то от новия детайл */
                // ред 'ALL' и колона 'ALL'
	            if ($rec->rRow == 'ALL' && $rec->rColumn == 'ALL') {
	                for ($r = 1; $r <= $rackRows; $r++) {
	                    for ($c = 1; $c <= $rackColumns; $c++) {
	                        // Проверка за палети/движения
	                        if (isset($palletsInStoreArr[$rec->rackId][store_Racks::rackRowConv($r)][$c])) {
                                $form->setError('rRow,rColumn', 'Зададената област обхваща палет места, на които вече има|*, 
                                                 <br/>|палети или наредени движения|*!');
                                break 2;
                            }	                        
	                    }                   
	                }
	            }           
	            
	            // ред 'ALL' и колона not 'ALL'
	            if ($rec->rRow == 'ALL' && $rec->rColumn != 'ALL') {
	                for ($r = 1; $r <= $rackRows; $r++) {
                        // Проверка за палети/движения
                        if (isset($palletsInStoreArr[$rec->rackId][store_Racks::rackRowConv($r)][$rec->rColumn])) {
                            $form->setError('rRow,rColumn', 'Зададената област обхваща палет места, на които вече има|*, 
                                             <br/>|палети или наредени движения|*!');
                            break;
                        }                        
	                }
	            }            
	            
	            // ред not 'ALL' и колона 'ALL'
	            if ($rec->rRow != 'ALL' && $rec->rColumn == 'ALL') {
	                for ($c = 1; $c <= $rackColumns; $c++) {
                        // Проверка за палети/движения
                        if (isset($palletsInStoreArr[$rec->rackId][$rec->rRow][$c])) {
                            $form->setError('rRow,rColumn', 'Зададената област обхваща палет места, на които вече има|*, 
                                             <br/>|палети или наредени движения|*!');
                            break;
                        }                        
	                }
	            }
	   
	            // ред not 'ALL' и колона not 'ALL'
	            if ($rec->rRow != 'ALL' && $rec->rColumn != 'ALL') {
                    // Проверка за палети/движения
                    if (isset($palletsInStoreArr[$rec->rackId][$rec->rRow][$rec->rColumn])) {
                        $form->setError('rRow,rColumn', 'На (към) зададената позиция има|*, 
                                         <br/>|палети или наредени движения|*!');
                        break;
                    }	            	
	            	
	            	// Проверка дали има вече съществуващ детайл за тази клетка с този 'action'
	            	$existingDetailsRecId = store_RackDetails::fetchField("#rackId = {$rec->rackId} 
	            	                                                AND #rRow = '{$rec->rRow}'
	            	                                                AND #rColumn = '{$rec->rColumn}'
	            	                                                AND #action = '{$rec->action}'", 'id');
	            	
	            	if ($existingDetailsRecId) {
	            		$rec->id = $existingDetailsRecId;
	            	}
	            	// ENDOF Проверка дали има вече съществуващ детайл за тази клетка с този 'action'
	            	
	            	// Проверка, ако новия детайл не е 'outofuse', дали за това ПМ има вече дефиниран детайл 'outofuse'
	            	if ($rec->action != 'outofuse') {
	            		if (isset($detailsForRackArr[$rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn]['outofuse'])) {
                            $form->setError('rRow,rColumn', 'Тази позиция вече е дефинирана като неизползваема!');
	            		}	            	
	            	}
                    // ENDOF Проверка, ако новия детайл не е 'outofuse', дали за това ПМ има вече дефиниран детайл 'outofuse'
	            	
	            	// Проверка, ако новия детайл е 'outofuse' дали за това ПМ има вече дефинирани детайли, които не са 'outofuse'
                    if ($rec->action == 'outofuse') {
                    	$detailsActionsArr = array('reserved', 'maxWeight', 'maxWidth', 'maxHeight');
                    	
                    	foreach ($detailsActionsArr as $v) {
                            if (isset($detailsForRackArr[$rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn][$v])) {
                                $form->setWarning('rRow,rColumn', 'За тази позиция вече има дефинирани детайли!');
                                break;
                            }                    		
                    	}
                    }	            	
	            	// ENDOF Проверка, ако новия детайл е 'outofuse' дали за това ПМ има вече дефинирани детайли, които не са 'outofuse'
	            	
	            }                            
            /* ENDOF Проверки за други детайли, палети и движения към ПМ-то от новия детайл */
        }
    }
    
    
    /**
     * Зарежда всички детайли за даден стелаж
     * 
     * @param int $rackId
     * @return array $detailsForRackArr
     */
    function getDetailsForRack($rackId)
    {
    	$rackRows    = store_Racks::fetchField("#id = {$rackId}", 'rows'); 
    	$rackColumns = store_Racks::fetchField("#id = {$rackId}", 'columns');
    	
    	$detailsArrBoolean = array('outofuse', 'reserved');
    	$detailsArrFloat  = array('maxWeight', 'maxWidth', 'maxHeight');
    	
    	$detailsResults = array();
    	
    	// Редове 'ALL' и колони 'ALL'
    	$query = store_RackDetails::getQuery();
    	$where = "#rackId = {$rackId} AND #rRow='ALL' AND #rColumn='ALL'";
    	
    	while($rec = $query->fetch($where)) {
    	   $detailsResults[] = $rec;	
    	}
    	unset($query, $where, $rec);
    	
        // Редове 'ALL' и колони not 'ALL'
        $query = store_RackDetails::getQuery();
    	$where = "#rackId = {$rackId} AND #rRow='ALL' AND #rColumn!='ALL'";
        
        while($rec = $query->fetch($where)) {
        	
           $detailsResults[] = $rec;    
        }
        unset($query, $where, $rec);
            
        // Редове not 'ALL' и колони 'ALL'
        $query = store_RackDetails::getQuery();
        $where = "#rackId = {$rackId} AND #rRow!='ALL' AND #rColumn='ALL'";
        
        while($rec = $query->fetch($where)) {
           $detailsResults[] = $rec;    
        }
        unset($query, $where, $rec);

        // Редове not 'ALL' и колони not 'ALL'
        $query = store_RackDetails::getQuery();
        $where = "#rackId = {$rackId} AND #rRow!='ALL' AND #rColumn!='ALL'";
        
        while($rec = $query->fetch($where)) {
           $detailsResults[] = $rec;    
        }
        unset($query, $where, $rec);

        // foreach 
        foreach ($detailsResults as $rec) {
        	$palletPlace = $rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn; 
        	
	   		$detailsRec['action'] = $rec->action;
	   		$detailsRec['metric'] = $rec->metric;
	   		
	   		// ред 'ALL' и колона 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn == 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                    for ($c = 1; $c <= $rackColumns; $c++) {
                    	$pp = $rec->rackId . "-" . store_Racks::rackRowConv($r) . "-" . $c;
                    	
                    	if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                    		$detailsForRackArr[$pp][$detailsRec['action']] = "YES";
                    		continue;
                    	}
                    	
                        if (in_array($detailsRec['action'], $detailsArrFloat)) {
                        	$detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                        }
                    }                	
                }
            }	   		
            
            // ред 'ALL' и колона not 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn != 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                	$pp = $rec->rackId . "-" . store_Racks::rackRowConv($r) . "-" . $rec->rColumn;
                	
                    if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = "YES";
                        continue;
                    }
                        
                    if (in_array($detailsRec['action'], $detailsArrFloat)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                    }                	
                }
            }            
            
            // ред not 'ALL' и колона 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn == 'ALL') {
                for ($c = 1; $c <= $rackColumns; $c++) {
                	$pp = $rec->rackId . "-" . $rec->rRow . "-" . $c;
                	
                    if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = "YES";
                        continue;
                    }
                        
                    if (in_array($detailsRec['action'], $detailsArrFloat)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                    }                	
                }
            }

            // ред not 'ALL' и колона not 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn != 'ALL') {
            	$pp = $rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn;
            	
                if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                    $detailsForRackArr[$pp][$detailsRec['action']] = "YES";
                    continue;
                }
                        
                if (in_array($detailsRec['action'], $detailsArrFloat)) {
                    $detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                }            	
            }    
        }
        
        return $detailsForRackArr;
    }

    
    /**
     * Проверка дали тази позиция присъства в детайлите и дали е неизползваема
     * @param int $rackId
     * @param string $palletPlace
     * @return boolean
     */
    static function checkIfPalletPlaceIsNotOutOfUse($rackId, $palletPlace) 
    {
        $detailsForRackArr = store_RackDetails::getDetailsForRack($rackId);
        
        if (empty($detailsForRackArr)) {
            return TRUE;
        } else {
            if (isset($detailsForRackArr[$palletPlace]['outofuse'])) {
                return FALSE;
            } else {
                return TRUE;
            }
        } 
 
    }
    
    
    /**
     * Проверка дали тази позиция присъства в детайлите и дали е резервирана
     * @param int $rackId
     * @param string $palletPlace
     * @return boolean
     */
    static function checkIfPalletPlaceIsNotReserved($rackId, $palletPlace) 
    {
        $detailsForRackArr = store_RackDetails::getDetailsForRack($rackId);
        
        if (empty($detailsForRackArr)) {
            return TRUE;
        } else {
            if (isset($detailsForRackArr[$palletPlace]['reserved'])) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }    
        
}
