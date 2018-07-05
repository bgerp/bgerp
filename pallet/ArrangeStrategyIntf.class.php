<?php



/**
 * Интерфейс за автоматично подреждане на палети в склада
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_ArrangeStrategyIntf
{
    
    
    /**
     * Връща автоматично изчислено палет място
     *
     * @param int $palletId
     */
    public function getAutoPalletPlace($productId)
    {
        return $this->class->getAutoPalletPlace($productId);
    }
}
