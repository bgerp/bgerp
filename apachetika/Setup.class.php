<?php


/**
 * Версията на програмата
 */
defIfNot('APACHE_TIKA_VERSION', '1.24.1');


/**
 * Клас 'apachetika_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   apachetika
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class apachetika_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Разпознаване и извличане на метаданни и текст от различни типове';
    
    
    /**
     * Новите Java дистрибуции се осигуряват при инициализиране.
     */
    public $noInstall = false;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'APACHE_TIKA_VERSION' => array('enum(1.7, 1.24.1, 2.6.0, 2.9.2, 3.3.3, 4.1.0)', 'caption=Версия на програмата'),
    
    );


    /** Инсталира само избраната Tika в общия Composer проект. */
    public function install()
    {
        $html = parent::install();
        $version = self::get('APACHE_TIKA_VERSION', true);
        if (version_compare($version, '3.0.0', '<')) {
            if (is_file(core_Composer::getAutoloadPath()) && core_Composer::isInstalled('bgerp/tika-app')) {
                $html .= core_Composer::remove('bgerp/tika-app');
            }

            return $html;
        }
        if ($error = $this->checkConfig()) {
            return $html . "<li class='debug-error'>" . $error . '</li>';
        }

        core_App::setTimeLimit(600);
        $repository = json_decode(file_get_contents(__DIR__ . '/composer-packages.json'), true);

        return $html . core_Composer::install('bgerp/tika-app', $version, true, $repository);
    }


    /**
     * Показва стандартна setup грешка при липсваща или неподходяща Java за новите версии.
     *
     * @return string|null
     */
    public function checkConfig()
    {
        $version = self::get('APACHE_TIKA_VERSION', true);
        $minimumJava = array('3.3.3' => 11, '4.1.0' => 17);
        if (!isset($minimumJava[$version])) {
            return;
        }

        $required = $minimumJava[$version];
        exec('java -version 2>&1', $output, $code);
        if ($code !== 0) {
            return "Apache Tika {$version} изисква Java {$required} или по-нова. Програмата java липсва или не може да се изпълни от уеб потребителя.";
        }
        if (!preg_match('/(?:openjdk|java) (?:version )?"(?:1\\.)?(\\d+)/i', implode("\n", $output), $matches)) {
            return "Не може да се определи версията на Java. Apache Tika {$version} изисква Java {$required} или по-нова.";
        }
        if ((int) $matches[1] < $required) {
            return "Apache Tika {$version} изисква Java {$required} или по-нова; открита е Java {$matches[1]}. Обновете Java или изберете съвместима версия на Tika.";
        }
    }


    /** Връща общия Composer проект за Java и PHP зависимостите. */
    public static function getComposerDir()
    {
        cls::load('core_Composer');

        return rtrim(EF_VENDOR_PATH, '/');
    }


    /** Връща launcher-а за избраната или изрично посочена версия, без да изтегля при извличане. */
    public static function getJarPath($version = null)
    {
        $version = $version ?? self::get('APACHE_TIKA_VERSION', true);
        if (in_array($version, array('3.3.3', '4.1.0'), true)) {
            $path = self::getComposerDir() . "/vendor/bgerp/tika-app/tika-app-{$version}.jar";
        } else {
            $path = getFullPath("apachetika/{$version}/tika-app.jar");
        }
        expect($path && is_file($path), 'Липсва избраната Apache Tika библиотека. Инициализирайте пакета apachetika', $version);
        if ($version === '4.1.0') {
            expect(is_dir(dirname($path) . '/lib'), 'Липсват зависимостите lib/ на Apache Tika', $version);
        }

        return $path;
    }
}
