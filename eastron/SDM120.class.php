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
    const SLOT_TYPES = 'RS485';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Електромер SDM120';
    
    
    /**
     * Интерфейс за входно-изходен порт
     */
    public $intefaces = 'sens2_ioport_Intf';
    
    
    /**
     * Описание на входовете
     */
    public $inputs = array(
        'Voltage' => array('caption' => 'Напрежение', 'uom' => 'V', 'addr' => 0),
        'Current' => array('caption' => 'Ток', 'uom' => 'A', 'addr' => 6),
        'ActivePower' => array('caption' => 'Активна енергия', 'uom' => 'W', 'addr' => 12),
        'ApparentPower' => array('caption' => 'Явна енергия', 'uom' => 'VA', 'addr' => 18),
        'ReactivePower' => array('caption' => 'Реактивна енергия', 'uom' => 'WAr', 'addr' => 24),
        'PowerFactor' => array('caption' => 'Косинус Фи', 'uom' => 'Deg', 'addr' => 30),
        'Frequency' => array('caption' => 'Честота', 'uom' => 'Hz', 'addr' => 70),
        'ImportActiveEnergy' => array('caption' => 'Вх. акт. енергия', 'uom' => 'KWh', 'addr' => 72),
        'ExportActiveEnergy' => array('caption' => 'Изх. акт. енергия', 'uom' => 'KWh', 'addr' => 74),
        'ImportReactiveEnergy' => array('caption' => 'Вх. реакт. енергия', 'uom' => 'kvarh', 'addr' => 76),
        'ExportReactiveEnergy' => array('caption' => 'Изх. реакт. енергия', 'uom' => 'kvarh', 'addr' => 78),
    );
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_ControllerIntf
     *
     * @param core_Form
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FNC('unit', 'int(5)', 'caption=Unit,hint=Unit, input, mandatory,value=2');
    }
    
    
    /**
     * Прочита стойностите на портовете, за които отговаря от изброените
     */
    public function read($controller, $rec, $ports)
    {
        // Вземи данните за регистрите от източника.
        $sdm120RegistersData = $controller->readPorts($rec->slot, array('unit' => $config->unit, 'registers' => self::getRegistersIDs()));
        
        // Създай уред и подай данните от регистрите.
        $sdm120 = new SDM120($sdm120RegistersData);
        
        // Прочитаме изчерпаната до сега информация.
        $res['Voltage'] = $sdm120->getVoltage();
        $res['Current'] = $sdm120->getCurrent();
        $res['ActivePower'] = $sdm120->getActivePower();
        $res['ApparentPower'] = $sdm120->getApparentPower();
        $res['ReactivePower'] = $sdm120->getReactivePower();
        $res['PowerFactor'] = $sdm120->getPowerFactor();
        $res['Frequency'] = $sdm120->getFrequency();
        $res['ImportActiveEnergy'] = $sdm120->getImportActiveEnergy();
        $res['ExportActiveEnergy'] = $sdm120->getExportActiveEnergy();
        $res['ImportReactiveEnergy'] = $sdm120->getImportActiveEnergy();
        $res['ExportReactiveEnergy'] = $sdm120->getExportReactiveEnergy();
        
        if (empty($addresses)) {
            
            return "Грешка при четене от {$config->ip}";
        }
        
        return $res;
    }
}
