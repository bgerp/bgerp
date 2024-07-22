<?php


/**
 *
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('ztm_Devices', 'Устройства', 'ztm, ceo');
        $this->TAB('ztm_RegisterValues', 'Стойности', 'ztm, ceo');
        $this->TAB('ztm_Profiles', 'Настройки->Профили', 'ztm, ceo');
        $this->TAB('ztm_Registers', 'Настройки->Регистри', 'ztm, ceo');
        $this->TAB('ztm_Groups', 'Настройки->Групи', 'ztm, ceo');

        $this->TAB('ztm_LongValues', 'Debug->Дълги стойности', 'debug');
        $this->TAB('ztm_Notes', 'Debug->Дневник', 'debug');
    }
}
