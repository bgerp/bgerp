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
 * @title     Интерфейс за мениджъри на публично съдържание
 */
class cms_SourceIntf
{
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посоченото меню
     */
    function getContentUrl($cMenuId)
    {
        return $this->class->getContentUrl($cMenuId);
    }


    /**
     * Връща URL към съдържание във вътрешната част (работилницата), което отговаря на посоченото меню
     */
    function getWorkshopUrl($cMenuId)
    {
        return $this->class->getWorkshopUrl($cMenuId);
    }
}