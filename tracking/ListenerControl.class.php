<?php




/**
 * Клас 'tracking_ListenerControl'
 *
 * @category  vendors
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tracking_ListenerControl extends core_Manager
{


    /**
     * Име
     */
    public $title = 'Демон контрол';

    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    /**
     * Кой има право да трие?
     */
    public $canDelete = 'no_one';
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,admin,tracking';
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'tracking_Wrapper';    

    /**
     * Полета за показване
     *
     * var string|array
     */
    public $listFields = 'pid';
        
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('pid', 'int()', 'caption=UID');
        $this->FLD('data', 'blob', 'caption=Параметри');
    }
    
    
    /**
     * Ако няма записи не вади таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public function act_ListenerControl()
    {

        $cmd = Request::get('cmd');
        
        if ($cmd == 'start') {
            self::Start();
        } elseif ($cmd == 'stop') {
            self::Stop();
        }
        
        redirect(array('tracking_listenerControl'));
    }

    
    /**
     * Ако няма записи не вади таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (self::isStarted()) {
            $data->toolbar->addBtn('Спиране', array('tracking_listenerControl', 'listenerControl', 'cmd'=>'stop'), array('class' => 'btn-close'));
        } else {
            $data->toolbar->addBtn('Стартиране', array('tracking_listenerControl', 'listenerControl', 'cmd'=>'start'), array('class' => 'btn-open'));
            unset($res); unset($data);
        }
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
            . " " . $conf->PROTOCOL . " " . getHostByName($conf->DOMAIN)
            . " " . $conf->PORT
            . " " . $conf->DOMAIN;

            $pid = exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd));
            $rec = new stdClass();
            $rec->pid = $pid;
            $rec->data = $cmd;
            $listenerControl = cls::get('tracking_listenerControl');
            
            $listenerControl->save($rec);  
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
        // Взимаме записа с PID-а от таблицата - ако няма запис - процеса е спрян
        $query = self::getQuery();
        $rec = $query->fetch(); // В data е командната линия

        // Парсираме резултата от ps -fp <PID> команда и взимаме командната линия на процеса
        exec("ps -fp " . $rec->pid, $output);
        // Ако командата се съдържа в резултата от ps значи процеса е нашия
        if (strpos($output[1], $rec->data) !== FALSE) {

            return (TRUE);
        } else {
            // Процеса не е нашия и чистим връзката с него
            $query->where("1=1");
            $query->delete();
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