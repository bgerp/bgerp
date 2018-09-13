<?php


/**
 * Драйвер за електромер Eastrongroup SDM120
 *
 *
 * @category  bgerp
 * @package   unipi
 *
 * @author    Orlin Dimitrov <orlin369@gmail.com>
 * @copyright 2018 POLYGONTeam OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Електромер SDM120
 *
 * @see       https://bg-etech.de/download/manual/SDM120CT-Modbus.pdf
 */
class eastron_SDM120 extends sens2_ioport_Abstract
{
    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = 'ModBus';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Електромер SDM120';
    
    
    /**
     * Интерфейс за входно-изходен порт
     */
    public $intefaces = 'sens2_ioport_Intf';
    
    
    /**
     * Колко максимално порта могат да се вържат на един слот
     */
    public $maxUnitPerSlot = 250;
    
    
    /**
     * Описание на входовете
     */
    public $inputs = array(
        'Voltage' => array('caption' => 'Напрежение', 'uom' => 'V', 'addr' => array(0, 1)),
        'Current' => array('caption' => 'Ток', 'uom' => 'A', 'addr' => array(6,7)),
        'ActivePower' => array('caption' => 'Активна енергия', 'uom' => 'W', 'addr' => array(12,13)),
        'ApparentPower' => array('caption' => 'Явна енергия', 'uom' => 'VA', 'addr' => array(18,19)),
        'ReactivePower' => array('caption' => 'Реактивна енергия', 'uom' => 'WAr', 'addr' => array(24,25)),
        'PowerFactor' => array('caption' => 'Косинус Фи', 'uom' => 'Deg', 'addr' => array(30,31)),
        'Frequency' => array('caption' => 'Честота', 'uom' => 'Hz', 'addr' => array(70,71)),
        'ImportActiveEnergy' => array('caption' => 'Вх. акт. енергия', 'uom' => 'KWh', 'addr' => array(72,73)),
        'ExportActiveEnergy' => array('caption' => 'Изх. акт. енергия', 'uom' => 'KWh', 'addr' => array(74,75)),
        'ImportReactiveEnergy' => array('caption' => 'Вх. реакт. енергия', 'uom' => 'kvarh', 'addr' => array(76,77)),
        'ExportReactiveEnergy' => array('caption' => 'Изх. реакт. енергия', 'uom' => 'kvarh', 'addr' => array(78,79)),
    );
    
    
    /**
     * Връша информация за портовете, които това устройство показва
     *
     * @return array масив с обекти имащи следните полета:
     *               o subname  - подчинено на променливата, може да е ''
     *               о suffix   - стринг, който се изписва след променливата (%, V, W, ...)
     *               o prefix   - стринг, който се изписва преди променливата
     *               о options  - масив с възможни стоийнисти
     *               о min      - минимална стойност
     *               о max      - максимална стойност
     *               о readable - дали порта може да се чете
     *               о writable - дали порта може да се записва
     */
    public function discovery()
    {
        $res = array();
        if (is_array($this->inputs)) {
            foreach ($this->inputs as $key => $inputInfo) {
                $portInfo = array(
                    'name' => $key,
                    'uom' => $inputInfo['uom'],
                    'readable' => true,
                );
                if (is_array($this->driverRec->{$key})) {
                    foreach ($this->driverRec->{$key} as $prop => $val) {
                        $portInfo[$prop] = $val;
                    }
                }
                $res[] = (object) $portInfo;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('unitId', 'int(min=0,max=255)', 'caption=Unit ID,mandatory');
        parent::addFields($fieldset);
    }
    
    
    /**
     * Връща допълнителен идентификатор за порта, който е базиран на данните в драйвера
     */
    public function getPortIdent($rec)
    {
        return $rec->unitId;
    }
    
    
    /**
     * Конвертира извлечената стойност в масив от Име => Стойност
     */
    public function convert($data, $name, $pRec)
    {
        $addr = $this->inputs[$name]['addr'];
        
        $v1 = $data[$addr[0]];
        $v2 = $data[$addr[1]];
        
        $res = self::registersToFlaot($v1, $v2);
        
        return $res;
    }
    
    
    /**
     * Convert two registers to float.
     *
     * @param int $reg_value1 Register 1.
     * @param int $reg_value2 Register 2.
     *
     * @return float Value from two registers.
     */
    private static function registersToFlaot($reg_value1, $reg_value2)
    {
        /** @var array Packet binary data. $bin_data */
        $bin_data = null;
        
        
        /** @var float Unpacked float value. $value */
        $value = NAN;
        if (isset($reg_value1)) {
            if (isset($reg_value2)) {
                $bin_data = pack('nn', $reg_value1, $reg_value2);
            }
        }
        if ($bin_data != null) {
            $value = unpack('G', $bin_data)[1];
        }
        
        return $value;
    }
}
