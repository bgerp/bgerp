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
    );


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $res = array();

        if ($inputs['kWhImport']) {
            $regId = ztm_Registers::fetchField(array("#name = 'monitoring.pa.measurements'"));
            $rQuery = ztm_RegisterValues::getQuery();
            $rQuery->where(array("#registerId = '[#1#]'", $regId));
            $rQuery->show('deviceId, value');
            while ($rRec = $rQuery->fetch()) {
                $val = ztm_LongValues::getValueByHash($rRec->value);
                $valArr = @json_decode($val);
                if ($val === false) {
                    ztm_RegisterValues::logErr('Невалидна стойност на регистъра', $rRec);
                }
                // get last element of array
                $valObj = end($valArr);

                $res['kWhImport'] = $valObj->ImportActiveEnergy;
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
        $dId = $dInst->getClassId('sens2_ProtoDriver');

        $nRec = new stdClass();
        $nRec->name = $name;
        $nRec->driver = $dId;
        $nRec->state = 'active';

        return sens2_Controllers::save($nRec, null, 'IGNORE');
    }
}