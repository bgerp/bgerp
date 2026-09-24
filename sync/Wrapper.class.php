<?php


/**
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sync_Wrapper extends plg_ProtoWrapper
{


    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('sync_Map', 'Карта', 'sync, admin');
        $this->TAB('sync_Settings', 'Настройки', 'sync, admin');
    }
}
