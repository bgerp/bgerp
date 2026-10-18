<?php


/**
 * Потребител в системата на Transpress
 */
defIfNot('TRANSPRESS_USERNAME', '');


/**
 * Парола в системата на Transpress
 */
defIfNot('TRANSPRESS_PASSWORD', '');


/**
 * Среда, към която се изпращат заявките
 */
defIfNot('TRANSPRESS_ENVIRONMENT', 'production');


/**
 * Адрес на продукционната среда
 */
defIfNot('TRANSPRESS_PRODUCTION_URL', 'https://app.transpress.bg/transpress');


/**
 * Адрес на тестовата среда
 */
defIfNot('TRANSPRESS_TEST_URL', 'https://app.transpress.bg/steps-beta');


/**
 * Интеграция с куриерското API на Transpress
 *
 * @category  bgerp
 * @package   transpress
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class transpress_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Описание на модула
     */
    public $info = 'Интеграция с куриерско API на "Transpress"';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'TRANSPRESS_USERNAME' => array('varchar', 'caption=|Акаунт в|* Transpress->Потребител,customizeBy=transpress|ceo'),
        'TRANSPRESS_PASSWORD' => array('password', 'caption=|Акаунт в|* Transpress->Парола,customizeBy=transpress|ceo'),
        'TRANSPRESS_ENVIRONMENT' => array('enum(production=Продукция,test=Тест)', 'caption=Връзка->Среда'),
        'TRANSPRESS_PRODUCTION_URL' => array('url', 'caption=Връзка->Продукция'),
        'TRANSPRESS_TEST_URL' => array('url', 'caption=Връзка->Тест'),
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = 'transpress';


    /**
     * Мениджъри за инсталиране
     */
    public $managers = array('transpress_WayBills');


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'transpress_interface_CourierImpl';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        $html .= fileman_Buckets::createBucket('transpress', 'Товарителници към Transpress', 'pdf', '104857600', 'user', 'user');

        return $html;
    }


    /**
     * Проверява дали са настроени данните за връзка
     *
     * @return string|null
     */
    public function checkConfig()
    {
        if(empty(self::get('USERNAME')) || empty(self::get('PASSWORD'))){

            return 'Не са настроени потребителят и паролата за връзка с Transpress';
        }

        return null;
    }
}
