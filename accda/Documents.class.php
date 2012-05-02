<?php



/**
 * Мениджър на документи за дълготрайни активи
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     ДА Документи
 */
class accda_Documents extends core_Manager
{
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    var $menuPage = 'Счетоводство';
    
    
    /**
     * Заглавие
     */
    var $title = 'ДА Документи';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Протокол за промяна на ДА';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, doc_DocumentPlg, plg_SaveAndNew, 
                    accda_Wrapper, plg_Search';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
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
     * @todo Чака за документация...
     */
    var $abbr = 'Dac';
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'folder_id, thread_id, container_id';
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    }
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        if(!$id) return;
        
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = $rec->title;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->authorId = $rec->createdBy;
        
        return $row;
    }
}