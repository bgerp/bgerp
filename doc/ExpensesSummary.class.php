<?php 


/**
 * История на използванията на разходните пера
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ExpensesSummary extends core_Manager
{
    
	
	/**
     * Заглавие
     */
    public $title = "История на разходните пера";
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption = Контейнер');
    	$this->FLD('count', 'int', 'notNull,value=0,caption=Брой');
    	$this->FLD('data', 'blob(serialize, compress)', 'input=none');
    	
        $this->setDbUnique('containerId');
    }
    
    
    /**
     * Връща броя разходи към документа
     * 
     * @param int $containerId - ид на контейнера
     * @return string $html - броя документи
     */
    public static function getSummary($containerId)
    {
    	$html = '';
    	
    	$expenseCount = self::fetchField("#containerId = {$containerId}", 'count');
    	if(isset($expenseCount)){
    		$count = cls::get('type_Int')->toVerbal($expenseCount);
    		$actionVerbal = tr('разходи');
    		$actionTitle = 'Показване на разходите към документа';
    		$document = doc_Containers::getDocument($containerId);
    		
    		if(haveRole('acc,ceo') && $document->haveRightFor('single')){
    			$linkArr = array($document->getInstance(), 'single', $document->that, 'Sid' =>  $containerId);
    		}
    		$link = ht::createLink("<b>{$count}</b><span>{$actionVerbal}</span>", $linkArr, FALSE, array('title' => $actionTitle));
    	
    		$html .= "<li class=\"action expenseSummary\">{$link}</li>";
    	}
    	
    	return $html;
    }
    
    
    /**
     * Подготвяме показването на разходите към документа
     * 
     * @param stdClass $data
     * @return void
     */
    public function prepareExpenses(&$data)
    {
    	// Вземаме cid от URL' то
        $cid = Request::get('Sid', 'int');
        $masterRec = $data->masterData->rec;
        
        // Ако не листваме данните за съответния контейнер
        if ($masterRec->containerId != $cid && !haveRole('acc,ceo')) {
        	$data->renderExpenses = FALSE;
        	return;
        }
        
        // Намираме кеширания запис за контейнера
        $rec = self::fetch("#containerId = {$masterRec->containerId}");
        if(!$rec){
        	$data->renderExpenses = FALSE;
        	return;
        }
        
        // Ако има записи вербализираме ги
        $data->rows = array();
        $data->recs = $rec->data;
       
        if(is_array($rec->data)){
        	foreach ($rec->data as $index => $r){
        		$data->rows[$index] = $this->getVerbalRow($r);
        	}
        }
    }
    
    
    /**
     * Вербализира записа за разхода
     * 
     * @param stdClass $rec
     * @return stdClass $row
     */
    private function getVerbalRow($rec)
    {
    	$row = new stdClass();
    	if(isset($rec->docId)){
    		$row->docId = cls::get($rec->docType)->getLink($rec->docId, 0);
    	}
    	
    	if(isset($rec->item2Id)){
    		$row->item2Id = acc_Items::getVerbal($rec->item2Id, 'titleLink');
    	} else {
    		$row->item2Id = tr('Не отнесени');
    	}
    	
    	$row->ROW_ATTR['class'] = ($rec->type == 'corrected') ? 'state-closed' : 'state-active';
    	
    	// Вербализиране на числата
    	foreach (array('quantity', 'amount') as $fld){
    		$Double = cls::get('type_Double');
    		$row->{$fld} = $Double->toVerbal($rec->{$fld});
    	}
    	
    	$row->valior = cls::get('type_Date')->toVerbal($rec->valior);
    	
    	if($rec->type == 'corrected'){
    		if($rec->notDistributed !== TRUE){
    			unset($row->docId, $row->valior);
    		}
    		
    		$item1rec = acc_Items::fetch($rec->item1Id);
    		
    		if($item1rec->classId == store_Stores::getClassId()){
    			$item1 = acc_Items::getVerbal($item1rec, 'titleLink');
    			$row->item2Id = "<b>{$row->item2Id}</b>";
    			$row->item2Id .= tr("|* |в склад|* <b>{$item1}</b>");
    		}
    		
    		if($rec->notDistributed !== TRUE){
    			$row->item2Id = tr('за') . " {$row->item2Id}";
    		}
    		
    		$row->item2Id = "<div class='small'>{$row->item2Id}<div>";
    	}
    	
    	return $row;
    }
    
    
    /**
     * Рендиране на разходите
     * 
     * @param stdClass $data
     * @return void|core_ET
     */
    public function renderExpenses($data)
    {
    	if($data->renderExpenses === FALSE) return;
    	
    	$tpl = new core_ET("");
    	$FieldSet = new core_FieldSet();
    	$FieldSet->FLD('quantity', 'double');
    	$FieldSet->FLD('amount', 'double(minDecimals=2)');
    	
    	$table = cls::get('core_TableView', array('mvc' => $FieldSet));
    	
    	// Подравняване на числата
    	plg_AlignDecimals2::alignDecimals($FieldSet, $data->recs, $data->rows);
    	
    	// Ако има отрицателни числа се оцветяват в червено
    	if(is_array($data->recs)){
    		foreach ($data->recs as $index => $rec){
    			foreach (array('quantity', 'amount') as $fld){
    				if($rec->type == 'corrected'){
    					$data->rows[$index]->{$fld} = "<small>{$data->rows[$index]->{$fld}}</small>";
    				}
    				
    				if($rec->{$fld} < 0){
    					$data->rows[$index]->{$fld} = "<span class='red'>{$data->rows[$index]->{$fld}}</span>";
    				}
    			}
    		}
    	}
    	
    	$currencyCode = acc_Periods::getBaseCurrencyCode();
    	
    	// Рендиране на таблицата
    	$tableHtml = $table->get($data->rows, "valior=Вальор,item2Id=Артикул,docId=Документ,quantity=Количество,amount=Сума|* <small>({$currencyCode}</small>)");
    	
    	$tpl->append($tableHtml);
    	
    	return $tpl;
    }
    
    
    /**
     * Обновява кеша на направените разходи към документа
     * 
     * @param int $containerId - ид на контейнера
     * @param mixed $itemRec - запис или ид на перото
     * @return void
     */
    public static function updateSummary($containerId, $itemRec)
    {
    	$itemRec = acc_Items::fetchRec($itemRec);
    	
    	// Кой запис отговаря на контейнера
    	$rec = self::fetch("#containerId = {$containerId}");
    	if(!$rec){
    		$rec = (object)array('containerId' => $containerId);
    		self::save($rec);
    	}
    	
    	$recs = $allocated = array();
    	
    	// Извличаме от журнала направените записи за разхода
    	$entries = acc_Journal::getEntries($itemRec);
    	$accId = acc_Accounts::getRecBySystemId('60201')->id;
    	
    	if(is_array($entries)){
    		foreach($entries as $ent){
    			$add = FALSE;
    			
    			if($ent->debitItem1 == $itemRec->id && $ent->debitAccId == $accId){
    				$add = TRUE;
    				$arr = &$recs;
    				$type = 'allocated';
    				$side = 'debit';
    			} elseif($ent->creditItem1 == $itemRec->id && $ent->creditAccId == $accId){
    				$add = TRUE;
    				$arr = &$allocated;
    				$side = 'credit';
    				$type = 'corrected';
    			}
    			
    			if($add === TRUE){
    				$index = $ent->docType . "|" . $ent->docId . "|" . $ent->{"{$side}AccId"} . "|" . $ent->{"{$side}Item1"} . "|" . $ent->{"{$side}Item2"} . "|" . $ent->{"{$side}Item3"};
    				$r = (object)array('docType'  => $ent->docType,
    								   'docId'    => $ent->docId,
    						           'accId'    => $ent->{"{$side}AccId"},
    						           'item1Id'  => $ent->{"debitItem1"},
    						           'item2Id'  => $ent->{"debitItem2"},
    						           'item3Id'  => $ent->{"debitItem3"},
    						           'index'   => $index,
    						           'valior'   => $ent->valior,
    						           'quantity' => $ent->{"{$side}Quantity"},
    						           'type'     => $type,
    						           'amount'   => $ent->amount,);
    				$arr[] = $r;
    			}
    		}
    	}
    	
    	$rec->count = count($recs);
    	$notDistributed = $allocated;
    	
    	foreach ($recs as $rec1){
    		$index = $rec1->index;
    		$res[] = $rec1;
    		
    		// Отделяне на тези записи, които съдържат текущия маркер
    		$foundArr = array_filter($allocated, function ($e) use ($index) {
    			return $e->index == $index;
    		});
    		
    		if(count($foundArr)){
    			$notDistributed = array_diff_key($notDistributed, $foundArr);
    			$res = array_merge($res, $foundArr);
    		}
    	}
    
    	if(count($notDistributed)){
    		$res[] = (object)array('type' => 'allocated');
    		foreach ($notDistributed as &$nRec){
    			$nRec->notDistributed = TRUE;
    		}
    		$res = array_merge($res, $notDistributed);
    	}
    	
    	// Кеширане на данните и бройката за контейнера
    	$rec->data = $res;
    	self::save($rec, 'data,count');
    }
    
    
    function act_Test()
    {
    	self::updateSummary('165465', '3265');
    }
}