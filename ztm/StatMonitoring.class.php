<?php


/**
 * Драйвер за генериране на статистика от други контролери и индикатори
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Статистика на ZTM
 */
class ztm_StatMonitoring extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Статистика на ZTM';


    /**
     * Входове на контролера
     */
    public $inputs = array(
        'min' => array('caption' => 'Минимална', 'logPeriod' => 3600, 'readPeriod' => 60),
        'average' => array('caption' => 'Средна', 'logPeriod' => 3600, 'readPeriod' => 60),
        'max' => array('caption' => 'Максимална', 'logPeriod' => 3600, 'readPeriod' => 60),
        'sum' => array('caption' => 'Сбор', 'logPeriod' => 3600, 'readPeriod' => 60),
        'cnt' => array('caption' => 'Брой', 'logPeriod' => 3600, 'readPeriod' => 60),
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
        $form->FNC('ztmDevice', 'keylist(mvc=ztm_Devices, select=name, find=everywhere)', 'caption=Контролери, input, mandatory');
        $form->FNC('registerId', 'key(mvc=ztm_Registers, select=name, where=#type \\= \\\'float\\\' OR #type \\= \\\'int\\\')', 'caption=Регистър, input, mandatory');
    }


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $dQuery = ztm_Devices::getQuery();
        $dQuery->in('id', $config->ztmDevice);
        $aArr = $res = array();
        while ($dRec = $dQuery->fetch()) {
            $dId = $dRec->id;
            if ($dRec->state != 'active') {
                $dId = ztm_Devices::fetchField(array("#name = '[#1#]' AND #state = 'active'", $dRec->name), 'id');
            }
            if  (!$dId) {

                continue;
            }
            $rVal = ztm_RegisterValues::get($dId, $config->registerId);
            if (!$rVal) {

                continue;
            }

            foreach ($inputs as $input) {
                if ($input == 'min') {
                    setIfNot($res[$input], $rVal->value);
                    $res[$input] = min($rVal->value, $res[$input]);
                }
                if ($input == 'max') {
                    setIfNot($res[$input], $rVal->value);
                    $res[$input] = max($rVal->value, $res[$input]);
                }
                if ($input == 'sum') {
                    setIfNot($res[$input], 0);
                    $res[$input] += $rVal->value;
                }
                if ($input == 'cnt') {
                    setIfNot($res[$input], 0);
                    $res[$input]++;
                }
                if ($input == 'average') {
                    setIfNot($aArr['cnt'], 0);
                    setIfNot($aArr['sum'], 0);
                    $aArr['cnt']++;
                    $aArr['sum'] += $rVal->value;
                }
            }
        }

        if (!empty($aArr)) {
            $res['average'] = $aArr['sum'] / $aArr['cnt'];
        }

        return $res;
    }
}
