<?php


/**
 * Поддържа системното меню и табове-те на пакета 'pwa'
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('pwa_Settings', 'PWA->Настройки', 'pwa, admin');
        $this->TAB('pwa_PushSubscriptions', 'PWA->Абонаменти', 'pwa, admin');
    }
}
