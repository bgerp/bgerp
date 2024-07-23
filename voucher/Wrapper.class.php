<?php


/**
 * Клиентски ваучери - обвивка
 *
 *
 * @category  bgerp
 * @package   voucher
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class voucher_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('voucher_Cards', 'Карти', 'ceo, voucher');
        $this->TAB('voucher_Types', 'Типове', 'ceo, voucher');
    }
}
