<?php 


/**
 * 
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Systems extends core_Master
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'issue_Systems';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Поддържани системи';
    
    
    /**
     * 
     */
    var $singleTitle = 'Система';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/system-monitor.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'support/tpl/SingleLayoutSystem.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, support';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, support';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, support';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, support';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, support';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     *
     */
    var $canActivate = 'admin, support';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, doc_FolderPlg, plg_Created, plg_Rejected, plg_RowTools, plg_Search, plg_State';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    var $autoCreateFolder = 'instant';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces =
    // Интерфейс за корица на папка
    'doc_FolderIntf';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name=Система, folderId, description';
    
    
    /**
     * 
     */
    var $rowToolsField = 'id';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'name, description';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'support_Components';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar', "caption=Наименование,mandatory, width=100%");
        $this->FLD('allowedTypes', 'keylist(mvc=support_IssueTypes, select=type)', 'caption=Позволени типове, mandatory, width=100%');
        $this->FLD('description', 'richtext(rows=10,bucket=Support)', "caption=Описание, width=100%");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако имаме създадена папка
        if ($rec->folderId) {
            
            // Записите за папката
            $folderRec = doc_Folders::fetch($rec->folderId);
            
            // Вземаме линка към папката
            $row->folderId = doc_Folders::recToVerbal($folderRec)->title;
        } else {
            
            // Заглавието на папката
            $title = $mvc->getFolderTitle($rec->id);
            
            // Добавяме бутон за създаване на папка
            $row->folderId = ht::createBtn('Папка', array($mvc, 'createFolder', $rec->id), "Наистина ли желаете да създадетe папка за документи към|* \"{$title}\"?", 
                             FALSE, array('class' => 'btn-new-folder'));
        }
    }
    
    
    /**
     * След създаване на папка, сменяма състоянието на активно
     */
    function on_AfterForceCoverAndFolder($mvc, &$folderId, $rec)
    {
        $nRec = new stdClass();
        $nRec->id = $rec->id;
        $nRec->state = 'active';
        $mvc->save($nRec);
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'edit') {
            if ($rec->state == 'active') {
//                $requiredRoles = 'no_one';    
            } 
        }
    }
}