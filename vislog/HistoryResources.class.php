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
class vislog_HistoryResources extends core_Manager
{
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Система';
    
    
    /**
     * Заглавие
     */
    public $title = 'Search Log Resources';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'vislog_Wrapper';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете?
     */
    public $canRead = 'cms, ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin, cms';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('query', 'varchar(255)', 'caption=Ресурс');
        
        $this->setDbUnique('query');
    }
}
