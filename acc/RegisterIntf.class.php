<?php

/**
 * Интерфейс за регистри източници на пера
 *
 * @category   bgERP 2.0
 * @package    acc
 * @title:     Източник на пера
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class acc_RegisterIntf
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
    function getAccItemRec($rec) 
    {
        return $this->class->getAccItemRec($rec);
    }
}