<?php



/**
 * Клас 'transbid_Wrapper'
 *
 * Опаковка на пакета transbid
 *
 *
 * @category  bgerp
 * @package   transbid
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   Property
 * @since     v 0.1
 */
class transsrv_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
        $this->TAB('transsrv_TransportUnits', 'Транспорт->Единици', 'trans,admin');
        $this->TAB('transsrv_TransportModes', 'Транспорт->Видове', 'trans,admin');
    }
}