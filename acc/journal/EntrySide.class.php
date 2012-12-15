<?php
/**
 * Помощен клас моделиращ дебитна или кредитна част на ред от счетоводна транзакция
 *
 * @author developer
 *
 */
class acc_journal_EntrySide
{
    const DEBIT  = 'debit';
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
     *
     * @var boolean
     */
    private $isInitialized = FALSE;


    /**
     * Конструктор
     *
     * @param array|object $data
     */
    public function __construct($data = NULL)
    {
        if (isset($data)) {
            $this->init($data);
        }
    }


    /**
     * Инициализира ред на транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param array|stdClass $data
     */
    public function initFromTransactionSource($data, $type)
    {
        $data = (object)$data;
        $type = strtolower($type);

        expect ($type == self::DEBIT || $type == self::CREDIT);

        $result = array(
            'account'  => NULL,
            'items'    => array(),
            'amount'   => NULL,
            'quantity' => NULL,
            'price'    => NULL,
        );

        if (isset($data->{"{$type}Acc"})) {
            $result['account'] = acc_journal_Account::system($data->{"{$type}Acc"});
        } elseif (isset($data->{"{$type}AccId"})) {
            $result['account'] = acc_journal_Account::id($data->{"{$type}AccId"});
        }

        if (isset($data->{"{$type}Quantity"})) {
            $result['quantity'] = isset($data->{"{$type}Quantity"});
        }
        if (isset($data->{"{$type}Price"})) {
            $result['price'] = isset($data->{"{$type}Price"});
        }
        if (isset($data->{"{$type}Amount"})) {
            $result['amount'] = isset($data->{"{$type}Amount"});
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

        $this->amount   = isset($data->amount) ? $data->amount : NULL;
        $this->quantity = isset($data->quantity) ? $data->quantity : NULL;
        $this->price    = isset($data->price) ? $data->price : NULL;
        $this->account  = $data->account instanceof acc_journal_Account ? $data->account :
                                new acc_journal_Account($data->account);

        $this->items = array();

        if (is_array($data->items)) {
            foreach ($data->items as $item) {
                $this->items[] = $item instanceof acc_journal_Item ? $item :
                                    new acc_journal_Item($item);
            }
        }

        $this->isInitialized = TRUE;
    }


    public function setAmount($amount)
    {
        $this->amount = $amount;
    }


    public function __get($name)
    {
        expect(property_exists($this, $name));

        return $this->{$name};
    }


    /**
     * Ще приеме ли сметката зададените пера?
     *
     * @see acc_journal_Account::accepts()
     *
     * @return boolean
     */
    public function checkAcceptable()
    {
        expect($this->account->accepts($this->items));
    }


    /**
     * Съгласуван ли е записа с размерностите на сметката
     *
     * @see acc_journal_Account::accepts()
     *
     * @return boolean
     */
    public function checkDimensions()
    {
        expect(!($this->account->isDimensional() xor isset($this->quantity)));
        
        return TRUE;
    }
    

    public function evaluate()
    {
        switch (true) {
            case isset($this->amount) && isset($this->quantity) && isset($this->price):
                expect($this->amount == $this->quantity * $this->price);
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
            default:
                expect(isset($this->quantity));
        }
    }
}
