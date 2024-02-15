<?php


/**
 * Интерфейс за регистрите на ztm
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за регистрите на ztm
 */
class ztm_interfaces_RegSyncValues
{


    /**
     * Клас
     */
    public $class;


    /**
     * Връща регистрите и стойностите им
     *
     * @return array
     */
    public function getRegValues()
    {

        return $this->class->getRegValues();
    }


    /**
     * Прочита и промяне регистрите и стойностите им
     *
     * @param null|stdClass $result
     * @param null|array $regArr
     * @param null|array $oDeviceRec
     * @param stdClass $deviceRec
     *
     * @return array
     */
    public function prepareRegValues($result, $regArr, $oDeviceRec, $deviceRec)
    {

        return $this->class->prepareRegValues($result, $regArr, $oDeviceRec, $deviceRec);
    }
}