<?php
class acc_journal_Transaction
{
    /**
     * @var float
     */
    protected $amount;
    
    
    /**
     * 
     * @var array
     */
    protected $entries = array(); 
    
    
    /**
     * 
     * @param float|array|object $amount ако е float се приема за обща стойност на транзакцията;
     *                                   в противен случай - за данни, резултат от извикването
     *                                   на @see acc_TransactionSourceIntf::getTransaction()
     *                                   
     * @see acc_TransactionSourceIntf::getTransaction()                                   
     */
    public function __construct($amount = NULL)
    {
        if (isset($amount)) {
            if (is_numeric($amount)) {
                $this->amount = $amount;
            } else {
                $this->initFromTransactionSource($amount);
            }
        }
    }
    
    
    /**
     * Инициализира транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     * 
     * @param stdClass $data
     */
    public function initFromTransactionSource($data)
    {
        $data = (object)$data;
        
        $this->amount  = isset($data->totalAmount) ? $data->totalAmount : NULL;
        $this->entries = array();
        
        expect(isset($data->entries) && is_array($data->entries));
        
        foreach ($data->entries as $entryData) {
            $this->add()->initFromTransactionSource($entryData);
        }
    }
    
    
    /**
     * 
     * @param acc_journal_Entry $entry
     */
    public function add($entry = NULL)
    {
        if (!isset($entry) || !($entry instanceof acc_journal_Entry)) {
            $entry = new acc_journal_Entry($entry);
        }
        
        $this->entries[] = $entry;
        
        return $entry;
    }

    
    /**
     * @return boolean
     */
    public function check()
    {
        $totalAmount = 0;
        
        foreach ($this->entries as $entry) {
            expect($entry->check(), 'Невалиден ред на транзакция');
            
            $totalAmount += $entry->amount();
        }
        
        if (isset($this->amount)) {
            expect($this->amount == $totalAmount, "Несъответствие между изчислената ({$totalAmount}) и зададената ({$this->amount}) суми на транзакция");
        }
        
        return TRUE;
    }
}