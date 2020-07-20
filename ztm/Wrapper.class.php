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
        $this->TAB('ztm_Profiles', 'Профили->Списък', 'ztm, ceo');
        $this->TAB('ztm_ProfileDefaults', 'Профили->Регистри', 'ztm, ceo');
        
        $this->TAB('ztm_Registers', 'Регистри->Списък', 'ztm, ceo');
        $this->TAB('ztm_RegistersDef', 'Регистри->Видове', 'ztm, ceo');
        $this->TAB('ztm_RegisterLongValues', 'Регистри->Дълги стойности', 'debug');
    }
}
