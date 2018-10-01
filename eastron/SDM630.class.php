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
class eastron_SDM630 extends modbus_IOPort
{
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Електромер SDM630';
    
    
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
        'Phase1Power' => array('W', array(12, 13)),
        'Phase2Power' => array('W', array(14, 15)),
        'Phase3Power' => array('W', array(16, 17)),
        'Phase1VoltAmps' => array('VA', array(18, 19)),
        'Phase2VoltAmps' => array('VA', array(20, 21)),
        'Phase3VoltAmps' => array('VA', array(22, 23)),
        'Phase1VoltAmpsReactive' => array('VAr', array(24, 25)),
        'Phase2VoltAmpsReactive' => array('VAr', array(26, 27)),
        'Phase3VoltAmpsReactive' => array('VAr', array(28, 29)),
        'Phase1PowerFactor' => array('Deg', array(30, 31)),
        'Phase2PowerFactor' => array('Deg', array(32, 33)),
        'Phase3PowerFactor' => array('Deg', array(34, 35)),
        'Phase1PhaseAngle' => array('Deg', array(36, 37)),
        'Phase2PhaseAngle' => array('Deg', array(38, 39)),
        'Phase3PhaseAngle' => array('Deg', array(40, 41)),
        'AverageLineToNeutralVolts' => array('V', array(42, 43)),
        'AverageLineCurrent' => array('A', array(46, 47)),
        'SumOfLineCurrents' => array('A', array(48, 49)),
        'TotalSystemPower' => array('W', array(52, 53)),
        'TotalSystemVoltAmps' => array('VA', array(56, 57)),
        'TotalSystemVAr' => array('VA', array(60, 61)),
        'TotalSystemPowerFactor' => array('Deg', array(62, 63)),
        'TotalSystemPhaseAngle' => array('Deg', array(66, 67)),
        'FrequencyOfSupplyVoltages' => array('Hz', array(70, 71)),
        'TotalImportkWh' => array('kWh', array(72, 73)),
        'TotalExportkWh' => array('kWh', array(74, 75)),
        'TotalImportkVAarh' => array('kVArh', array(76, 77)),
        'TotalExportkVAarh' => array('kVArh', array(78, 79)),
        'TotalVAh' => array('kVAh', array(80, 81)),
        'Ah' => array('Ah', array(82, 83)),
        'TotalSystemPowerDemand' => array('VA', array(84, 85)),
        'MaximumTotalSystemPowerDemand' => array('VA', array(86, 87)),
        'TotalSystemVaDemand' => array('VA', array(100, 101)),
        'MaximumTotalSystemVADemand' => array('VA', array(102, 103)),
        'NeutralCurrentDemand' => array('A', array(104, 105)),
        'MaximumNeutralCurrentDemand' => array('A', array(106, 107)),
        'Line1ToLine2Volts' => array('V', array(200, 201)),
        'Line2ToLine3Volts' => array('V', array(202, 203)),
        'Line3ToLine1Volts' => array('V', array(204, 205)),
        'AverageLineToLineVolts' => array('V', array(206, 207)),
        'NeutralCurrent' => array('A', array(224, 225)),
        'Phase1L/NVoltsThd' => array('%', array(234, 235)),
        'Phase2L/NVoltsThd' => array('%', array(236, 237)),
        'Phase3L/NVoltsThd' => array('%', array(238, 239)),
        'Phase1CurrentThd' => array('%', array(240, 241)),
        'Phase2CurrentThd' => array('%', array(242, 243)),
        'Phase3CurrentThd' => array('%', array(244, 245)),
        'AverageLineToNeutralVoltsTHD' => array('%', array(248, 249)),
        'AverageLineCurrentTHD' => array('%', array(250, 251)),
        'Phase1CurrentDemand' => array('A', array(257, 258)),
        'Phase2CurrentDemand' => array('A', array(259, 260)),
        'Phase3CurrentDemand' => array('A', array(261, 262)),
        'MaximumPhase1CurrentDemand' => array('A', array(263, 264)),
        'MaximumPhase2CurrentDemand' => array('A', array(265, 266)),
        'MaximumPhase3CurrentDemand' => array('A', array(267, 268)),
        'Line1ToLine2VoltsTHD' => array('%', array(333, 334)),
        'Line2ToLine3VoltsTHD' => array('%', array(335, 336)),
        'Line3ToLine1VoltsTHD' => array('%', array(337, 338)),
        'AverageLineToLineVoltsTHD' => array('%', array(339, 340)),
        'TotalkWh' => array('kWh', array(341, 342)),
        'TotalkVArh' => array('kVArh', array(343, 344)),
        'L1ImportkWh' => array('kWh', array(345, 346)),
        'L2ImportkWh' => array('kWh', array(347, 348)),
        'L3ImportkWh' => array('kWh', array(349, 350)),
        'L1ExportkWh' => array('kWh', array(351, 352)),
        'L2ExportkWh' => array('kWh', array(353, 354)),
        'L3ExportkWh' => array('kWh', array(355, 356)),
        'L1TotalkWh' => array('kWh', array(357, 358)),
        'L2TotalkWh' => array('kWh', array(359, 360)),
        'L3TotalkWh' => array('kWh', array(361, 362)),
        'L1ImportkVArh' => array('kVArh', array(363, 364)),
        'L2ImportkVArh' => array('kVArh', array(365, 366)),
        'L3ImportkVArh' => array('kVArh', array(367, 368)),
        'L1ExportkVArh' => array('kVArh', array(369, 370)),
        'L2ExportkVArh' => array('kVArh', array(371, 372)),
        'L3ExportkVArh' => array('kVArh', array(373, 374)),
        'L1TotalkVArh' => array('kVArh', array(375, 376)),
        'L2TotalkVArh' => array('kVArh', array(377, 378)),
        'L3TotalkVArh' => array('kVArh', array(379, 380)),
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
