<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на кореспонденция по сметки
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_CorespondingReportImpl extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Кореспонденция по сметка';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 30;
    
    
    /**
     * Работен кеш
     */
    public $cache = array();
    
    
    /**
     * Работен кеш
     */
    public $cache2 = array();
    
    
    /**
     * Работен кеш
     */
    public $cache3 = array();
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_Form &$form)
    {
    	// Добавяме полетата за филтър
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    	$form->FLD('baseAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Основна,mandatory,silent,removeAndRefreshForm=groupBy');
    	$form->FLD('corespondentAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Кореспондент,mandatory,silent,removeAndRefreshForm=groupBy');
    	$form->FLD('side', 'enum(all=Всички,debit=Дебит,credit=Кредит)', 'caption=Обороти,removeAndRefreshForm=orderField|orderBy,silent');
    	$form->FLD('orderBy', 'enum(,DESC=Низходящо,ASC=Възходящо)', 'caption=Сортиране->Вид,silent,removeAndRefreshForm=orderField,formOrder=100');
    	$form->FLD('orderField', 'enum(,debitQuantity=Дебит к-во,debitAmount=Дебит сума,creditQuantity=Кредит к-во,creditAmount=Кредит сума,blQuantity=Остатък к-во,blAmount=Остатък сума)', 'caption=Сортиране->Поле,formOrder=101');
    	
    	$this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
    	// Поставяме удобни опции за избор на период
    	$op = acc_Periods::getPeriodOptions();
    
    	$form->setSuggestions('from', array('' => '') + $op->fromOptions);
    	$form->setSuggestions('to', array('' => '') + $op->toOptions);
    	 
    	if(isset($form->rec->baseAccountId) && isset($form->rec->corespondentAccountId)){
    		$baseAccInfo = acc_Accounts::fetch($form->rec->baseAccountId);
    		$corespAccInfo = acc_Accounts::fetch($form->rec->corespondentAccountId);
    
    		$baseGroups = $sets = array();
    
    		foreach (range(1, 3) as $i){
    			if(isset($baseAccInfo->{"groupId{$i}"})){
    				if(!isset($sets[$baseAccInfo->{"groupId{$i}"}])){
    					$sets[$baseAccInfo->{"groupId{$i}"}] = acc_Lists::getVerbal($baseAccInfo->{"groupId{$i}"}, 'name');
    				}
    				$baseGroups[$baseAccInfo->{"groupId{$i}"}] = $baseAccInfo->{"groupId{$i}"};
    			}
    			 
    			if(isset($corespAccInfo->{"groupId{$i}"})){
    				if(!isset($sets[$corespAccInfo->{"groupId{$i}"}])){
    					$sets[$corespAccInfo->{"groupId{$i}"}] = acc_Lists::getVerbal($corespAccInfo->{"groupId{$i}"}, 'name');
    				}
    			}
    		}
    		
    		// Добавяме поле за групиране ако има по какво
    		if(count($sets)){
    			$sets = arr::fromArray($sets);
    			$defaults = implode(',', $baseGroups);
    			$form->FLD('groupBy', "set({$sets})", 'caption=Групиране по');
    			$form->setDefault('groupBy', $defaults);
    		}
    	}
    	 
    	if(isset($form->rec->orderBy) && $form->rec->orderBy != ''){
    		
    		if(isset($form->rec->side)){
    			if($form->rec->side == 'credit'){
    				$options = arr::make('creditQuantity=Кредит к-во,creditAmount=Кредит сума');
    			} elseif($form->rec->side == 'debit') {
    				$options = arr::make('debitQuantity=Дебит к-во,debitAmount=Дебит сума');
    			} else {
    				$options = arr::make('debitQuantity=Дебит к-во,debitAmount=Дебит сума,creditQuantity=Кредит к-во,creditAmount=Кредит сума,blQuantity=Остатък к-во,blAmount=Остатък сума');
    			}
    		
    			$form->setOptions('orderField', $options);
    		}
    	} else {
    		$form->setField('orderField', 'input=none');
    	}
    	
    	$this->invoke('AfterPrepareEmbeddedForm', array($form));
    }
    

    /**
     * Рендира вътрешната форма като статична форма в подадения шаблон
     *
     * @param core_ET $tpl - шаблон
     * @param string $placeholder - плейсхолдър
     */
    protected function prependStaticForm(core_ET &$tpl, $placeholder = NULL)
    {
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    	$form->rec = $this->innerForm;
    	$form->class = 'simpleForm';
    
    	$tpl->prepend($form->renderStaticHtml(), $placeholder);
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	// Проверяваме дали началната и крайната дата са валидни
    	if($form->isSubmitted()){
    		if($form->rec->to < $form->rec->from){
    			$form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
    		}
    		
    		if($form->rec->orderBy == ''){
    			unset($form->rec->orderBy);
    		}
    	}
    }
        
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
    	$data->summary = (object)array('debitQuantity' => 0, 'debitAmount' => 0, 'creditQuantity' => 0, 'creditAmount' => 0, 'blQuantity' => 0, 'blAmount' => 0);
    	$data->hasSameAmounts = TRUE;
    	$data->rows = $data->recs = array();
    	$form = $this->innerForm;
    	
    	$data->groupBy = arr::make($this->innerForm->groupBy, TRUE);
    	$data->groupBy = array_values($data->groupBy);
    	
    	array_unshift($data->groupBy, null);
    	unset($data->groupBy[0]);
    	$this->prepareListFields($data);
    	
    	// Извличаме записите от журнала за периода, където участват основната и кореспондиращата сметка
    	$jQuery = acc_JournalDetails::getQuery();
    	acc_JournalDetails::filterQuery($jQuery, $form->from, $form->to);
    	$jQuery->where("#debitAccId = {$form->baseAccountId} AND #creditAccId = {$form->corespondentAccountId}");
    	$jQuery->orWhere("#debitAccId = {$form->corespondentAccountId} AND #creditAccId = {$form->baseAccountId}");
    	
    	// За всеки запис добавяме го към намерените резултати
    	while($jRec = $jQuery->fetch()){
    		$this->addEntry($form->baseAccountId, $jRec, $data, $form->groupBy);
    	}
    	
    	// Ако има намерени записи
    	if(count($data->recs)){ 
    		
    		// За всеки запис
    		foreach ($data->recs as &$rec){
    			
    			// Изчисляваме окончателния остатък (дебит - кредит)
    			$rec->blQuantity = $rec->debitQuantity - $rec->creditQuantity;
    			$rec->blAmount = $rec->debitAmount - $rec->creditAmount;
    			
    			foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
    				$data->summary->{$fld} += $rec->{$fld};
    			}
    			
    			// Проверка дали сумата и к-то са еднакви
    			if($rec->blQuantity != $rec->blAmount){
    				$data->hasSameAmounts = FALSE;
    			}
    		}
    	}
    	
    	// Ако не се групира по размерна сметка, не показваме количества
    	if(count($data->groupBy)){
    		$data->hasDimensional = FALSE;
    		foreach ($data->groupBy as $grId){
    			if(acc_Lists::fetchField($grId, 'isDimensional') == 'yes'){
    				$data->hasDimensional = TRUE;
    			}
    		}
    	}
    	
		// Обработваме обобщената информация
    	$this->prepareSummary($data);
		
    	return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$data)
    {
    	// Ако има намерени записи
    	if(count($data->recs)){
    		
    		// Подготвяме страницирането
    		$pageVar = str::addHash("P", 5, "{$mvc->className}{$mvc->EmbedderRec->that}");
    		$data->Pager = cls::get('core_Pager',  array('pageVar' => $pageVar, 'itemsPerPage' => $mvc->listItemsPerPage));
    		$data->Pager->itemsCount = count($data->recs);
    		
    		foreach ($data->recs as $rec1){
    			foreach (range(1, 6) as $i){
    				if(!empty($rec1->{"item{$i}"})){
    					$mvc->cache[$rec1->{"item{$i}"}] = $rec1->{"item{$i}"};
    				}
    			}
    		}
    		
    		// Кешираме номерата на перата в отчета
    		if(count($mvc->cache)){
    			$iQuery = acc_Items::getQuery();
    			$iQuery->show("num");
    			$iQuery->in('id', $mvc->cache);
    			 
    			while($iRec = $iQuery->fetch()){
    				$mvc->cache[$iRec->id] = $iRec->num;
    			}
    		}
    		
    		if(!$mvc->innerForm->orderBy){
    			// Подготвяме поле за сортиране по номерата на перата
    			foreach ($data->recs as &$rec){
    				$rec->sortField = '';
    				foreach (range(1, 3) as $j){
    					if(isset($rec->{"item{$j}"})){
    						$rec->sortField .= $mvc->cache[$rec->{"item{$j}"}];
    					}
    				}
    				
    				$rec->sortField = strtolower(str::utf2ascii($rec->sortField));
    			}
    			
    			// Сортираме записите според полето за сравнение
    			usort($data->recs, array($mvc, "sortRecs"));
    		} else {
    			
    			// Ако има избрано поле за сортиране, сортираме по него
    			arr::order($data->recs, $mvc->innerForm->orderField, $mvc->innerForm->orderBy);
    		}
    		
    		
    		// За всеки запис
    		foreach ($data->recs as &$rec){
    			
    			// Ако не е за текущата страница не го показваме
    			if(!$data->Pager->isOnPage()) continue;
    			
    			// Вербално представяне на записа
    			$data->rows[] = $mvc->getVerbalRec($rec);
    		}
    	}
  
    }
    
    
    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
    	$tpl = getTplFromFile('acc/tpl/CorespondingReportLayout.shtml');
    	 
    	return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
    	if(empty($data)) return;
    	
    	$tpl = $this->getReportLayout();
    	$tpl->replace($this->getReportTitle(), 'TITLE');
    	
    	 
    	$tpl->placeObject($data->summary);
    	$tpl->replace(acc_Periods::getBaseCurrencyCode(), 'baseCurrencyCode');
    	 
    	// Кои полета ще се показват
    	$fields = arr::make("debitQuantity=Дебит->К-во,debitAmount=Дебит->Сума,creditQuantity=Кредит->К-во,creditAmount=Кредит->Сума,blQuantity=Остатък->К-во,blAmount=Остатък->Сума", TRUE);
    	$newFields = array();
    
    	if(count($data->groupBy)){
    		foreach ($data->groupBy as $id => $grId){
    			$newFields["item{$id}"] = acc_Lists::fetchField($grId, 'name');
    		}
    	}
    	 
    	if(count($newFields)){
    		$fields = $newFields + $fields;
    	}
    	 
    	// Ако к-та и сумите са еднакви, не показваме количествата
    	if($data->hasSameAmounts === TRUE || $data->hasDimensional !== TRUE){
    		unset($fields['debitQuantity']);
    		unset($fields['creditQuantity']);
    		unset($fields['blQuantity']);
    	}
    	 
    	if($this->innerForm->side){
    		if($this->innerForm->side == 'debit'){
    			unset($fields['creditQuantity'], $fields['creditAmount'], $fields['blQuantity'], $fields['blAmount']);
    		}elseif($this->innerForm->side == 'credit'){
    			unset($fields['debitQuantity'], $fields['debitAmount'], $fields['blQuantity'], $fields['blAmount']);
    		}
    	}
    	 
    	$f = cls::get('core_FieldSet');
    	$f->FLD('item1', 'varchar', 'tdClass=itemClass');
    	$f->FLD('item2', 'varchar', 'tdClass=itemClass');
    	$f->FLD('item3', 'varchar', 'tdClass=itemClass');
    	$f->FLD('item4', 'varchar', 'tdClass=itemClass');
    	$f->FLD('item5', 'varchar', 'tdClass=itemClass');
    	$f->FLD('item6', 'varchar', 'tdClass=itemClass');
    	foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
    		$f->FLD($fld, 'int', 'tdClass=accCell');
    	}
    	 
    	// Рендираме таблицата
    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$tableHtml = $table->get($data->rows, $fields);
    	 
    	$tpl->replace($tableHtml, 'CONTENT');
    	 
    	// Рендираме пейджъра, ако го има
    	if(isset($data->Pager)){
    		$tpl->replace($data->Pager->getHtml(), 'PAGER');
    	}
    	 
    	// Показваме данните от формата
    	$form = cls::get('core_Form');
    	$this->addEmbeddedFields($form);
    	$form->setField('baseAccountId', 'caption=Основна с-ка');
    	$form->setField('corespondentAccountId', 'caption=Кореспондент с-ка');
    	$form->rec = $this->innerForm;
    	$form->class = 'simpleForm';
    
    	$this->prependStaticForm($tpl, 'FORM');
    	 
    	$embedderTpl->append($tpl, 'data');
    }
    
    
    /**
     * Вербално представяне на групираните записи
     *
     * @param stdClass $rec - групиран запис
     * @return stdClass $row - вербален запис
     */
    protected static function getVerbalRec($rec)
    {
    	$row = new stdClass();
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	 
    	// Вербалното представяне на перата
    	foreach (range(1, 6) as $i){
    		if(!empty($rec->{"item{$i}"})){
    			$row->{"item{$i}"} = acc_Items::getVerbal($rec->{"item{$i}"}, 'titleLink');
    		}
    	}
    	 
    	// Вербално представяне на сумите и к-та
    	foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
    		if(isset($rec->{$fld})){
    			$row->{$fld} = $Double->toVerbal($rec->{$fld});
    			if($rec->{$fld} < 0){
    				$row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
    			}
    		}
    	}
    	
    	$row->measure = $rec->measure;
    
    	// Връщаме подготвеното вербално рпедставяне
    	return $row;
    }
    
    
    /**
     * Подготвя обобщената информация
     *
     * @param stdClass $data
     */
    private function prepareSummary(&$data)
    {
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	 
    	foreach ((array)$data->summary as $index => $fld){
    		$f = $data->summary->{$index};
    		$data->summary->{$index} = $Double->toVerbal($f);
    		if($f < 0){
    			$data->summary->{$index} = "<span class='red'>{$data->summary->{$index}}</span>";
    		}
    	}
    }
    
    
    /**
     * Филтриране на записите по код
     * Подрежда кодовете или свойствата във възходящ ред.
     * Ако първата аналитичност са еднакви, сравнява по кодовете на втората ако и те по тези на третата
     */
    private function sortRecs($a, $b)
    {
    	if($a->sortField == $b->sortField) return 0;
    
    	return (strnatcasecmp($a->sortField, $b->sortField) < 0) ? -1 : 1;
    }
    
    
    /**
     * Групира записите от журнала по пера
     * 
     * @param int      $baseAccountId - Ид на основната сметка
     * @param stdClass $jRec          - запис от журнала
     * @param array    $recs          - групираните записи
     * @return void
     */
    private function addEntry($baseAccountId, $jRec, &$data, $groupBy)
    {
    	$recs = &$data->recs;
    	
    	// Обхождаме дебитната и кредитната част, И намираме в какви номенклатури имат сметките
    	foreach (array('debit', 'credit') as $type){
    		if(!isset($this->cache2[$jRec->{"{$type}AccId"}])){
    			$this->cache2[$jRec->{"{$type}AccId"}] = acc_Accounts::fetch($jRec->{"{$type}AccId"}, 'groupId1,groupId2,groupId3');
    		}
    	}
    	
    	$debitGroups = $this->cache2[$jRec->debitAccId];
    	$creditGroups = $this->cache2[$jRec->creditAccId];
    	
    	// Кешираме всяко перо на коя позиция трябва да се показва
    	$index = array();
    	foreach (array('debit', 'credit') as $type){
    		foreach (range(1, 3) as $i){
    			$groups = ${"{$type}Groups"};
    			if(in_array($groups->{"groupId{$i}"}, $data->groupBy)){
    				$index[$jRec->{"{$type}Item{$i}"}] = $jRec->{"{$type}Item{$i}"};
    				$this->cache3[$jRec->{"{$type}Item{$i}"}] = array_search($groups->{"groupId{$i}"}, $data->groupBy);
    			}
    		}
    	}
    	
    	$index = implode('|', $index);
    	
    	// Ако записите няма обект с такъв индекс, създаваме го
    	if(!array_key_exists($index, $recs)){
    		$recs[$index] = new stdClass();
    		$indexArr = explode('|', $index);
    		
    		// Проверяваме на коя позиция перата от индекса трябва да се показват
    		if(count($indexArr)){
    			foreach ($indexArr as $itemId){
    				$key = $this->cache3[$itemId];
    				$recs[$index]->{"item{$key}"} = $itemId;
    			}
    		}
    	}
    	
    	// Сумираме записите
    	foreach (array('debit', 'credit') as $type){
    		
    		// Пропускаме движенията от сметката кореспондент
    		if($jRec->{"{$type}AccId"} != $baseAccountId) continue;
    		
    		// Сумираме дебитния или кредитния оборот
    		$quantityFld = "{$type}Quantity";
    		$amountFld = "{$type}Amount";
    		
    		$recs[$index]->{$quantityFld} += $jRec->{"{$type}Quantity"};
    		$recs[$index]->{$amountFld} += $jRec->amount;
    	}
    }
    
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
    	
    }

    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
    	$innerState = &$this->innerState;
    	if(count($innerState->rows)){
    		foreach ($innerState->rows as $row){
    			foreach (array('debitAmount', 'debitQuantity','creditAmount', 'creditQuantity', 'blQuantity', 'blAmount') as $fld){
    				unset($row->$fld);
    			}
    		}
    	}
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
    	$activateOn = "{$this->innerForm->to} 23:59:59";
    	
    	return $activateOn;
    }
    
    
    /**
     * Ако имаме в url-то export създаваме csv файл с данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * $todo да се замени в кода
     */
    public function exportCsv()
    {
    
    	$conf = core_Packs::getConfig('core');
    
    	if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
    		redirect(array($this), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
    	}
    
    	$csv = "";
    
    	// генериран хедър
    	$header = $this->generateHeader()->header;
    	
    	// Кешираме номерата на перата в отчета
    	$iQuery = acc_Items::getQuery();
    	$iQuery->show("num");
    	
    	while($iRec = $iQuery->fetch()){
    		$mvc->cache[$iRec->id] = $iRec->num;
    	}
    	
    	// Подготвяме поле за сортиране по номерата на перата
    	foreach ($this->innerState->recs as &$rec){ 
    		$rec->sortField = '';
    		foreach (range(1, 3) as $j){
    			if(isset($rec->{"item{$j}"})){
    				$rec->sortField .= $mvc->cache[$rec->{"item{$j}"}];
    			}
    		}
    		 
    		$rec->sortField = strtolower(str::utf2ascii($rec->sortField));
    	}
    	
    	// Сортираме записите според полето за сравнение
    	usort($this->innerState->recs, array($this, "sortRecs"));

    	if(count($this->innerState->recs)) { 
    		foreach ($this->innerState->recs as $id => $rec) {
    
    			$rCsv = $this->generateCsvRows($rec);
    
    			$csv .= $rCsv;
    			$csv .=  "\n";
    
    		}
    			
    		$csv = $header . "\n" . $csv;
    	} /*else {
    		$csv = $header . "\n" . $lastRow . "\n" . $zeroRow;
    	}*/
    
    	return $csv;
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getExportFields_()
    {

    	// Кои полета ще се показват
    	$fields = arr::make("debitQuantity=Дебит - количество,debitAmount=Дебит - cума,creditQuantity=Кредит - количество,creditAmount=Кредит - сума,blQuantity=Остатък - количество,blAmount=Остатък - сума", TRUE);
    	$newFields = array();
    	
    	if(count($this->innerState->groupBy)){
    		foreach ($this->innerState->groupBy as $id => $grId){
    			$newFields["item{$id}"] = acc_Lists::fetchField($grId, 'name');
    		}
    	}
    	
    	if(count($newFields)){
    		$fields = $newFields + $fields;
    	}
    
    	return $fields;
    }
    
    
    /**
     * Ще направим заглавито на колонките
     * според това дали ще има скрити полета
     *
     * @return stdClass
     * @todo да се замени в кода по-горе
     */
    protected function generateHeader_()
    {
    
    	$exportFields = $this->getExportFields();
    	
    	// Ако к-та и сумите са еднакви, не показваме количествата
    	if($this->innerState->hasSameAmounts === TRUE){
    		unset($exportFields['debitQuantity']);
    		unset($exportFields['creditQuantity']);
    		unset($exportFields['blQuantity']);
    	}
    	
    	if($this->innerForm->side){
    		if($this->innerForm->side == 'debit'){
    			unset($exportFields['creditQuantity'], $exportFields['creditAmount'], $exportFields['blQuantity'], $exportFields['blAmount']);
    		}elseif($this->innerForm->side == 'credit'){
    			unset($exportFields['debitQuantity'], $exportFields['debitAmount'], $exportFields['blQuantity'], $exportFields['blAmount']);
    		}
    	}
    	
    
    	foreach ($exportFields as $caption) {
    		$header .= "," . $caption;
    	}
    		
    	return (object) array('header' => $header);
    }
    
    
    /**
     * Ще направим row-овете в CSV формат
     *
     * @return string $rCsv
     */
    protected function generateCsvRows_($rec)
    {
    
    	$exportFields = $this->getExportFields();
    	$rec = frame_CsvLib::prepareCsvRows($rec);
    
    	$rCsv = '';
    
    	foreach ($rec as $field => $value) {
    		$rCsv = '';
    
    		foreach ($exportFields as $field => $caption) {
    				
    			if ($rec->{$field}) {
    
    				$value = $rec->{$field};
    				$value = html2text_Converter::toRichText($value);
    				// escape
    				if (preg_match('/\\r|\\n|,|"/', $value)) {
    					$value = '"' . str_replace('"', '""', $value) . '"';
    				}
    				$rCsv .= "," . $value;
    
    			} else {
    				$rCsv .= "," . '';
    			}
    		}
    	}
    
    	return $rCsv;
    }
    
    
    /**
     * Връща дефолт заглавието на репорта
     */
    public function getReportTitle()
    {
    	$baseSysId = acc_Accounts::fetchField($this->innerForm->baseAccountId, 'systemId');
    	$corrSysId = acc_Accounts::fetchField($this->innerForm->corespondentAccountId, 'systemId');
    	$title = tr("|Кореспонденция на сметки|* {$baseSysId} / {$corrSysId}");
    	
    	return $title;
    }
}