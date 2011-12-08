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
        $this->FLD('rColumn', 'int',                       'caption=Позиция->Колона');
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

            $palletsInStoreArr = store_Pallets::getPalletsInStore();        	
        	
        	$recRacks = store_Racks::fetch("#id = {$rec->rackId}");
        	
            if (empty($rec->rColumn)) {
                $form->setError('rColumn', 'Моля, въведете колона');
            }        	
        	
            if ($rec->rRow != 'ALL') {
	            if (store_Racks::rackRowConv($rec->rRow) > $recRacks->rows) {
	                $form->setError('rRow', 'Няма такъв ред в палета. Най-големия ред е|* ' . store_Racks::rackRowConv($recRacks->rows) . '.');
	            }
            }
        	
            if ($rec->rColumn != 'ALL') {
	            if ($rec->rColumn > $recRacks->columns) {
	                $form->setError('rColumn', 'Няма такава колона в палета. Най-голямата колона е|* ' . $recRacks->columns . '.');
	            }
            }
            
            if ($rec->rRow != 'ALL' && $rec->rColumn != 'ALL') {
                if (isset($palletsInStoreArr[$rec->rackId][$rec->rRow][$rec->rColumn])) {
                    $form->setError('rRow,rColumn', 'За тази позиция не може да се добавят детайли|*, 
                                                     <br/>|защото е заета или има наредено движение към нея|*!');
                }            	
            }
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
    	
    	$query = store_RackDetails::getQuery();

        while($rec = $query->fetch("#rackId = {$rackId}")) {
	   		$palletPlace = $rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn; 
        	
	   		$deatailsRec['action'] = $rec->action;
	   		$deatailsRec['metric'] = $rec->metric;
	   		
	   		// ред 'ALL' и колона 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn == 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                    for ($c = 1; $c <= $rackColumns; $c++) {
                        $detailsForRackArr[$rec->rackId . "-" . store_Racks::rackRowConv($r) . "-" . $c] = $deatailsRec;
                    }                	
                }
            }	   		
            
            // ред 'ALL' и колона not 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn != 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                    $detailsForRackArr[$rec->rackId . "-" . store_Racks::rackRowConv($r) . "-" . $rec->rColumn] = $deatailsRec;
                }
            }            
            
            // ред not 'ALL' и колона 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn == 'ALL') {
                for ($c = 1; $c <= $rackColumns; $c++) {
                    $detailsForRackArr[$rec->rackId . "-" . store_Racks::rackRowConv($rec->rColumn) . "-" . $c] = $deatailsRec;
                }
            }

            // ред not 'ALL' и колона not 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn != 'ALL') {
	   		    $detailsForRackArr[$palletPlace] = $deatailsRec;
            }    
        }
        // bp($detailsForRackArr);
        return $detailsForRackArr;
    }

    
    /**
     * Проверка дали тази позиция присъства в детайлите и дали е неизползваема
     * @param int $rackId
     * @param string $palletPlace
     * @return boolean
     */
    static function checkIfPalletPlaceIsNotOutOfUse($rackId, $palletPlace) {
    	$palletPlaceArr    = explode("-", $palletPlace);
    	$palletPlaceRow    = $palletPlaceArr[1];  
    	$palletPlaceColumn = $palletPlaceArr[2];
    	
        $detailsForRackArr = store_RackDetails::getDetailsForRack($rackId);
        
        if (empty($detailsForRackArr)) {
            return TRUE;
        } else {
	        // Only details for 'outofuse'
	        foreach ($detailsForRackArr as $k => $v) {
	            if ($v['action'] != 'outofuse') {
	                unset($detailsForRackArr[$k]);
	            }
	        }
	        // ENDOF Only details for 'outofuse'        
	        
	        if (empty($detailsForRackArr)) {
	           return TRUE;
	        } else {
		        foreach ($detailsForRackArr as $k => $v) {
		            // Проверка за тази позиция в детайлите
		            if (array_key_exists($palletPlace, $detailsForRackArr)) {
		                return FALSE;
		            } else return TRUE;
		        }
	        }
        }
    }
    
    
    /**
     * Проверка дали тази позиция присъства в детайлите и дали е резервирана
     * @param int $rackId
     * @param string $palletPlace
     * @return boolean
     */
    static function checkIfPalletPlaceIsNotReserved($rackId, $palletPlace) {
        $palletPlaceArr    = explode("-", $palletPlace);
        $palletPlaceRow    = $palletPlaceArr[1];  
        $palletPlaceColumn = $palletPlaceArr[2];
        
        $detailsForRackArr = store_RackDetails::getDetailsForRack($rackId);
        
        if (empty($detailsForRackArr)) {
            return TRUE;
        } else {
            // Only details for 'reserved'
            foreach ($detailsForRackArr as $k => $v) {
                if ($v['action'] != 'reserved') {
                    unset($detailsForRackArr[$k]);
                }
            }
            // ENDOF Only details for 'reserved'        
            
            if (empty($detailsForRackArr)) {
               return TRUE;
            } else {
                foreach ($detailsForRackArr as $k => $v) {
                    // Проверка за тази позиция в детайлите
                    if (array_key_exists($palletPlace, $detailsForRackArr)) {
                        return FALSE;
                    } else return TRUE;
                }
            }
        }
    }    
        
}
