<?php


/**
 * Помощен модел за лесна работа с баланс, в който участват само определени пера и сметки
 *
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
class acc_ActiveShortBalance
{
    /**
     * Променлива в която ще се помни баланса
     */
    private $balance = array();
    
    
    /**
     * Извлечените записи
     */
    private $params = array();
    
    
    /**
     * Извлечените записи
     */
    private $recs;
    
    
    /**
     * От дата
     */
    private $from;
    
    
    /**
     * До дата
     */
    private $to;
    
    
    /**
     * @var acc_Balances
     */
    private $acc_Balances;
    
    
    /**
     * Конструктор на обекта
     *
     * Масив $params с атрибути
     * ['itemsAll']     - списък от ид-та на пера, които може да са на всяка позиция
     * ['accs']         - списък от систем ид-та на сметки
     * ['item1']        - списък от ид-та на пера, поне едно от които може да е на първа позиция
     * ['item2']        - списък от ид-та на пера, поне едно от които може да е на втора позиция
     * ['item3']        - списък от ид-та на пера, поне едно от които може да е на трета позиция
     * ['from']         - От дата
     * ['to']           - До дата
     * ['cacheBalance'] - Да кеширали в обекта изчисления баланс
     */
    public function __construct($params = array())
    {
        $this->from = $params['from'];
        $this->to = $params['to'];
        $strict = (isset($params['strict']) ? true : false);
        $this->params = $params;
        
        core_App::setTimeLimit(600);
        
        // Изчисления баланс се кешира, само ако е указано
        if ($params['cacheBalance'] !== false) {
            
            // Подготвяме заявката към базата данни
            $jQuery = acc_JournalDetails::getQuery();
            acc_JournalDetails::filterQuery($jQuery, $params['from'], $params['to'], $params['accs'], $params['itemsAll'], $params['item1'], $params['item2'], $params['item3'], $strict);
            
            // Изчисляваме мини баланса
            $this->recs = $jQuery->fetchAll();
            
            // Изчисляваме и кешираме баланса
            $this->calcBalance($this->recs, $this->balance);
        }
        
        $this->acc_Balances = cls::get('acc_Balances');
    }
    
    
    /**
     * Изчислява мини баланса
     */
    private function calcBalance($recs, &$balance = array())
    {
        if (countR($recs)) {
            $sysIds = array();
            
            // За всеки запис
            foreach ($recs as $rec) {
                
                // За дебита и кредита
                foreach (array('debit', 'credit') as $type) {
                    $accId = $rec->{"{$type}AccId"};
                    $item1 = $rec->{"{$type}Item1"};
                    $item2 = $rec->{"{$type}Item2"};
                    $item3 = $rec->{"{$type}Item3"};
                    
                    if (is_array($this->params) && $this->params['keepUnique'] !== true) {
                        if (empty($this->params['item1'])) {
                            $item1 = '';
                        }
                        if (empty($this->params['item2'])) {
                            $item2 = '';
                        }
                        if (empty($this->params['item3'])) {
                            $item3 = '';
                        }
                    }
                    
                    // За всяка уникална комбинация от сметка и пера, сумираме количествата и сумите
                    $sign = ($type == 'debit') ? 1 : -1;
                    $index = $accId . '|' . $item1 . '|' . $item2 . '|' . $item3;
                    
                    $b = &$balance[$index];
                    
                    if (!isset($sysIds[$accId])) {
                        $sysIds[$accId] = acc_Accounts::fetchField($accId, 'systemId');
                    }
                    
                    $b['accountId'] = $accId;
                    $b['accountSysId'] = $sysIds[$accId];
                    $b['ent1Id'] = $item1;
                    $b['ent2Id'] = $item2;
                    $b['ent3Id'] = $item3;
                    
                    $b["{$type}Quantity"] += $rec->{"{$type}Quantity"};
                    $b["{$type}Amount"] += $rec->amount;
                    $b['blQuantity'] += round($rec->{"{$type}Quantity"} * $sign, 6);
                    $b['blAmount'] += round($rec->amount * $sign, 6);
                }
            }
        }
    }
    
    
    /**
     * Връща крайното салдо на няколко сметки
     *
     * @param mixed    $accs   - масив от систем ид-та на сметка
     * @param bool|int $itemId - дали да се филтрира само за посоченото перо
     *
     * @return float $res    - крайното салдо
     */
    public function getAmount($accs, $itemId = false)
    {
        $arr = arr::make($accs);
        
        expect(countR($arr));
        
        $res = 0;
        
        foreach ($arr as $accSysId) {
            foreach ($this->balance as $index => $b) {
                
                // Ако филтрираме и по перо, пропускаме тези записи, в които то не участва
                if ($itemId) {
                    $indexArr = explode('|', $index);
                    
                    if (!in_array($itemId, $indexArr)) {
                        continue;
                    }
                }
                
                if ($b['accountSysId'] == $accSysId) {
                    $res += $b['blAmount'];
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Изчислява баланса преди зададените дати в '$this->from' и '$this->to'
     */
    public function getBalanceBefore($accs, &$accArr = null)
    {
        $newBalance = array();
        $accInfos = array();
        
        // Намираме последния изчислен баланс преди началната дата
        $balanceRec = $this->acc_Balances->getBalanceBefore($this->from);
        
        // Обръщаме сис ид-та на сметките в техните ид-та
        $accArr = arr::make($accs);
        
        if (countR($accArr)) {
            foreach ($accArr as &$acc) {
                $acc = acc_Accounts::fetchField("#systemId = {$acc}");
            }
        }
        
        $newFrom = null;
        
        // Ако има такъв баланс
        if ($balanceRec) {
            
            // Извличаме неговите записи
            $bQuery = acc_BalanceDetails::getQuery();
            $bQuery->show('accountId,ent1Id,ent2Id,ent3Id,blAmount,blQuantity');
            acc_BalanceDetails::filterQuery($bQuery, $balanceRec->id, $accs, $this->params['itemsAll'], $this->params['item1'], $this->params['item2'], $this->params['item3']);
            $bQuery->where('#blQuantity != 0 OR #blAmount != 0');
            
            while ($bRec = $bQuery->fetch()) {
                if (!isset($accInfos[$bRec->accountId])) {
                    $accInfos[$bRec->accountId] = acc_Accounts::getAccountInfo($bRec->accountId);
                }
                
                if (countR($accInfos[$bRec->accountId]->groups)) {
                    
                    // Ако е за синтетична сметка, пропускаме го
                    if (empty($bRec->ent1Id) && empty($bRec->ent2Id) && empty($bRec->ent3Id)) {
                        continue;
                    }
                }
                
                // Ако има подадени сметки и сметката на записа не е в масива пропускаме
                if (countR($accArr) && !in_array($bRec->accountId, $accArr)) {
                    continue;
                }
                
                if (is_array($this->params) && $this->params['keepUnique'] !== true) {
                    if (empty($this->params['item1'])) {
                        $bRec->ent1Id = '';
                    }
                    if (empty($this->params['item2'])) {
                        $bRec->ent2Id = '';
                    }
                    if (empty($this->params['item3'])) {
                        $bRec->ent3Id = '';
                    }
                }
                
                // Натруваме в $newBalance
                $index = $bRec->accountId . '|' . $bRec->ent1Id . '|' . $bRec->ent2Id . '|' . $bRec->ent3Id;
                $bRec = (array) $bRec;
                
                if (!array_key_exists($index, $newBalance)) {
                    $newBalance[$index] = $bRec;
                } else {
                    $newBalance[$index]['blAmount'] += $bRec['blAmount'];
                    $newBalance[$index]['blQuantity'] += $bRec['blQuantity'];
                }
            }
            
            $newFrom = dt::addDays(1, $balanceRec->toDate, false);
        }
        
        $newTo = dt::addDays(-1, $this->from, false);
        
        // Извличаме всички записи които са между последния баланс и избраната дата за начало на търсенето
        $jQuery = acc_JournalDetails::getQuery();
        acc_JournalDetails::filterQuery($jQuery, $newFrom, $newTo, $accs, $this->params['itemsAll'], $this->params['item1'], $this->params['item2'], $this->params['item3'], false);
        
        // Натрупваме им сумите към началния баланс
        $this->calcBalance($jQuery->fetchAll(), $newBalance);
        
        // Изчислените крайни салда стават начални салда на показвания баланс
        if (countR($newBalance)) {
            foreach ($newBalance as $index => &$r) {
                $r['baseAmount'] = $r['blAmount'];
                $r['baseQuantity'] = $r['blQuantity'];
                unset($r['debitAmount'], $r['creditAmount'], $r['debitQuantity'], $r['creditQuantity']);
            }
        }
        
        return $newBalance;
    }
    
    
    /**
     * Връща изчислен баланс за няколко сметки
     * взима началните салда от последния изчислен баланс и към тях натрупва записите от журнала
     * които не са влезли в баланса
     */
    public function getBalance($accs)
    {
        $accArr = array();
        $newBalance = $this->getBalanceBefore($accs, $accArr);
        
        // Извличаме записите, направени в избрания период на търсене
        $jQuery = acc_JournalDetails::getQuery();
        acc_JournalDetails::filterQuery($jQuery, $this->from, $this->to, $accs, $this->params['itemsAll'], $this->params['item1'], $this->params['item2'], $this->params['item3']);
        $all = $jQuery->fetchAll();
        $this->calcBalance($all, $newBalance);
        
        // Оставяме само тези, които са на избраната сметка
        if (countR($newBalance)) {
            foreach ($newBalance as $index => &$r) {
                $r = (object) $r;
                
                if (countR($accArr) && !in_array($r->accountId, $accArr)) {
                    unset($newBalance[$index]);
                }
            }
        }
        
        return $newBalance;
    }
    
    
    /**
     * Връща хронологията на движенията на посочената сметка
     *
     * @param string   $accSysId        - Ид на сметка
     * @param datetime $from            - от дата
     * @param datetime $to              - до дата
     * @param int      $item1           - перо 1 / NULL ако няма
     * @param int      $item2           - перо 2 / NULL ако няма
     * @param int      $item3           - перо 3 / NULL ако няма
     * @param bool     $groupByDocument - дали резултатите да са групирани по документ
     *
     * @return mixed $res
     *               [history] - Масив с редовете на хронологията
     *               [valior]         - вальор на документа
     *               [docType]        - ид на типа на документа
     *               [docId]          - ид на документа
     *               [ent1Id]         - перо 1
     *               [ent2Id]         - перо 2
     *               [ent3Id]         - перо 3
     *               [baseQuantity]   - начално к-во
     *               [baseAmount]     - начална сума
     *               [debitQuantity]  - дебит к-во
     *               [debitAmount]    - дебит сума
     *               [creditQuantity] - кредит к-во
     *               [creditAmount]   - кредит сума
     *               [blQuantity]     - крайно к-во
     *               [blAmount]       - крайна сума
     *
     *		   [summary] - обобщената информация за всички движенията
     *				[baseQuantity]   - начално к-во
     * 				[baseAmount]     - начална сума
     * 				[debitQuantity]  - дебит к-во
     * 				[debitAmount]    - дебит сума
     * 				[creditQuantity] - кредит к-во
     * 				[creditAmount]   - кредит сума
     * 				[blQuantity]     - крайно к-во
     * 				[blAmount]       - крайна сума
     */
    public static function getBalanceHystory($accSysId, $from = null, $to = null, $item1 = null, $item2 = null, $item3 = null, $groupByDocument = true, $strict = true)
    {
        $accId = acc_Accounts::getRecBySystemId($accSysId)->id;
        
        // Изчисляваме крайното салдо за аналитичната сметка в периода преди избраните дати
        $Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => $accSysId, 'item1' => $item1, 'item2' => $item2, 'item3' => $item3, 'strict' => $strict, 'cacheBalance' => false));
        $calcedBalance = $Balance->getBalanceBefore($accSysId);
        
        $indexArr = $accId . '|' . $item1 . '|' . $item2 . '|' . $item3;
        
        // Ако няма данни досега, започваме с нулеви крайни салда
        if (!isset($calcedBalance[$indexArr])) {
            $calcedBalance[$indexArr] = array('blAmount' => 0, 'blQuantity' => 0);
        }
        
        // Извличаме записите точно в периода на филтъра
        $jQuery = acc_JournalDetails::getQuery();
        acc_JournalDetails::filterQuery($jQuery, $from, $to, $accSysId, null, $item1, $item2, $item3, $strict);
        $jQuery->orderBy('valior', 'ASC');
        $jQuery->orderBy('id', 'ASC');
        
        $entriesInPeriod = $jQuery->fetchAll();
        
        $history = array();
        
        // Обхождаме всички записи и натрупваме сумите им към крайното салдо
        if (countR($entriesInPeriod)) {
            foreach ($entriesInPeriod as $jRec) {
                $entry = array('id' => $jRec->id,
                    'docType' => $jRec->docType,
                    'docId' => $jRec->docId,
                    'reason' => $jRec->reason,
                    'valior' => $jRec->valior,
                    'reasonCode' => $jRec->reasonCode);
                
                $add = false;
                
                foreach (array('debit', 'credit') as $type) {
                    $sign = ($type == 'debit') ? 1 : -1;
                    $quantityField = "{$type}Quantity";
                    $accId = $jRec->{"{$type}AccId"};
                    
                    $ent1Id = !empty($jRec->{"{$type}Item1"}) ? $jRec->{"{$type}Item1"} : null;
                    $ent2Id = !empty($jRec->{"{$type}Item2"}) ? $jRec->{"{$type}Item2"} : null;
                    $ent3Id = !empty($jRec->{"{$type}Item3"}) ? $jRec->{"{$type}Item3"} : null;
                    
                    if (empty($item1)) {
                        $ent1Id = '';
                    }
                    if (empty($item2)) {
                        $ent2Id = '';
                    }
                    if (empty($item3)) {
                        $ent3Id = '';
                    }
                    
                    $index = "{$accId}|{$ent1Id}|{$ent2Id}|{$ent3Id}";
                    
                    if ($indexArr != $index) {
                        continue;
                    }
                    
                    // Оставяме само записите за тази аналитична сметка
                    if (isset($calcedBalance[$index])) {
                        if ($groupByDocument !== true) {
                            $entry['baseQuantity'] = $calcedBalance[$index]['blQuantity'];
                            $entry['baseAmount'] = $calcedBalance[$index]['blAmount'];
                        }
                        
                        if (!is_null($jRec->{$quantityField})) {
                            $add = true;
                            $entry[$quantityField] = $jRec->{$quantityField};
                            ${"{$type}Quantity"} += $entry[$quantityField];
                            
                            if ($groupByDocument !== true) {
                                $calcedBalance[$index]['blQuantity'] += $jRec->{$quantityField} * $sign;
                                $entry['blQuantity'] = $calcedBalance[$index]['blQuantity'];
                            }
                        }
                        
                        if (!is_null($jRec->amount)) {
                            $add = true;
                            $entry["{$type}Amount"] = $jRec->amount;
                            ${"{$type}Amount"} += $entry["{$type}Amount"];
                            
                            if ($groupByDocument !== true) {
                                $calcedBalance[$index]['blAmount'] += $jRec->amount * $sign;
                                $entry['blAmount'] = $calcedBalance[$index]['blAmount'];
                            }
                        }
                    }
                }
                
                if ($add) {
                    $history[$jRec->id] = $entry;
                }
            }
            
            // Правим групиране на записите
            if (countR($history) && $groupByDocument === true) {
                $groupedRecs = array();
                
                // Групираме всички записи от журнала по документи
                foreach ($history as $dRec) {
                    $index = $dRec['docType'] . '|' . $dRec['docId'] . '|' . $dRec['reasonCode'];
                    
                    if (!isset($groupedRecs[$index])) {
                        $groupedRecs[$index] = $dRec;
                    } else {
                        foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount') as $key) {
                            if (!empty($dRec[$key])) {
                                $groupedRecs[$index][$key] += $dRec[$key];
                            }
                        }
                    }
                }
                
                // За всеки от групираните записи, изчисляваме му крайното салдо
                foreach ($groupedRecs as &$dRec2) {
                    $dRec2['baseQuantity'] = $calcedBalance[$indexArr]['blQuantity'];
                    $dRec2['baseAmount'] = $calcedBalance[$indexArr]['blAmount'];
                    
                    
                    $blAmount = $dRec2['debitAmount'] - $dRec2['creditAmount'];
                    $blQuantity = $dRec2['debitQuantity'] - $dRec2['creditQuantity'];
                    
                    $calcedBalance[$indexArr]['blAmount'] += $blAmount;
                    $calcedBalance[$indexArr]['blQuantity'] += $blQuantity;
                    
                    $dRec2['blAmount'] = $calcedBalance[$indexArr]['blAmount'];
                    $dRec2['blQuantity'] = $calcedBalance[$indexArr]['blQuantity'];
                }
                
                $history = $groupedRecs;
            }
        }
        
        $debitQuantity = $debitAmount = $creditQuantity = $creditAmount = 0;
        if (countR($history)) {
            foreach ($history as $arr) {
                foreach (array('debitQuantity', 'debitAmount', 'creditAmount', 'creditQuantity') as $fld) {
                    if (isset($arr[$fld])) {
                        ${$fld} += $arr[$fld];
                    }
                }
            }
        }
        
        $lastArr = end($history);
        $blQuantity = (countR($history)) ? $lastArr['blQuantity'] : $calcedBalance[$indexArr]['blQuantity'];
        $blAmount = (countR($history)) ? $lastArr['blAmount'] : $calcedBalance[$indexArr]['blAmount'];
        
        $summary = array('baseQuantity' => $calcedBalance[$indexArr]['baseQuantity'],
            'baseAmount' => $calcedBalance[$indexArr]['baseAmount'],
            'creditQuantity' => $creditQuantity,
            'creditAmount' => $creditAmount,
            'debitQuantity' => $debitQuantity,
            'debitAmount' => $debitAmount,
            'blQuantity' => $blQuantity,
            'blAmount' => $blAmount);
        
        return array('history' => array_values($history), 'summary' => $summary);
    }
}
