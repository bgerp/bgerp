<?php


/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_RegisterPlg extends core_Plugin
{


    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     * @param null|mixed $saveFields
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFields = null)
    {
        $stateMap = array('0' => 'unknownId', '1' => 'allowed', '2' => 'authErr', '3' => 'denied', '4' => 'authErr');

        $lastAttRegId = ztm_Registers::fetchField(array("#name = 'ac.last_update_attendees'"));

        if ($lastAttRegId) {
            if ($rec->registerId == $lastAttRegId) {
                $val = ztm_LongValues::getValueByHash($rec->value);
                $val = @json_decode($val);
                if (is_array($val)) {
                    foreach ($val as $vObj) {
                        $lName = cls::get('ztm_Devices')->prepareName($rec->deviceId);
                        $zoneId = acs_Zones::fetchField(array("#name = '[#1#]'", $lName));

                        $action = $stateMap[$vObj->card_state];

                        acs_Logs::add($vObj->card_id, $zoneId, $action, $vObj->ts, $vObj->reader_id);
                    }
                }
            }
        }
    }
}
