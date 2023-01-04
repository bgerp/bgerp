<?php


/**
 * Урл към намиране на адреса на офиса
 */
defIfNot('SPEEDY_OFFICE_LOCATOR_URL', "https://www.speedy.bg/speedy_office_locator_widget/googleMaps.php?lang=bg&id=[#NUM#]&src=sws");


/**
 * Урл за онлайн проследяване на пратката
 */
defIfNot('SPEEDY_TRACKING_URL', "https://www.speedy.bg/bg/track-shipment?shipmentNumber=[#NUM#]");


/**
 * Базово урл на библиотеката
 */
defIfNot('SPEEDY_API_BASE_URL', 'https://api.speedy.bg/v1/');


/**
 * Потребителско име в системата на Speedy
 */
defIfNot('SPEEDY_DEFAULT_ACCOUNT_USERNAME', '');


/**
 * Парола в системата на спиди
 */
defIfNot('SPEEDY_DEFAULT_ACCOUNT_PASSWORD', '');


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     * @todo да се махне като направим да работи с API-то
     */
    public $depends = 'tcost=0.1';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с API на "Speedy"';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array('speedy_Offices',
                             'speedy_BillOfLadings',
                             'migrate::deletePlugins2251');
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'speedy';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'speedy_interface_DeliveryToOffice,speedy_interface_ApiImpl';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Update Speedy Offices',
            'description' => 'Обновяване на офисите на Speedy',
            'controller' => 'speedy_Offices',
            'action' => 'UpdateOffices',
            'period' => 1440,
            'offset' => 120,
            'timeLimit' => 200
        ),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'SPEEDY_DEFAULT_ACCOUNT_USERNAME' => array('varchar','caption=Акаунт в сайта на Speedy->Потребител,customizeBy=speedy|ceo'),
        'SPEEDY_DEFAULT_ACCOUNT_PASSWORD' => array('password', 'caption=Акаунт в сайта на Speedy->Парола,customizeBy=speedy|ceo'),
        'SPEEDY_OFFICE_LOCATOR_URL' => array('varchar', 'caption=URL->Локатор на офис'),
        'SPEEDY_TRACKING_URL' => array('varchar', 'caption=URL->Проследяване на пратка'),
        'SPEEDY_API_BASE_URL' => array('url', 'caption=URL->Библиотека на спиди'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('billOfLadings', 'Товарителници към Speedy',  'pdf,jpg,jpeg,png', '200MB', 'user', 'user');
        
        return $html;
    }
    

    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return null|string
     */
    public function checkConfig()
    {
        $accountUserName = self::get('DEFAULT_ACCOUNT_USERNAME');
        $accountPassword = self::get('DEFAULT_ACCOUNT_PASSWORD');
        
        if(empty($accountUserName) || empty($accountPassword)){
            
            return "Не са настроени паролата и акаунта, за връзка с онлайн услугите на Speedy";
        }
    }


    /**
     * Изтриване на стар плъгин
     */
    public function deletePlugins2251()
    {
        core_Plugins::delete("#plugin = 'speedy_plg_BillOfLading'");
    }
}