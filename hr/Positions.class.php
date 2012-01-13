<?php 



/**
 * Смени
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Positions extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Длъжности";
    
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Длъжност";
    
    
    var $pageMenu = "Персонал";
    
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                        plg_SaveAndNew, WorkingCycles=hr_WorkingCycles';
    
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,hr';
    
    
    
    /**
     * Кой може да пише?
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