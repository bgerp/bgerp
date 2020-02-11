<?php


/**
 * Клас 'floor_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'floor'
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class floor_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('floor_Plans', 'Планове', 'floor, ceo, admin');
    }
}
