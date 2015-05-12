<?php


/**
 * Детайл на бюлетените
 * 
 * @category  bgerp
 * @package   crm
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class marketing_BulletinSubscribers extends core_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'bulletinId';

    
    /**
     * 
     */
    var $title = 'Абонати за бюлетин';

    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_RowTools, plg_Created';
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'ceo, marketing';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'ceo, marketing';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, marketing';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'ceo, marketing';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, marketing';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'ceo, marketing';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * 
     */
    public $listFields = 'id, email, name, company, ip, brid, createdOn, createdBy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('bulletinId', 'key(mvc=marketing_Bulletins)', 'input=hidden,silent');
        $this->FLD('email', 'email', 'caption=Имейл, mandatory');
        $this->FLD('name', 'varchar(128)', 'caption=Имена, oldFieldName=names');
        $this->FLD('company', 'varchar(128)', 'caption=Фирма');
        $this->FLD('ip', 'ip', 'caption=IP, input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър, input=none');
        
        $this->setDbUnique('bulletinId, email');
    }
    
    
    /**
     * 
     * 
     * @param integer $bId
     * @param string $email
     * @param string $name
     * @param string $company
     */
    public static function addData($bId, $email, $name = '', $company = '')
    {
        // Проверява дали имейла е валиден, за да може да се запише
        if (!$email || !type_Email::isValidEmail($email)) {
            
            return ;
        }
        
        // Добавяме данните към `brid` в модела
        $userData = array('email' => $email);
        if ($company) {
            $userData['company'] = $company;
        }
        if ($name) {
            $userData['name'] = $name;
        }
        core_Browser::setVars($userData);
        
        if (!self::fetch(array("#bulletinId = '[#1#]' AND #email='[#2#]'", $bId, $email))) {
            $rec = new stdClass();
            $rec->bulletinId = $bId;
            $rec->email = $email;
            $rec->name = $name;
            $rec->company = $company;
            $rec->ip = core_Users::getRealIpAddr();
            $rec->brid = core_Browser::getBrid();
            
            self::save($rec);
        }
    }

    
    /**
     * 
     * 
     * @param marketing_BulletinSubscribers $mvc
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	// Оцветяваме BRID
    	$row->brid = core_Browser::getLink($rec->brid);
    	
        if ($rec->ip) {
        	// Декорираме IP-то
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE);
    	}
    }
    
    
    /**
     * Проверява дали има регистрация от това IP
     * 
     * @param IP|NULL $ip
     * 
     * @return boolean
     */
    public static function haveRecForIp($bId, $ip=NULL)
    {
        if (!$ip) {
            $ip = core_Users::getRealIpAddr();
        }
        
        $rec = self::fetch(array("#bulletinId = '[#1#]' AND #ip = '[#2#]'", $bId, $ip));
        
        return (boolean)$rec;
    }
    
    
    /**
     * 
     * 
     * @param marketing_Bulletin $mvc
     * @param object $data
     * @param object $res
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
}
