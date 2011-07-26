<?php 

/**
 * Смени
 */
class hr_Shifts extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Смени";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = "Смяна";
    
    
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
        $this->FLD('cycle', 'key(mvc=hr_WorkingCycles,select=name)', "caption=Раб. цикъл");
        $this->FLD('startingOn', 'datetime', "caption=Започване на");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_BeforePrepareEditForm($mvc, $data)
    {
        if(!$mvc->WorkingCycles->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне един работен режим", 'tpl_Error', NULL, array('hr_WorkingCycles'));
        }
    }
}