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
class eastron_SDM120 extends modbus_IOPort
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Електромер SDM120';
    
    
    /**
     * Описание на портовете на устройството
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
        'TotalSystemPowerDemand' => array('caption' => 'Енергия за собствени нужди', 'uom' => 'W', 'addr' => array(86, 86)),
        'MaximumTotalSystemPowerDemand' => array('caption' => 'Максимална енергия за собствени нужди', 'uom' => 'W', 'addr' => array(88, 89)),
        'TotalActiveEnergy' => array('caption' => 'Сумарна активна енергия', 'uom' => 'kVArh', 'addr' => array(343, 344)),
    );


    /**
     * Масив със стойности в описанието на портовете, които не се променят
     */
    public $staticInfo = array('readable' => true, 'writable' => false, 'type' => 'float');
}
