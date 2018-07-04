<?php


/**
 * Клас 'acc_strategy_FIFO' -
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_strategy_FIFO extends acc_strategy_Strategy
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
            list($q, $a) = array_shift($this->data);
            $quantity -= $q;
            $amount += $a;
        }
        
        // Изчисляваме остатъка и коригираме с него общата стойност.
        $a = ($a / $q) * $quantity;
        $amount += $a;
        
        if ($quantity < 0) {
            // извлекли сме твърде много, трябва да върнем обратно остатъка
            array_unshift($this->data, array(-$quantity, -$a));
        }
        
        return $amount;
    }
}
