<?php 


/**
 * Входящи факсове
 *
 *
 * @category  bgerp
 * @package   fax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fax_Incomings extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Входящи факсове';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Факс';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, fax';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, fax';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, fax';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, fax';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canFax = 'admin, fax, user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'fax_Wrapper, doc_DocumentPlg, plg_RowTools, 
         plg_Printing, email_plg_Document, doc_EmailCreatePlg, doc_FaxCreatePlg, plg_Sorting';
        
    
    /**
     * Нов темплейт за показване
     */
//    var $singleLayoutFile = 'email/tpl/SingleLayoutMessages.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/fax.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Fin";
    
    
    /**
     * Първоначално състояние на документа
     */
    var $firstState = 'closed';
    
    
//    /**
//     * Полето "Относно" да е хипервръзка към единичния изглед
//     */
//    var $rowToolsSingleField = 'subject';
    
    
//    /**
//     * Полета, които ще се показват в листов изглед
//     */
//    var $listFields = 'id,subject,date,fromEml=От,toEml=До,accId,boxIndex,country';
    
    
//    /**
//     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
//     */
//    var $searchFields = 'subject, fromEml, fromName, textPart';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        
    }
}