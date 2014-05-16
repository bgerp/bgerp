<?php


/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('BGERP_OWN_COMPANY_NAME', 'Моята Фирма ООД');


/**
 * Държавата на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('BGERP_OWN_COMPANY_COUNTRY', 'Bulgaria');


/**
 * ID на нашата фирма
 */
defIfNot('BGERP_OWN_COMPANY_ID', 1);


/**
 * Хипервръзки за телефоните -> Desktop устройства
 */
defIfNot('CRM_TEL_LINK_WIDE', 'yes');


/**
 * Хипервръзки за телефоните -> Mobile устройства
 */
defIfNot('CRM_TEL_LINK_NARROW', 'yes');


/**
 * Клас 'crm_Setup' -
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class crm_Setup extends core_ProtoSetup
{
    
    
    /**
     * ID на нашата фирма
     */
    const BGERP_OWN_COMPANY_ID = BGERP_OWN_COMPANY_ID;
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'crm_Companies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Визитник и управление на контактите";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'crm_Groups',
            'crm_Companies',
            'crm_Persons',
            'crm_ext_IdCards',
            'crm_Personalization',
            'crm_ext_CourtReg',
            'crm_Profiles',
            'crm_Locations',
            'crm_Formatter',
    
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'crm';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.32, 'Указател', 'Визитник', 'crm_Companies', 'default', "crm, user"),
        );
 
            
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
             'CRM_TEL_LINK_WIDE'   => array ('enum(none=Няма,
                                                   yes=Да,)', 'caption=Хипервръзки за телефоните->Desktop'),
    		 
    		 'CRM_TEL_LINK_NARROW'   => array ('enum(none=Няма,
                                                   yes=Да,)', 'caption=Хипервръзки за телефоните->Mobile'),
    
             );
             
             
    /**
     * Скрипт за инсталиране
     */
    function install()
    {
        
        $html = parent::install();
                
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg,image/jpeg,png', '3MB', 'user', 'every_one');
        
         // Кофа за снимки
        $html .= $Bucket->createBucket('location_Images', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        // Кофа за crm файлове
        $html .= $Bucket->createBucket('crmFiles', 'CRM Файлове', NULL, '300 MB', 'user', 'user');
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме на плъгина за превръщане на никовете в оцветени линкове
        $html .= $Plugins->forcePlugin('NickToLink', 'crm_ProfilesPlg', 'core_Manager', 'family');

        // Нагласяване на Cron        
        $rec = new stdClass();
        $rec->systemId    = 'PersonsToCalendarEvents';
        $rec->description = "Обновяване на събитията за хората";
        $rec->controller  = 'crm_Persons';
        $rec->action      = 'UpdateCalendarEvents';
        $rec->period      = 24*60*60;
        $rec->offset      = 16;
        $rec->delay       = 0;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $html .= "<li style='color:green;'>Cron: Обновяване на събитията за хората в календара</li>";
        } else {
            $html .= "<li>Cron от преди е бил нагласен: Обновяване на събитията за хората в календара</li>";
        }

        return $html;
    }
    
    
    /**
     * Деинсталиране
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}