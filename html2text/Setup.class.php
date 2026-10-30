<?php


/**
 * Брояч на линковете
 */
defIfNot('HTML2TEXT_BUILD_LINK_COUNTER', true);


defIfNot('HTML2TEXT_VERSION', '1.0.0');


/**
 * Клас 'html2text_Setup'
 *
 * Настройки за конвертиране на HTML към текст
 *
 *
 * @category  bgerp
 * @package   html2text
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class html2text_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет, които превръща HTML текста в обикновен форматиран текст';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;


    /** 1.0.0 обозначава досегашната bgERP реализация, а не upstream release. */
    public $configDescription = array(
        'HTML2TEXT_VERSION' => array('enum(1.0.0=1.0.0 - bgERP,4.3.2=4.3.2)', 'mandatory,caption=HTML към текст->Версия'),
    );


    /** Проверява необходимото разширение преди използване на новата библиотека. */
    public function checkConfig()
    {
        if (self::get('VERSION') === '4.3.2' && !extension_loaded('mbstring')) {
            return 'Html2Text 4.3.2 изисква PHP разширението mbstring.';
        }
    }
}
