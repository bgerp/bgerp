<?php 


/**
 * История на използванията на разходните пера
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ExpensesSummary extends core_Manager
{
    
	public $oldClassName = 'doc_CostObjectSummaries';
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
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper';
    
    
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
     * @param stdClass $r
     * @return stdClass $row
     */
    private function getVerbalRow($r)
    {
    	$row = new stdClass();
    	$row->docId = cls::get($r->docType)->getLink($r->docId, 0);
    	$row->item2Id = acc_Items::getVerbal($r->item2Id, 'titleLink');
    	
    	foreach (array('quantity', 'amount') as $fld){
    		$Double = cls::get('type_Double');
    		$row->{$fld} = $Double->toVerbal($r->{$fld});
    	}
    	
    	$row->valior = cls::get('type_Date')->toVerbal($r->valior);
    	
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
    				if($rec->{$fld} < 0){
    					$data->rows[$index]->{$fld} = "<span class='red'>{$data->rows[$index]->{$fld}}</span>";
    				}
    			}
    		}
    	}
    	
    	// Рендиране на таблицата
    	$tableHtml = $table->get($data->rows, 'valior=Вальор,item2Id=Артикул,docId=Документ,quantity=Количество,amount=Сума');
    	
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
    	
    	$recs = array();
    	
    	// Извличаме от журнала направените записи за разхода
    	$entries = acc_Journal::getEntries($itemRec);
    	$accId = acc_Accounts::getRecBySystemId('60201')->id;
    	
    	if(is_array($entries)){
    		foreach($entries as $ent){
    			foreach (array('debit', 'credit') as $type){
    				if($ent->{"{$type}Item1"} == $itemRec->id && $ent->{"{$type}AccId"} == $accId){
    					$sign = ($type == 'debit') ? 1 : -1;
    					
    					$r = (object)array('docType'   => $ent->docType, 
    									   'docId'     => $ent->docId,
    									   'accId'     => $ent->{"{$type}AccId"},
    									   'item1Id'   => $ent->{"{$type}Item1"},
    									   'item2Id'   => $ent->{"{$type}Item2"},
    									   'item3Id'   => $ent->{"{$type}Item3"},
    									   'valior'    => $ent->valior,
    									   'quantity'  => $sign * $ent->{"{$type}Quantity"},
    									   'amount'    => $sign * $ent->amount,);
    					$recs[] = $r;
    				}
    			}
    		}
    	}
    	
    	// Кеширане на данните и бройката за контейнера
    	$rec->data = $recs;
    	$rec->count = count($recs);
    	self::save($rec, 'data,count');
    }
}