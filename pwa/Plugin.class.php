<?php


/**
 * Клас създаване на Progressive web application manifest
 *
 * @package   pwa
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Plugin extends core_Plugin
{

    public function on_Output(&$invoker)
    {
        $canUse = pwa_Settings::canUse();

        // Ако е активирана опцията за мобилно приложение - манифестираме го
        if ($canUse == 'yes') {
            $swDate = filemtime(getFullPath('pwa/js/sw.js'));
            $swDate = date('Y-m-d H:i:s', $swDate);
            $invoker->appendOnce("\n<link  rel=\"manifest\" href=\"/pwa.webmanifest\" data-sw-date=\"{$swDate}\">", 'HEAD');
        }
        $invoker->push('pwa/js/swRegister.js', 'JS', true);
        jquery_Jquery::run($invoker, 'syncServiceWorker();', true);
    }
}
