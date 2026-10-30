<?php


/** Досегашната вградена версия остава default за всички инсталации. */
defIfNot('TCPDF_VERSION', '6.2.13');


/**
 * Избор на TCPDF библиотека; новият клон се доставя чрез Composer.
 *
 * @category vendors
 * @package tcpdf
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class tcpdf_Setup extends core_ProtoSetup
{
    public $version = '0.1';

    public $info = 'Генериране на PDF и CAD чертежи с TCPDF';

    public $configDescription = array(
        'TCPDF_VERSION' => array('enum(6.2.13,6.11.4,7.0.20=7.0.20 (PHP 8.2+ / Composer))', 'mandatory, caption=TCPDF->Версия'),
    );


    /** Проверява средата преди зареждане или инсталиране на PHP 8 библиотеката. */
    public static function getPhpVersionError($version)
    {
        if ($version === '7.0.20' && PHP_VERSION_ID < 80200) {
            return 'TCPDF 7.0.20 изисква PHP 8.2 или по-нова версия. За PHP 7.4 изберете 6.11.4.';
        }
    }


    /** Връща стандартна грешка към полето, преди core_Packs да запише избора. */
    public function inputConfigDescriptionForm(&$form)
    {
        if ($form->isSubmitted() && ($error = self::getPhpVersionError($form->rec->TCPDF_VERSION ?? null))) {
            $form->setError('TCPDF_VERSION', $error);
        }
    }


    /** Показва неподходящ PHP или липсваща нова библиотека в core_Packs. */
    public function checkConfig()
    {
        if ($error = self::getPhpVersionError(self::get('VERSION'))) {
            return $error;
        }
        if (self::get('VERSION') === '7.0.20'
            && !core_Composer::isInstalled('tecnickcom/tcpdf', self::get('VERSION'), true)) {
            return 'Липсва избраната версия на TCPDF. Инициализирайте пакета tcpdf.';
        }
        if (self::get('VERSION') === '7.0.20') return self::getFontError();
    }


    /** Проверява основните шрифтове, които Composer сам не генерира. */
    public static function getFontError()
    {
        if (!defined('K_PATH_FONTS') && !defined('EF_VENDOR_PATH')) {
            return 'Не е зададена Composer директорията EF_VENDOR_PATH за TCPDF 7.';
        }
        $path = defined('K_PATH_FONTS') ? K_PATH_FONTS
            : dirname(core_Composer::getAutoloadPath()) . '/tecnickcom/tc-lib-pdf-font/target/fonts';
        foreach (array('core/helvetica.json', 'dejavu/dejavusans.json', 'dejavu/dejavusans.z', 'dejavu/dejavusans.ctg.z') as $font) {
            if (!is_readable(rtrim($path, '/') . '/' . $font)) {
                return 'Липсват шрифтовете за TCPDF 7. Изпълнете с PHP 8.2+ официалния vendor/tecnickcom/tc-lib-pdf-font/util/build_fonts.php в Composer директорията (README-BGERP.md).';
            }
        }
    }


    /** Инсталира новата библиотека само когато е избрана на съвместим PHP. */
    public function install()
    {
        if ($error = self::getPhpVersionError(self::get('VERSION'))) {
            return "<li class='red'>" . $error . '</li>';
        }
        $html = parent::install();
        if (self::get('VERSION') === '7.0.20') {
            $html .= core_Composer::install('tecnickcom/tcpdf', self::get('VERSION'), true);
            if ($error = self::getFontError()) $html .= "<li class='red'>" . $error . '</li>';
        }

        return $html;
    }


    /** Разпознава зареденото глобално API, включително когато идва от друг потребител. */
    public static function getLoadedVersion()
    {
        if (!class_exists('TCPDF', false)) return null;
        if (class_exists('TCPDF_STATIC', false)) return TCPDF_STATIC::getTCPDFVersion();
        if (class_exists('Composer\\InstalledVersions') && \Composer\InstalledVersions::isInstalled('tecnickcom/tcpdf')) {
            return ltrim((string) \Composer\InstalledVersions::getPrettyVersion('tecnickcom/tcpdf'), 'v');
        }
    }


    /** Зарежда една версия на глобалните TCPDF класове за текущата заявка. */
    public static function loadLibrary()
    {
        $version = self::get('VERSION');
        expect(in_array($version, array('6.2.13', '6.11.4', '7.0.20'), true), 'Неподдържана TCPDF версия');
        $error = self::getPhpVersionError($version);
        expect(!$error, $error);
        if (class_exists('TCPDF', false)) {
            expect(self::getLoadedVersion() === $version, 'TCPDF версията се сменя в нова заявка');

            return;
        }

        if ($version === '7.0.20') {
            expect(core_Composer::isInUse() && class_exists('TCPDF') && self::getLoadedVersion() === $version,
                'Липсва избраната версия на TCPDF. Инициализирайте пакета tcpdf.');
            $error = self::getFontError();
            expect(!$error, $error);

            return;
        }
        if ($version === '6.11.4') {
            // Статусът на поддръжка е описан в README-BGERP, без известие във всеки PDF експорт.
            defIfNot('TCPDF_SILENCE_DEPRECATION', true);
        }
        $path = __DIR__ . ($version === '6.2.13' ? '' : '/' . $version);
        require_once $path . '/tcpdf.php';
    }
}
