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
    public $title = 'Счетоводство->Кореспонденция по сметка';
    
    
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
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_Form &$form)
    {
    	// Добавяме полетата за филтър
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    	$form->FLD('baseAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Основна,mandatory');
    	$form->FLD('corespondentAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Кореспондент,mandatory');
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
    	}
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
    	
    	// Извличаме записите от журнала за периода, където участват основната и кореспондиращата сметка
    	$jQuery = acc_JournalDetails::getQuery();
    	acc_JournalDetails::filterQuery($jQuery, $form->from, $form->to);
    	$jQuery->where("#debitAccId = {$form->baseAccountId} AND #creditAccId = {$form->corespondentAccountId}");
    	$jQuery->orWhere("#debitAccId = {$form->corespondentAccountId} AND #creditAccId = {$form->baseAccountId}");
    	
    	// За всеки запис добавяме го към намерените резултати
    	while($jRec = $jQuery->fetch()){
    		$this->addEntry($form->baseAccountId, $jRec, $data->recs);
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
    	
		// Обработваме обобщената информация
    	$this->prepareSummary($data);
    	
    	return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$data)
    {
    	// Ако има намерени записи
    	if(count($data->recs)){
    		
    		// Подготвяме страницирането
    		$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
    		$data->Pager->itemsCount = count($data->recs);
    		
    		foreach ($data->recs as $rec1){
    			foreach (range(1, 3) as $i){
    				if(isset($rec1->{"item{$i}"})){
    					$this->cache[$rec1->{"item{$i}"}] = $rec1->{"item{$i}"};
    				}
    			}
    		}
    		
    		// Кешираме номерата на перата в отчета
    		if(count($this->cache)){
    			$iQuery = acc_Items::getQuery();
    			$iQuery->show("num");
    			$iQuery->in('id', $this->cache);
    			 
    			while($iRec = $iQuery->fetch()){
    				$this->cache[$iRec->id] = $iRec->num;
    			}
    		}
    		
    		// Подготвяме поле за сортиране по номерата на перата
    		foreach ($data->recs as &$rec){
    			$rec->sortField = '';
    			foreach (range(1, 3) as $j){
    				if(isset($rec->{"item{$j}"})){
    					$rec->sortField .= $this->cache[$rec->{"item{$j}"}];
    				}
    			}
    			
    			$rec->sortField = strtolower(str::utf2ascii($rec->sortField));
    		}
    		
    		// Сортираме записите според полето за сравнение
    		usort($data->recs, array($this, "sortRecs"));
    		
    		// За всеки запис
    		foreach ($data->recs as &$rec){
    			
    			// Ако не е за текущата страница не го показваме
    			if(!$data->Pager->isOnPage()) continue;
    			
    			// Вербално представяне на записа
    			$data->rows[] = $this->getVerbalRec($rec);
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
    private function addEntry($baseAccountId, $jRec, &$recs)
    {
    	// Обхождаме дебитната и кредитната част
    	foreach (array('debit', 'credit') as $type){
    		
    		// Ако сметката на дебита/кредита не е основната сметка не натрупваме оборотите
    		if($jRec->{"{$type}AccId"} != $baseAccountId) continue;
    		
    		// Индекса за групиране са аналитичностите
    		$index = "{$jRec->{"{$type}Item1"}}|{$jRec->{"{$type}Item2"}}|{$jRec->{"{$type}Item3"}}";
    	
    		// Ако записите няма обект с такъв индекс, създаваме го
    		if(!array_key_exists($index, $recs)){
    			$recs[$index] = new stdClass();
    			foreach (range(1, 3) as $i){
    				if(!empty($jRec->{"{$type}Item{$i}"})){
    					$recs[$index]->{"item{$i}"} = $jRec->{"{$type}Item{$i}"};
    				}
    			}
    		}
    		 
    		// Сумираме дебитния или кредитния оборот
    		$quantityFld = "{$type}Quantity";
    		$amountFld = "{$type}Amount";
    		$recs[$index]->{$quantityFld} += $jRec->{"{$type}Quantity"};
    		$recs[$index]->{$amountFld} += $jRec->amount;
    	}
    }
    
    
    /**
     * Вербално представяне на групираните записи
     * 
     * @param stdClass $rec - групиран запис
     * @return stdClass $row - вербален запис
     */
    private function getVerbalRec($rec)
    {
    	$row = new stdClass();
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	
    	// Вербалното представяне на перата
    	foreach (range(1, 3) as $i){
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
     * Рендира вградения обект
     */
    public function renderEmbeddedData($data)
    {
    	// Взимаме шаблона
    	$tpl = getTplFromFile('acc/tpl/CorespondingReportLayout.shtml');
    	
    	$tpl->placeObject($data->summary);
    	$tpl->replace(acc_Periods::getBaseCurrencyCode(), 'baseCurrencyCode');
    	
    	// Кои полета ще се показват
    	$fields = arr::make("item1,item2,item3,debitQuantity=Дебит->К-во,debitAmount=Дебит->Сума,creditQuantity=Кредит->К-во,creditAmount=Кредит->Сума,blQuantity=Остатък->К-во,blAmount=Остатък->Сума", TRUE);
   		$accInfo = acc_Accounts::getAccountInfo($this->innerForm->baseAccountId);
   		
   		// Добавяме имената на номенклатурите
   		foreach (range(1, 3) as $i){
   			if(isset($accInfo->groups[$i])){
   				$fields["item{$i}"] = $accInfo->groups[$i]->rec->name;
   			} else {
   				unset($fields["item{$i}"]);	
   			}
   		}
    	
    	// Ако к-та и сумите са еднакви, не показваме количествата
   		if($data->hasSameAmounts === TRUE){
   			unset($fields['debitQuantity']);
   			unset($fields['creditQuantity']);
   			unset($fields['blQuantity']);
   		}
    	
   		$f = cls::get('core_FieldSet');
   		$f->FLD('item1', 'varchar', 'tdClass=itemClass');
   		$f->FLD('item2', 'varchar', 'tdClass=itemClass');
   		$f->FLD('item3', 'varchar', 'tdClass=itemClass');
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
    		
    	$tpl->append($form->renderStaticHtml(), 'FORM');
    	
    	// Връщаме шаблона
    	return $tpl;
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
}