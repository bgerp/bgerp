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
    protected $debit;
    
    
    /**
     * Кредитна част на ред от счетоводна транзакция
     *
     * @var acc_journal_EntrySide
     */
    protected $credit;
    
    
    /**
     * Стойност на реда в основна валута
     * 
     * @var float
     */
    protected $amount;

    
    /**
     * Конструктор
     * 
     * @param object|array $debitData дебитната част на реда
     * @param object|array $creditData кредитната част на реда
     */
    public function __construct($debitData = NULL, $creditData = NULL)
    {
        $this->debit  = new acc_journal_EntrySide($debitData);
        $this->credit = new acc_journal_EntrySide($creditData);
    }


    /**
     * Инициализира ред на транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param stdClass $data
     * @return acc_journal_Entry
     */
    public function initFromTransactionSource($data)
    {
        $this->debit->initFromTransactionSource($data, acc_journal_EntrySide::DEBIT);
        $this->credit->initFromTransactionSource($data, acc_journal_EntrySide::CREDIT);
        
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
    
    public function check()
    {
           $this->debit->checkAcceptable() && $this->credit->checkAcceptable()
        && $this->checkBalanced()
        && $this->debit->checkDimensions() && $this->credit->checkDimensions()
           ;
        
        return TRUE;
    }
    
    public function amount()
    {
        if (!isset($this->amount)) {
            $this->check();
        }
        
        return $this->amount;
    }
    
    
    protected function checkBalanced()
    {
        $this->debit->evaluate();
        $this->credit->evaluate();
        
        if (!isset($this->debit->amount) && isset($this->credit->amount)) {
            $this->debit->setAmount($this->credit->amount);
            $this->debit->evaluate();
        }
        if (isset($this->debit->amount) && !isset($this->credit->amount)) {
            $this->credit->setAmount($this->debit->amount);
            $this->credit->evaluate();
        }
        
        expect(isset($this->debit->amount) && isset($this->credit->amount) && $this->debit->amount == $this->credit->amount);
        expect(!isset($this->amount) || $this->amount == $this->debit->amount);
        
        $this->amount = $this->debit->amount;
    }
}
