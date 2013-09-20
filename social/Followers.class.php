<?php 


/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class social_Followers extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Твърдения";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Твърдение";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Декларации";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'sales_Wrapper, dec_Wrapper, plg_Created, plg_RowTools,  plg_Printing, plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, dec';
    
    
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo, dec';
    

    /**
     * Шаблон за единичния изглед
     */
    //var $singleLayoutFile = 'hr/tpl/SingleLayoutShift.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
		
    }
    
}