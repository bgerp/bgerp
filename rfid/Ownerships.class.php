<?php



/**
 * class Ownerships
 *
 * Отговаря за текущото и миналото състояние на притежаването на RFID номера
 *
 *
 * @category  bgerp
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Ownerships extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Притежания';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,rfid';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,admin,rfid';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools2';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'holderId,tagId,startOn,endOn,createdOn,createdBy';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('holderId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'caption=Притежател,mandatory,silent');
        $this->FLD('tagId', 'int', 'caption=rfid');
        $this->FLD('startOn', 'datetime', 'caption=Притежание->от');
        $this->FLD('endOn', 'datetime(defaultTime=23:59:59)', 'caption=Притежание->до');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $options = crm_Persons::getEmployeesOptions();
        if ($holderId = $data->form->rec->holderId) {
            if (!array_key_exists($holderId, $options)) {
                $options[$holderId] = crm_Persons::getVerbal($holderId, 'name');
            }
        }
        
        $data->form->setOptions('holderId', array('' => '') + $options);
    }
    
    
    public function prepareHolders($data)
    {
        $data->rfidRecs = $data->rfidRows = array();
        expect($data->masterMvc instanceof crm_Persons);
        $query = self::getQuery();
        $query->where("#holderId = {$data->masterId}");
        while ($rec = $query->fetch()) {
            $data->rfidRecs[$rec->id] = $rec;
            $row = self::recToVerbal($rec);
            $data->rfidRows[$rec->id] = $row;
        }
        
        if ($this->haveRightFor('add', (object) array('holderId' => $data->masterId))) {
            $data->rfidUrl = array(get_called_class(), 'add', 'holderId' => $data->masterId, 'ret_url' => true);
        }
    }
    
    public function renderHolders($data)
    {
        $tpl = new core_ET('');
        $fields = $this->listFields;
        expect(false, $fields);
         
        return $tpl;
    }
}
