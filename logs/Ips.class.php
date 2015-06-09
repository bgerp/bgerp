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
class logs_Ips extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Ip-та";
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin';
    
    
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
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, logs_Wrapper';
    
    
    /**
     * 
     */
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
    public static function getIpId($ip = NULL)
    {
        if (!$ip) {
            $ip = core_Users::getRealIpAddr();
        }
        
        if (!self::$ipsArr) {
            self::$ipsArr = (array) Mode::get('ipsArr');
        }
        
        // Ако в сесията нямада id-то на IP-то, определяме го, записваме в модела и в сесията
        if (!isset(self::$ipsArr[$ip])) {
            if (!($id = self::fetchField(array("#ip = '[#1#]'", $ip), 'id'))) {
                
                $rec = new stdClass();
                $rec->ip = $ip;
                $rec->country2 = drdata_IpToCountry::get($ip); // TODO така ли трябва да е?
                
                $id = self::save($rec);
            }
            
            if ($id) {
                self::$ipsArr[$ip] = $id;
            }
            
            Mode::setPermanent('ipsArr', self::$ipsArr);
        }
        
        return self::$ipsArr[$ip];
    }
}
