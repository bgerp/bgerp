<?php
/**
 * Мениджър за дефиниране на регистри в Zontromat
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 
 * @title     Дефинирани регистри в Zontromat
 */
class ztm_Registers extends core_Master
{
    public $title = 'Дефинирани регистри в Zontromat';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    public $canSingle = 'ztm, ceo';
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    public $canReject = 'ztm, ceo';
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting';
    
    
    /**
     *
     * @var string
     */
     public $listFields = 'id, name, type, range, plugin, priority, default, description';
    
     
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име');
        $this->FLD('type', 'enum(int,bool,float,str,json,int/float)', 'caption=Тип');
        $this->FLD('range', 'text', 'caption=Диапазон');
        $this->FLD('plugin', 'varchar(32)', 'caption=Модул');
        $this->FLD('priority', 'enum(system, device, global, time)', 'caption=Приоритет за вземане на стойност');
        $this->FLD('default', 'text', 'caption=Дефолтна стойност');
        $this->FLD('description', 'text', 'caption=Описание на регистъра');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        if($this->getQuery()->count()){
            return;
        }
        $file = 'ztm/csv/Registri.csv';
        
        $fields = array(
            0 => 'name',
            1 => 'type',
            2 => 'range',
            3 => 'plugin',
            4 => 'priority',
            5 => 'default',
            6 => 'description',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Какъв наш тип отговаря на техния
     * 
     * @param int $registerId
     * @param boolean $forForm
     * 
     * @return core_Type
     */
    public static function getOurType($registerId)
    {
        $type = ztm_Registers::fetchField($registerId, 'type');
        switch($type){
            case 'int':
                $ourType = 'Int';
                break;
            case 'float':
                $ourType = 'Double';
            case 'bool':
                $ourType = 'enum(yes=Да,no=Не)';
                break;
            case 'str':
                $ourType = 'varchar';
                break;
            case 'int/float':
                $ourType = 'Double';
                break;
            default:
                $ourType = 'text';
                break;
        }
        
        return core_Type::getByName($ourType);
    }
    
    
    /**
     * Добавя функционално поле за въвеждане на допустима стойност
     * 
     * @param core_Form $form
     * @param string|null $registerFld
     * @param string|null $valueFld 
     * 
     * @return void
     */
    public static function extendAddForm($form, $registerFld = 'registerId', $valueFld = 'value')
    {
        $rec = &$form->rec;
        
        if(isset($rec->{$registerFld})){
            $form->FLD('extValue', ztm_Registers::getOurType($rec->{$registerFld}), 'caption=Стойност,mandatory');
            $form->rec->_type = ztm_Registers::fetchField($rec->{$registerFld}, 'type');
            
            if(!empty($rec->{$valueFld})){
                $value = ztm_LongValues::getValueByHash($rec->{$valueFld});
                $form->setDefault('extValue', $value);
            }
        }
    }
    
    
    /**
     * Обработва стойността, ако е от нескаларен тип записва я в помощен модел
     * подменяйки я с нейния хеш
     * 
     * @param int $registerId
     * @param mixed $extValue
     * 
     * @return mixed
     */
    public static function recordValue($registerId, $extValue)
    {
        $type = ztm_Registers::fetchField($registerId, 'type');
        
        // Записва стойността в помощния модел при нужда
        if(in_array($type, array('json'))){
            $hash = md5(serialize($extValue));
            $value = $hash;
            
            $existingValue = ztm_LongValues::fetchField("#hash = '{$hash}'", 'value');
            if(!isset($existingValue)){
                $valueToSave = (is_array($extValue) || is_object($extValue)) ? json_encode($extValue) : $extValue;
                
                
                $longRec = (object)array('hash' => $hash, 'value' => $valueToSave);
                //bp($longRec);
                
                ztm_LongValues::save($longRec);
            }
        } else {
            $value = $extValue;
        }
        
        return $value;
    }
}