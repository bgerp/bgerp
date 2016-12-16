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
    public function addEmbeddedFields(core_FieldSet &$form)
    {
    	// Добавяме полетата за филтър
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    	$form->FLD('baseAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Основна,mandatory,silent,removeAndRefreshForm=list1|list2|list3|list4|list5|list6|feat1|feat2|feat3|feat4|feat5|feat6');
    	$form->FLD('corespondentAccountId', 'acc_type_Account(allowEmpty)', 'caption=Сметки->Кореспондент,mandatory,silent,removeAndRefreshForm=list1|list2|list3|list4|list5|list6|feat1|feat2|feat3|feat4|feat5|feat6');
    	$form->FLD('side', 'enum(all=Всички,debit=Дебит,credit=Кредит)', 'caption=Обороти,removeAndRefreshForm=orderField|orderBy,silent');
    	
    	$form->FLD('orderBy', 'enum(DESC=Низходящо,ASC=Възходящо)', 'caption=Сортиране->Вид,silent,removeAndRefreshForm=orderField,formOrder=100');
    	$form->FLD('orderField', 'enum(debitQuantity=Дебит к-во,debitAmount=Дебит сума,creditQuantity=Кредит к-во,creditAmount=Кредит сума,blQuantity=Остатък к-во,blAmount=Остатък сума)', 'caption=Сортиране->Поле,formOrder=101');
    	
    	$form->FLD('compare', 'enum(no=Няма,months=Предходен период,year=Миналогодишен период)', 'caption=Съпоставка,silent');
    	
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
    	$form->setDefault('compare', 'no');
    	
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
    				$form->FLD("feat{$i}", 'varchar', "caption=|*{$caption}->|Свойства|*,placeholder=Без показване,class=w100");
    				$form->FLD("list{$i}", 'int', "input=hidden");
    				$form->setDefault("list{$i}", $listId);
    				
    				// За всяка номенклатура даваме избор да и се изберат свойства
    				$items = cls::get('acc_Items')->makeArray4Select('title', "#lists LIKE '%|{$listId}|%'", 'id');
    				$features = acc_Features::getFeatureOptions(array_keys(array($items)));
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
    			$form->setError('to, from', 'Началната дата трябва да е по-малка от крайната');
    		}
    		
    		if($form->rec->orderBy == ''){
    			unset($form->rec->orderBy);
    		}
    	}
    }
        
    
    /**
     * Филтрира заявката
     */
    protected function prepareFilterQuery(&$query, $form)
    {
    	acc_JournalDetails::filterQuery($query, $form->from, $form->to);
    	$query->where("#debitAccId = {$form->baseAccountId} AND #creditAccId = {$form->corespondentAccountId}");
    	$query->orWhere("#debitAccId = {$form->corespondentAccountId} AND #creditAccId = {$form->baseAccountId}");
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     */
    public function prepareInnerState()
    {
    	core_App::setTimeLimit(300);
    	$data = new stdClass();
    	
    	$data->summary = (object)array('debitQuantity' => 0, 'debitAmount' => 0, 'creditQuantity' => 0, 'creditAmount' => 0, 'blQuantity' => 0, 'blAmount' => 0);
    	$data->summaryNew = (object)array('debitQuantity' => 0, 'debitAmount' => 0, 'creditQuantity' => 0, 'creditAmount' => 0, 'blQuantity' => 0, 'blAmount' => 0);
    	$data->summaryAll = (object)array('debitQuantity' => 0, 'debitAmount' => 0, 'creditQuantity' => 0, 'creditAmount' => 0, 'blQuantity' => 0, 'blAmount' => 0,
    	                                  'debitQuantityNew' => 0, 'debitAmountNew' => 0, 'creditQuantityNew' => 0, 'creditAmountNew' => 0, 'blQuantityNew' => 0, 'blAmountNew' => 0);
    	$data->hasSameAmounts = TRUE;
    	$data->rows = $data->recs = array();
    	$data->recsNew = array();
    	$data->recsAll = array();
    	$form = $this->innerForm;

    	$date = acc_Periods::comparePeriod($form->from, $form->to, $form->compare); 
    	$data->toOld = $date->to;
    	$data->fromOld = $date->from;
    	$data->contragent = $form->ent1Id;
    	$data->compare = $form->compare;

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

    	if (!is_null($data->fromOld) && !is_null($data->toOld)) {
    	    $jQuery = acc_JournalDetails::getQuery();
    	    $jQueryNew = clone $jQuery;
    	    $newForm = clone $form;
    	
    	    $newForm->from = $data->fromOld;
    	    $newForm->to = $data->toOld;
    	     
    	    // Извличаме записите от журнала за периода, където участват основната и кореспондиращата сметка
    	    $this->prepareFilterQuery($jQueryNew, $newForm);
    	
    	    $data->recsNew = $this->prepareData($this,$data, $jQueryNew, $newForm, $data->recsNew, 'New');
    	} 
    	    
    	$jQuery = acc_JournalDetails::getQuery();
        
        // Извличаме записите от журнала за периода, където участват основната и кореспондиращата сметка
        $this->prepareFilterQuery($jQuery, $form);
        	
        $data->recs = $this->prepareData($this,$data, $jQuery, $form, $data->recs, '');
    
		// Обработваме обобщената информация
    	$this->prepareSummary($data);

    	if (count($data->recsNew) || count($data->recs)) {

    	    foreach ($data->recs as $id => $r) {

    	       if(!array_key_exists($id, $data->recsAll)){
    	           $data->recsAll[$id] =

    	                (object) array (    	                
        	                'item1' => $r->item1,
        	                'item2' => $r->item2,
    	                    'item3' => $r->item3,
    	                    'item4' => $r->item4,
    	                    'item5' => $r->item5,
    	                    'valior' => $r->valior,
    	                    'debitQuantity' => $r->debitQuantity, 
    	                    'debitAmount' => $r->debitAmount,
    	                    'creditQuantity' => $r->creditQuantity,
    	                    'creditAmount' => $r->creditAmount,
        	                'blQuantity' => $r->blQuantity,
        	                'blAmount' => $r->blAmount,
        	                'delta' => $r->delta,);
    	       } 
    	    }
    
    	    foreach ($data->recsNew as $idNew => $rNew) {   
                if(!array_key_exists($idNew, $data->recsAll)){
    	                 
    	                $data->recsAll[$idNew] =
    	                (object) array (  
    	                    'item1' => $rNew->item1,
    	                    'item2' => $rNew->item2,
    	                    'item3' => $rNew->item3,
    	                    'item4' => $rNew->item4,
    	                    'item5' => $rNew->item5,
    	                    'valiorNew' => $rNew->valior,
    	                    'debitQuantityNew' => $rNew->debitQuantity,
    	                    'debitAmountNew' => $rNew->debitAmount,
    	                    'creditQuantityNew' => $rNew->creditQuantity,
    	                    'creditAmountNew' => $rNew->creditAmount,
    	                    'blQuantityNew' => $rNew->blQuantity,
    	                    'blAmountNew' => $rNew->blAmount,
    	                    'deltaNew' => $rNew->delta,
    	               );      
                } else {
    	            $obj = &$data->recsAll[$idNew];
    	            /*$obj->item1 = $rNew->item1;
    	            $obj->item2 = $rNew->item2;
    	            $obj->item3 = $rNew->item3;
    	            $obj->item4 = $rNew->item4;
    	            $obj->item5 = $rNew->item5;*/
    	            $obj->valiorNew = $rNew->valiors;
    	            $obj->debitQuantityNew = $rNew->debitQuantity;
    	            $obj->debitAmountNew = $rNew->debitAmount;
    	            $obj->creditQuantityNew = $rNew->creditQuantity;
    	            $obj->creditAmountNew = $rNew->creditAmount;
    	            $obj->blQuantityNew = $rNew->blQuantity;
    	            $obj->blAmountNew = $rNew->blAmount;
    	            $obj->deltaNew = $rNew->delta;
                }
    	    }
    	}

    	return $data;
    }
    
    
    /**
     * Помощна функция, която ще направи резултатния sdtClass
     * 
     * 
     * @param mvc $mvc
     * @param stdClass $data
     * @param query $query
     * @param stdClass $form
     * @param stdClass $res
     * @param string $type
     * 
     * @return stdClass
     */
    public static function prepareData($mvc,$data, $query, $form,$res, $sufix)
    {
        // За всеки запис добавяме го към намерените резултати
        $recs = $query->fetchAll();
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
            $mvc->addEntry($form->baseAccountId, $jRec, $data, $form->groupBy, $form, $features, $res);
        }

        // Ако има намерени записи
        if(count($res)){
        
            // За всеки запис
            foreach ($res as &$rec){
        
                // Изчисляваме окончателния остатък (дебит - кредит)
                $rec->blQuantity = $rec->debitQuantity - $rec->creditQuantity;
                $rec->blAmount = $rec->debitAmount - $rec->creditAmount;
        
                foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){

                    $data->{"summary{$sufix}"}->{$fld} += $rec->{$fld};
                    $data->summaryAll->{$fld.$sufix} += $rec->{$fld};
                }
        
                // Проверка дали сумата и к-то са еднакви
                if($rec->blQuantity != $rec->blAmount){
                    $data->hasSameAmounts = FALSE;
                }
            }
        
            foreach ($res as &$rec1){
                $fld = ($form->side == 'credit') ? 'creditAmount' : (($form->side == 'debit') ? 'debitAmount' : 'blAmount');
                if(!empty($data->{"summary{$sufix}"}->{$fld})){
                	$rec1->delta = round($rec1->{$fld} / $data->{"summary{$sufix}"}->{$fld}, 5);
                } else {
                	$rec1->delta = 0;
                }
                
                $rec1->delta = cls::get('type_Percent')->toVerbal($rec1->delta);
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

        return $res;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$data)
    {	
        $recs = array();

    	// Ако има намерени записи
    	if(count($data->recs)){

    		// Ако има избрано поле за сортиране, сортираме по него
    		arr::order($data->recs, $mvc->innerForm->orderField, $mvc->innerForm->orderBy);
    		
    		if(is_array($data->recsAll)) {
    		    arr::order($data->recsAll, $mvc->innerForm->orderField, $mvc->innerForm->orderBy);
    		}

    	    if ($mvc->innerForm->compare != 'no') {
    	        if (count($data->recsAll)) {
                    foreach ($data->recsAll as $recsAll) {
        	           $recs[] = $recsAll;
                    }
    	        }
    	    } else { 
    	        $recs = $data->recs;
    	    }

    	    
    	    if(count($recs)) { 
    	        if(!Mode::is('printing')) {
        	        // Подготвяме страницирането
        	        $pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
        	        $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
        	        $data->Pager = $pager;
        	        $data->Pager->itemsCount = count($recs);
    	        }
    	        // За всеки запис
    	        foreach ($recs as $id=>$rec){ 
    	            if (is_array($rec)) {

    		            foreach($rec as $is=>&$r) {
    		                $r->id = $id + 1;
    		                if(!Mode::is('printing')) {
        		                // Ако не е за текущата страница не го показваме
        		                if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
    		                }
    		             
    		                // Вербално представяне на записа
    		                $data->rows[] = $mvc->getVerbalRec($r, $data);
    		            }
    		        } else {
    		            $rec->id = $id + 1;

    		                if(!Mode::is('printing')) {
        		                // Ако не е за текущата страница не го показваме
        		                if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
    		                }
        			
        			    // Вербално представяне на записа
        			    $data->rows[] = $mvc->getVerbalRec($rec, $data);
    		        }
    	        }
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

        if($this->innerForm->compare == 'no') {
            $tpl->removeBlock('summeryNew');
    	}

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

    	if ($this->innerForm->compare !== 'no') {
    	    $tpl->placeObject($data->summaryAll);
    	} else {
    	    $tpl->placeObject($data->summary);
    	    $tpl->removeBlock('summeryAll');
    	}
    	
    	if($data->contragent) {
    	   $tpl->replace(acc_Items::getVerbal($data->contragent, 'titleLink'), 'contragent');
    	}
    	$tpl->replace(acc_Periods::getBaseCurrencyCode(), 'baseCurrencyCode');

    	$cntItem = array();
    	for ($i = 0; $i <= count($data->rows); $i++) {
	    	foreach (range(1, 6) as $l){
	    		if(!empty($data->rows[$i]->{"item{$l}"})){
	    			$cntItem[$l] = "item{$l}";
	    		}
	    	}
    	}

    	if(count($cntItem) <= 1 && count($data->recs) >= 2 ) {
    	
	    	// toolbar
	    	$btns = $this->generateBtns($data);
	
	    	if ($this->innerForm->compare != 'no') {
		        $tpl->replace($btns->buttonList, 'buttonList');
		        $tpl->replace($btns->buttonBar, 'buttonBar');
	    	} else {
	    		$tpl->replace($btns->buttonList, 'buttonList');
	    		$tpl->replace($btns->buttonPie, 'buttonPie');
	    	}
    	
    	}

        $curUrl = getCurrentUrl();
        
        $pageVar = core_Pager::getPageVar($this->EmbedderRec->className, $this->EmbedderRec->that);
        
        $pagePie = $pageVar . "_pie";
        $pageBar = $pageVar . "_bar";

        if ($curUrl[$pagePie] == $this->EmbedderRec->that) {

        	$chart = $this->getChartPie($data);
        	$tpl->append($chart, 'CONTENT');
        	
        } elseif($curUrl[$pageBar] == $this->EmbedderRec->that){ 
        	$chart = $this->getChartBar($data);
    
        	$tpl->append($chart, 'CONTENT');

        } else {
 
	    	$f = cls::get('core_FieldSet');
	    	$f->FLD('id', 'int', 'tdClass=accClass');
	    	$f->FLD('item1', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item2', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item3', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item4', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item5', 'varchar', 'tdClass=itemClass');
	    	$f->FLD('item6', 'varchar', 'tdClass=itemClass');
	    	foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount', 'delta', 
	    			       'blQuantityNew', 'blAmountNew', 'deltaNew') as $fld){
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
    	$Int = cls::get('type_Int');

        if (isset($rec->id)) {
    	   $row->id = $Int->toVerbal($rec->id);
        }
 
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
    	foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount','debitQuantityNew', 'debitAmountNew', 'creditQuantityNew', 'creditAmountNew', 'blQuantity', 'blAmount','blQuantityNew', 'blAmountNew') as $fld){
    		if(isset($rec->{$fld})){
    			$row->{$fld} = $Double->toVerbal($rec->{$fld});
    			if($rec->{$fld} < 0){
    				$row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
    			}
    		}
    	}
    	
    	if (isset($rec->delta) || isset($rec->deltaNew)) { 
    	    $row->delta = $rec->delta;
    	    $row->deltaNew = $rec->deltaNew;
    	}
    	
    	if (isset($rec->measure)) {
    	   $row->measure = $rec->measure;
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

    	if ($this->innerForm->compare != 'no') {
    	    foreach ((array)$data->summaryAll as $index => $fld){
    	        $f = $data->summaryAll->{$index};
    	        $data->summaryAll->{$index} = $Double->toVerbal($f);
    	        if($f < 0){
    	            $data->summaryAll->{$index} = "<span class='red'>{$data->summaryAll->{$index}}</span>";
    	        }
    	    }
    	    
    	} else {
    	    foreach ((array)$data->summary as $index => $fld){
    	        $f = $data->summary->{$index};
    	        $data->summary->{$index} = $Double->toVerbal($f);
    	        if($f < 0){
    	            $data->summary->{$index} = "<span class='red'>{$data->summary->{$index}}</span>";
    	        }
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
    private function addEntry($baseAccountId, $jRec, &$data, $groupBy, $form, $features, &$recs)
    {
    	
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
    	$form = $this->innerForm;
    	$newFields = array();

    	// Кои полета ще се показват
    	if($this->innerForm->compare != 'no'){
    		$fromVerbal = dt::mysql2verbal($form->from, 'd.m.Y');
    		$toVerbal = dt::mysql2verbal($form->to, 'd.m.Y');
    		
    		$prefix = (string) $fromVerbal . " - " . $toVerbal;

    		$fields = arr::make("debitQuantity={$prefix}->Дебит->К-во,debitAmount={$prefix}->Дебит->Сума,creditQuantity={$prefix}->Кредит->К-во,creditAmount={$prefix}->Кредит->Сума,blQuantity={$prefix}->Остатък->К-во,blAmount={$prefix}->Остатък->Сума,delta={$prefix}->Дял", TRUE);
    		
    	} else $fields = arr::make("debitQuantity=Дебит->К-во,debitAmount=Дебит->Сума,creditQuantity=Кредит->К-во,creditAmount=Кредит->Сума,blQuantity=Остатък->К-во,blAmount=Остатък->Сума,delta=Дял", TRUE);

    	foreach (range(1, 6) as $i){
    		if(!empty($form->{"feat{$i}"})){
    			if($form->{"feat{$i}"} == '*'){
    			    $newFields["id"] = "№";
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
    	
    	if($this->innerForm->compare != 'no'){
    		
    		if ($data->fromOld != NULL &&  $data->toOld != NULL) {

    			$fromOldVerbal = dt::mysql2verbal($data->fromOld, "d.m.Y");
    			$toOldVerbal = dt::mysql2verbal($data->toOld, "d.m.Y");
    		
    		
	    		
	    		$prefixOld = (string) $fromOldVerbal . " - " . $toOldVerbal;

	            $fieldsCompare = arr::make("debitQuantityNew={$prefixOld}->Дебит->К-во,
	    				                    debitAmountNew={$prefixOld}->Дебит->Сума,
	    				                    creditQuantityNew={$prefixOld}->Кредит->К-во,
	    				                    creditAmountNew={$prefixOld}->Кредит->Сума,
	    				                    blQuantityNew={$prefixOld}->Остатък->К-во,
	    				                    blAmountNew={$prefixOld}->Остатък->Сума,
	    				                    deltaNew={$prefixOld}->Дял", TRUE);
	
	    		
	    		$fields = $fields + $fieldsCompare;
	    		
	    		if($this->innerForm->side){ 
	    			if($this->innerForm->side == 'debit'){
	    				unset($fields['creditQuantityNew'], $fields['creditAmountNew'], $fields['blQuantityNew'], $fields['blAmountNew']);
	    			} elseif($this->innerForm->side == 'credit'){
	    				unset($fields['debitQuantityNew'], $fields['debitAmountNew'], $fields['blQuantityNew'], $fields['blAmountNew']);
	    			}
	    		}
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
    				unset($row->{$fld});
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
            redirect(array($this), FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
        }
        
        $exportFields = $this->innerState->listFields;
        
        $fields = $this->getFields();
        
        arr::order($this->innerState->recs, $this->innerForm->orderField, $this->innerForm->orderBy);
        
        $rows = $this->prepareEmbeddedData()->rows; 

        if (count($this->innerState->recs)) {
            foreach($this->innerState->recs as $id => $rec) {
        
                $dataRecs[] = $this->getVerbalRec($rec, $data);
                foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount', 'plus', 'minus','quantity','sum','аmountSelf') as $fld){
                
                    if(!is_null($rec->{$fld})){
                        $dataRecs[$id]->{$fld} = $rec->{$fld};
                    }
                }
                
                if(!is_null($rec->delta)){
                    $dataRecs[$id]->delta = str_replace("&nbsp;", '', $rec->delta);
                }
                
                if(!is_null($rec->measure)){
                    $dataRecs[$id]->measure = $rows[$id]->measure;
                }
                
                if(!is_null($rec->valior)){
                    $dataRecs[$id]->valior = $rec->valior;
                }
                
                foreach (array('item1', 'item2', 'item3', 'item4', 'item5', 'item6') as $fld1){
                    if(!is_null($rec->{$fld1})){ 
                        $dataRecs[$id]->{$fld1} = str_replace("&nbsp;", '', $dataRecs[$id]->{$fld1});
                        $dataRecs[$id]->{$fld1} = trim(html_entity_decode(strip_tags($dataRecs[$id]->{$fld1})));
                    } 
                }
            }
    
            foreach($exportFields as $caption => $name) {
                if($caption == 'creditAmount') {
                    unset($exportFields[$caption]);
                    $exportFields['sum'] = 'Сума';
                }
                
                if($caption == 'blAmountCompare') {
                    unset($exportFields[$caption]);
                }
       
            }
            
            if($this->innerState->summary &&  ($this->getReportLayout()->isPlaceholderExists('ROW_AFTER') || 
                                               $this->getReportLayout()->isPlaceholderExists('ROW_BEFORE'))) { 
                $afterRow = 'ОБЩО';
    
                $rec = $this->prepareEmbeddedData($this->innerState->recs)->summary;
                 
                foreach ($rec as $f => $value) {
                    $rCsv = '';
                    $rec->sum = $rec->creditAmount;
                    foreach ($exportFields as $field => $caption) {
                        if ($rec->{$field}) {
                            if ($field == 'delta') {
                                $rCsv .= '' . ",";
                            } else {
                                $value = $rec->{$field};
                                $rCsv .= $value. ",";
                            }
                        }else {
                            $rCsv .= '' . ",";
                        }
                    }
                }
                
                $csv = csv_Lib::createCsv($dataRecs, $fields, $exportFields);
                $csv .= "\n".$afterRow.$rCsv;
                
            } else {
                $csv = csv_Lib::createCsv($dataRecs, $fields, $exportFields);
            }
        	
        	return $csv;
        } 
    }

    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getFields_()
    {
        // Кои полета ще се показват
        $f = new core_FieldSet;
        $f->FLD('id', 'int');
        $f->FLD('debitQuantity', 'double');
        $f->FLD('debitAmount', 'double');
        $f->FLD('creditQuantity', 'double');
        $f->FLD('creditAmount', 'double');
        $f->FLD('blQuantity', 'double');
        $f->FLD('blAmount', 'double');
        $f->FLD('blQuantityNew', 'double');
        $f->FLD('blAmountNew', 'double');
        $f->FLD('quantity', 'double');
        $f->FLD('sum', 'double');
        $f->FLD('valior', 'date');
        $f->FLD('delta', 'varchar');
        $f->FLD('deltaNew', 'varchar');
        $f->FLD('measure', 'varchar');

        return $f;
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

        $curUrl = getCurrentUrl();
	
        $pageVar = core_Pager::getPageVar($this->EmbedderRec->className, $this->EmbedderRec->that);
        
        $pagePie = $pageVar . "_pie";
        $pageBar = $pageVar . "_bar";
       
        if ($curUrl["{$pageVar}_pie"] || $curUrl["{$pageVar}_bar"] ) { 
        	unset ($curUrl["{$pageVar}_pie"]);
        	unset ($curUrl["{$pageVar}_bar"]);
        }
        
        $realUrl = $curUrl;
   
    	// правим бутони за toolbar
    	$btnList = ht::createBtn('Таблица', $realUrl, NULL, NULL,
    			'ef_icon = img/16/table.png');
    	
    	$curUrl[$pagePie] = $this->EmbedderRec->that;
    	$urlPie = $curUrl;
    	
    	$btnPie = ht::createBtn('Графика', $urlPie, NULL, NULL,
    			'ef_icon = img/16/chart_pie.png');
    	
    	$realUrl[$pageBar] = $this->EmbedderRec->that;
    	$urlBar = $realUrl;
    	
    	$btnBar = ht::createBtn('Сравнение', $urlBar, NULL, NULL,
    			'ef_icon = img/16/chart_bar.png');
    	
    	$btns = array();
    	
    	$btns = (object) array('buttonList' => $btnList, 'buttonPie' => $btnPie, 'buttonBar' => $btnBar);
    	
    	return $btns;
    }
    
    
    /**
     * Изчертаване на графиката
     * 
     * @param stdClass $data
     * @return core_ET
     */
    protected function generateChartData ($data)
    {
    	//arr::order($data->recs, $this->innerForm->orderField, $this->innerForm->orderBy);
    
        $arr = array();
    	$dArr = array();
    	$value1 = array();
    	$value2 = array();
    	$labels = array();

    	if ($this->innerForm->compare != 'no') { 
    	    foreach ($data->recsAll as $id => $rec) {
    	        $value = abs($rec->{$this->innerForm->orderField});
    	        $valueNew = abs($rec->{$this->innerForm->orderField."New"});
    	        
    	        if ($rec->valior) { 
    	            $m = date('m', strtotime($rec->valior)); 
    	            $verbalM = dt::getMonth($m, 'M');
    	        } else {
    	            $m = date('m', strtotime($rec->valiorNew));
    	            $verbalM = dt::getMonth($m, 'M');
    	        }
    	        if (!in_array($verbalM, $labels)) {
    	           $labels[] = $verbalM;
    	        }
    	        
    	        $value1[] = $value;
    	        $value2[] = $valueNew;
    	    }
    	} else {
        	foreach ($data->recs as $id => $rec) { 

        	    $value = abs($rec->{$this->innerForm->orderField});

        		if ($rec->item1 || $rec->item2 || $rec->item3 || $rec->item4 || $rec->item5 || $rec->item6) { 
    	    		// правим масив с всички пера и стойност 
    	    		// сумирано полето което е избрали във формата
    	    		if(!array_key_exists($id, $dArr)){ 
    	    		
    	    			$dArr[$id] =
    	    			(object) array ('item1' => $rec->item1,
    	    					'item2' => $rec->item2,
    	    					'item3' => $rec->item3,
    	    					'item4' => $rec->item4,
    	    					'item5' => $rec->item5,
    	    					'item6' => $rec->item6,
    	    					'value' => $value
    	    		
    	    			);
    	    		// в противен случай го ъпдейтваме
    	    		} else {
    	    			 
    	    			$obj = &$dArr[$id];
    	    
    	    			$obj->item1 = $rec->item1;
    	    			$obj->item2 = $rec->item2;
    	    			$obj->item3 = $rec->item3;
    	    			$obj->item4 = $rec->item4;
    	    			$obj->item5 = $rec->item5;
    	    			$obj->item6 = $rec->item6;
    	    			$obj->value = $value;
    	    		}
    	    	} elseif (isset($rec->valior)) {
    	    		if(!array_key_exists($id, $dArr)){
    	    	
    		    		$dArr[$id] =
    		    		(object) array ('valior' => $rec->valior,
    		    				'value' => $rec->sum,
    		    				'valueNew' => $rec->sumNew
    		    				 
    		    		);
    	    		} else {
    	    			$obj = &$dArr[$id];
    	    			 
    	    			$obj->valior = $rec->valior;
    
    	    			$obj->value = $rec->sum;
    	    			$obj->valueNew = $rec->sumNew;
    	    		}
    	    	}
        	}
    	}

    	
		foreach ($dArr as $id=>$rec){
			
			if (isset($rec->valior)) { 
				$m = date('m', strtotime($rec->valior));
				$y = date('Y', strtotime($rec->valior));

				$labels[] = dt::getMonth($m, 'F', 'bg');
				$value1[] = $rec->value;
				$value2[] = $rec->valueNew;
			}
		}
		
		usort($dArr, function($a, $b) {
		     
		  return ($a->value > $b->value) ? -1 : 1;
		});

		$arr = $this->preparePie($dArr, 12);

		$title = '';
		if (is_array($arr)) {
    		foreach ($arr as $id => $recSort) {
    		    $title = $recSort->title;
    		    $info["{$title}"] = $recSort->value;
    		}
		}

    	$pie = array (
    				'legendTitle' => $this->getReportTitle(),
    				'suffix' => "лв.",
    				'info' => $info,
    	);

    	$yFrom = date('Y', strtotime($this->innerForm->from));
    	$yTo = date('Y', strtotime($this->innerForm->to));
    	
    	if($yFrom ==  $yTo) {
    		$year = $yFrom;
    		$yearPrev = $yFrom -1;
    	} else {
    		$year = $yFrom . "-" . $yTo;
    		$yPrevFrom = $yFrom - 1;
    		$yPrevTo= $yTo - 1;
    		$yearPrev = $yPrevFrom . "-" . $yPrevTo;
    	}

    	$bar = array(
    			'legendTitle' => $this->getReportTitle(),
    			'labels' => $labels,
    			'values' => array(
    					$yearPrev => $value2,
    					$year => $value1
    			)
        );

    	$chartData = array();
    	$chartData[] = (object) array('type' => 'pie', 'data' => $pie);
    	$chartData[] = (object) array('type' => 'bar', 'data' => $bar);
    
    	return $chartData;
    }
    
    
    protected function getChartPie ($data)
    {
    	$ch = $this->generateChartData($data);

    	$coreConf = core_Packs::getConfig('doc');
    	$chartAdapter = $coreConf->DOC_CHART_ADAPTER;
    	$chartHtml = cls::get($chartAdapter);

    	$chart =  $chartHtml::prepare($ch[0]->data,$ch[0]->type);

    	return $chart;
    }
    
    
    protected function getChartBar ($data)
    {
    	$ch = $this->generateChartData($data);

    	$coreConf = core_Packs::getConfig('doc');
    	$chartAdapter = $coreConf->DOC_CHART_ADAPTER;
    	$chartHtml = cls::get($chartAdapter);

    	$chart =  $chartHtml::prepare($ch[1]->data,$ch[1]->type);
    
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
    	
        $newArr = array();

    	foreach ($data as $key => $rec) {
    		// Вземаме всички пера като наредени н-орки
    		$title = '';
    		foreach (range(1, 6) as $i){
	    		if(!empty($rec->{"item{$i}"})){

	    			$title .= $rec->{"item{$i}"} . "|";
	    		}
	    	}

	    	$newArr[$key] =
	    			(object) array ('title' => substr($title, 0,strlen($title)-1),
	    							'value' => $rec->value
	    		
	    					);
    	}

    	// броя на елементите в получения масив
    	$cntData = count($data);
    
    	// ако, числото което сме определили за новия масив
    	// е по-малко от общия брой елементи
    	// на подадения масив
    	if ($cntData <= $n) {
   
    		// връщаме направо масива
    	    foreach($newArr as $id => $rec) {

    	        // Вербалното представяне на перата
    	        $t = explode("|", $rec->title);
    	        $titleV = '';
    	        for ($i=0; $i <= count($t) -1; $i++) {
    	            $titleV .= acc_Items::getVerbal($t[$i], 'title'). "|";
    	        }
    	        $titleV = substr($titleV, 0,strlen($titleV)-1);
    	    
    	        $res[] = (object) array ('key' => $k, 'title' => $titleV, 'value' => $rec->value);
    	    }
    
    		//в противен случай
    	} else {
    		// взимаме първите n елемента от сортирания масив
    		
    		for($k = 0; $k <= $n -1; $k++) {

    			// Вербалното представяне на перата
    			$t = explode("|", $newArr[$k]->title);
    			$titleV = '';
    			for ($i=0; $i <= count($t) -1; $i++) {
 
    				$titleV .= acc_Items::getVerbal($t[$i], 'title'). "|";
    			}
    			$titleV = substr($titleV, 0,strlen($titleV)-1);

    			$res[] = (object) array ('key' => $k, 'title' => $titleV, 'value' => $newArr[$k]->value);
    		}

    		// останалите елементи ги събираме
    		for ($i = $n; $i <= $cntData; $i++){

    			$sum += $newArr[$i]->value;
    		}
    
    		// ако имаме изрично зададено име за обобщения елемент
    		if ($otherName) {
    			// използваме него и го добавяме към получения нов масив с
    			// n еленета и сумата на останалите елементи
    			$res[] = (object) array ('key' => $n+1, 'title' => $otherName, 'value' => $sum);
    			// ако няма, използваме default
    		} else {
    			$res[] = (object) array ('key' => $n+1,'title' => "Други", 'value' => $sum);
    		}
    	}

    	return $res;
    }
}
