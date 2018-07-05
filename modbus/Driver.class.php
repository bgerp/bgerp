<?php



/**
 * Драйвер за Modbus IP устройство
 *
 *
 * @category  vendors
 * @package   modbus
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class modbus_Driver extends core_BaseClass
{
    
    
    /**
     * IP на устройството
     */
    public $ip;
    
    
    /**
     * Порт за Modbus протокола
     */
    public $port = 502;
    
    
    /**
     * id на устройството
     */
    public $id;
    
    
    /**
     * Чете от посочения адрес определен брой данни
     */
    public function read($startAddr, $quantity)
    {
        $Plc = $this->getAndInitPlc();
        
        $values = $Plc->ReadModbus($startAddr, $quantity);     // Lecture de 50 mots a partir de 400001
        $Plc->ModClose();
        
        return $values;
    }
    
    
    /**
     * Чете група от регистри
     */
    public function readArr($regArr)
    {
        $Plc = $this->getAndInitPlc();
        
        $values = $Plc->ReadArrRegs($regArr);
        
        return $values;
    }
    
    
    /**
     * Създава и зарежда комуникатора с PLC-то
     */
    public function getAndInitPlc()
    {
        require_once(dirname(__FILE__) . '/_lib/ClassModbusTcp.php');
        
        $Plc = new ModbusTcp;
        
        if ($this->ip{0} == 's') {
            $Plc->SetSimulation();
        }
        
        $Plc->SetAdIpPLC($this->ip);
        
        $Plc->PortIpPLC = $this->port;
        
        $Plc->Unit = $this->unit;
        
        if ($this->type == 'float') {
            $Plc->SetTypeFloat();
        } elseif ($this->type == 'double') {
            $Plc->SetTypeDouble();
        }
        
        if ($this->mode == 'debug') {
            $Plc->SetDebug();
        }
        
        if ($this->mode == 'simulation') {
            $Plc->SetSimulation();
        }
        
        // $Plc->BridgeRoute = array( 52, 11, 0, 0, 0 );  // Avec routage dynamique si passerelle 174CEV20030
        
        return $Plc;
    }
    
    
    /**
     * Инициализира драйвера
     */
    public function init($params = array())
    {
        $this->unit = 0;
        
        parent::init((array) $params);
    }
}
