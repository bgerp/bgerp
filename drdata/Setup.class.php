<?php

/**
 * Хост по подразбиране за изпращач
 */
defIfNot("SENDER_HOST", "localhost");


/**
 * Стандартен и-мейл на изпращача
 */
defIfNot("SENDER_EMAIL", 'team@example.com');


/**
 * Стандартен код на държавата
 */
defIfNot("COUNTRY_PHONE_CODE", '359');


/**
 * Избягвани под-стрингове при парсиране на вход. писма
 */
defIfNot("DRDATA_AVOID_IN_EXT_ADDRESS", '');


/**
 * Хипервръзки за телефоните -> Desktop устройства
 */
defIfNot('TEL_LINK_WIDE', 'yes');


/**
 * Хипервръзки за телефоните -> Mobile устройства
 */
defIfNot('TEL_LINK_NARROW', 'yes');


/**
 * Кеширане на информацията за VAT номерата
 */
defIfNot('DRDATA_VAT_TTL', 2 * core_DateTime::SECONDS_IN_MONTH);


/**
 * До колко време след последното използване да се проверяват
 */
defIfNot('DRDATA_LAST_USED_EXP', core_DateTime::SECONDS_IN_MONTH);


/**
 * class drdata_Setup
 *
 * Инсталиране/Деинсталиране на
 * доктор за адресни данни
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class drdata_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.15';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'drdata_Countries';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Готови данни и типове от различни области";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            'SENDER_HOST'   => array ('identifier', 'mandatory, caption=Настойки на проверителя на имейл адреси->Хост'),
            'SENDER_EMAIL'  => array ('email', 'mandatory, caption=Настойки на проверителя на имейл адреси->`От` имейл'),
            'COUNTRY_PHONE_CODE'  => array ('int', 'mandatory, caption=Код на държава по подразбиране->Код'),
            'DRDATA_AVOID_IN_EXT_ADDRESS' => array('text', 'caption=Избягвани думи при извличане на адресни данни от текст->Стрингове'),
    		'TEL_LINK_WIDE'   => array ('enum(none=Няма,
                                                   yes=Да,)', 'caption=Хипервръзки за телефоните->Desktop'),
    		 
    		'TEL_LINK_NARROW'   => array ('enum(none=Няма,
                                                   yes=Да,)', 'caption=Хипервръзки за телефоните->Mobile'),
            'DRDATA_VAT_TTL'  => array ('time(suggestions=1 месец|2 месеца|3 месеца|4 месеца|6 месеца|12 месеца)', 'mandatory, caption=Кеширане на информацията за VAT номерата->Време'),
            'DRDATA_LAST_USED_EXP'  => array ('time(suggestions=1 месец|2 месеца|3 месеца|4 месеца|6 месеца|12 месеца)', 'mandatory, caption=Ограничение за проверка след последно използване->Време'),
 
        );

        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'drdata_Countries',
            'drdata_IpToCountry',
            'drdata_DialCodes',
            'drdata_PhoneCache',
            'drdata_Vats',
            'drdata_Domains',
    		'drdata_Languages',
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