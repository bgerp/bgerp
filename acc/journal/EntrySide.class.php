<?php
/**
 * Помощен клас моделиращ дебитна или кредитна част на ред от счетоводна транзакция
 *
 * Използва се само от acc_journal_Entry.
 * 
 * @author developer
 * @see acc_journal_Entry
 */
class acc_journal_EntrySide
{
    /**
     * @var string
     */
    const DEBIT  = 'debit';
    
    
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
     * @param string $type debit или credit
     */
    public function __construct($data, $type)
    {
        $this->init($data);
        $this->type = $type;
    }


    /**
     * Инициализира ред на транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param array|stdClass $data
     */
    public function initFromTransactionSource($data)
    {
        $data = (object)$data;
        $type = strtolower($this->type);

        expect ($type == self::DEBIT || $type == self::CREDIT);

        $result = array(
            'account'  => NULL,
            'items'    => array(),
            'amount'   => NULL,
            'quantity' => NULL,
            'price'    => NULL,
        );

        if (isset($data->{"{$type}Acc"})) {
            $result['account'] = new acc_journal_Account($data->{"{$type}Acc"});
        } elseif (isset($data->{"{$type}AccId"})) {
            $result['account'] = acc_journal_Account::byId($data->{"{$type}AccId"});
        }

        if (isset($data->{"{$type}Quantity"})) {
            $result['quantity'] = $data->{"{$type}Quantity"};
        }
        if (isset($data->{"{$type}Price"})) {
            $result['price'] = $data->{"{$type}Price"};
        }
        if (isset($data->{"{$type}Amount"})) {
            $result['amount'] = $data->{"{$type}Amount"};
        }

        foreach (range(1, 3) as $n) {
            if (isset($data->{"{$type}Item{$n}"})) {
                $itemData = (object)$data->{"{$type}Item{$n}"};
                $result['items'][$n-1] = new acc_journal_Item($itemData->cls, $itemData->id);
            } elseif (isset($data->{"{$type}Item{$n}Id"})) {
                $result['items'][$n-1] = new acc_journal_Item($data->{"{$type}Item{$n}Id"});
            }
        }

        return $this->init($result);
    }


    public function init($data)
    {
        $data = (object)$data;

        $this->amount   = isset($data->amount)   ? floatval($data->amount) : NULL;
        $this->quantity = isset($data->quantity) ? floatval($data->quantity) : NULL;
        $this->price    = isset($data->price)    ? floatval($data->price) : NULL;
        $this->account  = $data->account instanceof acc_journal_Account ? $data->account :
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
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        if (!property_exists($this, $name)) {
            return FALSE;
        }
    
        return isset($this->{$name});
    }
    

    /**
     * Readonly достъп до полетата на обекта
     * 
     * @param string $name
     * @return mixed
     * @throws core_exception_Expect когато полето не е дефинирано в класа
     */
    public function __get($name)
    {
        expect(property_exists($this, $name), $name);
    
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
        expect($this->account->accepts($this->items));
        
        return TRUE;
    }
    

    /**
     * Изчислява, ако е възможно, незададеното amount/price/quantity
     * 
     *  amount = price * quantity
     *  
     * Ако са зададени:
     *  
     *   o точно две стойности  - изчислява третата, така че да задоволи горното тъждество
     *   o в останалите случаи (< 2 или точно 3 ст-сти) - не прави нищо
     */
    public function evaluate()
    {
        switch (true) {
            case isset($this->amount) && isset($this->quantity) && isset($this->price):
                break;
            case isset($this->quantity) && isset($this->price):
                $this->amount = $this->quantity * $this->price;
                break;
            case isset($this->amount) && isset($this->price):
                $this->quantity = $this->amount / $this->price;
                break;
            case isset($this->amount) && isset($this->quantity):
                $this->price = $this->amount / $this->quantity;
                break;
        }
    }
    
    
    public function forceItems()
    {
        /* @var $item acc_journal_Item */
        foreach ($this->items as $i=>$item) {
            $item->force($this->account->{'groupId' . ($i+1)});
        }
    }
    
    
    /**
     * 
     * @return array
     */
    public function getData()
    {
        $type = $this->type;
        
        $rec = array(
            "{$type}AccId"    => $this->account->id,  // 'key(mvc=acc_Accounts,select=title,remember)',
            "{$type}Item1"    => isset($this->items[0]) ? $this->items[0]->id : NULL, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Item2"    => isset($this->items[1]) ? $this->items[1]->id : NULL, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Item3"    => isset($this->items[2]) ? $this->items[2]->id : NULL, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Quantity" => $this->quantity, // 'double'
            "{$type}Price"    => $this->price, // 'double(minDecimals=2)'
        );

        return $rec;
    }
}
