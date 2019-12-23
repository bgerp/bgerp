<?php


/**
 * Клас - 'acc_journal_Transaction'
 *
 * @category bgerp
 * @package acc
 *
 * @author Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license GPL 3
 *
 * @since v 0.1
 */
class acc_journal_Transaction
{
    /**
     *
     * @var array
     */
    protected $entries = array();
    
    
    /**
     *
     * @var stdClass
     */
    public $rec;
    
    
    /**
     * @var acc_Journal
     */
    public $Journal;
    
    
    /**
     * @var acc_JournalDetails
     */
    public $JournalDetals;
    
    
    /**
     * @param float|array|object $amount ако е float се приема за обща стойност на транзакцията;
     *                                   в противен случай - за данни, резултат от извикването
     *                                   на @see acc_TransactionSourceIntf::getTransaction()
     *
     * @see acc_TransactionSourceIntf::getTransaction()
     */
    public function __construct($amount = null)
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
        $this->JournalDetails = cls::get('acc_JournalDetails');
    }
    
    
    /**
     * Инициализира транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param stdClass $data
     *
     * @return void
     */
    public function init($data)
    {
        $data = (object) $data;
        
        $this->entries = array();
        
        acc_journal_Exception::expect(isset($data->entries) && is_array($data->entries), 'Няма ентрита');
        
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
     *
     * @return acc_journal_Entry $entry
     */
    public function add($entry = null)
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
     * @return bool
     *
     * @throws acc_journal_Exception
     */
    public function check()
    {
        if(Mode::is('saveTransaction') && countR($this->entries)){
            acc_journal_Exception::expect($this->rec->valior, 'Няма вальор');
        }
        
        /* @var $entry acc_journal_Entry */
        if (countR($this->entries)) {
            foreach ($this->entries as $entry) {
                try {
                    $entry->check();
                } catch (acc_journal_Exception $ex) {
                    throw new acc_journal_Exception('Невалиден ред на транзакция: ' . $ex->getMessage());
                }
            }
        }
        
        if (isset($this->rec->totalAmount)) {
            $sumItemsAmount = $this->amount();
            $roundTotal = core_Math::roundNumber($this->rec->totalAmount);
            
            acc_journal_Exception::expect(
                
                trim($roundTotal) == trim($sumItemsAmount),
                "Несъответствие между изчислената ({$sumItemsAmount}) и зададената ({$roundTotal}) суми на транзакция"
            
            );
        }
        
        return true;
    }
    
    
    /**
     * Изчислява общата сума на транзакцията като сбор от сумите на отделните й редове
     *
     * @return float
     */
    protected function amount()
    {
        $totalAmount = 0;
        
        /* @var $entry acc_journal_Entry */
        foreach ($this->entries as $entry) {
            $totalAmount += $entry->amount();
        }
        
        return core_Math::roundNumber($totalAmount);
    }
    
    
    /**
     * Записва транзакция в БД
     *
     * @return bool
     */
    public function save()
    {
        $this->check();
        
        if (!$this->begin()) {
            
            return false;
        }
        
        try {
            if (countR($this->entries)) {
                $recsToSave = array();
                foreach ($this->entries as $entry) {
                    $recsToSave[] = $entry->getRec($this->rec->id);
                }
                
                // Записваме всички детайли с една заявка
                if (!$this->JournalDetails->saveArray($recsToSave)) {
                    
                    // Проблем при записването на детайл-запис. Rollback!!!
                    $this->rollback();
                    
                    return false;
                }
            }
            
            $this->commit();
        } catch (core_exception_Expect $ex) {
            $this->rollback();
            throw $ex;
        }
        
        return true;
    }
    
    
    /**
     * Стартира процеса на записване на транзакция
     *
     * @return bool
     */
    protected function begin()
    {
        // Преди да започне транзакцията, се гледа ако документа е чернова/заявка и има започната транзакция по погрешка
        // ако има такава изтрива се, за да не гърми за дупликация
        $Doc = cls::get($this->rec->docType);
        $docState = $Doc->fetchField($this->rec->docId, 'state');
        if(in_array($docState, array('draft', 'pending'))){
            if($exJournalRec = acc_Journal::fetchByDoc($Doc, $this->rec->docId)){
                $this->Journal->delete("#id = {$exJournalRec->id}");
                wp($exJournalRec, $docState);
            }
        }
        
        // Ако транзакцията е празна не се записва в журнала
        if ($this->isEmpty()) {
            
            return true;
        }
        
        // Начало на транзакция: създаваме draft мастър запис, за да имаме ключ за детайлите
        $this->rec->state = 'draft';
        $this->rec->totalAmount = $this->amount();
        
        if (!$this->Journal->save($this->rec)) {
            // Не стана създаването на мастър запис, аборт!
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Финализира транзакция след успешно записване
     *
     * @return bool
     */
    protected function commit()
    {
        // Транзакцията е записана. Активираме
        $this->rec->state = 'active';
        
        // Ако транзакцията е празна не се записва в журнала
        if ($this->isEmpty()) {
            
            return true;
        }
        
        return $this->Journal->save($this->rec);
    }
    
    
    /**
     * Изтрива частично записана транзакция
     *
     * @return bool
     */
    public function rollback()
    {
        $this->JournalDetails->delete("#journalId = {$this->rec->id}");
        $this->Journal->delete($this->rec->id);
        
        // Логваме в журнала
        $this->Journal->logWrite('Rollback на ред от журнала', $this->rec->id);
        
        return true;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function isEmpty()
    {
        return empty($this->entries);
    }
    
    
    /**
     * Генерира обратна транзакция
     */
    public function invert()
    {
        // Обратната транзакция е множество от обратните записи на текущата транзакция
        foreach ($this->entries as &$entry) {
            $entry->invert();
        }
    }
    
    
    /**
     * Добавя към записите на текущата транзакция всички записи на друга транзакция
     *
     * @param acc_journal_Transaction $transaction
     */
    public function join(acc_journal_Transaction $transaction)
    {
        foreach ($transaction->entries as $entry) {
            $this->add($entry);
        }
    }
    
    
    /**
     * Кои са затворените пера в транзакцията
     */
    public function getClosedItems()
    {
        $closedEntries = array();
        
        if (isset($this->entries)) {
            foreach ($this->entries as $entry) {
                $closedEntries += $entry->debit->getClosedItems();
                $closedEntries += $entry->credit->getClosedItems();
            }
        }
        
        return $closedEntries;
    }
}
