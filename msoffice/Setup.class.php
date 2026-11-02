<?php


defIfNot('MSOFFICE_SIMPLEXLSX_VERSION', '0.8.4');


/**
 * Настройки на XLSX четеца
 *
 * @category  vendors
 * @package   msoffice
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class msoffice_Setup extends core_ProtoSetup
{
    /** Версия на пакета. */
    public $version = '0.1';


    /** Описание на модула. */
    public $info = 'Четене на Excel XLSX файлове със SimpleXLSX';


    /** Пакет без инсталация. */
    public $noInstall = true;


    /** Запазва избора за конкретната инсталация. */
    public $configDescription = array(
        'MSOFFICE_SIMPLEXLSX_VERSION' => array('enum(0.8.4,1.1.19)', 'mandatory,caption=SimpleXLSX->Версия'),
    );


    /** Проверява разширенията на новата версия по стандартния начин. */
    public function checkConfig()
    {
        if (self::get('SIMPLEXLSX_VERSION') === '1.1.19') {
            foreach (array('libxml', 'SimpleXML', 'zlib', 'mbstring') as $extension) {
                if (!extension_loaded($extension)) {
                    return 'SimpleXLSX 1.1.19 изисква PHP разширението ' . $extension . '.';
                }
            }
        }
    }
}
