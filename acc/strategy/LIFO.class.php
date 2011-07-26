<?php

cls::load('acc_strategy_Strategy');


/**
 * Клас 'acc_strategy_LIFO' -
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
class acc_strategy_LIFO extends acc_strategy_Strategy
{
    /**
     *  @todo Чака за документация...
     */
    function consume($quantity)
    {
        if ($quantity == 0) {
            return 0;
        }
        
        if (empty($this->data)) {
            return false;
        }
        
        // Сега не е ясно дали изобщо е допустимо отрицателно количество.
        // Ясно е обаче, че кода не е готов да обработи този случай.
        assert($quantity > 0);
        
        $amount = 0;
        
        while (!empty($this->data) && $quantity > 0) {
            list($q, $a) = array_pop($this->data);
            $quantity -= $q;
            $amount += $a;
        };
        
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