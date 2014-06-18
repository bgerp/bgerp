<?php


/**
 * Адрес на който ще се слуша за данни
 */
defIfNot('LOCAL_IP', '127.0.0.1');

/**
 * Порт
 */
defIfNot('PORT', '8500');

/**
 * Протокол
 */
defIfNot('PROTOCOL', 'udp');

/**
 * IP на хост от който се приемат данни // IP на демона, от където праща данните
 */
defIfNot('DATA_SENDER', '127.0.0.1');

/**
 * Домейн на системата
 */
defIfNot('DOMAIN', 'bgerp.local');

/**
 * Период на рестартиране на сървиса
 */
defIfNot('RESTART_PERIOD', '3600');

/**
 * pid на процеса за слушане
 */
defIfNot('PID', '');

/**
 * Клас 'tracking_Setup'
 *
 * @category  vendors
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tracking_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'tracking_Log';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = "Данни за точки от тракери";
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            'LOCAL_IP' => array ('ip', 'mandatory, caption=IP от което ще се четат данните'),
            'PORT' => array ('int', 'mandatory, caption=Порт'),
            'PROTOCOL' => array ('enum(udp=udp, tcp=tcp)', 'mandatory, caption=Протокол'),
            'DATA_SENDER' => array ('ip', 'mandatory, caption=Адрес на изпращач'),
            'DOMAIN' => array ('varchar(255)', 'mandatory, caption=Домейн'),
            'RESTART_PERIOD' => array ('int()', 'mandatory, caption=Период за рестарт')
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'tracking_Log',
//            'tracking_ListenerControl',
            'tracking_Vehicles'
    );

    /**
     * Роли за достъп до модула
     */
    public $roles = 'tracking';
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.4, 'Мониторинг', 'Tracking', 'tracking_Log', 'default', "tracking,ceo,admin"),
    );
    
    /**
     * Добавяне на крон
     */
    function install()
    {
        //Данни за работата на cron
        $conf = core_Packs::getConfig('tracking');
    
        // Наглася Cron да стартира приемача на данни
        $Cron = cls::get('core_Cron');
    
        $rec = new stdClass();
        $rec->systemId = "trackingWatchDog";
        $rec->description = "Грижа приемача на данни да е пуснат";
        $rec->controller = "tracking_Setup";
        $rec->action = "WatchDog";
        $rec->period = (int) $conf->RESTART_PERIOD / 60;
        $rec->offset = 0;
    
        if ($Cron->addOnce($rec)) {
            $html .= "<li><font color='green'>Задаване по крон WatchDog tracking</font></li>";
        } else {
            $html .= "<li>Отпреди Cron е бил нагласен за WatchDog tracking</li>";
        }
    
        $html .= parent::install();
    
        return $html;
    }

    /**
     * Проверява дали е пуснат сървиса, и ако не е го пуска
     *
     * @param string
     * @return array
     */
    public function cron_WatchDog()
    {
        if (!self::isStarted()) {
            self::Start(); echo ("Startiran");
        }
        echo ("OK");
        /* 
         * @todo: На определено време е добре сървиса да се рестартира.
         */
    }
    /**
     * Пуска листенер-а
     *
     * @return bool
     */
    private function Start()
    {
        $conf = core_Packs::getConfig('tracking');
        if (!self::isStarted()) {
    
            $conf->DATA = "php " . realpath(dirname(__FILE__)) . "/sockListener.php"
                    . " " . $conf->PROTOCOL . " " . getHostByName($conf->DOMAIN)
                    . " " . $conf->PORT
                    . " " . $conf->DOMAIN;
    
            $conf->PID = exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd));

            core_Packs::setConfig('tracking', $conf);
        }
    
        return ($conf->PID);
    }
    
    
    /**
     * Спира листенер-а
     *
     * @return bool
     */
    private static function Stop()
    {
        $conf = core_Packs::getConfig('tracking');
        
        if (!empty($conf->PID)) {
            posix_kill($conf->PID, 9);
        }
    
        return (TRUE);
    }
    
    
    /**
     * Стартиран ли е листенер-а
     *
     * @return bool
     */
    private static function isStarted()
    {
        $conf = core_Packs::getConfig('tracking');
        // Взимаме PID-а от конфигурацията - ако няма стойност - процеса е спрян
    
        // Парсираме резултата от ps -fp <PID> команда и взимаме командната линия на процеса
        exec("ps -fp " . $conf->PID, $output);
        // Ако командата се съдържа в резултата от ps значи процеса е нашия
        if (strpos($output[1], $conf->DATA) !== FALSE) {
    
            return (TRUE);
        } else {
            // Процеса не е нашия и чистим връзката с него
            unset($conf->PID);
            unset($conf->DATA);
            
            core_Packs::setConfig('tracking', $conf);
        }
    
        return (FALSE);
    }
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}