<?php


/**
 * Клас 'ibex_Wrapper'
 *
 * Поддържа табове-те на пакета 'ibex'
 *
 *
 * @category  bgerp
 * @package   ibex
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ibex_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('ibex_Register', 'История', 'admin, ceo, cms');

        $this->title = 'Данни';
    }
}
