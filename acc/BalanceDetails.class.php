<?php



/**
 * Мениджър на записите в баланс
 *
 *
 * @category  all
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceDetails extends core_Detail
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'acc_Wrapper, Accounts=acc_Accounts, Lists=acc_Lists';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "accountNum, accountId, baseQuantity, baseAmount, 
                        debitQuantity, debitAmount,    creditQuantity, creditAmount, 
                        blQuantity, blAmount";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'balanceId';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    /**
     * Временен акумулатор при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    private $balance;
    
    /**
     *
     * Стратегии на сметките - използва се при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    private $strategies;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('balanceId', 'key(mvc=acc_Balances)', 'caption=Баланс');
        $this->FLD('accountId', 'key(mvc=acc_Accounts,title=title)', 'caption=Сметка->име,column=none');
        $this->EXT('accountNum', 'acc_Accounts', 'externalName=num,externalKey=accountId', 'caption=Сметка->#');
        $this->FLD('ent1Id', 'key(mvc=acc_Items,title=numTitleLink)', 'caption=Сметка->перо 1');
        $this->FLD('ent2Id', 'key(mvc=acc_Items,title=numTitleLink)', 'caption=Сметка->перо 2');
        $this->FLD('ent3Id', 'key(mvc=acc_Items,title=numTitleLink)', 'caption=Сметка->перо 3');
        $this->FLD('baseQuantity', 'double', 'caption=База->К-во');
        $this->FLD('baseAmount', 'double(decimals=2)', 'caption=База->Сума');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->К-во');
        $this->FLD('debitAmount', 'double(decimals=2)', 'caption=Дебит->Сума');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->К-во');
        $this->FLD('creditAmount', 'double(decimals=2)', 'caption=Кредит->Сума');
        $this->FLD('blQuantity', 'double', 'caption=Салдо->К-во');
        $this->FLD('blAmount', 'double(decimals=2)', 'caption=Салдо->Сума');
        
        //        $this->setDbUnique('balanceId, accountId, ent1Id, ent2Id, ent3Id');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($mvc->isDetailed()) {
            // Детайлизиран баланс на конкретна аналитична сметка
            $mvc->prepareDetailedBalance($data);
        } else {
            // Обобщен баланс на синтетичните сметки
            $mvc->prepareOverviewBalance($data);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        if ($mvc->isDetailed() && $groupingForm = $mvc->getGroupingForm($data->masterId)) {
            $groupBy = array();
            
            //       foreach (range(1,3) as $i) {
            //           if ($groupingForm->rec->{"grouping{$i}"} && !$groupingForm->rec->{"filter{$i}"}) {
            //               $groupBy[$i] = $groupingForm->rec->{"grouping{$i}"};
            //           }
            //       }
            
            if (!empty($groupBy)) {
                $mvc->doGrouping($data, $groupBy);
            }
        }
    }
    
    
    /**
     * Групира записите на баланс по зададен признак
     *
     * @param StdClass $data
     * @param array $by масив от признаци - $by[N]: признак за групиране по N-тата аналитичност
     * N = 1,2,3
     */
    private function doGrouping($data, $by)
    {
        $groupedRecs = array();
        $groupedIdx = array();
        $listRec = array();
        $registers = array();
        
        // Извличаме записите за номенклатурите, по които имаме групиране
        foreach (array_keys($by) as $i) {
            $listRec[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
        }
        
        foreach ($data->recs as $rec) {
            $f = array(1=>null, 2=>null, 3=>null);
            
            foreach ($by as $i=>$featureId) {
                $f[$i] = $this->Lists->getGroupOf(
                    $listRec[$i],
                    $rec->{"ent{$i}Id"},
                    $featureId
                );
            }
            
            $r = &$groupedIdx[$f[1]][$f[2]][$f[3]];
            
            if (!isset($r)) {
                $r->grouping1 = $f[1];
                $r->grouping2 = $f[2];
                $r->grouping3 = $f[3];
                $groupedRecs[] = &$r;
            }
            
            $r->baseQuantity += $rec->baseQuantity;
            $r->baseAmount += $rec->baseAmount;
            $r->debitQuantity += $rec->debitQuantity;
            $r->debitAmount += $rec->debitAmount;
            $r->creditQuantity += $rec->creditQuantity;
            $r->creditAmount += $rec->creditAmount;
            $r->blQuantity += $rec->blQuantity;
            $r->blAmount += $rec->blAmount;
        }
        
        $data->recs = $groupedRecs;
        
        // Конвертираме групираните записи към вербални стойности
        $data->rows = array();
        
        foreach ($data->recs as $rec) {
            $data->rows[] = $this->recToVerbal($rec, $data->listFields);
        }
    }
    
    
    /**
     * Подготовка за обобщен баланс на синтетичните сметки
     *
     * @param StdClass $data
     */
    private function prepareOverviewBalance($data)
    {
        $data->query->where('#ent1Id IS NULL AND #ent2Id IS NULL AND #ent3Id IS NULL');
        $data->query->orderBy('#accountNum', 'ASC');
        
        $data->listFields = array(
            'accountNum' => 'Сметка->#',
            'accountId' => 'Сметка->име',
            'debitAmount' => 'Обороти->дебит',
            'creditAmount' => 'Обороти->кредит',
            'baseAmount' => 'Салдо->начално',
            'blAmount' => 'Салдо->крайно',
        );
    }
    
    
    /**
     * Подготовка за детайлизиран баланс на конкретна аналитична сметка,
     * евентуално групиран по зададени признаци
     *
     * @param StdClass $data
     */
    private function prepareDetailedBalance($data)
    {
        // Кода по-надолу има смисъл само за детайлизиран баланс, очаква да има фиксирана
        // сметка.
        expect($this->Master->accountRec);
        
        $data->query->where("#accountId = {$this->Master->accountRec->id}");
        $data->query->where('#ent1Id IS NOT NULL OR #ent2Id IS NOT NULL OR #ent3Id IS NOT NULL');
        
        $groupingForm = $this->getGroupingForm($data->masterId);
        
        // Извличаме записите за номенклатурите, по които е разбита сметката
        $listRecs = array();
        $registers = array();
        
        foreach (range(1, 3) as $i) {
            if ($this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
            }
        }
        
        $data->listFields = array();
        
        /**
         * Указва дали редом с паричните стойности да се покажат и колони с количества.
         *
         * Количествата има смисъл да се виждат само за сметки, на които поне една от
         * аналитичностите е измерима.
         *
         * @var boolean true - показват се и количества, false - не се показват
         */
        $bShowQuantities = FALSE;
        
        foreach ($listRecs as $i=>$listRec) {
            $bShowQuantities = $bShowQuantities || ($listRec->dimensional == 'yes');
            
            if ($groupingForm && $groupingForm->rec->{"grouping{$i}"}) {
                //
                // Групиране по признак на текущата номенклатура
                //
                if ($groupingForm->rec->{"filter{$i}"}) {
                    //
                    // Има зададен филтър - не правим групиране, а само филтриране на
                    // обектите за които зададения признак има зададената във филтъра стойност
                    //
                    $data->listFields["ent{$i}Id"] = $listRec->name;
                    $itemIds = $this->Lists->getItemsByGroup(
                        $listRec,
                        $groupingForm->rec->{"grouping{$i}"},
                        $groupingForm->rec->{"filter{$i}"}
                    );
                    array_unshift($itemIds, -1);
                    $data->query->where("#ent{$i}Id IN (" . implode(',', $itemIds) . ")");
                } else {
                    $this->FNC("grouping{$i}", 'varchar', 'caption=' . $listRec->name);
                    $data->listFields["grouping{$i}"] = $listRec->name;
                }
            } else {
                $data->listFields["ent{$i}Id"] = $listRec->name;
                
                if (!$flag) {
                    // Не можем да използваме следните редове повече от веднъж, това е проблем
                    $flag = TRUE;
                    $data->query->EXT("ent{$i}Num", 'acc_Items', "externalName=num,externalKey=ent{$i}Id");
                    $data->query->orderBy("#ent{$i}Num", 'ASC');
                }
            }
        }
        
        if ($bShowQuantities) {
            $data->listFields += array(
                'debitQuantity' => 'Дебит->к-во',
                'debitAmount' => 'Дебит->сума',
                'creditQuantity' => 'Кредит->к-во',
                'creditAmount' => 'Кредит->сума',
                'baseQuantity' => 'Начално салдо->к-во',
                'baseAmount' => 'Начално салдо->сума',
                'blQuantity' => 'Крайно салдо->к-во',
                'blAmount' => 'Крайно салдо->сума',
            );
        } else {
            $data->listFields += array(
                'debitAmount' => 'Обороти->Дебит',
                'creditAmount' => 'Обороти->Кредит',
                'baseAmount' => 'Салдо->начално',
                'blAmount' => 'Салдо->крайно',
            );
        }
    }
    
    
    /**
     * Лека промяна в детайл-layout-а: лентата с инструменти е над основната таблица, вместо под нея.
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterRenderDetailLayout($mvc, &$res, $data)
    {
        $res = new ET("
            [#ListTable#]
            [#ListSummary#]
            [#ListToolbar#]
        ");
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    static function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if ($mvc->isDetailed()) {
            if ($form = $mvc->getGroupingForm($data->masterId)) {
                $tpl->append($form->renderHtml() . '<br />');
            }
        }
    }
    
    
    /**
     * Създаване и подготовка на формата за групиране.
     *
     * @param int $balanceId ИД на баланса, в контекста на който се случва това
     */
    private function getGroupingForm($balanceId)
    {
        
        /**
         * Помощна променлива за кеширане на веднъж създадената форма
         */
        static $form;
        
        if (isset($form)) {
            return $form;
        }
        
        $form = FALSE;
        
        expect($this->Master->accountRec);
        
        $listRecs = array();
        $registers = array();
        
        foreach (range(1, 3) as $i) {
            if ($this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
                
                if (empty($registers[$i]->features)) {
                    unset($listRecs[$i], $registers[$i]);
                }
            }
        }
        
        if (empty($listRecs)) {
            // Нито един регистър не предлага признаци за групиране
            return $form;
        }
        
        $form = cls::get('core_Form');
        
        $form->method = 'GET';
        $form->title = 'Групиране & Филтриране';
        
        $form->FLD("accId", 'int', 'silent,input=hidden');
        $form->input("accId", true);
        
        foreach ($listRecs as $i=>$listRec) {
            $register = $registers[$i];
            
            expect(!empty($register->features));
            
            $grouping = Request::get("grouping{$i}");
            $filter = Request::get("filter{$i}");
            
            $enum = $backUrl = array();
            
            $isGrouped = !empty($grouping);
            $isFiltered = $isGrouped && !empty($filter);
            
            if ($isFiltered) {
                $enum[] = $grouping . '=' .
                $register->features[$grouping]->title;
                $backUrl += array(
                    "grouping{$i}" => $grouping,
                );
            } else {
                foreach ($register->features as $featureId=>$featureObj) {
                    $enum[] = $featureId . '=' . $featureObj->title;
                }
            }
            
            if ($isGrouped) {
                $backUrl += array(
                    "accId" => $form->rec->accId,
                );
            }
            
            if (!empty($enum)) {
                array_unshift($enum, '');
                $form->FLD("grouping{$i}", 'enum(' . implode(',', $enum) . ')',
                    "silent,caption={$listRec->name} по,width=300px"
                );
            }
            
            if ($isFiltered) {
                $form->FLD("filter{$i}", "enum(,{$filter}=" . $register->features[$grouping]->titleOf($filter) . ")", 'silent',
                    array('caption'=>$register->features[$grouping]->title));
            }
        }
        
        $form->input(null, true);
        
        $form->toolbar->addSbBtn('Обнови', '', '', "id=btnGroup,class=btn-group");
        $form->toolbar->addBtn('Назад', array(
                $this->Master,
                'single',
                $balanceId,
            ) + $backUrl,
            'id=btnBack');
        
        return $form;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($row->accountId && strlen($row->accountNum) >= 3) {
            $accRec = $mvc->Accounts->fetch($rec->accountId, 'groupId1,groupId2,groupId3');
            
            if ($accRec->groupId1 || $accRec->groupId2 || $accRec->groupId3) {
                $row->accountId = ht::createLink($row->accountId,
                    array($mvc->master, 'single', $rec->balanceId, 'accId'=>$rec->accountId));
            }
        }
        
        $row->ROW_ATTR['class'] .= ' level-' . strlen($rec->accountNum);
        
        if (!$mvc->isDetailed()) {
            return;
        }
        
        $groupingRec = $mvc->getGroupingForm($rec->balanceId)->rec;
        
        foreach (range(1, 3) as $i) {
            if (property_exists($row, "grouping{$i}")) {
                if (!empty($row->{"grouping{$i}"})) {
                    if ($this->Master->accountRec->{"groupId{$i}"}) {
                        $listRec = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
                        $featureId = $groupingRec->{"grouping{$i}"};
                        $featureObj = $register->features[$featureId];
                        $row->{"grouping{$i}"} =
                        ht::createLink(
                            $featureObj->titleOf($row->{"grouping{$i}"}),
                            array_merge(
                                getCurrentUrl(),
                                array("filter{$i}" => $rec->{"grouping{$i}"})
                            )
                        );
                    }
                } else {
                    $row->{"grouping{$i}"} = '<i>Други</i>';
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function isDetailed()
    {
        return !empty($this->Master->accountRec);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function calculateBalance($balanceRec)
    {
        // Изтриваме всички предишни записи, свързани с този баланс.
        // Някой би трябвало вече да се е погрижил до тук да се стига само ако е допустимо
        $this->delete("#balanceId = {$balanceRec->id}");
        
        $this->balance = array();
        $this->strategies = array();
        
        //
        // Ако има базов баланс, зареждаме го. 
        //
        if ($balanceRec->baseBalanceId) {
            $this->loadBalance($balanceRec->baseBalanceId);
        }
        
        //
        // Добавяме към баланса транзакциите от зададения период.
        //
        $this->calcBalanceForPeriod($balanceRec->fromDate, $balanceRec->toDate);
        
        //
        // Записваме готовия баланс
        //
        foreach ($this->balance as $accId => $l0) {
            foreach ($l0 as $ent1 => $l1) {
                foreach ($l1 as $ent2 => $l2) {
                    foreach ($l2 as $ent3 => $rec) {
                        $rec['balanceId'] = $balanceRec->id;
                        $this->save((object)$rec);
                    }
                }
            }
        }
        
        unset($this->balance, $this->strategies);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function loadBalance($balanceId)
    {
        $query = $this->getQuery();
        $query->where("#balanceId = {$balanceId}");
        $query->where('#blQuantity != 0 OR #blAmount != 0');
        
        while ($rec = $query->fetch()) {
            $accId = $rec->accountId;
            
            $ent1Id = !empty($rec->ent1Id) ? $rec->ent1Id : null;
            $ent2Id = !empty($rec->ent2Id) ? $rec->ent2Id : null;
            $ent3Id = !empty($rec->ent3Id) ? $rec->ent3Id : null;
            
            if ($strategy = $this->getStrategyFor($accId, $ent1Id, $ent2Id, $ent3Id)) {
                // "Захранваме" обекта стратегия с количество и сума
                $strategy->feed($rec->blQuantity, $rec->blAmount);
            }
            
            $b = $this->balance[$accId][$ent1Id][$ent2Id][$ent3];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            $b['baseQuantity'] += $rec->blQuantity;
            $b['baseAmount'] += $rec->blAmount;
            $b['blQuantity'] += $rec->blQuantity;
            $b['blAmount'] += $rec->blAmount;
        }
    }
    
    
    /**
     * Изчислява стойността на счетоводен баланс за зададен период от време.
     *
     * @param string $from дата в MySQL формат
     * @param string $to дата в MySQL формат
     */
    private function calcBalanceForPeriod($from, $to)
    {
        $JournalDetails = &cls::get('acc_JournalDetails');
        
        $query = $JournalDetails->getQuery();
        $query->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('state', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('jid', 'acc_Journal', 'externalName=id');
        $query->where("#state = 'active'");
        $query->where("#valior BETWEEN '{$from}' AND '{$to}'");
        
        while ($rec = $query->fetch()) {
            $this->calcAmount($rec);
            $this->addEntry($rec, 'debit');
            $this->addEntry($rec, 'credit');
        }
    }
    
    
    /**
     * Попълва с адекватна стойност с полето $rec->amount, в случай, че то е празно.
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     */
    private function calcAmount($rec)
    {
        $debitStrategy = $creditStrategy = NULL;
        
        // Намираме стратегиите на дебит и кредит с/ките (ако има)
        $debitStrategy = $this->getStrategyFor(
            $rec->debitAccId,
            $rec->debitEnt1,
            $rec->debitEnt2,
            $rec->debitEnt3
        );
        $creditStrategy = $this->getStrategyFor(
            $rec->creditAccId,
            $rec->creditEnt1,
            $rec->creditEnt2,
            $rec->creditEnt3
        );
        
        if ($creditStrategy) {
            // Кредитната сметка има стратегия.
            // Ако е активна, извличаме цена от стратегията
            // Ако е пасивна - "захранваме" стратегията с данни;
            // (точно обратното на дебитната сметка)
            switch ($this->Accounts->getType($rec->creditAccId)) {
                case 'active' :
                    if ($amount = $creditStrategy->consume($rec->creditQuantity)) {
                        $rec->amount = $amount;
                    }
                    break;
                case 'passive' :
                    $creditStrategy->feed($rec->creditQuantity, $rec->amount);
                    break;
            }
        }
        
        if ($debitStrategy) {
            // Дебитната сметка има стратегия.
            // Ако е активна, "захранваме" стратегията с данни;
            // Ако е пасивна - извличаме цена от стратегията
            switch ($this->Accounts->getType($rec->debitAccId)) {
                case 'active' :
                    $debitStrategy->feed($rec->debitQuantity, $rec->amount);
                    break;
                case 'passive' :
                    if ($amount = $debitStrategy->consume($rec->debitQuantity)) {
                        $rec->amount = $amount;
                    }
                    break;
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function &getStrategyFor($accountId, $ent1Id, $ent2Id, $ent3Id)
    {
        $e1 = !empty($ent1Id) ? $ent1Id : null;
        $e2 = !empty($ent2Id) ? $ent2Id : null;
        $e3 = !empty($ent3Id) ? $ent3Id : null;
        
        $strategy = NULL;
        
        if (isset($this->strategies[$accountId][$e1][$e2][$e3])) {
            // Имаме вече създаден обект-стратегия
            $strategy = $this->strategies[$accountId][$e1][$e2][$e3];
        } elseif (isset($this->strategies[$accountId]) &&
            $this->strategies[$accountId] === false) {
            // Тази сметка вече е била "питана" за стратегия (дебитна или кредитна) и
            // резултатът е бил отрицателен. За това си спестяваме ново питане - гарантирано е, 
            // че отговорът отново ще бъде същият.
            $strategy = FALSE;
        } elseif ($strategy = $this->Accounts->createStrategyObject($accountId)) {
            // Има стратегия - записваме инстанцията й.
            $this->strategies[$accountId][$e1][$e2][$e3] = &$strategy;
        } else {
            // Няма стратегия. И това не зависи от перата. За да спестим бъдещи извиквания,
            // записваме false
            $this->strategies[$accountId] = FALSE;
        }
        
        return $strategy;
    }
    
    
    /**
     * Добавя дебитната или кредитната част на ред от транзакция (@see acc_JournalDetails)
     * в баланса
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     * @param string $type 'debit' или 'credit'
     */
    private function addEntry($rec, $type)
    {
        expect(in_array($type, array('debit', 'credit')));
        
        expect($rec->amount, $rec);
        
        $sign = ($type == 'debit') ? 1 : -1;
        
        $accId = $rec->{"{$type}AccId"};
        
        $ent1Id = !empty($rec->{"{$type}Ent1"}) ? $rec->{"{$type}Ent1"} : null;
        $ent2Id = !empty($rec->{"{$type}Ent2"}) ? $rec->{"{$type}Ent2"} : null;
        $ent3Id = !empty($rec->{"{$type}Ent3"}) ? $rec->{"{$type}Ent3"} : null;
        
        if ($ent1Id != null || $ent2Id != null || $ent3Id != null) {
            
            $b = $this->balance[$accId][$ent1Id][$ent2Id][$ent3Id];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            
            $this->inc($b["{$type}Quantity"], $rec->quantity);
            $this->inc($b["{$type}Amount"], $rec->amount);
            $this->inc($b['blQuantity'], $rec->quantity * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
        }
        
        for ($accNum = $this->Accounts->getNumById($accId); !empty($accNum); $accNum = substr($accNum, 0, -1)) {
            if (!($accId = $this->Accounts->getIdByNum($accNum))) {
                continue;
            }
            
            $b = $this->balance[$accId][null][null][null];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = NULL;
            $b['ent2Id'] = NULL;
            $b['ent3Id'] = NULL;
            
            $this->inc($b["{$type}Quantity"], $rec->quantity);
            $this->inc($b["{$type}Amount"], $rec->amount);
            $this->inc($b['blQuantity'], $rec->quantity * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
        }
    }
    
    
    /**
     * Ако вторият аргумент е с празна (empty()) стойност - не прави нищо. В противен случай
     * увеличава стойността на първия аргумент със стойността на втория.
     *
     * Ако $v = '', $add = '', то след $v += $add, $v ще има стойност нула. Целта на този метод
     * е стойността на $v да остане непроменена (празна) в такива случаи.
     *
     * @param number $v
     * @param mixed $add
     */
    private function inc(&$v, $add)
    {
        if (!empty($add)) {
            $v += $add;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява ръчното манипулиране на записи
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if (!in_array($action, array('list', 'read'))) {
            $requiredRoles = 'no_one';
        }
    }
}