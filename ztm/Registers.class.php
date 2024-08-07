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
 *
 * @title     Дефинирани регистри в Zontromat
 */
class ztm_Registers extends core_Master
{
    public $title = 'Дефинирани регистри в Zontromat';
    
    
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
    public $canList = 'ztm, ceo';
    public $canSingle = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'ztm, ceo';
    
    
    /**
     * Активните регистри след импортиране
     */
    protected $activeRegistersArr;
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting, plg_Search';
    
    
    /**
     *
     * @var string
     */
    public $listFields = 'id, name, type, format, range, plugin, scope, default, profileIds, description';


    /**
     * @var string
     */
    public $searchFields = 'name, type, range, plugin, scope, default, profileIds, description, format';



    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'powerUser';


    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име');
        $this->FLD('type', 'enum(int,bool,float,str,json)', 'caption=Тип');
        $this->FLD('range', 'text', 'caption=Диапазон');
        $this->FLD('plugin', 'varchar(64)', 'caption=Модул');
        $this->FLD('scope', 'enum(system=Система, device=Устройство, global=Глобално, both=И двете)', 'caption=Обхват, oldFieldName=priority');
        $this->FLD('default', 'text', 'caption=Дефолтна стойност');
        $this->FLD('profileIds', 'keylist(mvc=ztm_Profiles, select=name)', 'caption=Профили');
        $this->FLD('description', 'text', 'caption=Описание на регистъра');
        $this->FLD('format', 'enum(,temperature=Температура, datalen_byte=Данни, time_sec=Време (s), time_min=Време (m), time_hour=Време (h))', 'caption=Формат');

        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        if (ztm_Setup::get('FORCE_REGISTRY_SYNC')) {
            $csv = @file_get_contents('https://raw.githubusercontent.com/bgerp/ztm/master/registers.csv');

            if (trim($csv)) {
                if ($fPath = getFullPath('ztm/csv/Registri.csv')) {
                    if (md5($csv) != md5(@file_get_contents($fPath))) {
                        @file_put_contents($fPath, $csv);

                        sleep(1);
                    }
                }
            }
        }

//         $fields = array(
//             0 => 'name',
//             1 => 'type',
//             2 => 'range',
//             3 => 'plugin',
//             4 => 'scope',
//             5 => 'default',
//             6 => 'description',
//             7 => 'profiles',
//             8 => 'format',
//         );

        $res = cls::get('ztm_Setup')->callMigrate('profilesToNotes2430', 'ztm');

        $cntObj = csv_Lib::importOnce($this, 'ztm/csv/Registri.csv');
        $res .= $cntObj->html;

        $res .= cls::get('ztm_Setup')->callMigrate('fixProfiles2430', 'ztm');

