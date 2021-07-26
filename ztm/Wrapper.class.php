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
        $this->TAB('ztm_Profiles', 'Профили', 'ztm, ceo');
        $this->TAB('ztm_Groups', 'Групи', 'ztm, ceo');
        
        $this->TAB('ztm_RegisterValues', 'Регистри->Стойности', 'ztm, ceo');
        $this->TAB('ztm_Registers', 'Регистри->Регистри', 'ztm, ceo');
        $this->TAB('ztm_LongValues', 'Регистри->Дълги стойности', 'debug');
//        $this->TAB('ztm_Simulation', 'Симулация->Топлинна', 'debug');
    }
}
