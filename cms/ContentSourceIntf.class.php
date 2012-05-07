<?php



/**
 * Интерфейс за източник на публично съдържание
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
class cms_ContentSourceIntf
{
    /**
     * Показва публично съдържание, като очаква в Request $cs
     */
    function act_ShowContent()
    {
        $this->class->act_ShowContent();
    }

    
    /**
     * Връща титлата на публично съдържание отговарящо на $cs
     */
    function getContentTitle($cs)
    {
        $this->class->getContentTitle($cs, $link);
    }
    
    
    /**
     * Връща URL към публично съдържание отговарящо на $cs
     */
    function getContentUrl($cs)
    {
        $this->class->getContentUrl($cs, $link);
    }

}