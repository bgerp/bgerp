<?php 


/**
 *
 *
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Automation extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Правила за автоматизация';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'distro_Wrapper';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
//    var $interfaces = '';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
//    var $listFields = '';
    
    
    
//    var $rowToolsField = 'id';

    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//    var $searchFields = '';
    
    
    /**
     * Детайла, на модела
     */
//    var $details = '';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('type', 'enum(state=Състояние, created=Създадено)', 'caption=Тип, width=100%');
        
        $this->setDbUnique('title');
    }
}
