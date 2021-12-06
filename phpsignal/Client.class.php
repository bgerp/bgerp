<?php


use jigarakatidus\Signal;

/**
 * Клиент за работа със signal
 *
 * @category  vendors
 * @package   phpsignal
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class phpsignal_Client
{
    /**
     * Изпраща съобщение до друг клиент
     *
     * @param string $number  - номер на получател
     *
     * @return string - резултат
     */
    public static function send($number)
    {
        
        if (core_Composer::isInUse()) {
            try {
                // Инстанция на класа
                $checker = new Signal(
                    phpsignal_Setup::get('SIGNAL_BIN_PATH'), // Binary Path
                    phpsignal_Setup::get('SIGNAL_NUMBER'), // Username/Number including Country Code with '+'
                    Signal::FORMAT_JSON // Format
                    );
            } catch (Exception $e) {
                reportException($e);
            }
        }
        
        return $res;
    }
}
