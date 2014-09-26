<?php

/**
 * Клас моделиращ ред от счетоводна транзакция
 * 
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class acc_journal_Entry
{
    /**
     * Дебитна част на ред от счетоводна транзакция
     * 
     * @var acc_journal_EntrySide
     */
    public $debit;
    
    
    /**
     * Кредитна част на ред от счетоводна транзакция
     *
     * @var acc_journal_EntrySide
     */
    public $credit;
    
    
    /**
     * Стойност на реда в основна валута
     * 
     * @var float
     */
    public $amount;

    
    /**
     * @var acc_JournalDetails
     */
    public $JournalDetails;
    
    
    /**
     * Конструктор
     * 
     * @param object|array $debitData дебитната част на реда
     * @param object|array $creditData кредитната част на реда
     */
    public function __construct($debitData = NULL, $creditData = NULL)
    {
        $this->debit  = new acc_journal_EntrySide($debitData, acc_journal_EntrySide::DEBIT);
        $this->credit = new acc_journal_EntrySide($creditData, acc_journal_EntrySide::CREDIT);
        
        $this->JournalDetails = cls::get('acc_JournalDetails');
    }


    /**
     * Инициализира ред на транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param stdClass $data
     * @return acc_journal_Entry
     */
    public function initFromTransactionSource($data)
    {
        $this->debit->initFromTransactionSource($data);
        $this->credit->initFromTransactionSource($data);
        
        return $this;
    }
    
    
    /**
     * 
     * @param array $data
     * @return acc_journal_Entry
     */
    public function setDebit($data)
    {
        $this->debit->init($data);
        
        return $this;
    }
    
    
    /**
     * 
     * @param array $data
     * @return acc_journal_Entry
     */
    public function setCredit($data)
    {
        $this->credit->init($data);
        
        return $this;
    }
    
    
    /**
     * Удостоверяване на допустимостта на един ред от счетоводна транзакция.
     * 
     * @return boolean
     */
    public function check()
    {
        // Проверка за съответствие между разбивките на сметката и зададените пера  
        $this->debit->checkItems() && $this->credit->checkItems();
        
        // Цена по кредита е позволена единствено и само, когато кредит-сметка няма зададена 
        // стратегия (LIFO, FIFO, WAC).
        if ($this->credit->account->hasStrategy()) {
            acc_journal_Exception::expect(
                $this->credit->account->isDimensional(), 
                'Сметките със стратегия трябва да са с размерна аналитичност'
            );
        }
        
        // Има ли кредит сметката размерна аналитичност?
        if ($this->credit->account->isDimensional()) {
            // Количеството по кредита е задължително за сметки с размерна аналитичност
            acc_journal_Exception::expect(
                isset($this->credit->quantity), 
                'Липсва количество при кредитиране на сметка с размерна аналитичност'
            );
        }
        
        // Има ли кредит сметката размерна аналитичност?
        if ($this->debit->account->isDimensional()) {
            // Количеството по дебита е задължително за сметки с размерна аналитичност
            acc_journal_Exception::expect(
                isset($this->debit->quantity), 
                'Липсва количество при дебитиране на сметка с размерна аналитичност'
            );

            // Наличието на цена по дебита, за сметки с размерна аналитичност е
            // 1. Задължително, ако кореспондиращата кредит сметка НЯМА стратегия
            // 2. Забранено, ако  кореспондиращата кредит сметка ИМА стратегия
            
            if ($this->credit->account->hasStrategy()) {
            } else {
                acc_journal_Exception::expect(
                    isset($this->debit->price), 
                    'Липсва цена при дебитиране на сметка с размерна аналитичност, която кореспондира с кредит сметка без стратегия'
                );
            }
        }
        
        $this->checkAmounts();
        
        return TRUE;
    }

    
    protected function checkAmounts()
    {
        $PRECISION = 0.001;
        
        if (isset($this->debit->amount) && isset($this->credit->amount)) {
            acc_journal_Exception::expect(
                abs($this->debit->amount - $this->credit->amount) < $PRECISION 
                    &&
                abs($this->debit->amount - $this->amount()) < $PRECISION,
                "Дебит-стойността на транзакцията не съвпада с кредит-стойността"
            );
        }
        
        return TRUE;
    }
    
    /**
     * Връща сумата на реда от транзакция или NULL, ако е неопределена
     * 
     * @return number
     */
    public function amount()
    {
        if (isset($this->amount)) {
            return $this->amount;
        }
        if (isset($this->debit->amount)) {
            return $this->debit->amount;
        }
        
        return $this->credit->amount;
    }
    
    
    public function save($transactionId)
    {
        $this->debit->forceItems();
        $this->credit->forceItems();
        
        $entryRec = $this->debit->getData() 
                    + $this->credit->getData()
                    + array(
                          'journalId' => $transactionId,
                          'amount'    => $this->amount()
                      );
        
        
        return $this->JournalDetails->save((object)$entryRec);
    }
    
    /**
     * Обръща знаците на запис на транзакция
     */
    public function invert()
    {
        $this->debit->invert();
        $this->credit->invert();
    }
}
