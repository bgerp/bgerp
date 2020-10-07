<?php


/**
 * Адрес на който ще се слуша за данни
 */
defIfNot('PAMI_IP', '');


/**
 * Порт
 */
defIfNot('PAMI_PORT', '');


/**
 * Протокол
 */
defIfNot('PAMI_SCHEME', 'tcp');


/**
 * Потребител
 */
defIfNot('PAMI_USER', '');


/**
 * Парола
 */
defIfNot('PAMI_PASS', '');


/**
 * pid на процеса за слушане
 */
defIfNot('PAMI_PID', '');


/**
 * Команден ред за изпълнение на командата
 */
defIfNot('PAMI_CMD', '');


/**
 * Колко дни да се не се трият логовете
 */
defIfNot('PAMI_LOG_KEEP_DAYS', '7');


/**
 * Колко дни да се не се трият логовете
 */
defIfNot('PAMI_SAVE_TO_LOG', 'yes');


/**
 * URL за логване
 */
defIfNot('PAMI_LOG_URL', 'http://127.0.0.1');


/**
 * 
 */
defIfNot('PAMI_LOG_KEY', '');


/**
 * Клас 'pami_Setup'
 *
 * @category  bgerp
 * @package   pami
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pami_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    /**
     * Необходими пакети
     */
    public $depends = 'callcenter=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'pami_Logs';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'PAMI връзка с Астериск';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PAMI_IP' => array('ip', 'mandatory, caption=Данни за връзка с PAMI->IP'),
        'PAMI_PORT' => array('int(Min=0, max=65535)', 'mandatory, caption=Данни за връзка с PAMI->Порт'),
        'PAMI_SCHEME' => array('enum(tcp,tls)', 'mandatory, caption=Данни за връзка с PAMI->Схема'),
        'PAMI_USER' => array('varchar(64)', 'mandatory, mandatory, caption=Данни за връзка с PAMI->Потребител'),
        'PAMI_PASS' => array('password', 'mandatory,caption=Данни за връзка с PAMI->Парола'),
        'PAMI_LOG_KEY' => array('password', 'caption=Ключ за връзка->Парола'),
        'PAMI_LOG_KEEP_DAYS' => array('int(min=0)', 'caption=Живот на логовете->Дни'),
        'PAMI_LOG_URL' => array('varchar', 'caption=URL за логване->URL'),
        'PAMI_SAVE_TO_LOG' => array('enum(yes=Да,no=Не)', 'caption=Дали да се записва в лога->Избор'),
        'PAMI_PID' => array('varchar(readonly)', 'caption=PID на процеса за слушане->PID,readonly'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'pami_Logs',
    );
    
    
    /**
     * Роли, които да се инсталират
     */
    public $roles = 'pami';
    
    
    /**
     * Добавяне на крон
     */
    public function install()
    {
        $html .= parent::install();
        
        $html .= core_Composer::install('marcelog/pami', '2.*');
        
        // Наглася Cron да стартира приемача на данни
        $rec = new stdClass();
        $rec->systemId = 'pamiWatchDog';
        $rec->description = 'Подсигурява PAMI listener да е пуснат';
        $rec->controller = 'pami_Setup';
        $rec->action = 'WatchDog';
        $rec->period = 1;
        $rec->offset = 0;
        $html .= core_Cron::addOnce($rec);
        
        // Наглася Cron да трие стари данни
        $rec = new stdClass();
        $rec->systemId = 'pamiDeleteOldLogRecords';
        $rec->description = 'Изтрива стари лог записи';
        $rec->controller = 'pami_Logs';
        $rec->action = 'deleteOldRecords';
        $rec->period = 60 * 24;
        $rec->offset = 0;
        $html .= core_Cron::addOnce($rec);
        
        if (core_Composer::isInUse() && core_Composer::isInstalled('marcelog/pami')) {
            $pid = $this->get('PID');
            $stopped = $this->stop();
            if (isset($stopped)) {
                $html .= "<li class='green'>Спрян слушач за PAMI - pid={$pid}</li>";
                log_System::add('pami_Setup', "Спрян слушач за PAMI - от инсталация", null, 'debug');
            }
            
            $pid = $this->start();
            if ($pid) {
                $html .= "<li class='green'>Стартиран слушач за PAMI - pid={$pid}</li>";
                log_System::add('pami_Setup', "Стартиран слушач за PAMI - от инсталация", null, 'debug');
            } elseif ($pid === null) {
                $html .= "<li class='red'>Грешка при пускане на PAMI слушач - не са попълнени данни</li>";
            } else {
                $html .= "<li>Процеса за слушане с PAMI е стартиран от преди това.</li>";
            }
        } else {
            $html .= "<li class='red'>Не е инталиран композера</li>";
        }
        
        return $html;
    }
    
    
    /**
     * Проверява дали е пуснат сървиса, и ако не е го пуска
     *
     * @param string
     *
     * @return array
     */
    public function cron_WatchDog()
    {
        // В ненатоварено време на случаен принцип, форсираме рестартиране на процеса
        $h = date('G');
        $m = date('i');
        $force = false;
        if (($h >= 2) && ($h <= 7)) {
            if ($m == rand(0,60)) {
                $force = true;
            }
        }
        
        $pid = $this->start($force);
        if (isset($pid)) {
            $type = $force ? 'notice' : 'warning';
            log_System::add('pami_Setup', "Принудително пускане на PAMI с PID={$pid} по крон", null, $type);
        }
    }
    
    
    /**
     * Пуска листенер-а
     *
     * @return null|integer
     */
    private function start($force = false)
    {
        $ip = $this->get('IP');
        $port = $this->get('PORT');
        $scheme = $this->get('SCHEME');
        $user = $this->get('USER');
        $pass = $this->get('PASS');
        
        if (!$ip || !$port || !$scheme || !$user || !$pass) {
            
            log_System::add('pami_Setup', "Не са зададени параметри", null, 'notice');
            
            return null;
        }
        
        $pid = null;
        if ($force) {
            if ($this->isStarted()) {
                $this->stop();
                log_System::add('pami_Setup', "Форсирано спиране", null, 'debug');
            }
        }
        
        if (!$this->isStarted()) {
            $url = $this->get('LOG_URL');
            $url = rtrim($url, '/');
            $url .= '/pami_Logs/Log/?';
            
            $key = $this->get('LOG_KEY');
            
            $cmd = 'php ' . realpath(dirname(__FILE__)) . '/listener.php' . ' ' . $scheme . ' ' . $ip . ' ' . $port . ' ' . $user . ' ' . $pass . ' ' . core_Composer::getAutoloadPath() . ' ' . $url . ' ' . $key;
            
            $pid = @exec(sprintf('%s > /dev/null 2>&1 & echo $!', $cmd));
            
            core_Packs::setConfig('pami', array('PAMI_PID' => $pid, 'PAMI_CMD' => $cmd));
            
            if ($force) {
                log_System::add('pami_Setup', "Форсирано пускане на процеса", null, 'debug');
            } else {
                log_System::add('pami_Setup', "Стартиране на процеса", null, 'debug');
            }
        }
        
        return $pid;
    }
    
    
    /**
     * Спира листенер-а
     *
     * @return bool
     */
    private function stop()
    {
        $pid = $this->get('PID');
        
        $res = null;
        
        if (!empty($pid)) {
            $res = posix_kill($pid, 9);
            
            core_Packs::setConfig('pami', array('PAMI_PID' => ''));
            
            // За премахване от кеша
            $packName = $this->getPackName();
            unset(static::$conf[$packName]);
        }
        
        return ($res);
    }
    
    
    /**
     * Стартиран ли е листенер-а
     *
     * @return bool
     */
    private function isStarted()
    {
        $pid = $this->get('PID');
        
        // Взимаме PID-а от конфигурацията - ако няма стойност значи не е бил пускан или е спрян
        if (empty($pid)) {
            
            return false;
        }
        
        $cmd = trim($this->get('CMD'));
        
        // Парсираме резултата от ps -fp <PID> команда и взимаме командната линия на процеса
        @exec('ps -fp ' . $pid, $output);
        
        // Ако командата се съдържа в резултата от ps значи процеса е нашия
        if (strpos($output[1], $cmd) !== false) {
            
            return true;
        }
        
        log_System::add(get_called_class(), "Открит чужд процес с PID={$pid} и CMD={$cmd}", null, 'warning');
        
        // Процеса не е нашия и чистим връзката с него
        core_Packs::setConfig('pami', array('PAMI_PID' => '', 'PAMI_CMD' => ''));
        
        return false;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Спираме процеса
        if (true === $this->stop()) {
            $res .= "<li class='debug-new'>Успешно спрян процес.</li>";
            $res .= parent::deinstall();
        } else {
            $res .= "<li class='debug-error'>Неуспешно спрян процес.</li>";
        }
        
        return $res;
    }
}
