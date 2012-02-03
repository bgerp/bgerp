<?php



/**
 * Мениджър на групи от дълготрайни активи
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     ДА Групи
 */
class accda_Groups extends core_Manager
{
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    var $menuPage = 'Счетоводство';
    
    
    /**
     * Заглавие
     */
    var $title = 'ДА Групи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    accda_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,accda';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,accda';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,accda';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,accda';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,accda';
    
    
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