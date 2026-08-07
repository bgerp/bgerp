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


/**
 *
 *
 * @package   chromephp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
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
        'CHROMEPHP_SHOW_PAGE_NUMBERS' => array('enum(no=Не,yes=Да)', 'caption=Дали да се показват броя на страниците->Избор'),
        'CHROMEPHP_BIN_PATH' => array('varchar', 'caption=Път до изпълнимия файл на Chrome->Път'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $html .= core_Composer::install('chrome-php/chrome');

        if (!core_Composer::isInUse()) {
            $html .= "<li class='red'>Проблем при зареждането на composer</li>";
        }

        $html .= $this->installChromeBin();

        return $html;
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
        if (strlen($binPath) && is_executable($binPath)) {

            return "<li><strong>Chrome</strong>: използва се `{$binPath}`</li>";
        }

        // Търсим системно инсталиран Chrome/Chromium
        foreach (array('chrome', 'google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser') as $bin) {
            $found = trim((string) shell_exec("command -v {$bin} 2>/dev/null"));
            if (strlen($found)) {
                core_Packs::setConfig('chromephp', array('CHROMEPHP_BIN_PATH' => $found));

                return "<li class='debug-new'><strong>Chrome</strong>: намерен е системен бинарник `{$found}`</li>";
            }
        }

        if (PHP_OS_FAMILY != 'Linux' || php_uname('m') != 'x86_64') {

            return "<li class='debug-error'>Chrome не е намерен и не може да се инсталира автоматично за тази платформа. Задайте ръчно CHROMEPHP_BIN_PATH</li>";
        }

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
