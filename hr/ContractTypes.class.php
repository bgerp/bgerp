<?php 



/**
 * Типове договори
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_ContractTypes extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Шаблони";
    
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Шаблон";
    
    
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
        $this->FLD('script', 'text', "caption=Текст");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        
        $this->setDbUnique('name');
    }
}