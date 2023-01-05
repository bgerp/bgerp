<?php


/**
 * Ключ за достъп
 */
defIfNot('CVC_TOKEN', '');


/**
 * Урл
 */
defIfNot('CVC_URL', 'https://lox.e-cvc.bg/');


/**
 * Дефолтен изпращач
 */
defIfNot('CVC_SENDER_ID', '');


/**
 * Урл за онлайн проследяване на пратката
 */
defIfNot('CVC_TRACKING_URL', "https://my.e-cvc.bg/track?wb=[#NUM#]");


/**
 *
 *
 * @category  bgerp
 * @package   cvc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cvc_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с CVC API';

    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CVC_TOKEN' => array('password(show)', 'caption=Ключ,class=w100'),
        'CVC_URL' => array('url', 'caption=УРЛ'),
        'CVC_TRACKING_URL' => array('varchar', 'caption=Проследяване'),
        'CVC_SENDER_ID' => array('varchar', 'caption=Изпращач'),
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('cvc'),
    );


    /**
     * Мениджъри за инсталиране
     */
    public $managers = array('cvc_Offices',
                             'cvc_Hubs',
                             'cvc_WayBills',
        );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'cvc_interface_CourierImpl,cvc_interface_DeliveryToOffice';


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Update CVC Hubs & Offices',
            'description' => 'Обновяване на хъбовете и офисите на CVC',
            'controller' => 'cvc_Setup',
            'action' => 'UpdateHubsAndOffices',
            'period' => 1440,
            'offset' => 140,
            'timeLimit' => 200
        ),
    );


    /**
     * Екшън обновяващ хъбовете и офисите на cvc по разписание
     */
    public function cron_UpdateHubsAndOffices()
    {
        $token = cvc_Setup::get('TOKEN');
        if(empty($token)){
            log_System::add($this, "Проблем при свързване към API на CVC (липсва токен)", null, 'warning');
            return;
        }

        foreach (array('cvc_Offices' => 'getOffices', 'cvc_Hubs' => 'getHubs') as $class => $apiFnc){
            $Class = cls::get($class);

            try{
                $arrayToSync = cvc_Adapter::{$apiFnc}();
            } catch(core_exception_Expect $e){
                log_System::add($this, "Проблем при свързване към API на CVC", null, 'warning');
                continue;
            }

            $Class->sync($arrayToSync);
        }
    }


    /**
     * Менижиране на формата формата за настройките
     *
     * @param core_Form $configForm
     * @return void
     */
    public function manageConfigDescriptionForm(&$configForm)
    {
        // Задаване на опциите за избор на изпращача
        $senderOptions = cvc_Adapter::getSenderOptions();
        if(countR($senderOptions)){
            $configForm->setOptions('CVC_SENDER_ID', $senderOptions);
            $configForm->setField('CVC_SENDER_ID', 'mandatory');
        } else {
            $configForm->setReadOnly('CVC_SENDER_ID');
            if(empty(cvc_Setup::get('TOKEN'))){
                $configForm->info = "<div class='red'>" . tr("Задайте токен, запишете и след това отворете настройките отново за да изберете изпращач|*!") . "</div>";
            } else {
                $configForm->info = "<div class='red'>" . tr("Има проблем при връзката с CVC API и извличане на изпращачите|*!") . "</div>";
            }
        }
    }

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $html .= fileman_Buckets::createBucket('cvc', 'Файлове за cvc', '', '104857600', 'user', 'user');

        return $html;
    }
}
