<?php



/**
 * Клас показващ счетоводна информация за даден мениджър който е перо в счетоводството
 *
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
     * Подготовка на данните за справка
     */
    public function prepareAccReports(&$data)
    {
    	// Ако има роля право
    	if(haveRole('ceo,reports')){
    		
    		// Подготовка на данните
    		$this->ObjectLists->prepareObjectLists($data);
    		$this->prepareBalanceReports($data);
    	} else {
    		
    		// Ако няма права дисейлбваме таба
    		$data->disabled = TRUE;
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
    	$tpl = new ET();
    	
    	// Рендиране на данните за номенклатурата
    	$itemsTpl = $this->ObjectLists->renderObjectLists($data);
    	$tpl->append($itemsTpl);
    	
    	$tpl->append("<br />");
    	
    	// Рендиране на баланс репортите
    	$balanceTpl = $this->renderBalanceReports($data);
    	$tpl->append($balanceTpl);
    	
    	// Връщане на шаблона
    	return $tpl;
    }
    
    
    /**
     * Подготовка на данните на баланса
     */
    private function prepareBalanceReports(&$data)
    {
    	$accounts = arr::make($data->masterMvc->balanceRefAccounts);
    	$items = acc_Items::fetchItem($data->masterMvc->getClassId(), $data->masterId);
    	
    	// Ако мастъра не е перо, няма какво да се показва
    	if(empty($items)) return;
    	
    	// По коя номенклатура ще се групира
    	$groupBy = $data->masterMvc->balanceRefGroupBy;
    	
    	$dRecs = acc_Balances::fetchCurrent($accounts, $items->id);
    	;
    	if(!count($dRecs)) return;
    	
    	$rows = array();
	    $Double = cls::get('type_Double');
	    $Double->params['decimals'] = 2;
	    
    	foreach ($dRecs as $dRec){
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
	    	
	    	if(count($row) > 1) {
	    		unset($row["ent{$gPos}Id"]);
	    	}
	    	
	    	// К-то и сумата с еобръщат във вербален вид
	    	foreach (array('blQuantity', 'blAmount') as $fld){
	    		$style = ($dRec->$fld < 0) ? "color:red" : "";
	    		$row[$fld] = "<span style='float:right;{$style}'>" . $Double->toVerbal($dRec->$fld) . "</span>";
	    	}
	    	
	    	$rows[$dRec->accountId][] = $row;
    	}
	   
    	// Връщане на извлечените данни
	    $data->balanceRows = $rows;
    }
    
    
    /**
     * Рендиране на данните за баланса
     */
    private function renderBalanceReports(&$data)
    {
    	$tpl = getTplFromFile('acc/tpl/BalanceRefDetail.shtml');
    	
    	// Ако има какво да се показва
    	if($data->balanceRows){
    		$table = cls::get('core_TableView');
    		
    		// За всички записи групирани по сметки
    		foreach ($data->balanceRows as $accId => $rows){
    			
    			// Името на сметката и нейните групи
    			$accNum = acc_Accounts::getTitleById($accId);
    			$accGroups = acc_Accounts::getAccountInfo($accId)->groups;
    			
    			// Името на сметката излиза над таблицата
    			$content = new ET("<span>{$accNum}</span></br />");
    			
    			// Кои полета ще се показват в таблицата
    			$fields = arr::make("ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,blQuantity=К-во,blAmount=Сума");
    			
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
    	
    	return $tpl;
    }
}