<?php 


/**
 * Работни цикли
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Cycles extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Работни графици";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Работен график";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * @todo Чака за документация...
     */
    //var $details = 'hr_CycleDetails';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing';
    
    
    /**
     * Единична икона
     */
    //var $singleIcon = 'img/16/timespan.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    //var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,hr';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,hr';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hr';
    
    /**
     * @todo Чака за документация...
     */
    //var $singleFields = 'id,name,cycleDuration,info';
    
    
    /**
     * Шаблон за единичния изглед
     */
    //var $singleLayoutFile = 'hr/tpl/SingleLayoutWorkingCycles.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {

    }

}
