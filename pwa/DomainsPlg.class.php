<?php


/**
 * Свързване на домейните с PWA
 *
 * @package   pwa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_DomainsPlg extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('publicKey', 'password(128)', 'caption=Публичен ключ, input=none, single=none, column=none');
        $mvc->FLD('privateKey', 'password(128)', 'caption=Частен ключ, input=none, single=none, column=none');
    }
}
