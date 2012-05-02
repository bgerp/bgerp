<?php



/**
 * Рутира всички получени факсове
 * 
 * @category  bgerp
 * @package   fax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fax_Router extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, fax_Wrapper, plg_RowTools';
    
    
    /**
     * Заглавие
     */
    var $title    = "Рутер на факсове";
    
    
//    /**
//     * Полета, които ще се показват в листов изглед
//     */
//    var $listFields = 'id, type, key, originLink=Източник, priority';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead   = 'admin, fax';
    
    
    /**
     * Кой има право да пише?
     */
    var $canWrite  = 'admin, fax';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin, fax';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
    }
}