<?php


/**
 * Дали да се показват броя на страниците
 */
defIfNot('CHROMEPHP_SHOW_PAGE_NUMBERS', 'no');


/**
 * Път до изпълнимия файл на Chrome
 * chrome
 * chromium-browser
 */
defIfNot('CHROMEPHP_BIN_PATH', '');


/** Досегашният общ Composer пакет остава избран по подразбиране. */
defIfNot('CHROMEPHP_VERSION', 'shared');


/**
 * Колко секунди се чакат ресурсите на страницата (картинки, скриптове) преди печата на PDF
 */
defIfNot('CHROMEPHP_LOAD_TIMEOUT', 20);


/**
 * Колко секунди се чака отговор от Chrome (нов таб, печат на PDF)
 */
defIfNot('CHROMEPHP_RESPONSE_TIMEOUT', 30);


/**
 *
 *
 * @package   chromephp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://github.com/chrome-php/chrome
 */
class chromephp_Setup extends core_ProtoSetup
{

    /**
     * Информация за пакета
     */
    public $info = 'Chrome PHP конвертиране на HTML към PDF';


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'chromephp_Browser';



    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CHROMEPHP_VERSION' => array('enum(shared=Общ Composer пакет,1.16.1)', 'mandatory, caption=Chrome PHP->Версия'),
        'CHROMEPHP_SHOW_PAGE_NUMBERS' => array('enum(no=Не,yes=Да)', 'caption=Дали да се показват броя на страниците->Избор'),
        'CHROMEPHP_BIN_PATH' => array('varchar', 'caption=Път до изпълнимия файл на Chrome->Път'),
        'CHROMEPHP_LOAD_TIMEOUT' => array('int(min=1,max=120)', 'caption=Изчакване на ресурсите на страницата преди печат->Секунди'),
        'CHROMEPHP_RESPONSE_TIMEOUT' => array('int(min=5,max=300)', 'caption=Изчакване на отговор от Chrome->Секунди'),
    );


    /**
     * Ограничава избора на бинарник само до реално открити на системата
     * Chrome/Chromium изпълними файлове - предпазва от въвеждане на
     * произволна команда в полето
     *
     * @param core_Form $configForm
     * @return void
     */
    public function manageConfigDescriptionForm(&$configForm)
    {
        if (!$configForm->getField('CHROMEPHP_BIN_PATH', false)) {

            return;
        }

        $runtime = self::getRuntimeInfo();
        $info = '<div>' . ht::escapeAttr($runtime) . '</div>';
        $probe = Mode::get('chromephpLastProbe');
        if (($probe['runtime'] ?? null) === $runtime) {
            $info .= '<div>' . tr('Последна проба с празен таб||Last empty tab check') . ' (' . ht::escapeAttr($probe['time'] ?? '') . ')';
            if (!empty($probe['product'])) {
                $info .= ' · ' . ht::escapeAttr($probe['product']);
            }
            foreach (array('startup' => 'Старт', 'page' => 'Празен таб||Empty tab') as $key => $caption) {
                if (isset($probe[$key])) {
                    $info .= ' · ' . tr($caption) . ' ' . ht::escapeAttr($probe[$key]) . ' ' . tr('сек.');
                }
            }
            $info .= empty($probe['error']) ? ' · ' . tr('Успешна||Passed') : '<br><span class="red">' . ht::escapeAttr($probe['error']) . '</span>';
            $info .= '</div>';
        }
        $configForm->setInfoBeforeField('CHROMEPHP_VERSION', $info);
        $configForm->toolbar->addBtn('Проба с празен таб||Test empty tab',
            array('chromephp_Browser', 'checkBrowser', 'Cf' => Request::getSessHash('checkBrowser')),
            'ef_icon=img/16/cog.png,target=_blank,title=Проверка със записаните настройки и лимит 5 секунди за команда');

        $options = $this->getAvailableBinPaths();
        if (countR($options)) {
            $configForm->setOptions('CHROMEPHP_BIN_PATH', $options);
            $configForm->setField('CHROMEPHP_BIN_PATH', 'mandatory');
        } else {
            $configForm->setReadOnly('CHROMEPHP_BIN_PATH');
            $configForm->info = "<div class='red'>" . tr('Не е намерен инсталиран Chrome/Chromium бинарник. Инсталирайте пакета отново, за да бъде изтеглен автоматично|*.') . '</div>';
        }
    }


    /**
     * Търси реално налични на системата Chrome/Chromium изпълними файлове -
     * текущо зададения (ако е валиден), системно инсталираните и
     * автоматично изтегления chrome-headless-shell
     *
     * @return array $path => $path
     */
    private function getAvailableBinPaths()
    {
        $paths = array();

        $current = trim((string) chromephp_Setup::get('BIN_PATH'));
        if (is_file($current) && is_executable($current)) {
            $paths[$current] = $current;
        }

        $paths += chromephp_Browser::getSystemBinPaths();

        // Дефинира EF_VENDOR_PATH, ако core_Composer още не е зареждан
        cls::load('core_Composer');

        $vendorBin = rtrim(EF_VENDOR_PATH, '/') . '/chrome/chrome-headless-shell-linux64/chrome-headless-shell';
        if (is_executable($vendorBin)) {
            $paths[$vendorBin] = $vendorBin;
        }

        return $paths;
    }


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $version = self::get('VERSION');
        $html .= core_Composer::install('chrome-php/chrome', $version === 'shared' ? '' : $version, $version !== 'shared');
        if (!core_Composer::isInUse()) {
            $html .= "<li class='red'>Проблем при зареждането на composer</li>";
        }

        $html .= $this->installChromeBin();

        return $html;
    }


    /** Проверява избраната версия в общия vendor без изпълнение на Composer. */
    public static function isLibraryInstalled()
    {
        $factory = dirname(core_Composer::getAutoloadPath()) . '/chrome-php/chrome/src/BrowserFactory.php';
        if (!is_readable($factory)) {
            return false;
        }
        $installed = self::getLibraryVersion();
        $version = self::get('VERSION');

        return $installed !== null && ($version === 'shared' || version_compare(ltrim($installed, 'v'), $version, '=='));
    }


    /** Чете локалните метаданни на Composer 2 или Composer 1 без CLI и autoload. */
    private static function getLibraryVersion()
    {
        $dir = dirname(core_Composer::getAutoloadPath()) . '/composer';
        if (is_readable($dir . '/installed.json')) {
            $data = json_decode((string) file_get_contents($dir . '/installed.json'), true);
            foreach ((array) ($data['packages'] ?? $data) as $package) {
                if (($package['name'] ?? '') === 'chrome-php/chrome') {
                    return $package['version'] ?? null;
                }
            }
        }

        return null;
    }


    /** Данни за активната инсталация, достъпни и при неуспешен старт на Chrome. */
    public static function getRuntimeInfo()
    {
        return 'PHP ' . PHP_VERSION . ' · chrome-php/chrome ' . (self::getLibraryVersion() ?? '?')
            . ' · ' . (chromephp_Browser::getBinPath() ?: '?');
    }


    /** Зарежда общия Composer autoloader след проверка на избраната версия. */
    public static function loadLibrary()
    {
        if (self::get('VERSION') !== 'shared' && version_compare(PHP_VERSION, '7.4.15', '<')) {
            return false;
        }

        return self::isLibraryInstalled() && core_Composer::isInUse();
    }


    /** Показва липсващите изисквания през стандартните настройки на core_Packs. */
    public function checkConfig()
    {
        if (self::get('VERSION') !== 'shared' && version_compare(PHP_VERSION, '7.4.15', '<')) {
            return 'Chrome PHP 1.16.1 изисква PHP 7.4.15 или по-нова версия.';
        }
        if (!self::isLibraryInstalled()) {
            return 'Липсва избраната библиотека Chrome PHP. Инициализирайте пакета chromephp.';
        }
        if (!chromephp_Browser::getBinPath()) {
            return 'Не е намерен Chrome/Chromium. Инициализирайте пакета или изберете инсталиран браузър.';
        }
    }


    /**
     * Осигурява изпълним файл на Chrome - търси системно инсталиран,
     * а ако няма такъв - изтегля портативен chrome-headless-shell
     * от дистрибуцията "Chrome for Testing" в EF_VENDOR_PATH
     *
     * @return string
     */
    private function installChromeBin()
    {
        // Ако вече е зададен работещ бинарник - нищо не правим
        $binPath = trim((string) chromephp_Setup::get('BIN_PATH'));
        if (is_file($binPath) && is_executable($binPath)) {

            return "<li><strong>Chrome</strong>: използва се `{$binPath}`</li>";
        }

        // Търсим системно инсталиран Chrome/Chromium
        $paths = chromephp_Browser::getSystemBinPaths();
        if ($found = reset($paths)) {
            core_Packs::setConfig('chromephp', array('CHROMEPHP_BIN_PATH' => $found));

            return "<li class='debug-new'><strong>Chrome</strong>: намерен е системен бинарник `{$found}`</li>";
        }

        if (PHP_OS_FAMILY != 'Linux' || php_uname('m') != 'x86_64') {

            return "<li class='debug-error'>Chrome не е намерен и не може да се инсталира автоматично за тази платформа. Задайте ръчно CHROMEPHP_BIN_PATH</li>";
        }

        // Дефинира EF_VENDOR_PATH, ако core_Composer още не е зареждан
        cls::load('core_Composer');

        $vPath = rtrim(EF_VENDOR_PATH, '/');
        $binFile = $vPath . '/chrome/chrome-headless-shell-linux64/chrome-headless-shell';

        if (!is_executable($binFile)) {
            if (!class_exists('ZipArchive')) {

                return "<li class='debug-error'><strong>Chrome</strong>: липсва PHP разширението `zip`, необходимо за разархивиране</li>";
            }

            core_App::setTimeLimit(600);

            // Определяме последната стабилна версия на Chrome for Testing
            $json = @file_get_contents('https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions.json');
            $version = json_decode((string) $json)->channels->Stable->version ?? null;
            if (!$version) {

                return "<li class='debug-error'><strong>Chrome</strong>: грешка при определяне на последната версия на Chrome for Testing</li>";
            }

            // Изтегляме архива
            $url = "https://storage.googleapis.com/chrome-for-testing-public/{$version}/linux64/chrome-headless-shell-linux64.zip";
            $zipPath = $vPath . '/chrome-headless-shell-linux64.zip';
            if (!@copy($url, $zipPath)) {

                return "<li class='debug-error'><strong>Chrome</strong>: грешка при изтегляне на `{$url}`</li>";
            }

            // Разархивираме в EF_VENDOR_PATH/chrome
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true || !$zip->extractTo($vPath . '/chrome')) {
                @unlink($zipPath);

                return "<li class='debug-error'><strong>Chrome</strong>: грешка при разархивиране на `{$zipPath}`</li>";
            }
            $zip->close();
            @unlink($zipPath);

            @chmod($binFile, 0755);

            if (!is_executable($binFile)) {

                return "<li class='debug-error'><strong>Chrome</strong>: `{$binFile}` не е изпълним след разархивиране</li>";
            }
        }

        core_Packs::setConfig('chromephp', array('CHROMEPHP_BIN_PATH' => $binFile));

        $res = "<li class='debug-new'><strong>Chrome</strong>: инсталиран е chrome-headless-shell в `{$binFile}`</li>";

        // Проверяваме за липсващи системни библиотеки
        $missing = trim((string) shell_exec('ldd ' . escapeshellarg($binFile) . " 2>/dev/null | grep 'not found'"));
        if (strlen($missing)) {
            $missing = str_replace("\n", '; ', $missing);
            $res .= "<li class='debug-error'><strong>Chrome</strong>: липсват системни библиотеки, инсталирайте ги с root права: {$missing}</li>";
        }

        return $res;
    }
}
