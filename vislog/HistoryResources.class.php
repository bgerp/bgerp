<?php



/**
 * Клас 'vislog_HistoryResources' -
 *
 *
 * @category  vendors
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vislog_HistoryResources extends core_Manager {
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = 'Система';
    
    
    /**
     * Заглавие
     */
    var $title = 'Search Log Resources';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "vislog_Wrapper";
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = "no_one";
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'cms, ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, admin, cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo, admin, cms';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
        $this->FLD('query', 'varchar(255)', 'caption=Ресурс');
        
        $this->setDbUnique('query');
    }
}