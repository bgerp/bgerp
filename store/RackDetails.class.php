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
        $this->FNC('num',     'int',                   'caption=№, notSorting');
        $this->FLD('rackId',  'key(mvc=store_racks)',  'caption=Палет място->Стелаж, input=hidden');
        $this->FLD('rRow',    'enum(A,B,C,D,E,F,G,H)', 'caption=Палет място->Ред');
        $this->FLD('rColumn', 'int(2)',                'caption=Палет място->Колона');
        $this->FLD('action',  'enum(forbidden=забранен, 
                                    maxWeight=макс. тегло, 
                                    maxWidth=макс. широчина,
                                    maxHeight=макс. височина,
                                    constrColumnsStep=носещи колони през брой палет места)', 'caption=Действие');
        $this->FLD('metric',  'double(decimals=2)',    'caption=Мярка (кг)');
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
        
        if ($rec->action == 'constrColumnsStep') {
        	$row->rRow = 'ALL';
        	$row->rColumn = 'ALL';
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

           	// Ако имаме стъпка на носещите колони, тогава полетата за ред и колона са NULL
	    	if ($rec->action == 'constrColumnsStep') {
				if (!preg_match('/^[1-5]{1}$/', $rec->metric)) {
					$form->setError('action', '<b>Носещи колони през брой палет места</b> трябва да е цяло число от 1 до 5');					
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
    	$query = store_RackDetails::getQuery();

        while($rec = $query->fetch("#rackId = {$rackId}")) {
        	if ($rec->state == "closed") {
        		$detailsForRackArr[] = $rec->rackId . "-" . $rec->rRow . "-" . $rec->rColumn;
        	}
        }

        return $detailsForRackArr;
    }
    
    
    /**
     * В случай, че детайла носи информация за носещите колони
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_BeforeSave($mvc,&$id,$rec)
    {
    	if ($rec->action = constrColumnsStep) {
    		// Ако имаме стъпка на носещи колони, тогава полетата за ред и колона са NULL
    		$rec->rRow = NULL;
    		$rec->rColumn = NULL;

	    	/* Можем да имаме за даден стелаж само едно дефиниране на носещи колони */
	    	// Ако правим нов запис в детайлите 
	    	if (!$rec->id) {
	    		if ($mvc->fetchfield("#rackId = {$rec->rackId} AND #action = 'constrColumnsStep'", 'id')) {
	    			$rec->id = $mvc->fetchfield("#rackId = {$rec->rackId} AND #action = 'constrColumnsStep'", 'id');
	    		}
	    	}
	    	
	    	// Ако редактираме запис
	        if ($rec->id) {
	    		if ($mvc->fetchfield("#rackId = {$rec->rackId} AND #action = 'constrColumnsStep'", 'id')) {
	    			$mvc->delete($rec->id);
	    			
	    			$rec->id = $mvc->fetchfield("#rackId = {$rec->rackId} AND #action = 'constrColumnsStep'", 'id');
	    		}
	    	}
	    	/* END Можем да имаме за даден стелаж само едно дефиниране на носещите колони */
    	}
    }    

}