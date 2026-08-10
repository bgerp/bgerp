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

    /**
     * Тип на кеша за версиите на Service Worker
     */
    const SERVICE_WORKER_VERSION_CACHE_TYPE = 'pwaSwVersion';


    /**
     * Живот на кеша за версиите на Service Worker (в минути)
     */
    const SERVICE_WORKER_VERSION_CACHE_LIFETIME = 525600;


    /**
     * Версии, прочетени в текущия хит
     */
    protected static $serviceWorkerVersions = array();


    /**
     * Връща версията на регистрирания Service Worker
     *
     * Версията нормално се записва от pwa_Setup при генерирането на файла.
     * За инсталации, които още не са минали през обновяване, съдържанието се
     * прочита и хешира еднократно, след което резултатът се кешира.
     *
     * @param int|null $domainId
     *
     * @return string|int
     */
    public static function getServiceWorkerVersion($domainId = null)
    {
        if (!isset($domainId)) {
            $domainId = cms_Domains::getCurrent('id', false);
        }

        $handler = self::getServiceWorkerVersionHandler($domainId);
        if (array_key_exists($handler, self::$serviceWorkerVersions)) {

            return self::$serviceWorkerVersions[$handler];
        }

        $version = core_Cache::get(
            self::SERVICE_WORKER_VERSION_CACHE_TYPE,
            $handler
        );
        if ($version === false && core_Webroot::isExists('serviceWorker.js', $domainId)) {
            $version = self::setServiceWorkerVersion(
                $domainId,
                core_Webroot::getContents('serviceWorker.js', $domainId)
            );
        }

        if ($version === false) {
            $defaultSwPath = getFullPath('pwa/js/sw.js');
            $version = ($defaultSwPath && is_file($defaultSwPath)) ? filemtime($defaultSwPath) : '';
        }

        self::$serviceWorkerVersions[$handler] = $version;

        return $version;
    }


    /**
     * Записва версията на Service Worker за домейна
     *
     * @param int    $domainId
     * @param string $contents
     *
     * @return string
     */
    public static function setServiceWorkerVersion($domainId, $contents)
    {
        $handler = self::getServiceWorkerVersionHandler($domainId);
        $version = md5((string) $contents);
        core_Cache::set(
            self::SERVICE_WORKER_VERSION_CACHE_TYPE,
            $handler,
            $version,
            self::SERVICE_WORKER_VERSION_CACHE_LIFETIME
        );
        self::$serviceWorkerVersions[$handler] = $version;

        return $version;
    }


    /**
     * Премахва запазената версия за домейна
     *
     * @param int $domainId
     */
    public static function removeServiceWorkerVersion($domainId)
    {
        $handler = self::getServiceWorkerVersionHandler($domainId);
        core_Cache::remove(self::SERVICE_WORKER_VERSION_CACHE_TYPE, $handler);
        unset(self::$serviceWorkerVersions[$handler]);
    }


    /**
     * Връща манипулатора за версията на Service Worker
     *
     * @param int|null $domainId
     *
     * @return string
     */
    protected static function getServiceWorkerVersionHandler($domainId)
    {
        $domain = cms_Domains::fetchField((int) $domainId, 'domain');
        if ($domain) {
            // Езиковите записи за един и същ домейн споделят webroot и SW.
            $domain = cms_Domains::getReal($domain);
        } else {
            $domain = 'domainId_' . (int) $domainId;
        }

        return md5(strtolower(trim($domain)));
    }

    public function on_Output(&$invoker)
    {
        $canUse = pwa_Settings::canUse();

        // Ако е активирана опцията за мобилно приложение - манифестираме го
        if ($canUse == 'yes') {
            $dId = cms_Domains::getCurrent('id', false);
            $swVersion = self::getServiceWorkerVersion($dId);

            $invoker->appendOnce("\n<link  rel=\"manifest\" href=\"/pwa.webmanifest\" data-sw-date=\"{$swVersion}\">", 'HEAD');
        }
        $invoker->push('pwa/js/swRegister.js', 'JS', true);
        jquery_Jquery::run($invoker, 'syncServiceWorker();', true);
    }
}
