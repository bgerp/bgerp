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
        setIfNot($data->masterMvc->balanceRefShowZeroRows, TRUE);
        setIfNot($data->masterMvc->showAccReportsInTab, TRUE);
        
        // Ако потребителя има достъп до репортите
        if(haveRole($data->masterMvc->canReports)){
            
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
        
        // Полета за таблицата
        $data->listFields = arr::make("tools=Пулт,ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,blQuantity=К-во,blAmount=Сума");
        
        // Създаване на нова инстанция на core_Mvc за задаване на td - класове
        // Създава се с new за да сме сигурни че обекта е нова празна инстанция
        $data->reportTableMvc = new core_Mvc;
        $data->reportTableMvc->FLD('tools', 'varchar', 'tdClass=accToolsCell');
        $data->reportTableMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
        $data->reportTableMvc->FLD('blAmount', 'int', 'tdClass=accCell');
        $data->total = 0;
        
        // Перото с което мастъра фигурира в счетоводството
        $items = acc_Items::fetchItem($data->masterMvc->getClassId(), $data->masterId);
        
        $balanceRec = acc_Balances::getLastBalance();
        $data->balanceRec = $balanceRec;
        
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
        
        $attr = array();
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf('img/16/clock_history.png', '') . ');';
        $attr['title'] = tr("Хронологична справка");
        
        foreach ($data->recs as $dRec){
            
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
                $histUrl = array('acc_BalanceHistory', 'History', 'fromDate' => $balanceRec->fromDate, 'toDate' => $balanceRec->toDate, 'accNum' => $dRec->accountNum);
                $histUrl['ent1Id'] = $dRec->ent1Id;
                $histUrl['ent2Id'] = $dRec->ent2Id;
                $histUrl['ent3Id'] = $dRec->ent3Id;
                $row['tools'] = ht::createLink('', $histUrl, NULL, $attr);
            }
            
            // К-то и сумата се обръщат във вербален вид
            foreach (array('blQuantity', 'blAmount') as $fld){
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
        if(isset($data->balanceRec->periodId)){
        	$tpl->replace(acc_Periods::getVerbal($data->balanceRec->periodId, 'title'), 'periodId');
        }
        
        $data->listFields['tools'] = ' ';
        
        // Ако има какво да се показва
        if($data->balanceRows){
            $Double = cls::get('type_Double');
            $Double->params['decimals'] = 2;
            
            $table = cls::get('core_TableView', array('mvc' => $data->reportTableMvc));
            
            // За всички записи групирани по сметки
            foreach ($data->balanceRows as $accId => $arr){
                $rows = $arr['rows'];
                $total = $arr['total'];
                
                // Името на сметката и нейните групи
                $accNum = acc_Balances::getAccountLink($accId);
                $accGroups = acc_Accounts::getAccountInfo($accId)->groups;
                
                // Името на сметката излиза над таблицата
                $content = new ET("<span class='accTitle'>{$accNum}</span>");
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
                
                $tableHtml = $table->get($rows, $fields);
                $colspan = count($fields) - 1;
                $totalRow = $Double->toVerbal($total);
                $totalRow = ($total < 0) ? "<span style='color:red'>{$totalRow}</span>" : $totalRow;
                $totalHtml = "<tr><th colspan='{$colspan}' style='text-align:right'>" . tr('Общо') . ":</th><th style='text-align:right;font-weight:bold'>{$totalRow}</th></tr>";
                $tableHtml->replace($totalHtml, 'ROW_AFTER');
                $tableHtml->removeBlocks;
                
                // Добавяне на таблицата в шаблона
                $content->append($tableHtml);
                $tpl->append("<div class='summary-group'>" . $content . "</div>" , 'CONTENT');
                
            }
            
            if(count($data->balanceRows) > 1){
            	$lastRow = "<div class='acc-footer'>" . tr('Сумарно'). ": " . $data->totalRow . "</div>";
            	$tpl->append($lastRow, 'CONTENT');
            }
        } else {
            
            // Ако няма какво да се показва
            $tpl->append(tr("Няма записи"), 'CONTENT');
        }
        
        
        // Връщане на шаблона
        return $tpl;
    }
}
