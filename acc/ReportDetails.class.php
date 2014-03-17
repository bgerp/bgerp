<?php



/**
 * Клас показващ счетоводна информация за даден мениджър който е перо в счетоводството
 * За да работи трябва да се добави като детайл на съответния мениджър
 * В мениджъра е нужно да има следните класови променливи:
 * 
 * 		$balanceRefAccounts - систем ид-та на сч. сметки, от които ще се правят справки
 * 		$balanceRefGroupBy - инт на сч. перо по което ще се групират(този на мениджъра)
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ReportDetails extends core_Manager
{
	
	
	/**
	 * Кои мениджъри ще се зареждат
	 */
	public $loadList = 'ObjectLists=acc_Items';
	
	
	/**
	 * Кой има достъп до списъчния изглед
	 */
	public $canList = 'no_one';
	
	
	/**
	 * Кой може да пише
	 */
	public $canWrite = 'no_one';
	
	
	/**
     * Подготовка на данните за справка
     */
    public function prepareAccReports(&$data)
    {
    	// Роли по подразбиране
    	setIfNot($data->masterMvc->canReports, 'ceo,reports');
    	
    	// Ако потребителя има достъп до репортите
    	if(haveRole($data->masterMvc->canReports)){
    		
    		// Информацията за перата
    		$this->ObjectLists->prepareObjectLists($data);
    		
    		// Извличане на счетоводните записи
    		$this->prepareBalanceReports($data);
    		$data->Order = 1;
    	} else {
    		
    		// Ако няма права дисейлбваме таба
    		$data->disabled = TRUE;
    		$data->Order = 80;
    	}
    	
    	// Име на таба
    	$data->TabCaption = 'Счетоводство';
    }
    
    
	/**
     * Рендиране на данните за справка
     */
    public function renderAccReports(&$data)
    {
    	// Взима се шаблона
    	$tpl = new ET("");
    	
    	// Рендиране на данните за номенклатурата
    	$itemsTpl = $this->ObjectLists->renderObjectLists($data);
    	
    	// Добаяне на информацията за номенклатурите в шаблона
    	$tpl->append($itemsTpl);
    	
    	// Добавяне на интервал между двата детайла
    	$tpl->append("<br />");
    	
    	// Рендиране на баланс репортите
    	$balanceTpl = $this->renderBalanceReports($data);
    	
    	// Добавяне на репорта в шаблона
    	$tpl->append($balanceTpl);
    	
    	// Връщане на шаблона
    	return $tpl;
    }
    
    
    /**
     * Подготовка на данните на баланса
     * 
     * @param stdClass $data - обект с данни от мастъра
     */
    private function prepareBalanceReports(&$data)
    {
    	$accounts = arr::make($data->masterMvc->balanceRefAccounts);
    	
    	// Полета за таблицата
    	$data->listFields = arr::make("tools=Пулт,ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,blQuantity=К-во,blAmount=Сума");
    	
    	// Перото с което мастъра фигурира в счетоводството
    	$items = acc_Items::fetchItem($data->masterMvc->getClassId(), $data->masterId);
    	
    	// Ако мастъра не е перо, няма какво да се показва
    	if(empty($items)) return;
    	
    	// По коя номенклатура ще се групира
    	$groupBy = $data->masterMvc->balanceRefGroupBy;
    	
    	// Взимане на данните от текущия баланс в който участват посочените сметки
    	// и ид-то на перото е на произволна позиция
    	$dRecs = acc_Balances::fetchCurrent($accounts, $items->id);
    	
    	// Ако няма записи, не се прави нищо
    	if(empty($dRecs) || !count($dRecs)) return;
    	
    	$rows = array();
	    $Double = cls::get('type_Double');
	    $Double->params['decimals'] = 2;
	    
	    $data->recs = $dRecs;
	    
	    // Извикване на евент в мастъра за след извличане на записите от БД
	    $data->masterMvc->invoke('AfterPrepareAccReportRecs', array($data));
	    $balanceRec = acc_Balances::getLastBalance();
	    $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf('img/16/clock_history.png', '') . ');';
	    $attr['title'] = tr("Хронологична справка");
        
    	foreach ($data->recs as $dRec){
    		
    		// На коя позиция се намира, перото на мастъра
	    	$gPos = acc_Lists::getPosition($dRec->accountNum, $groupBy);
	    	
	    	// Обхождане на останалите пера
	    	$row = array();
	    	$accGroups = acc_Accounts::getAccountInfo($dRec->accountId)->groups;
	    	
	    	foreach (range(1, 3) as $pos){
	    		$entry = $dRec->{"ent{$pos}Id"};
	    		
	    		// Ако има ентри и то е позволено за сметката
	    		if(isset($entry) && isset($accGroups[$pos])){
	    				
	    			// Ако перото не е групиращото, ще се показва в справката
	    			$row["ent{$pos}Id"] = acc_Items::getVerbal(acc_Items::fetch($entry), 'numTitleLink');
	    		}
	    	}
	    	
	    	$histUrl = array('acc_BalanceDetails', 'history', 'fromDate' => $balanceRec->fromDate, 'toDate' => $balanceRec->toDate, 'accountId' => $dRec->accountId);
	    	$histUrl['ent1Id'] = $dRec->ent1Id;
	    	$histUrl['ent2Id'] = $dRec->ent2Id;
	    	$histUrl['ent3Id'] = $dRec->ent3Id;
	    	
	    	// Ако има повече от едно перо, несе показва това на мениджъра
	    	if(count($row) > 1) {
	    		unset($row["ent{$gPos}Id"]);
	    	}
	    	
	    	$row['tools'] = ht::createLink(' ', $histUrl, NULL, $attr);
	    	
	    	// К-то и сумата се обръщат във вербален вид
	    	foreach (array('blQuantity', 'blAmount') as $fld){
	    		$style = ($dRec->$fld < 0) ? "color:red" : "";
	    		$row[$fld] = "<span style='float:right;{$style}'>" . $Double->toVerbal($dRec->$fld) . "</span>";
	    	}
	    	
	    	$rows[$dRec->accountId][] = $row;
    	}
	  	
    	// Връщане на извлечените данни
	    $data->balanceRows = $rows;
	    
	    // Извикване на евент в мастъра, че записите са подготвени
	    $data->masterMvc->invoke('AfterPrepareAccReportRows', array($data));
    }
    
    
    /**
     * Рендиране на данните за баланса
     * 
     * @param stdClass $data - обект с данни от мастъра
     * @return core_ET - шаблона на детайла
     */
    private function renderBalanceReports(&$data)
    {
    	$tpl = getTplFromFile('acc/tpl/BalanceRefDetail.shtml');
    	$data->listFields['tools'] = ' ';
    	
    	// Ако има какво да се показва
    	if($data->balanceRows){
    		$tMvc = cls::get('core_Mvc');
    		$tMvc->FLD('tools', 'varchar', 'style=accToolsCell');
    		$tMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
    		$tMvc->FLD('blAmount', 'int', 'tdClass=accCell');
    		$table = cls::get('core_TableView', array('mvc' => $tMvc));
    		
    		// За всички записи групирани по сметки
    		foreach ($data->balanceRows as $accId => $rows){
    			
    			// Името на сметката и нейните групи
    			$accNum = acc_Accounts::getTitleById($accId);
    			$accGroups = acc_Accounts::getAccountInfo($accId)->groups;
    			
    			// Името на сметката излиза над таблицата
    			$content = new ET("<span>{$accNum}</span></br />");
    			$fields = $data->listFields;
    			
    			// Обикаляне на всички пера
    			foreach (range(1, 3) as $i){
    				$ent = "ent{$i}Id";
    				if(empty($rows[0][$ent])){
    					
    					// Ако не са сетнати не се показва колонка в таблицата
    					unset($fields[$ent]);
    				} else {
    					
    					// Вербалното име на номенклатурата
    					$fields[$ent] = $accGroups[$i]->rec->name;
    				}
    			}
    			
    			// Добавяне на таблицата в шаблона
    			$content->append($table->get($rows, $fields));
    			$tpl->append($content . "</br />", 'CONTENT');
    		}
    	} else {
    		
    		// Ако няма какво да се показва
    		$tpl->append(tr("Няма справки"), 'CONTENT');
    	}
    	
    	// Връщане на шаблона
    	return $tpl;
    }
}