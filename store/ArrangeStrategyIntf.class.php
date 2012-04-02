<?php



/**
 * Интерфейс за автоматично подреждане на палети в склада
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ArrangeStrategyIntf
{
    
    
    /**
     * Връща автоматично изчислено палет място
     *
     * @param int $palletId
     */
    function getAutoPalletPlace($productId)
    {
        return $this->class->getAutoPalletPlace($productId);
    }
}