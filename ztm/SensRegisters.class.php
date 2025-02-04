<?php


/**
 * Драйвер за регистрите на ZTM
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     ЗТМ->Регистри
 */
class ztm_SensRegisters extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'ЗТМ->Регистри';


    /**
     * Входове на контролера
     */
    public $inputs = array(
    );


    /**
     * Изходи на контролера
     */
    public $outputs = array(
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
        $typeTable = 'table(columns=name|register|path|period|uom,captions=Име|Регистър|Път|Време|Мярка,widths=12em|15em|15em|3em|3em,mandatory=register|name)';

        $form->FNC('ztmDevice', 'key(mvc=ztm_Devices, select=name, find=everywhere)', 'caption=Контролер, input, mandatory');
        $form->FNC('read', $typeTable, 'caption=Четене, input');
        $form->FNC('write', $typeTable, 'caption=Запис, input');

        $readOptArr = ztm_Registers::getRegisters();
        $form->setFieldTypeParams('read', array('register_opt' => array('' => '') + $readOptArr));

        $writeOptArr = ztm_Registers::getRegisters(array('device'));
        $form->setFieldTypeParams('write', array('register_opt' => array('' => '') + $writeOptArr));
    }


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $resArr = array();

        $dRec = ztm_Devices::fetchRec($config->ztmDevice);

        $dArr = $this->discovery();

        foreach ($inputs as $input) {
            foreach ($dArr as $d) {
                if ($d->name == $input) {
                    $path = $d->slot;
                    list($regId, $oParams) = explode('|', $path, 2);

                    $rRec = ztm_RegisterValues::fetch(array("#deviceId = '[#1#]' AND #registerId = [#2#]", $dRec->id, $regId));
                    if ($rRec) {
                        $profileValue = ztm_LongValues::getValueByHash($rRec->value);

                        if ($oParams && isset($profileValue)) {
                            $profileValue = @json_decode($profileValue);
                            while (true) {
                                if (!isset($oParams)) {

                                    break;
                                }

                                list($cParam, $oParams) = explode('|', $oParams, 2);
                                if (is_array($profileValue)) {
                                    $profileValue = $profileValue[$cParam];
                                } elseif (is_object($profileValue)) {
                                    $profileValue = $profileValue->{$cParam};
                                } else {
                                    $profileValue = null;
                                    break;
                                }
                            }

                            if (isset($oParams)) {
                                $profileValue = null;
                            }
                        }

                        $resArr[$input] = $profileValue;
                    }
                }
            }
        }

        return $resArr;
    }


    /**
     * Записва стойностите на изходите на контролера
     *
     * @param array $outputs         масив със системните имена на изходите и стойностите, които трябва да бъдат записани
     * @param array $config          конфигурациони параметри
     * @param array $persistentState персистентно състояние на контролера от базата данни
     *
     * @return array Масив със системните имена на изходите и статус (TRUE/FALSE) на операцията с него
     */
    public function writeOutputs($outputs, $config, &$persistentState)
    {
        $resArr = array();

        $dRec = ztm_Devices::fetchRec($config->ztmDevice);

        $dArr = $this->discovery();

        foreach ($outputs as $oKey => $oVal) {
            foreach ($dArr as $d) {
                if ($d->name == $oKey) {
                    $path = $d->slot;
                    list($regId, $oParams) = explode('|', $path, 2);

                    $rRec = ztm_RegisterValues::fetch(array("#deviceId = '[#1#]' AND #registerId = [#2#]", $dRec->id, $regId));
                    if ($rRec) {
                        $profileValue = ztm_LongValues::getValueByHash($rRec->value);
                        if ($oParams) {
                            if (isset($profileValue)) {
                                $oParamsArr = explode('|', $oParams);
                                $profileValue = @json_decode($profileValue);

                                $temp = &$profileValue;
                                foreach ($oParamsArr as $key) {
                                    if (is_array($temp)) {
                                        if (!isset($temp[$key])) {
                                            $oVal = null;

                                            break;
                                        }

                                        $temp = &$temp[$key];
                                    }
                                    if (is_object($temp)) {
                                        if (!isset($temp->{$key})) {
                                            $oVal = null;

                                            break;
                                        }

                                        $temp = &$temp->{$key};
                                    }
                                }

                                $temp = $oVal;

                                unset($temp);
                            } else {
                                $oVal = null;
                            }
                        } else {
                            $profileValue = $oVal;
                        }

                        ztm_RegisterValues::forceSync($regId, $profileValue, $dRec->id);

                        $resArr[$oKey] = $oVal;
                    }
                }
            }
        }

        return $resArr;
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
        foreach (array('read', 'write') as $field) {
            $arr = type_Table::toArray($this->driverRec->config->{$field});
            foreach ($arr as $v) {
                if (!$v) {

                    continue;
                }

                $rObj = new stdClass();
                $rObj->name = $v->name;
                if ($v->path) {
                    $rObj->path = $v->path;
                }
                $rObj->slot = $v->register;
                if ($v->path) {
                    $rObj->slot .= '|' . $v->path;
                }
                if ($v->uom) {
                    $rObj->uom = $v->uom;
                }
                if (!$v->period) {
                    $v->period = 3600;
                }
                if ($field == 'read') {
                    $rObj->readable = true;
                    if ($v->period) {
                        $rObj->readPeriod = $v->period;
                    }
                }

                if ($field == 'write') {
                    $rObj->writable = true;
                    if ($v->period) {
                        $rObj->logPeriod = $v->period;
                    }
                }

                $res[] = $rObj;
            }
        }

        return $res;
    }
}
