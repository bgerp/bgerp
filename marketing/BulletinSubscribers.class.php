<?php


/**
 * Детайл на бюлетените
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.12
 */
class marketing_BulletinSubscribers extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'bulletinId';
    
    
    public $title = 'Абонати за бюлетин';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Абонамент за бюлетин';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'plg_RowTools, plg_Created, marketing_Wrapper';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'ceo, marketing';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'ceo, marketing';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, marketing';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'ceo, marketing';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, marketing';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'ceo, marketing';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    public $listItemsPerPage = 20;
    
    
    public $listFields = 'id, email, ip, brid, createdOn, createdBy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('bulletinId', 'key(mvc=marketing_Bulletins, select=domain)', 'input=hidden,silent');
        $this->FLD('email', 'email', 'caption=Имейл, mandatory, export');
        $this->FLD('ip', 'ip', 'caption=IP');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър');
        
        $this->setDbUnique('bulletinId, email');
    }
    
    
    /**
     *
     *
     * @param int    $bId
     * @param string $email
     */
    public static function addData($bId, $email)
    {
        // Проверява дали имейла е валиден, за да може да се запише
        if (!$email || !type_Email::isValidEmail($email)) {
            
            return ;
        }
        
        // Добавяме данните към `brid` в модела
        $userData = array('email' => $email);
        log_Browsers::setVars($userData);
        
        $domain = marketing_Bulletins::fetchField((int) $bId, 'domain');
        
        if (!self::fetch(array("#bulletinId = '[#1#]' AND #email='[#2#]'", $bId, $email))) {
            vislog_History::add('Нов абонамент за бюлетин ' . $domain);
            
            $rec = new stdClass();
            $rec->bulletinId = $bId;
            $rec->email = $email;
            $rec->ip = core_Users::getRealIpAddr();
            $rec->brid = log_Browsers::getBrid();
            
            self::save($rec);
        } else {
            vislog_History::add('Дублиран абонамент за бюлетин ' . $domain);
        }
        
        // Разблокираме имейла, ако е бил блокиран
        blast_BlockedEmails::addEmail($email, 'force', 'ok');
    }
    
    
    /**
     *
     *
     * @param marketing_BulletinSubscribers $mvc
     * @param object                        $row
     * @param object                        $rec
     * @param array                         $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // Оцветяваме BRID
        $row->brid = log_Browsers::getLink($rec->brid);
        
        if ($rec->ip) {
            // Декорираме IP-то
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, true);
        }
    }
    
    
    /**
     * Проверява дали има регистрация от това IP
     *
     * @param IP|NULL $ip
     *
     * @return bool
     */
    public static function haveRecForIp($bId, $ip = null)
    {
        if (!$ip) {
            $ip = core_Users::getRealIpAddr();
        }
        
        $rec = self::fetch(array("#bulletinId = '[#1#]' AND #ip = '[#2#]'", $bId, $ip));
        
        return (boolean) $rec;
    }
    
    
    /**
     *
     *
     * @param marketing_BulletinSubscribers $mvc
     * @param object                        $data
     * @param object                        $res
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
}
