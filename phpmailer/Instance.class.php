<?php


/**
 * Подразбиращата се кодировка на съобщенията
 */
defIfNot('PML_CHARSET', 'utf-8');


/**
 * Ника на изпращача по подразбиране
 */
defIfNot('PML_DEF_NICK', 'support');


/**
 * Адреса във 'From' хедъра на съобщението
 */
defIfNot('PML_FROM_EMAIL', PML_DEF_NICK . '@' . $_SERVER['SERVER_NAME']);


/**
 * Името във 'From' хедъра на съобщението
 */
defIfNot('PML_FROM_NAME', EF_APP_TITLE . ' ' .
mb_convert_case(PML_DEF_NICK, MB_CASE_TITLE, PML_CHARSET));


/**
 * Адреса на изпращача (Return-Path) на съобщението
 */
defIfNot('PML_SENDER', PML_FROM_EMAIL);


/**
 * Какъв да е метода за изпращане на писма?
 * ("mail", "sendmail", or "smtp")
 */
defIfNot('PML_MAILER','sendmail');


/**
 * Къде се намира Sendmail?
 */
defIfNot('SENDMAIL_PATH','/usr/sbin/sendmail');


/**
 * Дефинираме пътя до кода на PHP_Mailer
 */
defIfNot('PML_CLASS', '5.2/class.phpmailer.php');


/**
 * Да изпраща ли по единично писмата от адесите в 'To:'
 */
defIfNot('PML_SINGLE_TO', FALSE);

// Зареждаме кода на на PHP_Mailer
require_once(PML_CLASS);


/**
 * Клас от EF, който агрегира PHP Mailer Lite
 */
class phpmailer_Instance extends core_BaseClass
{
    
    
    /**
     *  Инициализиране на обекта
     */
    function init($params = array())
    {
        // Създаваме инстанция на PHPMailerLite
        $PML = new PHPMailer();
        
        // Задаваме стойностите от конфигурацията
        $PML->Mailer    = PML_MAILER;
        $PML->CharSet   = PML_CHARSET;
//      $PML->Encoding  = PML_ENCODING;
        $PML->From      = PML_FROM_EMAIL;
        $PML->FromName  = PML_FROM_NAME;
        $PML->Sendmail  = SENDMAIL_PATH;
        $PML->SingleTo  = PML_SINGLE_TO;
        $PML->Host      = PML_HOST;
        $PML->Port      = PML_PORT;
        $PML->SMTPAuth  = PML_SMTPAUTH;
        $PML->SMTPSecure= PML_SMTPSECURE;
        $PML->Username  = PML_USERNAME;
        $PML->Password  = PML_PASSWORD;

        // Добавяме динамичните параметри, които могат да 
        // "препокрият" зададените конфигурационни стойности
        if(count($params)) {
            foreach($params as $name => $value) {
                $PML->{$name} = $value;
            }
        }
        
        // Връщаме създадения обект
        return $PML;
    }
}