<?php 


/**
 * Работни цикли
 *
 *
 * @category  all
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_WorkingCycles extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Работни Цикли";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Работен цикъл";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,dma';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,dma';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        $this->FLD('serial', 'text', "caption=Последователност,hint=На всеки ред запишете: \nчасове работа&#44; минути почивка&#44; неработни часове");
        
        $this->setDbUnique('name');
    }
}