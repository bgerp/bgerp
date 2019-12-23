<?php


/**
 * Интерфейс за мениджъри на публично съдържание
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
    public function getUrlByMenuId($cMenuId)
    {
        return $this->class->getUrlByMenuId($cMenuId);
    }
    
    
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посочения запис
     */
    public function getUrlByRec($rec)
    {
        return $this->class->getUrlByMenuId($rec);
    }
    
    
    /**
     * Връща URL към съдържание във вътрешната част (работилницата), което отговаря на посоченото меню
     */
    public function getWorkshopUrl($cMenuId)
    {
        return $this->class->getWorkshopUrl($cMenuId);
    }
    
    
    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     */
    public function getSearchResults($menuId, $q, $maxLimit = 10)
    {
        return $this->class->getSearchResults($menuId, $q, $maxLimit);
    }
    
    
    /**
     * Връща връща масив със обекти, съдържащи връзки към публичните страници, генерирани от този обект
     */
    public function getSitemapEntries($menuId)
    {
        return $this->class->getSitemapEntries($menuId);
    }
}
