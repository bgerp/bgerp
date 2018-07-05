<?php


/**
 * Дали поддържа UTF-8
 */
defIfNot('SMSSYNC_SUPPORT_UTF8', 'yes');


/**
 * Максималната дължина на стринга
 */
defIfNot('SMSSYNC_MAX_STRING_LEN', 160);


/**
 * Стринг с позволените имена за изпращач
 */
defIfNot('SMSSYNC_ALLOWED_USER_NAMES', '');



/**
 * Защитени ключове
 */
defIfNot('SMSSYNC_SECRET_KEY', md5(EF_SALT . 'SMSSync'));


/**
 * Разрешени IP адреси, от които да се изпраща/получава SMS
 */
defIfNot('SMSSYNC_ALLOWED_IP_ADDRESS', '');



/**
 * Максимален брой SMS-и, които ще се вземат при извикване
 */
defIfNot('SMSSYNC_SMS_LIMIT', '10');


/**
 * Инсталиране на smssync
 *
 * @category  vendors
 * @package   smssync
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class smssync_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Изпращане на SMS чрез SMSSync';
    
    
    
    public $configDescription = array(
        'SMSSYNC_SECRET_KEY' => array('varchar', 'caption=Защитен ключ за изпращане/получаване->Ключ'),
        'SMSSYNC_ALLOWED_IP_ADDRESS' => array('varchar', 'caption=Разрешени IP адреси от които да се пращат/получават съобщенията->IP адрес'),
        'SMSSYNC_ALLOWED_USER_NAMES' => array('varchar', 'caption=Стринг с позволените имена за изпращач->Списък'),
        'SMSSYNC_MAX_STRING_LEN' => array('int', 'caption=Максималната дължина на стринга->Бр. символи'),
        'SMSSYNC_SUPPORT_UTF8' => array('enum(no=Не, yes=Да)', 'caption=Дали поддържа UTF-8->Да/Не'),
        'SMSSYNC_SMS_LIMIT' => array('int(min=1, max=100)', 'caption=Лимит за изпращане на SMS-и при едно извикване->Брой'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'smssync_SMS',
        );
}
