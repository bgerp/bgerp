<?php


/**
 * Клас 'acc_strategy_Strategy' -
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class acc_strategy_Strategy
{
    /**
     * Ид на аналитична сметка
     */
    public $accountId;
    
    
    /**
     * Масив, които ще захрани стратегията с данни
     */
    public $data = array();
    
    
    /**
     * Конструктор
     */
    public function __construct($accountId)
    {
        $this->accountId = $accountId;
    }
    
    
    /**
     * Захранване на стратегията с данни
     *
     * @param float $quantity
     * @param float $amount
     */
    public function feed($quantity, $amount)
    {
        $this->data[] = array(
            $quantity, $amount
        );
    }
    
    
    /**
     * Извличане на паричната стойност на зададено количество.
     *
     * @param float $quantity
     *
     * @return float
     */
    abstract public function consume($quantity);
}
