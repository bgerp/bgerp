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
        $this->TAB('ztm_ProfileDefaults', 'Профили->Регистри', 'ztm, ceo');
        $this->TAB('ztm_RegistersDef', 'Регистри', 'ztm, ceo');
    }
}
