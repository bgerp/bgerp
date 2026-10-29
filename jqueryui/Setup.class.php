<?php


/**
 * Версията на JQueryUI, която се използва
 */
defIfNot('JQUERYUI_VERSION', '1.12.1');


/**
 * URL за зареждане на JqueryUI от CDN
 */
defIfNot('JQUERYUI_CDN_URL', '');


/**
 * Хеш за сигурност при зареждане от CDN
 */
defIfNot('JQUERYUI_CDN_INTEGRITY', '');


/**
 * Клас 'jqueryui_Ui' - Работа с JQuery UI библиотеката
 *
 *
 * @category  bgerp
 * @package   jqueryui
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class jqueryui_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'JQueryUI';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'JQUERYUI_VERSION' => array('enum(1.8.2, 1.11.3, 1.12.1, 1.13.3, 1.14.2)', 'caption=Версия на JQueryUI->Версия'),
        'JQUERYUI_CDN_URL' => array('url', 'caption=Зареждане от CDN->Url'),
        'JQUERYUI_CDN_INTEGRITY' => array('varchar(64)', 'caption=Зареждане от CDN->Integrity'),
    );
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        return implode(', ', self::getJsFiles());
    }


    /**
     * Запазва еднакъв ред на библиотеката и съвместимото API със и без compactor.
     */
    public static function getJsFiles()
    {
        $version = self::get('VERSION');
        $files = array();
        if ($version === '1.14.2') {
            $files[] = 'jqueryui/js/compat.js';
        }
        $files[] = 'jqueryui/' . $version . '/jquery-ui.js';

        return $files;
    }


    /**
     * Показва стандартна setup грешка за неподдържана комбинация с jQuery.
     */
    public function checkConfig()
    {
        if (self::get('VERSION') !== '1.14.2') {
            return;
        }
        $version = jquery_Setup::get('VERSION');
        if ((version_compare($version, '1.12.4', '>=') && version_compare($version, '2.0.0', '<')) ||
            (version_compare($version, '2.2.4', '>=') && version_compare($version, '3.0.0', '<')) ||
            (version_compare($version, '3.6.4', '>=') && version_compare($version, '5.0.0', '<'))) {
            return;
        }

        return 'jQuery UI 1.14.2 изисква съвместима jQuery версия. Изберете jQuery 3.7.1 или 4.0.0 от настройките на jquery, или запазете предишната jQuery UI версия.';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('jqueryui');
        
        return 'jqueryui/' . $conf->JQUERYUI_VERSION . '/jquery-ui.min.css';
    }
}
