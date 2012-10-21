<?php



/**
 * Мениджър на задачи за производство
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заявки за покупки
 */
class mp_Tasks extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Производствени задачи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, mp_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,mp';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,mp';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,mp';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,mp';
    
    
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