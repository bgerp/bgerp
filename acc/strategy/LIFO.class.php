<?php

cls::load('acc_strategy_Strategy');


/**
 * Клас 'acc_strategy_LIFO' -
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_strategy_LIFO extends acc_strategy_Strategy
{
    
    
    /**
     * Извличане на паричната стойност на зададено количество.
     * @param  double $quantity
     * @return double
     */
    public function consume($quantity)
    {
        if ($quantity == 0) {
            return 0;
        }
        
        if (empty($this->data)) {
            return false;
        }
        
        $amount = 0;
        
        while (!empty($this->data) && $quantity > 0) {
            list($q, $a) = array_pop($this->data);
            $quantity -= $q;
            $amount += $a;
        }
        
        // Изчисляваме остатъка и коригираме с него общата стойност.
        $a = ($a / $q) * $quantity;
        $amount += $a;
        
        if ($quantity < 0) {
            // извлекли сме твърде много, трябва да върнем обратно остатъка
            array_push($this->data, array(-$quantity, -$a));
        }
        
        return $amount;
    }
}
