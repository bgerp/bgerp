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
class hr_CycleDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Работни графици - детайли";
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = "Работен график - детайл";
    
    /**
     * @todo Чака за документация...
     */
    //var $masterKey = 'cycleId';
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    //var $loadList = 'plg_RowTools, plg_SaveAndNew, plg_RowZebra';
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //var $listFields = 'day,mode=Режим,start,duration,break';
    
    /**
     * @todo Чака за документация...
     */
    var $rowToolsField = 'day';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,hr';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
 
    }

}
