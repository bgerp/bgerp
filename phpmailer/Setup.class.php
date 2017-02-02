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
defIfNot('PML_VERSION', '5.2.8');


/**
 * Да изпраща ли по единично писмата от адресите в 'To:'
 */
defIfNot('PML_SINGLE_TO', 0);


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
     * Описание на модула
     */
    var $info = "Адаптер за PHP Mailer Lite";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            'PML_CHARSET'   => array ('varchar', 'mandatory, caption=Имейл съобщение->Kодировка'),
            'PML_FROM_EMAIL'   => array ('email', 'mandatory, caption=Имейл съобщение->Адрес `From`'),
            'PML_FROM_NAME'  => array ('varchar', 'mandatory, caption=Имейл съобщение->Име `From`'),
    		'PML_SINGLE_TO' => array('enum(1=Индивидуални имейли, 0=Общ имейл)', 'caption=При повече от един адрес в `To`->Изпращане като,row=2'),
            'PML_MAILER' => array ('enum(mail=mail, sendmail=sendmail, smtp=smtp)', 'caption=Изпращане на писма->Метод'),  
            'SENDMAIL_PATH'  => array ('varchar', 'caption=Sendmail->Пътя до папката'),
            'PML_HOST'  => array ('varchar', 'caption=Smtp->Хост'),
       		'PML_PORT'  => array ('int', 'caption=Smtp->Порт'),
    		'PML_SMTPAUTH'  => array ('enum(TRUE=да, FALSE=не)', 'caption=Smtp->Оторизация'),
    		'PML_USERNAME'  => array ('varchar', 'caption=Smtp->Потребител'),
    		'PML_PASSWORD'  => array ('varchar', 'caption=Smtp->Парола'),
    		'PML_SMTPSECURE'  => array ('enum(tls=TLS, ssl=SSL, 0=няма)', 'caption=Smtp->Криптиране'),
    		'PML_VERSION'  => array ('enum(5.2.8, 5.2.22)', 'caption=PML->Версия'),
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
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}