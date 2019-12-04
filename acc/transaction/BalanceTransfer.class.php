<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_BalanceTransfers
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
class acc_transaction_BalanceTransfer extends acc_DocumentTransactionSource
{
    /**
     *
     * @var acc_BalanceTransfers
     */
    public $class;
    
    
    /**
     * @var array
     */
    private $Balance = array();
    
    
    /**
     *
     * @var float
     */
    public $total = 0;
    
    
    /**
     * @param int $id
     *
     * @return stdClass
     *
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
        set_time_limit(600);
        
        $rec = $this->class->fetchRec($id);
        
        $result = (object) array(
            'reason' => $this->class->getRecTitle($id),
            'valior' => $rec->valior,
            'totalAmount' => null,
            'entries' => array()
        );
        
        $to = $rec->valior;
        $accs = acc_Accounts::fetchField($rec->fromAccount, 'systemId');
        
        // Подготвяме активен баланс за посочените сметки
        $Balance = new acc_ActiveShortBalance(array('from' => $to,
            'to' => $to,
            'accs' => $accs,
            'item1' => $rec->fromEnt1Id,
            'item2' => $rec->fromEnt2Id,
            'item3' => $rec->fromEnt3Id,
            'strict' => true,
            'cacheBalance' => false));
        
        $this->Balance = $Balance->getBalanceBefore($accs);
        
        $result->entries = $this->getEntries($rec);
        $result->totalAmount = $this->total;
        
        return $result;
    }
    
    
    /**
     * Подготвя записите на транзакцията
     *
     * @param stdClass $rec - запис на документ
     *
     * @return array $entries - генерираните записи
     */
    private function getEntries($rec)
    {
        $entries = array();
        
        // Намираме систем ид-та на избраните сметки
        $fromSysId = acc_Accounts::fetchField($rec->fromAccount, 'systemId');
        $toSysId = acc_Accounts::fetchField($rec->toAccount, 'systemId');
        
        $toAccountRec = acc_Accounts::fetch($rec->toAccount);
        
        // Ако има намерен баланс
        if (count($this->Balance)) {
            
            // За всеки запис
            foreach ($this->Balance as $b) {
                $entry = array();
                $entry['amount'] = abs($b['blAmount']);
                $quantity = abs($b['blQuantity']);
                
                // Ако не е задено дестинационно перо, приемаме, че е същото
                // ent1Id, ent2Id и ent3Id са същите като избраните пера за прехвърляне
                $toEnt1Id = ($rec->toEnt1Id) ? $rec->toEnt1Id : $b['ent1Id'];
                $toEnt2Id = ($rec->toEnt2Id) ? $rec->toEnt2Id : $b['ent2Id'];
                $toEnt3Id = ($rec->toEnt3Id) ? $rec->toEnt3Id : $b['ent3Id'];
                
                if ($b['accountId'] != $rec->fromAccount) {
                    continue;
                }
                
                // Аналитичноста от която ще прехвърляме
                $fromArr = array($fromSysId, $b['ent1Id'], $b['ent2Id'], $b['ent3Id'], 'quantity' => $quantity);
                
                // Аналитичността в която ще прехвърлим
                $toArr = array($toSysId, $toEnt1Id, $toEnt2Id, $toEnt3Id, 'quantity' => $quantity);
                
                
                if ($b['blAmount'] >= 0) {
                    $entry['debit'] = $toArr;
                    $entry['credit'] = $fromArr;
                } else {
                    $entry['debit'] = $fromArr;
                    $entry['credit'] = $toArr;
                }
                
                $this->total += $entry['amount'];
                $entries[] = $entry;
            }
        }
        
        return $entries;
    }
}
