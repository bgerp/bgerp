<?php 

/**
 * Работни цикли
 */
class hr_WorkingCycles extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Работни Цикли";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = "Работен цикъл";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,dma';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = 'admin,dma';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        $this->FLD('serial', 'text', "caption=Последователсност,hint=На всеки ред запишете: \nчасове работа&#44; минути почивка&#44; неработни часове");
        
        $this->setDbUnique('name');
    }
}