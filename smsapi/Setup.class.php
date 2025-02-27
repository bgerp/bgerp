<?php


/**
 * URL за изпращане на SMS-и
 */
defIfNot('SMSAPI_URL', 'https://api.smsapi.bg/sms.do');

/**
 * URL за изпращане на SMS-и
 */
defIfNot('SMSAPI_NOTIFY_URL', ''); // https://domain.bg/smsapi_SMS/Delivery/

/**
 * Дали поддържа UTF-8
 */
defIfNot('SMSAPI_SUPPORT_UTF8', false);


/**
 * Максималната дължина на стринга
 */
defIfNot('SMSAPI_MAX_STRING_LEN', 160);


/**
 * Стринг с позволените имена за изпращач
 */
defIfNot('SMSAPI_ALLOWED_USER_NAMES', '');


/**
 * class smsapi_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез smsapi
 *
 *
 * @category  vendors
 * @package   smsapi
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class smsapi_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'SMS изпращане чрез SMSAPI';
    
    
    public $configDescription = array(
        'SMSAPI_URL' => array('url', 'mandatory, caption=Адрес за изпращане на SMS-и през smsapi->URL адрес'),
        'SMSAPI_NOTIFY_URL' => array('url', 'mandatory, caption=Адрес за Delivery report->URL адрес'),
        'SMSAPI_TOKEN' => array('varchar', 'mandatory, caption=Токън за изпращане на SMS-и през smsapi->Токън'),
        'SMSAPI_SUPPORT_UTF8' => array('enum(no=Не, yes=Да)', 'caption=Дали поддържа UTF-8->Да/Не'),
        'SMSAPI_MAX_STRING_LEN' => array('int',  'caption=Максималната дължина на стринга->Бр. символи'),
        'SMSAPI_ALLOWED_USER_NAMES' => array('text(rows=1)', 'caption=Стринг с позволените имена за изпращач->Списък с имена'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'smsapi_SMS',
    );
}
