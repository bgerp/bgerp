<?php

/**
 * Регистри, които имат представяне в номенклатурите
 */
interface intf_Register
{
    
    /**
     * Преобразуване на запис на регистър към запис за перо в номенклатура (@see acc_Items)
     *
     * @param stdClass $rec запис от модела на мениджъра, който имплементира този интерфейс
     * @return stdClass запис за модела acc_Items
     *
     *  o title
     *  o uomId (ако има)
     */
    function getAccItemRec($rec);
}