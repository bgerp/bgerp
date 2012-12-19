<?php
class acc_journal_Transaction
{
    /**
     * 
     * @var array
     */
    protected $entries = array(); 
    
    protected $rec;
    
    /**
     * @var acc_Journal
     */
    public $Journal;
    
    
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
        $rec = new stdClass();
        
        if (isset($amount)) {
            if (is_numeric($amount)) {
                $this->rec->totalAmount = floatval($amount);
            } else {
                $this->init($amount);
            }
        }
        
        $this->Journal = cls::get('acc_Journal');
    }
    
    
    /**
     * Инициализира транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     * 
     * @param stdClass $data
     * @return void
     */
    public function init($data)
    {
        $data = (object)$data;
        
        $this->entries = array();
        
        expect(isset($data->entries) && is_array($data->entries));
        
        foreach ($data->entries as $entryData) {
            $this->add()->initFromTransactionSource($entryData);
        }

        unset($data->entries);
        $this->rec = clone $data;
    }
    
    
    /**
     * Добавя нов ред в транзакция
     * 
     * @param acc_journal_Entry $entry
     * @return acc_journal_Entry $entry
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
     * Проверка на валидността на счетоводна транзакция
     * 
     * @return boolean
     */
    public function check()
    {
        $sumItemsAmount = $this->amount();
        
        if (isset($this->rec->totalAmount)) {
            expect($this->rec->totalAmount == $sumItemsAmount, "Несъответствие между изчислената ({$sumItemsAmount}) и зададената ({$this->rec->totalAmount}) суми на транзакция");
        }
        
        foreach ($this->entries as $entry) {
            expect($entry->check(), 'Невалиден ред на транзакция');
        }
        
        return TRUE;
    }
    
    
    /**
     * Изчислява общата сума на транзакцията като сбор от сумите на отделните й редове
     * 
     * @return float
     */
    public function amount()
    {
        $totalAmount = 0;
        
        /* @var $entry acc_journal_Entry */
        foreach ($this->entries as $entry) {
            expect($entry->check(), 'Невалиден ред на транзакция');
        
            $totalAmount += $entry->amount();
        }
        
        return $totalAmount;
    }
    
    
    /**
     * Записва транзакция в БД
     * 
     * @return boolean
     */
    public function save()
    {
        if (!$this->check()) {
            // Невалидна транзакция
            return FALSE;
        }
        
        if (!$this->begin()) {
            return FALSE;
        }

        foreach ($this->entries as $entry) {
            if (!$entry->save($this->rec->id)) {
                // Проблем при записването на детайл-запис. Rollback!!!
                $this->rollback();
                return FALSE;
            }
        }
        
        return $this->commit();
    }
    
    
    /**
     * Стартира процеса на записване на транзакция
     * 
     * @return boolean
     */
    public function begin()
    {
        // Начало на транзакция: създаваме draft мастър запис, за да имаме ключ за детайлите
        $this->rec->state = 'draft';
        $this->rec->totalAmount = $this->amount();
        
        if (!$this->Journal->save($this->rec)) {
            // Не стана създаването на мастър запис, аборт!
            // @TODO: Изтриване на драфт-записа
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Финализира транзакция след успешно записване
     * 
     * @return boolean
     */
    public function commit()
    {
        //  Транзакцията е записана. Активираме
        $this->rec->state = 'active';
        
        return $this->Journal->save($this->rec);
    }
    
    
    /**
     * Изтрива частично записана транзакция
     * 
     * @return boolean
     */
    public function rollback()
    {
        $this->JournalDetails->delete("#journalId = {$this->rec->id}");
        $this->Journal->delete($this->rec->id);
        
        return TRUE;
    }
}