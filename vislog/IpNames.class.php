<?php



/**
 * Клас 'vislog_IpNames' -
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vislog_IpNames extends core_Manager {
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = 'Система';
    
    
    /**
     * Заглавие
     */
    var $title = 'Search Log Ip-s';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "vislog_Wrapper,plg_RowTools";
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = "cms,admin,ceo";
    
    
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
        
        $this->FLD('ip', 'varchar(15)', 'caption=IP');
        $this->FLD('name', 'varchar(15)', 'caption=Име');
        
        $this->setDbUnique('ip');
    }
}