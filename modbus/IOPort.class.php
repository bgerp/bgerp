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
 * @title     Електромер SDM630
 *
 * @see       https://bg-etech.de/download/manual/SDM120CT-Modbus.pdf
 */
class modbus_IOPort extends sens2_ioport_Abstract
{
    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = 'ModBus';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Modbus Port';
    
    
    /**
     * Интерфейс за входно-изходен порт
     */
    public $intefaces = 'sens2_ioport_Intf';
    
    
    /**
     * Колко максимално порта могат да се вържат на един слот
     */
    public $maxUnitPerSlot = 250;
    
    
    /**
     * Описание на портовете на устройството
     */
    public $inputs = array();


    /**
     * Масив със стойности в описанието на портовете, които не се променят
     */
    public $staticInfo;
    

    /**
     * Съответствие между полетата в описанието на порта и ключовете в описанието
     */
    public $keyMap;
    

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
            self::arraysExpand($this->inputs, $this->staticInfo, $this->keyMap);

     
            foreach ($this->inputs as $name => $portInfo) {
                $port = array(
                    'name' => $name,
                    'uom' => $portInfo['uom'],
                    'readable' => $portInfo['readable'],
                    'writable' => $portInfo['writable'],
                );
                $res[] = (object) $port;
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
        self::arraysExpand($this->inputs, $this->staticInfo, $this->keyMap); 
        $portInfo = $this->inputs[$name];
        $addr = $portInfo['addr'];
        $type = $portInfo['type'];
 
        if(is_int($addr)) {
            $addr = array($addr);
        }

        foreach($addr as $a) {
            $vals[] = $data[$a];
        }
        
        $method = 'registersTo' . $type;
        
        expect(method_exists($this, $method));

        $res = self::{$method}($vals);
        
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
    protected static function registersToFloat($vals)
    {
        /** @var array Packet binary data. $bin_data */
        $bin_data = null;
        
        
        /** @var float Unpacked float value. $value */
        $value = NAN;
        if (isset($vals[0])) {
            if (isset($vals[1])) {
                $bin_data = pack('nn', $vals[0], $vals[1]);
            }
        }
        if ($bin_data != null) {
            $value = unpack('G', $bin_data)[1];
        }
        
        return $value;
    }


    /**
     * Разширява масив от масиви, с посочения статичен масив и добавя именовани ключове на цифровите индекси
     */
    public static function arraysExpand(&$arrays, &$static, &$map)
    {  
        if(is_array($static) || is_array($map)) {

            foreach($arrays as &$arr) {
                if(is_array($static)) {
                    $arr = $arr + $static; 
                }
                if(is_array($map)) {
                    foreach($map as $id => $key) {
                        $arr[$key] = $arr[$id];
                    }
                }
            }
            $static = $map = null;
        }
    }
}
