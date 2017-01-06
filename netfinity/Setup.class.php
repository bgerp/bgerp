<?php


/**
 * Урл за изпращане на SMS-и
 */
defIfNot('NETFINITY_URL', 'http://api.smspro.bg/bsms/send?apikey=[#apikey#]&msisdn=[#number#]&text=[#message#]&id=[#msgid#]');


/**
 * Урл за изпращане на SMS-и
 */
defIfNot('NETFINITY_APIKEY', '');


/**
 * Дали поддържа UTF-8
 */
defIfNot('NETFINITY_SUPPORT_UTF8', FALSE);


/**
 * Максималната дължина на стринга
 */
defIfNot('NETFINITY_MAX_STRING_LEN', 160);


/**
 * Стринг с позволените имена за изпращач
 */
defIfNot('NETFINITY_ALLOWED_USER_NAMES', '');


/**
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез нетфинити
 *
 *
 * @category  vendors
 * @package   netfinity
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class netfinity_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез Нетфинити";
    
    
    /**
     * 
     */
    var $configDescription = array (
        'NETFINITY_URL' => array('url', 'mandatory, caption=Адрес за изпращане на SMS-и през Нетфинити->URL адрес'),
        'NETFINITY_APIKEY' => array('varchar', 'mandatory, caption=Ключ за достъп до услугата->Стринг'),
        'NETFINITY_SUPPORT_UTF8' => array('enum(no=Не, yes=Да)', 'caption=Дали поддържа UTF-8->Избор'),
        'NETFINITY_MAX_STRING_LEN' => array('int',  'caption=Максималната дължина на стринга->Бр. символи'),
        'NETFINITY_ALLOWED_USER_NAMES' => array('text(rows=1)', 'caption=Стринг с позволените имена за изпращач->Списък с имена'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'netfinity_SMS',
    );
}
