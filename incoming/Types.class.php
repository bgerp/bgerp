<?php 

/**
 * Типове входящи документи
 *
 *
 * @category  bgerp
 * @package   incoming
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class incoming_Types extends core_Master
{
    /**
     * Заглавие на модела
     */
    public $title = 'Типове входящи документи';
    
    
    /**
     * @todo Чака за документация...
     */
    public $singleTitle = 'Тип на входящ документ';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, doc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, doc';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'ceo, doc';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    public $canDoc = 'powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_Modified,incoming_Wrapper,plg_Rowtools2';
    
    
    /**
     * Полето "Заглавие" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Тип документ,mandatory');
        $this->setDbUnique('name');
    }
}
