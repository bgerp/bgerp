<?php



/**
 * Клас показващ счетоводна информация за даден мениджър който е перо в счетоводството
 * За да работи трябва да се добави като детайл на съответния мениджър
 * В мениджъра е нужно да има следните класови променливи:
 *
 * $balanceRefAccounts     - систем ид-та на сч. сметки, от които ще се правят справки
 * $balanceRefGroupBy      - интерфейс на сч. перо по което ще се групират(този на мениджъра)
 * $balanceRefShowZeroRows - да се показват ли записите близки до нулата
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
        setIfNot($data->masterMvc->canReports, 'ceo');
        setIfNot($data->masterMvc->canAddacclimits, 'ceo');
        setIfNot($data->masterMvc->balanceRefShowZeroRows, TRUE);
        setIfNot($data->masterMvc->showAccReportsInTab, TRUE);
        
        $balanceRec = acc_Balances::getLastBalance();
        $data->balanceRec = $balanceRec;
        
        // Ако няма баланс или записи в баланса, не показваме таба
        $data->renderReports = TRUE;
        
        // Ако потребителя има достъп до репортите
        if(haveRole($data->masterMvc->canReports)){
            
            // Извличане на счетоводните записи
            $this->prepareBalanceReports($data);
            $data->Order = 1;
        } else {
        	$data->renderReports = FALSE;
        }
        
        // Име на таба
        if($data->renderReports === TRUE){
        	$data->TabCaption = 'Счетоводство';
        }
        
        // Ако мастъра е документ, искаме детайла да се показва в горния таб с детайл
        if(cls::haveInterface('doc_DocumentIntf', $data->masterMvc)){
        	$data->Tab = 'top';
        }
    }
    
    
    /**
     * Рендиране на данните за справка
     */
    public function renderAccReports(&$data)
    {
        if($data->renderReports === FALSE) return;
        
        // Взима се шаблона
        $tpl = new ET("");
        
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
    	$data->canSeePrices = haveRole('ceo,accJournal');
    	
        // Полета за таблицата
        $data->listFields = arr::make("tools=Пулт,ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,blQuantity=К-во,blPrice=Цена,blAmount=Сума");
        if($data->canSeePrices === FALSE){
        	unset($data->listFields['blPrice'],$data->listFields['blAmount']);
        }
        
        $data->limitFields = arr::make("item1=item1,item2=item2,item3=item3,side=Салдо,type=Вид,limitQuantity=Сума,createdBy=Създадено от");
        
        // Създаване на нова инстанция на core_Mvc за задаване на td - класове
        // Създава се с new за да сме сигурни, че обекта е нова празна инстанция
        $data->reportTableMvc = new core_Mvc;
        $data->reportTableMvc->FLD('tools', 'varchar', 'tdClass=accToolsCell,smartCenter');
        $data->reportTableMvc->FLD('blQuantity', 'int', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('limitQuantity', 'double', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('createdBy', 'double', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('blAmount', 'int', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('blPrice', 'int', 'tdclass=accCell,smartCenter');
        $data->total = 0;
        
        // Перото с което мастъра фигурира в счетоводството
        $items = acc_Items::fetchItem($data->masterMvc->getClassId(), $data->masterId);
        
        // Ако мастъра не е перо, няма какво да се показва
        if(empty($items)) {
        	$data->renderReports = FALSE;
        	return;
        }
        
        // Ако баланса е заключен не показваме нищо
        if(core_Locks::isLocked('RecalcBalances')){
        	$data->balanceIsRecalculating = TRUE;
        	return;
        }
        
        // По коя номенклатура ще се групира
        $groupBy = $data->masterMvc->balanceRefGroupBy;
        
        // Взимане на данните от текущия баланс в който участват посочените сметки
        // и ид-то на перото е на произволна позиция
        $dRecs = acc_Balances::fetchCurrent($accounts, $items->id);
        
        $rows = array();
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        $data->recs = $dRecs;
        
        // Извикване на евент в мастъра за след извличане на записите от БД
        $data->masterMvc->invoke('AfterPrepareAccReportRecs', array($data));
        
        $attr = array();
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf('img/16/clock_history.png', '') . ');';
        $attr['title'] = tr("Хронологична справка");
        
        foreach ($data->recs as $dRec){
            @$dRec->blPrice = $dRec->blAmount / $dRec->blQuantity;
        	
            // На коя позиция се намира, перото на мастъра
            $gPos = acc_Lists::getPosition(acc_Accounts::fetchField($dRec->accountId, 'systemId'), $groupBy);
            
            // Обхождане на останалите пера
            $row = array();
            $accGroups = acc_Accounts::getAccountInfo($dRec->accountId)->groups;
            
            foreach (range(1, 3) as $pos){
                $entry = $dRec->{"ent{$pos}Id"};
                
                // Ако има ентри и то е позволено за сметката
                if(isset($entry) && isset($accGroups[$pos])){
                    
                    // Ако перото не е групиращото, ще се показва в справката
                    $row["ent{$pos}Id"] = acc_Items::getVerbal(acc_Items::fetch($entry), 'titleLink');
                    $row["ent{$pos}Id"] = "<span class='feather-title'>{$row["ent{$pos}Id"]}</span>";
                }
            }
            
            // Ако има повече от едно перо, несе показва това на мениджъра
            if(count($row) > 1) {
                unset($row["ent{$gPos}Id"]);
            }
            
            if(acc_BalanceDetails::haveRightFor('history', $dRec)){
                $histUrl = array('acc_BalanceHistory', 'History', 'fromDate' => $data->balanceRec->fromDate, 'toDate' => $data->balanceRec->toDate, 'accNum' => $dRec->accountNum);
                $histUrl['ent1Id'] = $dRec->ent1Id;
                $histUrl['ent2Id'] = $dRec->ent2Id;
                $histUrl['ent3Id'] = $dRec->ent3Id;
                $row['tools'] = ht::createLink('', $histUrl, NULL, $attr);
            }
            
            // К-то и сумата се обръщат във вербален вид
            foreach (array('blQuantity', 'blAmount', 'blPrice') as $fld){
                $style = ($dRec->$fld < 0) ? "color:red" : "";
                $row[$fld] = "<span style='float:right;{$style}'>" . $Double->toVerbal($dRec->$fld) . "</span>";
            }
            
            $row['amountRec'] = $dRec->blAmount;
            $row['id'] = $dRec->id;
            
            $conf = core_Packs::getConfig('acc');
            $tolerance = $conf->ACC_MONEY_TOLERANCE;
            
            // Ако количеството и сумата са близки до нулата в определена граница ги пропускаме, освен ако не е указано да се показват
            if(($dRec->blQuantity > (-1 * $tolerance) &&  $dRec->blQuantity < $tolerance) &&
                ($dRec->blAmount > (-1 * $tolerance) &&  $dRec->blAmount < $tolerance) && $data->masterMvc->balanceRefShowZeroRows === FALSE) {
                continue;
            }
            
            $rows[$dRec->accountId]['rows'][] = $row;
            $rows[$dRec->accountId]['total'] += $dRec->blAmount;
            $data->total += $dRec->blAmount;
        }
        
        // За засегнатите сметки, проверяваме имали зададени сч. лимити за тях
        foreach ($accounts as $sysId){
        	$limitQuery = acc_Limits::getQuery();
        	$limitQuery->where("#item1 = {$items->id} || #item2 = {$items->id} || #item3 = {$items->id}");
        	 
        	$row1['limits'] = array();
        	while($lRec = $limitQuery->fetch()){
        		 
        		$lRow = acc_Limits::recToVerbal($lRec);
        		$lRow->state = cls::get('acc_Limits')->getFieldType('state')->toVerbal($lRec->state);
        		$rows[$lRec->accountId]['limits'][$lRec->id] = $lRow;
        	}
        }
        
        $data->totalRow = $Double->toVerbal($data->total);
        $data->totalRow = ($data->total < 0) ? "<span class='red'>{$data->totalRow}</span>" : $data->totalRow;
        
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
    	
    	// Ако баланса се преизчислява в момента, показваме подходящо съобщение
    	if($data->balanceIsRecalculating === TRUE){
    		$warning = "<span class='red'>" . tr('Баланса се преизчислява в момента|*! |Моля изчакайте|*.') . "</span>";
        	$tpl->append($warning, 'CONTENT');
        	
        	return $tpl;
        }
    	
        if(isset($data->balanceRec->periodId)){
        	$tpl->replace(acc_Periods::getVerbal($data->balanceRec->periodId, 'title'), 'periodId');
        }
        
        $limitTitle = tr("Лимити");
        if(acc_Limits::haveRightFor('list') && !Mode::is('text', 'xhtml') && !Mode::is('printing')){
        	$limitTitle = ht::createLink($limitTitle, array('acc_Limits', 'list'), FALSE, 'title=Към счетоводните лимити');
        }
        $tpl->replace($limitTitle, 'LIMIT_LINK');
        
        $data->listFields['tools'] = ' ';
        
        // Ако има какво да се показва
        if($data->balanceRows){
            $Double = cls::get('type_Double');
            $Double->params['decimals'] = 2;
            
            $table = cls::get('core_TableView', array('mvc' => $data->reportTableMvc));
            $count = $limitCount = 0;
            
            // За всички записи групирани по сметки
            foreach ($data->balanceRows as $accId => $arr){
                $rows = $arr['rows'];
                $total = $arr['total'];
                
                // Името на сметката и нейните групи
                $accNum = acc_Balances::getAccountLink($accId);
                $accInfo = acc_Accounts::getAccountInfo($accId);
                $accGroups = $accInfo->groups;
                
                // Името на сметката излиза над таблицата
                $content = new ET("<span class='accTitle'>{$accNum}</span>");
                $fields = $data->listFields;
                
                // Ако няма номенклатура артикул в сметката, не показваме еденичната цена
                if(!acc_Lists::getPosition($accInfo->rec->systemId, 'cat_ProductAccRegIntf')){
                	unset($fields['blPrice']);
                }
                
                $limitFields = $data->limitFields;
                
                $unsetPosition = acc_Lists::getPosition($accInfo->rec->systemId, $data->masterMvc->balanceRefGroupBy);
                foreach (range(1, 3) as $i){
                	if($i != $unsetPosition && isset($accGroups[$i])){
                		
                		$fields["ent{$i}Id"] = $accGroups[$i]->rec->name;
                		$limitFields["item{$i}"] = $accGroups[$i]->rec->name;
                	} else {
                		
                		unset($fields["ent{$i}Id"]);
                		unset($limitFields["item{$i}"]);
                	}
                }
               
                // Ако има записи показваме таблицата
                if(count($rows)){
                	
                	$tableHtml = $table->get($rows, $fields);
                	
                	if($data->canSeePrices !== FALSE){
                		$colspan = count($fields) - 1;
                		$totalRow = $Double->toVerbal($total);
                		$totalRow = ($total < 0) ? "<span style='color:red'>{$totalRow}</span>" : $totalRow;
                		$totalHtml = "<tr><th colspan='{$colspan}' style='text-align:right'>" . tr('Общо') . ":</th><th style='text-align:right;font-weight:bold'>{$totalRow}</th></tr>";
                		$tableHtml->replace($totalHtml, 'ROW_AFTER');
                		$tableHtml->removeBlocks;
                	}
                	
                	// Добавяне на таблицата в шаблона
                	$content->append($tableHtml);
                	$tpl->append("<div class='summary-group'>" . $content . "</div>" , 'CONTENT');
                	$count++;
                }
               
                // Ако има зададени лимити за тази сметка, показваме и тях
                if(count($arr['limits'])){
                	$unset1 = $unset2 = $unset3 = TRUE;
                	foreach ($arr['limits'] as $lRec){
                		foreach (range(1, 3) as $i){
                			if(isset($lRec->{"item{$i}"})){
                				${"unset{$i}"} = FALSE;
                			}
                		}
                	} 
                	
                	foreach (range(1, 3) as $i){
                		if(${"unset{$i}"} === TRUE){
                			unset($limitFields["item{$i}"]);
                		}
                	}
                	
                	$tpl->append("<span class='accTitle' style = 'margin-top:7px'>{$accNum}</span>", 'LIMITS');
                	$limitsHtml = $table->get($arr['limits'], array('tools' => 'Пулт') + $limitFields);
                	$tpl->append($limitsHtml, 'LIMITS');
                	$limitCount++;
                }
            }
           
            if($count > 1 && $data->canSeePrices !== FALSE){
            	$lastRow = "<div class='acc-footer'>" . tr('Сумарно'). ": " . $data->totalRow . "</div>";
            	$tpl->append($lastRow, 'CONTENT');
            }
        }
        
        if(!$count){
        	$tpl->append(tr("Няма записи"), 'CONTENT');
        }
        
        if(!$limitCount){
        	$tpl->append(tr("Няма записи"), 'LIMITS');
        }
        
        // Ако потребителя може да добавя счетоводни лимити
        if(acc_Limits::haveRightFor('add', (object)array('objectId' => $data->masterId, 'classId' => $data->masterMvc->getClassId())) 
                && !Mode::is('text', 'xhtml') && !Mode::is('printing')){
        	$url = array('acc_Limits', 'add', 'classId' => $data->masterMvc->getClassId(), 'objectId' => $data->masterId, 'ret_url' => TRUE);
        	$btn = ht::createLink('', $url, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на ново ограничение на перото');
        	$tpl->append($btn, 'BTN_LIMITS');
        }
        
        // Връщане на шаблона
        return $tpl;
    }
}
