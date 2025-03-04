<?php
use Minishlink\WebPush\VAPID;

/**
 * За кои домейни да се използва PWA
 */
defIfNot('PWA_DOMAINS', '');


/**
 * Имейл адрес, който ще се ипозлва за mailto в PWA
 */
defIfNot('PWA_MAILTO', '');


/**
 * Клас 'pwa_Setup' -  bgERP progressive web application
 *
 *
 * @package   pwa
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Setup extends core_ProtoSetup
{
    public $info = 'bgERP progressive web application';


    /**
     * Необходими пакети
     */
    public $depends = 'cms=0.1';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PWA_DOMAINS' => array('keylist(mvc=cms_Domains, select=domain, forceGroupBy=domain)', 'caption=Мобилно приложение->Домейни'),
    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'pwa_PushSubscriptions',
    );


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'pwa_PushSubscriptions';


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'PushAlertForNotifications',
            'description' => 'Известяване на потребителите за нови нотификации чрез PWA Push',
            'controller' => 'pwa_PushSubscriptions',
            'action' => 'PushAlertForNotifications',
            'period' => 1,
            'delay' => 15,
            'timeLimit' => 50
        )
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина към страницата
        $html .= $Plugins->installPlugin('bgERP PWA', 'pwa_Plugin', 'core_page_Active', 'family');
        $html .= $Plugins->installPlugin('bgERP PWA Profile', 'pwa_ProfilePlg', 'crm_Profiles', 'private');
        $html .= $Plugins->installPlugin('bgERP PWA Profile за колаборатори', 'pwa_ProfilePlg', 'cms_Profiles', 'private');
        $html .= $Plugins->installPlugin('bgERP PWA Domains', 'pwa_DomainsPlg', 'cms_Domains', 'private');

        $html .= $Plugins->installPlugin('PWA Абониране', 'pwa_SubscribePlg', 'bgerp_Index', 'private');
        $html .= $Plugins->installPlugin('PWA Абониране в портала', 'pwa_SubscribePlg', 'bgerp_Portal', 'private');

        $Domains = cls::get('cms_Domains');
        $Domains->setupMvc();

        $html .= fileman_Buckets::createBucket('pwa', 'Файлове качени с PWA', '', '1GB', 'user', 'every_one');
        
        $existDArr = array();
        $dArr = type_Keylist::toArray($this->get('DOMAINS'));
        foreach ($dArr as $domainId) {
            $dRec = cms_Domains::fetch($domainId);
            if ($dRec) {
                $domainName = $dRec->domain;
                $existDArr[$domainName] = $domainName;
            }
        }

        $dQuery = cms_Domains::getQuery();
        $dQuery->notIn('domain', $existDArr);
        while ($dRec = $dQuery->fetch()) {
            core_Webroot::remove('serviceWorker.js', $dRec->id);
            core_Webroot::remove('pwa.webmanifest', $dRec->id);
        }

        $sw = getFileContent('pwa/js/sw.js');

        foreach ($dArr as $domainId) {
            $manifest = pwa_Manifest::getPWAManifest($domainId);

            $dRec = cms_Domains::fetch($domainId);
            if ($dRec->wrFiles) {
                try {
                    $inst = cls::get('archive_Adapter', array('fileHnd' => $dRec->wrFiles));

                    $entries = $inst->getEntries();

                    if(is_array($entries) && countR($entries)) {
                        foreach($entries as $i => $e) {
                            if(preg_match("/[a-z0-9\\-\\_\\.]+/i", $e->path)) {
                                if ((trim(strtolower($e->path)) === 'serviceworker.js') || (trim(strtolower($e->path)) === 'pwa.webmanifest')) {
                                    $fh = $inst->getFile($i);
                                    $fiContent = fileman_Files::getContent($fh);
                                    if ((trim(strtolower($e->path)) === 'serviceworker.js')) {
                                        $sw = $fiContent;
                                    } else {
                                        $manifest = $fiContent;
                                    }
                                }
                            }
                        }
                    }
                } catch (Archive_7z_Exception $e) {
                    wp($e);
                }
            }

            if (core_Webroot::isExists('pwa.webmanifest', $domainId)) {
                $pwaPrevContent = core_Webroot::getContents('pwa.webmanifest', $domainId);
            } else {
                $pwaPrevContent = '';
            }
            if ($pwaPrevContent != $manifest) {
                core_Webroot::remove('pwa.webmanifest', $domainId);
                core_Webroot::register($manifest, 'Content-Type: application/json', 'pwa.webmanifest', $domainId);

                $html .= '<li>Генериране на манифест на PWA за ' . cms_Domains::fetchField($domainId, 'domain') . '</li>';
            }

            if (core_Webroot::isExists('serviceworker.js', $domainId)) {
                $swPrevContent = core_Webroot::getContents('serviceworker.js', $domainId);
            } else {
                $swPrevContent = '';
            }

            if ($swPrevContent != $sw) {
                core_Webroot::remove('serviceworker.js', $domainId);
                core_Webroot::register($sw, 'Content-Type: text/javascript', 'serviceworker.js', $domainId);

                $html .= '<li>Регистриране на PWA за ' . cms_Domains::fetchField($domainId, 'domain') . '</li>';
            }
        }

        $cVersion = 7;
        $pVersion = phpversion();
        if ((version_compare($pVersion, '7.3') < 0)) {
            $cVersion = 6;
        }
        if ((version_compare($pVersion, '7.2') < 0)) {
            $cVersion = 5;
        }
        if ((version_compare($pVersion, '7.1') < 0)) {
            $cVersion = 2;
        }

        if ((version_compare($pVersion, '8') >= 0)) {
            $cVersion = 8;
        }

        if ($cVersion <= 6) {
            $html .= '<li class="red">Препоръчва се да се използва PHP 7.3 или по-нова версия.</li>';
        }

        try {
            // 8 -> за PHP > PHP 8.0
            // 7 -> за PHP > PHP 7.3 7.4
            // 6 -> PHP 7.2
            // 3-5 -> PHP 7.1
            // 2 -> PHP 7.0
            // 1 -> PHP 5.6
            $html .= core_Composer::install('minishlink/web-push', $cVersion);

            if (!core_Composer::isInUse()) {
                $html .= "<li class='red'>Проблем при зареждането на composer</li>";
            }

            $dQuery = cms_Domains::getQuery();
            $existKeysDomainsArr = $notExistKeysDomainsArr =  array();
            while ($dRec = $dQuery->fetch()) {
                if (trim($dRec->publicKey) && trim($dRec->privateKey)) {
                    $existKeysDomainsArr[$dRec->domain][$dRec->id] = $dRec;
                } else {
                    $notExistKeysDomainsArr[$dRec->domain][$dRec->id] = $dRec;
                }
            }

            foreach ($notExistKeysDomainsArr as $dName => $notDArr) {
                if ($existKeysDomainsArr[$dName]) {
                    foreach ($notDArr as $nRec) {
                        $uRec = reset($existKeysDomainsArr[$dName]);
                        $nRec->publicKey = $uRec->publicKey;
                        $nRec->privateKey = $uRec->privateKey;
                        cms_Domains::save($nRec, 'publicKey, privateKey');
                    }
                } else {
                    $keysArr = array();
                    try {
                        $keysArr = @VAPID::createVapidKeys();
                    } catch (core_exception_Expect $e) {
                        reportException($e);
                    } catch (Throwable $t) {
                        reportException($t);
                    } catch (Error $e) {
                        reportException($e);
                    }

                    if (empty($keysArr)) {
                        $html .= "<li class='red'>Не може да се добавят 'VAPID' ключове за {$dName}</li>";
                    }

                    foreach ($notDArr as $nRec) {
                        if (!empty($keysArr)) {
                            $html .= "<li style='green'>Добавени са VAPID ключове към домейн {$nRec->domain}</li>";
                            $nRec->publicKey = $keysArr['publicKey'];
                            $nRec->privateKey = $keysArr['privateKey'];
                            cms_Domains::save($nRec, 'publicKey, privateKey');
                        }
                    }

                    sleep(1);
                }
            }
        } catch (core_exception_Expect $e) {
            $html .= '<li class="red">Composer не е инсталиран. Не е зададен "EF_VENDOR_PATH"</li>';
        } catch (Throwable $t) {
            $html .= '<li class="red">Composer не е инсталиран. Не е зададен "EF_VENDOR_PATH"</li>';
        } catch (Error $e) {
            $html .= '<li class="red">Composer не е инсталиран. Не е зададен "EF_VENDOR_PATH"</li>';
        }

        return $html;
    }
}
