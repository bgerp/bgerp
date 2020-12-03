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
 * Коя е клиентската версия на библиотеката
 */
defIfNot('SPEEDY_CLIENT_LIBRARY_VERSION', '3.5.4');


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
 * @copyright 2006 - 2020 Experta OOD
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
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'speedy_Offices';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция със "speedy"';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array('speedy_Offices', 'speedy_BillOfLadings');
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'speedy';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        //array(1.99999, 'Система', 'SPEEDY', 'speedy_Offices', 'default', 'admin'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'speedy_interface_DeliveryToOffice';
    
    
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
        'SPEEDY_CLIENT_LIBRARY_VERSION' => array('enum(3.5.4)','caption=Клиентска библиотека->Версия'),
        'SPEEDY_OFFICE_LOCATOR_URL' => array('varchar', 'caption=URL->Локатор на офис'),
        'SPEEDY_TRACKING_URL' => array('varchar', 'caption=URL->Проследяване на пратка'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Генериране на товарителница от ЕН към Speedy', 'speedy_plg_BillOfLading', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Генериране на товарителница от Продажба към Speedy', 'speedy_plg_BillOfLading', 'sales_Sales', 'private');
        
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
}