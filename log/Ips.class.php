<?php 


/**
 *
 *
 * @category  bgerp
 * @package   logs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Ips extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'logs_Ips';
    
    
    /**
     * Заглавие
     */
    public $title = 'IP-та';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'debug';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, log_Wrapper';
    
    
    
    public static $ipsArr = array();
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('ip', 'ip', 'caption=IP');
        $this->FLD('country2', 'varchar(2)', 'caption=Код на държавата');
         
        $this->setDbUnique('ip');
    }
    
    
    /**
     * Връща id за съответния запис на IP
     *
     * @param IP $ip
     *
     * @return integer
     */
    public static function getIpId($ip = null)
    {
        $haveSession = false;
        $Session = cls::get('core_Session');
        if ($Session->isStarted()) {
            $haveSession = true;
        }
        
        if (!$ip) {
            $ip = core_Users::getRealIpAddr();
        }
        
        if (!self::$ipsArr) {
            if ($haveSession) {
                self::$ipsArr = (array) Mode::get('ipsArr');
            } else {
                self::$ipsArr = array();
            }
        }
        
        // Ако в сесията нямада id-то на IP-то, определяме го, записваме в модела и в сесията
        if (!isset(self::$ipsArr[$ip])) {
            if (!($id = self::fetchField(array("#ip = '[#1#]'", $ip), 'id'))) {
                $rec = new stdClass();
                $rec->ip = $ip;
                $rec->country2 = drdata_IpToCountry::get($ip); // TODO така ли трябва да е?
                
                $id = self::save($rec, null, 'IGNORE');
            }
            
            if ($id) {
                self::$ipsArr[$ip] = $id;
            }
            
            if ($haveSession) {
                Mode::setPermanent('ipsArr', self::$ipsArr);
            }
        }
        
        return self::$ipsArr[$ip];
    }
}
