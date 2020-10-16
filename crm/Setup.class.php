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
 * Да се показва ли полето Класификация на икономическите дейности (НКИД)
 */
defIfNot('CRM_VISIBLE_NKID', 'none');


/**
 * Използване на регистър VIES за търсене на фирми
 */
defIfNot('CRM_REGISTRY_USE_VIES', 'yes');


/**
 * Използване на Търговския регистър за търсене на фирми
 */
defIfNot('CRM_REGISTRY_USE_BRRA', 'yes');

/**
 * Вид на визитника
 */
defIfNot('CRM_ALPHABET_FILTER', 'standart');

/**
 * Клас 'crm_Setup' -
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2020 Experta OOD
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
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CRM_VISIBLE_NKID' => array('enum(none=Не показвай, yes=Покажи)', 'caption=Класификация на икономическите дейности->НКИД'),
        'CRM_REGISTRY_USE_BRRA' => array('enum(yes=Включено,ne=Изключено)', 'caption=Извличане и попълване на данни->Търговски регистър'),
        'CRM_REGISTRY_USE_VIES' => array('enum(yes=Включено,ne=Изключено)', 'caption=Извличане и попълване на данни->VIES'),
        'CRM_ALPHABET_FILTER' => array('enum(none=Без,standart=Стандартен,twoRows=Двоен)', 'caption=Вид на азбучника'),
    );
    
    
    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array('title' => 'Ключови думи', 'url' => array('crm_Persons', 'repairKeywords', 'ret_url' => true), 'params' => array('title' => 'Ре-индексиране на визитките')),
        array('title' => 'Промяна на условия', 'url' => array('cond_ConditionsToCustomers', 'update', 'ret_url' => true), 'params' => array('title' => 'Промяна на търговските условия на контрагентите', 'ef_icon' => 'img/16/arrow_refresh.png'))
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
        'crm_ext_Cards',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'crm';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.32, 'Указател', 'Визитник', 'crm_Companies', 'default', 'powerUser'),
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
}
