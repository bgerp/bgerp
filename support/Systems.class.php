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
    var $singleIcon = 'img/16/question.png';
    
    
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
    var $loadList = 'support_Wrapper, doc_FolderPlg, plg_Created, plg_Rejected, plg_RowTools, plg_Search';

    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces =
    // Интерфейс за корица на папка
    'doc_FolderIntf';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'nameLink=Наименование, description, folderId, inCharge, access, shared';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'description';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'name, description';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar', "caption=Наименование,mandatory");
        $this->FLD('description', 'text', "caption=Описание");
        
        // Титла - хипервръзка
        $this->FNC('nameLink', 'html', 'column=none');
    }
    
    
	/**
     * Изчислява полето 'nameLink', като име с хипервръзка към перата от тази номенклатура
     */
    static function on_CalcNameLink($mvc, $rec)
    {
        // Вербаната стойнст на полето
        $name = $mvc->getVerbal($rec, 'name');
        
        // Създаваме линк към компонентите
        $rec->nameLink = ht::createLink($name, array ('support_Components', 'list', 'systemIdFnc' => $rec->id));
    }
}