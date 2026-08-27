<?php


/**
 * Помощен клас за връзката с електронните везни
 *
 * @category  bgerp
 * @package   wscales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wscales_Helper
{
    /**
     * Добавя джаваскрипта за четене от везна, ако има достъпна за текущия компютър
     *
     * @param core_ET     $tpl           - шаблонът, към който се добавя скриптът
     * @param string      $weightFld     - име на полето, в което се показва теглото
     * @param string|null $liveWeightFld - име на скритото поле с живото тегло
     * @param string|null $formId        - id на формата с полето за теглото
     *
     * @return bool - добавен ли е скриптът
     */
    public static function appendJs(&$tpl, $weightFld, $liveWeightFld = null, $formId = null)
    {
        if (empty($weightFld)) {

            return false;
        }

        $deviceRec = peripheral_Devices::getDevice('wscales_intf_Scales');
        if (empty($deviceRec)) {

            return false;
        }

        // Записът идва от статичния кеш на устройствата, затова се клонира преди да се допълни
        $rec = clone $deviceRec;
        $rec->_weight = $weightFld;
        $rec->_liveWeight = $liveWeightFld;
        $rec->_formIdName = !empty($formId) ? '#' . $formId : null;

        jquery_Jquery::enable($tpl);
        $Intf = cls::getInterface('wscales_intf_Scales', $rec->driverClass);
        $tpl->appendOnce($Intf->getJs($rec), 'SCRIPTS');

        return true;
    }


    /**
     * Разрешава на браузъра да чете от везна, която не е на текущия компютър
     *
     * @return void
     */
    public static function allowCrossOrigin()
    {
        $deviceRec = peripheral_Devices::getDevice('wscales_intf_Scales');
        if (empty($deviceRec) || $deviceRec->hostName == 'localhost') {

            return;
        }

        header('Access-Control-Allow-Origin: *');
        header('Vary: Origin');
    }
}
