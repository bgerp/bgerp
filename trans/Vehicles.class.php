<?php


/**
 * Клас 'trans_Vehicles'
 *
 * Мениджър за транспортни средства
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Vehicles extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Транспортни средства';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Транспортно средство';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, trans_Wrapper, plg_RowNumbering, doc_FolderPlg, plg_Rejected, plg_State2, plg_Modified';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да разглежда?
     */
    public $canSingle = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';
    
    
    /**
     * Кой има право да разглежда?
     */
    public $canList = 'ceo, trans';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, trans';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,number,type,state,createdOn,createdBy';
    
    
    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutVehicle.shtml';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/tractor.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(120)', 'caption=Име,mandatory');
        $this->FLD('number', 'varchar(32)', 'caption=Рег. номер,mandatory');
        $this->FLD('load', 'double', 'caption=Товароносимост');
        $this->FLD('description', 'richtext(rows=3,bucket=Notes)', 'caption=Описание');
        $this->FLD('type', 'enum(truck=Камион,minibus=Минибус,pickup=Пикап,car=Лек автомобил,other=Друг)', 'caption=Вид');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        
        $this->setdbUnique('name');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec->lastUsedOn)) {
            $res = 'no_one';
        }
    }
    
    
    /**
     * Връща списък с достъпните за избор водачи на МПС
     * 
     * @return array $options
     */
    public static function getDriverOptions()
    {
        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        $vehicleDriverGroupId = crm_Groups::getIdFromSysId('vehicleDrivers');
        $keylist = keylist::addKey('', $emplGroupId);
        $keylist = keylist::addKey($keylist, $vehicleDriverGroupId);
        
        $options = array();
        $query = crm_Persons::getQuery();
        $query->likeKeylist('groupList', $keylist);
        $query->show('name');
        while ($dRec = $query->fetch()){
            $options[$dRec->id] = "{$dRec->name} ({$dRec->id})";
        }
        
        return $options;
    }
}
