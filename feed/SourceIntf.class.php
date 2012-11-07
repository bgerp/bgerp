<?php


/**
 * Клас 'feed_SourceIntf' - Интерфейс за източник на хранилка
 *
 * @category  vendors
 * @package   feed
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class feed_SourceIntf
{
	
	/**
	 * Интерфейсен метод за извличане на елементите за четене от хранилката
	 * @param int $itemsCnt
	 * @param varchar(2) $lg
	 */
    function getItems($itemsCnt, $lg)
    {
        
        return $this->class->getItems($itemsCnt, $lg);
    }
}