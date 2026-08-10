<?php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 *
 *
 * @category  bgerp
 * @package   pwa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Settings extends core_Manager
{


    /**
     * Тип на кеша за генерираните манифести
     */
    const MANIFEST_CACHE_TYPE = 'pwaManifest';


    /**
     * Тип на кеша за версиите на физическите манифести
     */
    const MANIFEST_VERSION_CACHE_TYPE = 'pwaManifestVer';


    /**
     * Живот на кеша за манифестите (в минути)
     */
    const MANIFEST_CACHE_LIFETIME = 525600;


    /**
     * Кеш за ограничаване на повторните опити за самовъзстановяване
     */
    const WEBROOT_REPAIR_CACHE_TYPE = 'pwaWebrootRepair';


    /**
     * Интервал между неуспешни автоматични опити (в минути)
     */
    const WEBROOT_REPAIR_CACHE_LIFETIME = 1;


    /**
     * Версия на алгоритъма и вградените ресурси за манифеста
     *
     * Да се увеличава при промяна на генератора или комплектните икони.
     */
    const MANIFEST_ASSETS_VERSION = 3;


    /**
     * Стандартни размери на иконите в манифеста
     */
    const MANIFEST_ICON_SIZES = '72,96,128,144,152,192,384,512';


    /**
     * Максимален брой потребителски икони, които се обработват от архив
     */
    const MAX_CUSTOM_ICONS = 64;


    /**
     * Максимална допустима страна на потребителска икона
     */
    const MAX_ICON_DIMENSION = 4096;


    /**
     * Версии, прочетени в текущия хит
     */
    protected static $manifestVersions = array();


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Настройки';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, pwa_Wrapper, plg_State2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'pwa, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'pwa, admin';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'pwa, admin';


    /**
     * Кой може да изтрива системните данни
     */
    public $canDeletesysdata = 'pwa, admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'pwa, admin';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'pwa, admin';


    /**
     * Броя на преките пътища, които може да се създадат
     */
    protected $shortcutCnt = 3;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt,allowEmpty)', 'caption=Домейн,mandatory,autoFilter');
        $this->FLD('name', 'varchar(255)', 'caption=Име на приложението->Дълго,mandatory');
        $this->FLD('shortName', 'varchar(128)', 'caption=Име на приложението->Кратко');
        $this->FLD('description', 'varchar', 'caption=Описание');
        $this->FLD('display', 'enum(fullscreen, standalone, minimal-ui, browser)', 'caption=Показване->Екран,mandatory');
        $this->FLD('displayOverride', 'set(browser, fullscreen, minimal-ui, standalone, tabbed, window-controls-overlay)', 'caption=Показване->Презапис');
        $this->FLD('backgroundColor', 'color_Type', 'caption=Цвят->Фон');
        $this->FLD('themeColor', 'color_Type', 'caption=Цвят->Тема');
        $this->FLD('startUrl', 'varchar(255)', 'caption=Начално URL');
        $this->FLD('icons', 'fileman_FileType(bucket=pwaZip)', 'caption=Икони');
        $this->FLD('clientMode', 'set(auto, focus-existing, navigate-existing, navigate-new)', 'caption=Клиент->Режим');
        $this->FLD('orientation', 'enum(any, natural, landscape, landscape-primary, landscape-secondary, portrait, portrait-primary, portrait-secondary)', 'caption=Клиент->Ориентация');
        $this->FLD('scope', 'varchar(128)', 'caption=Обхват');

        for ($i = 1; $i <= $this->shortcutCnt; $i++) {
            $this->FLD("sc{$i}Name", 'varchar(255)', "caption=Пряк път {$i}->Име");
            $this->FLD("sc{$i}ShortName", 'varchar(128)', "caption=Пряк път {$i}->Кратко име");
            $this->FLD("sc{$i}Description", 'varchar', "caption=Пряк път {$i}->Описание");
            $this->FLD("sc{$i}Url", 'varchar(255)', "caption=Пряк път {$i}->URL");
            $this->FLD("sc{$i}Icon", 'fileman_FileType(bucket=pwa)', "caption=Пряк път {$i}->Икона");
        }

        $this->setDbUnique('domainId');
    }


    /**
     * Връща домейните, които се използват
     *
     * @return array
     */
    public static function getDomains()
    {
        $resArr = array();
        $query = self::getQuery();
        $query->where("#state != 'closed'");
        $query->orderBy('id', 'ASC');

        while ($rec = $query->fetch()) {
            $resArr[$rec->domainId] = cms_Domains::fetchField($rec->domainId, 'domain');
        }

        return $resArr;
    }


    /**
     * Подготвя манифест файла за PWA за съответния домейн
     *
     * @param $domainId
     * @return false|string
     */
    public static function getPWAManifest($domainId = null, $webrootDomain = null)
    {
        if (!isset($domainId)) {
            $domainId = cms_Domains::getCurrent('id', false);
        }
        if (!isset($webrootDomain)) {
            $webrootDomain = self::getWebrootDomain($domainId);
        }
        if (!$webrootDomain) {
            return false;
        }

        $rec = self::fetch(array("#domainId = '[#1#]'", $domainId));
        if (!$rec) {

            return false;
        }

        $cacheHandler = self::getManifestCacheHandler($domainId);
        $cacheDepends = array('pwa_Settings');
        $json = core_Cache::get(
            self::MANIFEST_CACHE_TYPE,
            $cacheHandler,
            self::MANIFEST_CACHE_LIFETIME,
            $cacheDepends
        );

        if ($json === false) {
            // Setup може да е стартиран през друг cms домейн. Манифестът
            // трябва да се генерира на езика на конкретния domainId.
            $domainLg = cms_Domains::fetchField($domainId, 'lang');
            if ($domainLg) {
                core_Lg::push($domainLg);
            }

            try {
                $iconInfoArr = self::prepareManifestIcons($rec, $domainId, $webrootDomain);

                $shortcuts = array();
                $me = cls::get(get_called_class());
                for ($i = 1; $i <= $me->shortcutCnt; $i++) {
                    if (!trim($rec->{"sc{$i}Name"})) {

                        continue;
                    }

                    $shortcut = array(
                        'name' => tr($rec->{"sc{$i}Name"}),
                        'short_name' => tr($rec->{"sc{$i}ShortName"}),
                        'description' => tr($rec->{"sc{$i}Description"}),
                        'url' => $rec->{"sc{$i}Url"},
                    );

                    $shortcutIcon = self::getShortcutIconInfo($rec->{"sc{$i}Icon"});
                    if ($shortcutIcon) {
                        $shortcut['icons'] = array((object) $shortcutIcon);
                    }

                    $shortcuts[] = (object) $shortcut;
                }

                $startUrl = trim((string) $rec->startUrl);
                if ($startUrl === '') {
                    $startUrl = '/?isPwa=yes';
                }

                $scope = trim((string) $rec->scope);
                if ($scope === '') {
                    $scope = '/';
                }

                $json = array(
                    'short_name' => tr($rec->shortName),
                    'name' => tr($rec->name),
                    'description' => tr($rec->description),
                    'display' => $rec->display,
                    'background_color' => $rec->backgroundColor,
                    'theme_color' => $rec->themeColor,
                    'start_url' => $startUrl,
                    'shortcuts' => $shortcuts,
                    'id' => $startUrl,
                    'scope' => $scope,
                    'icons' => $iconInfoArr,
                    'share_target' => array(
                        'action' => '/pwa_Share/Target',
                        'method' => 'POST',
                        'enctype' => 'multipart/form-data',
                        'params' => array(
                            'title' => 'name',
                            'text' => 'description',
                            'url' => 'link',
                            'files' => array(
                                array('name' => 'file[]',
                                    'accept' => array('*/*')
                                ),
                            ),
                        ),
                    ),
                );

                core_Cache::set(
                    self::MANIFEST_CACHE_TYPE,
                    $cacheHandler,
                    $json,
                    self::MANIFEST_CACHE_LIFETIME,
                    $cacheDepends
                );
            } finally {
                if ($domainLg) {
                    core_Lg::pop();
                }
            }
        }

        return json_encode($json);
    }


    /**
     * Подготвя и регистрира валидните икони за манифеста
     *
     * Размерите на растерните файлове винаги се прочитат от самия файл. Името
     * в архива не е надежден източник и не се използва за `sizes`.
     *
     * @param stdClass $rec
     * @param int      $domainId
     * @param string   $webrootDomain
     *
     * @return array
     */
    protected static function prepareManifestIcons($rec, $domainId, $webrootDomain)
    {
        $iconInfoArr = array();
        $resizeSource = null;
        if (!empty($rec->icons)) {
            $iconInfoArr = self::extractCustomManifestIcons($rec->icons, $webrootDomain, $resizeSource);
        }

        $existingSizes = array();
        foreach ($iconInfoArr as $iconInfo) {
            $existingSizes[$iconInfo['sizes']] = true;
        }

        // При валидна потребителска икона допълваме липсващите размери от
        // нея. Така архив само с един оригинал остава напълно поддържан.
        // Всеки резултат е отделен PNG с проверим реален размер.
        foreach (self::getManifestIconSizes() as $iconSize) {
            $sizes = $iconSize . 'x' . $iconSize;
            if (isset($existingSizes[$sizes])) {
                continue;
            }

            $content = false;
            if ($resizeSource) {
                $content = self::resizeRasterIcon($resizeSource['content'], $iconSize);
            }

            if ($content !== false) {
                $fileName = 'pwa-icon-custom-' . $sizes . '-' . substr(md5($content), 0, 12) . '.png';
                core_Webroot::register($content, 'Content-Type: image/png', $fileName, $webrootDomain);
                $iconInfoArr[] = array(
                    'src' => '/' . $fileName,
                    'sizes' => $sizes,
                    'type' => 'image/png',
                );
                continue;
            }

            // Ако няма използваем потребителски източник, вземаме точно
            // съответстващия вграден файл. Favicon никога не се преизползва.
            $sourcePath = getFullPath("pwa/icons/icon-{$sizes}.png");
            $imageInfo = self::getRasterIconInfo($sourcePath);
            if (!$imageInfo || $imageInfo['width'] != $iconSize || $imageInfo['height'] != $iconSize) {
                continue;
            }

            $content = @file_get_contents($sourcePath);
            if ($content === false) {
                continue;
            }

            $fileName = 'pwa-icon-' . $sizes . '-' . substr(md5($content), 0, 12) . '.png';
            core_Webroot::register($content, 'Content-Type: image/png', $fileName, $webrootDomain);
            $iconInfoArr[] = array(
                'src' => '/' . $fileName,
                'sizes' => $sizes,
                'type' => 'image/png',
            );
        }

        return $iconInfoArr;
    }


    /**
     * Извлича безопасните растерни икони от потребителския архив
     *
     * @param string $fileHnd
     * @param string $webrootDomain
     *
     * @return array
     */
    protected static function extractCustomManifestIcons($fileHnd, $webrootDomain, &$resizeSource = null)
    {
        $result = array();
        $archiveInst = null;
        $seenIcons = array();

        try {
            $archiveInst = cls::get('archive_Adapter', array('fileHnd' => $fileHnd));
            $entriesArr = $archiveInst->getEntries();
            if (!is_array($entriesArr)) {
                return $result;
            }

            $maxEntrySize = (int) archive_Setup::get('MAX_LEN');
            $candidateCnt = 0;
            foreach ($entriesArr as $entry) {
                $path = $entry->getPath();
                $extension = strtolower(fileman_Files::getExt($path));
                if (!in_array($extension, array('png', 'jpg', 'jpeg', 'webp', 'ico'), true)) {
                    continue;
                }

                $candidateCnt++;
                if ($candidateCnt > self::MAX_CUSTOM_ICONS) {
                    break;
                }

                $entrySize = (int) $entry->getSize();
                if ($entrySize <= 0 || ($maxEntrySize > 0 && $entrySize > $maxEntrySize)) {
                    continue;
                }

                if (!self::isSafeArchiveIconPath($path)) {
                    continue;
                }

                try {
                    // Четем съдържанието през 7z към stdout, без да извличаме
                    // подадения от архива път във файловата система.
                    $content = $entry->getContent();
                } catch (Throwable $e) {
                    continue;
                }

                $actualSize = strlen($content);
                if (!$actualSize || ($maxEntrySize > 0 && $actualSize > $maxEntrySize)) {
                    continue;
                }

                $imageInfo = self::getRasterIconInfoFromContent($content);
                if (!$imageInfo) {
                    continue;
                }

                $sizes = $imageInfo['width'] . 'x' . $imageInfo['height'];
                $contentHash = md5($content);
                $iconKey = $sizes . '|' . $contentHash;
                if (isset($seenIcons[$iconKey])) {
                    continue;
                }
                $seenIcons[$iconKey] = true;

                $fileName = 'pwa-icon-custom-' . $sizes . '-' . substr($contentHash, 0, 12) . '.' . $imageInfo['extension'];
                core_Webroot::register($content, 'Content-Type: ' . $imageInfo['mime'], $fileName, $webrootDomain);
                $result[] = array(
                    'src' => '/' . $fileName,
                    'sizes' => $sizes,
                    'type' => $imageInfo['mime'],
                );

                // ICO може да е валиден за манифеста, но GD не го декодира
                // надеждно. За генерирането предпочитаме най-големия
                // декодируем растерен оригинал. Квадратната форма е само
                // последен критерий при еднаква площ.
                if ($imageInfo['extension'] !== 'ico') {
                    $sourceScore = 2 * $imageInfo['width'] * $imageInfo['height'];
                    $sourceScore += ($imageInfo['width'] === $imageInfo['height']) ? 1 : 0;
                    if (!$resizeSource || $sourceScore > $resizeSource['score']) {
                        $resizeSource = array(
                            'content' => $content,
                            'score' => $sourceScore,
                        );
                    }
                }
            }
        } catch (Throwable $e) {
            // Невалиден архив не трябва да прекъсва генерирането. Липсващите
            // размери ще бъдат попълнени с вградените икони.
        } finally {
            if ($archiveInst) {
                try {
                    $archiveInst->deleteTempPath();
                } catch (Throwable $e) {
                    // Временният файл може вече да е премахнат от адаптера.
                }
            }
        }

        return $result;
    }


    /**
     * Генерира PNG с точен квадратен размер чрез общия bgERP image helper
     *
     * @param string $content
     * @param int    $size
     *
     * @return string|false
     */
    public static function resizeRasterIcon($content, $size)
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            return false;
        }

        $sourceImage = @imagecreatefromstring($content);
        if (!$sourceImage) {
            return false;
        }

        $resizedImage = null;
        $bufferLevel = ob_get_level();
        try {
            $resizedImage = thumb_Img::scaleGdImg($sourceImage, $size, $size, 'png');
            if (!$resizedImage) {
                return false;
            }

            ob_start();
            $saved = @imagepng($resizedImage);
            $result = ob_get_clean();
            if (!$saved || $result === '') {
                return false;
            }

            return $result;
        } catch (Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            return false;
        } finally {
            if ($resizedImage) {
                imagedestroy($resizedImage);
            }
            imagedestroy($sourceImage);
        }
    }


    /**
     * Проверява дали пътят в архива не излиза извън временната директория
     *
     * @param string $path
     *
     * @return bool
     */
    protected static function isSafeArchiveIconPath($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        if ($path === '' || strpos($path, "\0") !== false || $path[0] === '/' || preg_match('/^[a-z]:\//i', $path)) {
            return false;
        }

        foreach (explode('/', $path) as $part) {
            if ($part === '..') {
                return false;
            }
        }

        return true;
    }


    /**
     * Връща реалните размери и MIME тип на растерно изображение
     *
     * @param string|false $path
     *
     * @return array|false
     */
    protected static function getRasterIconInfo($path)
    {
        if (!$path || !is_file($path)) {
            return false;
        }

        $imageInfo = @getimagesize($path);

        return self::normalizeRasterIconInfo($imageInfo);
    }


    /**
     * Връща реалните размери и MIME тип от съдържание на изображение
     *
     * @param string $content
     *
     * @return array|false
     */
    protected static function getRasterIconInfoFromContent($content)
    {
        if (!function_exists('getimagesizefromstring')) {
            return false;
        }

        return self::normalizeRasterIconInfo(@getimagesizefromstring($content));
    }


    /**
     * Нормализира информацията за поддържано растерно изображение
     *
     * @param array|false $imageInfo
     *
     * @return array|false
     */
    protected static function normalizeRasterIconInfo($imageInfo)
    {
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1]) || empty($imageInfo['mime'])) {
            return false;
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        if ($width < 1 || $height < 1 || $width > self::MAX_ICON_DIMENSION || $height > self::MAX_ICON_DIMENSION) {
            return false;
        }

        $mime = strtolower($imageInfo['mime']);
        $extensions = array(
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
        );
        if (!isset($extensions[$mime])) {
            return false;
        }

        return array(
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
            'extension' => $extensions[$mime],
        );
    }


    /**
     * Връща информация за иконата на пряк път без да измисля размер
     *
     * @param string|null $fileHnd
     *
     * @return array|false
     */
    protected static function getShortcutIconInfo($fileHnd)
    {
        if (!$fileHnd) {
            return false;
        }

        try {
            $path = fileman_Files::fetchByFh($fileHnd, 'path');
            $imageInfo = self::getRasterIconInfo($path);
        } catch (Throwable $e) {
            return false;
        }

        if (!$imageInfo) {
            return false;
        }

        return array(
            'src' => toUrl(array('fileman_Download', 'Serve', 'fh' => $fileHnd)),
            'sizes' => $imageInfo['width'] . 'x' . $imageInfo['height'],
            'type' => $imageInfo['mime'],
        );
    }


    /**
     * Връща стандартните размери като масив
     *
     * @return array
     */
    protected static function getManifestIconSizes()
    {
        return array_map('intval', explode(',', self::MANIFEST_ICON_SIZES));
    }


    /**
     * Манипулатор на кеша за генериран манифест
     *
     * @param int $domainId
     *
     * @return string
     */
    protected static function getManifestCacheHandler($domainId)
    {
        return 'manifest_' . self::MANIFEST_ASSETS_VERSION . '_' . (int) $domainId;
    }


    /**
     * Връща съдържанието, което трябва да се публикува за домейна
     *
     * Потребителският pwa.webmanifest от cms_Domains::wrFiles има предимство
     * пред генерирания, както при инсталиране на пакета.
     *
     * @param int $domainId
     *
     * @return false|string
     */
    public static function getManifestContentsForDomain($domainId)
    {
        $customFiles = self::getCustomWebrootFiles($domainId);
        if (array_key_exists('pwa.webmanifest', $customFiles)) {
            return $customFiles['pwa.webmanifest'];
        }

        return self::getPWAManifest($domainId);
    }


    /**
     * Връща Service Worker-а, който трябва да се публикува за домейна
     *
     * Потребителският serviceWorker.js от cms_Domains::wrFiles има
     * предимство пред стандартния файл на пакета.
     *
     * @param int $domainId
     *
     * @return false|string
     */
    public static function getServiceWorkerContentsForDomain($domainId)
    {
        $customFiles = self::getCustomWebrootFiles($domainId);
        if (array_key_exists('serviceworker.js', $customFiles)) {
            return $customFiles['serviceworker.js'];
        }

        $defaultPath = getFullPath('pwa/js/sw.js');
        if (!$defaultPath || !is_file($defaultPath)) {
            return false;
        }

        return @file_get_contents($defaultPath);
    }


    /**
     * Публикува манифеста и Service Worker-а за конкретен домейн
     *
     * Това е единственото място, което записва двата основни PWA webroot
     * файла. Така Setup, промяната на настройките и автоматичното
     * възстановяване използват еднакви overrides, MIME headers и версии.
     *
     * @param int $domainId
     *
     * @return array|false
     */
    public static function publishWebrootFilesForDomain($domainId)
    {
        $domainId = (int) $domainId;
        if (!$domainId || !cms_Domains::fetch($domainId)) {
            return false;
        }
        $webrootDomain = self::getWebrootDomain($domainId);
        if (!$webrootDomain) {
            return false;
        }

        // Настройката може току-що да е записана. Не използваме стария
        // генериран manifest, но запазваме стандартното dependency кеширане
        // за следващите прочитания.
        core_Cache::remove(self::MANIFEST_CACHE_TYPE, self::getManifestCacheHandler($domainId));

        $customFiles = self::getCustomWebrootFiles($domainId);
        if (array_key_exists('pwa.webmanifest', $customFiles)) {
            $manifest = $customFiles['pwa.webmanifest'];
        } else {
            $manifest = self::getPWAManifest($domainId, $webrootDomain);
        }

        if (array_key_exists('serviceworker.js', $customFiles)) {
            $serviceWorker = $customFiles['serviceworker.js'];
        } else {
            $defaultPath = getFullPath('pwa/js/sw.js');
            $serviceWorker = ($defaultPath && is_file($defaultPath)) ? @file_get_contents($defaultPath) : false;
        }

        if ($manifest === false || $serviceWorker === false) {
            return false;
        }

        $manifestBefore = core_Webroot::isExists('pwa.webmanifest', $webrootDomain)
            ? core_Webroot::getContents('pwa.webmanifest', $webrootDomain)
            : null;
        $serviceWorkerBefore = core_Webroot::isExists('serviceworker.js', $webrootDomain)
            ? core_Webroot::getContents('serviceworker.js', $webrootDomain)
            : null;

        // Записваме и при еднакво съдържание. cms_Domains може да е
        // публикувал override със същите bytes, но с MIME по разширение.
        core_Webroot::register(
            $manifest,
            'Content-Type: application/manifest+json',
            'pwa.webmanifest',
            $webrootDomain
        );
        core_Webroot::register(
            $serviceWorker,
            'Content-Type: text/javascript',
            'serviceworker.js',
            $webrootDomain
        );

        $manifestExists = core_Webroot::isExists('pwa.webmanifest', $webrootDomain);
        $serviceWorkerExists = core_Webroot::isExists('serviceworker.js', $webrootDomain);
        $manifestAfter = $manifestExists
            ? core_Webroot::getContents('pwa.webmanifest', $webrootDomain)
            : null;
        $serviceWorkerAfter = $serviceWorkerExists
            ? core_Webroot::getContents('serviceworker.js', $webrootDomain)
            : null;

        if ($manifestExists) {
            self::setManifestVersion($domainId, $manifestAfter);
        } else {
            self::removeManifestVersion($domainId);
        }

        if (cls::load('pwa_Plugin', true)) {
            if ($serviceWorkerExists) {
                pwa_Plugin::setServiceWorkerVersion($domainId, $serviceWorkerAfter);
            } else {
                pwa_Plugin::removeServiceWorkerVersion($domainId);
            }
        }

        return array(
            'success' => $manifestExists && $serviceWorkerExists,
            'manifest' => $manifestExists,
            'serviceWorker' => $serviceWorkerExists,
            'manifestChanged' => $manifestBefore !== $manifestAfter,
            'serviceWorkerChanged' => $serviceWorkerBefore !== $serviceWorkerAfter,
        );
    }


    /**
     * Възстановява липсващите основни PWA файлове с кратък retry throttle
     *
     * При нормална работа методът прави само две проверки за съществуване.
     * Кешът предпазва от тежко генериране на всеки хит при постоянен проблем
     * с файловата система.
     *
     * @param int $domainId
     *
     * @return bool
     */
    public static function ensureWebrootFilesForDomain($domainId)
    {
        $webrootDomain = self::getWebrootDomain($domainId);
        if (!$webrootDomain) {
            return false;
        }

        if (
            core_Webroot::isExists('pwa.webmanifest', $webrootDomain)
            && core_Webroot::isExists('serviceworker.js', $webrootDomain)
        ) {
            return true;
        }

        $handler = md5(self::getManifestHost($domainId));
        if (core_Cache::get(self::WEBROOT_REPAIR_CACHE_TYPE, $handler) !== false) {
            return false;
        }

        // Маркерът се поставя преди генерирането. При exception или
        // непреодолима грешка следващият хит няма да повтори веднага опита.
        core_Cache::set(
            self::WEBROOT_REPAIR_CACHE_TYPE,
            $handler,
            true,
            self::WEBROOT_REPAIR_CACHE_LIFETIME
        );

        $res = self::regenerateManifestForDomain($domainId);
        if ($res) {
            core_Cache::remove(self::WEBROOT_REPAIR_CACHE_TYPE, $handler);
        }

        return $res;
    }


    /**
     * Публикува отново физическите PWA файлове за реалния хост
     *
     * Няколко езикови записа могат да споделят един хост и един webroot.
     * Затова преизчисляваме крайния активен запис в същия ред, в който
     * настройката на пакета ги обхожда, вместо да презапишем файла само с
     * текущия езиков вариант.
     *
     * @param int $domainId
     *
     * @return bool
     */
    public static function regenerateManifestForDomain($domainId)
    {
        return self::regenerateManifestForHost(self::getManifestHost($domainId));
    }


    /**
     * Публикува отново физическите PWA файлове за посочен реален хост
     *
     * @param string $host
     *
     * @return bool
     */
    public static function regenerateManifestForHost($host)
    {
        $host = strtolower(trim(cms_Domains::getReal($host)));
        self::invalidateManifestCachesForHost($host);

        $selectedDomainId = null;
        $query = self::getQuery();
        $query->where("#state != 'closed'");
        $query->orderBy('id', 'ASC');
        while ($settingsRec = $query->fetch()) {
            if (self::getManifestHost($settingsRec->domainId) === $host) {
                $selectedDomainId = $settingsRec->domainId;
            }
        }

        if (!$selectedDomainId) {
            core_Webroot::remove('pwa.webmanifest', $host);
            core_Webroot::remove('serviceworker.js', $host);
            self::removeManifestVersionForHost($host);
            if (cls::load('pwa_Plugin', true)) {
                pwa_Plugin::removeServiceWorkerVersionForHost($host);
            }

            return false;
        }

        $published = self::publishWebrootFilesForDomain($selectedDomainId);

        return $published && !empty($published['success']);
    }


    /**
     * Връща версията на публикувания манифест
     *
     * @param int|null $domainId
     *
     * @return string
     */
    public static function getManifestVersion($domainId = null)
    {
        if (!isset($domainId)) {
            $domainId = cms_Domains::getCurrent('id', false);
        }

        $handler = self::getManifestVersionHandler($domainId);
        if (array_key_exists($handler, self::$manifestVersions)) {
            return self::$manifestVersions[$handler];
        }

        $version = core_Cache::get(self::MANIFEST_VERSION_CACHE_TYPE, $handler);
        if ($version === false && core_Webroot::isExists('pwa.webmanifest', $domainId)) {
            $version = self::setManifestVersion(
                $domainId,
                core_Webroot::getContents('pwa.webmanifest', $domainId)
            );
        }

        if ($version === false) {
            $version = '';
        }

        self::$manifestVersions[$handler] = $version;

        return $version;
    }


    /**
     * Записва версията на публикувания манифест
     *
     * @param int    $domainId
     * @param string $contents
     *
     * @return string
     */
    public static function setManifestVersion($domainId, $contents)
    {
        $handler = self::getManifestVersionHandler($domainId);
        $version = md5((string) $contents);
        core_Cache::set(
            self::MANIFEST_VERSION_CACHE_TYPE,
            $handler,
            $version,
            self::MANIFEST_CACHE_LIFETIME
        );
        self::$manifestVersions[$handler] = $version;

        return $version;
    }


    /**
     * Премахва версията на манифеста за споделения хост
     *
     * @param int $domainId
     */
    public static function removeManifestVersion($domainId)
    {
        self::removeManifestVersionForHost(self::getManifestHost($domainId));
    }


    /**
     * Премахва версията на манифеста по реален хост
     *
     * @param string $host
     */
    protected static function removeManifestVersionForHost($host)
    {
        $handler = md5(strtolower(trim($host)));
        core_Cache::remove(self::MANIFEST_VERSION_CACHE_TYPE, $handler);
        unset(self::$manifestVersions[$handler]);
    }


    /**
     * Връща потребителските PWA файлове от архива със статични файлове
     *
     * Разрешени са само двата root файла. Архивът се прочита еднократно,
     * независимо дали override има за единия или и за двата.
     *
     * @param int $domainId
     *
     * @return array
     */
    protected static function getCustomWebrootFiles($domainId)
    {
        $result = array();
        $domainRec = cms_Domains::fetch($domainId);
        if (!$domainRec || empty($domainRec->wrFiles)) {
            return $result;
        }

        $archiveInst = null;
        try {
            $archiveInst = cls::get('archive_Adapter', array('fileHnd' => $domainRec->wrFiles));
            $entries = $archiveInst->getEntries();
            if (!is_array($entries)) {
                return $result;
            }

            $allowedFiles = array(
                'pwa.webmanifest' => true,
                'serviceworker.js' => true,
            );
            $maxSize = (int) archive_Setup::get('MAX_LEN');
            foreach ($entries as $entry) {
                $path = strtolower(trim(str_replace('\\', '/', $entry->getPath())));
                if (!isset($allowedFiles[$path])) {
                    continue;
                }

                $size = (int) $entry->getSize();
                if ($size < 0 || ($maxSize > 0 && $size > $maxSize)) {
                    continue;
                }

                $content = $entry->getContent();
                if ($content === false || $content === '' || ($maxSize > 0 && strlen($content) > $maxSize)) {
                    continue;
                }

                if ($path === 'pwa.webmanifest') {
                    json_decode($content);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue;
                    }
                }

                $result[$path] = $content;
                if (count($result) === count($allowedFiles)) {
                    break;
                }
            }
        } catch (Throwable $e) {
            return $result;
        } finally {
            if ($archiveInst) {
                try {
                    $archiveInst->deleteTempPath();
                } catch (Throwable $e) {
                    // Временният файл може вече да е премахнат от адаптера.
                }
            }
        }

        return $result;
    }


    /**
     * Инвалидира генерираните манифести за всички езици на един хост
     *
     * @param string $host
     */
    protected static function invalidateManifestCachesForHost($host)
    {
        $query = self::getQuery();
        while ($settingsRec = $query->fetch()) {
            if (self::getManifestHost($settingsRec->domainId) === $host) {
                core_Cache::remove(
                    self::MANIFEST_CACHE_TYPE,
                    self::getManifestCacheHandler($settingsRec->domainId)
                );
            }
        }
    }


    /**
     * Връща webroot домейна за конкретен cms_Domains запис
     *
     * За реалните домейни използваме директно стойността от записа, без
     * текущия HTTP host. Само историческият запис `localhost` остава
     * динамичен по дефиницията на cms_Domains::getReal().
     *
     * @param int $domainId
     *
     * @return string|false
     */
    protected static function getWebrootDomain($domainId)
    {
        $domain = cms_Domains::fetchField((int) $domainId, 'domain');
        if (!$domain) {
            return false;
        }

        $domain = strtolower(trim($domain));
        if ($domain === 'localhost') {
            $domain = strtolower(trim(cms_Domains::getReal($domain)));
        }

        return $domain;
    }


    /**
     * Връща нормализирания реален хост на домейна
     *
     * @param int $domainId
     *
     * @return string
     */
    protected static function getManifestHost($domainId)
    {
        $domain = self::getWebrootDomain($domainId);
        if (!$domain) {
            return 'domainId_' . (int) $domainId;
        }

        return $domain;
    }


    /**
     * Манипулатор за версията на физическия манифест
     *
     * @param int $domainId
     *
     * @return string
     */
    protected static function getManifestVersionHandler($domainId)
    {
        return md5(self::getManifestHost($domainId));
    }


    /**
     * Запомня стария домейн при преместване на настройката
     */
    public static function on_BeforeSave($mvc, &$id, &$rec, $fields = null)
    {
        if ($id) {
            $rec->_pwaOldDomainId = $mvc->fetchField($id, 'domainId', false);
        }
    }


    /**
     * Обновява физическия манифест веднага след запис
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fields = null)
    {
        $savedRec = $mvc->fetch($id, '*', false);
        if (!$savedRec) {
            return;
        }

        $domainIds = array($savedRec->domainId => $savedRec->domainId);
        if (!empty($rec->_pwaOldDomainId)) {
            $domainIds[$rec->_pwaOldDomainId] = $rec->_pwaOldDomainId;
        }

        foreach ($domainIds as $domainId) {
            try {
                self::regenerateManifestForDomain($domainId);
            } catch (Throwable $e) {
                reportException($e);
                $mvc->logWarning('Грешка при обновяване на PWA манифеста', $id);
            }
        }

        // plg_State2 извиква отделно AfterChangeState след save().
        $rec->_pwaManifestRegenerated = true;
    }


    /**
     * Подсигурява обновяването и при ръчна смяна на състоянието
     */
    public static function on_AfterChangeState($mvc, $rec, $newState)
    {
        if (!empty($rec->_pwaManifestRegenerated)) {
            return;
        }

        try {
            self::regenerateManifestForDomain($rec->domainId);
        } catch (Throwable $e) {
            reportException($e);
            $mvc->logWarning('Грешка при обновяване на PWA манифеста', $rec->id);
        }
    }


    /**
     * Помощна фунцкция за проверка дали може да се използва PWA
     *
     * @return string - yes|no
     */
    public static function canUse($dId = null)
    {
        if (!core_Packs::isInstalled('pwa')) {

            return 'no';
        }

        $defSettings = pwa_Settings::getDomains();
        if (empty($defSettings)) {

            return 'no';
        }

        if (isset($dId) && $dId > 0) {
            $pDomain = cms_Domains::fetchField($dId, 'domain');
        } else {
            $pDomain = cms_Domains::getPublicDomain('domain');
        }

        foreach ($defSettings as $domainId => $domainName) {
            if ($pDomain == $domainName) {

                return 'yes';
            }
        }

        return 'no';
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;

        $appTitle = tr(core_Setup::get('EF_APP_TITLE', true));
        $text = tr('интегрирана система за управление');

        $form->setDefault('name', $appTitle . ' - ' . $text);
        $form->setDefault('description', $appTitle . ' - ' . $text);
        $form->setDefault('shortName', $appTitle);
        $form->setDefault('display', 'standalone');
        $form->setDefault('backgroundColor', '#fff');
        $form->setDefault('themeColor', '#ddd');
        $form->setDefault('startUrl', '/?isPwa=yes');
        $form->setDefault('scope', '/');
        $form->setDefault('orientation', 'any');

        $form->setDefault('sc1Name', 'Сканиране на баркод');
        $form->setDefault('sc1ShortName', 'Баркод');
        $form->setDefault('sc1Description', 'Сканиране и търсене на информация за баркод');
        $form->setDefault('sc1Url', '/barcode_Search');

        $form->setDefault('state', 'active');
    }
}
