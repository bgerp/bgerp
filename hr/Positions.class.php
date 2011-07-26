<?php 

/**
 * Смени
 */
class hr_Positions extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Длъжности";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = "Длъжност";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                        plg_SaveAndNew, WorkingCycles=hr_WorkingCycles';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,hr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = 'admin,hr';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        $this->FLD('nkpd', 'varchar(9)', 'caption=НКПД, hint=Номер по НКИД');
        $this->FLD('nkid', 'varchar(9)', 'caption=НКИД, hint=Номер по НКПД');
        
        $this->FLD('descriptions', 'richtext', 'caption=Характеристика, ');
        
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        
        $this->setDbUnique('name');
    }
}