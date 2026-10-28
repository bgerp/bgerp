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


    /** Инсталира само Tika от собствен lock файл, без промяна на общите Composer зависимости. */
    public function install()
    {
        $html = parent::install();
        if (version_compare(self::get('APACHE_TIKA_VERSION', true), '3.0.0', '<')) {
            return $html;
        }
        if ($error = $this->checkConfig()) {
            return $html . "<li class='debug-error'>" . $error . '</li>';
        }

        $result = core_ComposerProject::install(__DIR__ . '/composer', self::getComposerDir());
        if ($result === false) {
            return $html . "<li class='debug-error'>Tika Composer: " . core_Type::escape(core_Composer::$error ?? '') . '</li>';
        }

        // Двете версии остават налични за превключване без преинсталиране.
        self::getJarPath('3.3.3');
        self::getJarPath('4.1.0');

        return $html . "<li class='debug-new'>Composer: Apache Tika 3.3.3 и 4.1.0 са инсталирани</li>";
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


    /** Отделя Java дистрибуциите от Composer проекта с общите PHP зависимости. */
    public static function getComposerDir()
    {
        cls::load('core_Composer');

        return rtrim(EF_VENDOR_PATH, '/') . '/apachetika';
    }


    /** Връща launcher-а за избраната или изрично посочена версия, без да изтегля при извличане. */
    public static function getJarPath($version = null)
    {
        $version = $version ?? self::get('APACHE_TIKA_VERSION', true);
        if (in_array($version, array('3.3.3', '4.1.0'), true)) {
            $major = $version === '3.3.3' ? '3' : '4';
            $path = self::getComposerDir() . "/vendor/bgerp/tika-app-{$major}/tika-app-{$version}.jar";
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
