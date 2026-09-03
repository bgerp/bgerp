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
     * Данни за домейна преди запис
     */
    protected static $domainsBeforeSave = array();


    /**
     * Реални хостове, чиито манифести ще се обновят в края на хита
     */
    protected static $manifestHostsToRegenerate = array();


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


    /**
     * Запомня стария хост и архива със статични файлове
     */
    public static function on_BeforeSave($mvc, &$id, &$rec, $fields = null)
    {
        if (!$id) {
            return;
        }

        $oldRec = $mvc->fetch($id, '*', false);
        if ($oldRec) {
            self::$domainsBeforeSave[$id] = (object) array(
                'domain' => $oldRec->domain,
                'wrFiles' => $oldRec->wrFiles,
            );
        }
    }


    /**
     * Планира обновяване след като cms_Domains публикува wrFiles
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fields = null)
    {
        $savedRec = $mvc->fetch($id, '*', false);
        if (!$savedRec) {
            return;
        }

        $oldRec = isset(self::$domainsBeforeSave[$id]) ? self::$domainsBeforeSave[$id] : null;
        unset(self::$domainsBeforeSave[$id]);

        $domainChanged = $oldRec && $oldRec->domain !== $savedRec->domain;
        $archiveChanged = $oldRec
            ? $oldRec->wrFiles !== $savedRec->wrFiles
            : !empty($savedRec->wrFiles);
        if (!$domainChanged && !$archiveChanged) {
            return;
        }

        if ($oldRec && $oldRec->domain) {
            self::queueManifestHost($oldRec->domain);
        }
        if ($savedRec->domain) {
            self::queueManifestHost($savedRec->domain);
        }
    }


    /**
     * Обновява манифестите след собственото on_AfterSave на cms_Domains
     */
    public static function on_Shutdown($mvc)
    {
        $hosts = self::$manifestHostsToRegenerate;
        self::$manifestHostsToRegenerate = array();

        foreach ($hosts as $host) {
            try {
                pwa_Settings::regenerateManifestForHost($host);
            } catch (Throwable $e) {
                reportException($e);
            }
        }
    }


    /**
     * Добавя реален хост в опашката без повторения
     *
     * @param string $host
     */
    protected static function queueManifestHost($host)
    {
        $host = strtolower(trim((string) cms_Domains::getReal($host)));
        if ($host !== '') {
            self::$manifestHostsToRegenerate[$host] = $host;
        }
    }
}
