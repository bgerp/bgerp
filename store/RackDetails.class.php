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
        $this->FNC('num',     'int',                       'caption=№, notSorting');
        $this->FLD('rackId',  'key(mvc=store_Racks)',      'caption=Палет място->Стелаж, input=hidden');
        $this->FLD('rRow',    'enum(A,B,C,D,E,F,G,H)',     'caption=Палет място->Ред');
        $this->FLD('rColumn', 'varchar(3)',                'caption=Палет място->Колона');
        $this->FLD('action',  'enum(forbidden=забранено палет място, 
                                    maxWeight=макс. тегло (кг), 
                                    maxWidth=макс. широчина (м),
                                    maxHeight=макс. височина (м))', 'caption=Действие->Име');
        $this->FLD('metric',  'double(decimals=2)',                 'caption=Действие->Параметър');
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
        	
            $palletsInStoreArr = store_Pallets::getPalletsInStore();        	
        	
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
            
        	$recRacks = store_Racks::fetch("#id = {$rec->rackId}");
        	
            if (empty($rec->rColumn)) {
                $form->setError('rColumn', 'Моля, въведете колона');
            }        	
        	
        	if ($rackRowsArr[$rec->rRow] > $recRacks->rows) {
        	    $form->setError('rRow', 'Няма такъв ред в палета. Най-големия ред е ' . $rackRowsArrRev[$recRacks->rows] . '.');
        	}
        	
            if ($rec->rColumn > $recRacks->columns) {
                $form->setError('rColumn', 'Няма такава колона в палета. Най-голямата колона е ' . $recRacks->columns . '.');
            }
            
            if (isset($palletsInStoreArr[$rec->rackId][$rec->rRow][$rec->rColumn])) {
            	$form->setError('rRow,rColumn', 'За това палет място не може да се добавят детайли, 
            	                                 <br/>защото е заето или има наредено движение към него!');
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
	   		$palletPlace = $rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn; 
        	
	   		$deatailsRec['action'] = $rec->action;
	   		$deatailsRec['metric'] = $rec->metric;
       		
	   		$detailsForRackArr[$palletPlace] = $deatailsRec;
        }

        return $detailsForRackArr;
    }
    
    
    /**
     * Проверка дали това палет място присъства в детайлите и дали е забранено
     * @param int $rackId
     * @param string $palletPlace
     * @return boolean
     */
    public static function checkIfPalletPlaceIsNotForbidden($rackId, $palletPlace) {
        $detailsForRackArr = store_RackDetails::getDetailsForRack($rackId);
        
        // Проверка за това палет място в детайлите
        if (!empty($detailsForRackArr) && array_key_exists($palletPlace, $detailsForRackArr)) {
            if ($detailsForRackArr[$palletPlace]['action'] == 'forbidden') {
                return FALSE;
            }  else return TRUE; 
        }  else return TRUE;
    }    
    
}