<?php



/**
 * Интерфейс за пера - валути
 *
 *
 * @category  all
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Валути
 */
class currency_CurrenciesAccRegIntf extends acc_RegisterIntf
{
    
    
    /**
     * Имат ли обектите на регистъра размерност?
     *
     * @return boolean
     */
    function isDimensional()
    {
        return TRUE;
    }
}