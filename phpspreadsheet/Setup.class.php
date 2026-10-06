<?php


/**
 * Инсталиране на PhpSpreadsheet за експорт на електронни таблици
 *
 * @category  vendors
 * @package   phpspreadsheet
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
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
     * Библиотеката е незадължителна за съществуващите XLS експорти
     */
    public function install()
    {
        $html = parent::install();

        // Тази версия поддържа едновременно PHP 7.4 и PHP 8.2.
        $html .= core_Composer::install('phpoffice/phpspreadsheet', '1.30.5');

        return $html;
    }
}
