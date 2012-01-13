<?php

cls::load('acc_strategy_Strategy');



/**
 * Клас 'acc_strategy_MAP' -
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class acc_strategy_MAP extends acc_strategy_Strategy
{
    protected $quantity = 0;
    protected $amount = 0;
    
    
    
    /**
     * Захранване на стратегията с данни
     *
     * @param double $quantity
     * @param double $amount
     */
    function feed($quantity, $amount)
    {
        $this->quantity += $quantity;
        $this->amount += $amount;
    }
    
    
    
    /**
     * @todo Чака за документация...
     */
    function consume($quantity)
    {
        if ($quantity == 0) {
            return 0;
        }
        
        if ($this->quantity == 0) {
            return false;
        }
        
        return $quantity * ($this->amount / $this->quantity);
    }
}