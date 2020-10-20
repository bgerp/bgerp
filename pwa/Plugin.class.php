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
        // Винаги добавяме, ако може ServiceWorker
        $serviceWorkerPath = getFullPath('pwa/js/sw.js');
        $name = 'sw' . crc32(filemtime($serviceWorkerPath)) . '.js';
        $domainId = cms_Domains::fetchField("#domain = 'localhost' AND #lang = 'bg'", 'id');

        if (!core_Webroot::isExists($name, $domainId)) {
            $lastServiceWorker = core_Permanent::get('lastServiceWorker' . $domainId);
            if($lastServiceWorker) {
                core_Webroot::remode($name, $domainId);
            }
            core_Webroot::register(file_get_contents($serviceWorkerPath), 'Expires: {{time_604800}}', $name, $domainId);
        }

        // Ако е активирана опцията за мобилно приложение - манифестираме го
        if (pwa_Setup::get('ACTIVE') == 'yes' && cms_Domains::getPublicDomain('domain') == 'localhost') {
            $invoker->appendOnce("\n<script>\n    var serviceWorkerURL = '/{$name}';\n</script>\n", 'HEAD');
            
            $cu = (int) core_Users::getCurrent();
            $cu = str::addHash($cu);
            $manifestUrl = toUrl(array('pwa_Manifest', 'Default', 'u' => $cu));
            $invoker->appendOnce("\n<link  rel=\"manifest\" href=\"{$manifestUrl}\">", 'HEAD');
        }
    }
}
