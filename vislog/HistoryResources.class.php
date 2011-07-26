<?php


/**
 * Клас 'vislog_HistoryResources' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    vislog
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class vislog_HistoryResources extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = 'Система';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Search Log Resources';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 20;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = "vislog_Wrapper";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = "no_one";
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        
        $this->FLD('query', 'varchar(255)', 'caption=Query');
        
        $this->setDbUnique('query');
    }
}