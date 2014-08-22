<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceReportImpl
{
    
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo, acc';
    
	
	/**
	 * Заглавие
	 */
    public $title = 'Подробен баланс';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function prepareReportForm($form)
    {
    	$form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,mandatory,silent', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
    	$form->FLD('from', 'datetime', 'caption=От,mandatory');
    	$form->FLD('to', 'datetime', 'caption=До,mandatory');
    	
    	if($form instanceof core_Form){
    		$form->input();
    	}
    	
    	// Ако е избрана сметка
    	if($form->rec->accountId){
    		$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
    		 
    		// Показваме номенкалтурите на сметката като предложения за селектиране
    		$options = array();
    		if(count($accInfo->groups)){
    			foreach ($accInfo->groups as $i => $gr){
    				$options["ent{$i}Id"] .= $gr->rec->name;
    			}
    		}
    		 
    		// Ако има номенклатури добавя ме ги към формата в type_Set поле и ги избираме всичките по дефолт
    		if(count($options)){
    			$setOptions = arr::fromArray($options);
    			$form->FLD('groupBy', "set($setOptions)", 'columns=1,input,caption=Групиране по');
    			$form->setDefault('groupBy', implode(',', array_flip($options)));
    		}
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function checkReportForm($form)
    {
    	if($form->isSubmitted()){
    		if($form->rec->to < $form->rec->from){
    			$form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
    		}
    	}
    }
    
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    private function prepareListFields(&$data)
    {
    	$data->accInfo = acc_Accounts::getAccountInfo($data->rec->accountId);
    	$bShowQuantities = FALSE;
    	$bShowQuantities = ($data->accInfo->isDimensional === TRUE) ? TRUE : FALSE;
    	$data->groupBy = arr::make($data->rec->groupBy, TRUE);
    	
    	$data->listFields = array();
    	if(count($data->accInfo->groups)){
    		$data->listFields = array('id' => '№');
    		foreach ($data->accInfo->groups as $i => $gr){
    			if(!count($data->groupBy) || isset($data->groupBy["ent{$i}Id"])){
    				$data->listFields["ent{$i}Id"] = acc_Lists::getVerbal($gr->rec, 'name');
    			}
    		}
    	}
    	
    	if ($bShowQuantities) {
				$data->listFields += array(
				'baseQuantity' => 'Начално салдо->ДК->К-во',
				'baseAmount' => 'Начално салдо->ДК->Сума',
				'debitQuantity' => 'Обороти->Дебит->К-во',
				'debitAmount' => 'Обороти->Дебит->Сума',
				'creditQuantity' => 'Обороти->Кредит->К-во',
				'creditAmount' => 'Обороти->Кредит->Сума',
				'blQuantity' => 'Крайно салдо->ДК->К-во',
				'blAmount' => 'Крайно салдо->ДК->Сума',);
		} else {
			$data->listFields += array(
			'baseAmount' => 'Салдо->Начално',
			'debitAmount' => 'Обороти->Дебит',
			'creditAmount' => 'Обороти->Кредит',
			'blAmount' => 'Салдо->Крайно',
			);
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function prepareReportData($filter)
    {
    	$data = new stdClass();
    	$data->rec = $filter;
    	
    	$this->prepareListFields($data);
    	
    	$accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
    	$Balance = new acc_ActiveShortBalance(array('from' => $data->rec->from, 'to' => $data->rec->to));
    	$data->recs = $Balance->getBalance($accSysId);
    	
    	$this->filterRecsByItems($data, $filter);
    	
    	// Подготвяме страницирането
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listItemsPerPage));
    	$Pager->itemsCount = count($data->recs);
    	$Pager->calc();
    	$data->pager = $Pager;
    	
    	$start = $data->pager->rangeStart;
    	$end = $data->pager->rangeEnd - 1;
    	
    	$data->hideQuantities = FALSE;
    	if(count($data->recs)){
    		$count = 0;
    		foreach ($data->recs as $id => $rec){
    			
    			// Показваме само тези редове, които са в диапазона на страницата
    			if($count >= $start && $count <= $end){
    				$rec->id = $count + 1;
    				$row = $this->recToVerbal($rec);
    				if($row->blAmount != $row->blQuantity){
    					$data->hideQuantities = TRUE;
    				}
    				$data->rows[$id] = $row;
    			}
    			
    			$count++;
    		}
    	}
    	
    	return $data;
    }
    
    
    /**
     * Оставяме в записите само тези, които трябва да показваме
     */
    private function filterRecsByItems(&$data, $filter)
    {
    	if(!count($data->recs)) return;
    	
    	if(count($data->groupBy)){
    		$newRecs = array();
    		
    		foreach ($data->recs as $id => $rec){
    			$newIndex = '';
    			$newIndex .= ($data->groupBy['ent1Id']) ? $rec->ent1Id : '';
    			$newIndex .= ($data->groupBy['ent2Id']) ? "|" . $rec->ent2Id : '';
    			$newIndex .= ($data->groupBy['ent3Id']) ? "|" . $rec->ent3Id : '';
    			
    			if(!isset($newRecs[$newIndex])){
    				$newRecs[$newIndex] = $rec;
    			} else {
    				$r = &$newRecs[$newIndex];
    				foreach (array('baseQuantity', 'baseAmount', 'debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
    					if(!is_null($rec->$fld)){
    						$r->$fld += $rec->$fld;
    					}
    				}
    			}
    		}
    		
    		$data->recs = $newRecs;
    	}
    	
    	foreach ($data->recs as $id => $rec){
    		foreach (range(1, 3)as $i){
    			if(isset($rec->{"ent{$i}Id"})){
    				static::$cache[$rec->{"ent{$i}Id"}] = $rec->{"ent{$i}Id"};
    			}
    		}
    	}
    	
    	// Запомняме номерата на замесените пера
    	$iQuery = acc_Items::getQuery();
    	$iQuery->show("num");
    	$iQuery->in('id', static::$cache);
    	while($iRec = $iQuery->fetch()){
    		static::$cache[$iRec->id] = $iRec->num;
    	}
    	
    	// Филтрираме ги по номерата
    	usort($data->recs, array($this, "sortRecs"));
    }
    
    
    /**
     * Филтриране на записите по код
     * Подрежда кодовете или свойствата във възходящ ред.
     * Ако първата аналитичност са еднакви, сравнява по кодовете на втората ако и те по тези на третата
     */
    private function sortRecs($a, $b)
    {
    	$cache = static::$cache;
    	 
    	foreach (range(1, 3) as $i){
    		${"cmpA{$i}"} = $cache[$a->{"ent{$i}Id"}];
    		${"cmpB{$i}"} = $cache[$b->{"ent{$i}Id"}];
    
    		// Ако са равни продължаваме
    		if(${"cmpA{$i}"} == ${"cmpB{$i}"}) continue;
    
    		${"cmpA{$i}"} = mb_strtolower(${"cmpA{$i}"});
    		${"cmpB{$i}"} = mb_strtolower(${"cmpB{$i}"});
    
    		return (strnatcasecmp(${"cmpA{$i}"}, ${"cmpB{$i}"}) < 0) ? -1 : 1;
    	}
    	 
    	// Ако всички са еднакви оставяме ги така
    	return 0;
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    private function recToverbal($rec)
    {
    	$Varchar = cls::get('type_Varchar');
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	$Int = cls::get('type_Int');
    	
    	$row = new stdClass();
    	$row->id = $Int->toVerbal($rec->id);
    	
    	foreach (array('baseAmount', 'debitAmount', 'creditAmount', 'blAmount', 'baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity') as $fld){
    		$row->$fld = $Double->toVerbal($rec->$fld);
    		$row->$fld = (($rec->$fld) < 0) ? "<span style='color:red'>{$row->$fld}</span>" : $row->$fld;
    	}
    	
    	foreach (array(1 => 'ent1Id', 2 =>  'ent2Id', 3 => 'ent3Id') as $id => $fld){
    		if(isset($rec->$fld)){
    			$row->$fld .= acc_Items::getVerbal($rec->$fld, 'titleLink');
    		}
    	}
    	
    	$row->ROW_ATTR['class'] = ($rec->id % 2 == 0) ? 'zebra1' :'zebra0';
    	
    	return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function renderReportData($filter, $data)
    {
    	if(empty($data)) return;
    	
    	$tpl = getTplFromFile('acc/tpl/ReportDetailedBalance.shtml');
    	$filter->accountId = acc_Balances::getAccountLink($data->rec->accountId, NULL, TRUE, TRUE);
    	
    	
    	// Показваме за кои пера има филтриране
    	if(count($data->groupBy)){
    		foreach ($data->groupBy as $fld){
    			$filter->groupBy .= $data->listFields[$fld] . ", ";
    		}
    		$filter->groupBy = trim($filter->groupBy, ', ');
    	}
    	
    	foreach (range(1, 3) as $i){
    		if(isset($data->rec->{"ent{$i}Id"})){
    			$filter->{"ent{$i}Id"} = "<b>" . acc_Lists::getVerbal($data->accInfo->groups[$i]->rec, 'name') . "</b>: ";
    			$filter->{"ent{$i}Id"} .= acc_Items::fetchField($data->rec->{"ent{$i}Id"}, 'titleLink');
    		}
    	}
    	
    	$tpl->placeObject($filter);
    	
    	$tableMvc = new core_Mvc;
    	$tableMvc->FLD('baseQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('baseAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blAmount', 'int', 'tdClass=accCell');
    	
    	if(!$data->hideQuantities){
    		unset($data->listFields['baseQuantity'], $data->listFields['debitQuantity'], $data->listFields['creditQuantity'], $data->listFields['blQuantity']);
    	}
    	
    	$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    	
    	$tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');
    	 
    	if($data->pager){
    		$tpl->append($data->pager->getHtml(), 'PAGER_BOTTOM');
    		$tpl->append($data->pager->getHtml(), 'PAGER_TOP');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    function canSelectSource($userId = NULL)
    {
    	 return core_Users::haveRole($this->canSelectSource, $userId);
    }
}