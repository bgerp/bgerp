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
     * Описание на модела
     */
    function description()
    {
        $this->FLD('pid', 'varchar(12)', 'caption=UID');
        $this->FLD('data', 'blob', 'caption=Параметри');
    }
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,admin,gps';
    
    
    /**
     * Входна точка за спиране и пускане на листенер-а
     *
     */
    public function act_ListenerControl()
    {
        $res  = "<li>Статус: " . (self::Started()?'<font color=green>Стартиран</font>':'<font color=red>Спрян</font>'). "</li>";
        $res .= "<li><a href=''>Стартиране</a></li>";
        $res .= "<li><a href=''>Спиране</a></li>";
        self::Start();
        
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
//        if (!$self->Started()) {
            $command = "php " . realpath(dirname(__FILE__)) . "/sockListener.php"
            . " " . $conf->PROTOCOL . " " . getHostByName($conf->DOMAIN)
            . " " . $conf->PORT
            . " " . $conf->DOMAIN; bp($command);
            
            exec($command, $output, $returnVar); 
//        }
        
        return ($res);
    }


    /**
     * Спира листенер-а
     * 
     * @return bool 
     */
    private function Stop()
    {
        
        return ($res);
    }

    
    /**
     * Стартиран ли е листенер-а
     *
     * @return bool
     */
    private function Started()
    {
        $conf = core_Packs::getConfig('gps');
        
        return ($res);
    }
}