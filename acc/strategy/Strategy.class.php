<?php
abstract


/**
 * Клас 'acc_strategy_Strategy' -
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_strategy_Strategy
{
    
    
    /**
     * Ид на аналитична сметка
     */
    var $accountId;
    
    /**
     * Масив, които ще захрани стратегията с данни
     */
    var $data = array();
    
    
    /**
     * Конструктор
     */
    function __construct($accountId)
    {
        $this->accountId = $accountId;
    }
    
    
    /**
     * Захранване на стратегията с данни
     *
     * @param double $quantity
     * @param double $amount
     */
    function feed($quantity, $amount)
    {
        $this->data[] = array(
            $quantity, $amount
        );
    }
    
    /**
     * Извличане на паричната стойност на зададено количество.
     *
     * @param double $quantity
     * @return double
     */
    abstract function consume($quantity);
}