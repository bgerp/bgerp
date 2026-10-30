<?php

/**
 * Пренася диагностиката на Web Push 11 към системния лог на bgERP.
 *
 * @category bgerp
 * @package pwa
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class pwa_WebPushLogger extends \Psr\Log\AbstractLogger
{
    /** Записва диагностиката, без PHP notice да прекъсва изпращането. */
    public function log($level, $message, array $context = array()): void
    {
        $type = in_array($level, array('emergency', 'alert', 'critical', 'error'), true) ? 'err'
            : ($level === 'warning' ? 'warning' : 'notice');
        log_System::add('pwa_PushSubscriptions', (string) $message, null, $type);
    }
}
