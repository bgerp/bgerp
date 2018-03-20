<?php



/**
 * Интерфейс за мениджъри на публично съдържание
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за мениджъри на публично съдържание
 */
class cms_SourceIntf
{
	
	/**
	 * Инстанция на обекта
	 */
	public $class;
	
	
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посоченото меню
     */
    function getUrlByMenuId($cMenuId)
    {
        return $this->class->getUrlByMenuId($cMenuId);
    }
    
    
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посочения запис
     */
    function getUrlByRec($rec)
    {
        return $this->class->getUrlByMenuId($rec);
    }


    /**
     * Връща URL към съдържание във вътрешната част (работилницата), което отговаря на посоченото меню
     */
    function getWorkshopUrl($cMenuId)
    {
        return $this->class->getWorkshopUrl($cMenuId);
    }

    
    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     */
    function getSearchResults($menuId, $q, $maxLimit = 10)
    {
        return $this->class->getSearchResults($menuId, $q, $maxLimit);
    }
}