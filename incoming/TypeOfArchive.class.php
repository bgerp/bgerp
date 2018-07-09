<?php


/**
 * Мениджър на типове архиви
 *
 *
 * @category  bgerp
 * @package   incoming
 *
 * @author   Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class incoming_TypeOfArchive extends core_Master
{
    /**
     * Заглавие на модела
     */
    public $title = 'Типове архиви';
    
    
    /**
     * @todo Чака за документация...
     */
    public $singleTitle = 'Тип на архив';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name,archivUnit,responsiblePerson';
    
    
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
        $this->FLD('name', 'varchar(128)', 'caption=Тип архив,mandatory');
        $this->FLD('archivUnit', 'varchar(128)', 'caption=Единица за архивиране,mandatory');
        $this->FLD('responsiblePerson', 'key(mvc=crm_Persons,select=name)', 'caption=Отговорник,mandatory');
        $this->FLD('storagePeriod', 'int', 'caption=Срок за съхранение,unit = години,mandatory');
        
        $this->setDbUnique('name');
    }
    
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $archiveUnits = 'папка|кашон|палет|стелаж';
        
        $storagePeriod = '1|2|3|4|5|6|7|8|9|10|15|20|25|50';
        
        $archiveUnitsArr = type_Keylist::toArray($archiveUnits);
        
        $storagePeriodArr = type_Keylist::toArray($storagePeriod);
        
        //$data->form->setOptions('archivUnit', array(' ' => 'избери единица ') + $archiveUnitsArr);
        
        $data->form->setSuggestions('archivUnit', $archiveUnitsArr);
        
        $data->form->setOptions('storagePeriod', $storagePeriodArr);
    }
}
