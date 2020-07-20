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
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'ztm_RegistersDef';
    
    
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
        $this->FLD('type', 'enum(int, bool, float, str, text, object,array)', 'caption=Тип');
        $this->FLD('range', 'text', 'caption=Диапазон');
        $this->FLD('plugin', 'varchar(32)', 'caption=Модул');
        $this->FLD('priority', 'enum(system, device, global, time)', 'caption=Приоритет за вземане на стойност');
        $this->FLD('default', 'varchar(32)', 'caption=Дефолтна стойност');
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
    
    
    public static function getValueFormType($registerId)
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
            $form->FLD('extValue', ztm_Registers::getValueFormType($rec->{$registerFld}), 'caption=Стойност,mandatory');
            
            if(!empty($rec->{$valueFld})){
                $value = ztm_LongValues::getValueByHash($rec->{$valueFld});
                $form->setDefault('extValue', $value);
            }
        }
    }
    
    
    /**
     * Записва стойностите 
     * 
     * @param int $registerId
     * @param mixed $extValue
     * 
     * @return mixed
     */
    public static function recordValues($registerId, $extValue)
    {
        $type = ztm_Registers::fetchField($registerId, 'type');
        
        // Записва стойността в помощния модел при нужда
        if(in_array($type, array('text', 'object', 'array'))){
            $hash = md5(serialize($extValue));
            $value = $hash;
            
            $exValue = ztm_LongValues::fetchField("#hash = '{$hash}'", 'value');
            if(!isset($exValue)){
                $longRec = (object)array('hash' => $hash, 'value' => serialize($extValue));
                ztm_LongValues::save($longRec);
            }
        } else {
            $value = $extValue;
        }
        
        return $value;
    }
}