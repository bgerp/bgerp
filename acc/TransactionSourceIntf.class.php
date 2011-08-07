<?php

/**
 * Интерфейс за документ, генериращ счетоводни транзакции
 *
 * @category   bgERP 2.0
 * @package    acc
 * @title:     Източник на счет. транзакции
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class acc_TransactionSourceIntf
{
    
    /**
     * Връща линк към документа с посоченото id
     */
    function getLink($id)
    {
        return $this->class->getLink($id);
    }
}