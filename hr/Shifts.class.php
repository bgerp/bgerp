<?php 


/**
 * Смени
 *
 *
 * @category  all
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Shifts extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Смени";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Смяна";
    
    
    /**
     * @todo Чака за документация...
     */
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
        $this->FLD('cycle', 'key(mvc=hr_WorkingCycles,select=name)', "caption=Раб. цикъл");
        $this->FLD('startingOn', 'datetime', "caption=Започване на");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_BeforePrepareEditForm($mvc, $data)
    {
        if(!$mvc->WorkingCycles->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне един работен режим", 'tpl_Error', NULL, array('hr_WorkingCycles'));
        }
    }
}