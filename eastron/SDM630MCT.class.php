<?php


/**
 * Драйвер за електромер Eastrongroup SDM630MCT
 *
 *
 * @category  bgerp
 * @package   unipi
 *
 * @author    Orlin Dimitrov <or.dimitrov@polygonteam.com>
 * @copyright 2022 POLYGONTeam OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Електромер SDM630MCT
 *
 * @see       https://www.eastroneurope.com/images/uploads/products/protocol/SDM630MCT_MODBUS_Protocol_V1.7.pdf
 */
class eastron_SDM630MCT extends modbus_IOPort
{
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Електромер SDM630MCT';
    
    
    /**
     * Описание на портовете на устройството
     */
    public $inputs = array(
        'Phase1LineToNeutralVolts' => array('V', array(0, 1)),
        'Phase2LineToNeutralVolts' => array('V', array(2, 3)),
        'Phase3LineToNeutralVolts' => array('V', array(4, 5)),
        'Phase1Current' => array('A', array(6, 7)),
        'Phase2Current' => array('A', array(8, 9)),
        'Phase3Current' => array('A', array(10, 11)),
        'Phase1ActivePower' => array('W', array(12, 13)),
        'Phase2ActivePower' => array('W', array(14, 15)),
        'Phase3ActivePower' => array('W', array(16, 17)),
        'Phase1ApparentPower' => array('VA', array(18, 19)),
        'Phase2ApparentPower' => array('VA', array(20, 21)),
        'Phase3ApparentPower' => array('VA', array(22, 23)),
        'Phase1ReactivePower' => array('VAr', array(24, 25)),
        'Phase2ReactivePower' => array('VAr', array(26, 27)),
        'Phase3ReactivePower' => array('VAr', array(28, 29)),
        'Phase1PowerFactor(1)' => array('None', array(30, 31)),
        'Phase2PowerFactor(1)' => array('None', array(32, 33)),
        'Phase3PowerFactor(1)' => array('None', array(34, 35)),
        'Phase1PhaseAngle' => array('Degrees', array(36, 37)),
        'Phase2PhaseAngle' => array('Degrees', array(38, 39)),
        'Phase3PhaseAngle' => array('Degrees', array(40, 41)),
        'AverageLineToNeutralVolts' => array('V', array(42, 43)),
        'AverageLineCurrent' => array('A', array(46, 47)),
        'SumOfLineCurrents' => array('A', array(48, 49)),
        'TotalSystemPower' => array('W', array(52, 53)),
        'TotalSystemVoltAmps' => array('VA', array(56, 57)),
        'TotalSystemVAr' => array('VAr', array(60, 61)),
        'TotalSystemPowerFactor(1)' => array('None', array(62, 63)),
        'TotalSystemPhaseAngle' => array('Degrees', array(66, 67)),
        'FrequencyOfSupplyVoltages' => array('Hz', array(70, 71)),
        'TotalImportKWh' => array('kWh', array(72, 73)),
        'TotalExportKWh' => array('kWh', array(74, 75)),
        'TotalImportKVArh' => array('kVArh', array(76, 77)),
        'TotalExportKVArh' => array('kVArh', array(78, 79)),
        'TotalVAh' => array('kVAh', array(80, 81)),
        'Ah' => array('Ah', array(82, 83)),
        'TotalSystemPowerDemand(2)' => array('W', array(84, 85)),
        'MaximumTotalSystemPowerDemand(2)' => array('W', array(86, 87)),
        'TotalSystemVADemand' => array('VA', array(100, 101)),
        'MaximumTotalSystemVADemand' => array('VA', array(102, 103)),
        'NeutralCurrentDemand' => array('Amps', array(104, 105)),
        'MaximumNeutralCurrentDemand' => array('Amps', array(106, 107)),
        'TotalSystemReactivePowerDemand(2)' => array('VAr', array(108, 109)),
        'MaximumTotalSystemReactivePowerDemand(2)' => array('VAr', array(110, 111)),
        'Line1ToLine2Volts' => array('V', array(200, 201)),
        'Line2ToLine3Volts' => array('V', array(202, 203)),
        'Line3ToLine1Volts' => array('V', array(204, 205)),
        'AverageLineToLineVolts' => array('V', array(206, 207)),
        'NeutralCurrent' => array('A', array(224, 225)),
        'Phase1L/NVoltsTHD' => array('%', array(234, 235)),
        'Phase2L/NVoltsTHD' => array('%', array(236, 237)),
        'Phase3L/NVoltsTHD' => array('%', array(238, 239)),
        'Phase1CurrentTHD' => array('%', array(240, 241)),
        'Phase2CurrentTHD' => array('%', array(242, 243)),
        'Phase3CurrentTHD' => array('%', array(244, 245)),
        'AverageLineToNeutralVoltsTHD' => array('%', array(248, 249)),
        'AverageLineCurrentTHD' => array('%', array(250, 251)),
        'TotalSystemPowerFactor(1)' => array('Degrees', array(254, 255)),
        'Phase1CurrentDemand' => array('A', array(258, 259)),
        'Phase2CurrentDemand' => array('A', array(260, 261)),
        'Phase3CurrentDemand' => array('A', array(262, 263)),
        'MaximumPhase1CurrentDemand' => array('A', array(264, 265)),
        'MaximumPhase2CurrentDemand' => array('A', array(266, 267)),
        'MaximumPhase3CurrentDemand' => array('A', array(268, 269)),
        'Line1ToLine2VoltsTHD' => array('%', array(334, 335)),
        'Line2ToLine3VoltsTHD' => array('%', array(336, 337)),
        'Line3ToLine1VoltsTHD' => array('%', array(338, 339)),
        'AverageLineToLineVoltsTHD' => array('%', array(340, 341)),
        'TotalKWh(3)' => array('kWh', array(342, 343)),
        'TotalKVArh(3)' => array('kVArh', array(344, 345)),
        'L1ImportKWh' => array('kWh', array(346, 347)),
        'L2ImportKWh' => array('kWh', array(348, 349)),
        'L3ImportKWh' => array('kWh', array(350, 351)),
        'L1ExportKWh' => array('kWh', array(352, 353)),
        'L2ExportKWh' => array('kWh', array(354, 355)),
        'L3ExportKWh' => array('kWh', array(356, 357)),
        'L1TotalKWh' => array('kWh', array(358, 359)),
        'L2TotalKWh' => array('kWh', array(360, 361)),
        'L3TotalKWh' => array('kWh', array(362, 363)),
        'L1ImportKVArh' => array('kVArh', array(364, 365)),
        'L2ImportKVArh' => array('kVArh', array(366, 367)),
        'L3ImportKVArh' => array('kVArh', array(368, 369)),
        'L1ExportKVArh' => array('kVArh', array(370, 371)),
        'L2ExportKVArh' => array('kVArh', array(372, 373)),
        'L3ExportKVArh' => array('kVArh', array(374, 375)),
        'L1TotalKVArh' => array('kVArh', array(376, 377)),
        'L2TotalKVArh' => array('kVArh', array(378, 379)),
        'L3TotalKVArh' => array('kVArh', array(380, 381)),
        'ResettableTotalActiveEnergy' => array('kWh', array(384, 385)),
        'ResettableTotalReactiveEnergy' => array('kVArh', array(386, 387)),
        'ResettableImportActiveEnergy' => array('kWh', array(388, 389)),
        'ResettableExportActiveEnergy' => array('kWh', array(390, 391)),
        'ResettableImportReactiveEnergy' => array('kVArh', array(392, 393)),
        'ResettableExportReactiveEnergy' => array('kVArh', array(394, 395)),
    );
    

    /**
     * Съответствие между полетата в описанието на порта и ключовете в описанието
     */
    public $keyMap = array('uom', 'addr');


    /**
     * Масив със стойности в описанието на портовете, които не се променят
     */
    public $staticInfo = array('readable' => true, 'writable' => false, 'type' => 'float');
    

}
