<?php


/**
 * Версията се сменя чрез настройките на пакета, без да променя избора на XLS конвертор.
 */
defIfNot('PHPSPREADSHEET_VERSION', '1.30.5');


/**
 * Инсталиране на PhpSpreadsheet за експорт на електронни таблици
 *
 * @category  vendors
 * @package   phpspreadsheet
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class phpspreadsheet_Setup extends core_ProtoSetup
{
    public $version = '0.1';


    public $depends = 'export=0.1';


    public $info = 'Експорт на електронни таблици с PhpSpreadsheet';


    public $defClasses = 'phpspreadsheet_Adapter';


    /**
     * Проверени версии за PHP 7.4 и PHP 8.2.
     */
    public $configDescription = array(
        'PHPSPREADSHEET_VERSION' => array('enum(1.30.5,1.30.7)', 'mandatory, caption=PhpSpreadsheet->Версия'),
    );


    /**
     * Библиотеката е незадължителна за съществуващите XLS експорти
     */
    public function install()
    {
        $html = parent::install();

        // Точното съвпадение позволява и връщане към по-старата версия.
        $html .= core_Composer::install('phpoffice/phpspreadsheet', self::get('VERSION'), true);

        return $html;
    }
}
