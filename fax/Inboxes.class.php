<?php


/**
 * Факс адреси
 *
 *
 * @category  bgerp
 * @package   fax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fax_Inboxes extends core_Master
{
    
    
    /**
     * Плъгини за работа
     */
    var $loadList = 'fax_Wrapper, plg_State, plg_Created, plg_Modified, doc_FolderPlg, plg_RowTools';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Факс адреси";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, fax';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, fax';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, fax';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin, fax';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, fax';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, fax';
    
    
    /**
     * Кой има права за
     */
    var $canFax = 'admin, fax';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces =
    // Интерфейс за корица на папка
    'doc_FolderIntf';
    
    
//    /**
//     * полета от БД по които ще се търси
//     */
//    var $searchFields = 'fax';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Факс кутии';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/fax.png';
    
    
//    /**
//     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
//     */
//    var $rowToolsSingleField = 'fax';
    
    
//    /**
//     * Полета, които ще се показват в листов изглед
//     */
//    var $listFields = 'id, fax, type, applyRouting=Общ, folderId, inCharge, access, shared, createdOn, createdBy';
        
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
    }
}