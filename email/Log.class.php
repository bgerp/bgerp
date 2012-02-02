<?php 
/**
 * История от събития, свързани с изпращането и получаването на писма
 * 
 * @category   bgerp
 * @package    email
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 *
 */
class email_Log extends core_Manager
{
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог за имейли";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, email';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, email';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, email';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, plg_Printing, plg_Created';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers,select=id)', 'caption=Контейнер');
        $this->FLD("action", "enum(sent, returned, received, printed, shared)", "caption=Действие");
        $this->FLD("date", "date", "caption=Дата");
        $this->FLD("userId", "key(mvc=core_Users)", 'caption=Потребител');
    }
    
}
