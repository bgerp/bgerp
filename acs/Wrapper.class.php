<?php


/**
 * Клас 'acs_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'acs'
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('acs_Zones', 'Зони', 'acs, admin');
        $this->TAB('acs_Permissions', 'Разрешения', 'acs, admin');
        $this->TAB('acs_Logs', 'Логове', 'acs, admin');
        
        $this->title = 'Контрол на достъп';
    }
}
