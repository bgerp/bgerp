<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_Articles
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_Article extends acc_DocumentTransactionSource
{
    /**
     * @param int $id
     *
     * @return stdClass
     *
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
        // Извличане на мастър-записа
        expect($rec = $this->class->fetchRec($id));
        
        $result = (object) array(
            'reason' => $rec->reason,
            'valior' => $rec->valior,
            'totalAmount' => 0,
            'entries' => array()
        );
        
        $totalAmount = 0;
        
        if (!empty($rec->id)) {
            // Извличаме детайл-записите на документа. В случая просто копираме полетата, тъй-като
            // детайл-записите на мемориалните ордери имат същата структура, каквато е и на
            // детайлите на журнала.
            $query = acc_ArticleDetails::getQuery();
            $query->where("#articleId = {$rec->id}");
            $query->orderBy('id', 'ASC');
            
            while ($entry = $query->fetch()) {
                $debitRec = acc_Accounts::fetch($entry->debitAccId);
                $creditRec = acc_Accounts::fetch($entry->creditAccId);
                
                $result->entries[] = array(
                    'amount' => round($entry->amount, 2),
                    
                    'debit' => array(
                        $debitRec->systemId,
                        $entry->debitEnt1, // Перо 1
                        $entry->debitEnt2, // Перо 2
                        $entry->debitEnt3, // Перо 3
                        'quantity' => $entry->debitQuantity,
                    ),
                    
                    'credit' => array(
                        $creditRec->systemId,
                        $entry->creditEnt1, // Перо 1
                        $entry->creditEnt2, // Перо 2
                        $entry->creditEnt3, // Перо 3
                        'quantity' => $entry->creditQuantity,
                    ),
                );
                
                if (!empty($entry->reason)) {
                    $result->entries[count($result->entries) - 1]['reason'] = $entry->reason;
                }
                
                // Проверка дали трябва да се сума на движението
                $quantityOnly = ($debitRec->type == 'passive' && $debitRec->strategy) ||
                ($creditRec->type == 'active' && $creditRec->strategy);
                
                // Ако трябва да е само количество, премахваме нулевата сума
                if ($quantityOnly) {
                    unset($result->entries[count($result->entries) - 1]['amount']);
                }
                
                //Добавяме сумата (ако я има) към общото
                if (isset($result->entries[count($result->entries) - 1]['amount'])) {
                    $totalAmount += $result->entries[count($result->entries) - 1]['amount'];
                }
            }
        }
        
        $result->totalAmount = $totalAmount;
        
        return $result;
    }
}
