<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_Salaries extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Заплати';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    }
}