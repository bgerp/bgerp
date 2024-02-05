<?php


/**
 * Драйвер за наблюдение стойностите на ZTM
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Наблюдение на ZTM
 */
class ztm_SensMonitoring extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Наблюдение на ZTM';
    
    
    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_ControllerIntf';


    /**
     * Входове на контролера
     */
    public $inputs = array(
        'kWhImport' => array('caption' => 'Входяща енергия', 'uom' => 'kWh', 'logPeriod' => 3600),
        'coldWater' => array('caption' => 'Студена вода', 'uom' => 'm³', 'logPeriod' => 3600),
        'hotWater' => array('caption' => 'Топла вода', 'uom' => 'm³', 'logPeriod' => 3600),
        'airTempLower' => array('caption' => 'Температура долу', 'uom' => 'ºC', 'logPeriod' => 3600, 'readPeriod' => 60),
        'airTempCent' => array('caption' => 'Температура център', 'uom' => 'ºC', 'logPeriod' => 3600, 'readPeriod' => 60),
        'airTempUpper' => array('caption' => 'Температура горе', 'uom' => 'ºC', 'logPeriod' => 3600, 'readPeriod' => 60),
        'ventLowerFan' => array('caption' => 'Вентилатор долу', 'uom' => '%', 'logPeriod' => 0, 'readPeriod' => 60),
        'ventUpperFan' => array('caption' => 'Вентилатор горе', 'uom' => '%', 'logPeriod' => 0, 'readPeriod' => 60),
    );


    /**
     * Мапинг на входовете и регистрите
     */
    protected $inputRegistryMaps = array('kWhImport|ImportActiveEnergy' => 'monitoring.pa.measurements',
                                         'coldWater|CumulativeTraffic' => 'monitoring.cw.measurements',
                                         'hotWater|CumulativeTraffic' => 'monitoring.hw.measurements',
                                         'airTempLower' => 'hvac.air_temp_lower_1.value',
                                         'airTempCent' => 'hvac.air_temp_cent_1.value',
                                         'airTempUpper' => 'hvac.air_temp_upper_1.value',
                                         'ventLowerFan' => 'vent.lower_1.fan.speed',
                                         'ventUpperFan' => 'vent.upper_1.fan.speed');


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $res = array();

        $dName = $this->driverRec->name;
        $dId = ztm_Devices::fetchField(array("#name = '[#1#]'", $dName));
        if (!$dId) {

            ztm_Devices::logNotice("Няма регистрирано устройство с име {$dName}");

            return $res;
        }

        foreach ($this->inputRegistryMaps as $iVal => $registry) {
            list($input, $reg) = explode('|', $iVal);
            if ($inputs[$input]) {
                $regId = ztm_Registers::fetchField(array("#name = '[#1#]'", $registry));
                $rQuery = ztm_RegisterValues::getQuery();
                $rQuery->where(array("#registerId = '[#1#]'", $regId));
                $rQuery->where(array("#deviceId = '[#1#]'", $dId));
                $rQuery->show('deviceId, value');

                while ($rRec = $rQuery->fetch()) {
                    $val = ztm_LongValues::getValueByHash($rRec->value);
                    // Ако е подадена стойност, търсим в масива иначе приемаме, че е стринг
                    if ($reg) {
                        $valArr = @json_decode($val);
                        if ($valArr === false) {
                            ztm_RegisterValues::logErr('Невалидна стойност на регистъра', $rRec);

                            continue;
                        }

                        if (!isset($valArr)) {
                            ztm_RegisterValues::logWarning('Празна стойност на регистъра', $rRec);

                            continue;
                        }

                        if (!is_array($valArr)) {
                            ztm_RegisterValues::logWarning('В регистъра се очаква валиден масив', $rRec);

                            continue;
                        }

                        if (empty($valArr)) {
                            ztm_RegisterValues::logWarning('В регистъра се очаква попълнен масив', $rRec);

                            continue;
                        }
                        $valObj = end($valArr);
                        $res[$input] = $valObj->{$reg};
                    } else {
                        $res[$input] = $val;
                    }
                }
            }
        }

        return $res;
    }


    /**
     * Добавя нов сензор
     *
     * @param string $name
     *
     * @return int
     */
    public static function addSens($name)
    {
        $me = get_called_class();

        $dInst = cls::get($me);
        expect($dInst);
        $dId = $dInst->getClassId();
        expect($dId);

        $nRec = new stdClass();
        $nRec->name = $name;
        $nRec->driver = $dId;
        $nRec->state = 'active';

        return sens2_Controllers::save($nRec, null, 'IGNORE');
    }
}
