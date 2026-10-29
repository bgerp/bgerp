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
     * Старият клон остава наличен за PHP 7.4.
     */
    public $configDescription = array(
        'PHPSPREADSHEET_VERSION' => array('enum(1.30.5,1.30.7,5.10.0=5.10.0 (PHP 8.2+))', 'mandatory, caption=PhpSpreadsheet->Версия'),
    );


    /** Проверява средата преди зареждане или инсталиране на PHP 8 библиотеката. */
    public static function getPhpVersionError($version)
    {
        if ($version === '5.10.0' && PHP_VERSION_ID < 80200) {
            return 'PhpSpreadsheet 5.10.0 изисква PHP 8.2 или по-нова версия. За PHP 7.4 изберете 1.30.7.';
        }
    }


    /** Връща стандартна грешка към полето, преди core_Packs да запише избора. */
    public function inputConfigDescriptionForm(&$form)
    {
        if ($form->isSubmitted() && ($error = self::getPhpVersionError($form->rec->PHPSPREADSHEET_VERSION ?? null))) {
            $form->setError('PHPSPREADSHEET_VERSION', $error);
        }
    }


    /** Показва неподходящ PHP или липсваща нова библиотека в core_Packs. */
    public function checkConfig()
    {
        if ($error = self::getPhpVersionError(self::get('VERSION'))) {
            return $error;
        }
        if (self::get('VERSION') === '5.10.0'
            && !core_Composer::isInstalled('phpoffice/phpspreadsheet', self::get('VERSION'), true)) {
            return 'Липсва избраната версия на PhpSpreadsheet. Инициализирайте пакета phpspreadsheet.';
        }
    }


    /**
     * Библиотеката е незадължителна за съществуващите XLS експорти
     */
    public function install()
    {
        if ($error = self::getPhpVersionError(self::get('VERSION'))) {
            return "<li class='red'>" . $error . '</li>';
        }
        $html = parent::install();

        // Точното съвпадение позволява и връщане към по-старата версия.
        $html .= core_Composer::install('phpoffice/phpspreadsheet', self::get('VERSION'), true);

        return $html;
    }
}
