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
class acc_reports_CorespondingImpl extends frame_BaseDriver
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'acc_CorespondingReportImpl';
	
	
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
    	$form->FLD('baseAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Основна,mandatory,silent,removeAndRefreshForm=list1|list2|list3|list4|list5|list6|feat1|feat2|feat3|feat4|feat5|feat6');
    	$form->FLD('corespondentAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Кореспондент,mandatory,silent,removeAndRefreshForm=list1|list2|list3|list4|list5|list6|feat1|feat2|feat3|feat4|feat5|feat6');
    	$form->FLD('side', 'enum(all=Всички,debit=Дебит,credit=Кредит)', 'caption=Обороти,removeAndRefreshForm=orderField|orderBy,silent');
    	
    	$form->FLD('orderBy', 'enum(DESC=Низходящо,ASC=Възходящо)', 'caption=Сортиране->Вид,silent,removeAndRefreshForm=orderField,formOrder=100');
    	$form->FLD('orderField', 'enum(debitQuantity=Дебит к-во,debitAmount=Дебит сума,creditQuantity=Кредит к-во,creditAmount=Кредит сума,blQuantity=Остатък к-во,blAmount=Остатък сума)', 'caption=Сортиране->Поле,formOrder=101');
    	
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
    	$form->setDefault('orderBy', 'DESC');
    	
    	// Ако има избрани сметки, показваме обединението на номенклатурите им
    	if(isset($form->rec->baseAccountId) && isset($form->rec->corespondentAccountId)){
    		$baseAccInfo = acc_Accounts::fetch($form->rec->baseAccountId);
    		$corespAccInfo = acc_Accounts::fetch($form->rec->corespondentAccountId);
    		$sets = array();
    		
    		foreach (range(1, 3) as $i){
    			if(isset($baseAccInfo->{"groupId{$i}"})){
    				$sets[$baseAccInfo->{"groupId{$i}"}] = acc_Lists::getVerbal($baseAccInfo->{"groupId{$i}"}, 'name');
    			}
    			 
    			if(isset($corespAccInfo->{"groupId{$i}"})){
    				$sets[$corespAccInfo->{"groupId{$i}"}] = acc_Lists::getVerbal($corespAccInfo->{"groupId{$i}"}, 'name');
    			}
    		}
    		
    		// Добавяме поле за групиране ако има по какво
    		if(count($sets)){
    			$i = 1;
    			foreach ($sets as $listId => $caption){
    				$form->FLD("feat{$i}", 'varchar', "caption=|*{$caption}->|Свойства|*");
    				$form->FLD("list{$i}", 'int', "input=hidden");
    				$form->setDefault("list{$i}", $listId);
    				
    				// За всяка номенклатура даваме избор да и се изберат свойства
    				$items = cls::get('acc_Items')->makeArray4Select('title', "#lists LIKE '%|{$listId}|%'", 'id');
    				$features = acc_Features::getFeatureOptions(array_keys($items));
    				$features = array('' => '') + $features + array('*' => $caption);
    				$form->setOptions("feat{$i}", $features);
    				$i++;
    			}
    		}
    	}
    	 
    	// Ако е избрано подреждаме
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
    	core_App::setTimeLimit(300);
    	$data = new stdClass();
    	$data->summary = (object)array('debitQuantity' => 0, 'debitAmount' => 0, 'creditQuantity' => 0, 'creditAmount' => 0, 'blQuantity' => 0, 'blAmount' => 0);
    	$data->hasSameAmounts = TRUE;
    	$data->rows = $data->recs = array();
    	$form = $this->innerForm;
    	
    	$data->groupBy = array();
    	foreach (range(1, 6) as $i){
    		if(!empty($form->{"feat{$i}"})){
    			$data->groupBy[$form->{"list{$i}"}] = $form->{"list{$i}"};
    		}
    	}
    	
    	$data->groupBy = array_values($data->groupBy);
    	array_unshift($data->groupBy, NULL);
    	unset($data->groupBy[0]);
    	
    	$this->prepareListFields($data);
    	
    	// Извличаме записите от журнала за периода, където участват основната и кореспондиращата сметка
    	$jQuery = acc_JournalDetails::getQuery();
    	acc_JournalDetails::filterQuery($jQuery, $form->from, $form->to);
    	$jQuery->where("#debitAccId = {$form->baseAccountId} AND #creditAccId = {$form->corespondentAccountId}");
    	$jQuery->orWhere("#debitAccId = {$form->corespondentAccountId} AND #creditAccId = {$form->baseAccountId}");
    	
    	// За всеки запис добавяме го към намерените резултати
    	$recs = $jQuery->fetchAll();
    	$allItems = array();
    	
    	if(is_array($recs)){
    		
    		// проверяваме имали избрано групиране по свойство което не е името на перото
    		$groupByFeatures = FALSE;
    		foreach (range(1, 3) as $i){
    			$groupByFeatures = $groupByFeatures || !empty($form->{"feat{$i}"});
    		}
    		
    		// Ако има
    		if($groupByFeatures === TRUE){
    			
    			// Намираме всички пера участващи в заявката
    			foreach ($recs as $rec1){
    				foreach(array('debit', 'credit') as $type){
    					foreach (range(1, 3) as $i){
    						if($item = $rec1->{"{$type}Item{$i}"}){
    							$allItems[$item] = $item;
    						}
    					}
    				}
    			}
    			
    			// Извличаме им свойствата
    			$features = acc_Features::getFeaturesByItems($allItems);
    		} else {
    			$features = array();
    		}
    	}
    	
    	foreach ($recs as $jRec){
    		$this->addEntry($form->baseAccountId, $jRec, $data, $form->groupBy, $form, $features);
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
    		
    		foreach ($data->recs as &$rec1){
    			$fld = ($form->side == 'credit') ? 'creditAmount' : (($form->side == 'debit') ? 'debitAmount' : 'blAmount');
    			@$rec1->delta = round($rec1->{$fld} / $data->summary->${fld}, 5);
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
    		
    		// Ако има избрано поле за сортиране, сортираме по него
    		arr::order($data->recs, $mvc->innerForm->orderField, $mvc->innerForm->orderBy);
    		
    		// За всеки запис
    		foreach ($data->recs as &$rec){
    			
    			// Ако не е за текущата страница не го показваме
    			if(!$data->Pager->isOnPage()) continue;
    			
    			// Вербално представяне на записа
    			$data->rows[] = $mvc->getVerbalRec($rec, $data);
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

    	// toolbar
    	$btns = $this->generateBtns($data);
    	
        $tpl->replace($btns->buttonList, 'buttonList');
        $tpl->replace($btns->buttonChart, 'buttonChart');
        
        $type = Request::get('Chart');
        $var = str::addHash("pie", 5, "{$this->EmbedderRec->that}");
        $tableVar = str::addHash("table", 5, "{$this->EmbedderRec->that}");


        if ((Request::get('var') == $var || Request::get('var') == $tableVar) && $data->recs) { //bp($type, $data->rec->containerId, $data);
        	$chart = $this->getChart($data);
        	$tpl->append($chart, 'CONTENT');
        } else {
    	
	    	$f = cls::get('core_FieldSet');
	    	$f->FLD('item1', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item2', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item3', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item4', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item5', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item6', 'varchar', 'tdClass=itemClass');
	    	foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount', 'delta') as $fld){
	    		$f->FLD($fld, 'int', 'tdClass=accCell');
	    	}
	    	 
	    	// Рендираме таблицата
	    	$table = cls::get('core_TableView', array('mvc' => $f));
	    	$tableHtml = $table->get($data->rows, $data->listFields);
	    	 
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
        }
    
    	$this->prependStaticForm($tpl, 'FORM');
    	 
    	$embedderTpl->append($tpl, 'data');
    }
    
    
    /**
     * Вербално представяне на групираните записи
     *
     * @param stdClass $rec - групиран запис
     * @return stdClass $row - вербален запис
     */
    protected function getVerbalRec($rec, $data)
    {
    	$row = new stdClass();
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	$Varchar = cls::get('type_Varchar');
    	
    	// Вербалното представяне на перата
    	foreach (range(1, 6) as $i){
    		if(!empty($rec->{"item{$i}"})){
    			if($this->innerForm->{"feat{$i}"} == '*'){
    				$row->{"item{$i}"} = acc_Items::getVerbal($rec->{"item{$i}"}, 'titleLink');
    			} else {
    				$row->{"item{$i}"} = $Varchar->toVerbal($rec->{"item{$i}"});
    				if($row->{"item{$i}"} == 'others'){
    					$row->{"item{$i}"} = tr("|*<i>|Други|*</i>");
    				}
    			}
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
    	
    	$row->delta = cls::get('type_Percent')->toVerbal($rec->delta);
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
    private function addEntry($baseAccountId, $jRec, &$data, $groupBy, $form, $features)
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
    	
    	$index = array();
    	foreach (range(1, 6) as $i){
    		if(!empty($form->{"feat{$i}"})){
    			foreach (array('debit', 'credit') as $type){
    				$groups = ${"{$type}Groups"};
    				foreach (range(1, 3) as $j){
    					if($groups->{"groupId{$j}"} == $form->{"list{$i}"}){
    						$key = $jRec->{"{$type}Item{$j}"};
    						
    						if($form->{"feat{$i}"} != '*'){
    							$featValue = $features[$key][$form->{"feat{$i}"}];
    							$key = isset($featValue) ? $featValue : 'others';
    						}
    						
    						$jRec->{"column{$i}"} = $key;
    						$index[$i] = $key;
    					}
    				}
    			}
    		}
    	}
    	
    	// Ако записите няма обект с такъв индекс, създаваме го
    	$index = implode('|', $index);
    	if(!array_key_exists($index, $recs)){
    		$recs[$index] = new stdClass();
    		
    		foreach (range(1, 6) as $k){
    			if(isset($jRec->{"column{$k}"})){
    				$recs[$index]->{"item{$k}"} = $jRec->{"column{$k}"};
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
    	// Кои полета ще се показват
    	$fields = arr::make("debitQuantity=Дебит->К-во,debitAmount=Дебит->Сума,creditQuantity=Кредит->К-во,creditAmount=Кредит->Сума,blQuantity=Остатък->К-во,blAmount=Остатък->Сума,delta=Дял", TRUE);
    	$newFields = array();
    	$form = $this->innerForm;
    	
    	foreach (range(1, 6) as $i){
    		if(!empty($form->{"feat{$i}"})){
    			if($form->{"feat{$i}"} == '*'){
    				$newFields["item{$i}"] = acc_Lists::getVerbal($form->{"list{$i}"}, 'name');
    			} else {
    				$newFields["item{$i}"] = $form->{"feat{$i}"};
    			}
    		}
    	}
    	
    	if(count($newFields)){
    		$fields = $newFields + $fields;
    	}
    	
    	if($this->innerForm->side){
    		if($this->innerForm->side == 'debit'){
    			unset($fields['creditQuantity'], $fields['creditAmount'], $fields['blQuantity'], $fields['blAmount']);
    		}elseif($this->innerForm->side == 'credit'){
    			unset($fields['debitQuantity'], $fields['debitAmount'], $fields['blQuantity'], $fields['blAmount']);
    		}
    	}
    	
    	$data->listFields = $fields;
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
   
    	arr::order($this->innerState->recs, $this->innerForm->orderField, $this->innerForm->orderBy);

    	if(count($this->innerState->recs)) { 
    		foreach ($this->innerState->recs as $id => $rec) {
    
    			$rCsv = $this->generateCsvRows($rec);
    
    			$csv .= $rCsv;
    			$csv .=  "\n";
    
    		}
    			
    		$csv = $header . "\n" . $csv;
    	}
    	
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

    
    /**
     * Генериране на бутоните за тулбара
     * 
     * @param stdClass $data
     * @return StdClass
     */
    public function generateBtns($data)
    {

    	$var = str::addHash("pie", 5, "{$this->EmbedderRec->that}");
    	$tableVar = str::addHash("table", 5, "{$this->EmbedderRec->that}");

        $curUrl = getCurrentUrl();

        if ($curUrl['varType']) {
        	$varDoc = $curUrl['varType'];
        }
    	// правим бутони за toolbar
    	$btnList = ht::createBtn('Таблица', array(
    			'doc_Containers',
    			'list',
    			'threadId' => Request::get('threadId', 'int'),
    			'docId' => Request::get('docId', 'varchar'),
    			'var' => $varDoc,
    			'varType' => $tableVar
    			 
    	), NULL, NULL,
    			'ef_icon = img/16/table.png');

    	
    	$btnChart = ht::createBtn('Графика', array(
    			'doc_Containers',
    			'list',
    			'Chart' => 'pie'. $data->rec->containerId,
    			'threadId' => Request::get('threadId', 'int'),
    			'docId' => Request::get('docId', 'varchar'),
    			'var' => $varDoc,
    			'varType' => $var,
    			 
    	), NULL, NULL,
    			'ef_icon = img/16/chart16.png');
    	
    	$btns = array();
    	
    	$btns = (object) array('buttonList' => $btnList, 'buttonChart' => $btnChart);

    	return $btns;
    }
    
    
    /**
     * Изчертаване на графиката
     * 
     * @param stdClass $data
     * @return core_ET
     */
    public function getChart ($data)
    {
    	foreach ($data->recs as $id => $rec) {

    		$balance += abs($rec->blAmount);
    	
    		$dArr[] = abs($rec->blAmount);
    	}

    	$arr = $this->preparePie($dArr, 12, 'Others');

    	foreach ($arr as $id => $recSort) {
    		$info[mb_substr($recSort->key,0,19)] = $recSort->value;
    	}
    	
    	$pie = array (
    				'legendTitle' => $this->getReportTitle(),
    				'suffix' => "лв.",
    				'info' => $info,
    	);
    	
    	$coreConf = core_Packs::getConfig('doc');
    	$chartAdapter = $coreConf->DOC_CHART_ADAPTER;
    	$chartHtml = cls::get($chartAdapter);
    	$chart =  $chartHtml::prepare($pie,'pie');

    	return $chart;
    }
    
    
    /**
     * По даден масив, правим подготовка за
     * графика тип "торта"
     *
     * @param array $data
     * @param int $n
     * @param string $otherName
     */
    public static function preparePie ($data, $n, $otherName = 'Други')
    {

    	foreach ($data as $key => $value) {
    		$newArr [] = (object) array ('key' => $key, 'value' => $value);
    	}
    
    	// броя на елементите в получения масив
    	$cntData = count($data);
    
    	// ако, числото което сме определили за новия масив
    	// е по-малко от общия брой елементи
    	// на подадения масив
    	if ($cntData <= $n) {
    
    		// връщаме направо масива
    		//return $data;
    		return $newArr;
    
    		//в противен случай
    	} else {
    		// взимаме първите n елемента от сортирания масив
    		for($k = 0; $k <= $n -1; $k++) {
    			$res[] = $newArr[$k];
    		}
    
    		// останалите елементи ги събираме
    		for ($i = $n; $i <= $cntData; $i++){
    			$sum += $newArr[$i]->value;
    		}
    
    		// ако имаме изрично зададено име за обобщения елемент
    		if ($otherName) {
    			// използваме него и го добавяме към получения нов масив с
    			// n еленета и сумата на останалите елементи
    			$res[] = (object) array ('key' => $otherName, 'value' => $sum);
    			// ако няма, използваме default
    		} else {
    			$res[] = (object) array ('key' => "Други", 'value' => $sum);
    		}
    	}
    
    	return $res;
    }
}
