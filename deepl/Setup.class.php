<?php


/**
 * Token
 */
defIfNot('DEEPL_TOKEN', '');


/**
 * Версия
 */
defIfNot('DEEPL_VERSION', 'freev2');



/**
 * Целеви език
 */
defIfNot('DEEPL_LANG', 'bg');


/**
 *
 *
 * @category  bgerp
 * @package   deepl
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class deepl_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с DEEPL';


    /**
     * Масив с възможните езици
     */
    protected static $lgArr = array('bg' => 'Български', 'en' => 'Английски', 'tr' => 'Турски', 'ro' => 'Румънски', 'ru' => 'Руски',
                                'de' => 'Немски', 'fr' => 'Френски', 'it' => 'Италиански', 'ja' => 'Японски', 'zh' => 'Китайски', 'es' => 'Испански',
                                'pl' => 'Полски', 'pt' => 'Португалски', 'cs' => 'Чешки', 'da' => 'Датски', 'el' => 'Гръцки', '' => '',
                                'et' => 'Естонски', 'fi' => 'Финландски', 'hu' => 'Унгарски', 'id' => 'Индонезийски', 'ko' => 'Корейски',
                                'lt' => 'Литовски', 'lv' => 'Латвийски', 'nb' => 'Норвежки', 'NL' => 'Холандски', 'sk' => 'Словашки',
                                'sl' => 'Словенски', 'sv' => 'Шведски', 'uk' => 'Украински');


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'DEEPL_TOKEN' => array('password(show)', 'caption=Ключ,class=w100,mandatory'),
        'DEEPL_VERSION' => array('enum(freev2=FREE v2, prov2=PRO v2)', 'caption=Версия на API'),
        'DEEPL_LANG' => array('enum()', 'caption=Целеви език при превеждане->Език, customizeBy=powerUser, optionsFunc=deepl_Setup::getCountries'),
    );

    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'deepl_Cache';


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('deepl'),
    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'deepl_Cache',
    );


    /**
     * Всички възможни езици, на които мож да се превежда
     * https://www.deepl.com/docs-api/translate-text/translate-text/
     * target_lang
     */
    public static function getCountries()
    {

        return self::$lgArr;
    }


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $Plugins = cls::get('core_Plugins');

        // Премахва превеждането през гугъл
        if ($Plugins->deinstallPlugin('google_plg_LgTranslate')) {
            $html .= "<li>Премахнати са всички инсталации на 'google_plg_LgTranslate'";
        }

        // Премахва превеждането през гугъл
        if ($Plugins->deinstallPlugin('email_plg_IncomingsTranslate')) {
            $html .= "<li>Премахнати са всички инсталации на 'email_plg_IncomingsTranslate'";
        }

        // Инсталиране на плъгин за автоматичен превод
        $html .= $Plugins->installPlugin('core_Lg Translate', 'deepl_plugins_LgTranslate', 'core_Lg', 'private');

        // Инсталиране на плъгин за превод на входящата поща
        $html .= $Plugins->installPlugin('Email Translate', 'deepl_plugins_IncomingsTranslate', 'email_Incomings', 'private');

        return $html;
    }
}
