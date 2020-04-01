<?php


/**
 * Обвивка на пакета speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на опаковката от табове
     */
    public function description()
    {
        $this->TAB('speedy_Offices', 'Офиси', 'admin');
    }
}
