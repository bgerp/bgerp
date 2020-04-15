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
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
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
        setIfNot($data->masterMvc->canAddacclimits, 'ceo,accLimits');
        setIfNot($data->masterMvc->balanceRefShowZeroRows, true);
        setIfNot($data->masterMvc->showAccReportsInTab, true);
        
        $data->TabCaption = 'Счетоводство';
        
        $balanceRec = acc_Balances::getLastBalance();
        $data->balanceRec = $balanceRec;
        
        // Ако няма баланс или записи в баланса, не показваме таба
        $data->renderReports = true;
        
        $tabParam = 'Tab';
        
        // Ако мастъра е документ, искаме детайла да се показва в горния таб с детайл
        if (cls::haveInterface('doc_DocumentIntf', $data->masterMvc)) {
            $data->Tab = 'top';
            $tabParam = $data->masterData->tabTopParam;
        }
        
        $prepareTab = Request::get($tabParam);
        $data->prepareTab = false;
        if (!$prepareTab || $prepareTab == 'AccReports') {
            $data->prepareTab = true;
        }
        
        // Ако потребителя има достъп до репортите
        if (haveRole($data->masterMvc->canReports) && ($data->Tab == 'top' || $data->isCurrent)) {
            
            // Извличане на счетоводните записи
            $this->prepareBalanceReports($data);
            $data->renderReports = true;
        
        //$data->Order = 1;
        } else {
            $data->renderReports = false;
        }
    }
    
    
    /**
     * Рендиране на данните за справка
     */
    public function renderAccReports(&$data)
    {
        if ($data->renderReports === false) {
            
            return;
        }
        
        // Взима се шаблона
        $tpl = new ET('');
        
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
        // Перото с което мастъра фигурира в счетоводството
        $data->itemRec = acc_Items::fetchItem($data->masterMvc->getClassId(), $data->masterId);
        
        // Ако мастъра не е перо, няма какво да се показва
        if (empty($data->itemRec)) {
            $data->renderReports = false;
            
            return;
        }
        
        if ($data->prepareTab === false) {
            
            return;
        }
        
        $accounts = arr::make($data->masterMvc->balanceRefAccounts, true);
        $data->canSeePrices = haveRole('ceo,accJournal');
        
        // Полета за таблицата
        $data->listFields = arr::make('tools=Пулт,ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,blQuantity=К-во,blPrice=Цена,blAmount=Сума');
        if ($data->canSeePrices === false) {
            unset($data->listFields['blPrice'], $data->listFields['blAmount']);
        }
        
        $data->limitFields = arr::make('item1=item1,item2=item2,item3=item3,side=Салдо,type=Вид,limitQuantity=Сума,createdBy=Създадено от');
        
        // Създаване на нова инстанция на core_Mvc за задаване на td - класове
        // Създава се с new за да сме сигурни, че обекта е нова празна инстанция
        $data->reportTableMvc = new core_Mvc;
        $data->reportTableMvc->FLD('tools', 'varchar', 'tdClass=accToolsCell,smartCenter');
        $data->reportTableMvc->FLD('blQuantity', 'int', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('limitQuantity', 'double', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('createdBy', 'double', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('blAmount', 'int', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('blPrice', 'int', 'tdClass=accCell,smartCenter');
        $data->reportTableMvc->FLD('emptyRow', 'varchar', 'tdClass=accCellNoRows');
        $data->total = 0;
        
        // Ако баланса е заключен не показваме нищо
        if (core_Locks::isLocked(acc_Balances::saveLockKey)) {
            $data->balanceIsRecalculating = true;
            
            return;
        }
        
        // По коя номенклатура ще се групира
        $groupBy = $data->masterMvc->balanceRefGroupBy;
        
        // Взимане на данните от текущия баланс в който участват посочените сметки
        // и ид-то на перото е на произволна позиция
        $res = array();
        $data->recs = acc_Balances::fetchCurrent($accounts, $data->itemRec->id);
        
        // Извикване на евент в мастъра за след извличане на записите от БД
        $data->masterMvc->invoke('AfterPrepareAccReportRecs', array($data));
        
        // Може ли потребителя да вижда хронологията на сметката
        $accounts = arr::make($accounts, true);
        if(is_array($accounts)){
            foreach ($accounts as $accSysId){
                $accountId = acc_Accounts::fetchField("#systemId = '{$accSysId}'", 'id');
                $recsWithAccount = array_filter($data->recs, function ($a) use ($accountId) {return $a->accountId == $accountId;});
                
                if(countR($recsWithAccount)){
                    foreach ($recsWithAccount as $dRec){
                        
                        // Ако има вербализират се
                        if($row = $this->getVerbalBalanceRec($dRec, $groupBy, $data)){
                            $res[$accountId]['rows'][] = $row;
                        }
                    }
                    
                    $res[$accountId]['total'] = arr::sumValuesArray($recsWithAccount, 'blAmount');
                } else {
                    // Ако няма в текущия период, търсим в кой последно има
                    if($lastBalanceIn = acc_Balances::fetchLastBalanceFor($accSysId, $data->itemRec->id)){
                        $lastBalanceRec = acc_Balances::fetch($lastBalanceIn);
                        $lastBalanceLink = acc_Periods::getVerbal($lastBalanceRec->periodId, 'title');
                        if (!Mode::isReadOnly()) {
                            $lastBalanceLink = ht::createLink($lastBalanceLink, acc_Balances::getSingleUrlArray($lastBalanceIn), false, array('title' => "Оборотна ведомост за|* \"{$lastBalanceLink}\""));
                        }
                        
                        $row = (object)array('emptyRow' => tr("Последни записи от|* <b>{$lastBalanceLink}</b>"));
                        $bRec = (object)array('balanceId' => $lastBalanceIn);
                        
                        $gPos = acc_Lists::getPosition($accSysId, $groupBy);
                        $bRec->{"ent{$gPos}Id"} = $data->itemRec->id;
                        if (acc_BalanceDetails::haveRightFor('history', $bRec)) {
                            $histUrl = array('acc_BalanceHistory', 'History', 'fromDate' => $lastBalanceRec->fromDate, 'toDate' => $lastBalanceRec->toDate, 'accNum' => $accSysId, "ent{$gPos}Id" => $data->itemRec->id);
                            $attr = array('title' => 'Хронологична справка');
                            $attr = ht::addBackgroundIcon($attr, 'img/16/clock_history.png');
                            $row->tools = ht::createLink('', $histUrl, null, $attr);
                        }
                        
                        // Ако има в предишен добавяме линк
                        $res[$accountId]['empty'] = true;
                        $res[$accountId]['total'] = null;
                        $res[$accountId]['rows'][] = $row;
                    }
                }
            }
        }
        
        if (is_array($accounts)) {
            $limitQuery = acc_Limits::getQuery();
            $limitQuery->where("#item1 = {$data->itemRec->id} || #item2 = {$data->itemRec->id} || #item3 = {$data->itemRec->id}");
            while ($lRec = $limitQuery->fetch()) {
                $lRow = acc_Limits::recToVerbal($lRec);
                $lRow->state = cls::get('acc_Limits')->getFieldType('state')->toVerbal($lRec->state);
                $res[$lRec->accountId]['limits'][$lRec->id] = $lRow;
            }
        }
        
        $data->total = arr::sumValuesArray($data->recs, 'blAmount');
        $data->totalRow = core_Type::getByName('double(decimals=2)')->toVerbal($data->total);
        $data->totalRow = ($data->total < 0) ? "<span class='red'>{$data->totalRow}</span>" : $data->totalRow;
        
        // Връщане на извлечените данни
        $data->balanceRows = $res;
        
        // Извикване на евент в мастъра, че записите са подготвени
        $data->masterMvc->invoke('AfterPrepareAccReportRows', array($data));
    }
    
    
    /**
     * Вербализира записа
     * 
     * @param stdClass $dRec
     * @param array $groupBy
     * @param stdClass $data
     * @return null|stdClass
     */
    private function getVerbalBalanceRec($dRec, $groupBy, $data)
    {
       $row = array();
       $dRec->blPrice = (!empty($dRec->blQuantity)) ? $dRec->blAmount / $dRec->blQuantity : 0;
       
       // На коя позиция се намира, перото на мастъра
       $gPos = acc_Lists::getPosition(acc_Accounts::fetchField($dRec->accountId, 'systemId'), $groupBy);
       
       // Обхождане на останалите пера
       
       $accGroups = acc_Accounts::getAccountInfo($dRec->accountId)->groups;
       
       foreach (range(1, 3) as $pos) {
           $entry = $dRec->{"ent{$pos}Id"};
           
           // Ако има ентри и то е позволено за сметката
           if (isset($entry, $accGroups[$pos])) {
               
               // Ако перото не е групиращото, ще се показва в справката
               $row["ent{$pos}Id"] = acc_Items::getVerbal(acc_Items::fetch($entry), 'titleLink');
               $row["ent{$pos}Id"] = "<span class='feather-title'>{$row["ent{$pos}Id"]}</span>";
           }
       }
       
       // Ако има повече от едно перо, несе показва това на мениджъра
       if (countR($row) > 1) {
           unset($row["ent{$gPos}Id"]);
       }
       
       if (acc_BalanceDetails::haveRightFor('history', $dRec)) {
           $histUrl = array('acc_BalanceHistory', 'History', 'fromDate' => $data->balanceRec->fromDate, 'toDate' => $data->balanceRec->toDate, 'accNum' => $dRec->accountNum);
           $histUrl['ent1Id'] = $dRec->ent1Id;
           $histUrl['ent2Id'] = $dRec->ent2Id;
           $histUrl['ent3Id'] = $dRec->ent3Id;
           
           $attr = array('title' => 'Хронологична справка');
           $attr = ht::addBackgroundIcon($attr, 'img/16/clock_history.png');
           $row['tools'] = ht::createLink('', $histUrl, null, $attr);
       }
       
       // К-то и сумата се обръщат във вербален вид
       $Double = core_Type::getByName('double(decimals=2)');
       foreach (array('blQuantity', 'blAmount', 'blPrice') as $fld) {
           $style = ($dRec->{$fld} < 0) ? 'color:red' : '';
           $row[$fld] = "<span style='float:right;{$style}'>" . $Double->toVerbal($dRec->{$fld}) . '</span>';
       }
       
       $row['amountRec'] = $dRec->blAmount;
       $row['id'] = $dRec->id;
       
       $conf = core_Packs::getConfig('acc');
       $tolerance = $conf->ACC_MONEY_TOLERANCE;
       
       // Ако количеството и сумата са близки до нулата в определена граница ги пропускаме, освен ако не е указано да се показват
       if (($dRec->blQuantity > (-1 * $tolerance) && $dRec->blQuantity < $tolerance) &&
           ($dRec->blAmount > (-1 * $tolerance) && $dRec->blAmount < $tolerance) && $data->masterMvc->balanceRefShowZeroRows === false) {
               
               return null;
       }
           
       return (object)$row;
    }
    
    
    /**
     * Рендиране на данните за баланса
     *
     * @param stdClass $data - обект с данни от мастъра
     *
     * @return core_ET - шаблона на детайла
     */
    private function renderBalanceReports(&$data)
    {
        $tpl = getTplFromFile('acc/tpl/BalanceRefDetail.shtml');
        if(is_object($data->itemRec)){
            $itemLink = acc_Items::getVerbal($data->itemRec, 'titleLink');
        } else {
            $itemLink = tr('НЯМА');
        }
        $tpl->append($itemLink, 'itemId');
        
        if (isset($data->balanceRec->periodId)) {
            $link = acc_Periods::getVerbal($data->balanceRec->periodId, 'title');
            if (!Mode::isReadOnly()) {
                $link = ht::createLink($link, acc_Balances::getSingleUrlArray($data->balanceRec->id), false, array('title' => "Оборотна ведомост за|* \"{$link}\""));
            }
            
            $tpl->replace($link, 'periodId');
        }
        
        // Ако баланса се преизчислява в момента, показваме подходящо съобщение
        if ($data->balanceIsRecalculating === true) {
            $warning = "<span class='red'>" . tr('Балансът се преизчислява в момента|*. |Моля, изчакайте|*!') . '</span>';
            $tpl->append($warning, 'CONTENT');
            
            return $tpl;
        }
        
        $limitTitle = tr('Лимити');
        $tpl->replace($limitTitle, 'LIMIT_LINK');
        $data->listFields['tools'] = ' ';
        
        // Ако има какво да се показва
        if ($data->balanceRows) {
            $Double = cls::get('type_Double');
            $Double->params['decimals'] = 2;
            
            $table = cls::get('core_TableView', array('mvc' => $data->reportTableMvc));
            $count = $limitCount = 0;
            
            // За всички записи групирани по сметки
            foreach ($data->balanceRows as $accId => $arr) {
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
                if (!acc_Lists::getPosition($accInfo->rec->systemId, 'cat_ProductAccRegIntf')) {
                    unset($fields['blPrice']);
                }
                
                if (Mode::isReadOnly()) {
                    unset($fields['tools']);
                }
                
                $limitFields = $data->limitFields;
                
                $unsetPosition = acc_Lists::getPosition($accInfo->rec->systemId, $data->masterMvc->balanceRefGroupBy);
                foreach (range(1, 3) as $i) {
                    if ($i != $unsetPosition && isset($accGroups[$i])) {
                        $fields["ent{$i}Id"] = $accGroups[$i]->rec->name;
                        $limitFields["item{$i}"] = $accGroups[$i]->rec->name;
                    } else {
                        unset($fields["ent{$i}Id"]);
                        unset($limitFields["item{$i}"]);
                    }
                }
                
                $tableHtml = null;
                if($arr['empty'] !== true){
                    if(countR($rows)){
                        $fields = core_TableView::filterEmptyColumns($rows, $fields, 'tools');
                        $tableHtml = $table->get($rows, $fields);
                        if ($data->canSeePrices !== false) {
                            $colspan = countR($fields) - 1;
                            $totalRow = $Double->toVerbal($total);
                            $totalRow = ($total < 0) ? "<span style='color:red'>{$totalRow}</span>" : $totalRow;
                            $totalHtml = "<tr><th colspan='{$colspan}' style='text-align:right'>" . tr('Общо') . ":</th><th style='padding-right: 4px; font-weight:bold'><span class='maxwidth totalCol accCell'>{$totalRow}</th></th></tr>";
                            $tableHtml->replace($totalHtml, 'ROW_AFTER');
                        }
                    }
                } else {
                    unset($fields["ent2Id"], $fields["ent3Id"], $fields["blQuantity"], $fields["blAmount"], $fields['blPrice']);
                    $fields["emptyRow"] = " ";
                    
                    $fields = core_TableView::filterEmptyColumns($rows, $fields, 'tools');
                    $tableHtml = $table->get($rows, $fields);
                }
               
                if(!is_null($tableHtml)){
                    $tableHtml->removeBlocks();
                    $content->append($tableHtml);
                    $count++;
                    
                    $tpl->append("<div class='summary-group'>" . $content . '</div>', 'CONTENT');
                }
                
                // Ако има зададени лимити за тази сметка, показваме и тях
                if (countR($arr['limits'])) {
                    $unset1 = $unset2 = $unset3 = true;
                    foreach ($arr['limits'] as $lRec) {
                        $lRec->_rowTools = $lRec->_rowTools->renderHtml();
                        foreach (range(1, 3) as $i) {
                            if (isset($lRec->{"item{$i}"})) {
                                ${"unset{$i}"} = false;
                            }
                        }
                    }
                    
                    foreach (range(1, 3) as $i) {
                        if (${"unset{$i}"} === true) {
                            unset($limitFields["item{$i}"]);
                        }
                    }
                    
                    $tpl->append("<span class='accTitle' style = 'margin-top:7px'>{$accNum}</span>", 'LIMITS');
                    $limitFields = array('_rowTools' => 'Пулт') + $limitFields;
                    $limitsHtml = $table->get($arr['limits'], $limitFields);
                    $tpl->append($limitsHtml, 'LIMITS');
                    $limitCount++;
                }
            }
            
            if ($count > 1 && $data->canSeePrices !== false) {
                $lastRow = "<div class='acc-footer' style='padding-right: 13px;'>" . tr('Сумарно'). ': ' . $data->totalRow . '</div>';
                $tpl->append($lastRow, 'CONTENT');
            }
        }
        
        if (!$count) {
            $tpl->append(tr('Няма записи'), 'CONTENT');
        }
        
        if (!$limitCount) {
            $tpl->append(tr('Няма записи'), 'LIMITS');
        }
        
        // Ако потребителя може да добавя счетоводни лимити
        if (acc_Limits::haveRightFor('add', (object) array('objectId' => $data->masterId, 'classId' => $data->masterMvc->getClassId()))
                && !Mode::isReadOnly()) {
            $url = array('acc_Limits', 'add', 'classId' => $data->masterMvc->getClassId(), 'objectId' => $data->masterId, 'ret_url' => true);
            $btn = ht::createLink('', $url, false, 'ef_icon=img/16/add.png,title=Добавяне на ново ограничение на перото');
            $tpl->append($btn, 'BTN_LIMITS');
        }
        
        // Връщане на шаблона
        return $tpl;
    }
}
