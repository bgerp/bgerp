<?php
abstract


/**
 * Клас 'acc_strategy_Strategy' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    acc
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_strategy_Strategy
{
    /**
     *  @todo Чака за документация...
     */
    var $accountId;
    
    protected $data = array();
    
    
    /**
     *  @todo Чака за документация...
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