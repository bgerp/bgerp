<?php
use Minishlink\WebPush\VAPID;

/**
 * За кои домейни да се използва PWA
 * @deprecated
 */
defIfNot('PWA_DOMAINS', '');


/**
 * Имейл адрес, който ще се ипозлва за mailto в PWA
 */
defIfNot('PWA_MAILTO', '');


/** Запазва досегашния автоматичен избор според PHP. */
defIfNot('PWA_WEB_PUSH_VERSION', 'auto');


/**
 * Клас 'pwa_Setup' -  bgERP progressive web application
 *
 *
 * @package   pwa
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.6';


    public $info = 'bgERP progressive web application';


    /** Точният избор е полезен и когато PHP 7.4/8.2 споделят един vendor. */
    public $configDescription = array(
        'PWA_WEB_PUSH_VERSION' => array('enum(auto=Автоматично според PHP,7.0.0,8.0.0=8.0.0 (PHP 8.0+),11.0.0=11.0.0 (PHP 8.2+))', 'mandatory,caption=Web Push->Версия'),
    );


    /** Връща избраната версия или досегашния автоматичен клон. */
    public static function getLibraryVersion()
    {
        $version = self::get('WEB_PUSH_VERSION');
        if ($version !== 'auto') return $version;
        if (PHP_VERSION_ID >= 80000) return '8';
        if (PHP_VERSION_ID >= 70300) return '7';
        if (PHP_VERSION_ID >= 70200) return '6';
        if (PHP_VERSION_ID >= 70100) return '5';

        return '2';
    }


    /** Проверява PHP преди Composer/autoload и преди запис на настройките. */
    public static function getPhpVersionError($version)
    {
        if ($version === '11.0.0' && PHP_VERSION_ID < 80200) {
            return 'Web Push 11.0.0 изисква PHP 8.2 или по-нова версия. За PHP 7.4 изберете 7.0.0.';
        }
        if ($version === '8.0.0' && PHP_VERSION_ID < 80000) {
            return 'Web Push 8.0.0 изисква PHP 8.0 или по-нова версия. За PHP 7.4 изберете 7.0.0.';
        }
        if ($version === '7.0.0' && PHP_VERSION_ID < 70300) {
            return 'Web Push 7.0.0 изисква PHP 7.3 или по-нова версия.';
        }
    }


    /** Стандартна грешка към неподходящия PHP избор. */
    public function inputConfigDescriptionForm(&$form)
    {
        if ($form->isSubmitted() && ($error = self::getPhpVersionError($form->rec->PWA_WEB_PUSH_VERSION ?? null))) {
            $form->setError('PWA_WEB_PUSH_VERSION', $error);
        }
    }


    /** Показва липсващата избрана зависимост, без да сменя общия Composer. */
    public function checkConfig()
    {
        if ($error = self::getPhpVersionError(self::get('WEB_PUSH_VERSION'))) return $error;
        if (!core_Composer::isInstalled('minishlink/web-push', self::getLibraryVersion(), self::get('WEB_PUSH_VERSION') !== 'auto')) {
            $error = 'Липсва избраната Web Push версия. Инициализирайте пакета pwa.';
            if (self::get('WEB_PUSH_VERSION') === '11.0.0') {
                $error .= ' Web Push 11 изисква съвместими PSR-18/PSR-17 реализации и psr/log 2 или 3; заключените зависимости може да изискват отделно обновяване.';
            }

            return $error;
        }
        if (self::get('WEB_PUSH_VERSION') === '11.0.0') {
            try {
                if (!core_Composer::isInUse()) return 'Не може да се зареди Web Push чрез Composer.';
                \Http\Discovery\Psr18ClientDiscovery::find();
                \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
                \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();
            } catch (core_exception_Redirect $e) {
                throw $e;
            } catch (Throwable $e) {
                return 'Web Push 11 изисква PSR-18 клиент и PSR-17 factories, например съвместими guzzlehttp/guzzle и guzzlehttp/psr7 в общия Composer.';
            }
        }
    }


    /**
     * Необходими пакети
     */
    public $depends = 'cms=0.1';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'pwa_PushSubscriptions',
        'pwa_Settings',
        'migrate::updateSettings2509',
        'migrate::updateState2516',
        'migrate::renewTruncatedEndpoints2608',
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
        if ($error = self::getPhpVersionError(self::get('WEB_PUSH_VERSION'))) {
            return '<li class="red">' . $error . '</li>';
        }
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
        $html .= fileman_Buckets::createBucket('pwaZip', 'Файлове за иконите в PWA', 'zip,rar,7z,tar,gz', '100MB', 'powerUser', 'powerUser');

        // @deprecated
        $existDArr = array();
        $dArr = type_Keylist::toArray($this->get('DOMAINS'));
        foreach ($dArr as $key => $domainId) {
            $dRec = cms_Domains::fetch($domainId);
            if ($dRec) {
                $domainName = $dRec->domain;
                $existDArr[$domainName] = $domainName;
            } else {
                // В старата настройка може да е останал вече изтрит домейн.
                unset($dArr[$key]);
            }
        }
        foreach (pwa_Settings::getDomains() as $dId => $name) {
            $existDArr[$name] = $name;
            // Активните настройки се нареждат след deprecated списъка и в
            // реда на pwa_Settings.id. При езикови записи за общ host така
            // крайният manifest е същият като при последваща регенерация.
            unset($dArr[$dId]);
            $dArr[$dId] = $dId;
        }

        $dQuery = cms_Domains::getQuery();
        $dQuery->notIn('domain', $existDArr);
        while ($dRec = $dQuery->fetch()) {
            core_Webroot::remove('serviceWorker.js', $dRec->id);
            core_Webroot::remove('pwa.webmanifest', $dRec->id);
            pwa_Plugin::removeServiceWorkerVersion($dRec->id);
            pwa_Settings::removeManifestVersion($dRec->id);
        }

        foreach ($dArr as $domainId) {
            $dRec = cms_Domains::fetch($domainId);
            if (!$dRec) {
                continue;
            }

            $published = pwa_Settings::publishWebrootFilesForDomain($domainId);
            if (!$published || empty($published['success'])) {
                $html .= '<li class="red">Не могат да се публикуват PWA файловете за ' . $dRec->domain . '</li>';
                continue;
            }

            if (!empty($published['manifestChanged'])) {
                $html .= '<li>Генериране на манифест на PWA за ' . $dRec->domain . '</li>';
            }
            if (!empty($published['serviceWorkerChanged'])) {
                $html .= '<li>Регистриране на PWA за ' . $dRec->domain . '</li>';
            }
        }

        $cVersion = self::getLibraryVersion();

        if (version_compare($cVersion, '7', '<')) {
            $html .= '<li class="red">Препоръчва се да се използва PHP 7.3 или по-нова версия.</li>';
        }

        try {
            // 8 -> за PHP > PHP 8.0
            // 7 -> за PHP > PHP 7.3 7.4
            // 6 -> PHP 7.2
            // 3-5 -> PHP 7.1
            // 2 -> PHP 7.0
            // 1 -> PHP 5.6
            $html .= core_Composer::install('minishlink/web-push', $cVersion, self::get('WEB_PUSH_VERSION') !== 'auto');
            if (!empty(core_Composer::$error)) return $html;

            if (!core_Composer::isInUse() || !class_exists(VAPID::class)) {
                $html .= "<li class='red'>Проблем при зареждането на composer</li>";

                return $html;
            }

            $dQuery = cms_Domains::getQuery();
            $existKeysDomainsArr = $notExistKeysDomainsArr =  array();
            while ($dRec = $dQuery->fetch()) {
                if (trim((string) $dRec->publicKey) && trim((string) $dRec->privateKey)) {
                    $existKeysDomainsArr[$dRec->domain][$dRec->id] = $dRec;
                } else {
                    $notExistKeysDomainsArr[$dRec->domain][$dRec->id] = $dRec;
                }
            }

            foreach ($notExistKeysDomainsArr as $dName => $notDArr) {
                if (!empty($existKeysDomainsArr[$dName])) {
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
                    } catch (core_exception_Redirect $t) {
                        throw $t;
                    } catch (Throwable $t) {
                        reportException($t);
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
        } catch (core_exception_Redirect $t) {
            throw $t;
        } catch (Throwable $t) {
            $html .= '<li class="red">Composer не е инсталиран. Не е зададен "EF_VENDOR_PATH"</li>';
        }

        return $html;
    }


    /**
     * Миграция за обновяване на настройките от PWA_DOMAINS към pwa_Settings
     */
    function updateSettings2509()
    {
        $dArr = type_Keylist::toArray($this->get('DOMAINS'));
        if (empty($dArr)) {

            return ;
        }

        fileman_Buckets::createBucket('pwaZip', 'Файлове за иконите в PWA', 'zip,7z', '100MB', 'powerUser', 'powerUser');
        $appTitle = core_Setup::get('EF_APP_TITLE', true);
        $text = 'интегрирана система за управление';

        foreach ($dArr as $dId) {
            $nRec = new stdClass();
            $tPath = fileman::getTempPath();

            expect($tPath);

            $iconSizes = array(72, 96, 128, 144, 152, 192, 384, 512);
            $iconsWritten = 0;

            $sourceContent = $fName = null;

            if (core_Webroot::isExists('android-chrome-512x512.png', $dId)) {
                $fName = 'android-chrome-512x512.png';
                $sourceContent = core_Webroot::getContents($fName, $dId);
            } elseif (core_Webroot::isExists('favicon.png', $dId)) {
                $fName = 'favicon.png';
                $sourceContent = core_Webroot::getContents($fName, $dId);
            }

            foreach ($iconSizes as $size) {
                $content = false;
                if (isset($sourceContent)) {
                    $content = pwa_Settings::resizeRasterIcon($sourceContent, $size);
                }

                if ($content === false) {
                    $content = getFileContent("pwa/icons/icon-{$size}x{$size}.png");
                    $fName = 'pwa-icon.png';
                }

                if ($content === false) {

                    continue;
                }

                if (@file_put_contents($tPath . '/' . $size . 'x' . $size . '_' . $fName, $content)) {
                    $iconsWritten++;
                }
            }

            if ($iconsWritten) {
                $tPathDest = fileman::getTempPath();
                archive_Adapter::compressFile($tPath . '/*', $tPathDest . '/pwa' . '.zip');
                $nRec->icons = fileman::absorbStr(file_get_contents($tPathDest . '/pwa' . '.zip'), 'pwaZip', 'pwa' . '.zip');
                core_Os::deleteDir($tPathDest);
            }
            core_Os::deleteDir($tPath);

            $nRec->domainId = $dId;
            $nRec->shortName = $appTitle;
            $nRec->name = $appTitle . ' - ' . $text;
            $nRec->description = $appTitle . ' - ' . $text;
            $nRec->display = 'standalone';
//            $nRec->displayOverride = type_Set::fromArray(array(''));
            $nRec->backgroundColor = '#fff';
            $nRec->themeColor = '#ddd';
            $nRec->startUrl = '/?isPwa=yes';
//            $nRec->clientMode = '';
//            $nRec->orientation = 'any';
            $nRec->scope = '/';

            $nRec->sc1Name = 'Сканиране на баркод';
            $nRec->sc1ShortName = 'Баркод';
            $nRec->sc1Description = 'Сканиране и търсене на информация за баркод';
            $nRec->sc1Url = '/barcode_Search';

            if ($scIcon = getFullPath('pwa/icons/barcode-scan.png')) {
                $nRec->sc1Icon = fileman::absorbStr(file_get_contents($scIcon), 'pwaZip', 'scIcon.png');
            }

            $nRec->state = 'active';

            pwa_Settings::save($nRec, null, 'IGNORE');

            if ($nRec->id) {
                // Зануляваме старата стойност, която няма да се използва повече
                $conf = core_Packs::getConfig('pwa');
                $data = array();

                if (!empty($conf->_data['PWA_DOMAINS'])) {
                    $data['PWA_DOMAINS'] = null;
                    core_Packs::setConfig('pwa', $data);
                }
            }
        }
    }


    /**
     * Активира PWA абонаментите, които са null
     *
     * @return void
     */
    function updateState2516()
    {
        $pQuery = pwa_PushSubscriptions::getQuery();
        $pQuery->where("#state IS NULL");

        while ($pRec = $pQuery->fetch()) {
            $pRec->state = 'active';
            pwa_PushSubscriptions::save($pRec, 'state');
        }
    }


    /**
     * Спира активните записи с endpoint, отрязан от старото varchar(255)
     *
     * setupMvc() вече е разширил колоната до 1024. Оригиналният край на
     * старите стойности обаче не може да бъде възстановен надеждно, затова
     * браузърът трябва да създаде и запише отново целия абонамент.
     */
    function renewTruncatedEndpoints2608()
    {
        $Subscriptions = cls::get('pwa_PushSubscriptions');
        $query = $Subscriptions->getQuery();
        $query->where("#state = 'active'");

        while ($dbRec = $query->fetch()) {
            if (strlen($dbRec->endpoint ?? '') < 255) {

                continue;
            }

            $rec = clone $dbRec;
            $rec->state = 'stopped';
            $savedId = $Subscriptions->save($rec, 'state,modifiedOn,modifiedBy');
            expect($savedId !== false, 'Не може да се маркира отрязаният PUSH endpoint за подновяване');
        }
    }
}
