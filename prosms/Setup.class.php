<?php

/**
 * Константи за изпращане на SMS-и през Pro-SMS
 */

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_URL', '');

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_USER', '');


/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_PASS', '');


/**
 * Дали поддържа UTF-8
 */
defIfNot('PROSMS_SUPPORT_UTF8', FALSE);


/**
 * Максималната дължина на стринга
 */
defIfNot('PROSMS_MAX_STRING_LEN', 160);


/**
 * Стринг с позволените имена за изпращач
 */
defIfNot('PROSMS_ALLOWED_USER_NAMES', '');


/**
 * class prosms_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез prosms
 *
 *
 * @category  vendors
 * @package   prosms
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class prosms_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез prosms";
    

    var $configDescription = array (
        'PROSMS_URL' => array('url', 'mandatory, caption=Данни за сметката за изпращане на SMS->URL'),
        'PROSMS_USER' => array('identifier', 'mandatory, caption=Данни за сметката за изпращане на SMS->Потребителско име'),
        'PROSMS_PASS' => array('password', 'mandatory, caption=Данни за сметката за изпращане на SMS->Парола'),
    
        'PROSMS_SUPPORT_UTF8' => array('enum(no=Не, yes=Да)', 'caption=UTF-8->Поддръжка'),
        'PROSMS_MAX_STRING_LEN' => array('int', 'caption=Максималната дължина на стринга->Брой символи'),
        'PROSMS_ALLOWED_USER_NAMES' => array('text(rows=1)', 'caption=Стринг с позволените имена за изпращач->Списък с имена'),
        );
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'prosms_SMS',
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