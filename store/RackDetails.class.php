<?php 
/**
 * Менаджира детайлите на стелажите (Details)
 */
class store_RackDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Палет места, които не могат да бъдат използвани";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Логистика";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, store_Wrapper, plg_State2';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'rackId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'tools=Пулт, rackId, rRow, rColumn, state=Състояние';
    
    
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
        $this->FNC('num',     'int',                   'caption=№, notSorting');
        $this->FLD('rackId',  'key(mvc=store_racks)',  'caption=Палет място->Стелаж, input=hidden');
        $this->FLD('rRow',    'enum(A,B,C,D,E,F,G,H)', 'caption=Палет място->Ред');
        $this->FLD('rColumn', 'int(2)',                'caption=Палет място->Колона');
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
        // Prpare 'Num'
        static $num;
        $num += 1;
        $row->num .= $num;
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

        // array digit to letter
        $rackRowsArrRev = array('1' => A,
                                '2' => B,
                                '3' => C,
                                '4' => D,
                                '5' => E,
                                '6' => F,
                                '7' => G,
                                '8' => H);	        
            
        	$recMaster = store_Racks::fetch("#id = {$rec->rackId}");
        	
        	if ($rackRowsArr[$rec->rRow] > $recMaster->rows) {
        	    $form->setError('rRow', 'Няма такъв ред в палета. Най-големия ред е ' . $rackRowsArrRev[$recMaster->rows] . '.');
        	}
        	
            if ($rec->rColumn > $recMaster->columns) {
                $form->setError('rColumn', 'Няма такава колона в палета. Най-голямата колона е ' . $recMaster->columns . '.');
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
    	$query = store_RackDetails::getQuery();

        while($rec = $query->fetch("#rackId = {$rackId}")) {
        	if ($rec->state == "closed") {
        		$detailsForRackArr[] = $rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn;
        	}
        }

        return $detailsForRackArr;
    }

}