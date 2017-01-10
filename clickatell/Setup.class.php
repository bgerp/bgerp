<?php


/**
 * URL за изпращане на SMS-и през clickatell
 */
defIfNot('CLICKATELL_URL', 'https://api.clickatell.com/http/sendmsg?callback=2&api_id=[#APIID#]&user=[#USERNAME#]&password=[#PASSWORD#]&to=[#PHONE#]&text=[#MESSAGE#]&from=[#FROM#]');


/**
 * URL за проверка на връзката с clickatell
 */
defIfNot('CLICKATELL_CHECK_URL', 'https://api.clickatell.com/http/auth?api_id=[#APIID#]&user=[#USERNAME#]&password=[#PASSWORD#]');


/**
 * API_ID
 */
defIfNot('CLICKATELL_APIID', '');


/**
 * Потребителско име
 */
defIfNot('CLICKATELL_USERNAME', '');


/**
 * Парола
 */
defIfNot('CLICKATELL_PASSWORD', '');


/**
 * Дали поддържа UTF-8
 */
defIfNot('CLIKATELL_SUPPORT_UTF8', FALSE);


/**
 * Максималната дължина на стринга
 */
defIfNot('CLIKATELL_MAX_STRING_LEN', 160);


/**
 * Стринг с позволените имена за изпращач
 */
defIfNot('CLIKATELL_ALLOWED_USER_NAMES', '');


/**
 * Инсталиране на clickatell
 *
 * @category  vendors
 * @package   clickatell
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class clickatell_Setup extends core_ProtoSetup
{ 
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез Clickatell";
    
    
    /**
     * 
     */
    var $configDescription = array (
        'CLICKATELL_URL' => array('url', 'caption=Адрес за изпращане на SMS-и->URL адрес'),
        'CLICKATELL_CHECK_URL' => array('url', 'caption=Адрес за проверка на връзката с clickatell->URL адрес'),
        'CLICKATELL_APIID' => array('varchar', 'mandatory, caption=Идентификатор на приложението->API_ID'),
        'CLICKATELL_USERNAME' => array('varchar', 'mandatory,caption=Потребителско име->Ник'),
        'CLICKATELL_PASSWORD' => array('varchar', 'mandatory, caption=Парола->Парола'),
    
        'CLIKATELL_SUPPORT_UTF8' => array('enum(no=Не, yes=Да)', 'caption=Дали поддържа UTF-8->Да/Не'),
        'CLIKATELL_MAX_STRING_LEN' => array('int', 'caption=Максималната дължина на стринга->Бр. символи'),
        'CLIKATELL_ALLOWED_USER_NAMES' => array('varchar', 'caption=Стринг с позволените имена за изпращач->Списък'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'clickatell_SMS',
        );
   
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}