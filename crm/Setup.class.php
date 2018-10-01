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
 * Клас 'crm_Setup' -
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
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
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'crm_Companies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1, callcenter=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Визитник и управление на контактите';
    
    
    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array('title' => 'Ключови думи', 'url' => array('crm_Persons', 'repairKeywords', 'ret_url' => true), 'params' => array('title' => 'Ре-индексиране на визитките'))
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Gather_contragent_info',
            'description' => 'Събиране на информация за контрагентите',
            'controller' => 'crm_ext_ContragentInfo',
            'action' => 'GatherInfo',
            'period' => 720,
            'offset' => 70,
            'timeLimit' => 300
        ),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'crm_Groups',
        'crm_Persons',
        'crm_Companies',
        'crm_ext_IdCards',
        'crm_Personalization',
        'crm_ext_CourtReg',
        'crm_Profiles',
        'crm_Locations',
        'crm_Formatter',
        'crm_ext_ContragentInfo',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'crm';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.32, 'Указател', 'Визитник', 'crm_Companies', 'default', 'crm, user'),
    );
    
    
    /**
     * Скрипт за инсталиране
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg,image/jpeg,png', '3MB', 'user', 'every_one');
        
        // Кофа за снимки
        $html .= $Bucket->createBucket('location_Images', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        // Кофа за crm файлове
        $html .= $Bucket->createBucket('crmFiles', 'CRM Файлове', null, '300 MB', 'user', 'user');
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме на плъгина за превръщане на никовете в оцветени линкове
        $html .= $Plugins->forcePlugin('NickToLink', 'crm_ProfilesPlg', 'core_Manager', 'family');
        
        $html .= $Plugins->forcePlugin('Линкове в статусите след логване', 'crm_UsersLoginStatusPlg', 'core_Users', 'private');
        
        $html .= $Plugins->forcePlugin('Персонални настройки на системата', 'crm_PersonalConfigPlg', 'core_ObjectConfiguration', 'private');
        
        // Нагласяване на Cron
        $rec = new stdClass();
        $rec->systemId = 'PersonsToCalendarEvents';
        $rec->description = 'Обновяване на събитията за хората';
        $rec->controller = 'crm_Persons';
        $rec->action = 'UpdateCalendarEvents';
        $rec->period = 24 * 60 * 60;
        $rec->offset = 16;
        $rec->delay = 0;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
    
    
    /**
     * Деинсталиране
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
