<?php 


/**
 * Модел за офиси на speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_Offices extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Офиси на спиди';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_Sorting, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "id,num,name,address,extName";
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('num', 'int', 'caption=Код');
        $this->FNC('extName', 'varchar', 'caption=Наименование');
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('pCode', 'varchar', 'caption=П.код');
        $this->FLD('address', 'varchar', 'caption=Адрес');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * Изчисление на пълното наименование на офиса
     */
    protected static function on_CalcExtName($mvc, $rec)
    {
        $rec->extName = "[{$rec->num}] {$rec->name}";
        if(!empty($rec->address)){
            $rec->extName .= ", {$rec->address}";
        }
    }
    
    
    /**
     * Кои са достъпните офиси за избор
     * 
     * @return array $options
     */
    public static function getAvailable()
    {
        $options = array();
        $query = self::getQuery();
        $query->where("#state != 'closed'");
        
        while($rec = $query->fetch()){
            $options[$rec->id] = $rec->extName;
        }
        
        return $options;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'speedy/data/Offices.csv';
        
        $fields = array(
            0 => 'num',
            1 => 'name',
            2 => 'address',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Обновяване на офисите на спиди по разписание
     */
    public function cron_UpdateOffices()
    {
        core_Users::forceSystemUser();

        $ownCompanyId = crm_Setup::get('BGERP_OWN_COMPANY_COUNTRY', true);
        $loginData = speedy_Adapter::getLoginData();
        if(empty($loginData['userName']) || empty($loginData['password'])) {
            core_Users::cancelSystemUser();
            return;
        }

        try{
            $theirCountryId = speedy_Adapter::getCountryId($ownCompanyId);
            $offices = speedy_Adapter::getOffices($theirCountryId);
        } catch(core_exception_Expect $e){
            log_System::add($this, "Проблем при свързване към акаунта на Speedy", null, 'warning');
            core_Users::cancelSystemUser();

            return;
        }

        // Ако има намерени офиси
        if(is_array($offices)){

            // Извличат им се адресните данни
            $current = array();
            foreach ($offices as $officeRes){
                $obj = (object)array('num' => $officeRes['id'], 'name' => $officeRes['name'], 'pCode' => $officeRes['pCode'], 'address' => trim($officeRes['address']), 'state' => 'active');
                $current[$obj->num] = $obj;
            }

            $query = self::getQuery();
            $exRecs = $query->fetchAll();
            $sync = arr::syncArrays($current, $exRecs, 'num', 'name,pCode,address,state');

            // Добавяне на новите офиси
            if(countR($sync['insert'])){
                $this->saveArray($sync['insert']);
            }
            
            // Ъпдейт на офисите с промяна
            if(countR($sync['update'])){
                $this->saveArray($sync['update'], 'id,pCode,address,name,state');
            }
            
            // Затваряне на вече не-активните офиси
            if(countR($sync['delete'])){
                $closeRecs = array();
                foreach ($sync['delete'] as $officeId){
                    $closeRecs[] = (object)array('id' => $officeId, 'state' => 'closed');
                }
                
                $this->saveArray($closeRecs, 'id,state');
            }
        }
          
        core_Users::cancelSystemUser();
    }
}