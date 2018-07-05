<?php

/**
 * Помощен клас моделиращ дебитна или кредитна част на ред от счетоводна транзакция
 *
 * Използва се само от acc_journal_Entry.
 *
 * @category bgerp
 * @package acc
 * @author Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license GPL 3
 * @since v 0.1
 * @see acc_journal_Entry
 */
class acc_journal_EntrySide
{
    
    /**
     * @var string
     */
    const DEBIT = 'debit';
    
    
    /**
     * @var string
     */
    const CREDIT = 'credit';
    
    /**
     *
     * @var acc_journal_Account
     */
    protected $account;
    
    /**
     *
     * @var array
     */
    protected $items;
    
    /**
     *
     * @var float
     */
    protected $amount;
    
    /**
     *
     * @var float
     */
    protected $quantity;
    
    /**
     *
     * @var float
     */
    protected $price;
    
    /**
     * @var string
     */
    protected $type;
    
    
    /**
     * Конструктор
     *
     * @param array|object $data
     * @param string       $type debit или credit
     */
    public function __construct($data, $type)
    {
        $this->init($data);
        $this->type = $type;
    }
    
    
    /**
     * Инициализира ред на транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param array|stdClass $data резултат от @see acc_TransactionSourceIntf::getTransaction()
     */
    public function initFromTransactionSource($transactionData)
    {
        $transactionData = (array) $transactionData;
        
        // Преобразуваме $transactionData към структура, подходяща за параметър на метода init()
        $data = new stdClass();
        
        acc_journal_Exception::expect(
            $d = $transactionData[$this->type],
            "Липсва {$this->type} част на транзакция",
        
            array('data' => $transactionData)
        );
        
        $data->amount = $transactionData['amount'];  // Сума в основна валута
        if (array_key_exists('quantity', $d)) {
            $data->quantity = $d['quantity'];
            unset($d['quantity']);
        }
        
        // SystemID или обект-инстанция на сметката е винаги първия елемент
        $data->account = array_shift($d);
        
        // Приемаме, че всичко останало в $d е пера.
        acc_journal_Exception::expect(
            count($d) <= 3,
            "{$this->type}: Макс 3 пера",
            array('data' => $transactionData)
        );
        
        // Изтриваме празните позиции за пера
        foreach (array_keys($d) as $i) {
            if (is_null($d[$i])) {
                unset($d[$i]);
            }
        }
        
        $data->items = $d;
        
        // Делегираме работата по инитициализацията на метода init()
        $this->init($data);
    }
    
    /**
     * Инициализира транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param  stdClass $data
     * @return void
     */
    public function init($data)
    {
        $data = (object) $data;
        
        $this->amount = isset($data->amount)   ? floatval($data->amount) : null;
        $this->quantity = isset($data->quantity) ? floatval($data->quantity) : null;
        $this->price = isset($data->price)    ? floatval($data->price) : null;
        $this->account = $data->account instanceof acc_journal_Account ? $data->account :
        new acc_journal_Account($data->account);
        
        $this->items = array();
        
        if (is_array($data->items)) {
            foreach ($data->items as $item) {
                $this->items[] = $item instanceof acc_journal_Item ? $item :
                new acc_journal_Item($item);
            }
        }
        
        // Изчисляване на незададената цена (price), количество (quantity) или сума (amount)
        $this->evaluate();
    }
    
    
    /**
     * Има ли зададена стойност поле на класа
     *
     * @param  string  $name
     * @return boolean
     */
    public function __isset($name)
    {
        if (!property_exists($this, $name)) {
            
            return false;
        }
        
        if ($name == 'price') {
            
            return !is_null($this->getPrice());
        }
        
        return isset($this->{$name});
    }
    
    
    /**
     * Readonly достъп до полетата на обекта
     *
     * @param  string                $name
     * @return mixed
     * @throws core_exception_Expect когато полето не е дефинирано в класа
     */
    public function __get($name)
    {
        expect(property_exists($this, $name), $name);
        
        if ($name == 'price') {
            
            return $this->getPrice();
        }
        
        return $this->{$name};
    }
    
    
    /**
     * Ще приеме ли сметката зададените пера?
     *
     * @see acc_journal_Account::accepts()
     *
     * @return boolean
     */
    public function checkItems()
    {
        $this->account->accepts($this->items);
        
        return true;
    }
    
    
    /**
     * Изчислява, ако е възможно, незададеното amount/quantity
     *
     * amount   = price * quantity, ако са зададени price и quantity
     * quantity = amount / price, ако са зададени price и amount
     *
     * В останалите случаи не прави нищо.
     */
    public function evaluate()
    {
        switch (true) {
            case isset($this->amount, $this->quantity, $this->price):
            break;
            case isset($this->quantity, $this->price):
            $this->amount = $this->quantity * $this->price;
            break;
            case isset($this->amount, $this->price):
            $this->quantity = $this->amount / $this->price;
            break;
        }
    }
    
    
    /**
     * @see acc_journal_Item
     */
    public function forceItems()
    {
        /* @var $item acc_journal_Item */
        foreach ($this->items as $i => $item) {
            $item->force($this->account->{'groupId' . ($i + 1)});
        }
    }
    
    
    /**
     * @return array
     */
    public function getData()
    {
        $type = $this->type;
        
        $rec = array(
            "{$type}AccId" => $this->account->id,  // 'key(mvc=acc_Accounts,select=title,remember)',
            "{$type}Item1" => isset($this->items[0]) ? $this->items[0]->id : null, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Item2" => isset($this->items[1]) ? $this->items[1]->id : null, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Item3" => isset($this->items[2]) ? $this->items[2]->id : null, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Quantity" => $this->quantity, // 'double'
            "{$type}Price" => $this->price, // 'double(minDecimals=2)'
        );
        
        return $rec;
    }
    
    
    /**
     * Обръща знаците на количеството и сумата
     */
    public function invert()
    {
        if (!empty($this->quantity)) {
            $this->quantity = -$this->quantity;
        }
        
        if (!empty($this->amount)) {
            $this->amount = -$this->amount;
        }
    }
    
    
    /**
     * Връща зададената или изчислена цена
     *
     * @return float NULL, ако цената нито е зададена, нито може да бъде изчислена
     */
    protected function getPrice()
    {
        if (isset($this->price)) {
            
            return $this->price;
        }
        
        if (isset($this->amount, $this->quantity)) {
            if ($this->quantity) {
                
                return $this->amount / $this->quantity;
            }
                
            // Зада няма деление на нула, ако к-то е нула
            return 0;
        }
    }
    
    
    /**
     * Намираме всички затворени пера в ентрито
     */
    public function getClosedItems()
    {
        $closedItems = array();
        
        // Ако има пера, обхождаме ги
        if (count($this->items)) {
            foreach ($this->items as $item) {
                
                // Запомняме затворените пера
                if ($item->isClosed() && isset($item->id)) {
                    $closedItems[$item->id] = $item->id;
                }
            }
        }
        
        // Връщаме затворените пера или празен масив, ако всички са отворени
        return $closedItems;
    }
}
