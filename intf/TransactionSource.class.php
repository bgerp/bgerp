<?php

/**
 * Интерфейс за документ, генериращ счетоводни транзакции
 */
interface intf_TransactionSource
{
    
    /**
     *
     */
    function getLink($id);
}