<?php


/**
 * Клас 'peripheral_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'peripheral'
 *
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('peripheral_Devices', 'Периферия', 'peripheral, admin');
        
        $this->title = 'Периферни устройства';
    }
}
