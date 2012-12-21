<?php 


/**
 * 
 *
 * @category  bgerp
 * @package   issue
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class issue_Systems extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Системи';
    
    
    /**
     * 
     */
    var $singleTitle = 'Система';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, issue';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, issue';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, issue';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, issue';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, issue';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     *
     */
    var $canActivate = 'admin, issue';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'issue_Wrapper, doc_FolderPlg';
    
    
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
        $name = $mvc->getVerbal($rec, 'name');
        
        $rec->nameLink = ht::createLink($name, array ('issue_Components', 'list', 'systemId' => $rec->id));
    }
    
    
	/**
     * Тази функция връща текущата система, като я открива по първия възможен начин:
     *
     * 1. От Заявката (Request)
     * 2. От Сесията (Mode)
     * 3. Първата активна номенклатура от таблицата
     */
    static function getCurrentIssueSystemId()
    {
        $systemId = Request::get('systemId', 'key(mvc=issue_Systems, select=name)');
        
        if(!$systemId) {
            $systemId = Mode::get('currentIssueSystemId');
        }
        
        if(!$systemId) {
            $systemQuery = static::getQuery();
            $systemQuery->orderBy('id');
            $listRec = $systemQuery->fetch('1=1');
            $systemId = $listRec->id;
        }
        
        if($systemId) {
            Mode::setPermanent('currentIssueSystemId', $systemId);
        } else {
            redirect(array('issue_Systems'));
        }
        
        return $systemId;
    }
    
    
}