        return $res;
    }

    
    /**
     * Какъв наш тип отговаря на техния
     *
     * @param int  $registerId
     * @param mixed $value
     *
     * @return core_Type
     */
    public static function getOurType($registerId, &$value = null)
    {
        $rec = self::fetch($registerId);

        if ($rec->format && isset($value)) {
            $decimals = null;
            $roundToSec = 0;
            if ($rec->format == 'time_min') {
                $roundToSec = 60;
            } elseif ($rec->format == 'time_hour') {
                $roundToSec = 3600;
            }

            $defType = null;
            switch ($rec->format) {
                case 'temperature':
                    $defType = 'physics_TemperatureType';
                    $decimals = 1;
                    break;
                case 'datalen_byte':
                    $defType = 'fileman_FileSize';
                    break;
                case 'time_sec':
                case 'time_min':
                case 'time_hour':
                    if ($roundToSec) {
                        if ($value < $roundToSec/2) {
                            $value = 0;
                        }

                        if ($value) {


                            $secs = $value % $roundToSec;

                            if ($secs >= ($roundToSec/2)) {
                                $secs = $roundToSec - $secs;
                            } else {
                                $secs *= -1;
                            }
                            $value = $value + $secs;
                        }
                    }

                    $defType = 'time';
                    break;
                default:
                    wp('Грешен формат', $rec, $value);
                    break;
            }

            if (isset($defType)) {
                $type = core_Type::getByName($defType);
                if (isset($decimals)) {
                    $type->params['decimals'] = $decimals;
                }

                return $type;
            }
        }

        switch ($rec->type) {
            case 'int':
                $ourType = 'Int';
                break;
            case 'float':
                $ourType = 'Double(smartRound)';
                break;
            case 'bool':
                $ourType = 'enum(true=Да,false=Не)';
                break;
            case 'str':
                $ourType = 'varchar';
                break;
            case 'json':
                $ourType = 'JSON';
                break;
            default:
                $ourType = 'text';
                break;
        }

        $oType = core_Type::getByName($ourType);
        if ($rec->type == 'json') {
            $oType->params['hideLevel'] = 0;
        }

        return $oType;
    }
    
    
    /**
     * Добавя функционално поле за въвеждане на допустима стойност
     *
     * @param core_Form   $form
     * @param string|null $registerFld
     * @param string|null $valueFld
     *
     * @return void
     */
    public static function extendAddForm($form, $registerFld = 'registerId', $valueFld = 'value')
    {
        $rec = &$form->rec;
        
        if (isset($rec->{$registerFld})) {
            $form->FLD('extValue', ztm_Registers::getOurType($rec->{$registerFld}), 'caption=Стойност,mandatory,class=w50');
            $rRec = ztm_Registers::fetch($rec->{$registerFld});
            
            if (trim($rRec->range)) {
                if (strpos($rRec->range, '/') !== false) {
                    list($min, $max) = explode('/', $rRec->range);
                    if (strlen($min)) {
                        $form->fields['extValue']->type->params['min'] = $min;
                    }
                    if (strlen($max)) {
                        $form->fields['extValue']->type->params['max'] = $max;
                    }
                } else {
                    $type = ztm_Registers::fetchField($rec->{$registerFld}, 'type');
                    if ($type != 'bool') {
                        $sArr = explode('|', $rRec->range);
                        $sArr = arr::make($sArr, true);
                        $form->setOptions('extValue', $sArr);
                    }
                }
            }
            
            if (!empty($rec->{$valueFld})) {
                $value = ztm_LongValues::getValueByHash($rec->{$valueFld});
                $form->setDefault('extValue', $value);
            }
        }
    }
    
    
    /**
     * Обработва стойността, ако е от нескаларен тип записва я в помощен модел
     * подменяйки я с нейния хеш
     *
     * @param int   $registerId
     * @param mixed $extValue
     *
     * @return mixed
     */
    public static function recordValue($registerId, $extValue)
    {
        $type = ztm_Registers::fetchField($registerId, 'type');
        
        if ($type == 'json') {
            
            // Ако типа е json, но стойността не е подсигуряваме се да е json
            $extValue = (str::isJson($extValue)) ? $extValue : json_encode($extValue);
        } elseif (in_array($type, array('int', 'float'))) {
            
            // Ако е число, проверка дали е подадена валидна числова стойност
            $Double = core_Type::getByName('double');
            if ($Double->fromVerbal($extValue) === false) {
                throw new core_exception_Expect("Въведената стойност '{$extValue}' трябва да е число|*!", 'Несъответствие');
            }
        }
        
        // Не бива до тук да стигат нескаларни стойностти
        if (isset($extValue) && !is_scalar($extValue)) {
            wp($extValue, $registerId, $type);
            $extValue = serialize($extValue);
            
            throw new core_exception_Expect("Въведената стойност '{$extValue}' не е скаларна|*!", 'Несъответствие');
        }
        
        // Записва стойността в помощния модел при нужда
        if ($type == 'json' || ($type == 'str' && strlen($extValue) > 32)) {
            $hash = md5(serialize($extValue));
            $value = $hash;
            
            $existingValue = ztm_LongValues::fetchField("#hash = '{$hash}'", 'value');
            if (!isset($existingValue)) {
                $longRec = (object) array('hash' => $hash, 'value' => $extValue);
                
                ztm_LongValues::save($longRec);
            }
        } else {
            $value = $extValue;
        }
        
        if ($type == 'bool') {
            
            if (!is_string($value)) {
                if ($value) {
                    $value = 'true';
                } else {
                    $value = 'false';
                }
            }
        }
        
        return $value;
    }
    
    
    /**
     * Преди импортиране на запис
     *
     * @param ztm_Registers $mvc
     * @param stdClass      $rec
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
//         $rec->default = trim($rec->default, '"');
        $rec->state = 'active';
        if ($rec->profiles) {
            $pArr = explode('|', $rec->profiles);
            $pIdArr = array();
            foreach ($pArr as $pSysId) {
                $pId = ztm_Profiles::getIdFromSysId($pSysId);
                if ($pId) {
                    $pIdArr[$pId] = $pId;
                }
            }

            $rec->profileIds = type_Keylist::fromArray($pIdArr);
        }
    }
    
    
    /**
     * След импортиране на запис
     *
     * @param ztm_Registers $mvc
     * @param stdClass      $rec
     */
    public function on_AfterImportRec($mvc, $rec)
    {
        setIfNot($mvc->activeRegistersArr, array());
        $mvc->activeRegistersArr[$rec->id] = $rec->id;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Първичния ключ на направения запис
     * @param stdClass     $rec    Всички полета, които току-що са били записани
     * @param string|array $fields Имена на полетата, които sa записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if (isset($rec->state) && $rec->state != 'active') {
            ztm_RegisterValues::delete(array("#registerId = '[#1#]'", $rec->id));
        }
    }
    
    
    /**
     * След приключване на процесите
     *
     * @param ztm_Registers $mvc
     */
    public static function on_Shutdown($mvc)
    {
        parent::on_Shutdown($mvc);
        
        // Деактивираме всички регистри, които не се добавят при импорт на `csv`
        if (is_array($mvc->activeRegistersArr)) {
            $query = $mvc->getQuery();
            $query->notIn('id', $mvc->activeRegistersArr);
            $query->where("#state = 'active'");
            
            $query->show('state');
            while ($rec = $query->fetch()) {
                $rec->state = 'closed';
                
                $mvc->save($rec, 'state');
            }
        }
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');

        // Сортиране на записите по num
        $data->query->orderBy('name', 'ASC');
    }
}
