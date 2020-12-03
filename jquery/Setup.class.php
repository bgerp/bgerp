<?php


/**
 * Версията на JQuery, която се използва
 */
defIfNot('JQUERY_VERSION', '1.11.2');


/**
 * URL за зареждане на Jquery от CDN
 */
defIfNot('JQUERY_CDN_URL', '');


/**
 * Хеш за сигурност при зареждане от CDN
 */
defIfNot('JQUERY_CDN_INTEGRITY', '');


/**
 * Клас 'jquery_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   jquery
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class jquery_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'JQuery';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'JQUERY_VERSION' => array('enum(1.7.1, 1.8.3, 1.11.2, 2.1.3, 3.4.1)', 'caption=Версия на JQuery->Версия'),
        'JQUERY_CDN_URL' => array('url', 'caption=Зареждане от CDN->Url'),
        'JQUERY_CDN_INTEGRITY' => array('varchar(64)', 'caption=Зареждане от CDN->Integrity'),
    );
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
