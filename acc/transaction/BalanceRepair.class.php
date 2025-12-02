<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_BalanceRepairs
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_BalanceRepair extends acc_DocumentTransactionSource
{
    /**
     * Запис на баланса
     */
    private $balanceRec;
    
    
    /**
     * Дата
     */
    private $date;
    
    
    /**
     * Сума
     */
    private $amount488 = 0;
    
    
    /**
     * @param int $id
     *
     * @return stdClass
     *
     * @see acc_TransactionSourceIntf::getTransaction
     * @throws core_exception_Expect
     */
    public function getTransaction($id)
    {
        // Извличане на мастър-записа
        expect($rec = $this->class->fetchRec($id));
        
        $this->balanceRec = acc_Balances::fetch($rec->balanceId);
        $pRec = acc_Periods::fetch($this->balanceRec->periodId);
        $this->date = acc_Periods::forceYearItem($rec->valior);
        
        $result = (object) array(
            'reason' => "Счетоводна разлика №{$rec->id}",
            'valior' => $pRec->end,
            'totalAmount' => null,
            'entries' => array()
        );
        
        // Ако има ид
        if ($rec->id) {
            
            // За всяка сметка в детайла
            $dQuery = acc_BalanceRepairDetails::getQuery();
            $dQuery->where("#repairId = {$rec->id}");
            while ($dRec = $dQuery->fetch()) {
                
                // Взимаме и записите
                $entries = $this->getEntries($dRec, $result->totalAmount, $pRec);
                if (countR($entries)) {
                    
                    // Обединяваме тези записи с общите
                    $result->entries = array_merge($result->entries, $entries);
                }
            }
        }
        
        return $result;
    }


    /**
     * Връща ентритата
     *
     * @param $dRec
     * @param $total
     * @param $periodRec
     * @return array $entries
     */
    private function getEntries($dRec, &$total, $periodRec)
    {
        $entries = array();
        $accRec = acc_Accounts::fetch($dRec->accountId);
        $bQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($bQuery, $this->balanceRec->id, $accRec->systemId);

        // Ако сметката има аналитичности, то няма да се поправя обобщаващия ред
        if(!empty($accRec->groupId1) || !empty($accRec->groupId2) || !empty($accRec->groupId3)){
            $bQuery->where('#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL');
        }
        
        $Items = cls::get('acc_Items');
        $itemsArr = $Items->getCachedItems();
        $accInfo = acc_Accounts::getAccountInfo($dRec->accountId);
        $isDimensional = $accInfo->isDimensional;

        // За всеки запис
        while ($bRec = $bQuery->fetch()) {
            $blAmount = $blQuantity = null;

            $hasQty       = false;   // условието за количество е удовлетворено
            $hasAmountQty = false;   // условието за сума е удовлетворено

            /* --------- Количество --------- */
            if ($isDimensional) {
                if (!empty($dRec->blQuantity)) {
                    // режим "праг" по количество
                    $diff = $bRec->blQuantity;

                    // ако е в прага -> условието за количество е изпълнено
                    if ($diff >= -1 * $dRec->blQuantity && $diff <= $dRec->blQuantity) {
                        if ($diff != 0) {
                            // само ако има реална разлика, записваме корекция
                            $blQuantity = $diff;
                        }
                        $hasQty = true;      // 0 също е "ОК" и не блокира
                    }

                } elseif (!empty($dRec->blRoundQuantity)) {
                    // режим "закръгляне" по количество
                    $diff = round(round($bRec->blQuantity, (int)$dRec->blRoundQuantity) - $bRec->blQuantity, 10);

                    if ($diff) {
                        $blQuantity = $diff;
                        $hasQty = true;
                    } else {
                        // има настройка, но няма разлика -> количеството не е за корекция
                        // но е "ОК" за AND проверката:
                        $hasQty = true;
                    }
                } else {
                    // няма никаква настройка за количество → не участва в условието (считаме го за "ОК")
                    $hasQty = true;
                }
            } else {
                // недименсионна сметка – количеството не участва в логиката, считаме го за "ОК"
                $hasQty = true;
            }


            /* --------- Сума --------- */
            if (!empty($dRec->blAmount)) {
                // режим "праг" по сума
                $diff = $bRec->blAmount;

                // ако е в прага → условието за сума е изпълнено (вкл. 0)
                if ($diff >= -1 * $dRec->blAmount && $diff <= $dRec->blAmount) {
                    if ($diff != 0) {
                        // само ако има реална разлика, записваме корекция
                        $blAmount = $diff;
                    }
                    $hasAmountQty = true;
                }

            } elseif (!empty($dRec->blRoundAmount)) {
                // режим "закръгляне" по сума
                $diff = round(round($bRec->blAmount, (int)$dRec->blRoundAmount) - $bRec->blAmount, 10);

                // тук diff е "разликата от закръглянето"
                if ($diff) {
                    $blAmount = $diff;
                }
                // дори да е 0, сума не трябва да блокира AND-а – просто няма корекция
                $hasAmountQty = true;

            } else {
                // няма никаква настройка за сума → не участва в условието (считаме го за "ОК")
                $hasAmountQty = true;
            }

            /* --------- Общото условие --------- */

            // 1) Искаме **И** количеството, И сумата да са "ОК" според настройките
            if (!($hasAmountQty && $hasQty)) {
                continue;
            }

            // 2) Ако и по баланс количество, и по баланс сума са 0 – няма какво да поправяме
            if (round($bRec->blQuantity, 10) == 0 && round($bRec->blAmount, 10) == 0) {
                continue;
            }

            // 3) Допълнително – ако не сме натрупали никаква корекция по нито едно от двете
            if (is_null($blQuantity) && is_null($blAmount)) {
                continue;
            }

            // Ако има поне едно перо
            if (!empty($bRec->ent1Id) || !empty($bRec->ent2Id) || !empty($bRec->ent3Id)) {
                if($dRec->repairAll != 'yes'){

                    // Проверяваме всички пера
                    $continue = true;

                    foreach (array('ent1Id', 'ent2Id', 'ent3Id') as $ent) {
                        if (!empty($bRec->{$ent})) {

                            // Ако има поне едно затворено, и то е затворено преди края на периода
                            if ($itemsArr['items'][$bRec->{$ent}]->state == 'closed') {
                                $jQuery = acc_JournalDetails::getQuery();
                                acc_JournalDetails::filterQuery($jQuery, null, dt::now(), $bRec->accountNum, $bRec->{$ent});
                                $jQuery->XPR('maxValior', 'date', 'MAX(#valior)');
                                $jQuery->limit(1);
                                $jQuery->show('maxValior');
                                $maxValior = $jQuery->fetch()->maxValior;

                                if($maxValior <= $periodRec->end){
                                    $continue = false;
                                    break;
                                }
                            }
                        }
                    }

                    // Ако всички пера са отворени продължаваме без да правим нищо
                    if ($continue) {
                        continue;
                    }
                }
            }

            $ourSideArr = array($accRec->systemId, $bRec->ent1Id, $bRec->ent2Id, $bRec->ent3Id);

            $entry = array('amount' => abs($blAmount));
            $total += abs($blAmount);
            
            if (!is_null($blQuantity)) {
                $ourSideArr['quantity'] = abs($blQuantity);
            } else {
                $ourSideArr['quantity'] = 0;
            }
            
            // Ако салдото е отрицателно отива като приход
            if ($blAmount < 0) {
                $entry['debit'] = $ourSideArr;
                $entry['credit'] = array('488');
                
                $this->amount488 -= $entry['amount'];
            } elseif ($blAmount > 0) {
                // Ако салдото е положително отива като разход
                $entry['debit'] = array('488');
                $entry['credit'] = $ourSideArr;
                
                $this->amount488 += $entry['amount'];
            } else {
                if ($blQuantity < 0) {
                    $entry['debit'] = $ourSideArr;
                    $entry['credit'] = array('488');
                    
                    $this->amount488 -= $entry['amount'];
                } else {
                    // Ако салдото е положително отива като разход
                    $entry['debit'] = array('488');
                    $entry['credit'] = $ourSideArr;
                    
                    $this->amount488 += $entry['amount'];
                }
            }
            
            $entry['reason'] = 'Разлики от закръгляния';
            $entries[] = $entry;
        }

        // Връщаме ентритата
        return $entries;
    }
}
