<?php


/** Досегашната вградена версия остава default за всички инсталации. */
defIfNot('TCPDF_VERSION', '6.2.13');


/**
 * Избор на вградена TCPDF библиотека.
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
        'TCPDF_VERSION' => array('enum(6.2.13,6.11.4)', 'mandatory, caption=TCPDF->Версия'),
    );


    /** Зарежда една версия на глобалните TCPDF класове за текущата заявка. */
    public static function loadLibrary()
    {
        $version = self::get('VERSION');
        expect(in_array($version, array('6.2.13', '6.11.4'), true), 'Неподдържана TCPDF версия');
        if (class_exists('TCPDF', false)) {
            expect(TCPDF_STATIC::getTCPDFVersion() === $version, 'TCPDF версията се сменя в нова заявка');

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
