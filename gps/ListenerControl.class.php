<?php




/**
 * Клас 'gps_ListenerControl'
 *
 * @category  vendors
 * @package   gps
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gps_ListenerControl extends core_Manager
{


    /**
     * Име
     */
    public $title = 'Демон контрол';

    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin,gps';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin,gps';
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,admin,gps';
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'gps_Wrapper';    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('pid', 'int()', 'caption=UID');
        $this->FLD('data', 'blob', 'caption=Параметри');
    }
    
    /**
     * Изпълнява се след начално установяване(настройка) на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $conf = core_Packs::getConfig('gps');
    
        // Наглася Cron да стартира приемача на данни
        $Cron = cls::get('core_Cron');
    
        $rec = new stdClass();
        $rec->systemId = "gpsWatchDog";
        $rec->description = "Грижа приемача на данни да е пуснат";
        $rec->controller = "gps_ListenerControl";
        $rec->action = "WatchDog";
        $rec->period = (int) $conf->RESTART_PERIOD / 60;
        $rec->offset = 0;
    
        $Cron->addOnce($rec);
    
    }
    
    
    /**
     * Входна точка за спиране и пускане на листенер-а
     *
     */
    public function act_ListenerControl()
    {
        $cmd = Request::get('cmd');
        if ($cmd == 'start') {
            self::Start();
        } else {
            self::Stop();
        }
        $res  = "<li>Статус: " . (self::isStarted()?'<font color=green>Стартиран</font>':'<font color=red>Спрян</font>'). "</li>";
        $res .= "<li><a href='?cmd=start'>Стартиране</a></li>";
        $res .= "<li><a href='?cmd=stop'>Спиране</a></li>";
        
        return ($res);
    }
    

    /**
     * Пуска листенер-а
     *
     * @return bool
     */
    private function Start()
    {
        $conf = core_Packs::getConfig('gps');
        if (!self::isStarted()) {
            // Изчистваме PID-a от таблицата
            $query = self::getQuery();
            $query->where("1=1");
            $query->delete();
            
            $cmd = "php " . realpath(dirname(__FILE__)) . "/sockListener.php"
            . " " . $conf->PROTOCOL . " " . getHostByName($conf->DOMAIN)
            . " " . $conf->PORT
            . " " . $conf->DOMAIN;

            $pid = exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd));
            $rec->pid = $pid;
            $rec->data = $cmd;
            
            $this->save($rec);  
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
        $query = self::getQuery();
        
        if ($rec = $query->fetch()) {
            posix_kill($rec->pid, 9);
        }
        $query->where("1=1");
        $query->delete();
        
        return (TRUE);
    }

    
    /**
     * Стартиран ли е листенер-а
     *
     * @return bool
     */
    private static function isStarted()
    {
        $conf = core_Packs::getConfig('gps');
        // Взимаме записа с PID-а от таблицата - ако няма запис - процеса е спрян
        $query = self::getQuery();
        $rec = $query->fetch(); // В data е командната линия

        // Парсираме резултата от ps -fp <PID> команда и взимаме командната линия на процеса
        exec("ps -fp " . $rec->pid, $output);
        // Ако командата се съдържа в резултата от ps значи процеса е нашия
        if (strpos($output[1], $rec->data) !== FALSE) {

            return (TRUE);
        }
        
        return (FALSE);
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
        // На определено време е добре сървиса да се рестартира.
    }
    
    
    
}