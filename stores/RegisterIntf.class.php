<?php

/**
 * Регистри, които представляват складове
 */
interface stores_RegisterIntf extends acc_RegisterIntf
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
     # function getAccItemRec($rec);
}