<?php


/**
 * Клас 'cms_FeedsSourceIntf' - Интерфейс за източник на хранилка
 *
 * @category  bgerp
 * @package   cms
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за източник на хранилка
 */
class cms_FeedsSourceIntf
{
    
    /**
     * Интерфейсен метод за извличане на елементите за четене от хранилката
     * @param int $itemsCnt
     * @param int $domainId
     */
    public function getItems($itemsCnt, $domainId)
    {
        return $this->class->getItems($itemsCnt, $domainId);
    }
}
