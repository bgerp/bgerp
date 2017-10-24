<?php 


/**
 * Типове входящи документи
 *
 *
 * @category  bgerp
 * @package   incoming
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class incoming_Types extends core_Master
{
    
     
    /**
     * Заглавие на модела
     */
    var $title = 'Типове входящи документи';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Тип на входящ документ';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, doc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, doc';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'ceo, doc';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canDoc = 'powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Modified,incoming_Wrapper,plg_Rowtools2';
    
    
    /**
     * Полето "Заглавие" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
         $this->FLD("name", "varchar(128)", 'caption=Тип документ,mandatory');
         $this->setDbUnique('name');
    }
}