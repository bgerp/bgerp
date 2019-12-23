<?php


/**
 * Универсален драйвер за Modbus TCP/IP контролер
 *
 *
 * @category  bgerp
 * @package   modbus
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 */
class modbus_GenericTCP extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Modbus TCP/IP';
    
    
    /**
     * Без автоматични полета във формата
     */
    public $notExpandForm = true;
    
    
    /**
     * Описание на входовете
     *
     */
    public $inputs = array();
    
    
    /**
     * Съответствие на дължина, поред типа
     */
    public $getQuantityByType = array(
        'float' => 2,
        'int' => 2
    );
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_ControllerIntf
     *
     * @param core_Form
     */
    public function prepareConfigForm($form)
    {
        $form->FNC('ip', 'ip', 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=502');
        $form->FNC('unit', 'int(5)', 'caption=Unit,hint=Unit, input, mandatory,value=1');
        $form->FNC('ports', 'table(columns=name|uom|address|type|read|write|scale,captions=Параметър|Мярка|Адрес|Тип|Обновяване|Логване|Скалиране,widths=12em|4em|6em|6em|5em|5em|6em,read_opt=1 мин|2 мин|5 мин|10 мин|20 мин|30 мин|60 мин,write_opt=1 мин|2 мин|5 мин|10 мин|20 мин|30 мин|60 мин,mandatory=name|type,type_opt=' . implode('|', array_keys($this->getQuantityByType)) . ')', 'caption=Входно-изходни параметри->Портове,input');
        
        // Стойности по подразбиране
        $form->setDefault('port', 502);
        $form->setDefault('unit', 1);
    }
    
    
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $ports = $this->discovery();
        
        $driver = new modbus_Driver();
        
        $driver->ip = $config->ip;
        $driver->port = $config->port;
        $driver->unit = $config->unit;
        $driver->type = 'words';
        
        $res = array();
        
        $portsArr = type_Table::toArray($this->driverRec->config->ports);
        foreach ($portsArr as $port) {
            expect($this->getQuantityByType[$port->type]);
            
            $values = $driver->read($port->address, $this->getQuantityByType[$port->type]);
            
            if ($values) {
                $values = array_values($values);
                $method = 'convert' . $port->type;
                $res[$port->name] = $this->{$method}($values, $port->type);
            }
        }
        
        if (empty($res)) {
            
            return "Нищо не беше обновено от {$config->ip}:{$config->port}";
        }
        
        return $res;
    }
    
    
    /**
     * Конвертира float
     */
    public function convertFloat($vArr)
    {
        return self::registersToFloat($vArr);
    }
    
    
    /**
     * Конверира int стойност
     */
    public function convertInt($vArr)
    {
        $res = $vArr[0] + $vArr[1] * 65536;
        
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
    protected static function registersToFloat($vals, $f = 'f', $u = 'vv')
    {
        /** @var array Packet binary data. $bin_data */
        $bin_data = null;
        
        
        /** @var float Unpacked float value. $value */
        $value = NaN;
        if (isset($vals[0])) {
            if (isset($vals[1])) {
                $bin_data = pack($u, $vals[0], $vals[1]);
            }
        }
        if ($bin_data != null) {
            $value = unpack($f, $bin_data)[1];
        }
        
        return $value;
    }
    
    
    /**
     * Връша информация за наличните портове
     *
     * @return array масив с обекти имащи следните полета:
     *               o name     - име на променливата
     *               о slot     - име на слота
     *               о uom      - стринг, който се изписва след променливата (%, V, W, ...)
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
        $portsArr = type_Table::toArray($this->driverRec->config->ports);
        foreach ($portsArr as $port) {
            $res[] = (object) array(
                'name' => $port->name,
                'uom' => $port->uom,
                'readPeriod' => 60 * (int) $port->read,
                'logPeriod' => 60 * (int) $port->write,
                'readable' => true,
                'scale' => $port->scale,
            );
        }
        
        return $res;
    }
}
