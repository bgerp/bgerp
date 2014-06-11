<?php


/**
 * 
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_LoginLog extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Логин лог на потребителите";
    
    
    /**
     * 
     */
    var $canSingle = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'createdOn';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_Created, plg_GroupByDate';
    
    
    /**
     * Кой може да види IP-то от последното логване
     */
    var $canViewlog = 'powerUser';
    
    
    /**
     * 
     */
    var $listFields = 'userId, status, ip, brid, createdOn, createdBy, timestamp';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('userId', 'user(select=nick, allowEmpty)', 'caption=Потребител, silent');
        $this->FLD('ip', 'ip', 'caption=IP');
        $this->FLD('brid', 'varchar', 'caption=BRID');
        $this->FLD('status', 'enum( 
        							success=Успешен,
									error=Грешка,
									block=Блокиран,
									reject=Оттеглен,
									draft=Чернова,
									missing_password=Липсва парола,
									wrong_password=Грешна парола,
									pass_reset=Ресетване на парола,
									pass_change=Промяна на парола,
									user_reg=Регистриране,
									user_activate=Активиране,
									change_nick=Промяна на ник,
									time_deviation=Отклонение във времето,
									used_timestamp=Използван timestamp
								  )', 'caption=Статус, silent');
        $this->FLD('timestamp', 'int', 'caption=Време, input=none');
        
        $this->setDbIndex('createdOn');
    }
    
    
    /**
     * Записва в лога опитите за логване
     * 
     * @param integer $userId
     * @param string $status
     * @param timestamp $time
     */
    static function add($userId, $status, $time=NULL)
    {
        $rec = new stdClass();
        $rec->userId = $userId;
        $rec->ip = core_Users::getRealIpAddr();
        $rec->status = $status;
        $rec->brid = core_Browser::getBrid();
        $rec->timestamp = $time;
        
        static::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Проверява дали timestamp-а е използван от съответния потребител за успешен вход
     * 
     * @param integer $userId
     * @param integer $timestamp
     * 
     * @return boolean
     */
    static function isTimestampUsed($userId, $timestamp)
    {
        $rec = static::fetch(array("#userId = '[#1#]' AND #timestamp = '[#2#]' AND #status='success'", $userId, $timestamp));
        
        if ($rec) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Проверява дали отклонението на подадения таймстамп е в границите на допустимото
     * 
     * @param integer $timestamp
     * 
     * @return boolean
     */
    static function isTimestampCorrect($timestamp)
    {
        $conf = core_Packs::getConfig('core');
        $maxDeviation = $conf->CORE_LOGIN_TIMESTAMP_DEVIATION;
        
        // Текущото време в таймстампа
        $nowTimestamp = dt::nowTimestamp();
        
        // Разликата между текущото време и зададенот
        $diff = abs($nowTimestamp - $timestamp);
        
        // Ако е в границите
        if ($maxDeviation > $diff) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща последните записи в лога за съответния потребител
     * 
     * @param integer $userId
     * @param integer $limit
     * @param array $statusArr
     * 
     * @return array
     */
    static function getLastAttempts($userId=NULL, $limit=5, $statusArr=array()) 
    {
        // Ако не е подаден потребител
        if ($userId == NULL) {
            $userId = core_Users::getCurrent();
        }
        
        $recsArr = array();
        
        // Всички записи за съответния потребител, подредени по дата
        $query = static::getQuery();
        $query->where("#userId = '{$userId}'");
        $query->orderBy('createdOn', 'DESC');
        
        // Ако е зададен лимит
        if ($limit) {
            $query->limit($limit);
        }
        
        // Ако е зададен масив със статуси
        if ($statusArr) {
            $query->orWhereArr('status', $statusArr);
        }
        
        while ($rec = $query->fetch()) {
            $recsArr[$rec->id] = $rec;
        }
        
        return $recsArr;
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->ip){
    	    $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
    	}
    }
    
    
    /**
     * 
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Ако имаме тип на обаждането
        if ($statusOptions = &$data->listFilter->getField('status')->type->options) {
            
            // Добавяме в началото празен стринг за всички
            $statusOptions = array('all' => '') + $statusOptions;
            
            // Избираме го по подразбиране
            $data->listFilter->setDefault('status', 'all');
        }
        
        // Кои полета да се показват
        $data->listFilter->showFields = 'userId, status';
        
        // Инпутваме заявката
        $data->listFilter->input('userId, status', 'silent');
        
        // Сортиране на записите по създаване
        $data->query->orderBy('createdOn', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако се търси по потребител
            if ($filter->userId) {
                $data->query->where(array("#userId = '[#1#]'", $filter->userId));
            }
            
            // Ако се търси по статус
            if ($filter->status && $filter->status != 'all') {
                $data->query->where(array("#status = '[#1#]'", $filter->status));
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param object $rec
     * @param id $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Текущия потребител може да си види записите от лога, admin и ceo могат на всичките
        if ($action == 'viewlog') {
            if ($rec && ($rec->userId != $userId)) {
                if (!haveRole('ceo, admin')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
