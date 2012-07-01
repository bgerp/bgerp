<?php



/**
 * Мениджър на заявки за покупки
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заявки за покупки
 */
class sales_Deals extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Сделки за продажби';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, sales_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,sales';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,sales';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,sales';
    
    
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