<?php

/**
 * Подразбиращата се кодировка на съобщенията
 */
defIfNot('PML_CHARSET', 'utf-8');


/**
 * Ник-а на изпращача по подразбиране
 */
defIfNot('PML_DEF_NICK', 'support');


/**
 * Адреса във 'From' хедър-а на съобщението
 */
defIfNot('PML_FROM_EMAIL', PML_DEF_NICK . '@' . $_SERVER['SERVER_NAME']);


/**
 * Името във 'From' хедър-а на съобщението
 */
defIfNot('PML_FROM_NAME', $conf->EF_APP_TITLE . ' ' .
    mb_convert_case(PML_DEF_NICK, MB_CASE_TITLE, PML_CHARSET));


/**
 * Адреса на изпращача (Return-Path) на съобщението
 */
defIfNot('PML_SENDER', PML_FROM_EMAIL);


/**
 * Какъв да е метода за изпращане на писма?
 * ("mail", "sendmail", or "smtp")
 */
defIfNot('PML_MAILER', 'sendmail');


/**
 * Къде се намира Sendmail?
 */
defIfNot('SENDMAIL_PATH', '/usr/sbin/sendmail');


/**
 * Дефинираме пътя до кода на PHP_Mailer
 */
defIfNot('PML_VERSION', '5.2');


/**
 * Да изпраща ли по единично писмата от адресите в 'To:'
 */
defIfNot('PML_SINGLE_TO', 'FALSE');


/**
 * Хоста за SMTP
 */
defIfNot('PML_HOST', $_SERVER['SERVER_NAME']);


/**
 * Порт за SMTP
 */
defIfNot('PML_PORT', 25);


/**
 * Оторизация за SMTP
 */
defIfNot('PML_SMTPAUTH', 'FALSE');


/**
 * ПОтребител за SMTP
 */
defIfNot('PML_USERNAME', '');


/**
 * Парола за SMTP
 */
defIfNot('PML_PASSWORD', '');


/**
 * Парола за SMTP
 */
defIfNot('PML_SMTPSECURE', 0);


/**
 * class phpmailer_Setup
 *
 * Инсталиране/Деинсталиране на
 * доктор за адресни данни
 *
 *
 * @category  vendors
 * @package   phpmailer
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class phpmailer_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'phpmailer_Instance';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Aгрегиране на PHP Mailer Lite";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            'PML_CHARSET'   => array ('varchar', 'mandatory, caption=Писмо->Kодировка на съобщенията'),
            'PML_FROM_EMAIL'   => array ('email', 'mandatory, caption=Писмо->Адреса във `От` хедър-а на съобщението'),
            'PML_FROM_NAME'  => array ('varchar', 'mandatory, caption=Писмо->Името във `От` хедър-а на съобщението'),
    		'PML_SINGLE_TO' => array('enum(TRUE=да, FALSE=не)', 'mandatory, caption=Писмо->Ще се изпраща ли единично писмата от адресите в `До`,row=2'),
            'PML_MAILER' => array ('enum(mail=mail, sendmail=sendmail, smtp=smtp)', 'mandatory, caption=Писмо->Какъв да е метода за изпращане на писма?'), //"mail", "sendmail", or "smtp") 
            'SENDMAIL_PATH'  => array ('varchar', 'caption=Sendmail->Пътя до папката на Sendmail'),
            'PML_HOST'  => array ('varchar', 'caption=Smtp->Хост'),
       		'PML_PORT'  => array ('int', 'caption=Smtp->Порт'),
    		'PML_SMTPAUTH'  => array ('enum(TRUE=да, FALSE=не)', 'caption=Smtp->Оторизация'),
    		'PML_USERNAME'  => array ('varchar', 'caption=Smtp->Потребител'),
    		'PML_PASSWORD'  => array ('varchar', 'caption=Smtp->Парола'),
    		'PML_SMTPSECURE'  => array ('enum(tls=TLS, ssl=SSL, 0=няма)', 'caption=Smtp->Криптографски протокол'),
    		'PML_VERSION'  => array ('enum(5.2, 5.2.8)', 'caption=PML->Версия'),
        );

        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    /*var $managers = array(
            'phpmailer_Instance',
            
        );*/
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
         // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}