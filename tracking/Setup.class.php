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
defIfNot('LOG_URL', 'http://bgerp.local/tracking_Log/Log/?');

/**
 * Период на рестартиране на сървиса
 */
defIfNot('RESTART_PERIOD', '3600');

/**
 * pid на процеса за слушане
 */
defIfNot('PID', '');

/**
 * Команден ред за изпълнение на командата
 */
defIfNot('CMD', '');

/**
 * Команден ред за изпълнение на командата
 */
defIfNot('DAYS_TO_KEEP', '60');

/**
 * Клас 'tracking_Setup'
 *
 * @category  bgerp
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
            'LOG_URL' => array ('varchar(255)', 'mandatory, caption=Url за логване'),
            'RESTART_PERIOD' => array ('int()', 'mandatory, caption=Период за рестарт'),
            'PID' => array ('varchar(readonly)', 'caption=PID на процеса за слушане,input=readonly,readonly'),
            'DAYS_TO_KEEP' => array ('int()', 'mandatory, caption=Живот за логовете'),
           // 'CMD' => array ('varchar(255)', 'input=hidden, caption=Команда на процеса'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'tracking_Vehicles',
            'tracking_Log'
    );

    /**
     * Роли за достъп до модула
     */
    public $roles = 'tracking';
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.4, 'Мониторинг', 'Проследяване', 'tracking_Vehicles', 'default', "tracking,ceo,admin"),
    );
    
    
    /**
     * Добавяне на крон
     */
    function install()
    {
        //Данни за работата на cron
        $conf = core_Packs::getConfig('tracking');
    
        // Наглася Cron да стартира приемача на данни
        $rec = new stdClass();
        $rec->systemId = "trackingWatchDog";
        $rec->description = "Грижа приемача на данни да е пуснат";
        $rec->controller = "tracking_Setup";
        $rec->action = "WatchDog";
        $rec->period = (int) $conf->RESTART_PERIOD / 60;
        $rec->offset = 0;
        $html .= core_Cron::addOnce($rec);
        
        // Наглася Cron да трие стари данни
        $rec = new stdClass();
        $rec->systemId = "trackingDeleteOldRecords";
        $rec->description = "Изтрива стари записи";
        $rec->controller = "tracking_Log";
        $rec->action = "DeleteOldRecords";
        $rec->period = (int) 60*24*8; // на 8 дена пуска задачата
        $rec->offset = 0;
        $html .= core_Cron::addOnce($rec);
        
        if ($pid = self::Start()) {
            $html .= "<li class='green'>Стартиран слушач за тракерите - pid={$pid}</li>";
        } else {
            $html .= "<li>Процеса за тракерите е стартиран от преди това.</li>";
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
            self::Start();
        }
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
    
            $cmd = "php " . realpath(dirname(__FILE__)) . "/sockListener.php"
                    . " " . $conf->PROTOCOL . " " . $conf->LOCAL_IP
                    . " " . $conf->PORT
                    . " " . $conf->LOG_URL;
    
            $pid = @exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd));

            core_Packs::setConfig('tracking', array('PID'=>$pid, 'CMD'=>$cmd));
        }
    
        return ($pid);
    }
    
    
    /**
     * Спира листенер-а
     *
     * @return bool
     */
    private static function Stop()
    {
        $pid = core_Packs::getConfigKey('tracking', 'PID');

        if (!empty($pid)) {
            $res = posix_kill($pid, 9);
        }
        
        return ($res);
    }
    
    
    /**
     * Стартиран ли е листенер-а
     *
     * @return bool
     */
    private static function isStarted()
    {
        
        $pid = core_Packs::getConfigKey('tracking', 'PID');
        $cmd = core_Packs::getConfigKey('tracking', 'CMD');

        // Взимаме PID-а от конфигурацията - ако няма стойност - процеса е спрян
        if (empty($pid)) return FALSE;
        
        // Парсираме резултата от ps -fp <PID> команда и взимаме командната линия на процеса
        @exec("ps -fp " . $pid, $output);
        // Ако командата се съдържа в резултата от ps значи процеса е нашия
        if (strpos($output[1], $cmd) !== FALSE) {
    
            return (TRUE);
        } else {
            // Процеса не е нашия и чистим връзката с него
            core_Packs::setConfig('tracking', array('PID' => '', 'CMD' => ''));
        }

        return (FALSE);
    }
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Спираме процеса
        if (TRUE === self::Stop()) {
            $res = "<li class='debug-new'>Успешно спрян процес.</li>";
        } else {
            $res = "<li class='debug-error'>Неуспешно спрян процес.</li>";
        }
        
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}