<?php



/**
 * Интерфейс за мениджъри на публично съдържание
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Съдържание
 */
class cms_SourceIntf
{
    /**
     * Връща URL към съдържание, което отговаря на посоченото меню
     */
    function getContentUrl($cMenuId)
    {
        return $this->class->getContentUrl($cMenuId);
    }

}